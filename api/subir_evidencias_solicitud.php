<?php
/**
 * API: Subir Acta de Entrega y Evidencias Fotográficas
 * Permite al solicitante / beneficiario subir las pruebas de entrega sin necesidad de login.
 */
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
    if (isset($input['evidencias_fotos']) && is_string($input['evidencias_fotos'])) {
        $input['evidencias_fotos'] = json_decode($input['evidencias_fotos'], true);
    }
}

$solicitudId = isset($input['solicitud_id']) ? (int)$input['solicitud_id'] : 0;
$token = isset($input['token']) ? trim($input['token']) : '';
$cedula = isset($input['cedula']) ? trim($input['cedula']) : '';
$actaUrl = isset($input['acta_entrega_url']) ? trim($input['acta_entrega_url']) : '';
$fotos = isset($input['evidencias_fotos']) && is_array($input['evidencias_fotos']) ? $input['evidencias_fotos'] : [];

if (empty($actaUrl)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Debe adjuntar el Acta de Entrega firmada (PDF o Imagen).']);
    exit;
}

if (empty($fotos)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Debe adjuntar al menos una fotografía como evidencia de entrega en campo.']);
    exit;
}

try {
    $where = [];
    $params = [];

    if (!empty($token)) {
        $where[] = "token_seguimiento = :token";
        $params['token'] = $token;
    } elseif ($solicitudId > 0 && !empty($cedula)) {
        $where[] = "id = :id AND cedula_productor = :cedula";
        $params['id'] = $solicitudId;
        $params['cedula'] = $cedula;
    } elseif ($solicitudId > 0) {
        $where[] = "id = :id";
        $params['id'] = $solicitudId;
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Parámetros de identificación insuficientes']);
        exit;
    }

    $stmtCheck = $pdo->prepare("SELECT id, codigo_solicitud, estado, nombre_productor FROM kardex_solicitudes WHERE " . implode(" AND ", $where));
    $stmtCheck->execute($params);
    $solicitud = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$solicitud) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Solicitud no encontrada o documento no coincide']);
        exit;
    }

    if ($solicitud['estado'] === 'PENDIENTE') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'La solicitud aún no ha sido despachada por el equipo de Kardex. Las evidencias se cargan posterior al retiro de los insumos.']);
        exit;
    }

    $fotosJson = json_encode($fotos, JSON_UNESCAPED_UNICODE);

    $stmtUpd = $pdo->prepare("
        UPDATE kardex_solicitudes 
        SET acta_entrega_url = :acta_url, 
            evidencias_fotos = :fotos_json, 
            fecha_evidencias = NOW(),
            estado = 'EVIDENCIAS_CARGADAS' 
        WHERE id = :id
    ");

    $stmtUpd->execute([
        'acta_url' => $actaUrl,
        'fotos_json' => $fotosJson,
        'id' => $solicitud['id']
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Acta de entrega y evidencias fotográficas cargadas exitosamente. El equipo administrador revisará los soportes para emitir el Paz y Salvo.',
        'codigo_solicitud' => $solicitud['codigo_solicitud'],
        'estado' => 'EVIDENCIAS_CARGADAS'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al guardar evidencias: ' . $e->getMessage()]);
}
