<?php
require 'api/db_config.php';
$stmt = $pdo->query('DESCRIBE usuarios');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
