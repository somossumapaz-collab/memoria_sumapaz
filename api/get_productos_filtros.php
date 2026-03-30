<?php
/**
 * API to fetch unique values for products filters
 */
require_once 'db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

try {
    $stmt_cat = $pdo->query("SELECT DISTINCT categoria FROM productor_productos WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria ASC");
    $categorias = $stmt_cat->fetchAll(PDO::FETCH_COLUMN);

    $stmt_prod = $pdo->query("SELECT DISTINCT producto_normal FROM productor_productos WHERE producto_normal IS NOT NULL AND producto_normal != '' ORDER BY producto_normal ASC");
    $productos_normales = $stmt_prod->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true, 
        'data' => [
            'categorias' => $categorias,
            'productos_normales' => $productos_normales
        ]
    ]);
} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Database error in get_productos_filtros.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error al obtener los filtros de productos.']);
}
?>
