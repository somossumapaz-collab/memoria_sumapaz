<?php
require_once 'db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

try {
    $nombre = trim($_POST['nombre_completo'] ?? '');
    $tipo_doc = trim($_POST['tipo_documento'] ?? '');
    $cedula = trim($_POST['numero_documento'] ?? '');
    $fecha_nac = trim($_POST['fecha_nacimiento'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $correo = trim($_POST['correo_electronico'] ?? '');
    $vereda = trim($_POST['vereda'] ?? '');
    $predio = trim($_POST['nombre_predio'] ?? '');
    $tecnica = trim($_POST['tecnica_artesania'] ?? '');

    // Basic Validation
    if (!$nombre || !$tipo_doc || !$cedula || !$fecha_nac || !$telefono || !$vereda || !$predio || !$tecnica) {
        throw new Exception('Faltan campos obligatorios en el formulario.');
    }

    // Check if provider is already registered
    $stmtCheck = $pdo->prepare("SELECT id FROM proveedores_tejedoras WHERE numero_documento = :cedula");
    $stmtCheck->execute(['cedula' => $cedula]);
    if ($stmtCheck->fetchColumn()) {
        throw new Exception('Este documento ya se encuentra inscrito en el programa de Tejedoras, Tejedores y Artesanos.');
    }

    // File Upload Validation for id_cedula
    if (!isset($_FILES['id_cedula']) || $_FILES['id_cedula']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Debe adjuntar la Cédula o Documento de Identidad en formato PDF.');
    }

    // Prepare Upload Directory
    $uploadDir = __DIR__ . '/../soportes_tejedoras/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('No se pudo crear el directorio para guardar soportes.');
        }
    }

    $tmpName = $_FILES['id_cedula']['tmp_name'];
    $originalName = $_FILES['id_cedula']['name'];
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if ($ext !== 'pdf') {
        throw new Exception('El documento debe ser exclusivamente en formato PDF.');
    }

    $newName = $cedula . 'cedula.' . $ext;
    $destPath = $uploadDir . $newName;

    if (!move_uploaded_file($tmpName, $destPath)) {
        throw new Exception('Error al guardar el documento de cédula en el servidor.');
    }

    // Insert into DB
    $stmt = $pdo->prepare("
        INSERT INTO proveedores_tejedoras (
            nombre_completo, tipo_documento, numero_documento, fecha_nacimiento, 
            telefono, correo_electronico, vereda, nombre_predio, tecnica_artesania, id_cedula
        ) VALUES (
            :nombre, :tipo_doc, :cedula, :fecha_nac,
            :telefono, :correo, :vereda, :predio, :tecnica, :cedula_file
        )
    ");

    $stmt->execute([
        'nombre' => $nombre,
        'tipo_doc' => $tipo_doc,
        'cedula' => $cedula,
        'fecha_nac' => $fecha_nac,
        'telefono' => $telefono,
        'correo' => $correo,
        'vereda' => $vereda,
        'predio' => $predio,
        'tecnica' => $tecnica,
        'cedula_file' => $newName
    ]);

    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
