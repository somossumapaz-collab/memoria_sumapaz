<?php
require 'api/db_config.php';
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nombre = 'admin' OR email = 'admin'");
$stmt->execute();
print_r($stmt->fetch(PDO::FETCH_ASSOC));
?>
