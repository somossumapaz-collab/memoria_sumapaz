<?php
/**
 * Simple Authentication script
 * Uses hardcoded admin/admin credentials
 */
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

if (empty($input['username']) || empty($input['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Usuario y contraseña son requeridos.']);
    exit;
}

$user = $input['username'];
$pass = $input['password'];

if ($user === 'admin' && $pass === 'admin') {
    // Authentication successful
    $_SESSION['is_admin'] = true;
    $_SESSION['username'] = $user;

    echo json_encode(['success' => true, 'message' => 'Login exitoso', 'redirect' => 'productores_registrados']);
} else {
    // Authentication failed
    http_response_code(401);
    echo json_encode(['error' => 'Usuario o contraseña incorrectos.']);
}
?>