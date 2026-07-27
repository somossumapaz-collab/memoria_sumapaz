<?php
require_once __DIR__ . '/../api/db_config.php';

$stmt = $pdo->prepare("SELECT data FROM pmapc_registros WHERE productor_id = 72");
$stmt->execute();
$json = $stmt->fetchColumn();
$parsed = json_decode($json, true);

echo "=== STRUCTURE OF PRODUCER 72 PMAPC DATA ===\n";
foreach ($parsed as $key => $val) {
    echo "\nKEY [$key]: " . (is_array($val) ? (array_keys($val) === range(0, count($val) - 1) ? "Array list of " . count($val) . " items" : "Assoc array with keys: " . implode(', ', array_keys($val))) : gettype($val) . " (len: " . strlen((string)$val) . ")");
}
