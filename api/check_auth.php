<?php
/**
 * Script to check if user is authenticated
 * Can be used by frontend JS to protect routes
 */
session_start();

header('Content-Type: application/json');

if (isset($_SESSION['user_id']) || (isset($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']))) {
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
    $rol_id = isset($_SESSION['rol_id']) ? (int)$_SESSION['rol_id'] : 1;
    $is_admin = true;
    $is_concursos_only = false;

    echo json_encode([
        'authenticated' => true, 
        'user_id' => $user_id,
        'username' => $_SESSION['username'] ?? 'admin', 
        'is_admin' => $is_admin,
        'rol' => $_SESSION['rol'] ?? 'USUARIO',
        'rol_id' => $rol_id,
        'is_concursos_only' => $is_concursos_only
    ]);
} else {
    http_response_code(401);
    echo json_encode(['authenticated' => false, 'error' => 'No autorizado']);
}
?>