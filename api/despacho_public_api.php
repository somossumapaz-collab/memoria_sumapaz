<?php
/**
 * API Pública para Consulta por Número de Contrato y Carga/Actualización de Reportes (Sin Login).
 * - Permite buscar cualquier contrato digitando su número (action=lookup_contract) y presentar la información actual de Mensaje 1 y Mensaje 2.
 * - El Mensaje 1 (Ficha contractual) solo se edita cuando haya lugar; el Mensaje 2 (Reporte periódico) se actualiza cada semana.
 * - Guarda cada modificación como una nueva versión inmutable en despacho_versiones_reporte y en despacho_bitacora para el tracking completo.
 * - Mantiene compatibilidad con enlaces por token si se envía el parámetro token.
 */
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/despacho_auth.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';
$rawBody = [];

if ($method === 'POST') {
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ct, 'application/json') !== false) {
        $rawBody = json_decode(file_get_contents('php://input'), true) ?: [];
    } else {
        $rawBody = $_POST;
    }
    if ($action === '' && !empty($rawBody['action'])) {
        $action = (string)$rawBody['action'];
    }
}

$rawToken = trim((string)($_GET['token'] ?? $rawBody['token'] ?? ''));

function validateUploadToken(PDO $pdo, string $rawToken): array {
    if ($rawToken === '' || strlen($rawToken) < 16) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error_code' => 'TOKEN_INVALIDO',
            'message' => 'Enlace de carga inválido o incompleto.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $tokenHash = hash('sha256', $rawToken);
    $stmt = $pdo->prepare("SELECT e.*,
            c.numero_contrato, c.codigo_normalizado, c.titulo_grupo, c.es_grupo,
            c.responsable_nombre AS contrato_responsable, c.tiene_mensaje1,
            c.estado_contractual, c.fecha_fin_vigente_texto,
            p.codigo AS periodo_codigo, p.nombre AS periodo_nombre, p.fecha_inicio AS periodo_inicio,
            p.fecha_fin AS periodo_fin, p.fecha_limite_entrega
        FROM despacho_enlaces_carga e
        INNER JOIN despacho_contratos c ON c.id = e.contrato_id
        LEFT JOIN despacho_periodos_reporte p ON p.id = e.periodo_id
        WHERE e.token_hash = ?
        LIMIT 1");
    $stmt->execute([$tokenHash]);
    $enlace = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$enlace) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error_code' => 'TOKEN_NO_ENCONTRADO',
            'message' => 'El enlace de carga no existe o no es válido.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ((int)$enlace['revocado'] === 1) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error_code' => 'TOKEN_REVOCADO',
            'message' => 'Este enlace de carga ha sido revocado por Despacho.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (strtotime($enlace['expira_en']) < time()) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error_code' => 'TOKEN_VENCIDO',
            'message' => 'Este enlace de carga venció el ' . $enlace['expira_en'] . '.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ((int)$enlace['usos_realizados'] >= (int)$enlace['max_usos']) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error_code' => 'TOKEN_LIMITE_USOS',
            'message' => 'Este enlace alcanzó el número máximo de cargas permitidas.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    return $enlace;
}

/**
 * Analiza texto libre de Mensaje 1 y Mensaje 2 para proponer campos estructurados.
 */
function parseSubmittedMessages(?string $m1, ?string $m2): array {
    $parsed = [
        'mensaje1' => [
            'objeto' => null,
            'contratista' => null,
            'interventoria' => null,
            'duracion_texto' => null,
            'valor_inicial' => null,
            'valor_total' => null,
            'fecha_inicio_texto' => null,
            'fecha_fin_inicial_texto' => null,
            'apoyo_supervision' => null,
            'modificaciones_detectadas' => [],
        ],
        'mensaje2' => [
            'fecha_reporte_texto' => date('Y-m-d'),
            'ejecucion_fisica_pct' => null,
            'ejecucion_fisica_texto' => null,
            'ejecucion_financiera_pct' => null,
            'ejecucion_financiera_valor' => null,
            'ejecucion_financiera_texto' => null,
            'ejecucion_financiera_obra_pct' => null,
            'ejecucion_financiera_inter_pct' => null,
            'estado_reportado_texto' => null,
            'fecha_fin_reportada_texto' => null,
            'observaciones' => null,
        ]
    ];

    if ($m1 !== null && trim($m1) !== '') {
        $lines = preg_split('/\r\n|\r|\n/', $m1);
        foreach ($lines as $line) {
            $clean = trim(str_replace('*', '', $line));
            if ($clean === '') continue;
            if ($parsed['mensaje1']['objeto'] === null && preg_match('/^(?:Objeto(?: del contrato)?|Descripci[oó]n)\s*:\s*(.+)$/iu', $clean, $m)) {
                $parsed['mensaje1']['objeto'] = trim($m[1], " \"“”");
            }
            if ($parsed['mensaje1']['contratista'] === null && preg_match('/^(?:Contratista|Nombre del contratista)\s*:\s*(.+)$/iu', $clean, $m)) {
                $parsed['mensaje1']['contratista'] = trim($m[1], " \"");
            }
            if ($parsed['mensaje1']['interventoria'] === null && preg_match('/^(?:Interventor[ií]a)\s*:\s*(.+)$/iu', $clean, $m)) {
                $parsed['mensaje1']['interventoria'] = trim($m[1], " \"");
            }
            if ($parsed['mensaje1']['duracion_texto'] === null && preg_match('/^(?:Duraci[oó]n(?: del contrato)?|Plazo)\s*:\s*(.+)$/iu', $clean, $m)) {
                $parsed['mensaje1']['duracion_texto'] = trim($m[1]);
            }
            if (preg_match('/^(?:Valor(?: inicial| contrato| del contrato)?|Monto)\s*:\s*(.+)$/iu', $clean, $m)) {
                if (preg_match('/(\d{1,3}(?:[\.\',]\d{3})+)/', $m[1], $mv)) {
                    $parsed['mensaje1']['valor_inicial'] = (float)preg_replace('/[^\d]/', '', $mv[1]);
                }
            }
            if (preg_match('/^(?:Valor total(?: contrato)?|Total)\s*:\s*(.+)$/iu', $clean, $m)) {
                if (preg_match('/(\d{1,3}(?:[\.\',]\d{3})+)/', $m[1], $mv)) {
                    $parsed['mensaje1']['valor_total'] = (float)preg_replace('/[^\d]/', '', $mv[1]);
                }
            }
            if ($parsed['mensaje1']['fecha_inicio_texto'] === null && preg_match('/^(?:Fecha de inicio(?: del contrato)?|Inicio)\s*:\s*(.+)$/iu', $clean, $m)) {
                $parsed['mensaje1']['fecha_inicio_texto'] = trim($m[1]);
            }
            if ($parsed['mensaje1']['fecha_fin_inicial_texto'] === null && preg_match('/^(?:Fecha de terminaci[oó]n(?: inicial)?|Fin)\s*:\s*(.+)$/iu', $clean, $m)) {
                $parsed['mensaje1']['fecha_fin_inicial_texto'] = trim($m[1]);
            }
            if ($parsed['mensaje1']['apoyo_supervision'] === null && preg_match('/^(?:Apoyo a la supervisi[oó]n)\s*:\s*(.+)$/iu', $clean, $m)) {
                $parsed['mensaje1']['apoyo_supervision'] = trim($m[1]);
            }
            if (preg_match('/^(?:Pr[oó]rroga|Adici[oó]n|Suspensi[oó]n|Ampliaci[oó]n).*:\s*(.+)$/iu', $clean, $m)) {
                $valMod = trim($m[1]);
                if ($valMod !== '' && strtoupper($valMod) !== 'N/A' && strtoupper($valMod) !== 'NO') {
                    $parsed['mensaje1']['modificaciones_detectadas'][] = $clean;
                }
            }
        }
    }

    if ($m2 !== null && trim($m2) !== '') {
        $lines = preg_split('/\r\n|\r|\n/', $m2);
        foreach ($lines as $line) {
            $clean = trim(preg_replace('/^[^\p{L}\p{N}]+/u', '', str_replace('*', '', $line)));
            if ($clean === '') continue;
            if (preg_match('/^Fecha\s*reporte\s*:\s*(.+)$/iu', $clean, $m)) {
                $parsed['mensaje2']['fecha_reporte_texto'] = trim($m[1]);
            }
            if (preg_match('/^Ejecuci[oó]n\s*f[ií]sica\s*:\s*(.+)$/iu', $clean, $m)) {
                $txtF = trim($m[1]);
                $parsed['mensaje2']['ejecucion_fisica_texto'] = $txtF;
                if (preg_match('/(\d+(?:[\.,]\d+)?)\s*%/u', $txtF, $mp)) {
                    $parsed['mensaje2']['ejecucion_fisica_pct'] = (float)str_replace(',', '.', $mp[1]);
                }
            }
            if (preg_match('/^Ejecuci[oó]n\s*financiera(?:\s*obra|\s*interventor[ií]a)?\s*:\s*(.+)$/iu', $clean, $m)) {
                $txtFin = trim($m[1]);
                if (stripos($clean, 'obra') !== false && stripos($clean, 'interventor') === false) {
                    if (preg_match('/(\d+(?:[\.,]\d+)?)\s*%/u', $txtFin, $mp)) {
                        $parsed['mensaje2']['ejecucion_financiera_obra_pct'] = (float)str_replace(',', '.', $mp[1]);
                    }
                } elseif (stripos($clean, 'interventor') !== false && stripos($clean, 'obra') === false) {
                    if (preg_match('/(\d+(?:[\.,]\d+)?)\s*%/u', $txtFin, $mp)) {
                        $parsed['mensaje2']['ejecucion_financiera_inter_pct'] = (float)str_replace(',', '.', $mp[1]);
                    }
                } else {
                    $parsed['mensaje2']['ejecucion_financiera_texto'] = $txtFin;
                    if (preg_match('/(\d+(?:[\.,]\d+)?)\s*%/u', $txtFin, $mp)) {
                        $parsed['mensaje2']['ejecucion_financiera_pct'] = (float)str_replace(',', '.', $mp[1]);
                    }
                    if (preg_match('/(\d{1,3}(?:[\.\',]\d{3})+)/', $txtFin, $mv)) {
                        $parsed['mensaje2']['ejecucion_financiera_valor'] = (float)preg_replace('/[^\d]/', '', $mv[1]);
                    }
                }
            }
            if (preg_match('/^Estado\s*:\s*(.+)$/iu', $clean, $m)) {
                $parsed['mensaje2']['estado_reportado_texto'] = trim($m[1]);
            }
            if (preg_match('/^Fecha\s*(?:de\s*)?finalizaci[oó]n(?:\s*vigente|\s*pr[oó]rroga|\s*inicial)?\s*:\s*(.+)$/iu', $clean, $m)) {
                $parsed['mensaje2']['fecha_fin_reportada_texto'] = trim($m[1]);
            }
        }
    }

    return $parsed;
}

