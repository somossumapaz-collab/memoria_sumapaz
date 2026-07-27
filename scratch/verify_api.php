<?php
require_once __DIR__ . '/../api/db_config.php';

$stmt = $pdo->query("SELECT COUNT(*) FROM productores_sumapaz WHERE beneficiario_2026 = 1");
$cnt1 = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM productores_sumapaz WHERE beneficiario_2026 = 0");
$cnt0 = $stmt->fetchColumn();

echo "Database count for beneficiario_2026 = 1: $cnt1\n";
echo "Database count for beneficiario_2026 = 0: $cnt0\n";
