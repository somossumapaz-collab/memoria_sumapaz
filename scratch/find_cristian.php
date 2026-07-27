<?php
require_once __DIR__ . '/../api/db_config.php';

$stmt = $pdo->query("SELECT id, nombre_completo, vereda, numero_documento FROM productores_sumapaz WHERE nombre_completo LIKE '%Cristian%' OR nombre_completo LIKE '%Morales%'");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found Cristian records:\n";
print_r($rows);
