<?php
/**
 * API to fetch registered producers from the database
 */
require_once 'db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

try {
    $stmt = $pdo->query("
        SELECT 
            id,
            nombre_completo,
            tipo_documento,
            numero_documento,
            fecha_nacimiento,
            telefono,
            correo_electronico,
            vereda,
            nombre_predio,
            fecha_creacion
        FROM productores_sumapaz
        ORDER BY fecha_creacion DESC
    ");

    $productores = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $productores]);
} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Database error in get_productores.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error al obtener los productores.']);
}
?>