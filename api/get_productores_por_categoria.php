<?php
/**
 * API to fetch producers filtered by category
 */
require_once 'db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$categoria_id = isset($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : 0;
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';

if ($categoria_id <= 0 && empty($tipo)) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

try {
    $sql = "
        SELECT 
            p.nombre_completo,
            p.tipo_documento,
            p.numero_documento,
            p.telefono,
            p.correo_electronico,
            p.vereda,
            c.nombre AS subcategoria
        FROM productores_sumapaz p
        INNER JOIN productor_categoria pc ON p.id = pc.productor_id
        INNER JOIN categorias_productivas c ON pc.categoria_id = c.id
        WHERE 1=1
    ";

    $params = [];
    if ($categoria_id > 0) {
        $sql .= " AND pc.categoria_id = :categoria_id";
        $params['categoria_id'] = $categoria_id;
    } elseif (!empty($tipo)) {
        $sql .= " AND c.tipo = :tipo";
        $params['tipo'] = $tipo;
    }

    $sql .= " ORDER BY p.nombre_completo ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $productores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $productores]);
} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Database error in get_productores_por_categoria.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error al obtener los productores por categoría.']);
}
?>
