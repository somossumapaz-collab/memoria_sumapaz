<?php
/**
 * API Endpoint: Get Detailed Contest Info with Criteria and Participant Evaluations
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_config.php';

$concursoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$concursoId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de concurso no proporcionado o inválido']);
    exit;
}

try {
    // 1. Fetch contest basic info
    $stmtC = $pdo->prepare("SELECT * FROM concursos WHERE id = ?");
    $stmtC->execute([$concursoId]);
    $concurso = $stmtC->fetch(PDO::FETCH_ASSOC);

    if (!$concurso) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Concurso no encontrado']);
        exit;
    }

    // 2. Fetch criteria for this contest
    $stmtCrit = $pdo->prepare("SELECT * FROM criterios WHERE concurso_id = ? ORDER BY id ASC");
    $stmtCrit->execute([$concursoId]);
    $rawCriterios = $stmtCrit->fetchAll(PDO::FETCH_ASSOC);
    $criterios = [];
    foreach ($rawCriterios as $cr) {
        $cr['max_points'] = (int)($cr['max_points_value'] ?: 0);
        $criterios[] = $cr;
    }

    // 3. Fetch participants and their evaluations
    $sqlPart = "
        SELECT 
            cp.id as concurso_participacion_id,
            fp.id as participante_id,
            fp.documento_identidad,
            fp.tipo_documento,
            fp.nombre_completo,
            fp.vereda,
            fp.edad,
            fp.telefono,
            fp.asistio,
            fp.firma_asistencia,
            e.id as evaluacion_id,
            e.nombre_jurado,
            e.puntaje_total,
            e.resultado_estado,
            e.observaciones,
            e.desagregado_puntaje,
            e.firma_participante,
            e.firma_jurado,
            COALESCE(e.imagen_id, img.id) as imagen_id,
            e.created_at as fecha_evaluacion
        FROM concurso_participaciones cp
        INNER JOIN feria_participantes fp ON cp.participante_id = fp.id
        LEFT JOIN evaluaciones e ON e.concurso_participacion_id = cp.id
        LEFT JOIN imagenes img ON (img.id = e.imagen_id OR img.id = e.id OR img.id = cp.id)
        WHERE cp.concurso_id = ?
        ORDER BY COALESCE(e.puntaje_total, -1) DESC, fp.nombre_completo ASC
    ";

    $stmtP = $pdo->prepare($sqlPart);
    $stmtP->execute([$concursoId]);
    $rawRows = $stmtP->fetchAll(PDO::FETCH_ASSOC);

    $participantes = [];
    foreach ($rawRows as $r) {
        $desagregado = null;
        if (!empty($r['desagregado_puntaje'])) {
            $desagregado = json_decode($r['desagregado_puntaje'], true);
        }

        $evaluacion = null;
        if ($r['evaluacion_id']) {
            $evaluacion = [
                'evaluacion_id' => (int)$r['evaluacion_id'],
                'nombre_jurado' => $r['nombre_jurado'],
                'puntaje_total' => (int)$r['puntaje_total'],
                'resultado_estado' => $r['resultado_estado'] ?: 'Calificado',
                'observaciones' => $r['observaciones'] ?: '',
                'desagregado_puntaje' => $desagregado,
                'firma_participante' => $r['firma_participante'] ?: '',
                'firma_jurado' => $r['firma_jurado'] ?: '',
                'imagen_id' => $r['imagen_id'] ? (int)$r['imagen_id'] : null,
                'fecha_evaluacion' => $r['fecha_evaluacion']
            ];
        }

        $participantes[] = [
            'id' => (int)$r['participante_id'],
            'concurso_participacion_id' => (int)$r['concurso_participacion_id'],
            'participante_id' => (int)$r['participante_id'],
            'documento_identidad' => $r['documento_identidad'],
            'tipo_documento' => $r['tipo_documento'],
            'nombre_completo' => $r['nombre_completo'],
            'vereda' => $r['vereda'],
            'edad' => $r['edad'] ? (int)$r['edad'] : null,
            'telefono' => $r['telefono'],
            'asistio' => (bool)$r['asistio'],
            'firma_asistencia' => $r['firma_asistencia'] ?: '',
            'evaluacion' => $evaluacion
        ];
    }

    echo json_encode([
        'success' => true,
        'concurso' => [
            'id' => (int)$concurso['id'],
            'sheet_name' => $concurso['sheet_name'],
            'title' => $concurso['title'],
            'category' => $concurso['category'] ?: 'General / Tradicional',
            'total_max_points' => (int)$concurso['total_max_points']
        ],
        'criterios' => $criterios,
        'participantes' => $participantes
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al obtener detalle del concurso: ' . $e->getMessage()]);
}
