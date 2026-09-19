<?php
/**
 * API Endpoint: Add a Participant to a Contest
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

    $concursoId = isset($data['concurso_id']) ? (int)$data['concurso_id'] : 0;
    $participanteId = isset($data['participante_id']) ? (int)$data['participante_id'] : 0;
    $doc = isset($data['documento_identidad']) ? trim($data['documento_identidad']) : '';
    $nombre = isset($data['nombre_completo']) ? trim($data['nombre_completo']) : '';
    $vereda = isset($data['vereda']) ? trim($data['vereda']) : '';
    $telefono = isset($data['telefono']) ? trim($data['telefono']) : '';
    $tipoDoc = isset($data['tipo_documento']) ? trim($data['tipo_documento']) : 'Cédula de Ciudadanía';

    if (!$concursoId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID de concurso requerido']);
        exit;
    }

    // Verify contest exists
    $stmtC = $pdo->prepare("SELECT id FROM concursos WHERE id = ?");
    $stmtC->execute([$concursoId]);
    if (!$stmtC->fetchColumn()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Concurso no encontrado']);
        exit;
    }

    $pdo->beginTransaction();

    // 1. Resolve or create participant in feria_participantes
    if (!$participanteId) {
        if (!$doc || !$nombre) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Se requiere documento de identidad y nombre completo para registrar el participante']);
            exit;
        }

        // Check if doc exists in feria_participantes
        $stmtCheck = $pdo->prepare("SELECT id FROM feria_participantes WHERE documento_identidad = ?");
        $stmtCheck->execute([$doc]);
        $existingId = $stmtCheck->fetchColumn();

        if ($existingId) {
            $participanteId = (int)$existingId;
        } else {
            // Insert new participant into feria_participantes
            $stmtInsP = $pdo->prepare("
                INSERT INTO feria_participantes (feria_id, documento_identidad, tipo_documento, nombre_completo, vereda, telefono, asistio)
                VALUES (1, ?, ?, ?, ?, ?, 1)
            ");
            $stmtInsP->execute([$doc, $tipoDoc, $nombre, $vereda, $telefono]);
            $participanteId = (int)$pdo->lastInsertId();
        }
    }

    // 2. Check if already enrolled in this contest
    $stmtCheckCP = $pdo->prepare("SELECT id FROM concurso_participaciones WHERE concurso_id = ? AND participante_id = ?");
    $stmtCheckCP->execute([$concursoId, $participanteId]);
    $existingCP = $stmtCheckCP->fetchColumn();

    if ($existingCP) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'El participante ya está inscrito en este concurso']);
        exit;
    }

    // 3. Enroll into concurso_participaciones
    $stmtInsCP = $pdo->prepare("INSERT INTO concurso_participaciones (concurso_id, participante_id) VALUES (?, ?)");
    $stmtInsCP->execute([$concursoId, $participanteId]);
    $newCPId = (int)$pdo->lastInsertId();

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Participante agregado al concurso exitosamente',
        'concurso_participacion_id' => $newCPId,
        'participante_id' => $participanteId
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al agregar participante: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
