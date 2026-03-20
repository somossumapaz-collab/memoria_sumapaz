<?php
require 'api/db_config.php';
$stmt = $pdo->query('SELECT SCANOMBRE, lat, lon FROM veredas_coordenadas');
file_put_contents('veredas.json', json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT));
?>
