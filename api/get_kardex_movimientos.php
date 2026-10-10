<?php
/**
 * API: Get Kardex Movement Logs (Audit Trail)
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_config.php';

try {
    $prodId = isset($_GET['producto_id']) ? trim($_GET['producto_id']) : '';
    $tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $fechaInicio = isset($_GET['fecha_inicio']) ? trim($_GET['fecha_inicio']) : '';
    $fechaFin = isset($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : '';

    $where = ["1=1"];
    $params = [];

    if ($prodId !== '' && $prodId !== 'all') {
        $where[] = "m.producto_id = :prod_id";
        $params['prod_id'] = (int)$prodId;
    }

    if ($tipo !== '' && $tipo !== 'all') {
        $where[] = "m.tipo = :tipo";
        $params['tipo'] = strtoupper($tipo);
    }

    if ($search !== '') {
        $where[] = "(p.nombre LIKE :search OR m.responsable LIKE :search OR m.cedula_responsable LIKE :search OR m.vereda LIKE :search OR m.concepto LIKE :search OR m.documento_referencia LIKE :search OR m.observaciones LIKE :search)";
        $params['search'] = "%$search%";
    }

    if ($fechaInicio !== '') {
        $where[] = "DATE(m.fecha_movimiento) >= :fecha_inicio";
        $params['fecha_inicio'] = $fechaInicio;
    }

    if ($fechaFin !== '') {
        $where[] = "DATE(m.fecha_movimiento) <= :fecha_fin";
        $params['fecha_fin'] = $fechaFin;
    }

    $sql = "
        SELECT 
            m.id,
            m.producto_id,
            p.nombre as producto_nombre,
            p.unidad_medida,
            c.nombre as categoria_nombre,
            m.tipo,
            m.cantidad,
            m.stock_anterior,
            m.stock_resultante,
            m.fecha_movimiento,
            m.fecha_ingreso,
            m.fecha_salida,
            m.responsable,
            m.cedula_responsable,
            m.vereda,
            m.concepto,
            m.documento_referencia,
            m.observaciones,
            COALESCE(u.nombre, 'Sistema') as usuario_registra
        FROM kardex_movimientos m
        JOIN kardex_productos p ON p.id = m.producto_id
        JOIN kardex_categorias c ON c.id = p.categoria_id
        LEFT JOIN usuarios u ON u.id = m.usuario_id
        WHERE " . implode(" AND ", $where) . "
        ORDER BY m.id DESC
        LIMIT 500
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'count' => count($movimientos),
        'movimientos' => $movimientos
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al consultar historial de movimientos: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
