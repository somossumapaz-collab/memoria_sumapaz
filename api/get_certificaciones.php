<?php
/**
 * API to fetch all certifications
 */
require_once 'db_config.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT id, nombre FROM certificaciones ORDER BY nombre ASC");
    $certificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $certificaciones]);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener certificaciones: ' . $e->getMessage()]);
}
?>
