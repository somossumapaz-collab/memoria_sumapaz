<?php
/**
 * Helper to calculate and verify evaluation scores for Proyecto 2315
 */

function check_caracterizacion_completeness($pdo, $productor_id, $carac) {
    $isEmpty = function($val) {
        return $val === null || $val === '' || $val === 'Ninguno' || $val === 'Ninguna' || $val === 'Seleccione...';
    };

    if ($isEmpty($carac['tipo_organizacion'])) return false;
    if ($isEmpty($carac['extension_predio'])) return false;
    if ($isEmpty($carac['tiempo_implementacion'])) return false;
    if ($isEmpty($carac['tipo_tenencia'])) return false;
    if ($isEmpty($carac['numero_personas'])) return false;
    if ($isEmpty($carac['mano_obra'])) return false;
    if ($isEmpty($carac['tipo_proceso'])) return false;

    if ($carac['sistema_diferenciado'] == "1" && $isEmpty($carac['descripcion'])) {
        return false;
    }

    if ($isEmpty($carac['destino'])) return false;
    if ($isEmpty($carac['transporte'])) return false;
    if ($isEmpty($carac['forma_pago'])) return false;
    if ($isEmpty($carac['define_precio'])) return false;

    if ($carac['en_tramite_bool'] === "Sí" && $isEmpty($carac['en_tramite'])) {
        return false;
    }

    // Check categories count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM productor_categoria WHERE productor_id = ?");
    $stmt->execute([$productor_id]);
    if ($stmt->fetchColumn() == 0) return false;

    // Check products or services count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM productor_productos WHERE productor_id = ?");
    $stmt->execute([$productor_id]);
    $prod_count = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM productor_servicios WHERE productor_id = ?");
    $stmt->execute([$productor_id]);
    $serv_count = $stmt->fetchColumn();

    if ($prod_count == 0 && $serv_count == 0) return false;

    return true;
}

