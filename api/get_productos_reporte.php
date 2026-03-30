<?php
/**
 * API to fetch products with producer details, category dropdown filter and text search
 */
require_once 'db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

try {
    $sql = "
        SELECT 
            pp.id, 
            pp.productor_id, 
            p.nombre_completo AS productor_nombre, 
            CONCAT(p.tipo_documento, ' ', p.numero_documento) AS productor_documento, 
            pp.nombre AS producto_nombre, 
            pp.volumen, 
            pp.unidad_volumen, 
            pp.frecuencia, 
            pp.presentacion, 
            pp.calidad, 
            pp.precio, 
            pp.unidad_precio, 
            pp.producto_normal, 
            pp.categoria 
        FROM productor_productos pp
        INNER JOIN productores_sumapaz p ON pp.productor_id = p.id
        WHERE 1=1
    ";

    $params = [];

    if ($categoria !== '') {
        $sql .= " AND pp.categoria = :categoria";
        $params['categoria'] = $categoria;
    }

    if ($search !== '') {
        $sql .= " AND (pp.nombre LIKE :search1 OR pp.producto_normal LIKE :search2)";
        $params['search1'] = '%' . $search . '%';
        $params['search2'] = '%' . $search . '%';
    }

    $sql .= " ORDER BY p.nombre_completo ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $productos]);
} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Database error in get_productos_reporte.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error al obtener los productos: ' . $e->getMessage()]);
}
?>
