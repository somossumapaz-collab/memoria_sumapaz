<?php
require_once __DIR__ . '/../api/db_config.php';

echo "=== CLEARING PMAPC RECORDS ONLY ===\n";

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM pmapc_registros");
    $before_pmapc = $stmt->fetchColumn();
    
    $stmt2 = $pdo->query("SELECT COUNT(*) FROM productores_sumapaz");
    $before_prod = $stmt2->fetchColumn();

    echo "Before: pmapc_registros count = $before_pmapc | productores_sumapaz count = $before_prod\n";

    // Truncate pmapc_registros
    $pdo->exec("TRUNCATE TABLE pmapc_registros");

    $stmtAfterPmapc = $pdo->query("SELECT COUNT(*) FROM pmapc_registros");
    $after_pmapc = $stmtAfterPmapc->fetchColumn();

    $stmtAfterProd = $pdo->query("SELECT COUNT(*) FROM productores_sumapaz");
    $after_prod = $stmtAfterProd->fetchColumn();

    echo "After: pmapc_registros count = $after_pmapc | productores_sumapaz count = $after_prod\n";

    if ($after_pmapc == 0 && $after_prod == $before_prod) {
        echo "SUCCESS: PMAPC records cleared completely. All $after_prod producer records were preserved intact!\n";
    } else {
        echo "WARNING: Unexpected counts after clear.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
