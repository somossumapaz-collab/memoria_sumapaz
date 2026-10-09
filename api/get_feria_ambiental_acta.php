<?php
/**
 * API Endpoint: Acta de inscripción de un productor de la Feria Agroambiental.
 * Devuelve el registro completo con firmas (base64), fotos, concursos inscritos y evaluaciones.
 * Acceso: rol 1 (admin) y rol 13 (feria ambiental).
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_config.php';

$isLocal = isset($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);
$rolId = isset($_SESSION['rol_id']) ? (int)$_SESSION['rol_id'] : null;
if (!$isLocal && !in_array($rolId, [1, 13], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID inválido'], JSON_UNESCAPED_UNICODE);
    exit;
}

/** Divide un campo que puede contener varias imágenes data-URI concatenadas. */
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

try {
    $stmt = $pdo->prepare("SELECT * FROM productor_predio_AMBIENTAL WHERE id = ?");
    $stmt->execute([$id]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$p) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Productor no encontrado'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT i.id_concurso, i.fecha_inscripcion, c.categoria, c.nombre_concurso
        FROM inscripcion_concurso_AMBIENTAL i
        LEFT JOIN estructura_concursos_AMBIENTAL c ON c.id = i.id_concurso
        WHERE i.id_productor_predio = ?
        ORDER BY i.id_concurso ASC
    ");
    $stmt->execute([$id]);
    $concursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $evaluaciones = [];
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, c.nombre_concurso
            FROM resultado_concurso_AMBIENTAL r
            LEFT JOIN estructura_concursos_AMBIENTAL c ON c.id = r.id_concurso
            WHERE r.id_productor_predio = ?
            ORDER BY r.id ASC
        ");
        $stmt->execute([$id]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $json = json_decode($r['resultados_json'] ?? '', true);
            $evaluaciones[] = [
                'concurso' => $r['nombre_concurso'],
                'puntaje' => is_array($json) && isset($json['totalScore']) ? $json['totalScore'] : null,
                'observaciones' => $r['observaciones'],
                'fecha' => $r['fecha'],
                'imagenes' => splitImages($r['imagenes']),
                'firma_evaluador' => splitImages($r['firma_evaluador'])[0] ?? null,
                'firma_concursante' => splitImages($r['firma_concursante'])[0] ?? null,
            ];
        }
    } catch (Exception $e) {
        // tabla opcional
    }

    $notes = (string)($p['notes'] ?? '');
    $obs = '';
    if (strpos($notes, 'OBSERVACIONES:') !== false) {
        $obs = trim(substr($notes, strpos($notes, 'OBSERVACIONES:') + strlen('OBSERVACIONES:')));
    }

    echo json_encode([
        'success' => true,
        'productor' => [
            'id' => (int)$p['id'],
            'nombre' => trim(($p['nombre_productor'] ?? '') . ' ' . ($p['apellido_productor'] ?? '')),
            'tipo_documento' => $p['tipo_documento'],
            'numero_documento' => $p['numero_documento'],
            'nombre_predio' => $p['nombre_predio'],
            'extension_finca' => $p['extension_finca'],
            'vereda' => $p['vereda'],
            'latitud' => $p['latitud'],
            'longitud' => $p['longitud'],
            'altitud_msnm' => $p['altitud_msnm'],
            'telefono' => $p['telefono'],
            'email' => $p['email'],
            'genero' => $p['genero'],
            'edad' => $p['edad'],
            'nivel_escolaridad' => $p['nivel_escolaridad'],
            'es_lgbtiq' => (int)$p['es_lgbtiq'] === 1,
            'condicion_discapacidad' => $p['condicion_discapacidad'],
            'en_parques_nacionales' => (int)$p['en_parques_nacionales'] === 1,
            'observaciones' => $obs,
            'nombre_inscriptor' => $p['nombre_inscriptor'] ?? '',
            'fecha_inscripcion' => $p['fecha_inscripcion'] ?: $p['created_at'],
            'firma_productor' => splitImages($p['firma_productor'])[0] ?? null,
            'firma_inscriptor' => splitImages($p['firma_inscriptor'])[0] ?? null,
            'fotos' => splitImages($p['photo_paths']),
        ],
        'concursos' => $concursos,
        'evaluaciones' => $evaluaciones,
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al obtener el acta: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
