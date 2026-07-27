<?php
require_once __DIR__ . '/../api/db_config.php';

$stmt = $pdo->prepare("SELECT id, nombre_completo, vereda, numero_documento FROM productores_sumapaz WHERE nombre_completo LIKE ?");
$stmt->execute(['%Carmen Rosa%']);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Carmen Rosa records in DB:\n";
print_r($rows);
