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

    // Basic Validation
    if (!$nombre || !$tipo_doc || !$cedula || !$fecha_nac || !$telefono || !$vereda || !$predio) {
        throw new Exception('Faltan campos obligatorios en el formulario.');
    }

    // Age calculation
    $birthDate = new DateTime($fecha_nac);
    $today = new DateTime('now');
    $age = $today->diff($birthDate)->y;

    if ($age < 14) {
        throw new Exception('El programa de Jóvenes Rurales está dirigido a personas desde los 14 años de edad.');
    }

    if ($age > 28) {
        throw new Exception('Solo se pueden inscribir personas entre 14 y 28 años de edad.');
    }

    // Check if provider is already registered
    $stmtCheck = $pdo->prepare("SELECT id FROM proveedores_jovenes WHERE numero_documento = :cedula");
    $stmtCheck->execute(['cedula' => $cedula]);
    if ($stmtCheck->fetchColumn()) {
        throw new Exception('Este documento ya se encuentra inscrito en el programa de Jóvenes Rurales.');
    }

    // Prepare Upload Directory
    $uploadDir = __DIR__ . '/../soportes_jovenes/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('No se pudo crear el directorio para guardar soportes.');
        }
    }

    // Document Validation
    if (!isset($_FILES['id_cedula']) || $_FILES['id_cedula']['error'] !== UPLOAD_ERR_OK) {
        $docLabel = ($age < 18) ? 'Tarjeta de Identidad o Documento del Joven' : 'Cédula de Ciudadanía';
        throw new Exception("Debe adjuntar la $docLabel en formato PDF.");
    }

    // Upload ID Document
    $tmpNameCed = $_FILES['id_cedula']['tmp_name'];
    $origNameCed = $_FILES['id_cedula']['name'];
    $extCed = strtolower(pathinfo($origNameCed, PATHINFO_EXTENSION));

    if ($extCed !== 'pdf') {
        throw new Exception('El documento de identidad debe ser exclusivamente en formato PDF.');
    }

    $nameCedulaFile = $cedula . 'cedula.' . $extCed;
    if (!move_uploaded_file($tmpNameCed, $uploadDir . $nameCedulaFile)) {
        throw new Exception('Error al guardar el documento de identidad en el servidor.');
    }

    $nameAutorizacionFile = null;

    // If minor (< 18), validate and upload parent authorization
    if ($age < 18) {
        if (!isset($_FILES['id_autorizacion_padres']) || $_FILES['id_autorizacion_padres']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Al ser menor de 18 años, debe adjuntar la Autorización del Padre, Madre o Tutor Legal en formato PDF.');
        }

        $tmpNameAut = $_FILES['id_autorizacion_padres']['tmp_name'];
        $origNameAut = $_FILES['id_autorizacion_padres']['name'];
        $extAut = strtolower(pathinfo($origNameAut, PATHINFO_EXTENSION));

        if ($extAut !== 'pdf') {
            throw new Exception('La autorización de los padres debe ser exclusivamente en formato PDF.');
        }

        $nameAutorizacionFile = $cedula . 'autorizacion.' . $extAut;
        if (!move_uploaded_file($tmpNameAut, $uploadDir . $nameAutorizacionFile)) {
            throw new Exception('Error al guardar el documento de autorización en el servidor.');
        }
    }

    // Insert into DB
    $stmt = $pdo->prepare("
        INSERT INTO proveedores_jovenes (
            nombre_completo, tipo_documento, numero_documento, fecha_nacimiento, edad,
            telefono, correo_electronico, vereda, nombre_predio,
            id_cedula, id_autorizacion_padres
        ) VALUES (
            :nombre, :tipo_doc, :cedula, :fecha_nac, :edad,
            :telefono, :correo, :vereda, :predio,
            :cedula_file, :aut_file
        )
    ");

    $stmt->execute([
        'nombre' => $nombre,
        'tipo_doc' => $tipo_doc,
        'cedula' => $cedula,
        'fecha_nac' => $fecha_nac,
        'edad' => $age,
        'telefono' => $telefono,
        'correo' => $correo,
        'vereda' => $vereda,
        'predio' => $predio,
        'cedula_file' => $nameCedulaFile,
        'aut_file' => $nameAutorizacionFile
    ]);

    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
