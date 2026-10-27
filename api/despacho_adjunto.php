<?php
/**
 * Descarga protegida de adjuntos del módulo Despacho.
 * Verifica sesión real de Despacho (ID 16 o Admin real) sin bypass localhost.
 */
require_once __DIR__ . '/despacho_auth.php';

$currentUser = verifyDespachoSession($pdo, true);

$adjuntoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($adjuntoId <= 0) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'ID de adjunto inválido.']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM despacho_adjuntos WHERE id = ? LIMIT 1");
$stmt->execute([$adjuntoId]);
$adj = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$adj) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Adjunto no encontrado.']);
    exit;
}

$fullPath = dirname(__DIR__) . '/' . ltrim($adj['ruta_relativa'], '/');
if (!file_exists($fullPath)) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'El archivo físico no existe en el servidor.']);
    exit;
}

header('Content-Type: ' . ($adj['mime_type'] ?: 'application/octet-stream'));
header('Content-Disposition: inline; filename="' . addslashes($adj['nombre_original']) . '"');
header('Content-Length: ' . filesize($fullPath));
header('X-Content-Type-Options: nosniff');
readfile($fullPath);
exit;
