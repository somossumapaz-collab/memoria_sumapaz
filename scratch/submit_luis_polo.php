<?php
require_once __DIR__ . '/../api/db_config.php';

$json_path = 'C:\Users\sotoc\Downloads\JSON Luis Felipe Polo Morales.json';
if (!file_exists($json_path)) {
    die("Error: File not found at {$json_path}\n");
}

echo "Using JSON path: {$json_path}\n";
$raw = file_get_contents($json_path);
$raw = preg_replace('/\[cite:\s*\d+\]/', '', $raw);
$data = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die("Fatal JSON syntax error: " . json_last_error_msg() . "\n");
}

echo "=== INSPECTING LUIS FELIPE POLO MORALES JSON ===\n";
echo "Keys count: " . count($data) . "\n";
echo "Persona entrevistada: " . ($data['PMAPC_F01']['persona_entrevistada'] ?? $data['f01']['persona_entrevistada'] ?? 'N/A') . "\n";
echo "Unidad productiva: " . ($data['PMAPC_F01']['nombre_unidad_productiva'] ?? $data['f01']['nombre_unidad_productiva'] ?? 'N/A') . "\n";

// Search producer in database
$stmt = $pdo->query("SELECT id, nombre_completo, vereda, numero_documento FROM productores_sumapaz WHERE nombre_completo LIKE '%Luis%' OR nombre_completo LIKE '%Felipe%' OR nombre_completo LIKE '%Polo%' OR nombre_completo LIKE '%Morales%'");
$producers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\nFound candidate producers in DB:\n";
print_r($producers);

$luis_id = null;
foreach ($producers as $p) {
    $name = strtolower($p['nombre_completo']);
    if (strpos($name, 'luis') !== false && strpos($name, 'polo') !== false) {
        $luis_id = $p['id'];
        break;
    }
}

if (!$luis_id && !empty($producers)) {
    foreach ($producers as $p) {
        if (strpos(strtolower($p['nombre_completo']), 'polo') !== false) {
            $luis_id = $p['id'];
            break;
        }
    }
}

echo "Targeting Producer ID: " . ($luis_id ? $luis_id : "NOT FOUND") . "\n";

if (!$luis_id) {
    echo "Creating producer record for Luis Felipe Polo Morales...\n";
    $stmtInsProd = $pdo->prepare("INSERT INTO productores_sumapaz (nombre_completo, vereda, tipo_productor) VALUES (?, ?, ?)");
    $stmtInsProd->execute(['Luis Felipe Polo Morales', 'Tunal Alto', 'Individual']);
    $luis_id = $pdo->lastInsertId();
    echo "Created producer with ID: {$luis_id}\n";
}

// Master table insert/update
$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

$f01 = $data['PMAPC_F01'] ?? ($data['f01'] ?? []);
$nombreOrg = $f01['nombre_unidad_productiva'] ?? ($f01['nombre_organizacion'] ?? 'Unidad ovinos y huevo campesino Luis Polo Morales');
$estadoAct = $f01['estado_actual'] ?? '';

$stmtMaster = $pdo->prepare("
    INSERT INTO pmapc_registros (productor_id, nombre_organizacion, estado_actual, data) 
    VALUES (?, ?, ?, ?) 
    ON DUPLICATE KEY UPDATE 
        nombre_organizacion = VALUES(nombre_organizacion),
        estado_actual = VALUES(estado_actual),
        data = VALUES(data),
        updated_at = CURRENT_TIMESTAMP
");
$stmtMaster->execute([$luis_id, $nombreOrg, $estadoAct, $jsonData]);

// Fetch registro_id
$stmtRegId = $pdo->prepare("SELECT id FROM pmapc_registros WHERE productor_id = ?");
$stmtRegId->execute([$luis_id]);
$registro_id = $stmtRegId->fetchColumn();

echo "Saved to pmapc_registros table successfully (registro_id = {$registro_id}, productor_id = {$luis_id})!\n";

// Populate relational tables via API submit script logic
function nan_val_lp($val) {
    if ($val === null || $val === '' || (is_string($val) && trim($val) === '')) {
        return 'NaN';
    }
    return is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : (string)$val;
}

$f02 = $data['PMAPC_F02'] ?? ($data['f02'] ?? []);
$f03 = $data['PMAPC_F03'] ?? ($data['f03'] ?? []);
$f04 = $data['PMAPC_F04'] ?? ($data['f04'] ?? []);

$pdo->prepare("DELETE FROM pmapc_estrategico WHERE registro_id = ?")->execute([$registro_id]);
$stmtEst = $pdo->prepare("
    INSERT INTO pmapc_estrategico (
        registro_id, productor_id,
        f01_nombre_organizacion, f01_tipo_actividad, f01_ubicacion, f01_coordenadas, f01_producto_principal, f01_estado_actual, f01_descripcion_general,
        f02_mision, f02_vision, f02_valores,
        f03_problema, f03_solucion, f03_diferencial, f03_valor_ambiental, f03_valor_social, f03_demostracion,
        f04_fortalezas, f04_oportunidades, f04_debilidades, f04_amenazas
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmtEst->execute([
    $registro_id, $luis_id,
    nan_val_lp($f01['nombre_unidad_productiva'] ?? ''),
    nan_val_lp($f01['tipo_actividad'] ?? ''),
    nan_val_lp($f01['ubicacion_especifica'] ?? ''),
    nan_val_lp($f01['coordenadas'] ?? ''),
    nan_val_lp($f01['producto_servicio_principal'] ?? ''),
    nan_val_lp($f01['estado_actual'] ?? ''),
    nan_val_lp($f01['descripcion_general'] ?? ''),
    nan_val_lp($f02['mision'] ?? ''),
    nan_val_lp($f02['vision'] ?? ''),
    nan_val_lp($f02['valores'] ?? ''),
    nan_val_lp($f03['por_que_adquieren_el_servicio'] ?? ''),
    nan_val_lp($f03['beneficio_cliente'] ?? ''),
    nan_val_lp($f03['diferencial'] ?? ''),
    nan_val_lp($f03['valor_ambiental'] ?? ''),
    nan_val_lp($f03['valor_social_comunitario'] ?? ''),
    nan_val_lp($f03['evidencia'] ?? ''),
    nan_val_lp($f04['fortalezas'] ?? ''),
    nan_val_lp($f04['oportunidades'] ?? ''),
    nan_val_lp($f04['debilidades'] ?? ''),
    nan_val_lp($f04['amenazas'] ?? '')
]);

// Dedicated Comments
$pdo->prepare("DELETE FROM pmapc_comentarios WHERE registro_id = ?")->execute([$registro_id]);
$stmtCom = $pdo->prepare("
    INSERT INTO pmapc_comentarios (registro_id, productor_id, origen_archivo, comentarios_texto, informacion_pendiente, conclusion_general, recomendaciones)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmtCom->execute([
    $registro_id,
    $luis_id,
    'C:\Users\sotoc\Downloads\JSON Luis Felipe Polo Morales.json',
    nan_val_lp($f01['observaciones_o_comentarios'] ?? $f03['observaciones_o_comentarios'] ?? ''),
    nan_val_lp($f03['observaciones_o_comentarios'] ?? ''),
    'NaN',
    'NaN'
]);

echo "SUCCESS! Relational tables populated for Luis Felipe Polo Morales (Producer ID: {$luis_id}, Registro ID: {$registro_id}).\n";
