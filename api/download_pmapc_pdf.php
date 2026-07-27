<?php
/**
 * API Endpoint: Export/Download Complete PMAPC as PDF / Printable Document
 * Styled and formatted to render 100% of all data from formats F01 to F26 matching exact column specifications
 * (including full F08 5-columns, multi-column F12A/B/C, F13..F26 tables, and Dict/String callouts).
 */

require_once __DIR__ . '/db_config.php';

$productor_id = $_GET['id'] ?? ($_POST['productor_id'] ?? null);

$data = [];
$producer = [
    'nombre_completo' => 'Productor / Unidad Productiva',
    'vereda' => 'Sumapaz',
    'nombre_organizacion' => '',
    'numero_documento' => 'No registrado'
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $rawInput = file_get_contents('php://input');
    $postInput = json_decode($rawInput, true) ?: $_POST;
    if (!empty($postInput['data'])) {
        $data = is_array($postInput['data']) ? $postInput['data'] : (json_decode($postInput['data'], true) ?: []);
    } elseif (is_array($postInput) && !isset($postInput['data'])) {
        $data = $postInput;
    }
    if (!empty($postInput['productor_id'])) {
        $productor_id = $postInput['productor_id'];
    }
}

if ($productor_id) {
    try {
        $stmtProd = $pdo->prepare("SELECT * FROM productores_sumapaz WHERE id = ?");
        $stmtProd->execute([$productor_id]);
        $fetchedProducer = $stmtProd->fetch();
        if ($fetchedProducer) {
            $producer = array_merge($producer, $fetchedProducer);
        }

        if (empty($data)) {
            $stmtPmapc = $pdo->prepare("SELECT data FROM pmapc_registros WHERE productor_id = ?");
            $stmtPmapc->execute([$productor_id]);
            $pmapcRow = $stmtPmapc->fetch();
            if ($pmapcRow && !empty($pmapcRow['data'])) {
                $data = json_decode($pmapcRow['data'], true) ?: [];
            }
        }
    } catch (Exception $e) {}
}

function val($v, $default = 'Pendiente de verificar') {
    if ($v === null || $v === '' || (is_string($v) && trim($v) === '')) return $default;
    if (is_bool($v)) return $v ? 'Sí' : 'No';
    if (is_array($v)) {
        if (empty($v)) return $default;
        // If it's a key-value associative dict, format nicely
        if (!isset($v[0])) {
            $parts = [];
            foreach ($v as $k => $val_item) {
                if (!empty($val_item)) {
                    $parts[] = '<strong>' . htmlspecialchars(ucwords(str_replace('_', ' ', $k))) . ':</strong> ' . htmlspecialchars((string)$val_item);
                }
            }
            return !empty($parts) ? implode('<br>', $parts) : $default;
        }
        return htmlspecialchars(json_encode($v, JSON_UNESCAPED_UNICODE));
    }
    return htmlspecialchars((string)$v);
}

// Helper to retrieve format data supporting PMAPC_FXX, pmapc_fXX, FXX, fXX
function getFormatData($data, $code) {
    $codeUpper = strtoupper($code);
    $codeLower = strtolower($code);

    if (isset($data["PMAPC_$codeUpper"])) return $data["PMAPC_$codeUpper"];
    if (isset($data["pmapc_$codeLower"])) return $data["pmapc_$codeLower"];
    if (isset($data[$codeUpper])) return $data[$codeUpper];
    if (isset($data[$codeLower])) return $data[$codeLower];

    return null;
}

// Extract all array lists inside a format object
function extractAllRowListsFromFormat($formatData) {
    if (empty($formatData)) return [];
    $lists = [];
    if (is_array($formatData)) {
        if (isset($formatData[0]) && is_array($formatData[0])) {
            $lists['datos'] = $formatData;
        } else {
            foreach ($formatData as $k => $v) {
                if (is_array($v) && isset($v[0]) && is_array($v[0])) {
                    $lists[$k] = $v;
                }
            }
        }
    }
    return $lists;
}

