<?php
/**
 * API Endpoint: Upload photographic evidence for contest evaluation
 * Saves binary image BLOB into `imagenes` table and links `imagen_id` in `evaluaciones`
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido. Utilice POST.']);
    exit;
}

try {
    $evaluacionId = isset($_POST['evaluacion_id']) ? (int)$_POST['evaluacion_id'] : 0;
    
    // Check if payload is JSON instead of FormData
    if (!$evaluacionId) {
        $inputRaw = file_get_contents('php://input');
        $jsonData = json_decode($inputRaw, true);
        if ($jsonData) {
            $evaluacionId = isset($jsonData['evaluacion_id']) ? (int)$jsonData['evaluacion_id'] : 0;
        }
    }

    if (!$evaluacionId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID de evaluación no proporcionado o inválido.']);
        exit;
    }

    // Verify evaluation exists
    $stmtCheck = $pdo->prepare("SELECT id FROM evaluaciones WHERE id = ?");
    $stmtCheck->execute([$evaluacionId]);
    if (!$stmtCheck->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'La evaluación especificada no existe.']);
        exit;
    }

    $datosImagen = null;
    $mimeType = 'image/jpeg';
    $nombreArchivo = 'evidencia_eval_' . $evaluacionId . '.jpg';

    // 1. Check $_FILES upload
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['imagen']['tmp_name'];
        $nombreOriginal = $_FILES['imagen']['name'];
        $mimeType = $_FILES['imagen']['type'] ?: 'image/jpeg';
        if ($nombreOriginal) {
            $nombreArchivo = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $nombreOriginal);
        }
        $datosImagen = file_get_contents($tmpName);
    } 
    // 2. Check base64 JSON upload
    else if (isset($jsonData['imagen_base64']) && !empty($jsonData['imagen_base64'])) {
        $b64 = $jsonData['imagen_base64'];
        if (preg_match('/^data:(image\/[a-zA-Z0-9\+\-]+);base64,/', $b64, $matches)) {
            $mimeType = $matches[1];
            $b64 = substr($b64, strpos($b64, ',') + 1);
        }
        $datosImagen = base64_decode($b64);
    }

    if (!$datosImagen || strlen($datosImagen) === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No se recibió ningún archivo de imagen válido.']);
        exit;
    }

    // Insert into `imagenes` table
    $stmtImg = $pdo->prepare("INSERT INTO imagenes (nombre_archivo, mime_type, datos_imagen) VALUES (?, ?, ?)");
    $stmtImg->execute([$nombreArchivo, $mimeType, $datosImagen]);
    $newImagenId = (int)$pdo->lastInsertId();

    // Update `evaluaciones` table with new imagen_id
    $stmtUpdate = $pdo->prepare("UPDATE evaluaciones SET imagen_id = ? WHERE id = ?");
    $stmtUpdate->execute([$newImagenId, $evaluacionId]);

    echo json_encode([
        'success' => true,
        'message' => 'Evidencia fotográfica guardada correctamente en la base de datos.',
        'evaluacion_id' => $evaluacionId,
        'imagen_id' => $newImagenId
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error en el servidor al subir imagen: ' . $e->getMessage()]);
}
