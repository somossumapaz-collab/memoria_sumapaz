<?php
/**
 * API Endpoint: Retrieve PMAPC Form Data
 * Fetches the saved JSON payload for a producer.
 */

require_once 'db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

$productor_id = $_GET['productor_id'] ?? $_GET['id'] ?? null;

if (!$productor_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El ID del productor es requerido.']);
    exit;
}

try {
    // 1. Fetch details of the producer to verify existence and return name/basic info
    $stmtProd = $pdo->prepare("SELECT id, nombre_completo, numero_documento, vereda, nombre_predio, telefono FROM productores_sumapaz WHERE id = ?");
    $stmtProd->execute([$productor_id]);
    $producer = $stmtProd->fetch();

    if (!$producer) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'El productor no existe.']);
        exit;
    }

    // 2. Fetch the PMAPC record if it exists
    $stmtPmapc = $pdo->prepare("SELECT data FROM pmapc_registros WHERE productor_id = ?");
    $stmtPmapc->execute([$productor_id]);
    $pmapc = $stmtPmapc->fetch();

    if ($pmapc) {
        $data = json_decode($pmapc['data'], true);
        echo json_encode([
            'success' => true,
            'exists' => true,
            'producer' => $producer,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => true,
            'exists' => false,
            'producer' => $producer,
            'data' => null
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    error_log("PMAPC fetch error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Error al recuperar los datos del PMAPC: ' . $e->getMessage()
    ]);
}
?>
