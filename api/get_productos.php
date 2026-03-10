<?php
/**
 * API Endpoint: Get Products with Categories
 */

require_once 'db_config.php';

header('Content-Type: application/json');

try {
    $sql = "SELECT 
                p.id,
                p.producto,
                p.descripcion,
                c.categoria
            FROM productos p
            JOIN categorias_productos c ON p.id_categoria = c.id";

    $stmt = $pdo->query($sql);
    $productos = $stmt->fetchAll();

    echo json_encode($productos, JSON_PRETTY_PRINT);

} catch (\PDOException $e) {
    echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
}
?>