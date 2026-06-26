<?php
require_once __DIR__ . '/../api/db_config.php';

$ids = [4, 7, 82, 247, 307];
foreach ($ids as $id) {
    $stmt = $pdo->prepare("SELECT nombre_completo, beneficiario_2026 FROM productores_sumapaz WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    echo "ID: $id | Name: {$row['nombre_completo']} | beneficiario_2026: {$row['beneficiario_2026']}\n";
}
?>
