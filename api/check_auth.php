<?php
/**
 * Script to check if user is authenticated
 * Can be used by frontend JS to protect routes
 */
session_start();

header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
    $rol_id = isset($_SESSION['rol_id']) ? (int)$_SESSION['rol_id'] : null;
    $is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    $is_concursos_only = ($user_id === 12 || $rol_id === 12);

    echo json_encode([
        'authenticated' => true, 
        'user_id' => $user_id,
        'username' => $_SESSION['username'], 
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