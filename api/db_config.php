<?php
/**
 * Database Configuration for Somos Sumapaz
 */

$host = 'srv1220.hstgr.io';
$port = '3306';
$db = 'u949171480_somos_sumapaz';
$user = 'u949171480_sumapaz_admin';
$pass = 'Somossumapaz2026*'; // To be filled by the user later

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Connection failed: ' . $e->getMessage()]);
    exit;
}
?>