<?php
require 'api/db_config.php';
$stmt = $pdo->query('SELECT categoria, nombre, frecuencia, COUNT(*) as count FROM productor_productos GROUP BY categoria, nombre, frecuencia ORDER BY count DESC LIMIT 10');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
