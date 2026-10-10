<?php
/**
 * API: Get Kardex Products Catalog
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_config.php';

try {
    $catId = isset($_GET['categoria_id']) ? trim($_GET['categoria_id']) : '';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';

    $where = ["1=1"];
    $params = [];

    if ($catId !== '' && $catId !== 'all') {
        $where[] = "p.categoria_id = :cat_id";
        $params['cat_id'] = (int)$catId;
    }

    if ($search !== '') {
        $where[] = "(p.nombre LIKE :search OR p.unidad_medida LIKE :search OR p.notas LIKE :search OR c.nombre LIKE :search)";
        $params['search'] = "%$search%";
    }

    if ($estado !== '' && $estado !== 'all') {
        $where[] = "p.estado = :estado";
        $params['estado'] = $estado;
    }

    $sql = "
        SELECT 
            p.id,
            p.nombre,
            p.unidad_medida,
            p.stock_actual,
            p.stock_minimo,
            p.estado,
            p.notas,
            p.categoria_id,
            c.nombre as categoria_nombre,
            p.fecha_actualizacion
        FROM kardex_productos p
        JOIN kardex_categorias c ON c.id = p.categoria_id
        WHERE " . implode(" AND ", $where) . "
        ORDER BY c.nombre ASC, p.nombre ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'count' => count($productos),
        'productos' => $productos
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al consultar productos: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
