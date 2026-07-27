<?php
require_once __DIR__ . '/../api/db_config.php';

$json_path = 'C:\Users\sotoc\Downloads\gemini-code-1785185540986.json';
$raw = file_get_contents($json_path);
$data = json_decode($raw, true);

// Find Jose Aquino Muñoz in productores_sumapaz
$stmt = $pdo->query("SELECT id, nombre_completo, vereda, numero_documento FROM productores_sumapaz WHERE nombre_completo LIKE '%Aquino%' OR nombre_completo LIKE '%Muñoz%' OR nombre_completo LIKE '%Munoz%'");
$producers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found candidate producers for Jose Aquino:\n";
print_r($producers);

if (empty($producers)) {
    die("No producer found!");
}

$id = $producers[0]['id'];
echo "\nTargeting Producer ID: {$id} ({$producers[0]['nombre_completo']})\n";

// Insert/Update pmapc_registros
$stmtCheck = $pdo->prepare("SELECT id FROM pmapc_registros WHERE productor_id = ?");
$stmtCheck->execute([$id]);
$existingId = $stmtCheck->fetchColumn();

if ($existingId) {
    $stmtUp = $pdo->prepare("UPDATE pmapc_registros SET data = ? WHERE id = ?");
    $stmtUp->execute([json_encode($data, JSON_UNESCAPED_UNICODE), $existingId]);
    echo "Updated existing PMAPC record ID {$existingId} for producer {$id}.\n";
} else {
    $stmtIn = $pdo->prepare("INSERT INTO pmapc_registros (productor_id, data) VALUES (?, ?)");
    $stmtIn->execute([$id, json_encode($data, JSON_UNESCAPED_UNICODE)]);
    echo "Inserted new PMAPC record for producer {$id}.\n";
}
