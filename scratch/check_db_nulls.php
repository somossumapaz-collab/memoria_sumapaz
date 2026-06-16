<?php
require_once 'api/db_config.php';

$total = $pdo->query("SELECT COUNT(*) FROM caracterizacion_productor")->fetchColumn();
$nulls = $pdo->query("SELECT COUNT(*) FROM caracterizacion_productor WHERE puntaje IS NULL")->fetchColumn();
$notNulls = $pdo->query("SELECT COUNT(*) FROM caracterizacion_productor WHERE puntaje IS NOT NULL")->fetchColumn();

echo "Total characterized: $total\n";
echo "Null scores: $nulls\n";
echo "Non-null scores: $notNulls\n";

if ($nulls > 0) {
    echo "Warning: There are still $nulls characterized producers with NULL scores.\n";
} else {
    echo "Excellent! All characterized producers have a score.\n";
}
