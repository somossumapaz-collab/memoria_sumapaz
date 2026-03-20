<?php
require_once 'db_config.php';
header('Content-Type: application/json');

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Fetch all trips with origin/destination coordinates
    $stmtViajes = $pdo->query("
        SELECT 
            tv.id, tv.created_at AS fecha_hora, tv.funcionario, tv.tipo_documento, tv.numero_documento, 
            tv.telefono, tv.email, tv.proposito, tv.proyecto_codigo, tv.proyecto_nombre, 
            tv.meta, tv.origen, tv.destino_final, tv.actividad, tv.created_at AS created_at_original,
            vc1.lat AS origen_lat, vc1.lon AS origen_lon,
            vc2.lat AS destino_lat, vc2.lon AS destino_lon
        FROM transporte_viajes tv
        LEFT JOIN veredas_coordenadas vc1 ON TRIM(UPPER(tv.origen)) = TRIM(UPPER(vc1.SCANOMBRE)) COLLATE utf8mb4_unicode_ci
        LEFT JOIN veredas_coordenadas vc2 ON TRIM(UPPER(tv.destino_final)) = TRIM(UPPER(vc2.SCANOMBRE)) COLLATE utf8mb4_unicode_ci
        ORDER BY tv.fecha_hora DESC
    ");
    $viajes = $stmtViajes->fetchAll(PDO::FETCH_ASSOC);

    // 2. Fetch all paths (recorridos) with coordinates
    $stmtRecorridos = $pdo->query("
        SELECT 
            tr.viaje_id, tr.vereda, tr.orden, tr.es_parada, 
            vc.lat, vc.lon
        FROM transporte_recorridos tr
        LEFT JOIN veredas_coordenadas vc ON TRIM(UPPER(tr.vereda)) = TRIM(UPPER(vc.SCANOMBRE)) COLLATE utf8mb4_unicode_ci
        ORDER BY tr.viaje_id, tr.orden ASC
    ");
    $recorridos = $stmtRecorridos->fetchAll(PDO::FETCH_ASSOC);

    // 3. Fetch all assigned vehicles
    $stmtVehiculos = $pdo->query("
        SELECT 
            tvv.viaje_id, v.tipo_transporte, v.tipo_vehiculo, v.placa
        FROM transporte_viaje_vehiculo tvv
        JOIN transporte_vehiculos v ON tvv.vehiculo_id = v.id
    ");
    $vehiculos = $stmtVehiculos->fetchAll(PDO::FETCH_ASSOC);

    // Assembly
    $tripsById = [];
    foreach ($viajes as $v) {
        $v['recorridos'] = [];
        $v['vehiculos'] = [];
        $tripsById[$v['id']] = $v;
    }

    foreach ($recorridos as $r) {
        if (isset($tripsById[$r['viaje_id']])) {
            $tripsById[$r['viaje_id']]['recorridos'][] = $r;
        }
    }

    foreach ($vehiculos as $vh) {
        if (isset($tripsById[$vh['viaje_id']])) {
            $tripsById[$vh['viaje_id']]['vehiculos'][] = $vh;
        }
    }

    echo json_encode(['success' => true, 'data' => array_values($tripsById)]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
