<?php
/**
 * API to toggle producer's beneficiary status (exclude or set to eligible)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_config.php';
require_once 'score_helper.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? intval($input['id']) : 0;
$status = isset($input['status']) ? intval($input['status']) : 0;

if ($id <= 0 || !in_array($status, [0, 2])) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos']);
    exit;
}

try {
    // 1. Update the target producer's eligibility status
    $stmt = $pdo->prepare("UPDATE productores_sumapaz SET beneficiario_2026 = ? WHERE id = ?");
    $stmt->execute([$status, $id]);

    // 2. Recalculate beneficiaries lists
    recalculate_beneficiarios($pdo);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al actualizar el estado: ' . $e->getMessage()]);
}
?>
