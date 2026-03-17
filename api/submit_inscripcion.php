<?php
/**
 * Submit Registration Form to productores_sumapaz table
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

// Validate required fields
$required_fields = ['nombre', 'tipo_documento', 'cedula', 'fecha_nacimiento', 'telefono', 'vereda', 'nombre_predio'];
foreach ($required_fields as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "El campo $field es obligatorio."]);
        exit;
    }
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO productores_sumapaz (
            nombre_completo,
            tipo_documento,
            numero_documento,
            fecha_nacimiento,
            telefono,
            correo_electronico,
            vereda,
            nombre_predio
        ) VALUES (
            :nombre_completo,
            :tipo_documento,
            :numero_documento,
            :fecha_nacimiento,
            :telefono,
            :correo_electronico,
            :vereda,
            :nombre_predio
        )
    ");

    $stmt->execute([
        ':nombre_completo' => $input['nombre'],
        ':tipo_documento' => $input['tipo_documento'],
        ':numero_documento' => $input['cedula'],
        ':fecha_nacimiento' => $input['fecha_nacimiento'],
        ':telefono' => $input['telefono'],
        ':correo_electronico' => $input['correo'] ?? null,
        ':vereda' => $input['vereda'],
        ':nombre_predio' => $input['nombre_predio']
    ]);

    echo json_encode(['success' => true, 'message' => 'Inscripción guardada exitosamente.']);
} catch (\PDOException $e) {
    http_response_code(500);
    // Be careful not to expose full database errors in production, but helpful for debugging
    error_log("Database error in submit_inscripcion.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error al guardar la inscripción. ' . $e->getMessage()]);
}
?>