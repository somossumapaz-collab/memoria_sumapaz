<?php
/**
 * API to update a registered producer in the database
 */
require_once 'db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Get JSON raw POST data
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

// Validate required fields including ID
$required_fields = ['id', 'nombre', 'tipo_documento', 'cedula', 'fecha_nacimiento', 'telefono', 'vereda', 'nombre_predio'];
foreach ($required_fields as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "El campo $field es obligatorio."]);
        exit;
    }
}

try {
    // Check if the numero_documento already exists for a DIFFERENT producer
    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM productores_sumapaz WHERE numero_documento = :numero_documento AND id != :id");
    $check_stmt->execute([
        ':numero_documento' => $input['cedula'],
        ':id' => $input['id']
    ]);
    $exists = $check_stmt->fetchColumn();

    if ($exists > 0) {
        http_response_code(409); // Conflict
        echo json_encode(['error' => 'Ya existe OTRO productor registrado con este número de documento.']);
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE productores_sumapaz SET
            nombre_completo = :nombre_completo,
            tipo_documento = :tipo_documento,
            numero_documento = :numero_documento,
            fecha_nacimiento = :fecha_nacimiento,
            telefono = :telefono,
            correo_electronico = :correo_electronico,
            vereda = :vereda,
            nombre_predio = :nombre_predio
        WHERE id = :id
    ");

    $stmt->execute([
        ':nombre_completo' => $input['nombre'],
        ':tipo_documento' => $input['tipo_documento'],
        ':numero_documento' => $input['cedula'],
        ':fecha_nacimiento' => $input['fecha_nacimiento'],
        ':telefono' => $input['telefono'],
        ':correo_electronico' => $input['correo'] ?? null,
        ':vereda' => $input['vereda'],
        ':nombre_predio' => $input['nombre_predio'],
        ':id' => $input['id']
    ]);

    echo json_encode(['success' => true, 'message' => 'Productor actualizado exitosamente.']);
} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Database error in update_productor.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error al actualizar el productor. ' . $e->getMessage()]);
}
?>