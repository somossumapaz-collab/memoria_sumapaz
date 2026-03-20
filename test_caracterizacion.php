<?php
require 'api/db_config.php';
$stmt = $pdo->query('DESCRIBE caracterizacion_productor');
file_put_contents('cols.json', json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT));
?>
