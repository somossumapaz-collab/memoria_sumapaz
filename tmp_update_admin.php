<?php
require 'api/db_config.php';
$pass = 'admin2026*';
$hash = password_hash($pass, PASSWORD_BCRYPT);
$stmt = $pdo->prepare("UPDATE usuarios SET password = :hash WHERE nombre = 'admin'");
$stmt->execute(['hash' => $hash]);
echo "Password updated successfully\n";
?>