function calculate_producer_scores($pdo, $productor_id) {
    // 1. Get characterization data
    $stmt = $pdo->prepare("SELECT * FROM caracterizacion_productor WHERE productor_id = ?");
    $stmt->execute([$productor_id]);
    $carac = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$carac) {
        return [
            'tiene_caracterizacion' => false,
            'is_complete' => false,
            'puntaje_social' => null,
            'puntaje_organizacional' => null,
            'puntaje_productivo' => null,
            'puntaje_comercial' => null,
            'puntaje_ambiental' => null,
            'puntaje_impacto' => null,
            'puntaje_total' => null,
            'breakdown' => []
        ];
    }

    $is_complete = check_caracterizacion_completeness($pdo, $productor_id, $carac);

    $breakdown = [];

    // Component 1: Enfoque Diferencial y Social (Max 20 pts)
    $social_score = 0;
    
    // Subcriterion 1.1: Discapacidad (5 pts)
    $stmt_disc = $pdo->prepare("SELECT COUNT(*) FROM discapacidad_productor WHERE productor_id = ? AND tiene_discapacidad = 'Sí'");
    $stmt_disc->execute([$productor_id]);
    $has_discapacidad = $stmt_disc->fetchColumn() > 0;
    $social_score += $has_discapacidad ? 5 : 0;
    $breakdown['c1_discapacidad'] = [
        'name' => 'El postulante es persona con discapacidad certificada o verificable',
        'max' => 5,
        'score' => $has_discapacidad ? 5 : 0,
        'status' => $has_discapacidad ? 'Cumple' : 'No cumple'
    ];

    // Get population groups
    $stmt_groups = $pdo->prepare("SELECT grupo_id FROM productor_grupo WHERE productor_id = ?");
    $stmt_groups->execute([$productor_id]);
    $groups = $stmt_groups->fetchAll(PDO::FETCH_COLUMN);

    // Subcriterion 1.2: Mujer cabeza de hogar (5 pts)
    $is_cabeza_hogar = in_array(1, $groups);
    $social_score += $is_cabeza_hogar ? 5 : 0;
    $breakdown['c1_mujer_cabeza'] = [
        'name' => 'El postulante es mujer cabeza de hogar',
        'max' => 5,
        'score' => $is_cabeza_hogar ? 5 : 0,
        'status' => $is_cabeza_hogar ? 'Cumple' : 'No cumple'
    ];

    // Subcriterion 1.3: Víctima del conflicto (5 pts)
    $is_victima = in_array(3, $groups);
    $social_score += $is_victima ? 5 : 0;
    $breakdown['c1_victima'] = [
        'name' => 'El postulante es víctima reconocida del conflicto armado',
        'max' => 5,
        'score' => $is_victima ? 5 : 0,
        'status' => $is_victima ? 'Cumple' : 'No cumple'
    ];

    // Subcriterion 1.4: Joven rural (18-28) o Adulto mayor (>=60) (5 pts)
    $stmt_prod = $pdo->prepare("SELECT fecha_nacimiento, panaca, ferias FROM productores_sumapaz WHERE id = ?");
    $stmt_prod->execute([$productor_id]);
    $prod_info = $stmt_prod->fetch(PDO::FETCH_ASSOC);
    $birth = $prod_info ? $prod_info['fecha_nacimiento'] : null;
    $panaca = $prod_info ? intval($prod_info['panaca']) : 0;
    $ferias = $prod_info ? intval($prod_info['ferias']) : 0;
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
    if (in_array(7, $groups) || in_array(6, $groups)) {
        $is_joven_or_adulto = true;
    }
    $social_score += $is_joven_or_adulto ? 5 : 0;
    $breakdown['c1_joven_adulto'] = [
        'name' => 'El postulante es joven rural (18 a 28 años) o adulto mayor (60 años o más)',
        'max' => 5,
        'score' => $is_joven_or_adulto ? 5 : 0,
        'status' => $is_joven_or_adulto ? 'Cumple' : 'No cumple',
        'detail' => $age ? "Edad calculada en 2026: $age años" : null
    ];


    // Component 2: Fortalecimiento Organizacional y Comunitario (Max 15 pts)
    $org_score = 0;
    
    // Subcriterion 2.1: Org productivas locales (5 pts)
    $tipo_org = $carac['tipo_organizacion'];
    $has_org = ($tipo_org && $tipo_org !== 'Ninguna' && $tipo_org !== 'Productor individual');
    $org_score += $has_org ? 5 : 0;
    $breakdown['c2_organizacion'] = [
        'name' => 'Participación activa demostrable en organizaciones productivas locales',
        'max' => 5,
        'score' => $has_org ? 5 : 0,
        'status' => $has_org ? 'Cumple' : 'No cumple',
        'detail' => $has_org ? "Organización: $tipo_org" : null
    ];

    // Subcriterion 2.2: Articulación comunitaria (5 pts) - SI ES FERIAS
    $is_ferias = ($ferias == 1);
    $org_score += $is_ferias ? 5 : 0;
    $breakdown['c2_comunitaria'] = [
        'name' => 'Participación en otros procesos de articulación comunitaria',
        'max' => 5,
        'score' => $is_ferias ? 5 : 0,
        'status' => $is_ferias ? 'Cumple' : 'Información no disponible',
        'detail' => $is_ferias ? 'Participación registrada en ferias y eventos locales/regionales' : 'No registra participación en ferias u otros procesos'
    ];

    // Subcriterion 2.3: Gestor conocimiento (5 pts) - CALIFICADO SI ES PANACA
    $is_panaca = ($panaca == 1);
    $org_score += $is_panaca ? 5 : 0;
    $breakdown['c2_gestor'] = [
        'name' => 'Potencial e idoneidad como gestor de conocimiento local',
        'max' => 5,
        'score' => $is_panaca ? 5 : 0,
        'status' => $is_panaca ? 'Cumple' : 'No cumple',
        'detail' => $is_panaca ? 'Postulante certificado por PANACA' : 'No cuenta con certificación PANACA'
    ];


    // Component 3: Capacidad Productiva y Maduración (Max 25 pts)
    $prod_score = 0;

    // Subcriterion 3.1: Tiempo de actividad > 3 años (5 pts)
    $tiempo = intval($carac['tiempo_implementacion']);
    $has_tiempo = $tiempo >= 36;
    $prod_score += $has_tiempo ? 5 : 0;
    $breakdown['c3_tiempo'] = [
        'name' => 'Tiempo de implementación continua de la actividad superior a tres (3) años',
        'max' => 5,
        'score' => $has_tiempo ? 5 : 0,
        'status' => $has_tiempo ? 'Cumple' : 'No cumple',
        'detail' => "$tiempo meses registrados"
    ];

    // Subcriterion 3.2: Tenencia estable del predio (5 pts)
    $tenencia = $carac['tipo_tenencia'];
    $has_tenencia = ($tenencia && $tenencia !== 'Seleccione...');
    $prod_score += $has_tenencia ? 5 : 0;
    $breakdown['c3_tenencia'] = [
        'name' => 'Tenencia formal, estable o legítima explotación del predio',
        'max' => 5,
        'score' => $has_tenencia ? 5 : 0,
        'status' => $has_tenencia ? 'Cumple' : 'No cumple',
        'detail' => $has_tenencia ? "Tenencia: $tenencia" : null
    ];

    // Subcriterion 3.3: Producción verificable, regular y permanente (5 pts)
    $stmt_prod_count = $pdo->prepare("SELECT COUNT(*) FROM productor_productos WHERE productor_id = ?");
    $stmt_prod_count->execute([$productor_id]);
    $prod_count = $stmt_prod_count->fetchColumn();

    $stmt_serv_count = $pdo->prepare("SELECT COUNT(*) FROM productor_servicios WHERE productor_id = ?");
    $stmt_serv_count->execute([$productor_id]);
    $serv_count = $stmt_serv_count->fetchColumn();
    
    $has_produccion = ($prod_count + $serv_count > 0);
    $prod_score += $has_produccion ? 5 : 0;
    $breakdown['c3_produccion'] = [
        'name' => 'Producción verificable, regular y permanente',
        'max' => 5,
        'score' => $has_produccion ? 5 : 0,
        'status' => $has_produccion ? 'Cumple' : 'No cumple',
        'detail' => "$prod_count productos y $serv_count servicios ofertados"
    ];

    // Subcriterion 3.4: Diversificación o transformación (5 pts)
    $stmt_cat_count = $pdo->prepare("SELECT COUNT(*) FROM productor_categoria WHERE productor_id = ?");
    $stmt_cat_count->execute([$productor_id]);
    $cat_count = $stmt_cat_count->fetchColumn();
    $has_valor_agregado = !empty(trim($carac['valor_agregado'] ?? '')) && $carac['valor_agregado'] !== 'Ninguno';
    $has_diversificacion = ($has_valor_agregado || $cat_count > 1);
    $prod_score += $has_diversificacion ? 5 : 0;
    $breakdown['c3_diversificacion'] = [
        'name' => 'Procesos de diversificación productiva o transformación de materia prima',
        'max' => 5,
        'score' => $has_diversificacion ? 5 : 0,
        'status' => $has_diversificacion ? 'Cumple' : 'No cumple',
        'detail' => $has_valor_agregado ? "Valor agregado: " . substr($carac['valor_agregado'], 0, 50) . "..." : "$cat_count categorías productivas registradas"
    ];

    // Subcriterion 3.5: Empleo familiar o local (5 pts)
    $mano_obra = $carac['mano_obra'];
    $has_mano_obra = ($mano_obra && $mano_obra !== 'Seleccione...');
    $prod_score += $has_mano_obra ? 5 : 0;
    $breakdown['c3_mano_obra'] = [
        'name' => 'Generación comprobada de empleo de carácter familiar o local',
        'max' => 5,
        'score' => $has_mano_obra ? 5 : 0,
        'status' => $has_mano_obra ? 'Cumple' : 'No cumple',
        'detail' => $has_mano_obra ? "Tipo mano de obra: $mano_obra" : null
    ];


    // Component 4: Comercialización y Sostenibilidad Económica (Max 15 pts)
    $com_score = 0;

    // Subcriterion 4.1: Canales de comercialización (5 pts)
    $stmt_canal_count = $pdo->prepare("SELECT COUNT(*) FROM productor_canal WHERE productor_id = ?");
    $stmt_canal_count->execute([$productor_id]);
    $canal_count = $stmt_canal_count->fetchColumn();
    $has_canales = $canal_count > 0;
    $com_score += $has_canales ? 5 : 0;
    $breakdown['c4_canales'] = [
        'name' => 'Existencia previa y activa de canales de comercialización',
        'max' => 5,
        'score' => $has_canales ? 5 : 0,
        'status' => $has_canales ? 'Cumple' : 'No cumple',
        'detail' => "$canal_count canales activos"
    ];

    // Subcriterion 4.2: Frecuencia periódica de comercialización (5 pts)
    $stmt_freq = $pdo->prepare("SELECT COUNT(*) FROM productor_productos WHERE productor_id = ? AND frecuencia IN ('Diarios', 'Semanal', 'Quincenal', 'Mensual')");
    $stmt_freq->execute([$productor_id]);
    $freq_count = $stmt_freq->fetchColumn();
    $has_freq = $freq_count > 0;
    $com_score += $has_freq ? 5 : 0;
    $breakdown['c4_frecuencia'] = [
        'name' => 'Frecuencia de comercialización periódica y demostrable',
        'max' => 5,
        'score' => $has_freq ? 5 : 0,
        'status' => $has_freq ? 'Cumple' : 'No cumple',
        'detail' => "$freq_count productos con comercialización frecuente"
    ];

    // Subcriterion 4.3: Integración Red Somos Sumapaz (5 pts)
    $destino = $carac['destino'];
    $has_integracion = ($destino === 'Para autoconsumo y venta de excedentes' || $destino === 'Para venta total o comercialización completa');
    $com_score += $has_integracion ? 5 : 0;
    $breakdown['c4_integracion'] = [
        'name' => 'Potencial alto de integración a la Red Agroalimentaria Territorial "Somos Sumapaz"',
        'max' => 5,
        'score' => $has_integracion ? 5 : 0,
        'status' => $has_integracion ? 'Cumple' : 'No cumple',
        'detail' => $has_integracion ? "Destino: $destino" : null
    ];


    // Component 5: Sostenibilidad Ambiental y Cumplimiento Normativo (Max 15 pts)
    $amb_score = 0;

    // Subcriterion 5.1: Prácticas agroecológicas (5 pts)
    $usa_abonos = $carac['usa_abonos'];
    $has_agroecologica = ($usa_abonos == '1' || strtolower($usa_abonos ?? '') === 'sí' || strtolower($usa_abonos ?? '') === 'si');
    $amb_score += $has_agroecologica ? 5 : 0;
    $breakdown['c5_agroecologia'] = [
        'name' => 'Implementación activa de prácticas agroecológicas o ambientalmente sostenibles',
        'max' => 5,
        'score' => $has_agroecologica ? 5 : 0,
        'status' => $has_agroecologica ? 'Cumple' : 'No cumple'
    ];

    // Subcriterion 5.2: Conservación ambiental (5 pts) - BASADO EN ABONOS, ASOCIADOS, DIFERENCIADOS
    $count_yes = 0;
    $abonos_val = ($carac['usa_abonos'] == 1);
    $asoc_val = ($carac['sistemas_asociados'] == 1);
    $dif_val = ($carac['sistema_diferenciado'] == 1);
    
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
    
    $amb_score += $conservacion_score;
    
    $details_list = [];
    $details_list[] = "Abonos/agroecología: " . ($abonos_val ? "Sí" : "No");
    $details_list[] = "Sistemas asociados: " . ($asoc_val ? "Sí" : "No");
    $details_list[] = "Producción diferenciada: " . ($dif_val ? "Sí" : "No");
    
    $breakdown['c5_conservacion'] = [
        'name' => 'Acciones directas y voluntarias de conservación ambiental en el territorio',
        'max' => 5,
        'score' => $conservacion_score,
        'status' => $conservacion_score > 0 ? 'Cumple' : 'No cumple',
        'detail' => implode(', ', $details_list) . " ($count_yes/3 criterios cumplidos)"
    ];

    // Subcriterion 5.3: Cumplimiento de requisitos sanitarios/ambientales (5 pts)
    $stmt_cert = $pdo->prepare("SELECT COUNT(*) FROM productor_certificacion WHERE productor_id = ? AND certificacion_id != 9");
    $stmt_cert->execute([$productor_id]);
    $cert_count = $stmt_cert->fetchColumn();
    $has_en_tramite = $carac['en_tramite_bool'] === 'Sí';
    $has_certificaciones = ($cert_count > 0 || $has_en_tramite);
    $amb_score += $has_certificaciones ? 5 : 0;
    $breakdown['c5_requisitos'] = [
        'name' => 'Cumplimiento de requisitos sanitarios, ambientales y de inocuidad básicos aplicables',
        'max' => 5,
        'score' => $has_certificaciones ? 5 : 0,
        'status' => $has_certificaciones ? 'Cumple' : 'No cumple',
        'detail' => $has_en_tramite ? 'Certificado/permiso en trámite' : "$cert_count certificaciones registradas"
    ];


    // Component 6: Impacto Territorial e Innovación (Max 10 pts)
    $imp_score = 0;

    // Subcriterion 6.1: Unidad vitrina (5 pts) - BASADO EN TIEMPO DE IMPLEMENTACIÓN (Regla de 3)
    static $max_months = null;
    if ($max_months === null) {
        $stmt_max = $pdo->query("SELECT MAX(CAST(tiempo_implementacion AS UNSIGNED)) FROM caracterizacion_productor");
        $max_months = intval($stmt_max->fetchColumn());
        if ($max_months <= 0) {
            $max_months = 1;
        }
    }
    $tiempo = intval($carac['tiempo_implementacion']);
    
    // Regla de 3
    $vitrina_score = ($tiempo / $max_months) * 5;
    $vitrina_score_rounded = round($vitrina_score, 2);
    $vitrina_score_int = intval(round($vitrina_score));
    
    $imp_score += $vitrina_score_int;
    
    $breakdown['c6_vitrina'] = [
        'name' => 'Potencial demostrativo de la unidad productiva como unidad piloto o vitrina',
        'max' => 5,
        'score' => $vitrina_score_int,
        'status' => $tiempo > 0 ? 'Cumple' : 'No cumple',
        'detail' => "$tiempo meses en funcionamiento ($vitrina_score_rounded pts calculados, máximo en sistema: $max_months meses)"
    ];

    // Subcriterion 6.2: Articulación sello Somos Sumapaz (5 pts) - SI ES FERIAS
    $is_ferias = ($ferias == 1);
    $imp_score += $is_ferias ? 5 : 0;
    $breakdown['c6_sello'] = [
        'name' => 'Viabilidad y potencial de articulación con el sello territorial "Somos Sumapaz"',
        'max' => 5,
        'score' => $is_ferias ? 5 : 0,
        'status' => $is_ferias ? 'Cumple' : 'Información no disponible',
        'detail' => $is_ferias ? 'Potencial demostrado a través de participación activa en ferias' : 'No registra información de ferias'
    ];

    $total_score = $social_score + $org_score + $prod_score + $com_score + $amb_score + $imp_score;

    return [
        'tiene_caracterizacion' => true,
        'is_complete' => $is_complete,
        'puntaje_social' => $social_score,
        'puntaje_organizacional' => $org_score,
        'puntaje_productivo' => $prod_score,
        'puntaje_comercial' => $com_score,
        'puntaje_ambiental' => $amb_score,
        'puntaje_impacto' => $imp_score,
        'puntaje_total' => $total_score,
        'breakdown' => $breakdown
    ];
}

function recalculate_and_save_score($pdo, $productor_id) {
    // 1. Calculate scores
    $scores = calculate_producer_scores($pdo, $productor_id);
    
    if (!$scores['tiene_caracterizacion']) {
        return false;
    }
    // 2. Update characterization with calculated scores

    // 3. Update characterization with calculated scores
    $stmt = $pdo->prepare("
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
    $stmt->execute([
        $scores['puntaje_social'],
        $scores['puntaje_organizacional'],
        $scores['puntaje_productivo'],
        $scores['puntaje_comercial'],
        $scores['puntaje_ambiental'],
        $scores['puntaje_impacto'],
        $scores['puntaje_total'],
        $productor_id
    ]);

    return true;
}
?>
