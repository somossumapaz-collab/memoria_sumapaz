<?php
/**
 * API Endpoint: Detalle completo de un Acta Oficial de Juzgamiento y Evaluación
 * Origen exclusivo: Tablas _AMBIENTAL (resultado_concurso_AMBIENTAL)
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
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($rawUid !== '') {
    if (strpos($rawUid, 'amb_') === 0) {
        $id = (int)substr($rawUid, 4);
    } else {
        $id = (int)$rawUid;
    }
}

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Identificador de evaluación inválido'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
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
                    'criterio' => $cr['name'] ?? ($cr['criterio'] ?? 'Criterio evaluable'),
                    'puntaje_maximo' => (int)($cr['maxPoints'] ?? ($cr['puntaje_maximo'] ?? 25)),
                    'puntaje_obtenido' => (int)($cr['score'] ?? ($cr['puntaje_obtenido'] ?? 0)),
                ];
            }
        }
    }

    $firmaJurado = splitImages($row['firma_evaluador'])[0] ?? null;
    $firmaConcursante = splitImages($row['firma_concursante'])[0] ?? null;
    $fotos = splitImages($row['imagenes']);

    $maxPosible = 0;
    foreach ($criteriaList as $cr) {
        $maxPosible += (int)$cr['puntaje_maximo'];
    }
    if ($maxPosible <= 0) $maxPosible = 100;

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
            'categoria' => trim((string)($row['categoria'] ?: 'Prácticas Agroambientales')),
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

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al obtener detalle del acta oficial: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
