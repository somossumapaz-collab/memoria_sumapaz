<?php
require_once __DIR__ . '/../api/db_config.php';

$stmt = $pdo->prepare("DELETE FROM pmapc_registros WHERE productor_id = 121");
$stmt->execute();
$deletedRows = $stmt->rowCount();

echo "Deleted {$deletedRows} PMAPC record(s) for Gilma Rocio Romero Guzman (ID 121) from pmapc_registros.\n";

// Verify ID 121 in get_pmapc
$stmtCheck = $pdo->prepare("SELECT * FROM pmapc_registros WHERE productor_id = 121");
$stmtCheck->execute();
$check = $stmtCheck->fetch();

if (!$check) {
    echo "SUCCESS: Gilma Rocio Romero Guzman (ID 121) has NO PMAPC record in DB now!\n";
} else {
    echo "WARNING: Record still present!\n";
}
