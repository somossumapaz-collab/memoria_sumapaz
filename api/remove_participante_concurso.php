<?php
/**
 * API Endpoint: Remove a Participant from a Contest
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    if (!$data) {
        $data = $_POST;
    }

    $cpId = isset($data['concurso_participacion_id']) ? (int)$data['concurso_participacion_id'] : 0;
    $concursoId = isset($data['concurso_id']) ? (int)$data['concurso_id'] : 0;
    $participanteId = isset($data['participante_id']) ? (int)$data['participante_id'] : 0;

    if (!$cpId && ($concursoId && $participanteId)) {
        $stmtFind = $pdo->prepare("SELECT id FROM concurso_participaciones WHERE concurso_id = ? AND participante_id = ?");
        $stmtFind->execute([$concursoId, $participanteId]);
        $cpId = (int)$stmtFind->fetchColumn();
    }

    if (!$cpId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No se especificó la participación a eliminar']);
        exit;
    }

    // Begin transaction
    $pdo->beginTransaction();

    // 1. Delete associated evaluations if any
    $stmtDelEval = $pdo->prepare("DELETE FROM evaluaciones WHERE concurso_participacion_id = ?");
    $stmtDelEval->execute([$cpId]);

    // 2. Delete contest participation record
    $stmtDelCP = $pdo->prepare("DELETE FROM concurso_participaciones WHERE id = ?");
    $stmtDelCP->execute([$cpId]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Participante removido del concurso exitosamente'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al desvincular participante: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