/**
 * Construye el paquete completo de un contrato (Mensaje 1 actual, Mensaje 2 actual, proyectos, periodo activo y versiones para tracking).
 */
function buildContractLookupPayload(PDO $pdo, array $contratoRow): array {
    $contratoId = (int)$contratoRow['id'];

    // Periodo activo
    $periodoActivo = $pdo->query("SELECT id, codigo, nombre, fecha_inicio, fecha_fin, fecha_limite_entrega
        FROM despacho_periodos_reporte
        WHERE es_periodo_activo = 1
        ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: null;

    // Proyectos vinculados
    $stmtP = $pdo->prepare("SELECT p.id, p.codigo, p.nombre
        FROM despacho_contrato_proyectos cp
        INNER JOIN despacho_proyectos p ON p.id = cp.proyecto_id
        WHERE cp.contrato_id = ?
        ORDER BY p.codigo ASC");
    $stmtP->execute([$contratoId]);
    $proyectos = $stmtP->fetchAll(PDO::FETCH_ASSOC);

    $proyectoPrincipal = !empty($proyectos) ? $proyectos[0] : [
        'id' => 0,
        'codigo' => 'SIN_PROYECTO',
        'nombre' => 'Contratos pendientes de asociar a Número de Proyecto'
    ];

    // Contratos hermanos que se desprenden del mismo proyecto
    if (!empty($proyectos)) {
        $stmtSib = $pdo->prepare("SELECT c.id, c.numero_contrato, c.titulo_grupo, c.responsable_nombre, c.contratista,
                c.estado_contractual, c.tiene_mensaje1, c.fecha_ultimo_reporte_texto, c.ejecucion_fisica_pct, c.ejecucion_financiera_pct
            FROM despacho_contrato_proyectos cp
            INNER JOIN despacho_contratos c ON c.id = cp.contrato_id
            WHERE cp.proyecto_id = ?
            ORDER BY COALESCE(c.fila_excel, 9999) ASC, c.id ASC");
        $stmtSib->execute([(int)$proyectoPrincipal['id']]);
        $contratosDelProyecto = $stmtSib->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $contratosDelProyecto = $pdo->query("SELECT c.id, c.numero_contrato, c.titulo_grupo, c.responsable_nombre, c.contratista,
                c.estado_contractual, c.tiene_mensaje1, c.fecha_ultimo_reporte_texto, c.ejecucion_fisica_pct, c.ejecucion_financiera_pct
            FROM despacho_contratos c
            WHERE NOT EXISTS (SELECT 1 FROM despacho_contrato_proyectos cp WHERE cp.contrato_id = c.id)
            ORDER BY COALESCE(c.fila_excel, 9999) ASC, c.id ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    // Componentes (Obra / Interventoría)
    $stmtComp = $pdo->prepare("SELECT id, tipo_componente, numero_componente, contratista, valor_total, avance_fisico_pct, avance_financiero_pct
        FROM despacho_componentes_contrato
        WHERE contrato_id = ?
        ORDER BY id ASC");
    $stmtComp->execute([$contratoId]);
    $componentes = $stmtComp->fetchAll(PDO::FETCH_ASSOC);

    // Modificaciones / Ajustes del contrato (Adiciones, Prórrogas, Suspensiones, Modificaciones)
    $stmtMod = $pdo->prepare("SELECT *
        FROM despacho_modificaciones_contrato
        WHERE contrato_id = ?
        ORDER BY id DESC");
    $stmtMod->execute([$contratoId]);
    $modificaciones = $stmtMod->fetchAll(PDO::FETCH_ASSOC);

    // Historial completo de versiones para tracking y para obtener el último Mensaje 1 y Mensaje 2
    $stmtVer = $pdo->prepare("SELECT v.*, r.radicado, r.origen_carga, p.codigo AS periodo_codigo, p.nombre AS periodo_nombre
        FROM despacho_versiones_reporte v
        INNER JOIN despacho_reportes r ON r.id = v.reporte_id
        LEFT JOIN despacho_periodos_reporte p ON p.id = v.periodo_id
        WHERE v.contrato_id = ?
        ORDER BY v.id DESC");
    $stmtVer->execute([$contratoId]);
    $versiones = $stmtVer->fetchAll(PDO::FETCH_ASSOC);

    $latestM1Texto = null;
    $latestM2Texto = null;
    $latestObraPct = null;
    $latestInterPct = null;
    $latestObs = null;

    foreach ($versiones as &$ver) {
        $ver['datos_estructurados'] = $ver['datos_estructurados_json'] ? json_decode($ver['datos_estructurados_json'], true) : null;
        if ($latestM1Texto === null && !empty($ver['mensaje1_texto_original'])) {
            $latestM1Texto = $ver['mensaje1_texto_original'];
        }
        if ($latestM2Texto === null && !empty($ver['mensaje2_texto_original'])) {
            $latestM2Texto = $ver['mensaje2_texto_original'];
        }
        if ($latestObraPct === null && $ver['ejecucion_financiera_obra_pct'] !== null) {
            $latestObraPct = (float)$ver['ejecucion_financiera_obra_pct'];
        }
        if ($latestInterPct === null && $ver['ejecucion_financiera_inter_pct'] !== null) {
            $latestInterPct = (float)$ver['ejecucion_financiera_inter_pct'];
        }
        if ($latestObs === null && !empty($ver['observaciones_reporte'])) {
            $latestObs = $ver['observaciones_reporte'];
        }
    }
    unset($ver);

    // Reporte en el periodo activo (si existe)
    $reportePeriodoActual = null;
    if ($periodoActivo) {
        $stmtR = $pdo->prepare("SELECT radicado, tipo_mensaje, version_actual, estado_entrega, estado_revision, comentario_revision, fecha_revision, updated_at
            FROM despacho_reportes
            WHERE contrato_id = ? AND periodo_id = ?
            ORDER BY id DESC LIMIT 1");
        $stmtR->execute([$contratoId, (int)$periodoActivo['id']]);
        $reportePeriodoActual = $stmtR->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    return [
        'contrato' => [
            'id' => $contratoId,
            'numero_contrato' => $contratoRow['numero_contrato'],
            'codigo_normalizado' => $contratoRow['codigo_normalizado'],
            'titulo_grupo' => $contratoRow['titulo_grupo'],
            'es_grupo' => (int)$contratoRow['es_grupo'] === 1,
            'area_nombre' => $contratoRow['area_nombre'] ?? null,
            'responsable_nombre' => $contratoRow['responsable_nombre'],
            'objeto' => $contratoRow['objeto'],
            'contratista' => $contratoRow['contratista'],
            'interventoria_nombre' => $contratoRow['interventoria_nombre'],
            'duracion_texto' => $contratoRow['duracion_texto'],
            'valor_inicial' => $contratoRow['valor_inicial'] !== null ? (float)$contratoRow['valor_inicial'] : null,
            'valor_total' => $contratoRow['valor_total'] !== null ? (float)$contratoRow['valor_total'] : null,
            'fecha_inicio_texto' => $contratoRow['fecha_inicio_texto'],
            'fecha_inicio_iso' => $contratoRow['fecha_inicio_iso'],
            'fecha_fin_inicial_texto' => $contratoRow['fecha_fin_inicial_texto'],
            'fecha_fin_inicial_iso' => $contratoRow['fecha_fin_inicial_iso'],
            'fecha_fin_vigente_texto' => $contratoRow['fecha_fin_vigente_texto'],
            'fecha_fin_vigente_iso' => $contratoRow['fecha_fin_vigente_iso'],
            'estado_contractual' => $contratoRow['estado_contractual'],
            'estado_reportado_texto' => $contratoRow['estado_reportado_texto'],
            'tiene_mensaje1_registrado' => (int)$contratoRow['tiene_mensaje1'] === 1,
            'requiere_mensaje1_obligatorio' => (int)$contratoRow['tiene_mensaje1'] === 0,
            'fecha_ultimo_reporte_texto' => $contratoRow['fecha_ultimo_reporte_texto'],
            'fecha_ultimo_reporte_iso' => $contratoRow['fecha_ultimo_reporte_iso'],
            'ejecucion_fisica_pct' => $contratoRow['ejecucion_fisica_pct'] !== null ? (float)$contratoRow['ejecucion_fisica_pct'] : null,
            'ejecucion_fisica_texto' => $contratoRow['ejecucion_fisica_texto'],
            'ejecucion_financiera_pct' => $contratoRow['ejecucion_financiera_pct'] !== null ? (float)$contratoRow['ejecucion_financiera_pct'] : null,
            'ejecucion_financiera_valor' => $contratoRow['ejecucion_financiera_valor'] !== null ? (float)$contratoRow['ejecucion_financiera_valor'] : null,
            'ejecucion_financiera_texto' => $contratoRow['ejecucion_financiera_texto'],
            'ejecucion_financiera_obra_pct' => $latestObraPct,
            'ejecucion_financiera_inter_pct' => $latestInterPct,
            'observaciones_ultimo_reporte' => $latestObs,
            'mensaje1_texto_actual' => $latestM1Texto,
            'mensaje2_texto_actual' => $latestM2Texto,
            'proyectos' => $proyectos,
            'componentes' => $componentes,
            'modificaciones' => $modificaciones,
        ],
        'proyecto_principal' => $proyectoPrincipal,
        'contratos_del_proyecto' => $contratosDelProyecto,
        'periodo' => $periodoActivo,
        'reporte_periodo_actual' => $reportePeriodoActual,
        'versiones_tracking' => $versiones,
    ];
}

try {
    // ========================================================================
    // 0. LISTADO DE PROYECTOS DE INVERSIÓN Y SUS CONTRATOS ASOCIADOS (SIN LOGIN)
    // ========================================================================
    if ($method === 'GET' && $action === 'list_projects') {
        // Asegurar que todos los proyectos oficiales de Sumapaz (assets/proyectos_metas_sumapaz.json) estén en despacho_proyectos
        $jsonCatalogPath = dirname(__DIR__) . '/assets/proyectos_metas_sumapaz.json';
        if (file_exists($jsonCatalogPath)) {
            $jsonCatalog = json_decode(file_get_contents($jsonCatalogPath), true);
            if (is_array($jsonCatalog)) {
                $stmtSyncP = $pdo->prepare("INSERT IGNORE INTO despacho_proyectos (codigo, nombre, activo) VALUES (?, ?, 1)");
                $seenCodigos = [];
                foreach ($jsonCatalog as $item) {
                    $codP = trim((string)($item['codigo'] ?? ''));
                    $nomP = trim((string)($item['nombre'] ?? ''));
                    if ($codP !== '' && !isset($seenCodigos[$codP])) {
                        $seenCodigos[$codP] = true;
                        $stmtSyncP->execute([$codP, $nomP ?: ('Proyecto ' . $codP)]);
                    }
                }
            }
        }

        $proyRows = $pdo->query("SELECT p.id, p.codigo, p.nombre
            FROM despacho_proyectos p
            WHERE p.activo = 1
            ORDER BY p.codigo ASC")->fetchAll(PDO::FETCH_ASSOC);

        $cpRows = $pdo->query("SELECT cp.proyecto_id, c.id, c.numero_contrato, c.titulo_grupo, c.responsable_nombre,
                c.contratista, c.estado_contractual, c.tiene_mensaje1, c.fecha_ultimo_reporte_texto,
                c.ejecucion_fisica_pct, c.ejecucion_financiera_pct
            FROM despacho_contrato_proyectos cp
            INNER JOIN despacho_contratos c ON c.id = cp.contrato_id
            ORDER BY COALESCE(c.fila_excel, 9999) ASC, c.id ASC")->fetchAll(PDO::FETCH_ASSOC);

        $contratosMap = [];
        foreach ($cpRows as $r) {
            $pid = (int)$r['proyecto_id'];
            $contratosMap[$pid][] = [
                'id' => (int)$r['id'],
                'numero_contrato' => $r['numero_contrato'],
                'titulo_grupo' => $r['titulo_grupo'],
                'responsable_nombre' => $r['responsable_nombre'],
                'contratista' => $r['contratista'],
                'estado_contractual' => $r['estado_contractual'],
                'tiene_mensaje1' => (int)$r['tiene_mensaje1'] === 1,
                'fecha_ultimo_reporte_texto' => $r['fecha_ultimo_reporte_texto'],
                'ejecucion_fisica_pct' => $r['ejecucion_fisica_pct'] !== null ? (float)$r['ejecucion_fisica_pct'] : null,
                'ejecucion_financiera_pct' => $r['ejecucion_financiera_pct'] !== null ? (float)$r['ejecucion_financiera_pct'] : null,
            ];
        }

        $proyectosOut = [];
        foreach ($proyRows as $p) {
            $pid = (int)$p['id'];
            $listaC = $contratosMap[$pid] ?? [];
            $proyectosOut[] = [
                'id' => $pid,
                'codigo' => $p['codigo'],
                'nombre' => $p['nombre'],
                'total_contratos' => count($listaC),
                'contratos' => $listaC,
            ];
        }

        // Contratos aún sin número de proyecto asociado
        $sinProyRows = $pdo->query("SELECT c.id, c.numero_contrato, c.titulo_grupo, c.responsable_nombre,
                c.contratista, c.estado_contractual, c.tiene_mensaje1, c.fecha_ultimo_reporte_texto,
                c.ejecucion_fisica_pct, c.ejecucion_financiera_pct
            FROM despacho_contratos c
            WHERE NOT EXISTS (SELECT 1 FROM despacho_contrato_proyectos cp WHERE cp.contrato_id = c.id)
            ORDER BY COALESCE(c.fila_excel, 9999) ASC, c.id ASC")->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($sinProyRows)) {
            $listaSin = array_map(function(array $r) {
                return [
                    'id' => (int)$r['id'],
                    'numero_contrato' => $r['numero_contrato'],
                    'titulo_grupo' => $r['titulo_grupo'],
                    'responsable_nombre' => $r['responsable_nombre'],
                    'contratista' => $r['contratista'],
                    'estado_contractual' => $r['estado_contractual'],
                    'tiene_mensaje1' => (int)$r['tiene_mensaje1'] === 1,
                    'fecha_ultimo_reporte_texto' => $r['fecha_ultimo_reporte_texto'],
                    'ejecucion_fisica_pct' => $r['ejecucion_fisica_pct'] !== null ? (float)$r['ejecucion_fisica_pct'] : null,
                    'ejecucion_financiera_pct' => $r['ejecucion_financiera_pct'] !== null ? (float)$r['ejecucion_financiera_pct'] : null,
                ];
            }, $sinProyRows);

            $proyectosOut[] = [
                'id' => 0,
                'codigo' => 'SIN_PROYECTO',
                'nombre' => 'Contratos pendientes de asociar a Número de Proyecto',
                'total_contratos' => count($listaSin),
                'contratos' => $listaSin,
            ];
        }

        $areasOut = $pdo->query("SELECT id, codigo, nombre FROM despacho_areas WHERE activo = 1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'proyectos' => $proyectosOut,
            'areas' => $areasOut
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ========================================================================
    // 1. CONSULTA DIRECTA POR NÚMERO DE PROYECTO O CONTRATO (SIN LOGIN NI TOKEN)
    // ========================================================================
    if ($method === 'GET' && ($action === 'lookup_contract' || ($rawToken === '' && (isset($_GET['query']) || isset($_GET['contrato_id']))))) {
        $contratoIdParam = isset($_GET['contrato_id']) ? (int)$_GET['contrato_id'] : 0;
        $queryParam = trim((string)($_GET['query'] ?? ''));

        if ($contratoIdParam > 0) {
            $stmt = $pdo->prepare("SELECT c.*, a.nombre AS area_nombre
                FROM despacho_contratos c
                LEFT JOIN despacho_areas a ON a.id = c.area_id
                WHERE c.id = ? LIMIT 1");
            $stmt->execute([$contratoIdParam]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'No se encontró el contrato solicitado.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $payload = buildContractLookupPayload($pdo, $row);
            $payload['success'] = true;
            echo json_encode($payload, JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($queryParam === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Por favor digite el número del contrato.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Buscar coincidencias exactas o parciales por número de contrato, código normalizado o título
        $like = '%' . $queryParam . '%';
        $prefix = $queryParam . '-%';
        $stmt = $pdo->prepare("SELECT c.*, a.nombre AS area_nombre
            FROM despacho_contratos c
            LEFT JOIN despacho_areas a ON a.id = c.area_id
            WHERE LOWER(c.numero_contrato) = LOWER(?)
               OR LOWER(c.codigo_normalizado) = LOWER(?)
               OR LOWER(c.numero_contrato) LIKE LOWER(?)
               OR LOWER(c.numero_contrato) LIKE LOWER(?)
               OR LOWER(c.titulo_grupo) LIKE LOWER(?)
            ORDER BY
                CASE
                    WHEN LOWER(c.numero_contrato) = LOWER(?) THEN 0
                    WHEN LOWER(c.codigo_normalizado) = LOWER(?) THEN 1
                    WHEN LOWER(c.numero_contrato) LIKE LOWER(?) THEN 2
                    ELSE 3
                END ASC,
                COALESCE(c.fila_excel, 9999) ASC");
        $stmt->execute([
            $queryParam, $queryParam, $prefix, $like, $like,
            $queryParam, $queryParam, $prefix
        ]);
        $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($matches) === 0) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'No se encontró ningún contrato con el número "' . $queryParam . '". Verifique el número e intente nuevamente.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Si hay coincidencia exacta única en la primera posición o solo 1 resultado, cargar directamente ese contrato
        $first = $matches[0];
        $isExactFirst = (strcasecmp($first['numero_contrato'], $queryParam) === 0
            || strcasecmp($first['codigo_normalizado'], $queryParam) === 0
            || stripos($first['numero_contrato'], $queryParam . '-') === 0);

        if (count($matches) === 1 || ($isExactFirst && count($matches) > 1 && stripos($matches[1]['numero_contrato'], $queryParam . '-') !== 0 && strcasecmp($matches[1]['numero_contrato'], $queryParam) !== 0)) {
            $payload = buildContractLookupPayload($pdo, $first);
            $payload['success'] = true;
            echo json_encode($payload, JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Si hay múltiples contratos que coinciden con el texto buscado, devolver la lista para que el usuario elija
        $listaCoincidencias = array_map(function(array $r) {
            return [
                'id' => (int)$r['id'],
                'numero_contrato' => $r['numero_contrato'],
                'titulo_grupo' => $r['titulo_grupo'],
                'responsable_nombre' => $r['responsable_nombre'],
                'contratista' => $r['contratista'],
                'estado_contractual' => $r['estado_contractual'],
                'tiene_mensaje1' => (int)$r['tiene_mensaje1'] === 1,
            ];
        }, $matches);

        echo json_encode([
            'success' => true,
            'multiple_matches' => true,
            'coincidencias' => $listaCoincidencias
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ========================================================================
    // 2. PREVISUALIZACIÓN DE TEXTO (CON O SIN TOKEN)
    // ========================================================================
    if ($method === 'POST' && $action === 'preview') {
        $m1Texto = isset($rawBody['mensaje1_texto']) ? trim((string)$rawBody['mensaje1_texto']) : '';
        $m2Texto = isset($rawBody['mensaje2_texto']) ? trim((string)$rawBody['mensaje2_texto']) : '';
        $parsed = parseSubmittedMessages($m1Texto !== '' ? $m1Texto : null, $m2Texto !== '' ? $m2Texto : null);
        echo json_encode([
            'success' => true,
            'preview' => $parsed,
            'nota_confirmacion' => 'Verifique los datos interpretados antes de guardar la actualización.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ========================================================================
    // 3. CONSULTA GET POR TOKEN (COMPATIBILIDAD CON ENLACES GENERADOS)
    // ========================================================================
    if ($method === 'GET') {
        $enlace = validateUploadToken($pdo, $rawToken);
        $contratoId = (int)$enlace['contrato_id'];
        $periodoId = $enlace['periodo_id'] !== null ? (int)$enlace['periodo_id'] : null;

        $stmtC = $pdo->prepare("SELECT c.*, a.nombre AS area_nombre FROM despacho_contratos c LEFT JOIN despacho_areas a ON a.id = c.area_id WHERE c.id = ? LIMIT 1");
        $stmtC->execute([$contratoId]);
        $contratoRow = $stmtC->fetch(PDO::FETCH_ASSOC);

        $payload = buildContractLookupPayload($pdo, $contratoRow);
        $payload['success'] = true;
        $payload['enlace'] = [
            'token_prefijo' => $enlace['token_prefijo'],
            'alcance' => $enlace['alcance'],
            'responsable_nombre' => $enlace['responsable_nombre'] ?: $enlace['contrato_responsable'],
            'usos_realizados' => (int)$enlace['usos_realizados'],
            'max_usos' => (int)$enlace['max_usos'],
            'expira_en' => $enlace['expira_en'],
        ];
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ========================================================================
    // 4. GUARDADO DE ACTUALIZACIÓN (DIRECTO POR CONTRATO O POR TOKEN)
    // ========================================================================
    if ($method === 'POST' && ($action === 'submit' || $action === '')) {
        $enlace = null;
        $contratoId = 0;
        $periodoId = null;
        $obligacionId = null;
        $fechaLimiteEntrega = null;
        $origenCarga = 'FORMULARIO_CONTRATO';

        if ($rawToken !== '') {
            $enlace = validateUploadToken($pdo, $rawToken);
            $contratoId = (int)$enlace['contrato_id'];
            $periodoId = $enlace['periodo_id'] !== null ? (int)$enlace['periodo_id'] : null;
            $obligacionId = $enlace['obligacion_id'] !== null ? (int)$enlace['obligacion_id'] : null;
            $fechaLimiteEntrega = $enlace['fecha_limite_entrega'] ?? null;
            $origenCarga = 'ENLACE_TOKEN';
        } else {
            $contratoId = isset($rawBody['contrato_id']) ? (int)$rawBody['contrato_id'] : 0;
            $esNuevoContrato = false;

            if ($contratoId <= 0 && !empty($rawBody['numero_contrato'])) {
                $stmtFind = $pdo->prepare("SELECT id FROM despacho_contratos WHERE LOWER(numero_contrato) = LOWER(?) OR LOWER(codigo_normalizado) = LOWER(?) LIMIT 1");
                $numC = trim((string)$rawBody['numero_contrato']);
                $stmtFind->execute([$numC, $numC]);
                $contratoId = (int)($stmtFind->fetchColumn() ?: 0);
            }

            if ($contratoId <= 0 && !empty($rawBody['nuevo_numero_contrato'])) {
                $nuevoNum = trim((string)$rawBody['nuevo_numero_contrato']);
                $stmtFindNew = $pdo->prepare("SELECT id FROM despacho_contratos WHERE LOWER(numero_contrato) = LOWER(?) OR LOWER(codigo_normalizado) = LOWER(?) LIMIT 1");
                $stmtFindNew->execute([$nuevoNum, $nuevoNum]);
                $contratoId = (int)($stmtFindNew->fetchColumn() ?: 0);

                if ($contratoId <= 0) {
                    $nuevoTitulo = trim((string)($rawBody['nuevo_titulo_grupo'] ?? '')) ?: ('Contrato ' . $nuevoNum);
                    $nuevoAreaId = !empty($rawBody['nuevo_area_id']) ? (int)$rawBody['nuevo_area_id'] : null;
                    $nuevoResp = trim((string)($rawBody['responsable_reportante'] ?? '')) ?: null;
                    $codNorm = strtoupper(preg_replace('/[^a-zA-Z0-9\-]/', '-', $nuevoNum)) . '-' . substr(uniqid(), -4);

                    $stmtInsCon = $pdo->prepare("INSERT INTO despacho_contratos (
                        codigo_normalizado, numero_contrato, titulo_grupo, es_grupo, area_id,
                        responsable_nombre, categoria_origen, nivel_alerta, tipo_alerta,
                        alerta_consolidada, estado_contractual, tiene_mensaje1, estado_entrega, estado_revision
                    ) VALUES (?, ?, ?, 0, ?, ?, 'SEGUIMIENTO', 'SEGUIMIENTO', 'En seguimiento', 'Registrado desde formulario por proyecto', 'EN_EJECUCION', 1, 'RECIBIDO', 'PENDIENTE_REVISION')");
                    $stmtInsCon->execute([
                        $codNorm,
                        $nuevoNum,
                        $nuevoTitulo,
                        $nuevoAreaId,
                        $nuevoResp
                    ]);
                    $contratoId = (int)$pdo->lastInsertId();
                    $esNuevoContrato = true;

                    // Asociar inmediatamente al Número de Proyecto seleccionado
                    $proyCodNew = trim((string)($rawBody['m1_numero_proyecto'] ?? $rawBody['nuevo_numero_proyecto'] ?? ''));
                    if ($proyCodNew !== '' && $proyCodNew !== 'SIN_PROYECTO') {
                        $stmtFindP = $pdo->prepare("SELECT id FROM despacho_proyectos WHERE codigo = ? LIMIT 1");
                        $stmtFindP->execute([$proyCodNew]);
                        $proyIdNew = (int)($stmtFindP->fetchColumn() ?: 0);
                        if ($proyIdNew <= 0) {
                            $stmtInsP = $pdo->prepare("INSERT INTO despacho_proyectos (codigo, nombre, activo) VALUES (?, ?, 1)");
                            $stmtInsP->execute([$proyCodNew, 'Proyecto ' . $proyCodNew]);
                            $proyIdNew = (int)$pdo->lastInsertId();
                        }
                        if ($proyIdNew > 0) {
                            $pdo->prepare("INSERT IGNORE INTO despacho_contrato_proyectos (contrato_id, proyecto_id) VALUES (?, ?)")
                                ->execute([$contratoId, $proyIdNew]);
                        }
                    }
                }
            }

            if ($contratoId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Debe seleccionar un contrato existente o digitar el Número del Nuevo Contrato antes de guardar.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            // Obtener el periodo activo actual
            $perRow = $pdo->query("SELECT id, fecha_limite_entrega FROM despacho_periodos_reporte WHERE es_periodo_activo = 1 ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if ($perRow) {
                $periodoId = (int)$perRow['id'];
                $fechaLimiteEntrega = $perRow['fecha_limite_entrega'];
                $stmtOb = $pdo->prepare("SELECT id FROM despacho_obligaciones_reporte WHERE periodo_id = ? AND contrato_id = ? LIMIT 1");
                $stmtOb->execute([$periodoId, $contratoId]);
                $obligacionId = (int)($stmtOb->fetchColumn() ?: 0) ?: null;
                if ($obligacionId === null) {
                    $stmtInsOb = $pdo->prepare("INSERT INTO despacho_obligaciones_reporte (periodo_id, contrato_id, requiere_mensaje1, requiere_mensaje2, fecha_esperada, estado_entrega, estado_revision) VALUES (?, ?, 1, 1, ?, 'RECIBIDO', 'PENDIENTE_REVISION')");
                    $stmtInsOb->execute([$periodoId, $contratoId, $fechaLimiteEntrega]);
                    $obligacionId = (int)$pdo->lastInsertId();
                }
            }
        }

        // Cargar estado actual del contrato antes de la modificación (para el tracking de cambios)
        $stmtCon = $pdo->prepare("SELECT * FROM despacho_contratos WHERE id = ? LIMIT 1");
        $stmtCon->execute([$contratoId]);
        $contratoActual = $stmtCon->fetch(PDO::FETCH_ASSOC);
        if (!$contratoActual) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'El contrato indicado no existe.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Obtener el último texto de Mensaje 1 y Mensaje 2 guardado previamente
        $stmtPrevVer = $pdo->prepare("SELECT mensaje1_texto_original, mensaje2_texto_original
            FROM despacho_versiones_reporte
            WHERE contrato_id = ?
            ORDER BY id DESC");
        $stmtPrevVer->execute([$contratoId]);
        $prevM1Texto = null;
        $prevM2Texto = null;
        foreach ($stmtPrevVer->fetchAll(PDO::FETCH_ASSOC) as $pv) {
            if ($prevM1Texto === null && !empty($pv['mensaje1_texto_original'])) {
                $prevM1Texto = $pv['mensaje1_texto_original'];
            }
            if ($prevM2Texto === null && !empty($pv['mensaje2_texto_original'])) {
                $prevM2Texto = $pv['mensaje2_texto_original'];
            }
        }

        // Si viene de enlace con token, verificar check de confirmación
        if ($enlace !== null && empty($rawBody['confirmado'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Debe confirmar explícitamente los datos interpretados antes de radicar el reporte.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $m1Texto = isset($rawBody['mensaje1_texto']) ? trim((string)$rawBody['mensaje1_texto']) : '';
        $m2Texto = isset($rawBody['mensaje2_texto']) ? trim((string)$rawBody['mensaje2_texto']) : '';
        $editarM1Flag = !empty($rawBody['editar_mensaje1']) && $rawBody['editar_mensaje1'] !== '0';

        $requiereM1 = ((int)$contratoActual['tiene_mensaje1'] === 0 && ($enlace !== null && $enlace['alcance'] === 'MENSAJE_1'));
        if ($requiereM1 && $m1Texto === '' && empty($rawBody['m1_objeto'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Este contrato requiere completar el Mensaje 1 (Ficha de información contractual).'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($m1Texto === '' && $m2Texto === '' && empty($rawBody['m2_ejecucion_fisica_pct']) && empty($rawBody['m2_ejecucion_financiera_pct']) && empty($rawBody['m2_ejecucion_fisica_texto']) && empty($rawBody['m2_ejecucion_financiera_texto']) && empty($rawBody['m1_objeto'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Debe diligenciar el contenido de la actualización (Mensaje 2 semanal y/o Mensaje 1).'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $autoParsed = parseSubmittedMessages($m1Texto !== '' ? $m1Texto : null, $m2Texto !== '' ? $m2Texto : null);

        $responsableRep = trim((string)($rawBody['responsable_reportante'] ?? ($enlace['responsable_nombre'] ?? null) ?? $contratoActual['responsable_nombre'] ?? 'Responsable'));
        if ($responsableRep === '') $responsableRep = 'Responsable';

        $fechaReporteTxt = trim((string)($rawBody['m2_fecha_reporte'] ?? $autoParsed['mensaje2']['fecha_reporte_texto'] ?? date('Y-m-d')));
        $fechaReporteIso = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaReporteTxt) ? $fechaReporteTxt : date('Y-m-d');

        // Soportar tanto campo único simple (m2_ejecucion_fisica_texto) como porcentaje numérico
        $rawFisicaTxt = trim((string)($rawBody['m2_ejecucion_fisica_texto'] ?? ''));
        $fisicaPct = isset($rawBody['m2_ejecucion_fisica_pct']) && $rawBody['m2_ejecucion_fisica_pct'] !== ''
            ? (float)$rawBody['m2_ejecucion_fisica_pct']
            : null;
        if ($fisicaPct === null && $rawFisicaTxt !== '' && preg_match('/(\d+(?:[\.,]\d+)?)/', $rawFisicaTxt, $mpFis)) {
            $valF = (float)str_replace(',', '.', $mpFis[1]);
            if ($valF >= 0 && $valF <= 100) $fisicaPct = $valF;
        }
        if ($fisicaPct === null) {
            $fisicaPct = $autoParsed['mensaje2']['ejecucion_fisica_pct'] ?? ($contratoActual['ejecucion_fisica_pct'] !== null ? (float)$contratoActual['ejecucion_fisica_pct'] : null);
        }
        $fisicaTxt = $rawFisicaTxt !== ''
            ? $rawFisicaTxt
            : trim((string)($autoParsed['mensaje2']['ejecucion_fisica_texto'] ?? ($fisicaPct !== null ? $fisicaPct . '%' : '')));
        if ($fisicaTxt === '') $fisicaTxt = $contratoActual['ejecucion_fisica_texto'];

        // Soportar tanto campo único simple (m2_ejecucion_financiera_texto) como porcentaje numérico
        $rawFinancTxt = trim((string)($rawBody['m2_ejecucion_financiera_texto'] ?? ''));
        $financPct = isset($rawBody['m2_ejecucion_financiera_pct']) && $rawBody['m2_ejecucion_financiera_pct'] !== ''
            ? (float)$rawBody['m2_ejecucion_financiera_pct']
            : null;
        if ($financPct === null && $rawFinancTxt !== '' && preg_match('/(\d+(?:[\.,]\d+)?)/', $rawFinancTxt, $mpFin)) {
            $valFin = (float)str_replace(',', '.', $mpFin[1]);
            if ($valFin >= 0 && $valFin <= 100) $financPct = $valFin;
        }
        if ($financPct === null) {
            $financPct = $autoParsed['mensaje2']['ejecucion_financiera_pct'] ?? ($contratoActual['ejecucion_financiera_pct'] !== null ? (float)$contratoActual['ejecucion_financiera_pct'] : null);
        }
        $financVal = isset($rawBody['m2_ejecucion_financiera_valor']) && $rawBody['m2_ejecucion_financiera_valor'] !== ''
            ? (float)$rawBody['m2_ejecucion_financiera_valor']
            : ($autoParsed['mensaje2']['ejecucion_financiera_valor'] ?? ($contratoActual['ejecucion_financiera_valor'] !== null ? (float)$contratoActual['ejecucion_financiera_valor'] : null));
        $financTxt = $rawFinancTxt !== ''
            ? $rawFinancTxt
            : trim((string)($autoParsed['mensaje2']['ejecucion_financiera_texto'] ?? ($financPct !== null ? $financPct . '%' : '')));
        if ($financTxt === '') $financTxt = $contratoActual['ejecucion_financiera_texto'];

        $finObraPct = isset($rawBody['m2_ejecucion_financiera_obra_pct']) && $rawBody['m2_ejecucion_financiera_obra_pct'] !== ''
            ? (float)$rawBody['m2_ejecucion_financiera_obra_pct']
            : $autoParsed['mensaje2']['ejecucion_financiera_obra_pct'];
        $finInterPct = isset($rawBody['m2_ejecucion_financiera_inter_pct']) && $rawBody['m2_ejecucion_financiera_inter_pct'] !== ''
            ? (float)$rawBody['m2_ejecucion_financiera_inter_pct']
            : $autoParsed['mensaje2']['ejecucion_financiera_inter_pct'];

        $estadoRepTxt = trim((string)($rawBody['m2_estado_reportado'] ?? $autoParsed['mensaje2']['estado_reportado_texto'] ?? $contratoActual['estado_reportado_texto'] ?? '')) ?: null;
        $fechaFinRepTxt = trim((string)($rawBody['m2_fecha_fin_reportada'] ?? $autoParsed['mensaje2']['fecha_fin_reportada_texto'] ?? $contratoActual['fecha_fin_vigente_texto'] ?? '')) ?: null;
        $fechaFinRepIso = ($fechaFinRepTxt && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFinRepTxt)) ? $fechaFinRepTxt : null;
        $obsRep = trim((string)($rawBody['m2_observaciones'] ?? '')) ?: null;

        // Datos estructurados de Mensaje 1 y Ajustes (Adición, Prórroga, Suspensión, Modificación)
        $m1Objeto = trim((string)($rawBody['m1_objeto'] ?? $autoParsed['mensaje1']['objeto'] ?? '')) ?: null;
        $m1Duracion = trim((string)($rawBody['m1_duracion'] ?? $autoParsed['mensaje1']['duracion_texto'] ?? '')) ?: null;
        $m1Contratista = trim((string)($rawBody['m1_contratista'] ?? $autoParsed['mensaje1']['contratista'] ?? '')) ?: null;
        $m1ApoyoSupervision = trim((string)($rawBody['m1_apoyo_supervision'] ?? $autoParsed['mensaje1']['apoyo_supervision'] ?? '')) ?: null;
        $rawValorM1 = trim((string)($rawBody['m1_valor_inicial'] ?? ''));
        $m1ValorInicial = $rawValorM1 !== ''
            ? (float)preg_replace('/[^\d\.]/', '', str_replace(',', '.', preg_replace('/\.(?=\d{3})/', '', $rawValorM1)))
            : $autoParsed['mensaje1']['valor_inicial'];
        $m1ValorTotal = isset($rawBody['m1_valor_total']) && $rawBody['m1_valor_total'] !== ''
            ? (float)$rawBody['m1_valor_total']
            : ($autoParsed['mensaje1']['valor_total'] ?? $m1ValorInicial);
        $m1FechaInicio = trim((string)($rawBody['m1_fecha_inicio'] ?? $autoParsed['mensaje1']['fecha_inicio_texto'] ?? '')) ?: null;
        $m1FechaFinInicial = trim((string)($rawBody['m1_fecha_fin_inicial'] ?? $autoParsed['mensaje1']['fecha_fin_inicial_texto'] ?? '')) ?: null;

        // Ajustes específicos: Adición (fecha + cantidad), Prórroga (tiempo), Suspensión (fecha)
        $tipoAjuste = strtoupper(trim((string)($rawBody['tipo_ajuste'] ?? 'NINGUNO')));
        $ajusteFechaAdicion = trim((string)($rawBody['ajuste_fecha_adicion'] ?? ''));
        $rawValorAdicion = trim((string)($rawBody['ajuste_valor_adicion'] ?? ''));
        $ajusteValorAdicion = $rawValorAdicion !== ''
            ? (float)preg_replace('/[^\d\.]/', '', str_replace(',', '.', preg_replace('/\.(?=\d{3})/', '', $rawValorAdicion)))
            : null;
        $ajusteTiempoProrroga = trim((string)($rawBody['ajuste_tiempo_prorroga'] ?? ''));
        $ajusteFechaSuspension = trim((string)($rawBody['ajuste_fecha_suspension'] ?? ''));

        $partesAjuste = [];
        if ($tipoAjuste === 'ADICION' || $ajusteFechaAdicion !== '' || $ajusteValorAdicion !== null) {
            $strAd = 'Adición';
            if ($ajusteFechaAdicion !== '') $strAd .= " (Fecha: $ajusteFechaAdicion)";
            if ($ajusteValorAdicion !== null) {
                $strAd .= " por $" . number_format($ajusteValorAdicion, 0, ',', '.');
                $baseIni = $m1ValorInicial ?? (float)($contratoActual['valor_inicial'] ?? 0);
                $m1ValorTotal = $baseIni + $ajusteValorAdicion;
            } elseif ($rawValorAdicion !== '') {
                $strAd .= " por $rawValorAdicion";
            }
            $partesAjuste[] = $strAd;
        }
        if ($tipoAjuste === 'PRORROGA' || $ajusteTiempoProrroga !== '') {
            $partesAjuste[] = 'Prórroga por ' . ($ajusteTiempoProrroga ?: 'tiempo reportado');
        }
        if ($tipoAjuste === 'SUSPENSION' || $ajusteFechaSuspension !== '') {
            $partesAjuste[] = 'Suspensión desde ' . ($ajusteFechaSuspension ?: 'fecha reportada');
        }
        if (!empty($rawBody['m1_modificacion_solicitud'])) {
            $partesAjuste[] = trim((string)$rawBody['m1_modificacion_solicitud']);
        }
        $m1ModificacionSolicitud = !empty($partesAjuste) ? implode(' | ', $partesAjuste) : null;
        $m1NumeroProyecto = trim((string)($rawBody['m1_numero_proyecto'] ?? $rawBody['proyecto_asociar_codigo'] ?? '')) ?: null;

        // Verificar si el contrato ya tiene un proyecto asociado en la base de datos
        $stmtChkProy = $pdo->prepare("SELECT p.codigo FROM despacho_contrato_proyectos cp INNER JOIN despacho_proyectos p ON p.id = cp.proyecto_id WHERE cp.contrato_id = ? LIMIT 1");
        $stmtChkProy->execute([$contratoId]);
        $proyectoExistenteCod = $stmtChkProy->fetchColumn() ?: null;

        if ($enlace === null && !$proyectoExistenteCod && ($m1NumeroProyecto === null || $m1NumeroProyecto === '' || $m1NumeroProyecto === 'SIN_PROYECTO')) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Este contrato no está asociado a ningún proyecto. Antes de guardar debe seleccionar o digitar el Número de Proyecto de Inversión al que pertenece.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $hasM1Edit = ($editarM1Flag || !empty($esNuevoContrato) || $enlace !== null || (int)$contratoActual['tiene_mensaje1'] === 0 || $m1ModificacionSolicitud !== null)
            && ($m1Texto !== '' || $m1Objeto !== null || $m1Duracion !== null || $m1Contratista !== null || $m1ApoyoSupervision !== null || $m1ValorInicial !== null || $m1ValorTotal !== null || $m1ModificacionSolicitud !== null || $m1NumeroProyecto !== null || !empty($esNuevoContrato));
        $hasM2Edit = ($m2Texto !== '' || isset($rawBody['m2_ejecucion_fisica_pct']) || isset($rawBody['m2_ejecucion_financiera_pct']) || !empty($rawBody['m2_ejecucion_fisica_texto']) || !empty($rawBody['m2_ejecucion_financiera_texto']) || isset($rawBody['m2_estado_reportado']) || $obsRep !== null);

        $tipoMensaje = ($hasM1Edit && $hasM2Edit) ? 'AMBOS' : ($hasM1Edit ? 'MENSAJE_1' : 'MENSAJE_2');

        if ($hasM1Edit && $m1Texto === '') {
            $apoyoFinal = $m1ApoyoSupervision ?: ($contratoActual['responsable_nombre'] ?: $responsableRep);
            $durFinal = $m1Duracion ?: ($contratoActual['duracion_texto'] ?: '');
            $m1Texto = "Mensaje 1:\nInformación del Contrato\n"
                . "Número del contrato: {$contratoActual['numero_contrato']}\n"
                . ($m1Objeto ? "Objeto del contrato: $m1Objeto\n" : ($contratoActual['objeto'] ? "Objeto del contrato: {$contratoActual['objeto']}\n" : ''))
                . ($durFinal ? "Duración del contrato: $durFinal\n" : '')
                . ($m1ValorInicial !== null ? "Valor: $" . number_format($m1ValorInicial, 0, ',', '.') . "\n" : '')
                . ($m1FechaInicio ? "Fecha de inicio del contrato: $m1FechaInicio\n" : '')
                . ($m1FechaFinInicial ? "Fecha de terminación inicial: $m1FechaFinInicial\n" : '')
                . ($m1Contratista ? "Contratista: $m1Contratista\n" : ($contratoActual['contratista'] ? "Contratista: {$contratoActual['contratista']}\n" : ''))
                . "Apoyo a la supervisión: $apoyoFinal"
                . ($m1ModificacionSolicitud ? "\nAjuste / Modificación: $m1ModificacionSolicitud" : '');
        }
        $m1TextoParaVersion = $hasM1Edit ? $m1Texto : $prevM1Texto;

        if ($hasM2Edit && $m2Texto === '') {
            $m2Texto = "Mensaje 2: Reporte Semanal de Ejecución Presupuestal\n"
                . "Fecha reporte: $fechaReporteTxt\n"
                . ($financTxt !== null ? "Ejecución financiera: $financTxt\n" : '')
                . ($fisicaTxt !== null ? "Ejecución física: $fisicaTxt\n" : '')
                . ($estadoRepTxt !== null ? "Estado: $estadoRepTxt\n" : '')
                . ($fechaFinRepTxt !== null ? "Fecha finalización: $fechaFinRepTxt" : '');
        }
        $m2TextoParaVersion = $hasM2Edit ? $m2Texto : $prevM2Texto;

        // Construir resumen detallado de modificaciones respecto al estado anterior para el tracking
        $resumenCambios = [];
        if (!empty($esNuevoContrato)) {
            $resumenCambios[] = 'Se agregó un nuevo contrato al Proyecto No. ' . ($m1NumeroProyecto ?: 'General') . ' (' . $contratoActual['numero_contrato'] . ')';
        } elseif ($m1NumeroProyecto !== null && $m1NumeroProyecto !== '' && $m1NumeroProyecto !== 'SIN_PROYECTO' && strcasecmp((string)$proyectoExistenteCod, $m1NumeroProyecto) !== 0) {
            $resumenCambios[] = "Asociado al Proyecto de Inversión No. $m1NumeroProyecto";
        }
        if ($m1ModificacionSolicitud !== null) {
            $resumenCambios[] = "Ajuste contractual registrado: $m1ModificacionSolicitud";
        }
        if ($hasM1Edit) {
            $resumenCambios[] = ((int)$contratoActual['tiene_mensaje1'] === 0)
                ? 'Se completó por primera vez el Mensaje 1 (Información del Contrato)'
                : 'Se actualizó el Mensaje 1 (Información del Contrato)';
            if ($m1Objeto !== null && $m1Objeto !== $contratoActual['objeto']) {
                $resumenCambios[] = 'Objeto del contrato actualizado';
            }
            if ($m1Contratista !== null && $m1Contratista !== $contratoActual['contratista']) {
                $resumenCambios[] = "Contratista: " . ($contratoActual['contratista'] ?: 'Sin dato') . " -> $m1Contratista";
            }
            if ($m1ValorTotal !== null && (float)$m1ValorTotal !== (float)$contratoActual['valor_total']) {
                $resumenCambios[] = "Valor: $" . number_format((float)$contratoActual['valor_total'], 0, ',', '.') . " -> $" . number_format($m1ValorTotal, 0, ',', '.');
            }
        }
        if ($hasM2Edit) {
            $resumenCambios[] = "Reporte Semanal Mensaje 2 (Fecha reporte: $fechaReporteTxt | Reporta: $responsableRep)";
            $oldFis = $contratoActual['ejecucion_fisica_pct'] !== null ? ((float)$contratoActual['ejecucion_fisica_pct'] . '%') : ($contratoActual['ejecucion_fisica_texto'] ?: 'Sin dato');
            $newFis = $fisicaPct !== null ? ($fisicaPct . '%') : ($fisicaTxt ?: 'Sin dato');
            if ($oldFis !== $newFis) {
                $resumenCambios[] = "Ejecución física: $oldFis -> $newFis";
            }
            $oldFin = $contratoActual['ejecucion_financiera_pct'] !== null ? ((float)$contratoActual['ejecucion_financiera_pct'] . '%') : ($contratoActual['ejecucion_financiera_texto'] ?: 'Sin dato');
            $newFin = $financPct !== null ? ($financPct . '%') : ($financTxt ?: 'Sin dato');
            if ($oldFin !== $newFin) {
                $resumenCambios[] = "Ejecución financiera: $oldFin -> $newFin";
            }
            if ($estadoRepTxt !== null && $estadoRepTxt !== $contratoActual['estado_reportado_texto']) {
                $resumenCambios[] = "Estado: " . ($contratoActual['estado_reportado_texto'] ?: 'Sin dato') . " -> $estadoRepTxt";
            }
        }

        $datosConfirmadosJson = json_encode([
            'confirmado_por_responsable' => true,
            'responsable_reportante' => $responsableRep,
            'tipo_mensaje' => $tipoMensaje,
            'editar_mensaje1' => $hasM1Edit,
            'resumen_cambios' => $resumenCambios,
            'mensaje1_estructurado' => [
                'objeto' => $m1Objeto ?? $contratoActual['objeto'],
                'duracion' => $m1Duracion ?? $contratoActual['duracion_texto'],
                'contratista' => $m1Contratista ?? $contratoActual['contratista'],
                'apoyo_supervision' => $m1ApoyoSupervision ?? $contratoActual['responsable_nombre'],
                'valor_inicial' => $m1ValorInicial ?? $contratoActual['valor_inicial'],
                'valor_total' => $m1ValorTotal ?? $contratoActual['valor_total'],
                'fecha_inicio' => $m1FechaInicio ?? $contratoActual['fecha_inicio_texto'],
                'fecha_fin_inicial' => $m1FechaFinInicial ?? $contratoActual['fecha_fin_inicial_texto'],
                'tipo_ajuste' => $tipoAjuste,
                'ajuste_fecha_adicion' => $ajusteFechaAdicion ?: null,
                'ajuste_valor_adicion' => $ajusteValorAdicion,
                'ajuste_tiempo_prorroga' => $ajusteTiempoProrroga ?: null,
                'ajuste_fecha_suspension' => $ajusteFechaSuspension ?: null,
                'modificacion_solicitud' => $m1ModificacionSolicitud,
            ],
            'mensaje2_estructurado' => [
                'fecha_reporte' => $fechaReporteTxt,
                'ejecucion_fisica_pct' => $fisicaPct,
                'ejecucion_fisica_texto' => $fisicaTxt,
                'ejecucion_financiera_pct' => $financPct,
                'ejecucion_financiera_valor' => $financVal,
                'ejecucion_financiera_texto' => $financTxt,
                'ejecucion_financiera_obra_pct' => $finObraPct,
                'ejecucion_financiera_inter_pct' => $finInterPct,
                'estado_reportado' => $estadoRepTxt,
                'fecha_fin_reportada' => $fechaFinRepTxt,
                'observaciones' => $obsRep,
            ]
        ], JSON_UNESCAPED_UNICODE);

        $pdo->beginTransaction();

        // Buscar si existe reporte en el periodo actual
        $stmtExistRep = $pdo->prepare("SELECT * FROM despacho_reportes WHERE contrato_id = ? AND (periodo_id <=> ?) ORDER BY id DESC LIMIT 1");
        $stmtExistRep->execute([$contratoId, $periodoId]);
        $repExist = $stmtExistRep->fetch(PDO::FETCH_ASSOC);

        $tokenIdVal = $enlace ? (int)$enlace['id'] : null;

        if ($repExist) {
            $reporteId = (int)$repExist['id'];
            $radicado = $repExist['radicado'];
            $nuevaVersionNum = (int)$repExist['version_actual'] + 1;
            $nuevoTipoMensaje = ($repExist['tipo_mensaje'] === 'AMBOS' || $tipoMensaje === 'AMBOS' || $repExist['tipo_mensaje'] !== $tipoMensaje)
                ? 'AMBOS'
                : $tipoMensaje;

            $pdo->prepare("UPDATE despacho_versiones_reporte SET estado_version = 'HISTORICO' WHERE reporte_id = ? AND estado_version IN ('PENDIENTE_REVISION', 'DEVUELTO', 'OBSERVADO')")
                ->execute([$reporteId]);

            $pdo->prepare("UPDATE despacho_reportes SET
                tipo_mensaje = ?,
                version_actual = ?,
                responsable_reportante = ?,
                origen_carga = ?,
                token_id = COALESCE(?, token_id),
                estado_entrega = 'RECIBIDO',
                estado_revision = 'PENDIENTE_REVISION',
                updated_at = NOW()
                WHERE id = ?")
                ->execute([$nuevoTipoMensaje, $nuevaVersionNum, $responsableRep, $origenCarga, $tokenIdVal, $reporteId]);
        } else {
            $nuevaVersionNum = 1;
            $radicado = sprintf('RAD-%s-C%02d-P%02d-%s', date('Ymd'), $contratoId, (int)$periodoId, strtoupper(substr(bin2hex(random_bytes(3)), 0, 5)));

            $stmtInsR = $pdo->prepare("INSERT INTO despacho_reportes (
                radicado, contrato_id, periodo_id, obligacion_id, tipo_mensaje, version_actual,
                responsable_reportante, origen_carga, token_id, estado_entrega, estado_revision
            ) VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?, 'RECIBIDO', 'PENDIENTE_REVISION')");
            $stmtInsR->execute([
                $radicado, $contratoId, $periodoId, $obligacionId, $tipoMensaje,
                $responsableRep, $origenCarga, $tokenIdVal
            ]);
            $reporteId = (int)$pdo->lastInsertId();
        }

        // Insertar nueva versión inmutable en despacho_versiones_reporte
        $ipOrigen = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $fuenteVer = $enlace ? 'Enlace token responsable' : 'Actualización por número de contrato';
        $stmtInsV = $pdo->prepare("INSERT INTO despacho_versiones_reporte (
            reporte_id, contrato_id, periodo_id, numero_version, tipo_mensaje,
            mensaje1_texto_original, mensaje2_texto_original, datos_estructurados_json,
            fecha_reporte_texto, fecha_reporte_iso, fecha_fin_reportada_texto, fecha_fin_reportada_iso,
            ejecucion_fisica_texto, ejecucion_fisica_pct,
            ejecucion_financiera_texto, ejecucion_financiera_pct, ejecucion_financiera_valor,
            ejecucion_financiera_obra_pct, ejecucion_financiera_inter_pct,
            estado_reportado_texto, observaciones_reporte, reportado_por, ip_origen, fuente_version, estado_version
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDIENTE_REVISION')");
        $stmtInsV->execute([
            $reporteId, $contratoId, $periodoId, $nuevaVersionNum, $tipoMensaje,
            $m1TextoParaVersion,
            $m2TextoParaVersion,
            $datosConfirmadosJson,
            $fechaReporteTxt, $fechaReporteIso, $fechaFinRepTxt, $fechaFinRepIso,
            $fisicaTxt, $fisicaPct,
            $financTxt, $financPct, $financVal,
            $finObraPct, $finInterPct,
            $estadoRepTxt, $obsRep, $responsableRep, $ipOrigen, $fuenteVer
        ]);
        $versionId = (int)$pdo->lastInsertId();

        $pdo->prepare("UPDATE despacho_reportes SET version_actual_id = ? WHERE id = ?")->execute([$versionId, $reporteId]);

        if ($m1ModificacionSolicitud !== null) {
            $tipoModDb = in_array($tipoAjuste, ['ADICION', 'PRORROGA', 'SUSPENSION', 'MODIFICACION'], true) ? $tipoAjuste : 'PRORROGA';
            $pdo->prepare("INSERT INTO despacho_modificaciones_contrato (
                contrato_id, tipo_modificacion, numero_modificacion, descripcion_texto, valor_adicion, fecha_fin_texto, estado_tramite, fuente
            ) VALUES (?, ?, 'Ajuste reportado en formulario', ?, ?, ?, 'REPORTADA_PENDIENTE_VALIDACION', ?)")
                ->execute([$contratoId, $tipoModDb, $m1ModificacionSolicitud, $ajusteValorAdicion, $fechaFinRepTxt, "Reporte $radicado v$nuevaVersionNum"]);
        }

        // Actualizar obligación del periodo
        if ($periodoId !== null) {
            $stmtOblUpd = $pdo->prepare("INSERT INTO despacho_obligaciones_reporte (
                periodo_id, contrato_id, responsable_nombre, requiere_mensaje1, requiere_mensaje2,
                fecha_esperada, estado_entrega, estado_revision, reporte_actual_id, radicado_ultimo, fecha_recepcion
            ) VALUES (?, ?, ?, 0, 1, COALESCE(?, CURDATE()), 'RECIBIDO', 'PENDIENTE_REVISION', ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                estado_entrega = 'RECIBIDO',
                estado_revision = 'PENDIENTE_REVISION',
                reporte_actual_id = VALUES(reporte_actual_id),
                radicado_ultimo = VALUES(radicado_ultimo),
                fecha_recepcion = NOW()");
            $stmtOblUpd->execute([
                $periodoId, $contratoId, $responsableRep, $fechaLimiteEntrega, $reporteId, $radicado
            ]);
        }

        // Actualizar los datos vigentes en despacho_contratos para que la siguiente consulta muestre la última información mientras el historial conserva todas las versiones previas
        $updFields = [
            'ultimo_reporte_id = ?',
            'estado_entrega = \'RECIBIDO\'',
            'estado_revision = \'PENDIENTE_REVISION\'',
            'fecha_ultimo_reporte_texto = ?',
            'fecha_ultimo_reporte_iso = ?',
            'ejecucion_fisica_texto = ?',
            'ejecucion_fisica_pct = ?',
            'ejecucion_financiera_texto = ?',
            'ejecucion_financiera_pct = ?',
            'ejecucion_financiera_valor = ?',
            'estado_reportado_texto = COALESCE(?, estado_reportado_texto)',
            'fecha_fin_vigente_texto = COALESCE(?, fecha_fin_vigente_texto)',
            'responsable_nombre = COALESCE(?, responsable_nombre)'
        ];
        $updParams = [
            $reporteId,
            $fechaReporteTxt,
            $fechaReporteIso,
            $fisicaTxt,
            $fisicaPct,
            $financTxt,
            $financPct,
            $financVal,
            $estadoRepTxt,
            $fechaFinRepTxt,
            $m1ApoyoSupervision ?: $responsableRep
        ];

        if ($hasM1Edit) {
            $updFields[] = 'tiene_mensaje1 = 1';
            $updFields[] = 'mensaje1_actual_id = ?';
            $updParams[] = $versionId;
            if ($m1Objeto !== null) {
                $updFields[] = 'objeto = ?';
                $updParams[] = $m1Objeto;
            }
            if ($m1Duracion !== null) {
                $updFields[] = 'duracion_texto = ?';
                $updParams[] = $m1Duracion;
            }
            if ($m1Contratista !== null) {
                $updFields[] = 'contratista = ?';
                $updParams[] = $m1Contratista;
            }
            if ($m1ValorInicial !== null) {
                $updFields[] = 'valor_inicial = ?';
                $updParams[] = $m1ValorInicial;
            }
            if ($m1ValorTotal !== null) {
                $updFields[] = 'valor_total = ?';
                $updParams[] = $m1ValorTotal;
            }
            if ($m1FechaInicio !== null) {
                $updFields[] = 'fecha_inicio_texto = ?';
                $updParams[] = $m1FechaInicio;
            }
            if ($m1FechaFinInicial !== null) {
                $updFields[] = 'fecha_fin_inicial_texto = ?';
                $updParams[] = $m1FechaFinInicial;
            }
        }

        if ($m1NumeroProyecto !== null && $m1NumeroProyecto !== '' && $m1NumeroProyecto !== 'SIN_PROYECTO') {
            $stmtFindP = $pdo->prepare("SELECT id FROM despacho_proyectos WHERE codigo = ? LIMIT 1");
            $stmtFindP->execute([$m1NumeroProyecto]);
            $proyIdAssoc = (int)($stmtFindP->fetchColumn() ?: 0);
            if ($proyIdAssoc <= 0) {
                $stmtInsP = $pdo->prepare("INSERT INTO despacho_proyectos (codigo, nombre, activo) VALUES (?, ?, 1)");
                $stmtInsP->execute([$m1NumeroProyecto, 'Proyecto ' . $m1NumeroProyecto]);
                $proyIdAssoc = (int)$pdo->lastInsertId();
            }
            if ($proyIdAssoc > 0) {
                $pdo->prepare("INSERT IGNORE INTO despacho_contrato_proyectos (contrato_id, proyecto_id) VALUES (?, ?)")
                    ->execute([$contratoId, $proyIdAssoc]);
            }
        }

        $updParams[] = $contratoId;
        $pdo->prepare("UPDATE despacho_contratos SET " . implode(', ', $updFields) . " WHERE id = ?")
            ->execute($updParams);

        // Procesar archivos adjuntos opcionales
        $adjuntosGuardados = [];
        if (!empty($_FILES['adjuntos'])) {
            $storageDir = dirname(__DIR__) . '/storage/despacho_adjuntos';
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0755, true);
            }
            $htPath = dirname(__DIR__) . '/storage/.htaccess';
            if (!file_exists($htPath)) {
                file_put_contents($htPath, "Order allow,deny\nDeny from all\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n");
            }

            $allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'xls', 'xlsx'];
            $maxSize = 10 * 1024 * 1024;

            $files = $_FILES['adjuntos'];
            $fileCount = is_array($files['name']) ? count($files['name']) : 1;

            for ($fIdx = 0; $fIdx < $fileCount; $fIdx++) {
                $fName = is_array($files['name']) ? $files['name'][$fIdx] : $files['name'];
                $fTmp = is_array($files['tmp_name']) ? $files['tmp_name'][$fIdx] : $files['tmp_name'];
                $fErr = is_array($files['error']) ? $files['error'][$fIdx] : $files['error'];
                $fSize = is_array($files['size']) ? (int)$files['size'][$fIdx] : (int)$files['size'];
                $fMime = is_array($files['type']) ? $files['type'][$fIdx] : $files['type'];

                if ($fErr === UPLOAD_ERR_NO_FILE || empty($fName)) continue;
                if ($fErr !== UPLOAD_ERR_OK) {
                    throw new RuntimeException("Error al subir el archivo adjunto: $fName");
                }
                if ($fSize <= 0 || $fSize > $maxSize) {
                    throw new RuntimeException("El archivo '$fName' supera el tamaño máximo permitido (10 MB) o está vacío.");
                }

                $ext = strtolower(pathinfo($fName, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExts, true)) {
                    throw new RuntimeException("Formato de archivo no permitido (.$ext). Use PDF, imagen, Word o Excel.");
                }

                $safeOrigName = preg_replace('/[^a-zA-Z0-9_\-\.\s]/', '_', basename($fName));
                $storedName = sprintf('att_c%d_v%d_%s.%s', $contratoId, $versionId, bin2hex(random_bytes(8)), $ext);
                $destFull = $storageDir . '/' . $storedName;
                $relPath = 'storage/despacho_adjuntos/' . $storedName;

                if (!move_uploaded_file($fTmp, $destFull)) {
                    throw new RuntimeException("No fue posible almacenar el soporte '$fName'.");
                }

                $sha256 = hash_file('sha256', $destFull);
                $stmtAdj = $pdo->prepare("INSERT INTO despacho_adjuntos (
                    contrato_id, reporte_id, version_reporte_id, nombre_original, nombre_almacenado,
                    ruta_relativa, mime_type, tamano_bytes, sha256_hash, subido_por_tipo, subido_por_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'RESPONSABLE_PUBLICO', ?)");
                $stmtAdj->execute([
                    $contratoId, $reporteId, $versionId, $safeOrigName, $storedName,
                    $relPath, $fMime ?: 'application/octet-stream', $fSize, $sha256, $tokenIdVal
                ]);

                $adjuntosGuardados[] = [
                    'id' => (int)$pdo->lastInsertId(),
                    'nombre_original' => $safeOrigName,
                    'tamano_bytes' => $fSize
                ];
            }
        }

        if ($enlace !== null) {
            $pdo->prepare("UPDATE despacho_enlaces_carga SET usos_realizados = usos_realizados + 1, ultimo_uso_en = NOW() WHERE id = ?")
                ->execute([(int)$enlace['id']]);
            $pdo->prepare("UPDATE despacho_requerimientos SET estado_requerimiento = 'RESPONDIDO', fecha_respuesta = NOW() WHERE enlace_carga_id = ?")
                ->execute([(int)$enlace['id']]);
        }

        despachoLogBitacora(
            $pdo,
            $contratoId,
            'VERSION_REPORTE',
            $versionId,
            'ACTUALIZACION_REPORTE_CONTRATO',
            $enlace ? 'TOKEN_PUBLICO' : 'CONSULTA_CONTRATO',
            $tokenIdVal,
            $responsableRep,
            [
                'ejecucion_fisica_pct' => $contratoActual['ejecucion_fisica_pct'],
                'ejecucion_financiera_pct' => $contratoActual['ejecucion_financiera_pct'],
                'fecha_ultimo_reporte_texto' => $contratoActual['fecha_ultimo_reporte_texto'],
            ],
            [
                'radicado' => $radicado,
                'numero_version' => $nuevaVersionNum,
                'tipo_mensaje' => $tipoMensaje,
                'resumen_cambios' => $resumenCambios,
                'ejecucion_fisica_pct' => $fisicaPct,
                'ejecucion_financiera_pct' => $financPct,
                'fecha_reporte' => $fechaReporteTxt,
            ]
        );

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Actualización guardada correctamente en el historial de tracking del contrato.',
            'comprobante' => [
                'contrato_id' => $contratoId,
                'radicado' => $radicado,
                'reporte_id' => $reporteId,
                'version_id' => $versionId,
                'numero_version' => $nuevaVersionNum,
                'contrato_id' => $contratoId,
                'contrato_numero' => $contratoActual['numero_contrato'],
                'contrato_grupo' => $contratoActual['titulo_grupo'],
                'periodo_nombre' => $enlace['periodo_nombre'] ?? 'Periodo actual',
                'responsable_reportante' => $responsableRep,
                'tipo_mensaje' => $tipoMensaje,
                'resumen_cambios' => $resumenCambios,
                'fecha_recepcion_sistema' => date('Y-m-d H:i:s'),
                'fecha_reporte_informada' => $fechaReporteTxt,
                'estado_entrega' => 'RECIBIDO',
                'estado_revision' => 'PENDIENTE_REVISION',
                'nota_importante' => 'La modificación quedó almacenada en el historial de versiones del contrato para seguimiento de Despacho.',
                'adjuntos' => $adjuntosGuardados,
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar el reporte: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
