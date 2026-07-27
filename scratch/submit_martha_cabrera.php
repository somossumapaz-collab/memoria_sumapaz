<?php
require_once __DIR__ . '/../api/db_config.php';

$json_path = 'C:\Users\sotoc\Downloads\gemini-code-1785186350849.json';
$raw = file_get_contents($json_path);
$data = json_decode($raw, true);

echo "=== INSPECTING MARTHA YANETH CABRERA JSON ===\n";
echo "Keys count: " . count($data) . "\n";
echo "Persona entrevistada: " . ($data['PMAPC_F01']['persona_entrevistada'] ?? 'N/A') . "\n";
echo "Unidad productiva: " . ($data['PMAPC_F01']['nombre_unidad_productiva'] ?? 'N/A') . "\n";

// Find Martha Yaneth Cabrera in productores_sumapaz
$stmt = $pdo->query("SELECT id, nombre_completo, vereda, numero_documento FROM productores_sumapaz WHERE nombre_completo LIKE '%Martha%' OR nombre_completo LIKE '%Cabrera%' OR nombre_completo LIKE '%Yaneth%'");
$producers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\nFound candidate producers:\n";
print_r($producers);

$martha_id = null;
foreach ($producers as $p) {
    if (strpos(strtolower($p['nombre_completo']), 'martha') !== false && strpos(strtolower($p['nombre_completo']), 'cabrera') !== false) {
        $martha_id = $p['id'];
        break;
    }
}

if (!$martha_id && !empty($producers)) {
    foreach ($producers as $p) {
        if (strpos(strtolower($p['nombre_completo']), 'cabrera') !== false) {
            $martha_id = $p['id'];
            break;
        }
    }
}

if (!$martha_id) {
    die("Martha Yaneth Cabrera not found in database!\n");
}

echo "\nTargeting Producer ID: {$martha_id}\n";

// Insert / Update pmapc_registros
$stmtCheck = $pdo->prepare("SELECT id FROM pmapc_registros WHERE productor_id = ?");
$stmtCheck->execute([$martha_id]);
$existingId = $stmtCheck->fetchColumn();

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

if ($existingId) {
    $stmtUp = $pdo->prepare("UPDATE pmapc_registros SET data = ? WHERE id = ?");
    $stmtUp->execute([$jsonData, $existingId]);
    echo "Successfully UPDATED existing PMAPC record ID {$existingId} for producer {$martha_id}.\n";
} else {
    $stmtIn = $pdo->prepare("INSERT INTO pmapc_registros (productor_id, data) VALUES (?, ?)");
    $stmtIn->execute([$martha_id, $jsonData]);
    echo "Successfully INSERTED new PMAPC record for producer {$martha_id}.\n";
}

// Verify payload length
$stmtVer = $pdo->prepare("SELECT CHAR_LENGTH(data) FROM pmapc_registros WHERE productor_id = ?");
$stmtVer->execute([$martha_id]);
$len = $stmtVer->fetchColumn();
echo "Saved payload length in DB: {$len} bytes.\n";
