<?php
/**
 * Script to check which producers had beneficiario_2026 = 1 but are no longer in the top 151.
 */
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../api/db_config.php';

try {
    // Fetch all characterized producers and calculate scores in memory (exactly like the main API)
    $stmt = $pdo->query("SELECT id, nombre_completo, vereda, fecha_nacimiento, panaca, ferias, beneficiario_2026 FROM productores_sumapaz");
    $productores_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $productores = [];
    foreach ($productores_raw as $p) {
        $productores[intval($p['id'])] = $p;
    }

    $stmt = $pdo->query("SELECT * FROM caracterizacion_productor");
    $caracterizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT productor_id, COUNT(*) FROM discapacidad_productor WHERE tiene_discapacidad = 'Sí' GROUP BY productor_id");
    $discapacidades = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $stmt = $pdo->query("SELECT productor_id, GROUP_CONCAT(grupo_id) as groups FROM productor_grupo GROUP BY productor_id");
    $grupos = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $stmt = $pdo->query("SELECT productor_id, nombre, frecuencia FROM productor_productos");
    $productos_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $productos = [];
    foreach ($productos_raw as $pr) {
        $pid = intval($pr['productor_id']);
        if (!isset($productos[$pid])) $productos[$pid] = [];
        $productos[$pid][] = $pr;
    }

    $stmt = $pdo->query("SELECT productor_id, nombre_actividad FROM productor_servicios");
    $servicios_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $servicios = [];
    foreach ($servicios_raw as $sr) {
        $pid = intval($sr['productor_id']);
        if (!isset($servicios[$pid])) $servicios[$pid] = [];
        $servicios[$pid][] = $sr;
    }

    $stmt = $pdo->query("SELECT productor_id, categoria_id FROM productor_categoria");
    $categorias_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $categorias = [];
    foreach ($categorias_raw as $cr) {
        $pid = intval($cr['productor_id']);
        if (!isset($categorias[$pid])) $categorias[$pid] = [];
        $categorias[$pid][] = intval($cr['categoria_id']);
    }

    $stmt = $pdo->query("SELECT productor_id, canal_id FROM productor_canal");
    $canales_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $canales = [];
    foreach ($canales_raw as $cn) {
        $pid = intval($cn['productor_id']);
        if (!isset($canales[$pid])) $canales[$pid] = [];
        $canales[$pid][] = intval($cn['canal_id']);
    }

    $stmt = $pdo->query("SELECT productor_id, certificacion_id FROM productor_certificacion");
    $certificaciones_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $certificaciones = [];
    foreach ($certificaciones_raw as $ct) {
        $pid = intval($ct['productor_id']);
        if (!isset($certificaciones[$pid])) $certificaciones[$pid] = [];
        $certificaciones[$pid][] = intval($ct['certificacion_id']);
    }

    $max_months = 1;
    foreach ($caracterizaciones as $c) {
        $tiempo = intval($c['tiempo_implementacion']);
        if ($tiempo > $max_months) {
            $max_months = $tiempo;
        }
    }

    $results = [];
    foreach ($caracterizaciones as $carac) {
        $pid = intval($carac['productor_id']);
        if (!isset($productores[$pid])) continue;
        
        $p = $productores[$pid];

        // Component 1: Social
        $has_discapacidad = isset($discapacidades[$pid]) && $discapacidades[$pid] > 0;
        $social_score = $has_discapacidad ? 5 : 0;

        $group_str = isset($grupos[$pid]) ? $grupos[$pid] : '';
        $group_list = array_map('intval', explode(',', $group_str));
        $is_cabeza_hogar = in_array(1, $group_list);
        $social_score += $is_cabeza_hogar ? 5 : 0;
        
        // Victim of conflict: ALWAYS 5 pts
        $social_score += 5; 

        $birth = $p['fecha_nacimiento'];
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
        $social_score += $is_joven_or_adulto ? 5 : 0;

        // Component 2: Organizacional
        $tipo_org = $carac['tipo_organizacion'];
        $has_org = ($tipo_org && $tipo_org !== 'Ninguna' && $tipo_org !== 'Productor individual');
        $org_score = $has_org ? 5 : 0;
        $is_ferias = (intval($p['ferias']) == 1);
        $org_score += $is_ferias ? 5 : 0;
        $is_panaca = (intval($p['panaca']) == 1);
        $org_score += $is_panaca ? 5 : 0;

        // Component 3: Productivo
        $tiempo = intval($carac['tiempo_implementacion']);
        $has_tiempo = $tiempo >= 36;
        $prod_score = $has_tiempo ? 5 : 0;
        $tenencia = $carac['tipo_tenencia'];
        $has_tenencia = ($tenencia && $tenencia !== 'Seleccione...');
        $prod_score += $has_tenencia ? 5 : 0;
        $prodCount = isset($productos[$pid]) ? count($productos[$pid]) : 0;
        $servCount = isset($servicios[$pid]) ? count($servicios[$pid]) : 0;
        $has_produccion = ($prodCount + $servCount > 0);
        $prod_score += $has_produccion ? 5 : 0;
        $catCount = isset($categorias[$pid]) ? count($categorias[$pid]) : 0;
        $val_agregado = $carac['valor_agregado'];
        $has_valor_agregado = !empty(trim($val_agregado ?? '')) && $val_agregado !== 'Ninguno';
        $has_diversificacion = ($has_valor_agregado || $catCount > 1);
        $prod_score += $has_diversificacion ? 5 : 0;
        $mano_obra = $carac['mano_obra'];
        $has_mano_obra = ($mano_obra && $mano_obra !== 'Seleccione...');
        $prod_score += $has_mano_obra ? 5 : 0;

        // Component 4: Comercial
        $canalCount = isset($canales[$pid]) ? count($canales[$pid]) : 0;
        $has_canales = $canalCount > 0;
        $com_score = $has_canales ? 5 : 0;
        $freq_count = 0;
        if (isset($productos[$pid])) {
            foreach ($productos[$pid] as $pr) {
                if (in_array($pr['frecuencia'], ['Diarios', 'Semanal', 'Quincenal', 'Mensual'])) {
                    $freq_count++;
                }
            }
        }
        $has_freq = $freq_count > 0;
        $com_score += $has_freq ? 5 : 0;
        $destino = $carac['destino'];
        $has_integracion = ($destino === 'Para autoconsumo y venta de excedentes' || $destino === 'Para venta total o comercialización completa');
        $com_score += $has_integracion ? 5 : 0;

        // Component 5: Ambiental
        $usa_abonos = $carac['usa_abonos'];
        $has_agroecologica = ($usa_abonos == '1' || strtolower($usa_abonos ?? '') === 'sí' || strtolower($usa_abonos ?? '') === 'si');
        $amb_score = $has_agroecologica ? 5 : 0;
        $abonos_val = ($carac['usa_abonos'] == 1);
        $asoc_val = ($carac['sistemas_asociados'] == 1);
        $dif_val = ($carac['sistema_diferenciado'] == 1);
        $count_yes = 0;
        if ($abonos_val) $count_yes++;
        if ($asoc_val) $count_yes++;
        if ($dif_val) $count_yes++;
        $conservacion_score = 0;
        if ($count_yes === 3) $conservacion_score = 5;
        elseif ($count_yes === 2) $conservacion_score = 3;
        elseif ($count_yes === 1) $conservacion_score = 1;
        $amb_score += $conservacion_score;
        $cert_count = 0;
        if (isset($certificaciones[$pid])) {
            foreach ($certificaciones[$pid] as $c) {
                if ($c != 9) $cert_count++;
            }
        }
        $has_en_tramite = $carac['en_tramite_bool'] === 'Sí';
        $has_certificaciones = ($cert_count > 0 || $has_en_tramite);
        $amb_score += $has_certificaciones ? 5 : 0;

        // Component 6: Impacto
        $vitrina_score = ($tiempo / $max_months) * 5;
        $vitrina_score_int = intval(round($vitrina_score));
        $imp_score = $vitrina_score_int;
        $imp_score += $is_ferias ? 5 : 0;

        $total_score = $social_score + $org_score + $prod_score + $com_score + $amb_score + $imp_score;

        $results[] = [
            'id' => $pid,
            'nombre_completo' => $p['nombre_completo'],
            'vereda' => $p['vereda'],
            'puntaje' => $total_score,
            'beneficiario_2026' => intval($p['beneficiario_2026'])
        ];
    }

    // Sort by score descending
    usort($results, function($a, $b) {
        return $b['puntaje'] - $a['puntaje'];
    });

    // Extract top 151 IDs
    $top_151_ids = [];
    foreach (array_slice($results, 0, 151) as $r) {
        $top_151_ids[] = intval($r['id']);
    }

    echo "CHECK RESULTS:\n";
    $dropped = [];
    
    // Any characterized producer who has beneficiario_2026 = 1 but is NOT in the top 151
    foreach ($results as $item) {
        $pid = intval($item['id']);
        if ($item['beneficiario_2026'] == 1 && !in_array($pid, $top_151_ids)) {
            $dropped[] = $item;
        }
    }

    echo "Found " . count($dropped) . " producers who are beneficiaries (1) in DB but fell out of the top 151:\n";
    foreach ($dropped as $idx => $d) {
        $num = $idx + 1;
        echo "$num. ID: {$d['id']} | Name: {$d['nombre_completo']} | Vereda: {$d['vereda']} | Score: {$d['puntaje']}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
