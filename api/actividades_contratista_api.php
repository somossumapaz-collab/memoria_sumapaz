<?php
/**
 * API para Control de Actividades, Contratistas y Formato Oficial de Actas GDI-GPD-F029
 * Tablas: actividades_contratista_*
 */
require_once __DIR__ . '/db_config.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Helper para responder JSON
function jsonResponse($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Helper para subir archivos base64 a disco
function saveBase64Image(string $base64Data, string $folder = 'firmas'): ?string {
    if (empty($base64Data)) return null;
    if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
        $data = substr($base64Data, strpos($base64Data, ',') + 1);
        $type = strtolower($type[1]);
        if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png', 'webp'])) {
            $type = 'png';
        }
        $data = base64_decode($data);
        if ($data === false) return null;
    } else {
        return null;
    }

    $uploadDir = __DIR__ . '/../uploads/' . $folder . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = uniqid($folder . '_', true) . '.' . $type;
    $filePath = $uploadDir . $fileName;

    if (file_put_contents($filePath, $data)) {
        return 'uploads/' . $folder . '/' . $fileName;
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
            $doc = trim($_GET['documento'] ?? '');
            if ($doc === '') {
                jsonResponse(['success' => false, 'message' => 'Documento requerido'], 400);
            }
            $stmt = $pdo->prepare("
                SELECT c.* 
                FROM actividades_contratistas c
                WHERE c.numero_documento = ?
                LIMIT 1
            ");
            $stmt->execute([$doc]);
            $contratista = $stmt->fetch();

            if (!$contratista) {
                jsonResponse(['success' => false, 'message' => 'Contratista no encontrado', 'encontrado' => false]);
            }

            // Proyectos asociados
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
                jsonResponse(['success' => false, 'message' => 'Metodo no permitido'], 405);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!is_array($input)) {
                $input = $_POST;
            }

            $numDoc = trim($input['numero_documento'] ?? '');
            $nombres = trim($input['nombres_apellidos'] ?? '');

            if ($numDoc === '' || $nombres === '') {
                jsonResponse(['success' => false, 'message' => 'El número de documento y nombres completos son obligatorios.'], 400);
            }

            $tipoDoc = trim($input['tipo_documento'] ?? 'CC');
            $nomIdentitario = trim($input['nombre_identitario'] ?? '');
            $telefono = trim($input['telefono'] ?? '');
            $correo = trim($input['correo'] ?? '');
            $dependencia = trim($input['dependencia'] ?? 'Alcaldía Local de Sumapaz');
            $cargo = trim($input['cargo'] ?? 'Profesional');
            $tipoVinculacion = trim($input['tipo_vinculacion'] ?? 'Contratista');
            $firmaRaw = $input['firma_digital'] ?? '';
            $proyectos = $input['proyectos_ids'] ?? [];
            if (!is_array($proyectos) && is_string($proyectos)) {
                $proyectos = json_decode($proyectos, true) ?: [];
            }

            // Procesar firma
            $firmaRuta = null;
            if (strpos($firmaRaw, 'data:image') === 0) {
                $firmaRuta = saveBase64Image($firmaRaw, 'firmas');
            } elseif (!empty($firmaRaw)) {
                $firmaRuta = $firmaRaw; // Ya era una URL o ruta
            }

            $pdo->beginTransaction();

            // Verificar si ya existe
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
            if (!empty($proyectos)) {
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
            }

            $pdo->commit();

            jsonResponse([
                'success' => true,
                'contratista_id' => $contratistaId,
                'message' => 'Contratista guardado y asociado a sus proyectos de inversión exitosamente.'
            ]);
            break;

        // 5. REGISTRAR ACTIVIDAD (REPORTE DE ACTA COMPLETO)
        case 'guardar_actividad':
            if ($method !== 'POST') {
                jsonResponse(['success' => false, 'message' => 'Metodo no permitido'], 405);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!is_array($input)) {
                $input = $_POST;
            }

            $contratistaDoc = trim($input['contratista_documento'] ?? '');
            $contratistaId = (int)($input['contratista_id'] ?? 0);
            $proyectoId = (int)($input['proyecto_id'] ?? 0);
            $objetoReunion = trim($input['objeto_reunion'] ?? '');
            $fecha = trim($input['fecha'] ?? date('Y-m-d'));
            $horaInicio = trim($input['hora_inicio'] ?? '08:00');
            $horaFin = trim($input['hora_fin'] ?? '10:00');

            if ($objetoReunion === '') {
                jsonResponse(['success' => false, 'message' => 'El objeto de la reunión es obligatorio.'], 400);
            }
            if ($proyectoId <= 0) {
                jsonResponse(['success' => false, 'message' => 'Debe asociar un proyecto de inversión.'], 400);
            }

            $pdo->beginTransaction();

            // Buscar o crear contratista responsable
            if ($contratistaId <= 0 && $contratistaDoc !== '') {
                $stmtC = $pdo->prepare("SELECT id FROM actividades_contratistas WHERE numero_documento = ?");
                $stmtC->execute([$contratistaDoc]);
                $cRow = $stmtC->fetch();
                if ($cRow) {
                    $contratistaId = (int)$cRow['id'];
                } else {
                    // Crear contratista básico si no existía
                    $nomResp = trim($input['nombre_responsable'] ?? 'Responsable');
                    $telResp = trim($input['telefono_responsable'] ?? '');
                    $correoResp = trim($input['correo_responsable'] ?? '');
                    $depResp = trim($input['dependencia'] ?? 'Alcaldía Local de Sumapaz');
                    $firmaRespRaw = $input['firma_responsable'] ?? '';
                    $firmaRespRuta = saveBase64Image($firmaRespRaw, 'firmas');

                    $stmtNewC = $pdo->prepare("
                        INSERT INTO actividades_contratistas 
                        (tipo_documento, numero_documento, nombres_apellidos, telefono, correo, dependencia, firma_digital)
                        VALUES ('CC', ?, ?, ?, ?, ?, ?)
                    ");
                    $stmtNewC->execute([$contratistaDoc, $nomResp, $telResp, $correoResp, $depResp, $firmaRespRuta]);
                    $contratistaId = (int)$pdo->lastInsertId();

                    // Asociar al proyecto seleccionado
                    $pdo->prepare("INSERT IGNORE INTO actividades_contratista_proyectos (contratista_id, proyecto_id) VALUES (?, ?)")
                        ->execute([$contratistaId, $proyectoId]);
                }
            }

            if ($contratistaId <= 0) {
                $pdo->rollBack();
                jsonResponse(['success' => false, 'message' => 'No se pudo identificar o registrar el contratista responsable.'], 400);
            }

            // Modalidad y lugar
            $modalidad = trim($input['modalidad'] ?? 'Presencial');
            $esVirtual = ($modalidad === 'Virtual' || !empty($input['es_virtual'])) ? 1 : 0;
            $linkReunion = trim($input['link_reunion'] ?? '');
            $lugar = trim($input['lugar'] ?? '');
            
            // Veredas visitadas
            $veredasVisitadas = $input['veredas_visitadas'] ?? [];
            if (is_array($veredasVisitadas)) {
                $veredasVisitadasStr = implode(', ', array_filter($veredasVisitadas));
            } else {
                $veredasVisitadasStr = trim((string)$veredasVisitadas);
            }

            $dependencia = trim($input['dependencia'] ?? 'Alcaldía Local de Sumapaz');
            $nombreResponsable = trim($input['nombre_responsable'] ?? '');
            $requirioTransporte = !empty($input['requirio_transporte']) ? 1 : 0;
            $formatoTransporte = !empty($input['formato_transporte_diligenciado']) ? 1 : 0;
            $detalleTransporte = trim($input['detalle_transporte'] ?? '');
            $desarrollo = trim($input['desarrollo_conclusiones'] ?? '');
            $casoHola = trim($input['caso_hola'] ?? '70103');

            // Generar consecutivo de código de acta
            $anio = date('Y', strtotime($fecha));
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM actividades_contratista_reportes WHERE YEAR(fecha) = ?");
            $stmtCount->execute([$anio]);
            $numConsecutivo = (int)$stmtCount->fetchColumn() + 1;
            $codigoActa = sprintf('ACTA-%s-%04d', $anio, $numConsecutivo);

            // Insertar reporte de actividad
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

            // Insertar compromisos
            $compromisos = $input['compromisos'] ?? [];
            if (is_array($compromisos)) {
                $stmtComp = $pdo->prepare("
                    INSERT INTO actividades_contratista_compromisos
                    (reporte_id, numero, actividad, responsable, fecha_entrega)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $idxComp = 1;
                foreach ($compromisos as $c) {
                    $actividad = trim($c['actividad'] ?? '');
                    if ($actividad === '') continue;
                    $resp = trim($c['responsable'] ?? $nombreResponsable);
                    $fEntrega = !empty($c['fecha_entrega']) ? $c['fecha_entrega'] : null;
                    $stmtComp->execute([$reporteId, $idxComp, $actividad, $resp, $fEntrega]);
                    $idxComp++;
                }
            }

            // Insertar participantes
            $participantes = $input['participantes'] ?? [];
            if (is_array($participantes)) {
                $stmtPart = $pdo->prepare("
                    INSERT INTO actividades_contratista_participantes
                    (reporte_id, tipo_documento, numero_documento, nombres_apellidos, nombre_identitario,
                     entidad_dependencia, cargo, tipo_vinculacion, correo, telefono, firma_digital, es_responsable)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                foreach ($participantes as $p) {
                    $pDoc = trim($p['numero_documento'] ?? '');
                    $pNom = trim($p['nombres_apellidos'] ?? '');
                    if ($pDoc === '' && $pNom === '') continue;

                    $pTipoDoc = trim($p['tipo_documento'] ?? 'CC');
                    $pNomId = trim($p['nombre_identitario'] ?? '');
                    $pEntidad = trim($p['entidad_dependencia'] ?? 'Alcaldía Local de Sumapaz');
                    $pCargo = trim($p['cargo'] ?? 'Profesional');
                    $pVinculacion = trim($p['tipo_vinculacion'] ?? 'Contratista');
                    $pCorreo = trim($p['correo'] ?? '');
                    $pTel = trim($p['telefono'] ?? '');
                    $pFirmaRaw = $p['firma_digital'] ?? '';
                    $pFirmaRuta = null;

                    if (strpos($pFirmaRaw, 'data:image') === 0) {
                        $pFirmaRuta = saveBase64Image($pFirmaRaw, 'firmas');
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

            // Insertar evidencias fotográficas
            $evidencias = $input['evidencias'] ?? [];
            if (is_array($evidencias)) {
                $stmtEvid = $pdo->prepare("
                    INSERT INTO actividades_contratista_evidencias
                    (reporte_id, archivo_url, descripcion)
                    VALUES (?, ?, ?)
                ");
                foreach ($evidencias as $ev) {
                    $fotoRaw = is_array($ev) ? ($ev['archivo'] ?? ($ev['url'] ?? '')) : (string)$ev;
                    $desc = is_array($ev) ? trim($ev['descripcion'] ?? '') : '';
                    if (empty($fotoRaw)) continue;

                    $fotoRuta = null;
                    if (strpos($fotoRaw, 'data:image') === 0) {
                        $fotoRuta = saveBase64Image($fotoRaw, 'actividades');
                    } else {
                        $fotoRuta = $fotoRaw;
                    }

                    if ($fotoRuta) {
                        $stmtEvid->execute([$reporteId, $fotoRuta, $desc]);
                    }
                }
            }

            // Subidas de archivos adicionales por $_FILES si vinieron vía multipart
            if (!empty($_FILES['fotos']['name']) && is_array($_FILES['fotos']['name'])) {
                $stmtEvid = $pdo->prepare("
                    INSERT INTO actividades_contratista_evidencias (reporte_id, archivo_url, descripcion)
                    VALUES (?, ?, ?)
                ");
                $uploadDir = __DIR__ . '/../uploads/actividades/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                foreach ($_FILES['fotos']['tmp_name'] as $k => $tmpPath) {
                    if (is_uploaded_file($tmpPath)) {
                        $ext = pathinfo($_FILES['fotos']['name'][$k], PATHINFO_EXTENSION) ?: 'jpg';
                        $targetName = 'actividades_' . uniqid('', true) . '.' . $ext;
                        if (move_uploaded_file($tmpPath, $uploadDir . $targetName)) {
                            $stmtEvid->execute([$reporteId, 'uploads/actividades/' . $targetName, 'Evidencia fotográfica']);
                        }
                    }
                }
            }

            $pdo->commit();

            jsonResponse([
                'success' => true,
                'reporte_id' => $reporteId,
                'codigo_acta' => $codigoActa,
                'message' => 'Acta y evidencia de reunión guardadas exitosamente.'
            ]);
            break;

        // 6. LISTADO DE ACTIVIDADES PARA PANEL DE CONTROL EN DESPACHO
        case 'actividades_list':
            $busqueda = trim($_GET['busqueda'] ?? '');
            $proyectoId = (int)($_GET['proyecto_id'] ?? 0);
            $modalidad = trim($_GET['modalidad'] ?? '');
            $fechaDesde = trim($_GET['fecha_desde'] ?? '');
            $fechaHasta = trim($_GET['fecha_hasta'] ?? '');
            $contratistaDoc = trim($_GET['documento'] ?? '');

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
            if ($fechaDesde !== '') {
                $where[] = "r.fecha >= ?";
                $params[] = $fechaDesde;
            }
            if ($fechaHasta !== '') {
                $where[] = "r.fecha <= ?";
                $params[] = $fechaHasta;
            }
            if ($contratistaDoc !== '') {
                $where[] = "c.numero_documento = ?";
                $params[] = $contratistaDoc;
            }

            $sqlWhere = implode(' AND ', $where);

            // Obtener datos
            $stmt = $pdo->prepare("
                SELECT r.*, 
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

            // KPIs globales
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

        // 7. DETALLE COMPLETO DE UN ACTA / ACTIVIDAD
        case 'actividad_detalle':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
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
                jsonResponse(['success' => false, 'message' => 'Actividad no encontrada'], 404);
            }

            // Participantes
            $stmtPart = $pdo->prepare("
                SELECT * FROM actividades_contratista_participantes 
                WHERE reporte_id = ? 
                ORDER BY es_responsable DESC, id ASC
            ");
            $stmtPart->execute([$id]);
            $reporte['participantes'] = $stmtPart->fetchAll();

            // Compromisos
            $stmtComp = $pdo->prepare("
                SELECT * FROM actividades_contratista_compromisos 
                WHERE reporte_id = ? 
                ORDER BY numero ASC, id ASC
            ");
            $stmtComp->execute([$id]);
            $reporte['compromisos'] = $stmtComp->fetchAll();

            // Evidencias
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
            $doc = trim($_GET['documento'] ?? '');
            if ($doc === '') {
                jsonResponse(['success' => false, 'message' => 'Debe ingresar un número de documento.'], 400);
            }

            // Busca donde es contratista responsable o participante
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

            // Buscar datos de la persona
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
                'persona' => $persona,
                'total_actas' => count($actas),
                'actas' => $actas
            ]);
            break;

        default:
            jsonResponse(['success' => false, 'message' => 'Accion no reconocida.'], 400);
            break;
    }
} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
}
