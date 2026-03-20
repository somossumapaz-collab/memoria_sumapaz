<?php
// Mock server variables
$_SERVER['REQUEST_METHOD'] = 'POST';
$login_script = file_get_contents('api/login.php');
$mock_json = json_encode(['username' => 'admin', 'password' => 'admin2026*']);
$login_script = str_replace("file_get_contents('php://input')", "'$mock_json'", $login_script);
$login_script = str_replace("session_start();", "/*session_start();*/", $login_script);
file_put_contents('api/tmp_mock_login.php', $login_script);
require 'api/tmp_mock_login.php';
?>
