<?php
require_once __DIR__ . '/../api/db_config.php';

$stmt = $pdo->query("
    SELECT p.id, p.beneficiario_2026, IFNULL(cp.puntaje, -1) as puntaje
    FROM productores_sumapaz p
    LEFT JOIN caracterizacion_productor cp ON p.id = cp.productor_id
");
$producers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total producers in DB: " . count($producers) . "\n";

usort($producers, function($a, $b) {
    return intval($b['puntaje']) - intval($a['puntaje']);
});

echo "Simulated Top 160 in update_global_beneficiaries:\n";
foreach (array_slice($producers, 0, 160) as $idx => $p) {
    $num = $idx + 1;
    echo "$num. ID: {$p['id']} | Score: {$p['puntaje']} | Beneficiario: {$p['beneficiario_2026']}\n";
}
?>
