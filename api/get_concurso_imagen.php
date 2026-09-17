<?php
/**
 * API Endpoint: Stream Image BLOB from `imagenes` Table
 */
require_once __DIR__ . '/db_config.php';

$imageId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$imageId) {
    http_response_code(400);
    exit('ID de imagen inválido');
}

try {
    $stmt = $pdo->prepare("SELECT mime_type, datos_imagen FROM imagenes WHERE id = ?");
    $stmt->execute([$imageId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || empty($row['datos_imagen'])) {
        http_response_code(404);
        exit('Imagen no encontrada');
    }

    $mime = $row['mime_type'] ?: 'image/jpeg';
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: $mime");
    header("Content-Length: " . strlen($row['datos_imagen']));
    header("Cache-Control: public, max-age=86400");
    echo $row['datos_imagen'];
    exit;
} catch (Exception $e) {
    http_response_code(500);
    exit('Error al obtener la imagen');
}
