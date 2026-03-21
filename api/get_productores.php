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
            p.id,
            p.nombre_completo,
            p.tipo_documento,
            p.numero_documento,
            p.fecha_nacimiento,
            p.telefono,
            p.correo_electronico,
            p.vereda,
            p.nombre_predio,
            p.fecha_creacion,
            p.mypime,
            CASE 
                WHEN MAX(cp.id) IS NOT NULL THEN 1
                ELSE 0
            END AS tiene_caracterizacion
        FROM productores_sumapaz p
        LEFT JOIN caracterizacion_productor cp ON p.id = cp.productor_id
        GROUP BY p.id
        ORDER BY p.fecha_creacion DESC
    ");

    $productores = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $productores]);
} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Database error in get_productores.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error al obtener los productores.']);
}
?>