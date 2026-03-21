<?php
require_once 'db_config.php';
header('Content-Type: application/json');

try {
    // Check if the user is authenticated
    session_start();
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'No autorizado']);
        exit;
    }

    // Prepare and execute the query to get total avituallamiento producers
    $stmt = $pdo->prepare("SELECT id, nombre_completo, tipo_documento, numero_documento, fecha_nacimiento, telefono, correo_electronico, vereda, nombre_predio, id_cedula, id_rut, id_curso_manipulacion, id_certificacion_bancaria, fecha_creacion FROM proveedores_avituallamiento ORDER BY fecha_creacion DESC");
    $stmt->execute();
    $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $proveedores]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al obtener los proveedores: ' . $e->getMessage()]);
}
?>
