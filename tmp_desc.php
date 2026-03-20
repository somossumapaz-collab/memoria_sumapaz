<?php
require 'api/db_config.php';
$tables = ['transporte_viajes', 'transporte_recorridos', 'transporte_vehiculos', 'transporte_viaje_vehiculo'];
foreach($tables as $t) {
    echo "=== $t ===\n";
    $stmt = $pdo->query("DESCRIBE $t");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
}
?>
