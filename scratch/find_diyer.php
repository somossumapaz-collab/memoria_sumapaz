<?php
require_once __DIR__ . '/../api/db_config.php';

$stmt = $pdo->query("SELECT id, nombre_completo, vereda, numero_documento FROM productores_sumapaz WHERE nombre_completo LIKE '%Diyer%' OR nombre_completo LIKE '%Diller%' OR nombre_completo LIKE '%Dier%'");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found Diyer records:\n";
print_r($rows);
