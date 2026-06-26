<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../api/db_config.php';
require_once __DIR__ . '/../api/score_helper.php';

echo "Database connected.\n";

$stmt = $pdo->query("SELECT productor_id FROM caracterizacion_productor");
$productores = $stmt->fetchAll(PDO::FETCH_COLUMN);
$total = count($productores);
echo "Found $total producers.\n";

$successCount = 0;
foreach ($productores as $idx => $pid) {
    $num = $idx + 1;
    echo "[$num/$total] Recalculating ID: $pid... ";
    flush();
    $res = recalculate_and_save_score($pdo, $pid);
    echo ($res ? "Success" : "Failed") . "\n";
    flush();
}
echo "All done! Success count: $successCount\n";
?>
