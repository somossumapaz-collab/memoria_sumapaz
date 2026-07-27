<?php
require_once __DIR__ . '/../api/db_config.php';

$json_path = 'C:\Users\sotoc\Downloads\gemini-code-1785187075542.json';
$raw = file_get_contents($json_path);
$data = json_decode($raw, true);

echo "=== INSPECTING WILLIAM DIMATÉ RICO JSON ===\n";
echo "Keys count: " . count($data) . "\n";
echo "Persona entrevistada: " . ($data['PMAPC_F01']['persona_entrevistada'] ?? 'N/A') . "\n";
echo "Unidad productiva: " . ($data['PMAPC_F01']['nombre_unidad_productiva'] ?? 'N/A') . "\n";

// Find William Dimaté Rico in productores_sumapaz
$stmt = $pdo->query("SELECT id, nombre_completo, vereda, numero_documento FROM productores_sumapaz WHERE nombre_completo LIKE '%William%' OR nombre_completo LIKE '%Dimate%' OR nombre_completo LIKE '%Dimaté%'");
$producers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\nFound candidate producers:\n";
print_r($producers);

$william_id = null;
foreach ($producers as $p) {
    if (strpos(strtolower($p['nombre_completo']), 'william') !== false && (strpos(strtolower($p['nombre_completo']), 'dimate') !== false || strpos(strtolower($p['nombre_completo']), 'dimaté') !== false)) {
        $william_id = $p['id'];
        break;
    }
}

if (!$william_id && !empty($producers)) {
    foreach ($producers as $p) {
        if (strpos(strtolower($p['nombre_completo']), 'william') !== false) {
            $william_id = $p['id'];
            break;
        }
    }
}

if (!$william_id) {
    die("William Dimaté Rico not found in database!\n");
}

echo "\nTargeting Producer ID: {$william_id}\n";

// Insert / Update pmapc_registros
$stmtCheck = $pdo->prepare("SELECT id FROM pmapc_registros WHERE productor_id = ?");
$stmtCheck->execute([$william_id]);
$existingId = $stmtCheck->fetchColumn();

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

if ($existingId) {
    $stmtUp = $pdo->prepare("UPDATE pmapc_registros SET data = ? WHERE id = ?");
    $stmtUp->execute([$jsonData, $existingId]);
    echo "Successfully UPDATED existing PMAPC record ID {$existingId} for producer {$william_id}.\n";
} else {
    $stmtIn = $pdo->prepare("INSERT INTO pmapc_registros (productor_id, data) VALUES (?, ?)");
    $stmtIn->execute([$william_id, $jsonData]);
    echo "Successfully INSERTED new PMAPC record for producer {$william_id}.\n";
}

// Verify payload length
$stmtVer = $pdo->prepare("SELECT CHAR_LENGTH(data) FROM pmapc_registros WHERE productor_id = ?");
$stmtVer->execute([$william_id]);
$len = $stmtVer->fetchColumn();
echo "Saved payload length in DB: {$len} bytes.\n";
