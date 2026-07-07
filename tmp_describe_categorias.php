<?php
require 'api/db_config.php';
$stmt = $pdo->query('SELECT DISTINCT categoria FROM productor_productos');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
