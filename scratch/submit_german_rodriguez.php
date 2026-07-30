<?php
require_once __DIR__ . '/../api/db_config.php';

$json_path = 'C:\Users\sotoc\Downloads\JSON German Rodriguez.json';
if (!file_exists($json_path)) {
    die("Error: File not found at {$json_path}\n");
}

echo "Using JSON path: {$json_path}\n";
$raw = file_get_contents($json_path);
$data = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die("Fatal JSON syntax error: " . json_last_error_msg() . "\n");
}

echo "=== INSPECTING GERMÁN OSWALDO RODRÍGUEZ DUARTE JSON ===\n";
echo "Keys count: " . count($data) . "\n";
echo "Persona entrevistada: " . ($data['PMAPC_F01']['persona_entrevistada'] ?? $data['f01']['persona_entrevistada'] ?? 'N/A') . "\n";
echo "Unidad productiva: " . ($data['PMAPC_F01']['nombre_unidad_productiva'] ?? $data['f01']['nombre_unidad_productiva'] ?? 'N/A') . "\n";

// Search producer in database
$stmt = $pdo->query("SELECT id, nombre_completo, vereda, numero_documento FROM productores_sumapaz WHERE nombre_completo LIKE '%German%' OR nombre_completo LIKE '%Germán%' OR nombre_completo LIKE '%Rodriguez%' OR nombre_completo LIKE '%Rodríguez%' OR nombre_completo LIKE '%Duarte%'");
$producers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\nFound candidate producers in DB:\n";
print_r($producers);

$german_id = null;
foreach ($producers as $p) {
    $name = strtolower($p['nombre_completo']);
    if (strpos($name, 'german') !== false || strpos($name, 'germán') !== false) {
        $german_id = $p['id'];
        break;
    }
}

if (!$german_id && !empty($producers)) {
    foreach ($producers as $p) {
        if (strpos(strtolower($p['nombre_completo']), 'duarte') !== false) {
            $german_id = $p['id'];
            break;
        }
    }
}

echo "Targeting Producer ID: " . ($german_id ? $german_id : "NOT FOUND") . "\n";

if (!$german_id) {
    echo "Creating producer record for Germán Oswaldo Rodríguez Duarte...\n";
    $stmtInsProd = $pdo->prepare("INSERT INTO productores_sumapaz (nombre_completo, vereda, tipo_productor) VALUES (?, ?, ?)");
    $stmtInsProd->execute(['Germán Oswaldo Rodríguez Duarte', 'San Juan', 'Individual']);
    $german_id = $pdo->lastInsertId();
    echo "Created producer with ID: {$german_id}\n";
}

// Perform submit via api submit_pmapc.php internal logic
$_SERVER['REQUEST_METHOD'] = 'POST';
$payload = [
    'productor_id' => $german_id,
    'data' => $data
];

// Save directly via submit_pmapc logic or database insert
$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

// Call submit_pmapc endpoint directly in script
$stmtMaster = $pdo->prepare("
    INSERT INTO pmapc_registros (productor_id, nombre_organizacion, estado_actual, data) 
    VALUES (?, ?, ?, ?) 
    ON DUPLICATE KEY UPDATE 
        nombre_organizacion = VALUES(nombre_organizacion),
        estado_actual = VALUES(estado_actual),
        data = VALUES(data),
        updated_at = CURRENT_TIMESTAMP
");

$f01 = $data['PMAPC_F01'] ?? ($data['f01'] ?? []);
$nombreOrg = $f01['nombre_unidad_productiva'] ?? ($f01['nombre_organizacion'] ?? 'Gallinas de la Montaña');
$estadoAct = $f01['estado_actual'] ?? '';

$stmtMaster->execute([$german_id, $nombreOrg, $estadoAct, $jsonData]);

// Fetch registro_id
$stmtRegId = $pdo->prepare("SELECT id FROM pmapc_registros WHERE productor_id = ?");
$stmtRegId->execute([$german_id]);
$registro_id = $stmtRegId->fetchColumn();

echo "Saved to pmapc_registros table successfully (registro_id = {$registro_id}, productor_id = {$german_id})!\n";

// Also run API submit if needed to populate relational tables
$ch = curl_init('http://localhost:8000/api/submit_pmapc.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$res = curl_exec($ch);
curl_close($ch);

echo "API submit result: " . ($res ? $res : "CURL not connected / direct DB insert executed.") . "\n";
