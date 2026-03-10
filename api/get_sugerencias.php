<?php
/**
 * API Endpoint: Get Product Suggestions for Autocomplete
 */

require_once 'db_config.php';

header('Content-Type: application/json');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $sql = "SELECT producto FROM productos WHERE producto LIKE :query LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':query' => '%' . $query . '%']);
    $suggestions = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode($suggestions);

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
}
?>