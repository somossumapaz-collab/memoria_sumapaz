<?php
/**
 * API to add event participation for a producer and set ferias = 1 (true)
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

$id_productor = isset($input['id_productor']) ? intval($input['id_productor']) : 0;
$fecha_evento = isset($input['fecha_evento']) ? trim($input['fecha_evento']) : '';
$nombre_evento = isset($input['nombre_evento']) ? trim($input['nombre_evento']) : '';
$observaciones = isset($input['observaciones']) ? trim($input['observaciones']) : '';

if ($id_productor <= 0 || empty($fecha_evento) || empty($nombre_evento)) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos requeridos faltantes (productor, fecha y nombre de evento son obligatorios)']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Insert into participacion_eventos
    $stmt = $pdo->prepare("
        INSERT INTO participacion_eventos (id_productor, fecha_evento, nombre_evento, observaciones, fecha_creacion)
        VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([$id_productor, $fecha_evento, $nombre_evento, $observaciones]);

    // 2. Set ferias = 1 (true) in productores_sumapaz
    $stmtUpdate = $pdo->prepare("
        UPDATE productores_sumapaz
        SET ferias = 1
        WHERE id = ?
    ");
    $stmtUpdate->execute([$id_productor]);

    // 3. Clear session cache flag so get_productores.php will recalculate
    $_SESSION['recalculated_scores'] = false;
    unset($_SESSION['recalculated_scores']);

    // 4. Force recalculation of scores and standard/adjusted rankings in DB immediately
    recalculate_all_producers_scores($pdo);
    recalculate_beneficiarios($pdo);

    $pdo->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Error al guardar la participación: ' . $e->getMessage()]);
}
