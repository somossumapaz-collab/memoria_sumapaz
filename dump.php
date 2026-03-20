<?php
require 'api/db_config.php';
$out = [];
$tables = ['transporte_viajes','transporte_recorridos','transporte_vehiculos','transporte_viaje_vehiculo'];
foreach($tables as $t) {
    $out[$t] = $pdo->query("DESCRIBE $t")->fetchAll(PDO::FETCH_ASSOC);
}
file_put_contents('schema.json', json_encode($out, JSON_PRETTY_PRINT));
?>
