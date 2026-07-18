<?php
/**
 * API to update a registered producer in the database (supports PDF upload)
 */
require_once 'db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// 1. Parse input data (Supports application/json and multipart/form-data)
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, TRUE);
} else {
    $input = [
        'id' => $_POST['id'] ?? null,
        'nombre' => $_POST['nombre'] ?? null,
        'tipo_documento' => $_POST['tipo_documento'] ?? null,
        'cedula' => $_POST['cedula'] ?? null,
        'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
        'telefono' => $_POST['telefono'] ?? null,
        'correo' => $_POST['correo'] ?? null,
        'vereda' => $_POST['vereda'] ?? null,
        'nombre_predio' => $_POST['nombre_predio'] ?? null,
        'nombre_organizacion' => $_POST['nombre_organizacion'] ?? null
    ];
}

// Validate required fields including ID
$required_fields = ['id', 'nombre', 'tipo_documento', 'cedula', 'fecha_nacimiento', 'telefono', 'vereda', 'nombre_predio'];
foreach ($required_fields as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "El campo $field es obligatorio."]);
        exit;
    }
}

// Helper to clean document/filename
function cleanFilename($str) {
    return preg_replace('/[^A-Za-z0-9_\-]/', '', $str);
}

// 2. Handle PDF Upload
$cedulaPath = null;
if (isset($_FILES['cedula_file']) && $_FILES['cedula_file']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['cedula_file']['tmp_name'];
    $fileName = $_FILES['cedula_file']['name'];
    $fileType = $_FILES['cedula_file']['type'];
    
    // Validate file extension and MIME type
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if ($fileExtension !== 'pdf' || $fileType !== 'application/pdf') {
        http_response_code(400);
        echo json_encode(['error' => 'Solo se permiten archivos en formato PDF para la cédula.']);
        exit;
    }
    
    // Create folder if it doesn't exist
    $uploadDir = __DIR__ . '/../uploads/cedulas/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Generate unique name
    $newFileName = 'cedula_' . cleanFilename($input['cedula']) . '_' . time() . '.pdf';
    $destPath = $uploadDir . $newFileName;
    
    if (move_uploaded_file($fileTmpPath, $destPath)) {
        $cedulaPath = 'uploads/cedulas/' . $newFileName;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al guardar el archivo PDF de la cédula en el servidor.']);
        exit;
    }
}

try {
    // Check if the numero_documento already exists for a DIFFERENT producer
    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM productores_sumapaz WHERE numero_documento = :numero_documento AND id != :id");
    $check_stmt->execute([
        ':numero_documento' => $input['cedula'],
        ':id' => $input['id']
    ]);
    $exists = $check_stmt->fetchColumn();

    if ($exists > 0) {
        http_response_code(409); // Conflict
        echo json_encode(['error' => 'Ya existe OTRO productor registrado con este número de documento.']);
        exit;
    }

    $db_update_fields = [
        'nombre_completo = :nombre_completo',
        'tipo_documento = :tipo_documento',
        'numero_documento = :numero_documento',
        'fecha_nacimiento = :fecha_nacimiento',
        'telefono = :telefono',
        'correo_electronico = :correo_electronico',
        'vereda = :vereda',
        'nombre_predio = :nombre_predio'
    ];
    
    $params = [
        ':nombre_completo' => $input['nombre'],
        ':tipo_documento' => $input['tipo_documento'],
        ':numero_documento' => $input['cedula'],
        ':fecha_nacimiento' => $input['fecha_nacimiento'],
        ':telefono' => $input['telefono'],
        ':correo_electronico' => $input['correo'] ?? null,
        ':vereda' => $input['vereda'],
        ':nombre_predio' => $input['nombre_predio'],
        ':id' => $input['id']
    ];

    if ($cedulaPath !== null) {
        $db_update_fields[] = 'cedula_pdf = :cedula_pdf';
        $params[':cedula_pdf'] = $cedulaPath;
    }

    $sql = "UPDATE productores_sumapaz SET " . implode(', ', $db_update_fields) . " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Also update organization name in characterization if provided
    if (isset($input['nombre_organizacion']) && !empty($input['nombre_organizacion'])) {
        $stmt_org = $pdo->prepare("UPDATE caracterizacion_productor SET nombre_organizacion = ? WHERE productor_id = ?");
        $stmt_org->execute([$input['nombre_organizacion'], $input['id']]);
    }

    require_once 'score_helper.php';
    recalculate_and_save_score($pdo, $input['id']);

    echo json_encode(['success' => true, 'message' => 'Productor actualizado exitosamente.']);
} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Database error in update_productor.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error al actualizar el productor: ' . $e->getMessage()]);
}
?>