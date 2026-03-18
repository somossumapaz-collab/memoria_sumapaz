<?php
/**
 * Fetch Transport Statistics
 */
require_once 'db_config.php';

header('Content-Type: application/json');

try {
    $stats = [];

    // 1. Total Trips
    $stmt = $pdo->query("SELECT count(*) as total FROM transporte_viajes");
    $stats['total_viajes'] = $stmt->fetch()['total'];

    // 2. Trips per Day (Last 30 days)
    $stmt = $pdo->query("
        SELECT DATE(fecha_hora) as fecha, count(*) as total 
        FROM transporte_viajes 
        GROUP BY DATE(fecha_hora) 
        ORDER BY fecha ASC 
        LIMIT 30
    ");
    $stats['viajes_por_dia'] = $stmt->fetchAll();

    // 3. Top Origins
    $stmt = $pdo->query("
        SELECT origen, count(*) as total 
        FROM transporte_viajes 
        GROUP BY origen 
        ORDER BY total DESC 
        LIMIT 10
    ");
    $stats['origenes_top'] = $stmt->fetchAll();

    // 4. Top Destinations
    $stmt = $pdo->query("
        SELECT destino_final, count(*) as total 
        FROM transporte_viajes 
        GROUP BY destino_final 
        ORDER BY total DESC 
        LIMIT 10
    ");
    $stats['destinos_top'] = $stmt->fetchAll();

    // 5. Vehicle Type Distribution
    // Joining with transporte_viaje_vehiculo to get actual usage frequency
    $stmt = $pdo->query("
        SELECT v.tipo_vehiculo, count(tvv.viaje_id) as total
        FROM transporte_vehiculos v
        JOIN transporte_viaje_vehiculo tvv ON v.id = tvv.vehiculo_id
        GROUP BY v.tipo_vehiculo
        ORDER BY total DESC
    ");
    $stats['tipos_vehiculo'] = $stmt->fetchAll();

    // 6. Purposes Distribution
    $stmt = $pdo->query("
        SELECT proposito, count(*) as total 
        FROM transporte_viajes 
        GROUP BY proposito 
        ORDER BY total DESC
    ");
    $stats['propositos'] = $stmt->fetchAll();

    // 7. Recent activity (last 5 trips)
    $stmt = $pdo->query("
        SELECT id, fecha_hora, funcionario, origen, destino_final 
        FROM transporte_viajes 
        ORDER BY fecha_hora DESC 
        LIMIT 5
    ");
    $stats['actividad_reciente'] = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener estadísticas: ' . $e->getMessage()]);
}
?>
