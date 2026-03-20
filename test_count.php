<?php
require 'api/db_config.php';
$stmt = $pdo->query('SELECT COUNT(*) as c FROM veredas_coordenadas');
$c = $stmt->fetch()['c'];
echo "Veredas_coordenadas count: $c\n";
?>
