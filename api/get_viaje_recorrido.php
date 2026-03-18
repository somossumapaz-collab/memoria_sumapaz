<?php
/**
 * API: Obtener recorrido de un viaje con coordenadas
 * Versión corregida y robusta
 */

require_once 'db_config.php';

// =========================
// 🔥 DEBUG (quítalo en producción)
// =========================
ini_set('display_errors', 1);
error_reporting(E_ALL);

// =========================
// HEADERS
// =========================
header('Content-Type: application/json');

// =========================
// VALIDACIÓN
// =========================
if (empty($_GET['viaje_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'ID de viaje requerido'
    ]);
    exit;
}

$viaje_id = intval($_GET['viaje_id']);

try {

    // =========================
    // 🔥 CONFIG PDO (MUY IMPORTANTE)
    // =========================
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // =========================
    // 1. VIAJE + COORDENADAS
    // =========================
    $stmt_viaje = $pdo->prepare("
        SELECT 
            tv.id,
            tv.funcionario,
            tv.created_at AS fecha_hora,
            tv.origen,
            tv.destino_final,

            vc1.lat  AS origen_lat,
            vc1.lon AS origen_lon,

            vc2.lat  AS destino_lat,
            vc2.lon AS destino_lon

        FROM transporte_viajes tv

        LEFT JOIN veredas_coordenadas vc1 
            ON TRIM(UPPER(tv.origen)) = TRIM(UPPER(vc1.SCANOMBRE))

        LEFT JOIN veredas_coordenadas vc2 
            ON TRIM(UPPER(tv.destino_final)) = TRIM(UPPER(vc2.SCANOMBRE))

        WHERE tv.id = ?
    ");

    $stmt_viaje->execute([$viaje_id]);
    $viaje = $stmt_viaje->fetch(PDO::FETCH_ASSOC);

    if (!$viaje) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Viaje no encontrado'
        ]);
        exit;
    }

    // =========================
    // 2. RECORRIDO
    // =========================
    $stmt_recorrido = $pdo->prepare("
        SELECT 
            tr.vereda,
            tr.orden,
            tr.es_parada,

            vc.lat,
            vc.lon AS lon

        FROM transporte_recorridos tr

        LEFT JOIN veredas_coordenadas vc 
            ON TRIM(UPPER(tr.vereda)) = TRIM(UPPER(vc.SCANOMBRE))

        WHERE tr.viaje_id = ?

        ORDER BY tr.orden ASC
    ");

    $stmt_recorrido->execute([$viaje_id]);
    $recorrido = $stmt_recorrido->fetchAll(PDO::FETCH_ASSOC);

    // =========================
    // 🔍 VALIDACIÓN EXTRA
    // =========================
    $missing = [];

    foreach ($recorrido as $r) {
        if (is_null($r['lat']) || is_null($r['lon'])) {
            $missing[] = $r['vereda'];
        }
    }

    if (is_null($viaje['origen_lat'])) {
        $missing[] = $viaje['origen'];
    }

    if (is_null($viaje['destino_lat'])) {
        $missing[] = $viaje['destino_final'];
    }

    // =========================
    // RESPUESTA FINAL
    // =========================
    echo json_encode([
        'success' => true,
        'viaje' => $viaje,
        'recorrido' => $recorrido,
        'debug' => [
            'veredas_sin_coordenadas' => $missing
        ]
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => 'Error en base de datos',
        'detalle' => $e->getMessage()
    ]);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => 'Error general',
        'detalle' => $e->getMessage()
    ]);
}
?>