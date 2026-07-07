<?php
require 'api/db_config.php';
$stmt = $pdo->query('SELECT categoria, producto_normal, frecuencia, COUNT(*) as count FROM productor_productos GROUP BY categoria, producto_normal, frecuencia ORDER BY count DESC LIMIT 30');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
