<?php
require_once __DIR__ . '/../api/db_config.php';

$stmt = $pdo->prepare("SELECT data FROM pmapc_registros WHERE productor_id = 165");
$stmt->execute();
$json = $stmt->fetchColumn();
$data = json_decode($json, true);

echo "=== INSPECTING F15 DATA FOR CARMEN ROSA ===\n";
$f15 = $data['PMAPC_F15'] ?? ($data['f15'] ?? null);
print_r($f15);
