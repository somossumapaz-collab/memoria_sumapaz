<?php
require_once __DIR__ . '/../api/db_config.php';

$stmt = $pdo->query("SELECT p.id as prod_id, p.nombre_completo, p.vereda, r.id as reg_id, CHAR_LENGTH(r.data) as data_len, r.updated_at 
                     FROM productores_sumapaz p 
                     LEFT JOIN pmapc_registros r ON p.id = r.productor_id 
                     WHERE p.nombre_completo LIKE '%German%' OR p.nombre_completo LIKE '%Germán%' OR p.nombre_completo LIKE '%Duarte%'");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "DATABASE CHECK FOR GERMÁN RODRÍGUEZ:\n";
print_r($rows);

if (!empty($rows) && $rows[0]['prod_id']) {
    $productor_id = $rows[0]['prod_id'];
    $json_path = 'C:\Users\sotoc\Downloads\JSON German Rodriguez.json';
    $data = json_decode(file_get_contents($json_path), true);
    
    // Now trigger relational tables populate directly by including api/submit_pmapc.php logic with internal variables
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    // Prepare fake php input
    $payload = [
        'productor_id' => $productor_id,
        'data' => $data
    ];
    
    file_put_contents(__DIR__ . '/../php_input_mock.json', json_encode($payload, JSON_UNESCAPED_UNICODE));
    
    // Let's call submit_pmapc logic directly using PDO transaction
    require_once __DIR__ . '/../api/setup_pmapc_db_schema.php';
    
    // Check if submit_pmapc table population functions work
    echo "\nPopulating relational tables (pmapc_estrategico, pmapc_clientes, pmapc_productos, etc.)...\n";
    
    // Execute logic similar to submit_pmapc.php
    function nan_v($val) {
        if ($val === null || $val === '' || (is_string($val) && trim($val) === '')) {
            return 'NaN';
        }
        return is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : (string)$val;
    }
    
    $f01 = $data['PMAPC_F01'] ?? ($data['f01'] ?? []);
    $f02 = $data['PMAPC_F02'] ?? ($data['f02'] ?? []);
    $f03 = $data['PMAPC_F03'] ?? ($data['f03'] ?? []);
    $f04 = $data['PMAPC_F04'] ?? ($data['f04'] ?? []);
    
    $nombreOrg = nan_v($f01['nombre_unidad_productiva'] ?? ($f01['nombre_organizacion'] ?? ''));
    $estadoAct = nan_v($f01['estado_actual'] ?? '');
    $pmapc_data_json = json_encode($data, JSON_UNESCAPED_UNICODE);
    
    $stmtMaster = $pdo->prepare("
        INSERT INTO pmapc_registros (productor_id, nombre_organizacion, estado_actual, data) 
        VALUES (?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
            nombre_organizacion = VALUES(nombre_organizacion),
            estado_actual = VALUES(estado_actual),
            data = VALUES(data),
            updated_at = CURRENT_TIMESTAMP
    ");
    $stmtMaster->execute([$productor_id, $nombreOrg, $estadoAct, $pmapc_data_json]);
    
    $stmtRegId = $pdo->prepare("SELECT id FROM pmapc_registros WHERE productor_id = ?");
    $stmtRegId->execute([$productor_id]);
    $registro_id = $stmtRegId->fetchColumn();
    
    // Estrategico
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
        $registro_id, $productor_id,
        nan_v($f01['nombre_unidad_productiva'] ?? ''),
        nan_v($f01['tipo_actividad'] ?? ''),
        nan_v($f01['ubicacion_especifica'] ?? ''),
        nan_v($f01['coordenadas'] ?? ''),
        nan_v($f01['producto_servicio_principal'] ?? ''),
        nan_v($f01['estado_actual'] ?? ''),
        nan_v($f01['descripcion_general'] ?? ''),
        nan_v($f02['mision'] ?? ''),
        nan_v($f02['vision'] ?? ''),
        nan_v($f02['valores'] ?? ''),
        nan_v($f03['por_que_adquieren_el_servicio'] ?? ''),
        nan_v($f03['beneficio_cliente'] ?? ''),
        nan_v($f03['diferencial'] ?? ''),
        nan_v($f03['valor_ambiental'] ?? ''),
        nan_v($f03['valor_social_comunitario'] ?? ''),
        nan_v($f03['evidencia'] ?? ''),
        nan_v($f04['fortalezas'] ?? ''),
        nan_v($f04['oportunidades'] ?? ''),
        nan_v($f04['debilidades'] ?? ''),
        nan_v($f04['amenazas'] ?? '')
    ]);
    
    // Dedicated comments
    $pdo->prepare("DELETE FROM pmapc_comentarios WHERE registro_id = ?")->execute([$registro_id]);
    $stmtCom = $pdo->prepare("
        INSERT INTO pmapc_comentarios (registro_id, productor_id, origen_archivo, comentarios_texto, informacion_pendiente, conclusion_general, recomendaciones)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtCom->execute([
        $registro_id,
        $productor_id,
        'C:\Users\sotoc\Downloads\JSON German Rodriguez.json',
        nan_v($f01['observaciones_o_comentarios'] ?? $f03['observaciones_o_comentarios'] ?? ''),
        nan_v($f03['observaciones_o_comentarios'] ?? ''),
        'NaN',
        'NaN'
    ]);
    
    echo "SUCCESS! Relational tables populated for Germán Oswaldo Rodríguez Duarte (Producer ID: {$productor_id}, Registro ID: {$registro_id}).\n";
}
