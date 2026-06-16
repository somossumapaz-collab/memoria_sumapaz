<?php
/**
 * Optimized high-performance migration script to add score columns to caracterizacion_productor
 * and calculate initial scores in bulk using a single database transaction.
 */

require_once 'db_config.php';

header('Content-Type: text/plain');

echo "Starting FAST database migration for scoring columns...\n";

// 1. Add columns to caracterizacion_productor if they do not exist
$cols = [
    'puntaje_social' => 'INT DEFAULT NULL',
    'puntaje_organizacional' => 'INT DEFAULT NULL',
    'puntaje_productivo' => 'INT DEFAULT NULL',
    'puntaje_comercial' => 'INT DEFAULT NULL',
    'puntaje_ambiental' => 'INT DEFAULT NULL',
    'puntaje_impacto' => 'INT DEFAULT NULL',
    'puntaje' => 'INT DEFAULT NULL'
];

foreach ($cols as $colName => $colDef) {
    try {
        $pdo->exec("ALTER TABLE caracterizacion_productor ADD COLUMN `$colName` $colDef");
        echo "Successfully added column `$colName`.\n";
    } catch (\PDOException $e) {
        // Code 42S21 is column already exists in MySQL
        if ($e->getCode() == '42S21' || strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "Column `$colName` already exists, skipping.\n";
        } else {
            echo "Error adding column `$colName`: " . $e->getMessage() . "\n";
        }
    }
}

// 2. Fetch all data in bulk to avoid N+1 queries
echo "Pre-fetching database tables in bulk...\n";

$startFetch = microtime(true);

// 2.1 Get caracterizacion_productor
$stmt = $pdo->query("SELECT * FROM caracterizacion_productor");
$caracterizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2.2 Get discapacidad_productor counts
$stmt = $pdo->query("SELECT productor_id, COUNT(*) FROM discapacidad_productor WHERE tiene_discapacidad = 'Sí' GROUP BY productor_id");
$discapacidades = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 2.3 Get productor_grupo
$stmt = $pdo->query("SELECT productor_id, GROUP_CONCAT(grupo_id) as groups FROM productor_grupo GROUP BY productor_id");
$grupos = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 2.4 Get productores_sumapaz birth dates, panaca and ferias status
$stmt = $pdo->query("SELECT id, fecha_nacimiento, panaca, ferias FROM productores_sumapaz");
$productores_info = $stmt->fetchAll(PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);

// 2.5 Get productor_productos count
$stmt = $pdo->query("SELECT productor_id, COUNT(*) FROM productor_productos GROUP BY productor_id");
$productos_count = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 2.6 Get productor_servicios count
$stmt = $pdo->query("SELECT productor_id, COUNT(*) FROM productor_servicios GROUP BY productor_id");
$servicios_count = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 2.7 Get productor_categoria count
$stmt = $pdo->query("SELECT productor_id, COUNT(*) FROM productor_categoria GROUP BY productor_id");
$categorias_count = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 2.8 Get productor_canal count
$stmt = $pdo->query("SELECT productor_id, COUNT(*) FROM productor_canal GROUP BY productor_id");
$canales_count = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 2.9 Get productor_productos frequent count
$stmt = $pdo->query("SELECT productor_id, COUNT(*) FROM productor_productos WHERE frecuencia IN ('Diarios', 'Semanal', 'Quincenal', 'Mensual') GROUP BY productor_id");
$frecuencia_count = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 2.10 Get productor_certificacion count
$stmt = $pdo->query("SELECT productor_id, COUNT(*) FROM productor_certificacion WHERE certificacion_id != 9 GROUP BY productor_id");
$certificaciones_count = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

echo "Fetched all tables in " . round(microtime(true) - $startFetch, 4) . " seconds.\n";

echo "Processing scores in memory and updating database inside a transaction...\n";

$startProcess = microtime(true);

$isEmpty = function($val) {
    return $val === null || $val === '' || $val === 'Ninguno' || $val === 'Ninguna' || $val === 'Seleccione...';
};

// Find the max months in operation in memory to avoid N+1 queries
$max_months = 1;
foreach ($caracterizaciones as $carac) {
    $tiempo = intval($carac['tiempo_implementacion']);
    if ($tiempo > $max_months) {
        $max_months = $tiempo;
    }
}

