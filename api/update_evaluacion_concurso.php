<?php
/**
 * API Endpoint: Update Contest Evaluation Score and Breakdown
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido. Utilice POST.']);
    exit;
}

try {
    $inputRaw = file_get_contents('php://input');
    $data = json_decode($inputRaw, true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Datos de entrada inválidos o JSON malformado.']);
        exit;
    }

    $evaluacionId = isset($data['evaluacion_id']) ? (int)$data['evaluacion_id'] : 0;
    if (!$evaluacionId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID de evaluación no proporcionado']);
        exit;
    }

    // Verify evaluation exists
    $stmtCheck = $pdo->prepare("SELECT id, concurso_participacion_id FROM evaluaciones WHERE id = ?");
    $stmtCheck->execute([$evaluacionId]);
    $existingEval = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$existingEval) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'La evaluación especificada no existe.']);
        exit;
    }

    $nombreJurado = isset($data['nombre_jurado']) ? trim($data['nombre_jurado']) : '';
    $observaciones = isset($data['observaciones']) ? trim($data['observaciones']) : '';
    $desagregadoRaw = isset($data['desagregado_puntaje']) && is_array($data['desagregado_puntaje']) ? $data['desagregado_puntaje'] : [];

    $desagregadoClean = [];
    $puntajeTotal = 0;

    foreach ($desagregadoRaw as $item) {
        $critName = isset($item['criterio']) ? trim($item['criterio']) : 'Criterio';
        $maxPts = isset($item['puntaje_maximo']) ? (float)$item['puntaje_maximo'] : 0;
        $obtPts = isset($item['puntaje_obtenido']) ? (float)$item['puntaje_obtenido'] : 0;

        if ($obtPts < 0) $obtPts = 0;
        if ($maxPts > 0 && $obtPts > $maxPts) {
            $obtPts = $maxPts; // Cap at max points if exceeded
        }

        $puntajeTotal += $obtPts;

        $desagregadoClean[] = [
            'criterio' => $critName,
            'puntaje_maximo' => $maxPts,
            'puntaje_obtenido' => $obtPts
        ];
    }

    // Determine resultado_estado based on total points or standard logic
    $resultadoEstado = 'Calificado';
    if (isset($data['resultado_estado']) && !empty(trim($data['resultado_estado']))) {
        $resultadoEstado = trim($data['resultado_estado']);
    }

    $desagregadoJson = json_encode($desagregadoClean, JSON_UNESCAPED_UNICODE);

    $stmtUpdate = $pdo->prepare("
        UPDATE evaluaciones 
        SET puntaje_total = ?, 
            observaciones = ?, 
            nombre_jurado = CASE WHEN ? <> '' THEN ? ELSE nombre_jurado END, 
            desagregado_puntaje = ?,
            resultado_estado = ?
        WHERE id = ?
    ");

    $stmtUpdate->execute([
        $puntajeTotal,
        $observaciones,
        $nombreJurado,
        $nombreJurado,
        $desagregadoJson,
        $resultadoEstado,
        $evaluacionId
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Calificación y puntajes actualizados correctamente.',
        'evaluacion_id' => $evaluacionId,
        'puntaje_total' => $puntajeTotal
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error en el servidor al actualizar calificación: ' . $e->getMessage()]);
}
