<?php
/**
 * API: Update Product Image in Kardex
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!$input && !empty($_POST)) {
    $input = $_POST;
}

$productoId = isset($input['producto_id']) ? (int)$input['producto_id'] : 0;
$imagenUrl = isset($input['imagen_url']) ? trim($input['imagen_url']) : '';

if ($productoId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de producto inválido']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE kardex_productos SET imagen_url = :imagen_url WHERE id = :id");
    $stmt->execute([
        'imagen_url' => $imagenUrl ?: null,
        'id' => $productoId
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Imagen de insumo actualizada con éxito.',
        'producto_id' => $productoId,
        'imagen_url' => $imagenUrl
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al actualizar imagen: ' . $e->getMessage()]);
}