try {
    $pdo->beginTransaction();

    $updateStmt = $pdo->prepare("
        UPDATE caracterizacion_productor 
        SET puntaje_social = ?, 
            puntaje_organizacional = ?, 
            puntaje_productivo = ?, 
            puntaje_comercial = ?, 
            puntaje_ambiental = ?, 
            puntaje_impacto = ?, 
            puntaje = ?
        WHERE productor_id = ?
    ");

    $successCount = 0;
    $completeCount = 0;

    foreach ($caracterizaciones as $carac) {
        $pid = intval($carac['productor_id']);
        // Check completeness
        $is_complete = true;
        
        if ($isEmpty($carac['tipo_organizacion'])) $is_complete = false;
        else if ($isEmpty($carac['extension_predio'])) $is_complete = false;
        else if ($isEmpty($carac['tiempo_implementacion'])) $is_complete = false;
        else if ($isEmpty($carac['tipo_tenencia'])) $is_complete = false;
        else if ($isEmpty($carac['numero_personas'])) $is_complete = false;
        else if ($isEmpty($carac['mano_obra'])) $is_complete = false;
        else if ($isEmpty($carac['tipo_proceso'])) $is_complete = false;
        else if ($carac['sistema_diferenciado'] == "1" && $isEmpty($carac['descripcion'])) $is_complete = false;
        else if ($isEmpty($carac['destino'])) $is_complete = false;
        else if ($isEmpty($carac['transporte'])) $is_complete = false;
        else if ($isEmpty($carac['forma_pago'])) $is_complete = false;
        else if ($isEmpty($carac['define_precio'])) $is_complete = false;
        else if ($carac['en_tramite_bool'] === "Sí" && $isEmpty($carac['en_tramite'])) $is_complete = false;
        else {
            $catCount = isset($categorias_count[$pid]) ? $categorias_count[$pid] : 0;
            if ($catCount == 0) {
                $is_complete = false;
            } else {
                $prodCount = isset($productos_count[$pid]) ? $productos_count[$pid] : 0;
                $servCount = isset($servicios_count[$pid]) ? $servicios_count[$pid] : 0;
                if ($prodCount == 0 && $servCount == 0) {
                    $is_complete = false;
                }
            }
        }

        if ($is_complete) {
            $completeCount++;
        }

        // Component 1: Enfoque Diferencial y Social (Max 20 pts)
        $has_discapacidad = isset($discapacidades[$pid]) && $discapacidades[$pid] > 0;
        
        $group_str = isset($grupos[$pid]) ? $grupos[$pid] : '';
        $group_list = array_map('intval', explode(',', $group_str));
        $is_cabeza_hogar = in_array(1, $group_list);
        $is_victima = in_array(3, $group_list);

        $prod_row = isset($productores_info[$pid]) ? $productores_info[$pid] : null;
        $birth = $prod_row ? $prod_row['fecha_nacimiento'] : null;
        $panaca = $prod_row ? intval($prod_row['panaca']) : 0;
        $ferias = $prod_row ? intval($prod_row['ferias']) : 0;
        $is_joven_or_adulto = false;
        if ($birth && $birth !== '1900-01-01') {
            $birthYear = intval(substr($birth, 0, 4));
            if ($birthYear > 0) {
                $age = 2026 - $birthYear;
                if (($age >= 18 && $age <= 28) || $age >= 60) {
                    $is_joven_or_adulto = true;
                }
            }
        }
        if (in_array(7, $group_list) || in_array(6, $group_list)) {
            $is_joven_or_adulto = true;
        }

        $social_score = ($has_discapacidad ? 5 : 0) + ($is_cabeza_hogar ? 5 : 0) + ($is_victima ? 5 : 0) + ($is_joven_or_adulto ? 5 : 0);

        // Component 2: Fortalecimiento Organizacional y Comunitario (Max 15 pts)
        $tipo_org = $carac['tipo_organizacion'];
        $has_org = ($tipo_org && $tipo_org !== 'Ninguna' && $tipo_org !== 'Productor individual');
        $is_panaca = ($panaca == 1);
        $is_ferias = ($ferias == 1);
        $org_score = ($has_org ? 5 : 0) + ($is_panaca ? 5 : 0) + ($is_ferias ? 5 : 0);

        // Component 3: Capacidad Productiva y Maduración (Max 25 pts)
        $tiempo = intval($carac['tiempo_implementacion']);
        $has_tiempo = $tiempo >= 36;

        $tenencia = $carac['tipo_tenencia'];
        $has_tenencia = ($tenencia && $tenencia !== 'Seleccione...');

        $prodCount = isset($productos_count[$pid]) ? $productos_count[$pid] : 0;
        $servCount = isset($servicios_count[$pid]) ? $servicios_count[$pid] : 0;
        $has_produccion = ($prodCount + $servCount > 0);

        $has_valor_agregado = !empty(trim($carac['valor_agregado'])) && $carac['valor_agregado'] !== 'Ninguno';
        $catCount = isset($categorias_count[$pid]) ? $categorias_count[$pid] : 0;
        $has_diversificacion = ($has_valor_agregado || $catCount > 1);

        $mano_obra = $carac['mano_obra'];
        $has_mano_obra = ($mano_obra && $mano_obra !== 'Seleccione...');

        $prod_score = ($has_tiempo ? 5 : 0) + ($has_tenencia ? 5 : 0) + ($has_produccion ? 5 : 0) + ($has_diversificacion ? 5 : 0) + ($has_mano_obra ? 5 : 0);

        // Component 4: Comercialización y Sostenibilidad Económica (Max 15 pts)
        $has_canales = isset($canales_count[$pid]) && $canales_count[$pid] > 0;
        $has_freq = isset($frecuencia_count[$pid]) && $frecuencia_count[$pid] > 0;
        
        $destino = $carac['destino'];
        $has_integracion = ($destino === 'Para autoconsumo y venta de excedentes' || $destino === 'Para venta total o comercialización completa');

        $com_score = ($has_canales ? 5 : 0) + ($has_freq ? 5 : 0) + ($has_integracion ? 5 : 0);

        // Component 5: Sostenibilidad Ambiental y Cumplimiento Normativo (Max 15 pts)
        $usa_abonos = $carac['usa_abonos'];
        $has_agroecologica = ($usa_abonos == '1' || strtolower($usa_abonos) === 'sí' || strtolower($usa_abonos) === 'si');

        $certCount = isset($certificaciones_count[$pid]) ? $certificaciones_count[$pid] : 0;
        $has_en_tramite = $carac['en_tramite_bool'] === 'Sí';
        $has_certificaciones = ($certCount > 0 || $has_en_tramite);

        // Conservation score based on abonos, asociados, diferenciado
        $abonos_val = ($carac['usa_abonos'] == 1);
        $asoc_val = ($carac['sistemas_asociados'] == 1);
        $dif_val = ($carac['sistema_diferenciado'] == 1);
        
        $count_yes = 0;
        if ($abonos_val) $count_yes++;
        if ($asoc_val) $count_yes++;
        if ($dif_val) $count_yes++;
        
        $conservacion_score = 0;
        if ($count_yes === 3) {
            $conservacion_score = 5;
        } elseif ($count_yes === 2) {
            $conservacion_score = 3;
        } elseif ($count_yes === 1) {
            $conservacion_score = 1;
        }

        $amb_score = ($has_agroecologica ? 5 : 0) + ($has_certificaciones ? 5 : 0) + $conservacion_score;

        // Component 6: Impacto Territorial e Innovación (Max 10 pts)
        $tiempo = intval($carac['tiempo_implementacion']);
        $vitrina_score_int = intval(round(($tiempo / $max_months) * 5));
        $sello_score = ($ferias == 1) ? 5 : 0;
        
        $imp_score = $vitrina_score_int + $sello_score;

        $total_score = $social_score + $org_score + $prod_score + $com_score + $amb_score + $imp_score;

        $updateStmt->execute([
            $social_score,
            $org_score,
            $prod_score,
            $com_score,
            $amb_score,
            $imp_score,
            $total_score,
            $pid
        ]);
        
        $successCount++;
    }

    $pdo->commit();
    
    echo "Processed and updated all scores in " . round(microtime(true) - $startProcess, 4) . " seconds.\n";
    echo "Summary:\n";
    echo "==========================================\n";
    echo "Total characterization records: " . count($caracterizaciones) . "\n";
    echo "Successfully updated: $successCount\n";
    echo "Complete (with score value): $completeCount\n";
    echo "Incomplete (with NULL score): " . ($successCount - $completeCount) . "\n";
    echo "Migration completed successfully.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error during migration transaction: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
?>
