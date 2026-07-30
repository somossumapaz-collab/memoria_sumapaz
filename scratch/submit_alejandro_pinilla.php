<?php
require_once __DIR__ . '/../api/db_config.php';

$json_path = 'C:\Users\sotoc\Downloads\JSON Alejandro Pinilla.json';
if (!file_exists($json_path)) {
    $files = glob('C:\Users\sotoc\Downloads\*Alejandro*.json');
    if (!empty($files)) {
        $json_path = $files[0];
    }
}

echo "Using JSON path: {$json_path}\n";
$raw = file_get_contents($json_path);

// Test decoding
$data = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON Decode Error: " . json_last_error_msg() . "\n";
    // Attempt basic fix: replace mismatched brackets if any
    $raw_fixed = preg_replace('/\}\,\s*"\d+":\s*\{/', '},{', $raw);
    $raw_fixed = str_replace(['  ],'."\n".'  "f15b":', '  ],'."\n".'  "f15c":', '  ],'."\n".'  "f16":'], ['  },'."\n".'  "f15b":', '  },'."\n".'  "f15c":', '  },'."\n".'  "f16":'], $raw_fixed);
    $data = json_decode($raw_fixed, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        die("Fatal JSON syntax error in Alejandro Pinilla JSON: " . json_last_error_msg() . "\n");
    } else {
        echo "Fixed JSON syntax successfully!\n";
    }
}

echo "=== INSPECTING ALEJANDRO PINILLA JSON ===\n";
echo "Keys count: " . count($data) . "\n";
echo "Persona entrevistada: " . ($data['PMAPC_F01']['persona_entrevistada'] ?? $data['f01']['persona_entrevistada'] ?? 'N/A') . "\n";
echo "Unidad productiva: " . ($data['PMAPC_F01']['nombre_unidad_productiva'] ?? $data['f01']['nombre_unidad_productiva'] ?? 'N/A') . "\n";

// Find Alejandro Pinilla in productores_sumapaz
$stmt = $pdo->query("SELECT id, nombre_completo, vereda, numero_documento FROM productores_sumapaz WHERE nombre_completo LIKE '%Alejandro%' OR nombre_completo LIKE '%Pinilla%'");
$producers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\nFound candidate producers:\n";
print_r($producers);

$alejandro_id = null;
foreach ($producers as $p) {
    if (strpos(strtolower($p['nombre_completo']), 'alejandro') !== false && strpos(strtolower($p['nombre_completo']), 'pinilla') !== false) {
        $alejandro_id = $p['id'];
        break;
    }
}

if (!$alejandro_id && !empty($producers)) {
    foreach ($producers as $p) {
        if (strpos(strtolower($p['nombre_completo']), 'pinilla') !== false) {
            $alejandro_id = $p['id'];
            break;
        }
    }
}

if (!$alejandro_id) {
    die("Alejandro Pinilla not found in database!\n");
}

echo "\nTargeting Producer ID: {$alejandro_id}\n";

// Insert / Update pmapc_registros
$stmtCheck = $pdo->prepare("SELECT id FROM pmapc_registros WHERE productor_id = ?");
$stmtCheck->execute([$alejandro_id]);
$existingId = $stmtCheck->fetchColumn();

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

if ($existingId) {
    $stmtUp = $pdo->prepare("UPDATE pmapc_registros SET data = ? WHERE id = ?");
    $stmtUp->execute([$jsonData, $existingId]);
    echo "Successfully UPDATED existing PMAPC record ID {$existingId} for producer {$alejandro_id}.\n";
} else {
    $stmtIn = $pdo->prepare("INSERT INTO pmapc_registros (productor_id, data) VALUES (?, ?)");
    $stmtIn->execute([$alejandro_id, $jsonData]);
    echo "Successfully INSERTED new PMAPC record for producer {$alejandro_id}.\n";
}

// Verify payload length
$stmtVer = $pdo->prepare("SELECT CHAR_LENGTH(data) FROM pmapc_registros WHERE productor_id = ?");
$stmtVer->execute([$alejandro_id]);
$len = $stmtVer->fetchColumn();
echo "Saved payload length in DB: {$len} bytes.\n";
