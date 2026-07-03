<?php
/**
 * API to fetch all event participations
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_config.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

try {
    $stmt = $pdo->query("
        SELECT pe.id_evento, pe.id_productor, pe.fecha_evento, pe.nombre_evento, pe.observaciones, pe.fecha_creacion, p.nombre_completo as productor_nombre
        FROM participacion_eventos pe
        JOIN productores_sumapaz p ON pe.id_productor = p.id
        ORDER BY pe.fecha_evento DESC
    ");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $events]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener las participaciones: ' . $e->getMessage()]);
}
