<?php
/**
 * API Endpoint to Submit Visita Pecuaria
 */

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/persona_helper.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Registrar / actualizar persona (Productor)
    if (!empty($data['cedula_usuario']) && !empty($data['usuario'])) {
        upsertPersona($pdo, [
            'documento' => $data['cedula_usuario'],
            'nombre' => $data['usuario'],
            'finca' => $data['finca'] ?? null,
            'vereda' => $data['vereda'] ?? null,
            'corregimiento' => $data['corregimiento'] ?? null,
            'cuenca' => $data['cuenca'] ?? null,
            'tipo_persona' => 'Productor'
        ]);
    }

    // Registrar profesional si aplica
    if (!empty($data['profesional'])) {
        upsertPersona($pdo, [
            'documento' => $data['tarjeta_profesional'] ?? $data['profesional'],
            'nombre' => $data['profesional'],
            'tipo_persona' => 'Profesional',
            'tarjeta_profesional' => $data['tarjeta_profesional'] ?? null
        ]);
    }

    // 2. Insertar visita pecuaria
    $stmt = $pdo->prepare("
        INSERT INTO ambiental_visitas_pecuarias (
            fecha, corregimiento, vereda, finca, cuenca, hora_inicio, hora_fin,
            latitud, longitud, usuario, primera_vez, seguimiento, fecha_visita_anterior,
            diagnostico, procedimiento, recomendaciones, acepta_corresponsabilidad,
            proxima_visita, profesional, tarjeta_profesional, cedula_operario, cedula_usuario,
            firma_profesional, firma_operario, firma_usuario
        ) VALUES (
            :fecha, :corregimiento, :vereda, :finca, :cuenca, :hora_inicio, :hora_fin,
            :latitud, :longitud, :usuario, :primera_vez, :seguimiento, :fecha_visita_anterior,
            :diagnostico, :procedimiento, :recomendaciones, :acepta_corresponsabilidad,
            :proxima_visita, :profesional, :tarjeta_profesional, :cedula_operario, :cedula_usuario,
            :firma_profesional, :firma_operario, :firma_usuario
        )
    ");

    $stmt->execute([
        ':fecha' => $data['fecha'] ?? date('Y-m-d'),
        ':corregimiento' => $data['corregimiento'] ?? '',
        ':vereda' => $data['vereda'] ?? '',
        ':finca' => $data['finca'] ?? '',
        ':cuenca' => $data['cuenca'] ?? '',
        ':hora_inicio' => $data['hora_inicio'] ?? '',
        ':hora_fin' => $data['hora_fin'] ?? '',
        ':latitud' => isset($data['latitud']) ? (float)$data['latitud'] : null,
        ':longitud' => isset($data['longitud']) ? (float)$data['longitud'] : null,
        ':usuario' => $data['usuario'] ?? '',
        ':primera_vez' => !empty($data['primera_vez']) ? 1 : 0,
        ':seguimiento' => !empty($data['seguimiento']) ? 1 : 0,
        ':fecha_visita_anterior' => $data['fecha_visita_anterior'] ?? null,
        ':diagnostico' => $data['diagnostico'] ?? '',
        ':procedimiento' => $data['procedimiento'] ?? '',
        ':recomendaciones' => $data['recomendaciones'] ?? '',
        ':acepta_corresponsabilidad' => !empty($data['acepta_corresponsabilidad']) ? 1 : 0,
        ':proxima_visita' => $data['proxima_visita'] ?? null,
        ':profesional' => $data['profesional'] ?? '',
        ':tarjeta_profesional' => $data['tarjeta_profesional'] ?? '',
        ':cedula_operario' => $data['cedula_operario'] ?? '',
        ':cedula_usuario' => $data['cedula_usuario'] ?? '',
        ':firma_profesional' => $data['firma_profesional'] ?? null,
        ':firma_operario' => $data['firma_operario'] ?? null,
        ':firma_usuario' => $data['firma_usuario'] ?? null
    ]);

    $visitaId = $pdo->lastInsertId();

    // 3. Insertar especies
    if (!empty($data['especies']) && is_array($data['especies'])) {
        $espStmt = $pdo->prepare("INSERT INTO ambiental_visita_pecuaria_especies (visita_id, especie) VALUES (:visita_id, :especie)");
        foreach ($data['especies'] as $especie) {
            $espStmt->execute([
                ':visita_id' => $visitaId,
                ':especie' => $especie
            ]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'id' => (int)$visitaId,
        'message' => 'Visita pecuaria guardada exitosamente'
    ], JSON_UNESCAPED_UNICODE);

} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al guardar visita pecuaria: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
