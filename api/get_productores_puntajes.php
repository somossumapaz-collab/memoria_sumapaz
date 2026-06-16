<?php
/**
 * API to fetch all producers with their calculated scores and justifications in bulk.
 * Optimized to run in < 0.5s by using bulk pre-fetching instead of N+1 database queries.
 */
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

// Ensure authenticated
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

try {
    // 1. Fetch all database tables in bulk
    $stmt = $pdo->query("SELECT id, nombre_completo, vereda, fecha_nacimiento, panaca, ferias FROM productores_sumapaz");
    $productores_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $productores = [];
    foreach ($productores_raw as $p) {
        $productores[intval($p['id'])] = $p;
    }

    $stmt = $pdo->query("SELECT * FROM caracterizacion_productor");
    $caracterizaciones_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $caracterizaciones = [];
    foreach ($caracterizaciones_raw as $c) {
        $caracterizaciones[intval($c['productor_id'])] = $c;
    }

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

    // Find the max months in operation in memory
    $max_months = 1;
    foreach ($caracterizaciones as $c) {
        $tiempo = intval($c['tiempo_implementacion']);
        if ($tiempo > $max_months) {
            $max_months = $tiempo;
        }
    }

    // Helper for empty checks
    $isEmpty = function($val) {
        return $val === null || $val === '' || $val === 'Ninguno' || $val === 'Ninguna' || $val === 'Seleccione...';
    };

    // Calculate scores and justifications in memory
    $results = [];
    foreach ($caracterizaciones as $pid => $carac) {
        if (!isset($productores[$pid])) continue;
        
        $p = $productores[$pid];

        // Validate completeness
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
            $catCount = isset($categorias[$pid]) ? count($categorias[$pid]) : 0;
            if ($catCount == 0) {
                $is_complete = false;
            } else {
                $prodCount = isset($productos[$pid]) ? count($productos[$pid]) : 0;
                $servCount = isset($servicios[$pid]) ? count($servicios[$pid]) : 0;
                if ($prodCount == 0 && $servCount == 0) {
                    $is_complete = false;
                }
            }
        }



        $breakdown = [];

        // Component 1: Social (Max 20 pts)
        $has_discapacidad = isset($discapacidades[$pid]) && $discapacidades[$pid] > 0;
        $social_score = $has_discapacidad ? 5 : 0;
        $breakdown['c1_discapacidad'] = [
            'name' => 'El postulante es persona con discapacidad certificada o verificable',
            'max' => 5,
            'score' => $has_discapacidad ? 5 : 0,
            'status' => $has_discapacidad ? 'Cumple' : 'No cumple',
            'detail' => $has_discapacidad ? 'Discapacidad registrada' : 'No registra discapacidad'
        ];

        $group_str = isset($grupos[$pid]) ? $grupos[$pid] : '';
        $group_list = array_map('intval', explode(',', $group_str));
        $is_cabeza_hogar = in_array(1, $group_list);
        $social_score += $is_cabeza_hogar ? 5 : 0;
        $breakdown['c1_mujer_cabeza'] = [
            'name' => 'El postulante es mujer cabeza de hogar',
            'max' => 5,
            'score' => $is_cabeza_hogar ? 5 : 0,
            'status' => $is_cabeza_hogar ? 'Cumple' : 'No cumple',
            'detail' => $is_cabeza_hogar ? 'Mujer cabeza de hogar registrada' : 'No registra mujer cabeza de hogar'
        ];

        $is_victima = in_array(3, $group_list);
        $social_score += $is_victima ? 5 : 0;
        $breakdown['c1_victima'] = [
            'name' => 'El postulante es víctima reconocida del conflicto armado',
            'max' => 5,
            'score' => $is_victima ? 5 : 0,
            'status' => $is_victima ? 'Cumple' : 'No cumple',
            'detail' => $is_victima ? 'Víctima del conflicto registrada' : 'No registra víctima del conflicto'
        ];

        $birth = $p['fecha_nacimiento'];
        $is_joven_or_adulto = false;
        $age = null;
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
        $breakdown['c1_joven_adulto'] = [
            'name' => 'El postulante es joven rural o adulto mayor',
            'max' => 5,
            'score' => $is_joven_or_adulto ? 5 : 0,
            'status' => $is_joven_or_adulto ? 'Cumple' : 'No cumple',
            'detail' => $age ? "Edad calculada: $age años" : 'No registra rango de edad aplicable'
        ];

        // Component 2: Organizacional (Max 15 pts)
        $tipo_org = $carac['tipo_organizacion'];
        $has_org = ($tipo_org && $tipo_org !== 'Ninguna' && $tipo_org !== 'Productor individual');
        $org_score = $has_org ? 5 : 0;
        $breakdown['c2_organizacion'] = [
            'name' => 'Participación activa demostrable en organizaciones productivas locales',
            'max' => 5,
            'score' => $has_org ? 5 : 0,
            'status' => $has_org ? 'Cumple' : 'No cumple',
            'detail' => $has_org ? "Organización: $tipo_org" : 'No registra organización'
        ];

        $is_ferias = (intval($p['ferias']) == 1);
        $org_score += $is_ferias ? 5 : 0;
        $breakdown['c2_comunitaria'] = [
            'name' => 'Participación en otros procesos de articulación comunitaria',
            'max' => 5,
            'score' => $is_ferias ? 5 : 0,
            'status' => $is_ferias ? 'Cumple' : 'Información no disponible',
            'detail' => $is_ferias ? 'Participación registrada en ferias y eventos locales/regionales' : 'No registra participación en ferias'
        ];

        $is_panaca = (intval($p['panaca']) == 1);
        $org_score += $is_panaca ? 5 : 0;
        $breakdown['c2_gestor'] = [
            'name' => 'Potencial e idoneidad como gestor de conocimiento local',
            'max' => 5,
            'score' => $is_panaca ? 5 : 0,
            'status' => $is_panaca ? 'Cumple' : 'No cumple',
            'detail' => $is_panaca ? 'Postulante certificado por PANACA' : 'No cuenta con certificación PANACA'
        ];

        // Component 3: Productivo (Max 25 pts)
        $tiempo = intval($carac['tiempo_implementacion']);
        $has_tiempo = $tiempo >= 36;
        $prod_score = $has_tiempo ? 5 : 0;
        $breakdown['c3_tiempo'] = [
            'name' => 'Tiempo de implementación continua de la actividad superior a tres (3) años',
            'max' => 5,
            'score' => $has_tiempo ? 5 : 0,
            'status' => $has_tiempo ? 'Cumple' : 'No cumple',
            'detail' => "$tiempo meses de implementación"
        ];

        $tenencia = $carac['tipo_tenencia'];
        $has_tenencia = ($tenencia && $tenencia !== 'Seleccione...');
        $prod_score += $has_tenencia ? 5 : 0;
        $breakdown['c3_tenencia'] = [
            'name' => 'Tenencia formal, estable o legítima explotación del predio',
            'max' => 5,
            'score' => $has_tenencia ? 5 : 0,
            'status' => $has_tenencia ? 'Cumple' : 'No cumple',
            'detail' => $has_tenencia ? "Tenencia: $tenencia" : 'No registra tipo de tenencia'
        ];

        $prodCount = isset($productos[$pid]) ? count($productos[$pid]) : 0;
        $servCount = isset($servicios[$pid]) ? count($servicios[$pid]) : 0;
        $has_produccion = ($prodCount + $servCount > 0);
        $prod_score += $has_produccion ? 5 : 0;
        $breakdown['c3_produccion'] = [
            'name' => 'Producción verificable, regular y permanente',
            'max' => 5,
            'score' => $has_produccion ? 5 : 0,
            'status' => $has_produccion ? 'Cumple' : 'No cumple',
            'detail' => "$prodCount productos y $servCount servicios ofertados"
        ];

        $catCount = isset($categorias[$pid]) ? count($categorias[$pid]) : 0;
        $val_agregado = $carac['valor_agregado'];
        $has_valor_agregado = !empty(trim($val_agregado)) && $val_agregado !== 'Ninguno';
        $has_diversificacion = ($has_valor_agregado || $catCount > 1);
        $prod_score += $has_diversificacion ? 5 : 0;
        $breakdown['c3_diversificacion'] = [
            'name' => 'Procesos de diversificación productiva o transformación de materia prima',
            'max' => 5,
            'score' => $has_diversificacion ? 5 : 0,
            'status' => $has_diversificacion ? 'Cumple' : 'No cumple',
            'detail' => $has_valor_agregado ? "Valor agregado: " . substr($val_agregado, 0, 50) . "..." : "$catCount categorías productivas registradas"
        ];

        $mano_obra = $carac['mano_obra'];
        $has_mano_obra = ($mano_obra && $mano_obra !== 'Seleccione...');
        $prod_score += $has_mano_obra ? 5 : 0;
        $breakdown['c3_mano_obra'] = [
            'name' => 'Generación comprobada de empleo de carácter familiar o local',
            'max' => 5,
            'score' => $has_mano_obra ? 5 : 0,
            'status' => $has_mano_obra ? 'Cumple' : 'No cumple',
            'detail' => $has_mano_obra ? "Tipo mano de obra: $mano_obra" : 'No registra tipo de mano de obra'
        ];

        // Component 4: Comercial (Max 15 pts)
        $canalCount = isset($canales[$pid]) ? count($canales[$pid]) : 0;
        $has_canales = $canalCount > 0;
        $com_score = $has_canales ? 5 : 0;
        $breakdown['c4_canales'] = [
            'name' => 'Existencia previa y activa de canales de comercialización',
            'max' => 5,
            'score' => $has_canales ? 5 : 0,
            'status' => $has_canales ? 'Cumple' : 'No cumple',
            'detail' => "$canalCount canales activos"
        ];

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
        $breakdown['c4_frecuencia'] = [
            'name' => 'Frecuencia de comercialización periódica y demostrable',
            'max' => 5,
            'score' => $has_freq ? 5 : 0,
            'status' => $has_freq ? 'Cumple' : 'No cumple',
            'detail' => "$freq_count productos con comercialización frecuente"
        ];

        $destino = $carac['destino'];
        $has_integracion = ($destino === 'Para autoconsumo y venta de excedentes' || $destino === 'Para venta total o comercialización completa');
        $com_score += $has_integracion ? 5 : 0;
        $breakdown['c4_integracion'] = [
            'name' => 'Potencial alto de integración a la Red Agroalimentaria Territorial "Somos Sumapaz"',
            'max' => 5,
            'score' => $has_integracion ? 5 : 0,
            'status' => $has_integracion ? 'Cumple' : 'No cumple',
            'detail' => $has_integracion ? "Destino comercial: $destino" : 'No registra destino de comercialización directo'
        ];

        // Component 5: Ambiental (Max 15 pts)
        $usa_abonos = $carac['usa_abonos'];
        $has_agroecologica = ($usa_abonos == '1' || strtolower($usa_abonos) === 'sí' || strtolower($usa_abonos) === 'si');
        $amb_score = $has_agroecologica ? 5 : 0;
        $breakdown['c5_agroecologia'] = [
            'name' => 'Implementación activa de prácticas agroecológicas o ambientalmente sostenibles',
            'max' => 5,
            'score' => $has_agroecologica ? 5 : 0,
            'status' => $has_agroecologica ? 'Cumple' : 'No cumple',
            'detail' => $has_agroecologica ? 'Usa abonos orgánicos o agroecológicos' : 'No registra prácticas agroecológicas'
        ];

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
        $breakdown['c5_conservacion'] = [
            'name' => 'Acciones directas y voluntarias de conservación ambiental en el territorio',
            'max' => 5,
            'score' => $conservacion_score,
            'status' => $conservacion_score > 0 ? 'Cumple' : 'No cumple',
            'detail' => "Prácticas: " . ($abonos_val ? "Sí" : "No") . ", Sistemas asoc: " . ($asoc_val ? "Sí" : "No") . ", Sist diferenc: " . ($dif_val ? "Sí" : "No") . " ($count_yes/3)"
        ];

        $cert_count = 0;
        if (isset($certificaciones[$pid])) {
            foreach ($certificaciones[$pid] as $c) {
                if ($c != 9) $cert_count++;
            }
        }
        $has_en_tramite = $carac['en_tramite_bool'] === 'Sí';
        $has_certificaciones = ($cert_count > 0 || $has_en_tramite);
        $amb_score += $has_certificaciones ? 5 : 0;
        $breakdown['c5_requisitos'] = [
            'name' => 'Cumplimiento de requisitos sanitarios, ambientales y de inocuidad básicos aplicables',
            'max' => 5,
            'score' => $has_certificaciones ? 5 : 0,
            'status' => $has_certificaciones ? 'Cumple' : 'No cumple',
            'detail' => $has_en_tramite ? 'Certificaciones en trámite' : "$cert_count certificaciones registradas"
        ];

        // Component 6: Impacto (Max 10 pts)
        $vitrina_score = ($tiempo / $max_months) * 5;
        $vitrina_score_rounded = round($vitrina_score, 2);
        $vitrina_score_int = intval(round($vitrina_score));
        $imp_score = $vitrina_score_int;
        $breakdown['c6_vitrina'] = [
            'name' => 'Potencial demostrativo de la unidad productiva como unidad piloto o vitrina',
            'max' => 5,
            'score' => $vitrina_score_int,
            'status' => $tiempo > 0 ? 'Cumple' : 'No cumple',
            'detail' => "$tiempo meses en funcionamiento ($vitrina_score_rounded pts, máx: $max_months)"
        ];

        $imp_score += $is_ferias ? 5 : 0;
        $breakdown['c6_sello'] = [
            'name' => 'Viabilidad y potencial de articulación con el sello territorial "Somos Sumapaz"',
            'max' => 5,
            'score' => $is_ferias ? 5 : 0,
            'status' => $is_ferias ? 'Cumple' : 'Información no disponible',
            'detail' => $is_ferias ? 'Participación activa registrada en ferias y eventos' : 'No registra información de ferias'
        ];

        $total_score = $social_score + $org_score + $prod_score + $com_score + $amb_score + $imp_score;

        $results[] = [
            'id' => $pid,
            'nombre_completo' => $p['nombre_completo'],
            'vereda' => $p['vereda'],
            'puntaje' => $total_score,
            'is_complete' => $is_complete,
            'scores' => [
                'puntaje_social' => $social_score,
                'puntaje_organizacional' => $org_score,
                'puntaje_productivo' => $prod_score,
                'puntaje_comercial' => $com_score,
                'puntaje_ambiental' => $amb_score,
                'puntaje_impacto' => $imp_score,
                'puntaje_total' => $total_score,
                'breakdown' => $breakdown
            ]
        ];
    }

    // Sort by score descending
    usort($results, function($a, $b) {
        return $b['puntaje'] - $a['puntaje'];
    });

    echo json_encode(['success' => true, 'data' => $results]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener puntajes: ' . $e->getMessage()]);
}
?>
