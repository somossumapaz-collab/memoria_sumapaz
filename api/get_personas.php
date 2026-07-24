<?php
/**
 * API Endpoint to Fetch Personas from ambiental_persona
 */

require_once __DIR__ . '/db_config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $documento = isset($_GET['documento']) ? trim($_GET['documento']) : null;
    $tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : null;

    if ($documento) {
        $stmt = $pdo->prepare("SELECT * FROM ambiental_persona WHERE documento = :documento");
        $stmt->execute([':documento' => $documento]);
        $persona = $stmt->fetch();

        if (!$persona) {
            http_response_code(404);
            echo json_encode(['error' => 'Persona no encontrada'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode(['success' => true, 'data' => $persona], JSON_UNESCAPED_UNICODE);
    } else {
        $sql = "SELECT * FROM ambiental_persona";
        $params = [];
        if ($tipo) {
            $sql .= " WHERE tipo_persona = :tipo";
            $params[':tipo'] = $tipo;
        }
        $sql .= " ORDER BY nombre ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $personas = $stmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $personas], JSON_UNESCAPED_UNICODE);
    }
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
