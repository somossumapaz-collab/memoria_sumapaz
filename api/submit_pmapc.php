<?php
/**
 * API Endpoint: Submit / Update PMAPC Form Data
 * Handles JSON payload storage for all 26 formats of the PMAPC.
 */

require_once 'db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

// 1. Ensure the pmapc_registros table exists (stored as JSON/LONGTEXT)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pmapc_registros (
            id INT AUTO_INCREMENT PRIMARY KEY,
            productor_id BIGINT UNSIGNED NOT NULL,
            data LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (productor_id) REFERENCES productores_sumapaz(id) ON DELETE CASCADE,
            UNIQUE KEY unique_productor (productor_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error checking/creating table: ' . $e->getMessage()]);
    exit;
}

// 2. Parse input data
// Check if the data comes in raw JSON or post body
$inputRaw = file_get_contents('php://input');
$inputData = json_decode($inputRaw, true);

if (!$inputData) {
    // Fallback to POST variables
    $productor_id = $_POST['productor_id'] ?? null;
    $pmapc_data = $_POST['data'] ?? null;
} else {
    $productor_id = $inputData['productor_id'] ?? null;
    $pmapc_data = $inputData['data'] ?? null;
}

if (!$productor_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El ID del productor es requerido.']);
    exit;
}

if (!$pmapc_data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Los datos del PMAPC son requeridos.']);
    exit;
}

// Ensure the data is a valid JSON string
if (is_array($pmapc_data)) {
    $pmapc_data_json = json_encode($pmapc_data, JSON_UNESCAPED_UNICODE);
} else {
    // If it's already a string, validate it is JSON
    $jsonTest = json_decode($pmapc_data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Estructura de datos inválida. Debe ser JSON.']);
        exit;
    }
    $pmapc_data_json = $pmapc_data;
}

try {
    // Use INSERT ... ON DUPLICATE KEY UPDATE for automatic UPSERT
    $stmt = $pdo->prepare("
        INSERT INTO pmapc_registros (productor_id, data) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([$productor_id, $pmapc_data_json]);

    echo json_encode([
        'success' => true, 
        'message' => 'Plan de Manejo Ambiental, Productivo y Comercial (PMAPC) guardado exitosamente.'
    ]);

} catch (Exception $e) {
    error_log("PMAPC save error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Error al guardar los datos del PMAPC: ' . $e->getMessage()
    ]);
}
?>
