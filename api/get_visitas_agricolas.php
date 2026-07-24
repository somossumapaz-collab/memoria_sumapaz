<?php
/**
 * API Endpoint to Fetch Visitas Agrícolas
 */

require_once __DIR__ . '/db_config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM ambiental_visitas_agricolas WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $visita = $stmt->fetch();

        if (!$visita) {
            http_response_code(404);
            echo json_encode(['error' => 'Visita agrícola no encontrada'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Motivos
        $mStmt = $pdo->prepare("SELECT motivo FROM ambiental_motivos_visita_agricola WHERE visita_id = :visita_id");
        $mStmt->execute([':visita_id' => $id]);
        $visita['motivos'] = $mStmt->fetchAll(PDO::FETCH_COLUMN);

        // Tipos Huerta
        $hStmt = $pdo->prepare("SELECT tipo_huerta FROM ambiental_tipo_huerta WHERE visita_id = :visita_id");
        $hStmt->execute([':visita_id' => $id]);
        $visita['tiposHuerta'] = $hStmt->fetchAll(PDO::FETCH_COLUMN);

        // Cultivos
        $cStmt = $pdo->prepare("SELECT * FROM ambiental_cultivos_visita WHERE visita_id = :visita_id");
        $cStmt->execute([':visita_id' => $id]);
        $visita['cultivos'] = $cStmt->fetchAll();

        // Materiales
        $matStmt = $pdo->prepare("SELECT * FROM ambiental_materiales_entregados WHERE visita_id = :visita_id");
        $matStmt->execute([':visita_id' => $id]);
        $visita['materiales'] = $matStmt->fetchAll();

        $visita['muestra_suelo'] = (bool)$visita['muestra_suelo'];
        $visita['acepta_corresponsabilidad'] = (bool)$visita['acepta_corresponsabilidad'];

        echo json_encode(['success' => true, 'data' => $visita], JSON_UNESCAPED_UNICODE);
    } else {
        $stmt = $pdo->query("SELECT * FROM ambiental_visitas_agricolas ORDER BY id DESC");
        $visitas = $stmt->fetchAll();

        foreach ($visitas as &$v) {
            $mStmt = $pdo->prepare("SELECT motivo FROM ambiental_motivos_visita_agricola WHERE visita_id = :visita_id");
            $mStmt->execute([':visita_id' => $v['id']]);
            $v['motivos'] = $mStmt->fetchAll(PDO::FETCH_COLUMN);

            $hStmt = $pdo->prepare("SELECT tipo_huerta FROM ambiental_tipo_huerta WHERE visita_id = :visita_id");
            $hStmt->execute([':visita_id' => $v['id']]);
            $v['tiposHuerta'] = $hStmt->fetchAll(PDO::FETCH_COLUMN);

            $cStmt = $pdo->prepare("SELECT * FROM ambiental_cultivos_visita WHERE visita_id = :visita_id");
            $cStmt->execute([':visita_id' => $v['id']]);
            $v['cultivos'] = $cStmt->fetchAll();

            $matStmt = $pdo->prepare("SELECT * FROM ambiental_materiales_entregados WHERE visita_id = :visita_id");
            $matStmt->execute([':visita_id' => $v['id']]);
            $v['materiales'] = $matStmt->fetchAll();

            $v['muestra_suelo'] = (bool)$v['muestra_suelo'];
            $v['acepta_corresponsabilidad'] = (bool)$v['acepta_corresponsabilidad'];
        }

        echo json_encode(['success' => true, 'data' => $visitas], JSON_UNESCAPED_UNICODE);
    }
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
