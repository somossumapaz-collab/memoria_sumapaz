<?php
require_once __DIR__ . '/../api/db_config.php';

$stmt = $pdo->prepare("SELECT pr.id, p.nombre_completo, CHAR_LENGTH(pr.data) as len FROM pmapc_registros pr JOIN productores_sumapaz p ON pr.productor_id = p.id WHERE pr.productor_id = 165");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Saved Record Verification for Carmen Rosa Moreno Moreno (ID 165):\n";
print_r($row);