// Helper to render format block
function renderFormatBlock($formatCode, $formatTitle, $rawFormatData, $labelMap = []) {
    if (empty($rawFormatData)) return;

    echo '<div class="format-block">';
    echo '<h2 class="format-header">FORMATO PMAPC-' . htmlspecialchars($formatCode) . ' - ' . htmlspecialchars($formatTitle) . '</h2>';

    $rowLists = extractAllRowListsFromFormat($rawFormatData);

    if (!empty($rowLists)) {
        foreach ($rowLists as $listKey => $rows) {
            if ($listKey !== 'datos') {
                $listTitle = ucwords(str_replace(['_multiples_filas', '_'], ['', ' '], $listKey));
                echo '<h3 style="font-size: 11px; color: #444F2F; margin: 8px 0 4px 0;">' . htmlspecialchars($listTitle) . '</h3>';
            }
            echo '<table><thead><tr>';
            $keys = array_keys($rows[0]);
            foreach ($keys as $k) {
                $h = $labelMap[$k] ?? ucwords(str_replace('_', ' ', $k));
                echo '<th>' . htmlspecialchars($h) . '</th>';
            }
            echo '</tr></thead><tbody>';
            foreach ($rows as $r) {
                if (!is_array($r)) continue;
                echo '<tr>';
                foreach ($keys as $k) {
                    echo '<td>' . val($r[$k] ?? '') . '</td>';
                }
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
    } elseif (is_array($rawFormatData)) {
        // Key-Value table rendering (2 columns)
        echo '<table><thead><tr><th style="width: 35%;">Campo / Pregunta</th><th>Información Registrada</th></tr></thead><tbody>';
        foreach ($rawFormatData as $k => $v) {
            if ($k === 'observaciones_o_comentarios' || str_contains($k, '_registro_unico') || is_int($k)) continue;
            
            if (is_array($v) && !isset($v[0])) {
                foreach ($v as $subK => $subV) {
                    $h = $labelMap[$subK] ?? ucwords(str_replace('_', ' ', $subK));
                    echo '<tr><td style="font-weight: 600; background-color: #F8FAFC;">' . htmlspecialchars($h) . '</td><td>' . val($subV) . '</td></tr>';
                }
            } else {
                $h = $labelMap[$k] ?? ucwords(str_replace('_', ' ', $k));
                echo '<tr><td style="font-weight: 600; background-color: #F8FAFC;">' . htmlspecialchars($h) . '</td><td>' . val($v) . '</td></tr>';
            }
        }
        echo '</tbody></table>';
    } elseif (is_string($rawFormatData) && trim($rawFormatData) !== '') {
        echo '<div class="text-content-box">' . nl2br(htmlspecialchars($rawFormatData)) . '</div>';
    }

    // Render any single-register note (e.g. recomendacion_costeo_registro_unico, conclusion_registro_unico)
    if (is_array($rawFormatData)) {
        foreach ($rawFormatData as $k => $v) {
            if (str_contains($k, '_registro_unico') && !empty($v)) {
                $noteTitle = ucwords(str_replace(['_registro_unico', '_'], ['', ' '], $k));
                echo '<div class="single-register-box" style="margin-top: 8px; background-color: #F4F1EA; border-left: 4px solid #8E9A5E; padding: 8px 12px; border-radius: 4px; font-size: 11px;">';
                echo '<strong style="color: #444F2F; font-size: 11.5px;">' . htmlspecialchars($noteTitle) . ':</strong><br>' . val($v);
                echo '</div>';
            }
        }
    }

    $obs = is_array($rawFormatData) ? ($rawFormatData['observaciones_o_comentarios'] ?? null) : null;
    if ($obs) {
        echo '<div style="margin-top: 6px; font-style: italic; font-size: 10px; color: #555;"><strong>Observaciones del formato:</strong> ' . val($obs) . '</div>';
    }

    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>PMAPC - <?php echo val($producer['nombre_completo']); ?></title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #222222;
            line-height: 1.5;
            font-size: 11px;
            background: #FFFFFF;
            margin: 0;
            padding: 10px;
        }
        .no-print-bar {
            background: #2D372E;
            color: #FFFFFF;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: -10px -10px 15px -10px;
        }
        .btn-print {
            background: #8E9A5E;
            color: white;
            border: none;
            padding: 8px 18px;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
            font-size: 12px;
        }
        .doc-title-box {
            border: 2px solid #444F2F;
            background-color: #F4F1EA;
            padding: 16px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .doc-title-box h1 {
            color: #444F2F;
            margin: 0 0 6px 0;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .doc-title-box p {
            margin: 3px 0;
            font-size: 11.5px;
            color: #333333;
        }
        .format-block {
            margin-bottom: 22px;
            page-break-inside: avoid;
        }
        .format-header {
            font-size: 12px;
            font-weight: 700;
            color: #444F2F;
            background-color: #EFEBE4;
            padding: 8px 12px;
            border-left: 5px solid #444F2F;
            margin: 0 0 8px 0;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
            margin-top: 4px;
        }
        table th {
            background-color: #444F2F;
            color: #FFFFFF;
            text-align: left;
            padding: 7px 10px;
            border: 1px solid #333C23;
            font-weight: 700;
            font-size: 10.5px;
        }
        table td {
            padding: 6px 10px;
            border: 1px solid #D0D0D0;
            word-wrap: break-word;
            vertical-align: top;
        }
        table tbody tr:nth-child(even) {
            background-color: #F9F8F6;
        }
        .text-content-box {
            background-color: #F9F8F6;
            border: 1px solid #E0E0E0;
            padding: 10px 14px;
            border-radius: 4px;
            font-size: 11px;
            line-height: 1.5;
        }
        .comments-box {
            background-color: #FFFDE7;
            border: 1px solid #FFE082;
            color: #5D4037;
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 11px;
            white-space: pre-wrap;
            line-height: 1.5;
        }
        @media print {
            .no-print-bar { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

<div class="no-print-bar">
    <span>Documento Oficial PMAPC — Somos Sumapaz</span>
    <button class="btn-print" onclick="window.print()">Guardar como PDF / Imprimir</button>
</div>

<div class="doc-title-box">
    <h1>PLAN DE MANEJO AMBIENTAL, PRODUCTIVO Y COMERCIAL (PMAPC)</h1>
    <p><strong>Unidad Productiva / Organización:</strong> <?php echo val($data['nombre_organizacion'] ?? ($producer['nombre_organizacion'] ?? ($data['PMAPC_F01']['nombre_unidad_productiva'] ?? ($data['f01']['nombre_organizacion'] ?? 'Unidad Productiva')))); ?></p>
    <p><strong>Productor(a):</strong> <?php echo val($producer['nombre_completo']); ?> &nbsp;|&nbsp; <strong>Vereda:</strong> <?php echo val($producer['vereda']); ?> &nbsp;|&nbsp; <strong>Cédula:</strong> <?php echo val($producer['numero_documento'] ?? 'No registrado'); ?></p>
    <p><strong>Fecha de elaboración:</strong> <?php echo val($data['fecha_cargue'] ?? date('d/m/Y')); ?></p>
</div>

<!-- Render Formats F01 to F26 -->
<?php
$formatsList = [
    'F01' => 'IDENTIDAD DE LA UNIDAD PRODUCTIVA',
    'F02' => 'DIRECCIONAMIENTO ESTRATÉGICO',
    'F03' => 'PROPUESTA DE VALOR PRODUCTIVA, COMERCIAL Y AMBIENTAL',
    'F04' => 'ANÁLISIS FODA SISTÉMICO',
    'F05' => 'PERFIL DE CLIENTES, CONSUMIDORES Y COMPRADORES',
    'F06' => 'PROBLEMA DEL MERCADO Y NECESIDADES',
    'F07' => 'ANÁLISIS DE COOPERACIÓN TERRITORIAL Y ALIANZAS PRODUCTIVAS',
    'F08' => 'VALIDACIÓN DE MERCADO Y ACEPTACIÓN DEL SERVICIO',
    'F09' => 'FICHA TÉCNICA DEL PRODUCTO O SERVICIO',
    'F10' => 'PROCESO DE PRESTACIÓN DEL SERVICIO / PRODUCCIÓN',
    'F11' => 'INSUMOS Y MATERIAS PRIMAS NECESARIAS',
    'F12' => 'CAPACIDAD PRODUCTIVA E INFRAESTRUCTURA',
    'F12A' => 'LÍMITES AMBIENTALES Y CAPACIDAD PRODUCTIVA',
    'F12B' => 'IDENTIFICACIÓN DE RIESGOS DE SEGURIDAD Y SALUD EN EL TRABAJO (SG-SST)',
    'F12C' => 'PLAN BÁSICO DE ACCIONES SG-SST',
    'F13' => 'CANALES DE VENTA Y COSTOS FIJOS',
    'F14' => 'ESTRATEGIA DE PRECIOS Y MARGEN DE COMERCIALIZACIÓN',
    'F15' => 'PROYECCIÓN DE VENTAS E INGRESOS',
    'F15A' => 'ESTRATEGIA DE FIDELIZACIÓN DE CLIENTES',
    'F15B' => 'ESTRATEGIA DE LOGÍSTICA DE ÚLTIMA MILLA',
    'F15C' => 'TRAZABILIDAD DIGITAL VÍA QR',
    'F16' => 'INVERSIÓN INICIAL Y REQUERIDA',
    'F17' => 'ESTRUCTURA DE COSTOS Y GASTOS OPERATIVOS',
    'F18' => 'PROYECCIÓN DE FLUJO DE CAJA E INDICADORES',
    'F19' => 'INDICADORES DE HUELLA HÍDRICA Y DE CARBONO / SEGUIMIENTO',
    'F20' => 'ECONOMÍA CIRCULAR Y RESIDUOS',
    'F21' => 'EVALUACIÓN Y MATRIZ DE MADUREZ AMBIENTAL Y REGENERATIVA',
    'F22' => 'PLAN DE MANEJO, MITIGACIÓN Y MEJORA AMBIENTAL',
    'F22A' => 'ADAPTACIÓN AL CAMBIO CLIMÁTICO',
    'F23' => 'MATRIZ DE RIESGOS INTEGRALES',
    'F24' => 'PLAN DE ACCIÓN Y COMPROMISOS SELLO SOMOS SUMAPAZ',
    'F25' => 'INDICADORES INTEGRALES Y ANEXOS',
    'F26' => 'MATRIZ DE COHERENCIA SISTÉMICA Y EVALUACIÓN TÉCNICA'
];

$labelMaps = [
    'metodo' => 'Método / Canal',
    'a_quien' => 'A quién',
    'resultado' => 'Resultado Obtenido',
    'motivacion' => 'Motivación de Compra',
    'evidencia' => 'Evidencia Registrada',
    'nombre_unidad_productiva' => 'Nombre de la Unidad Productiva',
    'persona_entrevistada' => 'Persona entrevistada',
    'tipo_actividad' => 'Tipo de actividad',
    'ubicacion_especifica' => 'Ubicación específica',
    'producto_servicio_principal' => 'Producto / servicio principal',
    'estado_actual' => 'Estado actual',
    'personas_vinculadas' => 'Personas vinculadas',
    'coordenadas' => 'Coordenadas geográficas',
    'descripcion_general' => 'Descripción general',
    'mision' => 'Misión',
    'vision' => 'Visión',
    'valores' => 'Valores institucionales',
    'por_que_adquieren_el_servicio' => '¿Por qué adquieren el servicio/producto?',
    'beneficio_cliente' => 'Beneficio para el cliente / solución',
    'diferencial' => 'Diferencial competitivo',
    'valor_ambiental' => 'Valor agregado ambiental',
    'valor_social_comunitario' => 'Valor social o comunitario',
    'que_buscan_los_compradores' => '¿Qué buscan los compradores?',
    'como_se_conoce_la_necesidad' => '¿Cómo se conoce esa necesidad?',
    'quienes_compran_o_podrian_comprar' => '¿Quiénes compran o podrían comprar?',
    'ventaja_territorial' => 'Ventaja territorial Sumapaz / orgánicos',
    'cambios_demanda' => 'Cambios recientes en la demanda',
    'dificultades' => 'Dificultades principales de venta',
    'produccion_mensual_real' => 'Producción mensual real',
    'produccion_normal' => 'Producción normal de referencia',
    'capacidad_mensual_teorica' => 'Capacidad mensual teórica',
    'produccion_maxima_posible' => 'Producción máxima posible',
    'area_numero_habitaciones' => 'Área / N° de habitaciones / lote',
    'limitantes_productivos' => 'Limitantes técnico-productivos',
    'limitantes_ambientales' => 'Limitantes ambientales',
    'capacidad_instalada' => 'Capacidad instalada',
    'capacidad_utilizada' => 'Capacidad utilizada actual',
    'produce_mismo_todo_ano' => '¿Produce lo mismo todo el año?',
    'alcanza_para_demanda' => '¿Alcanza para la demanda?',
    'necesidades_aumentar_sosteniblemente' => 'Necesidades para aumentar sosteniblemente',
    'condicion' => 'Condición Ambiental',
    'limite_restriccion' => 'Límite / Restricción',
    'afectacion' => 'Afectación',
    'efecto' => 'Efecto / Riesgo',
    'incidencia' => 'Incidencia',
    'accion_mejora' => 'Acción de Mejora',
    'peligro' => 'Peligro u Origen',
    'aplica' => 'Aplica (Sí/No)',
    'nivel_riesgo' => 'Nivel de Riesgo',
    'controles_actuales' => 'Controles Actuales',
    'accion_preventiva' => 'Acción Preventiva',
    'responsable' => 'Responsable',
    'frecuencia' => 'Frecuencia',
    'canal' => 'Canal de Comercialización',
    'activo' => 'Activo (Sí/No)',
    'servicio' => 'Servicio / Producto',
    'costo_unitario' => 'Costo Unitario ($)',
    'margen' => 'Margen (%)',
    'precio_minimo' => 'Precio Mínimo ($)',
    'precio_final' => 'Precio Final ($)',
    'concepto_servicio' => 'Concepto / Servicio',
    'cantidad_mensual' => 'Cantidad Mensual',
    'precio' => 'Precio ($)',
    'ingreso_mensual' => 'Ingreso Mensual ($)',
    'forma_pago' => 'Forma de Pago',
    'cliente' => 'Perfil de Cliente',
    'estrategia' => 'Estrategia',
    'medio' => 'Medio',
    'servicio_insumo' => 'Servicio / Insumo',
    'tiempo' => 'Tiempo',
    'transporte_operacion' => 'Transporte / Operación',
    'condicion_calidad' => 'Condición de Calidad',
    'capacidad' => 'Capacidad',
    'costo' => 'Costo ($)',
    'elemento' => 'Elemento Trazable',
    'informacion' => 'Información Contenida',
    'desc' => 'Descripción',
    'valunit' => 'Valor Unitario ($)',
    'cant' => 'Cantidad',
    'total' => 'Total ($)',
    'req' => 'Requerimiento',
    'fuente' => 'Fuente',
    'valor_mensual_ciclo' => 'Valor Mensual ($)',
    'mes' => 'Mes',
    'ingresos' => 'Ingresos ($)',
    'gastos_operativos' => 'Gastos Operativos ($)',
    'gastos_comerciales' => 'Gastos Comerciales ($)',
    'gastos_ambientales' => 'Gastos Ambientales ($)',
    'balance' => 'Balance / Flujo Neto ($)',
    'variable' => 'Variable Ambiental',
    'estado' => 'Estado',
    'cantidad' => 'Cantidad',
    'impacto_1_a_5' => 'Impacto (1-5)',
    'accion' => 'Acción',
    'indicador' => 'Indicador',
    'que_se_mide' => '¿Qué se mide?',
    'dato_inicial' => 'Dato Inicial',
    'meta' => 'Meta',
    'residuo_recurso' => 'Residuo / Recurso',
    'manejo_actual' => 'Manejo Actual',
    'accion_circular' => 'Acción Circular',
    'destino' => 'Destino',
    'factor' => 'Factor / Criterio',
    'calificacion_1_a_5' => 'Calificación (1-5)',
    'impacto' => 'Impacto',
    'prioridad' => 'Prioridad',
    'plazo' => 'Plazo',
    'recursos' => 'Recursos',
    'aspecto' => 'Aspecto / Fenómeno',
    'situacion' => 'Situación',
    'riesgo' => 'Riesgo',
    'accion_propuesta' => 'Acción Propuesta',
    'causa' => 'Causa',
    'consecuencia' => 'Consecuencia',
    'nivel' => 'Nivel',
    'prevencion' => 'Prevención',
    'respuesta' => 'Respuesta',
    'actividad' => 'Actividad / Compromiso',
    'componente' => 'Componente',
    'resultado' => 'Resultado Esperado',
    'dimension' => 'Dimensión',
    'decision' => 'Decisión / Variable',
    'efecto_productivo' => 'Efecto Productivo',
    'efecto_comercial' => 'Efecto Comercial',
    'efecto_financiero' => 'Efecto Financiero',
    'efecto_ambiental' => 'Efecto Ambiental',
    'ajuste_necesario' => 'Ajuste Necesario'
];

foreach ($formatsList as $fCode => $fTitle) {
    $fData = getFormatData($data, $fCode);
    if ($fData !== null) {
        renderFormatBlock($fCode, $fTitle, $fData, $labelMaps);
    }
}
?>

<!-- COMENTARIOS Y OBSERVACIONES DE VERIFICACIÓN -->
<div class="format-block">
    <h2 class="format-header">COMENTARIOS, OBSERVACIONES E INFORMACIÓN PENDIENTE DE VERIFICAR</h2>
    <div class="comments-box">
        <?php echo val($data['pdf_comentarios'] ?? ($data['comentarios'] ?? 'Sin observaciones adicionales.')); ?>
    </div>
</div>

<script>
    window.onload = function() {
        setTimeout(function() {
            window.print();
        }, 500);
    };
</script>

</body>
</html>
