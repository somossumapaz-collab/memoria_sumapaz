<?php
require_once __DIR__ . '/../api/db_config.php';

try {
    echo "=== Producers Summary ===\n";
    $stmt = $pdo->query("SELECT beneficiario_2026, COUNT(*) as cnt FROM productores_sumapaz GROUP BY beneficiario_2026");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "beneficiario_2026: " . var_export($row['beneficiario_2026'], true) . " -> Count: " . $row['cnt'] . "\n";
    }

    echo "\n=== Columns of productores_sumapaz ===\n";
    $stmt = $pdo->query("DESCRIBE productores_sumapaz");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " | " . $row['Type'] . " | Null:" . $row['Null'] . " | Key:" . $row['Key'] . " | Default:" . var_export($row['Default'], true) . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
