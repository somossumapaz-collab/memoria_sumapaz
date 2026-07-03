<?php
require 'api/db_config.php';
$stmt = $pdo->query("DESCRIBE caracterizacion_productor");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "{$row['Field']} - {$row['Type']}\n";
}
?>
