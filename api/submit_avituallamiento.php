<?php
require_once 'db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

try {
    $nombre = $_POST['nombre_completo'] ?? '';
    $tipo_doc = $_POST['tipo_documento'] ?? '';
    $cedula = $_POST['numero_documento'] ?? '';
    $fecha_nac = $_POST['fecha_nacimiento'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $correo = $_POST['correo_electronico'] ?? '';
    $vereda = $_POST['vereda'] ?? '';
    $predio = $_POST['nombre_predio'] ?? '';

    // Basic Validation
    if (!$nombre || !$tipo_doc || !$cedula || !$fecha_nac || !$telefono || !$vereda || !$predio) {
        throw new Exception('Faltan campos obligatorios.');
    }

    // Prepare Upload Directory
    $uploadDir = __DIR__ . '/../soportes_avituallamiento/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('No se pudo crear el directorio de soportes.');
        }
    }

    $fileFields = [
        'id_rut' => 'rut',
        'id_curso_manipulacion' => 'curso_manipulacion',
        'id_certificacion_bancaria' => 'certificacion_bancaria'
    ];

    $savedFiles = [];

    // File Upload Processing
    foreach ($fileFields as $field => $suffix) {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("El archivo obligatorio para '$field' falta o tiene errores de subida.");
        }

        $tmpName = $_FILES[$field]['tmp_name'];
        $originalName = $_FILES[$field]['name'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        // e.g. 10305678rut.pdf
        $newName = $cedula . $suffix . '.' . $ext;
        $destPath = $uploadDir . $newName;

        if (!move_uploaded_file($tmpName, $destPath)) {
            throw new Exception("Error al guardar el archivo $field en el servidor.");
        }

        // Store the final filename for the database record
        $savedFiles[$field] = $newName;
    }

    // Insert into DB
    $stmt = $pdo->prepare("
        INSERT INTO proveedores_avituallamiento (
            nombre_completo, tipo_documento, numero_documento, fecha_nacimiento, 
            telefono, correo_electronico, vereda, nombre_predio,
            id_rut, id_curso_manipulacion, id_certificacion_bancaria
        ) VALUES (
            :nombre, :tipo_doc, :cedula, :fecha_nac,
            :telefono, :correo, :vereda, :predio,
            :rut, :curso, :certificacion
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
        'rut' => $savedFiles['id_rut'],
        'curso' => $savedFiles['id_curso_manipulacion'],
        'certificacion' => $savedFiles['id_certificacion_bancaria']
    ]);

    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
