<?php
/**
 * API: Get Kardex Dashboard Metrics & Options
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_config.php';

try {
    // 1. KPI Aggregates
    $stmtKpi = $pdo->query("
        SELECT 
            COUNT(id) as total_productos,
            COALESCE(SUM(stock_actual), 0) as stock_total,
            SUM(CASE WHEN stock_actual <= stock_minimo THEN 1 ELSE 0 END) as stock_bajo_count,
            SUM(CASE WHEN estado = 'VENCIDO' THEN 1 ELSE 0 END) as vencidos_count
        FROM kardex_productos
    ");
    $kpiData = $stmtKpi->fetch(PDO::FETCH_ASSOC);

    // 2. Movements count
    $stmtMov = $pdo->query("
        SELECT 
            SUM(CASE WHEN tipo = 'INGRESO' THEN 1 ELSE 0 END) as total_ingresos,
            SUM(CASE WHEN tipo = 'SALIDA' THEN 1 ELSE 0 END) as total_salidas,
            COALESCE(SUM(CASE WHEN tipo = 'INGRESO' THEN cantidad ELSE 0 END), 0) as cant_ingresada,
            COALESCE(SUM(CASE WHEN tipo = 'SALIDA' THEN cantidad ELSE 0 END), 0) as cant_salida
        FROM kardex_movimientos
    ");
    $movData = $stmtMov->fetch(PDO::FETCH_ASSOC);

    // 3. Categories with stock sum
    $stmtCats = $pdo->query("
        SELECT 
            c.id, 
            c.nombre, 
            COUNT(p.id) as total_items, 
            COALESCE(SUM(p.stock_actual), 0) as stock_categoria
        FROM kardex_categorias c
        LEFT JOIN kardex_productos p ON p.categoria_id = c.id
        GROUP BY c.id, c.nombre
        ORDER BY c.nombre ASC
    ");
    $categorias = $stmtCats->fetchAll(PDO::FETCH_ASSOC);

    // 4. Veredas list for destination selector
    $veredas = [];
    try {
        $stmtVeredas = $pdo->query("SELECT DISTINCT scanombre as vereda FROM veredas_coordenadas ORDER BY scanombre ASC");
        $veredas = $stmtVeredas->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $veredas = ['BETANIA', 'CHORRERAS', 'CONCEPCION', 'LAGUNITAS', 'LAS AURAS', 'LAS VEGAS', 'NAZARETH URBANO', 'NUEVA GRANADA', 'PASCA', 'RAIZAL', 'SAN JUAN', 'SAN JOSE', 'SANTA ROSA', 'TUNAL ALTO', 'TUNAL BAJO'];
    }

    echo json_encode([
        'success' => true,
        'kpis' => array_merge($kpiData, $movData),
        'categorias' => $categorias,
        'veredas' => $veredas
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener dashboard de Kardex: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
