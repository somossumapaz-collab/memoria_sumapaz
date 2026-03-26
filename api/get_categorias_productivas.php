<?php
/**
 * API to fetch productive categories
 */
require_once 'db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

try {
    $stmt = $pdo->query("SELECT id, tipo, nombre FROM categorias_productivas ORDER BY tipo, nombre");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $categorias]);
} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Database error in get_categorias_productivas.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error al obtener las categorías.']);
}
?>
