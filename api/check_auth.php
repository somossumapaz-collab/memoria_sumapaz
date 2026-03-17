<?php
/**
 * Script to check if user is authenticated
 * Can be used by frontend JS to protect routes
 */
session_start();

header('Content-Type: application/json');

if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    echo json_encode(['authenticated' => true, 'username' => $_SESSION['username']]);
} else {
    http_response_code(401);
    echo json_encode(['authenticated' => false, 'error' => 'No autorizado']);
}
?>