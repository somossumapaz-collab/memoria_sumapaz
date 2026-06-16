<?php
require 'api/db_config.php';
$stmt = $pdo->query('SHOW TABLES');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

$schema = [];
foreach ($tables as $table) {
    $stmt = $pdo->query("DESCRIBE `$table`");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $schema[$table] = array_map(function($c) {
        return $c['Field'] . ' (' . $c['Type'] . ')';
    }, $columns);
}

file_put_contents('scratch/full_db_schema.json', json_encode($schema, JSON_PRETTY_PRINT));
echo "Schema printed to scratch/full_db_schema.json\n";
?>
