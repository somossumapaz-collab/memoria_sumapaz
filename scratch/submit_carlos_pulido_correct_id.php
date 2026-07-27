<?php
require_once __DIR__ . '/../api/db_config.php';

// 1. Clean up incorrect record for ID 94
$stmtDel = $pdo->prepare("DELETE FROM pmapc_registros WHERE productor_id = 94");
$stmtDel->execute();
echo "Cleaned up ID 94 (Carlos Arturo Romero Romero).\n";

// 2. Insert for ID 187 (Carlos Arturo Pulido Torres)
$json_path = 'C:\Users\sotoc\Downloads\JSON Carlos Arturo Pulido.json';
$raw = file_get_contents($json_path);
$data = json_decode($raw, true);

$carlos_id = 187;
$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

$stmtCheck = $pdo->prepare("SELECT id FROM pmapc_registros WHERE productor_id = ?");
$stmtCheck->execute([$carlos_id]);
$existingId = $stmtCheck->fetchColumn();

if ($existingId) {
    $stmtUp = $pdo->prepare("UPDATE pmapc_registros SET data = ? WHERE id = ?");
    $stmtUp->execute([$jsonData, $existingId]);
    echo "Successfully UPDATED existing PMAPC record ID {$existingId} for producer ID 187 (Carlos Arturo Pulido Torres).\n";
} else {
    $stmtIn = $pdo->prepare("INSERT INTO pmapc_registros (productor_id, data) VALUES (?, ?)");
    $stmtIn->execute([$carlos_id, $jsonData]);
    echo "Successfully INSERTED new PMAPC record for producer ID 187 (Carlos Arturo Pulido Torres).\n";
}

// Verify payload length
$stmtVer = $pdo->prepare("SELECT CHAR_LENGTH(data) FROM pmapc_registros WHERE productor_id = ?");
$stmtVer->execute([$carlos_id]);
$len = $stmtVer->fetchColumn();
echo "Saved payload length in DB for ID 187: {$len} bytes.\n";
