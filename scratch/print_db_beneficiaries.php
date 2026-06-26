<?php
require_once __DIR__ . '/../api/db_config.php';

$stmt = $pdo->query("
    SELECT p.id, p.nombre_completo, p.vereda, p.beneficiario_2026, cp.puntaje
    FROM productores_sumapaz p
    JOIN caracterizacion_productor cp ON p.id = cp.productor_id
    WHERE p.beneficiario_2026 = 1
");
$beneficiaries = $stmt->fetchAll(PDO::FETCH_ASSOC);

usort($beneficiaries, function($a, $b) {
    return intval($b['puntaje']) - intval($a['puntaje']);
});

echo "Total active beneficiaries in DB: " . count($beneficiaries) . "\n";
echo "Showing last 20 beneficiaries:\n";
$last_20 = array_slice($beneficiaries, -20);
foreach ($last_20 as $idx => $b) {
    $pos = count($beneficiaries) - 20 + $idx + 1;
    echo "$pos. ID: {$b['id']} | Name: {$b['nombre_completo']} | Vereda: {$b['vereda']} | Score: {$b['puntaje']} | Status: {$b['beneficiario_2026']}\n";
}
?>
