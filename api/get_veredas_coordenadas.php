<?php
/**
 * Fetch all vereda coordinates for mapping
 */
require_once 'db_config.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT id, scanombre as vereda, lat, lon FROM veredas_coordenadas");
    $coordenadas = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $coordenadas
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener coordenadas: ' . $e->getMessage()]);
}
?>
