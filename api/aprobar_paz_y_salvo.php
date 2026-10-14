<?php
/**
 * API: Aprobar Paz y Salvo para Solicitud de Insumos
 * Requiere sesión de usuario administrativo.
 * Verifica que el acta de entrega y las evidencias fotográficas estén cargadas.
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!$input && !empty($_POST)) {
    $input = $_POST;
}

$solicitudId = isset($input['solicitud_id']) ? (int)$input['solicitud_id'] : 0;
$observaciones = isset($input['observaciones']) ? trim($input['observaciones']) : 'Paz y Salvo otorgado satisfactoriamente.';
$usuarioId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 14;

if ($solicitudId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de solicitud inválido']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM kardex_solicitudes WHERE id = :id");
    $stmt->execute(['id' => $solicitudId]);
    $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$solicitud) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Solicitud no encontrada']);
        exit;
    }

    if (empty($solicitud['acta_entrega_url'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No se puede otorgar Paz y Salvo: Falta el Acta de Entrega firmada.']);
        exit;
    }

    if (empty($solicitud['evidencias_fotos'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No se puede otorgar Paz y Salvo: Falta cargar las evidencias fotográficas de entrega.']);
        exit;
    }

    $stmtUpd = $pdo->prepare("
        UPDATE kardex_solicitudes 
        SET paz_y_salvo = 1, 
            estado = 'PAZ_Y_SALVO', 
            fecha_paz_y_salvo = NOW(), 
            usuario_paz_y_salvo = :usuario_id, 
            observaciones_paz_y_salvo = :obs 
        WHERE id = :id
    ");

    $stmtUpd->execute([
        'usuario_id' => $usuarioId,
        'obs' => $observaciones,
        'id' => $solicitudId
    ]);

    echo json_encode([
        'success' => true,
        'message' => "Paz y Salvo otorgado exitosamente para la solicitud {$solicitud['codigo_solicitud']}, el productor {$solicitud['nombre_productor']} y el proyecto {$solicitud['numero_proyecto']}.",
        'solicitud_id' => $solicitudId,
        'codigo_solicitud' => $solicitud['codigo_solicitud'],
        'paz_y_salvo' => 1,
        'estado' => 'PAZ_Y_SALVO'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al aprobar Paz y Salvo: ' . $e->getMessage()]);
}
