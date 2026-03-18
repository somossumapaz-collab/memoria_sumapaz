<?php
/**
 * Save Field Trip Record to Database
 * Tables: transporte_viajes, transporte_vehiculos, transporte_viaje_vehiculo, transporte_recorridos
 */
require_once 'db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Get JSON raw POST data
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Insert or Get Vehicle
    $placa = strtoupper(trim($input['placa']));
    $stmt_vehiculo_check = $pdo->prepare("SELECT id FROM transporte_vehiculos WHERE placa = :placa LIMIT 1");
    $stmt_vehiculo_check->execute([':placa' => $placa]);
    $vehiculo_id = $stmt_vehiculo_check->fetchColumn();

    if (!$vehiculo_id) {
        $stmt_vehiculo_ins = $pdo->prepare("
            INSERT INTO transporte_vehiculos (tipo_transporte, tipo_vehiculo, placa)
            VALUES (:tipo_transporte, :tipo_vehiculo, :placa)
        ");
        $stmt_vehiculo_ins->execute([
            ':tipo_transporte' => $input['tipo_transporte'],
            ':tipo_vehiculo' => $input['tipo_vehiculo'],
            ':placa' => $placa
        ]);
        $vehiculo_id = $pdo->lastInsertId();
    }

    // 2. Insert Voyage
    $stmt_viaje = $pdo->prepare("
        INSERT INTO transporte_viajes (
            fecha_hora, funcionario, tipo_documento, numero_documento, telefono, 
            email, proposito, proyecto_codigo, proyecto_nombre, meta, 
            origen, destino_final, actividad
        ) VALUES (
            :fecha_hora, :funcionario, :tipo_documento, :numero_documento, :telefono,
            :email, :proposito, :proyecto_codigo, :proyecto_nombre, :meta,
            :origen, :destino_final, :actividad
        )
    ");

    $stmt_viaje->execute([
        ':fecha_hora' => $input['fecha_hora'],
        ':funcionario' => $input['funcionario'],
        ':tipo_documento' => $input['tipo_documento'],
        ':numero_documento' => $input['numero_documento'],
        ':telefono' => $input['telefono'],
        ':email' => $input['email'],
        ':proposito' => $input['proposito'],
        ':proyecto_codigo' => $input['proyecto'] ?? null,
        ':proyecto_nombre' => $input['nombre_proyecto'] ?? null,
        ':meta' => $input['meta'] ?? null,
        ':origen' => $input['origen'],
        ':destino_final' => $input['destino_final'],
        ':actividad' => $input['actividad']
    ]);
    $viaje_id = $pdo->lastInsertId();

    // 3. Relation Voyage - Vehicle
    $stmt_rel = $pdo->prepare("INSERT INTO transporte_viaje_vehiculo (viaje_id, vehiculo_id) VALUES (?, ?)");
    $stmt_rel->execute([$viaje_id, $vehiculo_id]);

    // 4. Insert Route (Recorrido)
    if (!empty($input['recorrido_data']) && is_array($input['recorrido_data'])) {
        $stmt_recorrido = $pdo->prepare("
            INSERT INTO transporte_recorridos (viaje_id, orden, vereda, es_parada)
            VALUES (:viaje_id, :orden, :vereda, :es_parada)
        ");
        
        $orden = 1;
        foreach ($input['recorrido_data'] as $point) {
            $stmt_recorrido->execute([
                ':viaje_id' => $viaje_id,
                ':orden' => $orden++,
                ':vereda' => $point['name'],
                ':es_parada' => $point['isStop'] ? 1 : 0
            ]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Registro guardado exitosamente',
        'data' => [
            'hora' => $input['fecha_hora'],
            'funcionario' => $input['funcionario'],
            'origen' => $input['origen'],
            'placa' => $placa
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Error al guardar el registro: ' . $e->getMessage()]);
}
?>
