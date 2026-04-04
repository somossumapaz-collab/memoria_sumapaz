<?php
/**
 * API to fetch panaca producers from the database
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
            numero_documento, 
            vereda, 
            genero, 
            fecha_nacimiento, 
            telefono, 
            telefono_contacto, 
            correo, 
            estado, 
            cr, 
            fecha_convocatoria 
        FROM productores_panaca
        ORDER BY fecha_convocatoria DESC
    ");

    $productores = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $productores]);
} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Database error in get_convocatorias.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error al obtener los productores de panaca.']);
}
?>
