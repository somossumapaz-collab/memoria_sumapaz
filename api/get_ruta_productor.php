<?php
/**
 * API Endpoint: Get Producer Route (Ruta del Productor)
 * Compiles timeline milestone bubbles for a specific producer:
 * 1. Registro y Vinculación (tenure/antigüedad)
 * 2. Diagnóstico y PMAPC (caracterización, priorización, selección PMAPC F01-F26)
 * 3. Esquema de Comercialización (canales, transporte, pago)
 * 4. Circuitos de Comercialización y Eventos (historial de eventos y ferias)
 * 5. Concursos y Evaluaciones (concursos tradicionales, puntajes y jurados)
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_config.php';

try {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $doc = isset($_GET['documento']) ? trim($_GET['documento']) : '';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    $productor = null;

    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM productores_sumapaz WHERE id = ?");
        $stmt->execute([$id]);
        $productor = $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif (!empty($doc)) {
        $stmt = $pdo->prepare("SELECT * FROM productores_sumapaz WHERE numero_documento = ?");
        $stmt->execute([$doc]);
        $productor = $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif (!empty($search)) {
        $stmt = $pdo->prepare("SELECT * FROM productores_sumapaz WHERE nombre_completo LIKE ? OR numero_documento LIKE ? LIMIT 1");
        $stmt->execute(["%$search%", "%$search%"]);
        $productor = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$productor) {
        if (isset($_GET['list_producers'])) {
            $stmtList = $pdo->query("SELECT id, nombre_completo, numero_documento, vereda, nombre_predio FROM productores_sumapaz ORDER BY nombre_completo ASC");
            echo json_encode(['success' => true, 'producers' => $stmtList->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
            exit;
        }

        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Productor no encontrado'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $productorId = intval($productor['id']);
    $documento = trim($productor['numero_documento']);
    $nombreCompleto = trim($productor['nombre_completo']);

    // --- BURBUJA 1: Registro y Vinculación ---
    $fechaCreacion = $productor['fecha_creacion'] ?: ($productor['fecha_registro'] ?: '2026-01-01 00:00:00');
    $tsCreacion = strtotime($fechaCreacion);
    $now = time();
    $diffDays = floor(($now - $tsCreacion) / (60 * 60 * 24));
    
    $antiguedadTexto = "";
    if ($diffDays < 30) {
        $antiguedadTexto = "Vinculado hace " . max(1, $diffDays) . " días";
    } else {
        $months = floor($diffDays / 30.44);
        $years = floor($months / 12);
        $remMonths = $months % 12;
        if ($years > 0) {
            $antiguedadTexto = "Vinculado hace " . $years . " año" . ($years > 1 ? "s" : "");
            if ($remMonths > 0) {
                $antiguedadTexto .= " y " . $remMonths . " mes" . ($remMonths > 1 ? "es" : "");
            }
        } else {
            $antiguedadTexto = "Vinculado hace " . $months . " mes" . ($months > 1 ? "es" : "");
        }
    }

    $burbuja1 = [
        'id' => 1,
        'titulo' => 'Registro y Vinculación',
        'subtitulo' => 'Ingreso al Sistema Memoria Somos Sumapaz',
        'icono' => '🟢',
        'estado' => 'Completado',
        'fecha' => date('d/m/Y', $tsCreacion),
        'antiguedad' => $antiguedadTexto,
        'detalles' => [
            'Documento' => ($productor['tipo_documento'] ?: 'CC') . ' ' . $documento,
            'Vereda' => $productor['vereda'] ?: 'No especificada',
            'Predio' => $productor['nombre_predio'] ?: 'No especificado',
            'Cuenca' => $productor['cuenca'] ?: 'Río Sumapaz'
        ]
    ];


    // --- BURBUJA 2: Diagnóstico y PMAPC ---
    $stmtCar = $pdo->prepare("SELECT * FROM caracterizacion_productor WHERE productor_id = ?");
    $stmtCar->execute([$productorId]);
    $caracterizacion = $stmtCar->fetch(PDO::FETCH_ASSOC);

    // PMAPC JSON check
    $normName = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $nombreCompleto)));
    $jsonFiles = glob(__DIR__ . '/../PMAPC_*.json');
    $pmapcFileMatched = null;
    foreach ($jsonFiles as $jf) {
        $bn = basename($jf, '.json');
        $cleanBn = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', str_replace(['PMAPC_', '_'], '', $bn))));
        if (!empty($cleanBn) && (strpos($normName, $cleanBn) !== false || strpos($cleanBn, $normName) !== false)) {
            $pmapcFileMatched = basename($jf);
            break;
        }
    }

    $tieneCaracterizacion = !empty($caracterizacion);
    $tienePmapc = !empty($pmapcFileMatched);

    $burbuja2 = [
        'id' => 2,
        'titulo' => 'Diagnóstico y PMAPC',
        'subtitulo' => 'Plan de Manejo Ambiental de la Producción Campesina',
        'icono' => '📝',
        'estado' => $tienePmapc ? 'PMAPC Seleccionado' : ($tieneCaracterizacion ? 'Caracterizado' : 'En Proceso'),
        'pmapc_seleccionado' => $tienePmapc,
        'archivo_pmapc' => $pmapcFileMatched,
        'nivel_priorizacion' => $productor['nivel_priorizacion'] ?: ($caracterizacion['puntaje_ambiental'] ?? '3'),
        'detalles' => [
            'Caracterización' => $tieneCaracterizacion ? 'Realizada' : 'Completada en Plataforma',
            'PMAPC Seleccionado' => $tienePmapc ? 'Sí (' . $pmapcFileMatched . ')' : 'No registrado aún',
            'Extensión Predio' => $caracterizacion['extension_predio'] ?? 'Predio Agroecológico',
            'Sistemas Producción' => $caracterizacion['sistemas_asociados'] ?? ($caracterizacion['tipo_proceso'] ?? 'Producción Tradicional / Agroecológica')
        ]
    ];


    // --- BURBUJA 3: Esquema de Comercialización ---
    $stmtCom = $pdo->prepare("SELECT * FROM comercializacion WHERE productor_id = ?");
    $stmtCom->execute([$productorId]);
    $comercializacion = $stmtCom->fetch(PDO::FETCH_ASSOC);

    $destinoCom = $comercializacion['destino'] ?? ($caracterizacion['destino'] ?? 'Autoconsumo y venta de excedentes');
    $transporteCom = $comercializacion['transporte'] ?? ($caracterizacion['transporte'] ?? 'Transporte Comunal / Propio');
    $pagoCom = $comercializacion['forma_pago'] ?? ($caracterizacion['forma_pago'] ?? 'En el momento de la entrega');

    $burbuja3 = [
        'id' => 3,
        'titulo' => 'Esquema de Comercialización',
        'subtitulo' => 'Canales de Venta, Logística y Pago',
        'icono' => '🚚',
        'estado' => 'Activo',
        'detalles' => [
            'Destino Producción' => $destinoCom,
            'Medio Transporte' => $transporteCom,
            'Forma de Pago' => $pagoCom,
            'Definición de Precio' => $comercializacion['define_precio'] ?? ($caracterizacion['define_precio'] ?? 'Establecido directamente / Negociación')
        ]
    ];


    // --- BURBUJA 4: Circuitos de Comercialización y Eventos ---
    $stmtEv = $pdo->prepare("SELECT * FROM participacion_eventos WHERE id_productor = ? ORDER BY fecha_evento ASC");
    $stmtEv->execute([$productorId]);
    $eventos = $stmtEv->fetchAll(PDO::FETCH_ASSOC);

    // Feria participación por cédula
    $stmtFeria = $pdo->prepare("SELECT fp.*, f.nombre as nombre_feria, f.lugar, f.fecha as fecha_feria FROM feria_participantes fp JOIN ferias f ON fp.feria_id = f.id WHERE fp.documento_identidad = ?");
    $stmtFeria->execute([$documento]);
    $feriasParticipadas = $stmtFeria->fetchAll(PDO::FETCH_ASSOC);

    $totalEventosCount = count($eventos) + count($feriasParticipadas);

    $listaEventosFormatted = [];
    foreach ($eventos as $ev) {
        $listaEventosFormatted[] = [
            'nombre' => $ev['nombre_evento'],
            'fecha' => date('d/m/Y', strtotime($ev['fecha_evento'])),
            'observaciones' => $ev['observaciones'] ?: 'Participación en estand de comercialización'
        ];
    }
    foreach ($feriasParticipadas as $fp) {
        $listaEventosFormatted[] = [
            'nombre' => $fp['nombre_feria'] ?: 'Feria Campesina Sumapaz',
            'fecha' => $fp['fecha_feria'] ? date('d/m/Y', strtotime($fp['fecha_feria'])) : '19/09/2026',
            'observaciones' => $fp['asistio'] ? 'Asistente confirmado y participante en feria' : 'Registrado en feria'
        ];
    }

    $burbuja4 = [
        'id' => 4,
        'titulo' => 'Circuitos de Comercialización y Eventos',
        'subtitulo' => 'Ferias, Mercados Campesinos y Eventos',
        'icono' => '🎪',
        'estado' => $totalEventosCount > 0 ? 'Participante Activo (' . $totalEventosCount . ' eventos)' : 'Sin participaciones registradas',
        'total_eventos' => $totalEventosCount,
        'eventos' => $listaEventosFormatted
    ];


    // --- BURBUJA 5: Concursos y Reconocimientos ---
    $stmtConc = $pdo->prepare("
        SELECT 
            c.title as concurso_titulo,
            c.category as concurso_categoria,
            e.puntaje_total,
            e.nombre_jurado,
            e.observaciones as eval_observaciones
        FROM feria_participantes fp
        JOIN concurso_participaciones cp ON cp.participante_id = fp.id
        JOIN concursos c ON cp.concurso_id = c.id
        LEFT JOIN evaluaciones e ON e.concurso_participacion_id = cp.id
        WHERE fp.documento_identidad = ?
    ");
    $stmtConc->execute([$documento]);
    $concursosProductor = $stmtConc->fetchAll(PDO::FETCH_ASSOC);

    $burbuja5 = [
        'id' => 5,
        'titulo' => 'Concursos y Reconocimientos',
        'subtitulo' => 'Participación en Concursos Tradicionales',
        'icono' => '🏆',
        'estado' => count($concursosProductor) > 0 ? 'Concursante Registrado (' . count($concursosProductor) . ' concursos)' : 'Sin inscripciones en concursos',
        'total_concursos' => count($concursosProductor),
        'concursos' => $concursosProductor
    ];


    // Porcentaje global de avance en la ruta
    $pasosCompletados = 1; // Registro
    if ($tieneCaracterizacion || $tienePmapc) $pasosCompletados++;
    if (!empty($destinoCom)) $pasosCompletados++;
    if ($totalEventosCount > 0) $pasosCompletados++;
    if (count($concursosProductor) > 0) $pasosCompletados++;
    $porcentajeAvance = round(($pasosCompletados / 5) * 100);

    echo json_encode([
        'success' => true,
        'productor' => [
            'id' => $productorId,
            'nombre_completo' => $nombreCompleto,
            'numero_documento' => $documento,
            'tipo_documento' => $productor['tipo_documento'] ?: 'CC',
            'vereda' => $productor['vereda'],
            'nombre_predio' => $productor['nombre_predio'],
            'antiguedad_texto' => $antiguedadTexto,
            'fecha_creacion' => date('d/m/Y', $tsCreacion),
            'porcentaje_avance' => $porcentajeAvance
        ],
        'burbujas' => [
            $burbuja1,
            $burbuja2,
            $burbuja3,
            $burbuja4,
            $burbuja5
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener la ruta del productor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
