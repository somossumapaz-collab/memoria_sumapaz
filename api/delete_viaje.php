<?php
/**
 * Delete a trip record
 */
require_once 'db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

if (empty($input['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de viaje requerido']);
    exit;
}

$id = $input['id'];

try {
    $pdo->beginTransaction();

    // 1. Delete relations in transporte_viaje_vehiculo
    $stmt1 = $pdo->prepare("DELETE FROM transporte_viaje_vehiculo WHERE viaje_id = ?");
    $stmt1->execute([$id]);

    // 2. Delete route records in transporte_recorridos
    $stmt2 = $pdo->prepare("DELETE FROM transporte_recorridos WHERE viaje_id = ?");
    $stmt2->execute([$id]);

    // 3. Delete the trip itself
    $stmt3 = $pdo->prepare("DELETE FROM transporte_viajes WHERE id = ?");
    $stmt3->execute([$id]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Registro eliminado correctamente']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Error al eliminar: ' . $e->getMessage()]);
}
?>
