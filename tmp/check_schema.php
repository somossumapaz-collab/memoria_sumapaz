<?php
require 'api/db_config.php';
echo "Table: productores_panaca\n";
foreach($pdo->query('DESCRIBE productores_panaca') as $r) echo $r['Field'] . " - " . $r['Type'] . "\n";
echo "\nTable: productores_sumapaz\n";
foreach($pdo->query('DESCRIBE productores_sumapaz') as $r) echo $r['Field'] . " - " . $r['Type'] . "\n";
?>
