<?php
/**
 * API Endpoint to Fetch Visitas Pecuarias
 */

require_once __DIR__ . '/db_config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM ambiental_visitas_pecuarias WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $visita = $stmt->fetch();

        if (!$visita) {
            http_response_code(404);
            echo json_encode(['error' => 'Visita pecuaria no encontrada'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Obtener especies
        $espStmt = $pdo->prepare("SELECT especie FROM ambiental_visita_pecuaria_especies WHERE visita_id = :visita_id");
        $espStmt->execute([':visita_id' => $id]);
        $visita['especies'] = $espStmt->fetchAll(PDO::FETCH_COLUMN);
        $visita['primera_vez'] = (bool)$visita['primera_vez'];
        $visita['seguimiento'] = (bool)$visita['seguimiento'];
        $visita['acepta_corresponsabilidad'] = (bool)$visita['acepta_corresponsabilidad'];

        echo json_encode(['success' => true, 'data' => $visita], JSON_UNESCAPED_UNICODE);
    } else {
        $stmt = $pdo->query("SELECT * FROM ambiental_visitas_pecuarias ORDER BY id DESC");
        $visitas = $stmt->fetchAll();

        foreach ($visitas as &$v) {
            $espStmt = $pdo->prepare("SELECT especie FROM ambiental_visita_pecuaria_especies WHERE visita_id = :visita_id");
            $espStmt->execute([':visita_id' => $v['id']]);
            $v['especies'] = $espStmt->fetchAll(PDO::FETCH_COLUMN);
            $v['primera_vez'] = (bool)$v['primera_vez'];
            $v['seguimiento'] = (bool)$v['seguimiento'];
            $v['acepta_corresponsabilidad'] = (bool)$v['acepta_corresponsabilidad'];
        }

        echo json_encode(['success' => true, 'data' => $visitas], JSON_UNESCAPED_UNICODE);
    }
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
