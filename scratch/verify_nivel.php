<?php
require_once __DIR__ . '/../api/db_config.php';

echo "=== Verification of nivel_priorizacion column ===\n";

$stmt = $pdo->query("
    SELECT 
        beneficiario_2026, 
        nivel_priorizacion, 
        COUNT(*) as cnt 
    FROM productores_sumapaz 
    GROUP BY beneficiario_2026, nivel_priorizacion
    ORDER BY beneficiario_2026 DESC, nivel_priorizacion ASC
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $ben = $row['beneficiario_2026'];
    $lvl = $row['nivel_priorizacion'] === null ? 'NULL' : $row['nivel_priorizacion'];
    echo "beneficiario_2026: $ben | nivel_priorizacion: $lvl => {$row['cnt']} productores\n";
}
