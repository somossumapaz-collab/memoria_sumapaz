<?php
require_once __DIR__ . '/../api/db_config.php';

$stmt = $pdo->query("
    SELECT productor_id, puntaje
    FROM caracterizacion_productor
    ORDER BY puntaje DESC
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total rows in caracterizacion_productor: " . count($rows) . "\n";
echo "Top 160 scores in DB:\n";
foreach (array_slice($rows, 0, 160) as $idx => $r) {
    $num = $idx + 1;
    echo "$num. ID: {$r['productor_id']} | Score in DB: {$r['puntaje']}\n";
}
?>
