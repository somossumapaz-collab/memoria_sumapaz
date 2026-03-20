<?php
require 'api/db_config.php';
$stmt = $pdo->query('SHOW TABLES');
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
?>
