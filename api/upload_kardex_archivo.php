<?php
/**
 * API: Upload file (Image / PDF) for Kardex Agroambiental
 * Handles: productos, comprobantes de movimiento, fotos de entrega, actas y evidencias.
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    $errCode = isset($_FILES['archivo']) ? $_FILES['archivo']['error'] : 'NO_FILE';
    echo json_encode(['success' => false, 'error' => 'No se subió ningún archivo o hubo un error en la carga (Código: ' . $errCode . ')']);
    exit;
}

$tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : 'comprobante';
$allowedTypes = ['producto' => 'productos', 'comprobante' => 'comprobantes', 'entrega' => 'entregas', 'acta' => 'actas', 'evidencia' => 'evidencias'];

if (!array_key_exists($tipo, $allowedTypes)) {
    $tipo = 'comprobante';
}

$subfolder = $allowedTypes[$tipo];
$uploadDir = dirname(__DIR__) . '/uploads/kardex/' . $subfolder . '/';

if (!file_exists($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

$file = $_FILES['archivo'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

$allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
if ($tipo === 'acta') {
    $allowedExts[] = 'pdf';
}

if (!in_array($ext, $allowedExts)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => 'Tipo de archivo no permitido. Solo se permiten imágenes (' . implode(', ', $allowedExts) . ')'
    ]);
    exit;
}

// 10MB max limit
if ($file['size'] > 10 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El archivo supera el tamaño máximo permitido de 10 MB']);
    exit;
}

$uniqueName = $tipo . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$targetPath = $uploadDir . $uniqueName;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    $relativeUrl = 'uploads/kardex/' . $subfolder . '/' . $uniqueName;
    echo json_encode([
        'success' => true,
        'message' => 'Archivo subido correctamente.',
        'file_url' => $relativeUrl,
        'filename' => $uniqueName,
        'tipo' => $tipo
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'No se pudo mover el archivo al servidor']);
}
