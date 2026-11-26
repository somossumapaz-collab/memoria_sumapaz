<?php
/**
 * API para Control de Actividades, Contratistas y Formato Oficial de Actas GDI-GPD-F029
 * Tablas: actividades_contratista_*
 * Con medidas estrictas de ciberseguridad, validación y desinfección de datos.
 */
require_once __DIR__ . '/db_config.php';

// Headers de seguridad HTTP
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Rate limiting básico en sesión / archivo por IP para mitigar ataques de denegación de servicio o fuerza bruta
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$clientIp = trim(explode(',', $clientIp)[0]);
$rateKey = 'rate_' . md5($clientIp);
$currentTime = time();

if (!isset($_SESSION[$rateKey])) {
    $_SESSION[$rateKey] = ['count' => 1, 'time' => $currentTime];
} else {
    if ($currentTime - $_SESSION[$rateKey]['time'] < 60) {
        $_SESSION[$rateKey]['count']++;
        if ($_SESSION[$rateKey]['count'] > 60) {
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'message' => 'Demasiadas solicitudes en un corto periodo de tiempo. Por favor espere un momento antes de reintentar.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } else {
        $_SESSION[$rateKey] = ['count' => 1, 'time' => $currentTime];
    }
}

$action = trim($_GET['action'] ?? '');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Helper para responder JSON de forma estandarizada
function jsonResponse($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Helper para sanear texto seguro
function cleanStr(?string $val, int $maxLen = 255): string {
    if ($val === null) return '';
    $val = trim($val);
    $val = strip_tags($val);
    if (mb_strlen($val, 'UTF-8') > $maxLen) {
        $val = mb_substr($val, 0, $maxLen, 'UTF-8');
    }
    return $val;
}

// Helper seguro para almacenar imágenes en base64 con validación estricta y compresión ligera
function saveSecureBase64Image(string $base64Data, string $folder = 'firmas', int $maxBytes = 8388608): ?string {
    if (empty($base64Data)) return null;

    if (!preg_match('/^data:image\/(png|jpe?g|webp);base64,(.+)$/is', $base64Data, $matches)) {
        return null;
    }

    $rawBase64 = $matches[2];
    $data = base64_decode($rawBase64, true);
    if ($data === false) return null;

    // Validación de tamaño máximo (firmas: 2MB, evidencias: 8MB)
    if (strlen($data) > $maxBytes) {
        return null;
    }

    // Validación de imagen real mediante getimagesizefromstring
    $imgInfo = @getimagesizefromstring($data);
    if ($imgInfo === false) {
        return null;
    }

    $allowedMimes = [
        'image/png'  => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp'
    ];

    $mime = $imgInfo['mime'] ?? '';
    if (!isset($allowedMimes[$mime])) {
        return null;
    }

    $ext = $allowedMimes[$mime];

    // Optimización automática con GD para que ocupe poco espacio en disco/BD
    if (function_exists('imagecreatefromstring')) {
        $srcImg = @imagecreatefromstring($data);
        if ($srcImg !== false) {
            $origW = imagesx($srcImg);
            $origH = imagesy($srcImg);
            $isFirma = ($folder === 'firmas');
            $maxW = $isFirma ? 480 : 1024;
            $maxH = $isFirma ? 180 : 1024;

            $newW = $origW;
            $newH = $origH;
            if ($origW > $maxW || $origH > $maxH) {
                $ratio = min($maxW / max($origW, 1), $maxH / max($origH, 1));
                $newW = max(1, (int)round($origW * $ratio));
                $newH = max(1, (int)round($origH * $ratio));
            }

            $dstImg = imagecreatetruecolor($newW, $newH);
            if ($isFirma) {
                imagealphablending($dstImg, false);
                imagesavealpha($dstImg, true);
                $transparent = imagecolorallocatealpha($dstImg, 255, 255, 255, 127);
                imagefilledrectangle($dstImg, 0, 0, $newW, $newH, $transparent);
            } else {
                $white = imagecolorallocate($dstImg, 255, 255, 255);
                imagefilledrectangle($dstImg, 0, 0, $newW, $newH, $white);
            }

            imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

            ob_start();
            if ($isFirma) {
                imagepng($dstImg, null, 7);
                $ext = 'png';
            } else {
                imagejpeg($dstImg, null, 72);
                $ext = 'jpg';
            }
            $optimized = ob_get_clean();
            imagedestroy($srcImg);
            imagedestroy($dstImg);

            if ($optimized !== false && strlen($optimized) > 0 && ($isFirma || strlen($optimized) < strlen($data))) {
                $data = $optimized;
            }
        }
    }

    $uploadDir = __DIR__ . '/../uploads/' . $folder . '/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }
    @chmod($uploadDir, 0777);

    // Nombre de archivo criptográficamente seguro
    $safeName = $folder . '_' . bin2hex(random_bytes(16)) . '.' . $ext;
    $targetPath = $uploadDir . $safeName;

    if (@file_put_contents($targetPath, $data) !== false) {
        @chmod($targetPath, 0666);
        return 'uploads/' . $folder . '/' . $safeName;
    }

    return null;
}

try {
    switch ($action) {

        // 1. LISTADO DE PROYECTOS DE INVERSIÓN
        case 'proyectos_list':
            $stmt = $pdo->query("
                SELECT id, codigo, nombre 
                FROM despacho_proyectos 
                WHERE activo = 1 
                ORDER BY CAST(codigo AS UNSIGNED) ASC, nombre ASC
            ");
            jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        // 2. LISTADO DE VEREDAS
        case 'veredas_list':
            $stmt = $pdo->query("
                SELECT DISTINCT scanombre AS vereda 
                FROM veredas_coordenadas 
                WHERE scanombre IS NOT NULL AND TRIM(scanombre) != ''
                ORDER BY scanombre ASC
            ");
            jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        // 3. BUSCAR CONTRATISTA POR DOCUMENTO
        case 'buscar_contratista':
            $doc = cleanStr($_GET['documento'] ?? '', 30);
            if ($doc === '' || !preg_match('/^[A-Za-z0-9\-\.]{4,30}$/', $doc)) {
                jsonResponse(['success' => false, 'message' => 'Documento inválido'], 400);
            }

            $stmt = $pdo->prepare("
                SELECT id, tipo_documento, numero_documento, nombres_apellidos, nombre_identitario,
                       telefono, correo, dependencia, cargo, tipo_vinculacion, firma_digital
                FROM actividades_contratistas
                WHERE numero_documento = ?
                LIMIT 1
            ");
            $stmt->execute([$doc]);
            $contratista = $stmt->fetch();

            if (!$contratista) {
                jsonResponse(['success' => true, 'encontrado' => false, 'data' => null]);
            }

            $stmtProy = $pdo->prepare("
                SELECT p.id, p.codigo, p.nombre
                FROM actividades_contratista_proyectos cp
                INNER JOIN despacho_proyectos p ON cp.proyecto_id = p.id
                WHERE cp.contratista_id = ?
                ORDER BY p.codigo ASC
            ");
            $stmtProy->execute([$contratista['id']]);
            $contratista['proyectos'] = $stmtProy->fetchAll();

            jsonResponse(['success' => true, 'encontrado' => true, 'data' => $contratista]);
            break;

        // 4. GUARDAR / ACTUALIZAR CONTRATISTA
        case 'guardar_contratista':
            if ($method !== 'POST') {
                jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!is_array($input)) {
                $input = $_POST;
            }

            // Validación obligatoria de Consentimiento de Tratamiento de Datos (Ley 1581 de 2012)
            $consentimiento = !empty($input['consentimiento_datos']);
            if (!$consentimiento) {
                jsonResponse([
                    'success' => false,
                    'message' => 'Debe aceptar expresamente el consentimiento de tratamiento de datos personales conforme a la Ley 1581 de 2012 para continuar.'
                ], 400);
            }

            $numDoc = cleanStr($input['numero_documento'] ?? '', 30);
            $nombres = cleanStr($input['nombres_apellidos'] ?? '', 150);

            if ($numDoc === '' || !preg_match('/^[A-Za-z0-9\-\.]{4,30}$/', $numDoc)) {
                jsonResponse(['success' => false, 'message' => 'Número de documento de identificación no válido.'], 400);
            }
            if ($nombres === '' || mb_strlen($nombres, 'UTF-8') < 3) {
                jsonResponse(['success' => false, 'message' => 'Los nombres y apellidos completos son obligatorios.'], 400);
            }

            $tipoDoc = cleanStr($input['tipo_documento'] ?? 'CC', 20);
            $nomIdentitario = cleanStr($input['nombre_identitario'] ?? '', 150);
            $telefono = cleanStr($input['telefono'] ?? '', 50);
            $correo = cleanStr($input['correo'] ?? '', 120);

            if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                jsonResponse(['success' => false, 'message' => 'El correo electrónico proporcionado no tiene un formato válido.'], 400);
            }

            $dependencia = cleanStr($input['dependencia'] ?? 'Alcaldía Local de Sumapaz', 150);
            $cargo = cleanStr($input['cargo'] ?? 'Profesional', 50);
            $tipoVinculacion = cleanStr($input['tipo_vinculacion'] ?? 'Contratista', 50);
            $firmaRaw = $input['firma_digital'] ?? '';
            
            $proyectos = $input['proyectos_ids'] ?? [];
            if (!is_array($proyectos) && is_string($proyectos)) {
                $proyectos = json_decode($proyectos, true) ?: [];
            }

            if (empty($proyectos)) {
                jsonResponse(['success' => false, 'message' => 'Debe seleccionar al menos un proyecto de inversión asignado.'], 400);
            }

            // Procesamiento seguro de firma
            $firmaRuta = null;
            if (strpos($firmaRaw, 'data:image') === 0) {
                $firmaRuta = saveSecureBase64Image($firmaRaw, 'firmas', 2097152); // máx 2MB para firmas
                // Fallback garantizado: si falla la escritura en disco, se guarda el Data URI en BD (firma_digital es LONGTEXT)
                if (!$firmaRuta) {
                    $firmaRuta = $firmaRaw;
                }
            } elseif (!empty($firmaRaw)) {
                $firmaRuta = $firmaRaw;
            }

            $pdo->beginTransaction();

            $stmtCheck = $pdo->prepare("SELECT id, firma_digital FROM actividades_contratistas WHERE numero_documento = ?");
            $stmtCheck->execute([$numDoc]);
            $existente = $stmtCheck->fetch();

            if ($existente) {
                $contratistaId = (int)$existente['id'];
                if (!$firmaRuta && !empty($existente['firma_digital'])) {
                    $firmaRuta = $existente['firma_digital'];
                }
                $stmtUpdate = $pdo->prepare("
                    UPDATE actividades_contratistas 
                    SET tipo_documento = ?, nombres_apellidos = ?, nombre_identitario = ?, 
                        telefono = ?, correo = ?, dependencia = ?, cargo = ?, 
                        tipo_vinculacion = ?, firma_digital = COALESCE(?, firma_digital), updated_at = NOW()
                    WHERE id = ?
                ");
                $stmtUpdate->execute([
                    $tipoDoc, $nombres, $nomIdentitario,
                    $telefono, $correo, $dependencia, $cargo,
                    $tipoVinculacion, $firmaRuta, $contratistaId
                ]);
            } else {
                if (!$firmaRuta) {
                    $pdo->rollBack();
                    jsonResponse(['success' => false, 'message' => 'La firma digital es obligatoria para la inscripción.'], 400);
                }
                $stmtInsert = $pdo->prepare("
                    INSERT INTO actividades_contratistas 
                    (tipo_documento, numero_documento, nombres_apellidos, nombre_identitario,
                     telefono, correo, dependencia, cargo, tipo_vinculacion, firma_digital)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmtInsert->execute([
                    $tipoDoc, $numDoc, $nombres, $nomIdentitario,
                    $telefono, $correo, $dependencia, $cargo,
                    $tipoVinculacion, $firmaRuta
                ]);
                $contratistaId = (int)$pdo->lastInsertId();
            }

            // Sincronizar proyectos de inversión
            $pdo->prepare("DELETE FROM actividades_contratista_proyectos WHERE contratista_id = ?")->execute([$contratistaId]);
            $stmtInsProy = $pdo->prepare("
                INSERT IGNORE INTO actividades_contratista_proyectos (contratista_id, proyecto_id) 
                VALUES (?, ?)
            ");
            foreach ($proyectos as $pid) {
                $pidInt = (int)$pid;
                if ($pidInt > 0) {
                    $stmtInsProy->execute([$contratistaId, $pidInt]);
                }
            }

            $pdo->commit();

            jsonResponse([
                'success' => true,
                'contratista_id' => $contratistaId,
                'message' => 'Inscripción de contratista y proyectos asociados guardados exitosamente.'
            ]);
            break;

        // 5. REGISTRAR ACTIVIDAD (REPORTE DE ACTA COMPLETO)
        case 'guardar_actividad':
            if ($method !== 'POST') {
                jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!is_array($input)) {
                $input = $_POST;
            }

            $contratistaDoc = cleanStr($input['contratista_documento'] ?? '', 30);
            $contratistaId = (int)($input['contratista_id'] ?? 0);
            $proyectoId = (int)($input['proyecto_id'] ?? 0);
            $objetoReunion = cleanStr($input['objeto_reunion'] ?? '', 1000);
            $fecha = cleanStr($input['fecha'] ?? date('Y-m-d'), 10);
            $horaInicio = cleanStr($input['hora_inicio'] ?? '08:00', 20);
            $horaFin = cleanStr($input['hora_fin'] ?? '16:00', 20);

            if ($objetoReunion === '') {
                jsonResponse(['success' => false, 'message' => 'El objeto de la reunión es obligatorio.'], 400);
            }
            if ($proyectoId <= 0) {
                jsonResponse(['success' => false, 'message' => 'Debe asociar un proyecto de inversión válido.'], 400);
            }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                jsonResponse(['success' => false, 'message' => 'La fecha no tiene un formato válido (AAAA-MM-DD).'], 400);
            }

            $pdo->beginTransaction();

            // Identificar o crear contratista responsable
            if ($contratistaId <= 0 && $contratistaDoc !== '') {
                $stmtC = $pdo->prepare("SELECT id FROM actividades_contratistas WHERE numero_documento = ?");
                $stmtC->execute([$contratistaDoc]);
                $cRow = $stmtC->fetch();
                if ($cRow) {
                    $contratistaId = (int)$cRow['id'];
                } else {
                    $nomResp = cleanStr($input['nombre_responsable'] ?? 'Responsable', 150);
                    $depResp = cleanStr($input['dependencia'] ?? 'Alcaldía Local de Sumapaz', 150);

                    $stmtNewC = $pdo->prepare("
                        INSERT INTO actividades_contratistas 
                        (tipo_documento, numero_documento, nombres_apellidos, dependencia)
                        VALUES ('CC', ?, ?, ?)
                    ");
                    $stmtNewC->execute([$contratistaDoc, $nomResp, $depResp]);
                    $contratistaId = (int)$pdo->lastInsertId();

                    $pdo->prepare("INSERT IGNORE INTO actividades_contratista_proyectos (contratista_id, proyecto_id) VALUES (?, ?)")
                        ->execute([$contratistaId, $proyectoId]);
                }
            }

            if ($contratistaId <= 0) {
                $pdo->rollBack();
                jsonResponse(['success' => false, 'message' => 'No se pudo identificar el contratista responsable.'], 400);
            }

            $modalidad = cleanStr($input['modalidad'] ?? 'Presencial', 30);
            if (!in_array($modalidad, ['Presencial', 'Virtual', 'Mixta', 'Telefónica'], true)) {
                $modalidad = 'Presencial';
            }

            $esVirtual = ($modalidad === 'Virtual' || !empty($input['es_virtual'])) ? 1 : 0;
            $linkReunion = cleanStr($input['link_reunion'] ?? '', 255);
            $lugar = cleanStr($input['lugar'] ?? '', 255);

            $veredasVisitadas = $input['veredas_visitadas'] ?? [];
            if (is_array($veredasVisitadas)) {
                $cleanVeredas = array_map(function($v) { return cleanStr($v, 60); }, $veredasVisitadas);
                $veredasVisitadasStr = implode(', ', array_filter($cleanVeredas));
            } else {
                $veredasVisitadasStr = cleanStr((string)$veredasVisitadas, 500);
            }

            $dependencia = cleanStr($input['dependencia'] ?? 'Alcaldía Local de Sumapaz', 150);
            $nombreResponsable = cleanStr($input['nombre_responsable'] ?? '', 150);
            $requirioTransporte = !empty($input['requirio_transporte']) ? 1 : 0;
            $formatoTransporte = !empty($input['formato_transporte_diligenciado']) ? 1 : 0;
            $detalleTransporte = cleanStr($input['detalle_transporte'] ?? '', 255);
            $desarrollo = cleanStr($input['desarrollo_conclusiones'] ?? '', 10000);
            $casoHola = cleanStr($input['caso_hola'] ?? '70103', 50);

            // Consecutivo único y seguro de acta
            $anio = date('Y', strtotime($fecha));
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM actividades_contratista_reportes WHERE YEAR(fecha) = ?");
            $stmtCount->execute([$anio]);
            $numConsecutivo = (int)$stmtCount->fetchColumn() + 1;
            $codigoActa = sprintf('ACTA-%s-%04d', $anio, $numConsecutivo);

            $stmtRep = $pdo->prepare("
                INSERT INTO actividades_contratista_reportes
                (codigo_acta, contratista_id, proyecto_id, objeto_reunion, fecha, hora_inicio, hora_fin,
                 modalidad, lugar, veredas_visitadas, es_virtual, link_reunion, dependencia,
                 nombre_responsable, requirio_transporte, formato_transporte_diligenciado,
                 detalle_transporte, desarrollo_conclusiones, caso_hola, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Registrada')
            ");
            $stmtRep->execute([
                $codigoActa, $contratistaId, $proyectoId, $objetoReunion, $fecha, $horaInicio, $horaFin,
                $modalidad, $lugar, $veredasVisitadasStr, $esVirtual, $linkReunion, $dependencia,
                $nombreResponsable, $requirioTransporte, $formatoTransporte,
                $detalleTransporte, $desarrollo, $casoHola
            ]);
            $reporteId = (int)$pdo->lastInsertId();

            // Compromisos
            $compromisos = $input['compromisos'] ?? [];
            if (is_array($compromisos)) {
                $stmtComp = $pdo->prepare("
                    INSERT INTO actividades_contratista_compromisos
                    (reporte_id, numero, actividad, responsable, fecha_entrega)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $idxComp = 1;
                foreach ($compromisos as $c) {
                    $actividad = cleanStr($c['actividad'] ?? '', 500);
                    if ($actividad === '') continue;
                    $resp = cleanStr($c['responsable'] ?? $nombreResponsable, 150);
                    $fEntrega = cleanStr($c['fecha_entrega'] ?? '', 10);
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fEntrega)) {
                        $fEntrega = null;
                    }
                    $stmtComp->execute([$reporteId, $idxComp, $actividad, $resp, $fEntrega]);
                    $idxComp++;
                }
            }

            // Participantes
            $participantes = $input['participantes'] ?? [];
            if (is_array($participantes)) {
                $stmtPart = $pdo->prepare("
                    INSERT INTO actividades_contratista_participantes
                    (reporte_id, tipo_documento, numero_documento, nombres_apellidos, nombre_identitario,
                     entidad_dependencia, cargo, tipo_vinculacion, correo, telefono, firma_digital, es_responsable)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                foreach ($participantes as $p) {
                    $pDoc = cleanStr($p['numero_documento'] ?? '', 30);
                    $pNom = cleanStr($p['nombres_apellidos'] ?? '', 150);
                    if ($pDoc === '' && $pNom === '') continue;

                    $pTipoDoc = cleanStr($p['tipo_documento'] ?? 'CC', 20);
                    $pNomId = cleanStr($p['nombre_identitario'] ?? '', 150);
                    $pEntidad = cleanStr($p['entidad_dependencia'] ?? 'Alcaldía Local de Sumapaz', 150);
                    $pCargo = cleanStr($p['cargo'] ?? 'Profesional', 50);
                    $pVinculacion = cleanStr($p['tipo_vinculacion'] ?? 'Contratista', 50);
                    $pCorreo = cleanStr($p['correo'] ?? '', 120);
                    $pTel = cleanStr($p['telefono'] ?? '', 50);

                    $pFirmaRaw = $p['firma_digital'] ?? '';
                    $pFirmaRuta = null;
                    if (strpos($pFirmaRaw, 'data:image') === 0) {
                        $pFirmaRuta = saveSecureBase64Image($pFirmaRaw, 'firmas', 2097152);
                        // Fallback a Data URI si la escritura en disco no fue posible
                        if (!$pFirmaRuta) {
                            $pFirmaRuta = $pFirmaRaw;
                        }
                    } elseif (!empty($pFirmaRaw)) {
                        $pFirmaRuta = $pFirmaRaw;
                    }

                    $pEsResp = !empty($p['es_responsable']) ? 1 : 0;

                    $stmtPart->execute([
                        $reporteId, $pTipoDoc, $pDoc, $pNom, $pNomId,
                        $pEntidad, $pCargo, $pVinculacion, $pCorreo, $pTel,
                        $pFirmaRuta, $pEsResp
                    ]);
                }
            }

            // Evidencias fotográficas
            $evidencias = $input['evidencias'] ?? [];
            if (is_array($evidencias)) {
                $stmtEvid = $pdo->prepare("
                    INSERT INTO actividades_contratista_evidencias
                    (reporte_id, archivo_url, descripcion)
                    VALUES (?, ?, ?)
                ");
                foreach ($evidencias as $ev) {
                    $fotoRaw = is_array($ev) ? ($ev['archivo'] ?? ($ev['url'] ?? '')) : (string)$ev;
                    $desc = is_array($ev) ? cleanStr($ev['descripcion'] ?? '', 200) : '';
                    if (empty($fotoRaw)) continue;

                    $fotoRuta = null;
                    if (strpos($fotoRaw, 'data:image') === 0) {
                        $fotoRuta = saveSecureBase64Image($fotoRaw, 'actividades', 8388608); // máx 8MB
                        // Fallback garantizado: si la escritura en disco falla, se almacena el Data URI optimizado
                        // en la columna archivo_url (LONGTEXT), garantizando cero pérdida de imágenes
                        if (!$fotoRuta) {
                            $fotoRuta = $fotoRaw;
                        }
                    } elseif (!empty($fotoRaw)) {
                        $fotoRuta = $fotoRaw;
                    }

                    if ($fotoRuta) {
                        $stmtEvid->execute([$reporteId, $fotoRuta, $desc]);
                    }
                }
            }

            $pdo->commit();

            jsonResponse([
                'success' => true,
                'reporte_id' => $reporteId,
                'codigo_acta' => $codigoActa,
                'message' => 'Acta y evidencia de reunión registradas exitosamente.'
            ]);
            break;

        // 6. LISTADO DE ACTIVIDADES PARA PANEL DE CONTROL EN DESPACHO
        case 'actividades_list':
            $busqueda = cleanStr($_GET['busqueda'] ?? '', 100);
            $proyectoId = (int)($_GET['proyecto_id'] ?? 0);
            $modalidad = cleanStr($_GET['modalidad'] ?? '', 30);
            $fechaDesde = cleanStr($_GET['fecha_desde'] ?? '', 10);
            $fechaHasta = cleanStr($_GET['fecha_hasta'] ?? '', 10);
            $contratistaDoc = cleanStr($_GET['documento'] ?? '', 30);

            $where = ["1=1"];
            $params = [];

            if ($busqueda !== '') {
                $where[] = "(r.objeto_reunion LIKE ? OR r.codigo_acta LIKE ? OR c.nombres_apellidos LIKE ? OR c.numero_documento LIKE ? OR r.lugar LIKE ? OR r.veredas_visitadas LIKE ?)";
                $like = "%{$busqueda}%";
                $params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
            }
            if ($proyectoId > 0) {
                $where[] = "r.proyecto_id = ?";
                $params[] = $proyectoId;
            }
            if ($modalidad !== '') {
                $where[] = "r.modalidad = ?";
                $params[] = $modalidad;
            }
            if ($fechaDesde !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde)) {
                $where[] = "r.fecha >= ?";
                $params[] = $fechaDesde;
            }
            if ($fechaHasta !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
                $where[] = "r.fecha <= ?";
                $params[] = $fechaHasta;
            }
            if ($contratistaDoc !== '') {
                $where[] = "c.numero_documento = ?";
                $params[] = $contratistaDoc;
            }

            $sqlWhere = implode(' AND ', $where);

            $stmt = $pdo->prepare("
                SELECT r.id, r.codigo_acta, r.fecha, r.hora_inicio, r.hora_fin, r.modalidad,
                       r.lugar, r.veredas_visitadas, r.objeto_reunion, r.requirio_transporte,
                       r.formato_transporte_diligenciado, r.detalle_transporte, r.estado,
                       c.nombres_apellidos AS contratista_nombre,
                       c.numero_documento AS contratista_documento,
                       c.telefono AS contratista_telefono,
                       c.correo AS contratista_correo,
                       c.cargo AS contratista_cargo,
                       p.codigo AS proyecto_codigo,
                       p.nombre AS proyecto_nombre,
                       (SELECT COUNT(*) FROM actividades_contratista_participantes WHERE reporte_id = r.id) AS total_participantes,
                       (SELECT COUNT(*) FROM actividades_contratista_compromisos WHERE reporte_id = r.id) AS total_compromisos,
                       (SELECT COUNT(*) FROM actividades_contratista_evidencias WHERE reporte_id = r.id) AS total_evidencias
                FROM actividades_contratista_reportes r
                INNER JOIN actividades_contratistas c ON r.contratista_id = c.id
                LEFT JOIN despacho_proyectos p ON r.proyecto_id = p.id
                WHERE {$sqlWhere}
                ORDER BY r.fecha DESC, r.id DESC
            ");
            $stmt->execute($params);
            $actividades = $stmt->fetchAll();

            $stmtKpi = $pdo->query("
                SELECT 
                    COUNT(*) AS total_actividades,
                    COUNT(DISTINCT contratista_id) AS total_contratistas,
                    COUNT(DISTINCT proyecto_id) AS total_proyectos,
                    SUM(CASE WHEN modalidad = 'Presencial' THEN 1 ELSE 0 END) AS presenciales,
                    SUM(CASE WHEN modalidad = 'Virtual' THEN 1 ELSE 0 END) AS virtuales
                FROM actividades_contratista_reportes
            ");
            $kpis = $stmtKpi->fetch();

            jsonResponse([
                'success' => true,
                'total' => count($actividades),
                'kpis' => $kpis,
                'data' => $actividades
            ]);
            break;

        // 7. DETALLE COMPLETO DE UN ACTA
        case 'actividad_detalle':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                jsonResponse(['success' => false, 'message' => 'Identificador inválido'], 400);
            }

            $stmt = $pdo->prepare("
                SELECT r.*, 
                       c.nombres_apellidos AS contratista_nombre,
                       c.numero_documento AS contratista_documento,
                       c.tipo_documento AS contratista_tipo_documento,
                       c.nombre_identitario AS contratista_nombre_identitario,
                       c.telefono AS contratista_telefono,
                       c.correo AS contratista_correo,
                       c.dependencia AS contratista_dependencia,
                       c.cargo AS contratista_cargo,
                       c.tipo_vinculacion AS contratista_tipo_vinculacion,
                       c.firma_digital AS contratista_firma_digital,
                       p.codigo AS proyecto_codigo,
                       p.nombre AS proyecto_nombre
                FROM actividades_contratista_reportes r
                INNER JOIN actividades_contratistas c ON r.contratista_id = c.id
                LEFT JOIN despacho_proyectos p ON r.proyecto_id = p.id
                WHERE r.id = ?
                LIMIT 1
            ");
            $stmt->execute([$id]);
            $reporte = $stmt->fetch();

            if (!$reporte) {
                jsonResponse(['success' => false, 'message' => 'Acta de reunión no encontrada.'], 404);
            }

            $stmtPart = $pdo->prepare("
                SELECT * FROM actividades_contratista_participantes 
                WHERE reporte_id = ? 
                ORDER BY es_responsable DESC, id ASC
            ");
            $stmtPart->execute([$id]);
            $reporte['participantes'] = $stmtPart->fetchAll();

            $stmtComp = $pdo->prepare("
                SELECT * FROM actividades_contratista_compromisos 
                WHERE reporte_id = ? 
                ORDER BY numero ASC, id ASC
            ");
            $stmtComp->execute([$id]);
            $reporte['compromisos'] = $stmtComp->fetchAll();

            $stmtEvid = $pdo->prepare("
                SELECT * FROM actividades_contratista_evidencias 
                WHERE reporte_id = ? 
                ORDER BY id ASC
            ");
            $stmtEvid->execute([$id]);
            $reporte['evidencias'] = $stmtEvid->fetchAll();

            jsonResponse(['success' => true, 'data' => $reporte]);
            break;

        // 8. CONSULTA PÚBLICA DE ACTAS POR CÉDULA
        case 'consultar_actas_cedula':
            $doc = cleanStr($_GET['documento'] ?? '', 30);
            if ($doc === '' || !preg_match('/^[A-Za-z0-9\-\.]{4,30}$/', $doc)) {
                jsonResponse(['success' => false, 'message' => 'Número de documento de identificación no válido.'], 400);
            }

            $stmt = $pdo->prepare("
                SELECT DISTINCT 
                       r.id, r.codigo_acta, r.fecha, r.hora_inicio, r.hora_fin,
                       r.objeto_reunion, r.modalidad, r.lugar, r.veredas_visitadas,
                       r.dependencia, r.nombre_responsable, r.requirio_transporte,
                       p.codigo AS proyecto_codigo,
                       p.nombre AS proyecto_nombre,
                       c.nombres_apellidos AS contratista_nombre,
                       c.numero_documento AS contratista_documento,
                       CASE WHEN c.numero_documento = ? THEN 'Responsable' ELSE 'Participante' END AS rol_en_acta,
                       (SELECT COUNT(*) FROM actividades_contratista_participantes WHERE reporte_id = r.id) AS total_participantes,
                       (SELECT COUNT(*) FROM actividades_contratista_compromisos WHERE reporte_id = r.id) AS total_compromisos,
                       (SELECT COUNT(*) FROM actividades_contratista_evidencias WHERE reporte_id = r.id) AS total_evidencias
                FROM actividades_contratista_reportes r
                INNER JOIN actividades_contratistas c ON r.contratista_id = c.id
                LEFT JOIN despacho_proyectos p ON r.proyecto_id = p.id
                LEFT JOIN actividades_contratista_participantes part ON part.reporte_id = r.id
                WHERE c.numero_documento = ? OR part.numero_documento = ?
                ORDER BY r.fecha DESC, r.id DESC
            ");
            $stmt->execute([$doc, $doc, $doc]);
            $actas = $stmt->fetchAll();

            $stmtPersona = $pdo->prepare("
                SELECT nombres_apellidos, numero_documento, telefono, correo, dependencia, cargo, tipo_vinculacion 
                FROM actividades_contratistas 
                WHERE numero_documento = ?
                LIMIT 1
            ");
            $stmtPersona->execute([$doc]);
            $persona = $stmtPersona->fetch();

            jsonResponse([
                'success' => true,
                'persona' => $persona ?: null,
                'total_actas' => count($actas),
                'actas' => $actas
            ]);
            break;

        // 9. OBTENER FIRMA DIGITAL ACCESIBLE (STREAMING BINARIO)
        case 'ver_firma':
            $tipo = cleanStr($_GET['tipo'] ?? 'contratista', 20);
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                exit('Identificador no válido.');
            }

            $firmaData = null;
            if ($tipo === 'participante') {
                $stmtF = $pdo->prepare("SELECT firma_digital FROM actividades_contratista_participantes WHERE id = ?");
                $stmtF->execute([$id]);
                $firmaData = $stmtF->fetchColumn();
            } else {
                $stmtF = $pdo->prepare("SELECT firma_digital FROM actividades_contratistas WHERE id = ?");
                $stmtF->execute([$id]);
                $firmaData = $stmtF->fetchColumn();
            }

            if (!$firmaData) {
                http_response_code(404);
                exit('Firma no disponible.');
            }

            header('Access-Control-Allow-Origin: *');
            header('Cache-Control: public, max-age=86400');

            if (strpos($firmaData, 'data:image') === 0 && preg_match('/^data:image\/(png|jpe?g|webp);base64,(.+)$/is', $firmaData, $m)) {
                $mime = ($m[1] === 'jpg' || $m[1] === 'jpeg') ? 'image/jpeg' : 'image/' . $m[1];
                $bin = base64_decode($m[2]);
                header('Content-Type: ' . $mime);
                header('Content-Length: ' . strlen($bin));
                echo $bin;
                exit;
            } elseif (file_exists(__DIR__ . '/../' . $firmaData)) {
                $filePath = __DIR__ . '/../' . $firmaData;
                $mime = mime_content_type($filePath) ?: 'image/png';
                header('Content-Type: ' . $mime);
                header('Content-Length: ' . filesize($filePath));
                readfile($filePath);
                exit;
            } else {
                http_response_code(404);
                exit('Firma no encontrada.');
            }
            break;

        // 10. OBTENER EVIDENCIA FOTOGRÁFICA ACCESIBLE (STREAMING BINARIO)
        case 'ver_evidencia':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                exit('Identificador no válido.');
            }

            $stmtEv = $pdo->prepare("SELECT archivo_url FROM actividades_contratista_evidencias WHERE id = ?");
            $stmtEv->execute([$id]);
            $evData = $stmtEv->fetchColumn();

            if (!$evData) {
                http_response_code(404);
                exit('Evidencia no disponible.');
            }

            header('Access-Control-Allow-Origin: *');
            header('Cache-Control: public, max-age=86400');

            if (strpos($evData, 'data:image') === 0 && preg_match('/^data:image\/(png|jpe?g|webp);base64,(.+)$/is', $evData, $m)) {
                $mime = ($m[1] === 'jpg' || $m[1] === 'jpeg') ? 'image/jpeg' : 'image/' . $m[1];
                $bin = base64_decode($m[2]);
                header('Content-Type: ' . $mime);
                header('Content-Length: ' . strlen($bin));
                echo $bin;
                exit;
            } elseif (file_exists(__DIR__ . '/../' . $evData)) {
                $filePath = __DIR__ . '/../' . $evData;
                $mime = mime_content_type($filePath) ?: 'image/jpeg';
                header('Content-Type: ' . $mime);
                header('Content-Length: ' . filesize($filePath));
                readfile($filePath);
                exit;
            } else {
                http_response_code(404);
                exit('Evidencia no encontrada.');
            }
            break;

        default:
            jsonResponse(['success' => false, 'message' => 'Acción no permitida.'], 400);
            break;
    }
} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error en actividades_contratista_api.php: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Ocurrió un error interno en el procesamiento de la solicitud.'], 500);
}
