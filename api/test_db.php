<?php
/**
 * Test Database Connection
 */

require_once 'db_config.php';

header('Content-Type: application/json');

try {
    // Attempt to query categories as a simple test
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM categorias_productos");
    $result = $stmt->fetch();

    echo json_encode([
        'status' => 'success',
        'message' => 'Successfully connected to the database.',
        'database' => 'u949171480_somos_sumapaz',
        'categories_count' => $result['total']
    ]);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Connection failed: ' . $e->getMessage()
    ]);
}
?>