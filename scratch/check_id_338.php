<?php
require_once __DIR__ . '/../api/db_config.php';

$stmt = $pdo->prepare("SELECT * FROM productores_sumapaz WHERE id = 338");
$stmt->execute();
$prod = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Producer 338 record in productores_sumapaz:\n";
print_r($prod);

$stmtP = $pdo->prepare("SELECT * FROM pmapc_registros WHERE productor_id = 338");
$stmtP->execute();
$pmapc = $stmtP->fetch(PDO::FETCH_ASSOC);

echo "\nPMAPC record for 338 in pmapc_registros:\n";
if ($pmapc) {
    echo "Found record! Data length: " . strlen($pmapc['data']) . " bytes\n";
} else {
    echo "No PMAPC record found in pmapc_registros for productor_id = 338!\n";
}
