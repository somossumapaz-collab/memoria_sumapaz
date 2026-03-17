<?php
/**
 * Script to handle logout
 */
session_start();

session_destroy();
setcookie(session_name(), '', time() - 3600, '/');

header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Logged out', 'redirect' => 'index']);
?>