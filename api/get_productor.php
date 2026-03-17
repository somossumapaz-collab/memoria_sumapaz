<?php
/**
 * API to fetch a single registered producer from the database
 */
require_once 'db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing ID parameter']);
    exit;
}

$id = $_GET['id'];

try {
    $stmt = $pdo->prepare("
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
        WHERE id = :id
    ");

    $stmt->execute([':id' => $id]);
    $productor = $stmt->fetch();

    if ($productor) {
        echo json_encode(['success' => true, 'data' => $productor]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Productor no encontrado.']);
    }
} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Database error in get_productor.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error al obtener el productor.']);
}
?>