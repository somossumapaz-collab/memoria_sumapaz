<?php
/**
 * API Endpoint: Detalle completo de un Acta Oficial de Juzgamiento y Evaluación
 * Retorna datos del participante/productor, jurado, firmas digitales, evidencia fotográfica,
 * desglose de criterios y coordenadas para renderizar e imprimir el acta oficial.
 */
@ini_set('display_errors', '0');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_config.php';

$isLocal = (php_sapi_name() === 'cli') || (isset($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']));
$rolId = isset($_SESSION['rol_id']) ? (int)$_SESSION['rol_id'] : null;

if (!$isLocal && !isset($_SESSION['user_id']) && !in_array($rolId, [1, 13, 14], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!function_exists('splitImages')) {
    function splitImages($raw) {
        $raw = trim((string)$raw);
        if ($raw === '') return [];
        if (strpos($raw, 'data:image') === false) return [];
        $parts = preg_split('/(?=data:image\/)/', $raw, -1, PREG_SPLIT_NO_EMPTY);
        $out = [];
        foreach ($parts as $p) {
            $p = rtrim(trim($p), ",;| \n\r\t");
            if (strpos($p, 'data:image/') === 0) $out[] = $p;
        }
        return $out;
    }
}

$rawUid = $_GET['uid'] ?? '';
$fuente = $_GET['fuente'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($rawUid !== '') {
    if (strpos($rawUid, 'amb_') === 0) {
        $fuente = 'ambiental';
        $id = (int)substr($rawUid, 4);
    } elseif (strpos($rawUid, 'gen_') === 0) {
        $fuente = 'general';
        $id = (int)substr($rawUid, 4);
    }
}

if ($id <= 0 || !in_array($fuente, ['ambiental', 'general'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Identificador o fuente inválida'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if ($fuente === 'ambiental') {
        $stmt = $pdo->prepare("
            SELECT 
                r.*,
                p.nombre_productor, p.apellido_productor, p.tipo_documento, p.numero_documento,
                p.nombre_predio, p.extension_finca, p.vereda, p.cuenca, p.telefono, p.email,
                p.nombre_inscriptor, p.fecha_inscripcion,
                c.nombre_concurso, c.categoria
            FROM resultado_concurso_AMBIENTAL r
            LEFT JOIN productor_predio_AMBIENTAL p ON p.id = r.id_productor_predio
            LEFT JOIN estructura_concursos_AMBIENTAL c ON c.id = r.id_concurso
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Evaluación agroambiental no encontrada'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $nombreCompleto = trim(($row['nombre_productor'] ?? '') . ' ' . ($row['apellido_productor'] ?? ''));
        if ($nombreCompleto === '') $nombreCompleto = 'Participante #' . $row['id_productor_predio'];

        $jurado = trim((string)($row['nombre_inscriptor'] ?? ''));
        if ($jurado === '') $jurado = 'Jurado Agroambiental Oficial';

        $totalScore = 0;
        $criteriaList = [];
        $json = json_decode($row['resultados_json'] ?? '', true);
        if (is_array($json)) {
            if (isset($json['totalScore'])) {
                $totalScore = (float)$json['totalScore'];
            } elseif (isset($json['puntaje_total'])) {
                $totalScore = (float)$json['puntaje_total'];
            }

            if (isset($json['criteria']) && is_array($json['criteria'])) {
                foreach ($json['criteria'] as $cr) {
                    $criteriaList[] = [
                        'criterio' => $cr['name'] ?? ($cr['criterio'] ?? 'Criterio evaluado'),
                        'puntaje_maximo' => (int)($cr['maxPoints'] ?? ($cr['puntaje_maximo'] ?? 25)),
                        'puntaje_obtenido' => (int)($cr['score'] ?? ($cr['puntaje_obtenido'] ?? 0)),
                    ];
                }
            }
        }

        // Si no vienen criterios desglosados en el JSON, consultar el catálogo oficial de criterios
        if (empty($criteriaList) && !empty($row['id_concurso'])) {
            $stmtCrit = $pdo->prepare("SELECT name, max_points_value FROM criterios WHERE concurso_id = ? ORDER BY id ASC");
            $stmtCrit->execute([$row['id_concurso']]);
            $dbCrits = $stmtCrit->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($dbCrits)) {
                $criteriaList = array_map(function($c) {
                    return [
                        'criterio' => $c['name'],
                        'puntaje_maximo' => (int)($c['max_points_value'] ?: 25),
                        'puntaje_obtenido' => null,
                    ];
                }, $dbCrits);
            }
        }

        $maxPosible = 0;
        foreach ($criteriaList as $cr) {
            $maxPosible += (int)$cr['puntaje_maximo'];
        }
        if ($maxPosible <= 0) $maxPosible = 100;

        $firmaJurado = splitImages($row['firma_evaluador'])[0] ?? null;
        $firmaConcursante = splitImages($row['firma_concursante'])[0] ?? null;
        $fotos = splitImages($row['imagenes']);

        echo json_encode([
            'success' => true,
            'acta' => [
                'uid' => 'amb_' . $row['id'],
                'fuente' => 'ambiental',
                'fuente_label' => 'XXV Feria Agroambiental de Sumapaz (Rol 13)',
                'titulo_documento' => 'ACTA OFICIAL DE JUZGAMIENTO Y EVALUACION',
                'id_evaluacion' => (int)$row['id'],
                'id_concurso' => (int)$row['id_concurso'],
                'concurso' => trim((string)($row['nombre_concurso'] ?: ('Concurso #' . $row['id_concurso']))),
                'categoria' => trim((string)($row['categoria'] ?: 'Prácticas Ambientales')),
                'fecha' => !empty($row['fecha']) ? $row['fecha'] : ($row['created_at'] ?? ''),
                'coordenadas' => trim((string)($row['coordenadas'] ?? '')),
                'jurado' => $jurado,
                'participante' => [
                    'id' => (int)$row['id_productor_predio'],
                    'nombre' => $nombreCompleto,
                    'tipo_documento' => $row['tipo_documento'] ?: 'Cédula de Ciudadanía',
                    'numero_documento' => $row['numero_documento'] ?: '',
                    'vereda' => $row['vereda'] ?: 'Sumapaz',
                    'cuenca' => $row['cuenca'] ?: 'Rio Sumapaz',
                    'predio' => $row['nombre_predio'] ?: '',
                    'extension' => $row['extension_finca'] ? ($row['extension_finca'] . ' fanegadas') : '',
                    'telefono' => $row['telefono'] ?: '',
                ],
                'puntaje_total' => $totalScore,
                'puntaje_max_posible' => $maxPosible,
                'criterios' => $criteriaList,
                'observaciones' => trim((string)($row['observaciones'] ?? '')),
                'firma_jurado' => $firmaJurado,
                'firma_concursante' => $firmaConcursante,
                'fotos' => $fotos,
            ]
        ], JSON_UNESCAPED_UNICODE);

    } else {
        // Fuente: general
        $stmt = $pdo->prepare("
            SELECT 
                e.*,
                fp.id as participante_id, fp.documento_identidad, fp.tipo_documento,
                fp.nombre_completo, fp.vereda, fp.telefono,
                c.id as concurso_id, c.title as nombre_concurso, c.category as categoria,
                c.total_max_points
            FROM evaluaciones e
            JOIN concurso_participaciones cp ON cp.id = e.concurso_participacion_id
            JOIN feria_participantes fp ON fp.id = cp.participante_id
            JOIN concursos c ON c.id = cp.concurso_id
            WHERE e.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Evaluación estándar no encontrada'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $criteriaList = [];
        $desagregado = json_decode($row['desagregado_puntaje'] ?? '', true);
        if (is_array($desagregado) && !empty($desagregado)) {
            foreach ($desagregado as $cr) {
                $criteriaList[] = [
                    'criterio' => $cr['criterio'] ?? ($cr['name'] ?? 'Criterio Oficial'),
                    'puntaje_maximo' => (int)($cr['puntaje_maximo'] ?? ($cr['max_points'] ?? 25)),
                    'puntaje_obtenido' => isset($cr['puntaje_obtenido']) ? (float)$cr['puntaje_obtenido'] : (isset($cr['score']) ? (float)$cr['score'] : null),
                ];
            }
        }

        // Si no tiene desglose guardado, obtener los criterios oficiales del concurso
        if (empty($criteriaList) && !empty($row['concurso_id'])) {
            $stmtCrit = $pdo->prepare("SELECT name, max_points_value FROM criterios WHERE concurso_id = ? ORDER BY id ASC");
            $stmtCrit->execute([$row['concurso_id']]);
            $dbCrits = $stmtCrit->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($dbCrits)) {
                $criteriaList = array_map(function($c) {
                    return [
                        'criterio' => $c['name'],
                        'puntaje_maximo' => (int)($c['max_points_value'] ?: 25),
                        'puntaje_obtenido' => null,
                    ];
                }, $dbCrits);
            }
        }

        $maxPosible = (int)($row['total_max_points'] ?: 100);

        // Si tiene imagen_id en tabla imagenes
        $fotos = [];
        if (!empty($row['imagen_id'])) {
            $stmtImg = $pdo->prepare("SELECT mime_type, datos_imagen FROM imagenes WHERE id = ?");
            $stmtImg->execute([(int)$row['imagen_id']]);
            $imgRow = $stmtImg->fetch(PDO::FETCH_ASSOC);
            if ($imgRow && !empty($imgRow['datos_imagen'])) {
                $mime = $imgRow['mime_type'] ?: 'image/jpeg';
                $fotos[] = 'data:' . $mime . ';base64,' . base64_encode($imgRow['datos_imagen']);
            }
        }

        $firmaJurado = $row['firma_jurado'] ? trim((string)$row['firma_jurado']) : null;
        $firmaConcursante = $row['firma_participante'] ? trim((string)$row['firma_participante']) : null;

        echo json_encode([
            'success' => true,
            'acta' => [
                'uid' => 'gen_' . $row['id'],
                'fuente' => 'general',
                'fuente_label' => 'Concursos Sumapaz - Concurso Oficial',
                'titulo_documento' => 'ACTA OFICIAL DE JUZGAMIENTO Y EVALUACION',
                'id_evaluacion' => (int)$row['id'],
                'id_concurso' => (int)$row['concurso_id'],
                'concurso' => trim((string)$row['nombre_concurso']),
                'categoria' => trim((string)$row['categoria']),
                'fecha' => (string)$row['created_at'],
                'coordenadas' => '',
                'jurado' => trim((string)($row['nombre_jurado'] ?: 'Jurado Oficial')),
                'participante' => [
                    'id' => (int)$row['participante_id'],
                    'nombre' => trim((string)$row['nombre_completo']),
                    'tipo_documento' => $row['tipo_documento'] ?: 'Cédula de Ciudadanía',
                    'numero_documento' => trim((string)$row['documento_identidad']),
                    'vereda' => trim((string)($row['vereda'] ?: 'Sumapaz')),
                    'cuenca' => 'Sumapaz',
                    'predio' => '',
                    'extension' => '',
                    'telefono' => $row['telefono'] ?: '',
                ],
                'puntaje_total' => (float)$row['puntaje_total'],
                'puntaje_max_posible' => $maxPosible,
                'criterios' => $criteriaList,
                'observaciones' => trim((string)($row['observaciones'] ?? '')),
                'firma_jurado' => $firmaJurado,
                'firma_concursante' => $firmaConcursante,
                'fotos' => $fotos,
            ]
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al obtener detalle del acta: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
