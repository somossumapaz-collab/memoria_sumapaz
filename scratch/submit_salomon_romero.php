<?php
require_once __DIR__ . '/../api/db_config.php';

$json_path = 'C:\Users\sotoc\Downloads\gemini-code-1785186833512.json';
$raw = file_get_contents($json_path);
$data = json_decode($raw, true);

echo "=== INSPECTING SALOMÓN ROMERO MORENO JSON ===\n";
echo "Keys count: " . count($data) . "\n";
echo "Persona entrevistada: " . ($data['PMAPC_F01']['persona_entrevistada'] ?? 'N/A') . "\n";
echo "Unidad productiva: " . ($data['PMAPC_F01']['nombre_unidad_productiva'] ?? 'N/A') . "\n";

// Find Salomón Romero Moreno in productores_sumapaz
$stmt = $pdo->query("SELECT id, nombre_completo, vereda, numero_documento FROM productores_sumapaz WHERE nombre_completo LIKE '%Salomon%' OR nombre_completo LIKE '%Salomón%'");
$producers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\nFound candidate producers:\n";
print_r($producers);

$salomon_id = null;
foreach ($producers as $p) {
    if (strpos(strtolower($p['nombre_completo']), 'salom') !== false) {
        $salomon_id = $p['id'];
        break;
    }
}

if (!$salomon_id && !empty($producers)) {
    $salomon_id = $producers[0]['id'];
}

if (!$salomon_id) {
    die("Salomón Romero Moreno not found in database!\n");
}

echo "\nTargeting Producer ID: {$salomon_id}\n";

// Insert / Update pmapc_registros
$stmtCheck = $pdo->prepare("SELECT id FROM pmapc_registros WHERE productor_id = ?");
$stmtCheck->execute([$salomon_id]);
$existingId = $stmtCheck->fetchColumn();

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

if ($existingId) {
    $stmtUp = $pdo->prepare("UPDATE pmapc_registros SET data = ? WHERE id = ?");
    $stmtUp->execute([$jsonData, $existingId]);
    echo "Successfully UPDATED existing PMAPC record ID {$existingId} for producer {$salomon_id}.\n";
} else {
    $stmtIn = $pdo->prepare("INSERT INTO pmapc_registros (productor_id, data) VALUES (?, ?)");
    $stmtIn->execute([$salomon_id, $jsonData]);
    echo "Successfully INSERTED new PMAPC record for producer {$salomon_id}.\n";
}

// Verify payload length
$stmtVer = $pdo->prepare("SELECT CHAR_LENGTH(data) FROM pmapc_registros WHERE productor_id = ?");
$stmtVer->execute([$salomon_id]);
$len = $stmtVer->fetchColumn();
echo "Saved payload length in DB: {$len} bytes.\n";
