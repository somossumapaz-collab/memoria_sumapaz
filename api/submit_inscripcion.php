<?php
/**
 * Submit Registration Form to productores_sumapaz table with PDF Upload
 */
require_once 'db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
        'nombre' => $_POST['nombre'] ?? null,
        'tipo_documento' => $_POST['tipo_documento'] ?? null,
        'cedula' => $_POST['cedula'] ?? null,
        'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
        'telefono' => $_POST['telefono'] ?? null,
        'correo' => $_POST['correo'] ?? null,
        'vereda' => $_POST['vereda'] ?? null,
        'nombre_predio' => $_POST['nombre_predio'] ?? null
    ];
}

// Validate required fields
$required_fields = ['nombre', 'tipo_documento', 'cedula', 'fecha_nacimiento', 'telefono', 'vereda', 'nombre_predio'];
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
    // Check if the numero_documento already exists
    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM productores_sumapaz WHERE numero_documento = :numero_documento");
    $check_stmt->execute([':numero_documento' => $input['cedula']]);
    $exists = $check_stmt->fetchColumn();

    if ($exists > 0) {
        http_response_code(409); // Conflict
        echo json_encode(['error' => 'Ya existe un productor registrado con este número de documento.']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO productores_sumapaz (
            nombre_completo,
            tipo_documento,
            numero_documento,
            fecha_nacimiento,
            telefono,
            correo_electronico,
            vereda,
            nombre_predio,
            cedula_pdf
        ) VALUES (
            :nombre_completo,
            :tipo_documento,
            :numero_documento,
            :fecha_nacimiento,
            :telefono,
            :correo_electronico,
            :vereda,
            :nombre_predio,
            :cedula_pdf
        )
    ");

    $stmt->execute([
        ':nombre_completo' => $input['nombre'],
        ':tipo_documento' => $input['tipo_documento'],
        ':numero_documento' => $input['cedula'],
        ':fecha_nacimiento' => $input['fecha_nacimiento'],
        ':telefono' => $input['telefono'],
        ':correo_electronico' => $input['correo'] ?? null,
        ':vereda' => $input['vereda'],
        ':nombre_predio' => $input['nombre_predio'],
        ':cedula_pdf' => $cedulaPath
    ]);

    echo json_encode(['success' => true, 'message' => 'Inscripción guardada exitosamente.']);
} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Database error in submit_inscripcion.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error al guardar la inscripción: ' . $e->getMessage()]);
}
?>