<?php
/**
 * API Endpoint to Delete Visita (Agrícola o Pecuaria)
 */

require_once __DIR__ . '/db_config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$tipo = $data['tipo'] ?? ($_GET['tipo'] ?? null);
$id = isset($data['id']) ? (int)$data['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : null);

if (!$tipo || !$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Parámetros insuficientes (tipo e id requeridos)']);
    exit;
}

try {
    if ($tipo === 'agricola') {
        $stmt = $pdo->prepare("DELETE FROM ambiental_visitas_agricolas WHERE id = :id");
        $stmt->execute([':id' => $id]);
    } else if ($tipo === 'pecuaria') {
        $stmt = $pdo->prepare("DELETE FROM ambiental_visitas_pecuarias WHERE id = :id");
        $stmt->execute([':id' => $id]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Tipo de visita no válido']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Visita eliminada correctamente'
    ], JSON_UNESCAPED_UNICODE);

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
