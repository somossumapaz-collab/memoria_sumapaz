<?php
require_once __DIR__ . '/../api/db_config.php';

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'pmapc_%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "PMAPC tables found: " . implode(', ', $tables) . "\n";

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    foreach ($tables as $t) {
        $pdo->exec("TRUNCATE TABLE `$t`");
        echo "Truncated `$t`.\n";
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    $stmtProd = $pdo->query("SELECT COUNT(*) FROM productores_sumapaz");
    $prodCount = $stmtProd->fetchColumn();

    echo "\nVERIFICATION:\n";
    foreach ($tables as $t) {
        $cnt = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo "  Table `$t` count = $cnt\n";
    }
    echo "  Table `productores_sumapaz` count = $prodCount\n";
    echo "\nSUCCESS: All PMAPC data cleared completely! Producer records preserved ($prodCount producers).\n";

} catch (Exception $e) {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "Error: " . $e->getMessage() . "\n";
}
