<?php
require_once __DIR__ . '/../api/db_config.php';

// 1. Clean up incorrect record for ID 280
$stmtDel = $pdo->prepare("DELETE FROM pmapc_registros WHERE productor_id = 280");
$stmtDel->execute();
echo "Cleaned up ID 280 (Paula Daniela Larrota Borray).\n";

// 2. Insert for ID 331 (Daniela Rojas Suarez)
$json_path = 'C:\Users\sotoc\Downloads\gemini-code-1785187600158.json';
$raw = file_get_contents($json_path);
$data = json_decode($raw, true);

$daniela_id = 331;
$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

$stmtCheck = $pdo->prepare("SELECT id FROM pmapc_registros WHERE productor_id = ?");
$stmtCheck->execute([$daniela_id]);
$existingId = $stmtCheck->fetchColumn();

if ($existingId) {
    $stmtUp = $pdo->prepare("UPDATE pmapc_registros SET data = ? WHERE id = ?");
    $stmtUp->execute([$jsonData, $existingId]);
    echo "Successfully UPDATED existing PMAPC record ID {$existingId} for producer ID 331 (Daniela Rojas Suarez).\n";
} else {
    $stmtIn = $pdo->prepare("INSERT INTO pmapc_registros (productor_id, data) VALUES (?, ?)");
    $stmtIn->execute([$daniela_id, $jsonData]);
    echo "Successfully INSERTED new PMAPC record for producer ID 331 (Daniela Rojas Suarez).\n";
}

// Verify payload length
$stmtVer = $pdo->prepare("SELECT CHAR_LENGTH(data) FROM pmapc_registros WHERE productor_id = ?");
$stmtVer->execute([$daniela_id]);
$len = $stmtVer->fetchColumn();
echo "Saved payload length in DB for ID 331: {$len} bytes.\n";
