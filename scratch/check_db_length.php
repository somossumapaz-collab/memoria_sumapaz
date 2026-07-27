<?php
require_once __DIR__ . '/../api/db_config.php';

$stmt = $pdo->prepare("SELECT id, CHAR_LENGTH(data) as len FROM pmapc_registros WHERE productor_id = 33");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo "ID 33 Record: " . print_r($row, true) . "\n";
