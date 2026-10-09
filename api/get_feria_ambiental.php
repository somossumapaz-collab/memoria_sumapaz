<?php
/**
 * API Endpoint: Feria Agroambiental (datos subidos por la app concursosSumapaz - Rol 13)
 * Devuelve productores inscritos (sin imágenes pesadas), sus inscripciones a concursos,
 * el catálogo de concursos y el conteo de evaluaciones.
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

try {
    // 1. Catálogo de concursos
    $concursos = [];
    foreach ($pdo->query("SELECT id, categoria, nombre_concurso FROM estructura_concursos_AMBIENTAL ORDER BY id ASC") as $r) {
        $concursos[] = [
            'id' => (int)$r['id'],
            'categoria' => $r['categoria'],
            'nombre' => $r['nombre_concurso'],
        ];
    }

    // 2. Productores (sin base64 pesados; solo banderas de existencia)
    $sql = "
        SELECT id, local_id, nombre_productor, apellido_productor, tipo_documento, numero_documento,
               nombre_predio, extension_finca, vereda, cuenca, latitud, longitud, altitud_msnm,
               telefono, email, genero, edad, nivel_escolaridad, es_lgbtiq, condicion_discapacidad,
               en_parques_nacionales, notes, nombre_inscriptor, fecha_inscripcion, created_at,
               (firma_productor IS NOT NULL AND firma_productor != '') AS tiene_firma_productor,
               (firma_inscriptor IS NOT NULL AND firma_inscriptor != '') AS tiene_firma_inscriptor,
               (photo_paths IS NOT NULL AND photo_paths != '') AS tiene_fotos
        FROM productor_predio_AMBIENTAL
        ORDER BY id DESC
    ";
    $productores = [];
    foreach ($pdo->query($sql) as $r) {
        // Separar observaciones del bloque de concursos seleccionados
        $obs = '';
        if (!empty($r['notes']) && strpos($r['notes'], 'OBSERVACIONES:') !== false) {
            $obs = trim(substr($r['notes'], strpos($r['notes'], 'OBSERVACIONES:') + strlen('OBSERVACIONES:')));
        }
        $productores[] = [
            'id' => (int)$r['id'],
            'local_id' => $r['local_id'] !== null ? (int)$r['local_id'] : null,
            'nombre' => trim($r['nombre_productor'] ?? ''),
            'apellido' => trim($r['apellido_productor'] ?? ''),
            'tipo_documento' => $r['tipo_documento'],
            'numero_documento' => trim($r['numero_documento'] ?? ''),
            'nombre_predio' => $r['nombre_predio'],
            'extension_finca' => $r['extension_finca'],
            'vereda' => strtoupper(trim($r['vereda'] ?? '')),
            'latitud' => $r['latitud'] !== null ? (float)$r['latitud'] : null,
            'longitud' => $r['longitud'] !== null ? (float)$r['longitud'] : null,
            'altitud_msnm' => $r['altitud_msnm'] !== null ? (float)$r['altitud_msnm'] : null,
            'telefono' => $r['telefono'],
            'email' => $r['email'],
            'genero' => $r['genero'] ?: 'Sin dato',
            'edad' => $r['edad'] !== null ? (int)$r['edad'] : null,
            'nivel_escolaridad' => $r['nivel_escolaridad'],
            'es_lgbtiq' => (int)$r['es_lgbtiq'] === 1,
            'condicion_discapacidad' => $r['condicion_discapacidad'],
            'en_parques_nacionales' => (int)$r['en_parques_nacionales'] === 1,
            'observaciones' => $obs,
            'nombre_inscriptor' => trim($r['nombre_inscriptor'] ?? ''),
            'fecha_inscripcion' => $r['fecha_inscripcion'] ?: $r['created_at'],
            'created_at' => $r['created_at'],
            'tiene_firma_productor' => (bool)$r['tiene_firma_productor'],
            'tiene_firma_inscriptor' => (bool)$r['tiene_firma_inscriptor'],
            'tiene_fotos' => (bool)$r['tiene_fotos'],
        ];
    }

    // 3. Inscripciones a concursos
    $inscripciones = [];
    foreach ($pdo->query("SELECT id, id_productor_predio, id_concurso, fecha_inscripcion, created_at FROM inscripcion_concurso_AMBIENTAL ORDER BY id ASC") as $r) {
        $inscripciones[] = [
            'id' => (int)$r['id'],
            'productor_id' => (int)$r['id_productor_predio'],
            'concurso_id' => (int)$r['id_concurso'],
            'fecha' => $r['fecha_inscripcion'] ?: $r['created_at'],
        ];
    }

    // 4. Evaluaciones (resultados) sin imágenes
    $resultados = [];
    try {
        foreach ($pdo->query("SELECT id, id_productor_predio, id_concurso, resultados_json, fecha FROM resultado_concurso_AMBIENTAL ORDER BY id ASC") as $r) {
            $json = json_decode($r['resultados_json'] ?? '', true);
            $resultados[] = [
                'id' => (int)$r['id'],
                'productor_id' => (int)$r['id_productor_predio'],
                'concurso_id' => (int)$r['id_concurso'],
                'puntaje' => is_array($json) && isset($json['totalScore']) ? (float)$json['totalScore'] : null,
                'fecha' => $r['fecha'],
            ];
        }
    } catch (Exception $e) {
        // tabla opcional
    }

    echo json_encode([
        'success' => true,
        'concursos' => $concursos,
        'productores' => $productores,
        'inscripciones' => $inscripciones,
        'resultados' => $resultados,
        'generado' => date('Y-m-d H:i:s'),
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al obtener datos de la feria: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
