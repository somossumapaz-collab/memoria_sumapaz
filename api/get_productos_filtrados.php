<?php
/**
 * API Endpoint: Get Filtered Products
 * Supports 'categoria' and 'search' parameters.
 */

require_once 'db_config.php';

header('Content-Type: application/json');

$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : null;
$id_categoria = isset($_GET['id_categoria']) ? $_GET['id_categoria'] : null;
$search = isset($_GET['search']) ? $_GET['search'] : null;

try {
    $sql = "SELECT 
                p.id,
                p.producto,
                p.descripcion,
                c.categoria
            FROM productos p
            JOIN categorias_productos c ON p.id_categoria = c.id
            WHERE 1=1";

    $params = [];

    if ($id_categoria) {
        $sql .= " AND p.id_categoria = :id_categoria";
        $params[':id_categoria'] = $id_categoria;
    } elseif ($categoria) {
        $sql .= " AND c.categoria = :categoria";
        $params[':categoria'] = $categoria;
    }

    if ($search) {
        $sql .= " AND (p.producto LIKE :search OR p.descripcion LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $productos = $stmt->fetchAll();

    echo json_encode($productos, JSON_PRETTY_PRINT);

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
}
?>