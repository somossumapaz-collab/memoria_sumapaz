<?php
/**
 * API Privada del Módulo Despacho (Seguimiento Contractual y Alertas Sumapaz).
 * Protegida estrictamente en servidor por verifyDespachoSession($pdo):
 * Solo usuarios.id === 16 ('despacho') y administrador real (rol_id === 1 no restringido).
 */
require_once __DIR__ . '/despacho_auth.php';

header('Content-Type: application/json; charset=utf-8');

$currentUser = verifyDespachoSession($pdo, true);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';

if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $jsonBody = json_decode($rawInput, true);
    if (!is_array($jsonBody)) {
        $jsonBody = $_POST;
    }
    if ($action === '' && !empty($jsonBody['action'])) {
        $action = (string)$jsonBody['action'];
    }
}

/**
 * Calcula indicadores temporales y orientativos de un contrato sin inventar umbrales oficiales.
 */
function enrichContratoIndicators(array $c, string $refDateStr = '2026-10-26'): array {
    $refTs = strtotime($refDateStr) ?: time();
    $finIso = $c['fecha_fin_vigente_iso'] ?? $c['fecha_fin_inicial_iso'] ?? null;
    $iniIso = $c['fecha_inicio_iso'] ?? null;
    $estadoCon = $c['estado_contractual'] ?? 'EN_EJECUCION';

    $diasRestantes = null;
    $estadoPlazo = 'SIN_FECHA_FIN_VALIDADA';
    if ($estadoCon === 'SUSPENDIDO') {
        $estadoPlazo = 'PAUSADO_POR_SUSPENSION';
    } elseif ($estadoCon === 'TERMINADO' || $estadoCon === 'EN_LIQUIDACION') {
        $estadoPlazo = 'FINALIZADO_O_LIQUIDACION';
    } elseif ($finIso) {
        $finTs = strtotime($finIso);
        if ($finTs !== false) {
            $diasRestantes = (int)round(($finTs - $refTs) / 86400);
            $estadoPlazo = $diasRestantes < 0 ? 'PLAZO_VENCIDO' : 'PLAZO_VIGENTE';
        }
    }

    // Días desde el último reporte (fecha del reporte, diferenciada de la fecha de recepción en sistema)
    $diasSinReporte = null;
    if (!empty($c['fecha_ultimo_reporte_iso'])) {
        $repTs = strtotime($c['fecha_ultimo_reporte_iso']);
        if ($repTs !== false) {
            $diasSinReporte = max(0, (int)round(($refTs - $repTs) / 86400));
        }
    }

    // Comparación con cronograma aprobado (si existe) o indicador lineal orientativo explícito
    $cronogramaPct = $c['cronograma_aprobado_pct'] !== null ? (float)$c['cronograma_aprobado_pct'] : null;
    $fisicaPct = $c['ejecucion_fisica_pct'] !== null ? (float)$c['ejecucion_fisica_pct'] : null;
    $financPct = $c['ejecucion_financiera_pct'] !== null ? (float)$c['ejecucion_financiera_pct'] : null;

    $plazoTranscurridoLinealPct = null;
    $notaIndicadorTemporal = null;

    if ($cronogramaPct !== null) {
        $notaIndicadorTemporal = sprintf('Comparado contra cronograma aprobado (%.1f%% programado)', $cronogramaPct);
    } elseif ($estadoCon === 'SUSPENDIDO') {
        $notaIndicadorTemporal = 'Contrato suspendido: contabilización lineal de plazo pausada';
    } elseif ($iniIso && $finIso) {
        $iniTs = strtotime($iniIso);
        $finTs = strtotime($finIso);
        if ($iniTs !== false && $finTs !== false && $finTs > $iniTs) {
            $totalSec = $finTs - $iniTs;
            $elapsedSec = max(0, min($totalSec, $refTs - $iniTs));
            $plazoTranscurridoLinealPct = round(($elapsedSec / $totalSec) * 100, 1);
            $notaIndicadorTemporal = sprintf(
                'Indicador orientativo lineal: %.1f%% del plazo transcurrido (no obligación contractual; sin cronograma aprobado)',
                $plazoTranscurridoLinealPct
            );
        }
    }

    $c['dias_restantes_plazo_vigente'] = $diasRestantes;
    $c['estado_plazo'] = $estadoPlazo;
    $c['dias_desde_ultimo_reporte'] = $diasSinReporte;
    $c['plazo_transcurrido_lineal_orientativo_pct'] = $plazoTranscurridoLinealPct;
    $c['nota_indicador_temporal'] = $notaIndicadorTemporal;
    $c['ejecucion_fisica_pct'] = $fisicaPct;
    $c['ejecucion_financiera_pct'] = $financPct;
    $c['cronograma_aprobado_pct'] = $cronogramaPct;
    $c['valor_inicial'] = $c['valor_inicial'] !== null ? (float)$c['valor_inicial'] : null;
    $c['valor_adiciones'] = $c['valor_adiciones'] !== null ? (float)$c['valor_adiciones'] : null;
    $c['valor_total'] = $c['valor_total'] !== null ? (float)$c['valor_total'] : null;

    return $c;
}

try {
    if ($method === 'GET') {
        if ($action === 'auth_status') {
            echo json_encode([
                'success' => true,
                'authenticated' => true,
                'authorized' => true,
                'user' => $currentUser
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'dashboard' || $action === '') {
            // Obtener periodos
            $periodos = $pdo->query("SELECT * FROM despacho_periodos_reporte ORDER BY fecha_inicio DESC, id DESC")->fetchAll();
            $periodoActivoId = null;
            foreach ($periodos as $p) {
                if ((int)$p['es_periodo_activo'] === 1) {
                    $periodoActivoId = (int)$p['id'];
                    break;
                }
            }
            if ($periodoActivoId === null && !empty($periodos)) {
                $periodoActivoId = (int)$periodos[0]['id'];
            }

            $selectedPeriodoId = isset($_GET['periodo_id']) && $_GET['periodo_id'] !== '' ? (int)$_GET['periodo_id'] : $periodoActivoId;

            // Catálogos
            $areas = $pdo->query("SELECT id, codigo, nombre, descripcion FROM despacho_areas WHERE activo = 1 ORDER BY nombre ASC")->fetchAll();
            $proyectos = $pdo->query("SELECT p.id, p.codigo, p.nombre, COUNT(DISTINCT m.id) AS total_metas
                FROM despacho_proyectos p
                LEFT JOIN despacho_metas m ON m.proyecto_id = p.id
                WHERE p.activo = 1
                GROUP BY p.id, p.codigo, p.nombre
                ORDER BY p.codigo ASC")->fetchAll();
            $reglasConfig = $pdo->query("SELECT * FROM despacho_reglas_alerta_config ORDER BY id ASC")->fetchAll();
            foreach ($reglasConfig as &$rc) {
                $rc['parametros'] = json_decode($rc['parametros_json'] ?? '{}', true);
            }
            unset($rc);

            $responsables = $pdo->query("SELECT DISTINCT responsable_nombre FROM despacho_contratos WHERE responsable_nombre IS NOT NULL AND responsable_nombre != '' ORDER BY responsable_nombre ASC")->fetchAll(PDO::FETCH_COLUMN);

            // Mapa de proyectos por contrato (evitando duplicar filas de contratos en el listado y en totales)
            $cpRows = $pdo->query("SELECT cp.contrato_id, cp.proyecto_id, cp.nota_asociacion, p.codigo, p.nombre
                FROM despacho_contrato_proyectos cp
                INNER JOIN despacho_proyectos p ON p.id = cp.proyecto_id
                ORDER BY p.codigo ASC")->fetchAll();
            $proyectosPorContrato = [];
            foreach ($cpRows as $cpr) {
                $cid = (int)$cpr['contrato_id'];
                $proyectosPorContrato[$cid][] = [
                    'proyecto_id' => (int)$cpr['proyecto_id'],
                    'codigo' => $cpr['codigo'],
                    'nombre' => $cpr['nombre'],
                    'nota_asociacion' => $cpr['nota_asociacion']
                ];
            }

            // Mapa de componentes por contrato
            $compRows = $pdo->query("SELECT * FROM despacho_componentes_contrato ORDER BY id ASC")->fetchAll();
            $componentesPorContrato = [];
            foreach ($compRows as $cm) {
                $componentesPorContrato[(int)$cm['contrato_id']][] = $cm;
            }

            // Mapa de alertas activas por contrato (conservando múltiples causas simultáneas por contrato)
            $alertasRows = $pdo->query("SELECT a.*, c.numero_contrato, c.titulo_grupo, c.responsable_nombre
                FROM despacho_alertas a
                INNER JOIN despacho_contratos c ON c.id = a.contrato_id
                ORDER BY FIELD(a.nivel_alerta, 'CRÍTICA', 'ALTA', 'VALIDAR DATOS', 'SEGUIMIENTO', 'CIERRE'), a.id ASC")->fetchAll();
            $alertasPorContrato = [];
            $alertasVigentesList = [];
            foreach ($alertasRows as $al) {
                $al['datos_usados'] = $al['datos_usados_json'] ? json_decode($al['datos_usados_json'], true) : null;
                $cid = (int)$al['contrato_id'];
                $alertasPorContrato[$cid][] = $al;
                if ($al['estado_alerta'] === 'ACTIVA' || $al['estado_alerta'] === 'EN_GESTION') {
                    $alertasVigentesList[] = $al;
                }
            }

            // Mapa de obligaciones para el periodo seleccionado
            $obligacionesMap = [];
            if ($selectedPeriodoId) {
                $stmtObl = $pdo->prepare("SELECT o.*, r.created_at AS fecha_recepcion_sistema, r.origen_carga, r.version_actual
                    FROM despacho_obligaciones_reporte o
                    LEFT JOIN despacho_reportes r ON r.id = o.reporte_actual_id
                    WHERE o.periodo_id = ?");
                $stmtObl->execute([$selectedPeriodoId]);
                foreach ($stmtObl->fetchAll() as $ob) {
                    // Si la fecha esperada ya venció y sigue PENDIENTE, marcar estado efectivo FALTANTE_VENCIDO
                    $estadoEnt = $ob['estado_entrega'];
                    if ($estadoEnt === 'PENDIENTE' && !empty($ob['fecha_esperada']) && $ob['fecha_esperada'] < date('Y-m-d')) {
                        $estadoEnt = 'FALTANTE';
                    }
                    $ob['estado_entrega_efectivo'] = $estadoEnt;
                    $obligacionesMap[(int)$ob['contrato_id']] = $ob;
                }
            }

            // Consulta de todos los contratos con su área y fecha de recepción del último reporte
            $allContratos = $pdo->query("SELECT c.*, a.codigo AS area_codigo, a.nombre AS area_nombre,
                    r.radicado AS ultimo_radicado, r.created_at AS fecha_recepcion_sistema, r.origen_carga AS ultimo_origen_carga
                FROM despacho_contratos c
                LEFT JOIN despacho_areas a ON a.id = c.area_id
                LEFT JOIN despacho_reportes r ON r.id = c.ultimo_reporte_id
                ORDER BY COALESCE(c.fila_excel, 9999) ASC, c.id ASC")->fetchAll();

            // Calcular KPIs globales sobre los 49 contratos sin duplicar por proyecto
            $kpis = [
                'total_contratos' => count($allContratos),
                'categorias_origen' => [
                    'CRÍTICA' => 0,
                    'ALTA' => 0,
                    'VALIDAR DATOS' => 0,
                    'SEGUIMIENTO' => 0,
                    'CIERRE' => 0,
                ],
                'niveles_alerta_actuales' => [
                    'CRÍTICA' => 0,
                    'ALTA' => 0,
                    'VALIDAR DATOS' => 0,
                    'SEGUIMIENTO' => 0,
                    'CIERRE' => 0,
                ],
                'alertas_vigentes_total' => count($alertasVigentesList),
                'sin_mensaje1_count' => 0,
                'con_conflicto_validar_count' => 0,
                'grupos_obra_interventoria_count' => 0,
                'valor_total_consolidado' => 0.0,
                'resumen_periodo_seleccionado' => [
                    'periodo_id' => $selectedPeriodoId,
                    'recibidos' => 0,
                    'desactualizados' => 0,
                    'pendientes_o_faltantes' => 0,
                    'sin_reporte_en_periodo' => 0,
                    'aprobados' => 0,
                    'pendientes_revision' => 0,
                    'devueltos_observados' => 0,
                ]
            ];

            // Filtros recibidos por GET
            $fAreaId = isset($_GET['area_id']) && $_GET['area_id'] !== '' ? (int)$_GET['area_id'] : null;
            $fProyectoId = isset($_GET['proyecto_id']) && $_GET['proyecto_id'] !== '' ? (int)$_GET['proyecto_id'] : null;
            $fResponsable = isset($_GET['responsable']) && trim($_GET['responsable']) !== '' ? trim($_GET['responsable']) : null;
            $fNivelAlerta = isset($_GET['nivel_alerta']) && trim($_GET['nivel_alerta']) !== '' ? trim($_GET['nivel_alerta']) : null;
            $fEstadoContractual = isset($_GET['estado_contractual']) && trim($_GET['estado_contractual']) !== '' ? trim($_GET['estado_contractual']) : null;
            $fEstadoEntrega = isset($_GET['estado_entrega']) && trim($_GET['estado_entrega']) !== '' ? trim($_GET['estado_entrega']) : null;
            $fEstadoRevision = isset($_GET['estado_revision']) && trim($_GET['estado_revision']) !== '' ? trim($_GET['estado_revision']) : null;
            $fCriterioAlerta = isset($_GET['criterio_alerta']) && trim($_GET['criterio_alerta']) !== '' ? trim($_GET['criterio_alerta']) : null;
            $fSoloFaltantes = !empty($_GET['solo_faltantes']) && $_GET['solo_faltantes'] !== '0';
            $fSearch = isset($_GET['search']) && trim($_GET['search']) !== '' ? strtolower(trim($_GET['search'])) : null;

            $filteredContratos = [];

            foreach ($allContratos as $c) {
                $cid = (int)$c['id'];
                $c = enrichContratoIndicators($c);
                $c['proyectos_asociados'] = $proyectosPorContrato[$cid] ?? [];
                $c['componentes'] = $componentesPorContrato[$cid] ?? [];
                $c['alertas'] = $alertasPorContrato[$cid] ?? [];

                // Obligación en el periodo seleccionado (si no existe fila para ese contrato en el periodo, se reporta FALTANTE / SIN_REPORTE)
                $obl = $obligacionesMap[$cid] ?? [
                    'id' => null,
                    'periodo_id' => $selectedPeriodoId,
                    'contrato_id' => $cid,
                    'requiere_mensaje1' => (int)$c['tiene_mensaje1'] === 0 ? 1 : 0,
                    'requiere_mensaje2' => 1,
                    'fecha_esperada' => null,
                    'estado_entrega' => 'FALTANTE',
                    'estado_entrega_efectivo' => 'FALTANTE',
                    'estado_revision' => 'SIN_REPORTE',
                    'reporte_actual_id' => null,
                    'radicado_ultimo' => null,
                    'fecha_recepcion' => null,
                ];
                $c['obligacion_periodo'] = $obl;

                // Acumular KPIs globales (una sola vez por contrato)
                $catOrig = $c['categoria_origen'] ?? 'SEGUIMIENTO';
                if (isset($kpis['categorias_origen'][$catOrig])) {
                    $kpis['categorias_origen'][$catOrig]++;
                }
                $nivAct = $c['nivel_alerta'] ?? 'SEGUIMIENTO';
                if (isset($kpis['niveles_alerta_actuales'][$nivAct])) {
                    $kpis['niveles_alerta_actuales'][$nivAct]++;
                }
                if ((int)$c['tiene_mensaje1'] === 0) {
                    $kpis['sin_mensaje1_count']++;
                }
                if (!empty($c['dato_conflicto']) || $nivAct === 'VALIDAR DATOS') {
                    $kpis['con_conflicto_validar_count']++;
                }
                if ((int)$c['es_grupo'] === 1) {
                    $kpis['grupos_obra_interventoria_count']++;
                }
                if ($c['valor_total'] !== null) {
                    $kpis['valor_total_consolidado'] += (float)$c['valor_total'];
                }

                // KPIs del periodo seleccionado
                $estEntPer = $obl['estado_entrega_efectivo'] ?? $obl['estado_entrega'];
                $estRevPer = $obl['estado_revision'] ?? 'SIN_REPORTE';
                if ($estEntPer === 'RECIBIDO') {
                    $kpis['resumen_periodo_seleccionado']['recibidos']++;
                } elseif ($estEntPer === 'DESACTUALIZADO') {
                    $kpis['resumen_periodo_seleccionado']['desactualizados']++;
                } else {
                    $kpis['resumen_periodo_seleccionado']['pendientes_o_faltantes']++;
                }
                if (empty($obl['reporte_actual_id']) || $estRevPer === 'SIN_REPORTE') {
                    $kpis['resumen_periodo_seleccionado']['sin_reporte_en_periodo']++;
                } elseif ($estRevPer === 'APROBADO') {
                    $kpis['resumen_periodo_seleccionado']['aprobados']++;
                } elseif ($estRevPer === 'DEVUELTO' || $estRevPer === 'OBSERVADO') {
                    $kpis['resumen_periodo_seleccionado']['devueltos_observados']++;
                } else {
                    $kpis['resumen_periodo_seleccionado']['pendientes_revision']++;
                }

                // Aplicar filtros
                if ($fAreaId !== null && (int)$c['area_id'] !== $fAreaId) continue;
                if ($fProyectoId !== null) {
                    $hasProy = false;
                    foreach ($c['proyectos_asociados'] as $pa) {
                        if ((int)$pa['proyecto_id'] === $fProyectoId) {
                            $hasProy = true;
                            break;
                        }
                    }
                    if (!$hasProy) continue;
                }
                if ($fResponsable !== null) {
                    if ($fResponsable === '__SIN_RESPONSABLE__') {
                        if ($c['responsable_nombre'] !== null && $c['responsable_nombre'] !== '') continue;
                    } elseif (strcasecmp((string)$c['responsable_nombre'], $fResponsable) !== 0) {
                        continue;
                    }
                }
                if ($fNivelAlerta !== null && $c['nivel_alerta'] !== $fNivelAlerta && $c['categoria_origen'] !== $fNivelAlerta) {
                    continue;
                }
                if ($fEstadoContractual !== null && $c['estado_contractual'] !== $fEstadoContractual) {
                    continue;
                }
                if ($fEstadoEntrega !== null) {
                    if ($estEntPer !== $fEstadoEntrega && $c['estado_entrega'] !== $fEstadoEntrega) {
                        continue;
                    }
                }
                if ($fEstadoRevision !== null) {
                    if ($estRevPer !== $fEstadoRevision && $c['estado_revision'] !== $fEstadoRevision) {
                        continue;
                    }
                }
                if ($fSoloFaltantes) {
                    $isFaltante = (empty($obl['reporte_actual_id']) || in_array($estEntPer, ['PENDIENTE', 'FALTANTE', 'VENCIDO', 'DESACTUALIZADO'], true) || (int)$c['tiene_mensaje1'] === 0);
                    if (!$isFaltante) continue;
                }
                if ($fCriterioAlerta !== null) {
                    $hasCrit = false;
                    foreach ($c['alertas'] as $alItem) {
                        if (($alItem['criterio_codigo'] ?? '') === $fCriterioAlerta && $alItem['estado_alerta'] === 'ACTIVA') {
                            $hasCrit = true;
                            break;
                        }
                    }
                    if (!$hasCrit) continue;
                }
                if ($fSearch !== null) {
                    $haystack = strtolower(
                        ($c['numero_contrato'] ?? '') . ' ' .
                        ($c['titulo_grupo'] ?? '') . ' ' .
                        ($c['responsable_nombre'] ?? '') . ' ' .
                        ($c['contratista'] ?? '') . ' ' .
                        ($c['objeto'] ?? '') . ' ' .
                        ($c['tipo_alerta'] ?? '') . ' ' .
                        ($c['alerta_consolidada'] ?? '')
                    );
                    if (strpos($haystack, $fSearch) === false) continue;
                }

                $filteredContratos[] = $c;
            }

            $historialAlertas = $pdo->query("SELECT e.*, c.numero_contrato
                FROM despacho_eventos_alerta e
                LEFT JOIN despacho_contratos c ON c.id = e.contrato_id
                ORDER BY e.id DESC LIMIT 100")->fetchAll();

            echo json_encode([
                'success' => true,
                'user' => $currentUser,
                'periodo_seleccionado_id' => $selectedPeriodoId,
                'kpis' => $kpis,
                'catalogos' => [
                    'areas' => $areas,
                    'proyectos' => $proyectos,
                    'periodos' => $periodos,
                    'responsables' => $responsables,
                    'reglas_alerta_config' => $reglasConfig,
                ],
                'contratos' => $filteredContratos,
                'alertas_vigentes' => $alertasVigentesList,
                'historial_alertas' => $historialAlertas,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'contract_detail') {
            $contratoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($contratoId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de contrato inválido.']);
                exit;
            }

            $stmtC = $pdo->prepare("SELECT c.*, a.codigo AS area_codigo, a.nombre AS area_nombre
                FROM despacho_contratos c
                LEFT JOIN despacho_areas a ON a.id = c.area_id
                WHERE c.id = ? LIMIT 1");
            $stmtC->execute([$contratoId]);
            $contrato = $stmtC->fetch();
            if (!$contrato) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Contrato no encontrado.']);
                exit;
            }
            $contrato = enrichContratoIndicators($contrato);

            // Proyectos y metas asociadas
            $stmtP = $pdo->prepare("SELECT cp.*, p.codigo, p.nombre
                FROM despacho_contrato_proyectos cp
                INNER JOIN despacho_proyectos p ON p.id = cp.proyecto_id
                WHERE cp.contrato_id = ?
                ORDER BY p.codigo ASC");
            $stmtP->execute([$contratoId]);
            $proyectosAsoc = $stmtP->fetchAll();

            // Componentes (Obra / Interventoría / Principal)
            $stmtComp = $pdo->prepare("SELECT * FROM despacho_componentes_contrato WHERE contrato_id = ? ORDER BY id ASC");
            $stmtComp->execute([$contratoId]);
            $componentes = $stmtComp->fetchAll();

            // Modificaciones contractuales (prórrogas, adiciones, suspensiones)
            $stmtMod = $pdo->prepare("SELECT * FROM despacho_modificaciones_contrato WHERE contrato_id = ? ORDER BY id ASC");
            $stmtMod->execute([$contratoId]);
            $modificaciones = $stmtMod->fetchAll();

            // Obligaciones por periodo
            $stmtObl = $pdo->prepare("SELECT o.*, p.codigo AS periodo_codigo, p.nombre AS periodo_nombre, p.fecha_inicio, p.fecha_fin, p.fecha_limite_entrega, p.es_periodo_activo
                FROM despacho_obligaciones_reporte o
                INNER JOIN despacho_periodos_reporte p ON p.id = o.periodo_id
                WHERE o.contrato_id = ?
                ORDER BY p.fecha_inicio DESC, o.id DESC");
            $stmtObl->execute([$contratoId]);
            $obligaciones = $stmtObl->fetchAll();

            // Reportes y versiones completas (Mensaje 1 y Mensaje 2 históricos)
            $stmtRep = $pdo->prepare("SELECT r.*, p.codigo AS periodo_codigo, p.nombre AS periodo_nombre
                FROM despacho_reportes r
                LEFT JOIN despacho_periodos_reporte p ON p.id = r.periodo_id
                WHERE r.contrato_id = ?
                ORDER BY r.id DESC");
            $stmtRep->execute([$contratoId]);
            $reportes = $stmtRep->fetchAll();

            $stmtVer = $pdo->prepare("SELECT v.*, r.radicado, r.origen_carga, p.codigo AS periodo_codigo, p.nombre AS periodo_nombre
                FROM despacho_versiones_reporte v
                INNER JOIN despacho_reportes r ON r.id = v.reporte_id
                LEFT JOIN despacho_periodos_reporte p ON p.id = v.periodo_id
                WHERE v.contrato_id = ?
                ORDER BY v.id DESC");
            $stmtVer->execute([$contratoId]);
            $versiones = $stmtVer->fetchAll();
            $latestM1Text = null;
            $latestM2Text = null;
            foreach ($versiones as &$ver) {
                $ver['datos_estructurados'] = $ver['datos_estructurados_json'] ? json_decode($ver['datos_estructurados_json'], true) : null;
                if ($latestM1Text === null && !empty($ver['mensaje1_texto_original'])) {
                    $latestM1Text = $ver['mensaje1_texto_original'];
                }
                if ($latestM2Text === null && !empty($ver['mensaje2_texto_original'])) {
                    $latestM2Text = $ver['mensaje2_texto_original'];
                }
            }
            unset($ver);
            $contrato['mensaje1_texto'] = $latestM1Text;
            $contrato['mensaje2_ultimo_texto'] = $latestM2Text;

            // Adjuntos protegidos
            $stmtAdj = $pdo->prepare("SELECT id, contrato_id, reporte_id, version_reporte_id, alerta_id, nombre_original, mime_type, tamano_bytes, subido_por_tipo, created_at
                FROM despacho_adjuntos
                WHERE contrato_id = ?
                ORDER BY id DESC");
            $stmtAdj->execute([$contratoId]);
            $adjuntos = $stmtAdj->fetchAll();

            // Alertas y eventos
            $stmtAl = $pdo->prepare("SELECT * FROM despacho_alertas WHERE contrato_id = ? ORDER BY id DESC");
            $stmtAl->execute([$contratoId]);
            $alertas = $stmtAl->fetchAll();
            foreach ($alertas as &$al) {
                $al['datos_usados'] = $al['datos_usados_json'] ? json_decode($al['datos_usados_json'], true) : null;
            }
            unset($al);

            $stmtEv = $pdo->prepare("SELECT * FROM despacho_eventos_alerta WHERE contrato_id = ? ORDER BY id DESC");
            $stmtEv->execute([$contratoId]);
            $eventosAlerta = $stmtEv->fetchAll();

            // Enlaces de carga (sin exponer el token_hash completo)
            $stmtEnl = $pdo->prepare("SELECT e.id, e.token_prefijo, e.contrato_id, e.periodo_id, e.obligacion_id, e.responsable_nombre,
                    e.alcance, e.permite_multiples_cargas, e.usos_realizados, e.max_usos, e.expira_en, e.revocado, e.revocado_en, e.ultimo_uso_en, e.created_at,
                    p.codigo AS periodo_codigo, p.nombre AS periodo_nombre
                FROM despacho_enlaces_carga e
                LEFT JOIN despacho_periodos_reporte p ON p.id = e.periodo_id
                WHERE e.contrato_id = ?
                ORDER BY e.id DESC");
            $stmtEnl->execute([$contratoId]);
            $enlaces = $stmtEnl->fetchAll();

            // Requerimientos manuales
            $stmtReq = $pdo->prepare("SELECT * FROM despacho_requerimientos WHERE contrato_id = ? ORDER BY id DESC");
            $stmtReq->execute([$contratoId]);
            $requerimientos = $stmtReq->fetchAll();

            // Bitácora de auditoría
            $stmtBit = $pdo->prepare("SELECT * FROM despacho_bitacora WHERE contrato_id = ? ORDER BY id DESC LIMIT 100");
            $stmtBit->execute([$contratoId]);
            $bitacora = $stmtBit->fetchAll();

            echo json_encode([
                'success' => true,
                'contrato' => $contrato,
                'proyectos' => $proyectosAsoc,
                'componentes' => $componentes,
                'modificaciones' => $modificaciones,
                'obligaciones' => $obligaciones,
                'reportes' => $reportes,
                'versiones' => $versiones,
                'adjuntos' => $adjuntos,
                'alertas' => $alertas,
                'eventos_alerta' => $eventosAlerta,
                'enlaces_carga' => $enlaces,
                'requerimientos' => $requerimientos,
                'bitacora' => $bitacora,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción GET no reconocida: ' . $action]);
        exit;
    }

    // ========================================================================
    // ACCIONES POST (GESTIÓN Y REVISIÓN POR DESPACHO / ADMIN)
    // ========================================================================
    if ($method === 'POST') {
        if ($action === 'review_report') {
            $reporteId = (int)($jsonBody['reporte_id'] ?? 0);
            $decision = strtoupper(trim((string)($jsonBody['decision'] ?? ''))); // APROBADO, DEVUELTO, OBSERVADO
            $comentario = trim((string)($jsonBody['comentario'] ?? ''));
            $aplicarFichaValidada = !empty($jsonBody['aplicar_ficha_validada']);

            if ($reporteId <= 0 || !in_array($decision, ['APROBADO', 'DEVUELTO', 'OBSERVADO'], true)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Debe indicar un reporte_id válido y una decisión (APROBADO, DEVUELTO u OBSERVADO).']);
                exit;
            }
            if (in_array($decision, ['DEVUELTO', 'OBSERVADO'], true) && $comentario === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Al devolver u observar un reporte es obligatorio registrar el motivo/observación.']);
                exit;
            }

            $stmtR = $pdo->prepare("SELECT * FROM despacho_reportes WHERE id = ? LIMIT 1");
            $stmtR->execute([$reporteId]);
            $rep = $stmtR->fetch();
            if (!$rep) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Reporte no encontrado.']);
                exit;
            }

            $contratoId = (int)$rep['contrato_id'];
            $verId = (int)$rep['version_actual_id'];

            $pdo->beginTransaction();

            $pdo->prepare("UPDATE despacho_reportes SET estado_revision = ?, revisado_por = ?, revisado_por_nombre = ?, fecha_revision = NOW(), comentario_revision = ? WHERE id = ?")
                ->execute([$decision, $currentUser['id'], $currentUser['nombre'], $comentario !== '' ? $comentario : null, $reporteId]);

            if ($verId > 0) {
                $pdo->prepare("UPDATE despacho_versiones_reporte SET estado_version = ?, revisado_por = ?, fecha_revision = NOW(), comentario_revision = ? WHERE id = ?")
                    ->execute([$decision, $currentUser['id'], $comentario !== '' ? $comentario : null, $verId]);
            }

            // Actualizar obligación asociada
            if (!empty($rep['obligacion_id'])) {
                $pdo->prepare("UPDATE despacho_obligaciones_reporte SET estado_revision = ?, observacion_obligacion = COALESCE(?, observacion_obligacion) WHERE id = ?")
                    ->execute([$decision, $comentario !== '' ? $comentario : null, (int)$rep['obligacion_id']]);
            } elseif (!empty($rep['periodo_id'])) {
                $pdo->prepare("UPDATE despacho_obligaciones_reporte SET estado_revision = ?, observacion_obligacion = COALESCE(?, observacion_obligacion) WHERE contrato_id = ? AND periodo_id = ?")
                    ->execute([$decision, $comentario !== '' ? $comentario : null, $contratoId, (int)$rep['periodo_id']]);
            }

            // Actualizar estado_revision del contrato (nota: aprobar el reporte NO resuelve por sí solo alertas de riesgo contractual)
            $pdo->prepare("UPDATE despacho_contratos SET estado_revision = ? WHERE id = ?")
                ->execute([$decision, $contratoId]);

            // Si se aprueba y el revisor autoriza aplicar los datos confirmados de la versión al maestro del contrato
            if ($decision === 'APROBADO' && $aplicarFichaValidada && $verId > 0) {
                $stmtV = $pdo->prepare("SELECT * FROM despacho_versiones_reporte WHERE id = ?");
                $stmtV->execute([$verId]);
                $verData = $stmtV->fetch();
                if ($verData) {
                    $updFields = [];
                    $updParams = [];
                    if (!empty($verData['mensaje1_texto_original'])) {
                        $updFields[] = "tiene_mensaje1 = 1";
                        $updFields[] = "mensaje1_actual_id = ?";
                        $updParams[] = $verId;
                    }
                    if (!empty($verData['fecha_reporte_texto'])) {
                        $updFields[] = "fecha_ultimo_reporte_texto = ?";
                        $updParams[] = $verData['fecha_reporte_texto'];
                        $updFields[] = "fecha_ultimo_reporte_iso = ?";
                        $updParams[] = $verData['fecha_reporte_iso'];
                    }
                    if ($verData['ejecucion_fisica_texto'] !== null) {
                        $updFields[] = "ejecucion_fisica_texto = ?";
                        $updParams[] = $verData['ejecucion_fisica_texto'];
                        $updFields[] = "ejecucion_fisica_pct = ?";
                        $updParams[] = $verData['ejecucion_fisica_pct'];
                    }
                    if ($verData['ejecucion_financiera_texto'] !== null) {
                        $updFields[] = "ejecucion_financiera_texto = ?";
                        $updParams[] = $verData['ejecucion_financiera_texto'];
                        $updFields[] = "ejecucion_financiera_pct = ?";
                        $updParams[] = $verData['ejecucion_financiera_pct'];
                    }
                    if (!empty($verData['fecha_fin_reportada_texto'])) {
                        $updFields[] = "fecha_fin_vigente_texto = ?";
                        $updParams[] = $verData['fecha_fin_reportada_texto'];
                        $updFields[] = "fecha_fin_vigente_iso = ?";
                        $updParams[] = $verData['fecha_fin_reportada_iso'];
                    }
                    if (!empty($verData['estado_reportado_texto'])) {
                        $updFields[] = "estado_reportado_texto = ?";
                        $updParams[] = $verData['estado_reportado_texto'];
                    }
                    $updFields[] = "fuente_actualizacion = ?";
                    $updParams[] = "Reporte validado por " . $currentUser['nombre'] . " (" . date('d/m/Y') . ")";

                    if (!empty($updFields)) {
                        $updParams[] = $contratoId;
                        $pdo->prepare("UPDATE despacho_contratos SET " . implode(', ', $updFields) . " WHERE id = ?")->execute($updParams);
                    }
                }
            }

            despachoLogBitacora(
                $pdo,
                $contratoId,
                'REPORTE',
                $reporteId,
                'REVISION_' . $decision,
                'USUARIO',
                $currentUser['id'],
                $currentUser['nombre'],
                ['estado_revision_anterior' => $rep['estado_revision']],
                ['estado_revision_nuevo' => $decision, 'comentario' => $comentario, 'aplicar_ficha_validada' => $aplicarFichaValidada]
            );

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => "Revisión registrada como $decision por {$currentUser['nombre']}.",
                'reporte_id' => $reporteId,
                'estado_revision' => $decision,
                'revisado_por' => $currentUser['nombre'],
                'fecha_revision' => date('Y-m-d H:i:s'),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'generate_upload_link') {
            $contratoId = (int)($jsonBody['contrato_id'] ?? 0);
            $periodoId = !empty($jsonBody['periodo_id']) ? (int)$jsonBody['periodo_id'] : null;
            $alcance = strtoupper(trim((string)($jsonBody['alcance'] ?? 'AMBOS')));
            $diasVigencia = max(1, min(60, (int)($jsonBody['dias_vigencia'] ?? 7)));
            $maxUsos = max(1, min(50, (int)($jsonBody['max_usos'] ?? 10)));

            if ($contratoId <= 0 || !in_array($alcance, ['MENSAJE_1', 'MENSAJE_2', 'AMBOS', 'CORRECCION'], true)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Datos de contrato o alcance inválidos para generar enlace.']);
                exit;
            }

            $stmtC = $pdo->prepare("SELECT id, numero_contrato, titulo_grupo, responsable_nombre FROM despacho_contratos WHERE id = ? LIMIT 1");
            $stmtC->execute([$contratoId]);
            $con = $stmtC->fetch();
            if (!$con) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Contrato no encontrado.']);
                exit;
            }

            $responsable = trim((string)($jsonBody['responsable_nombre'] ?? $con['responsable_nombre'] ?? 'Responsable designado'));

            // Buscar o crear obligación si hay periodoId
            $obligacionId = null;
            if ($periodoId) {
                $stmtOb = $pdo->prepare("SELECT id FROM despacho_obligaciones_reporte WHERE contrato_id = ? AND periodo_id = ? LIMIT 1");
                $stmtOb->execute([$contratoId, $periodoId]);
                $obligacionId = $stmtOb->fetchColumn() ?: null;
            }

            $rawToken = bin2hex(random_bytes(24)); // 48 chars aleatorios
            $tokenHash = hash('sha256', $rawToken);
            $tokenPrefijo = substr($rawToken, 0, 8);
            $expiraEn = date('Y-m-d H:i:s', time() + ($diasVigencia * 86400));

            $stmtIns = $pdo->prepare("INSERT INTO despacho_enlaces_carga (
                token_hash, token_prefijo, contrato_id, periodo_id, obligacion_id, responsable_nombre,
                alcance, permite_multiples_cargas, usos_realizados, max_usos, expira_en, revocado, creado_por
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 0, ?, ?, 0, ?)");
            $stmtIns->execute([
                $tokenHash, $tokenPrefijo, $contratoId, $periodoId, $obligacionId, $responsable,
                $alcance, $maxUsos, $expiraEn, $currentUser['id']
            ]);
            $enlaceId = (int)$pdo->lastInsertId();

            $relativeUrl = "reporte_despacho.html?token=" . $rawToken;

            despachoLogBitacora(
                $pdo,
                $contratoId,
                'ENLACE_CARGA',
                $enlaceId,
                'GENERAR_ENLACE_TOKEN',
                'USUARIO',
                $currentUser['id'],
                $currentUser['nombre'],
                null,
                ['token_prefijo' => $tokenPrefijo, 'alcance' => $alcance, 'periodo_id' => $periodoId, 'expira_en' => $expiraEn]
            );

            echo json_encode([
                'success' => true,
                'message' => 'Enlace individual de carga generado exitosamente.',
                'enlace_id' => $enlaceId,
                'token' => $rawToken,
                'token_prefijo' => $tokenPrefijo,
                'url_relativa' => $relativeUrl,
                'expira_en' => $expiraEn,
                'alcance' => $alcance,
                'contrato' => $con
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'revoke_upload_link') {
            $enlaceId = (int)($jsonBody['enlace_id'] ?? 0);
            if ($enlaceId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de enlace inválido.']);
                exit;
            }
            $stmtE = $pdo->prepare("SELECT id, contrato_id, token_prefijo FROM despacho_enlaces_carga WHERE id = ?");
            $stmtE->execute([$enlaceId]);
            $enl = $stmtE->fetch();
            if (!$enl) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Enlace no encontrado.']);
                exit;
            }

            $pdo->prepare("UPDATE despacho_enlaces_carga SET revocado = 1, revocado_en = NOW(), revocado_por = ? WHERE id = ?")
                ->execute([$currentUser['id'], $enlaceId]);

            despachoLogBitacora(
                $pdo,
                (int)$enl['contrato_id'],
                'ENLACE_CARGA',
                $enlaceId,
                'REVOCAR_ENLACE_TOKEN',
                'USUARIO',
                $currentUser['id'],
                $currentUser['nombre'],
                ['revocado' => 0],
                ['revocado' => 1, 'token_prefijo' => $enl['token_prefijo']]
            );

            echo json_encode(['success' => true, 'message' => 'Enlace revocado correctamente.']);
            exit;
        }

        if ($action === 'create_period') {
            $codigo = strtoupper(trim((string)($jsonBody['codigo'] ?? '')));
            $nombre = trim((string)($jsonBody['nombre'] ?? ''));
            $tipoPeriodo = strtoupper(trim((string)($jsonBody['tipo_periodo'] ?? 'SEMANAL')));
            $fechaInicio = trim((string)($jsonBody['fecha_inicio'] ?? ''));
            $fechaFin = trim((string)($jsonBody['fecha_fin'] ?? ''));
            $fechaLimite = trim((string)($jsonBody['fecha_limite_entrega'] ?? $fechaFin));
            $activar = !empty($jsonBody['es_periodo_activo']);

            if ($codigo === '' || $nombre === '' || $fechaInicio === '' || $fechaFin === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Debe proporcionar código, nombre, fecha_inicio y fecha_fin del periodo.']);
                exit;
            }

            $pdo->beginTransaction();
            if ($activar) {
                $pdo->exec("UPDATE despacho_periodos_reporte SET es_periodo_activo = 0");
            }

            $stmtInsP = $pdo->prepare("INSERT INTO despacho_periodos_reporte (
                codigo, nombre, tipo_periodo, fecha_inicio, fecha_fin, fecha_limite_entrega, es_periodo_activo, estado, creado_por
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'ABIERTO', ?)");
            $stmtInsP->execute([$codigo, $nombre, $tipoPeriodo, $fechaInicio, $fechaFin, $fechaLimite, $activar ? 1 : 0, $currentUser['id']]);
            $newPeriodoId = (int)$pdo->lastInsertId();

            // Generar obligaciones para todos los contratos (un reporte de un periodo viejo NO satisface el periodo nuevo)
            $contratos = $pdo->query("SELECT id, responsable_nombre, tiene_mensaje1, estado_contractual FROM despacho_contratos")->fetchAll();
            $estadoInicialObl = ($fechaLimite < date('Y-m-d')) ? 'FALTANTE' : 'PENDIENTE';
            $stmtInsO = $pdo->prepare("INSERT INTO despacho_obligaciones_reporte (
                periodo_id, contrato_id, responsable_nombre, requiere_mensaje1, requiere_mensaje2,
                fecha_esperada, estado_entrega, estado_revision, observacion_obligacion
            ) VALUES (?, ?, ?, ?, 1, ?, ?, 'SIN_REPORTE', ?)");

            $oblCreated = 0;
            foreach ($contratos as $c) {
                $reqM1 = (int)$c['tiene_mensaje1'] === 0 ? 1 : 0;
                $obs = $reqM1 ? 'Pendiente ficha contractual (Mensaje 1) y reporte periódico (Mensaje 2)' : 'Pendiente cargue de Mensaje 2 para el periodo ' . $codigo;
                $stmtInsO->execute([$newPeriodoId, (int)$c['id'], $c['responsable_nombre'], $reqM1, $fechaLimite, $estadoInicialObl, $obs]);
                $oblCreated++;
            }

            despachoLogBitacora(
                $pdo,
                null,
                'PERIODO_REPORTE',
                $newPeriodoId,
                'CREAR_PERIODO_Y_OBLIGACIONES',
                'USUARIO',
                $currentUser['id'],
                $currentUser['nombre'],
                null,
                ['codigo' => $codigo, 'nombre' => $nombre, 'obligaciones_creadas' => $oblCreated]
            );

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => "Periodo '$nombre' creado con $oblCreated obligaciones de reporte pendientes.",
                'periodo_id' => $newPeriodoId,
                'obligaciones_creadas' => $oblCreated
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'create_manual_requirement') {
            $contratoId = (int)($jsonBody['contrato_id'] ?? 0);
            $periodoId = !empty($jsonBody['periodo_id']) ? (int)$jsonBody['periodo_id'] : null;
            $motivo = strtoupper(trim((string)($jsonBody['motivo'] ?? 'ACTUALIZACION_PERIODICA')));
            $generarEnlace = !isset($jsonBody['generar_enlace']) || !empty($jsonBody['generar_enlace']);
            $notaAdicional = trim((string)($jsonBody['nota_adicional'] ?? ''));

            if ($contratoId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de contrato requerido.']);
                exit;
            }

            $stmtC = $pdo->prepare("SELECT * FROM despacho_contratos WHERE id = ? LIMIT 1");
            $stmtC->execute([$contratoId]);
            $con = $stmtC->fetch();
            if (!$con) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Contrato no encontrado.']);
                exit;
            }

            $destinatario = trim((string)($jsonBody['destinatario_nombre'] ?? $con['responsable_nombre'] ?? 'Apoyo a la supervisión'));
            if ($destinatario === '') $destinatario = 'Responsable por asignar';

            $enlaceId = null;
            $urlGenerada = null;
            if ($generarEnlace) {
                $rawToken = bin2hex(random_bytes(24));
                $tokenHash = hash('sha256', $rawToken);
                $tokenPrefijo = substr($rawToken, 0, 8);
                $expiraEn = date('Y-m-d H:i:s', time() + (7 * 86400));
                $alcance = ((int)$con['tiene_mensaje1'] === 0) ? 'AMBOS' : 'MENSAJE_2';

                $stmtEnl = $pdo->prepare("INSERT INTO despacho_enlaces_carga (
                    token_hash, token_prefijo, contrato_id, periodo_id, responsable_nombre,
                    alcance, permite_multiples_cargas, usos_realizados, max_usos, expira_en, revocado, creado_por
                ) VALUES (?, ?, ?, ?, ?, ?, 1, 0, 10, ?, 0, ?)");
                $stmtEnl->execute([$tokenHash, $tokenPrefijo, $contratoId, $periodoId, $destinatario, $alcance, $expiraEn, $currentUser['id']]);
                $enlaceId = (int)$pdo->lastInsertId();
                $urlGenerada = "reporte_despacho.html?token=" . $rawToken;
            }

            $asunto = "Solicitud de actualización contractual - {$con['numero_contrato']} ({$con['titulo_grupo']})";
            $faltantesDesc = [];
            if ((int)$con['tiene_mensaje1'] === 0) {
                $faltantesDesc[] = "Mensaje 1 (Ficha de información contractual completa)";
            }
            $faltantesDesc[] = "Mensaje 2 (Reporte periódico de ejecución física y financiera actualizado)";
            if (!empty($con['dato_conflicto'])) {
                $faltantesDesc[] = "Aclaración/validación de dato en conflicto: " . $con['dato_conflicto'];
            }

            $mensajeTexto = "Cordial saludo, {$destinatario}.\n\n"
                . "Desde el módulo de Seguimiento Contractual de Despacho (Alcaldía Local de Sumapaz / FDRS) se registra requerimiento interno de actualización para el contrato:\n"
                . "• Contrato / Grupo: {$con['numero_contrato']} — {$con['titulo_grupo']}\n"
                . "• Motivo: {$motivo}\n"
                . "• Último reporte registrado: " . ($con['fecha_ultimo_reporte_texto'] ?? 'Sin fecha') . "\n"
                . "• Elementos requeridos:\n  - " . implode("\n  - ", $faltantesDesc) . "\n";

            if ($notaAdicional !== '') {
                $mensajeTexto .= "• Observación de Despacho: {$notaAdicional}\n";
            }
            if ($urlGenerada !== null) {
                $mensajeTexto .= "\nEnlace individual de carga (sin necesidad de usuario/contraseña, exclusivo para este contrato):\n" . $urlGenerada;
            }

            $stmtReq = $pdo->prepare("INSERT INTO despacho_requerimientos (
                contrato_id, periodo_id, enlace_carga_id, destinatario_nombre, motivo, asunto,
                mensaje_generado, url_carga_generada, estado_requerimiento, solicitado_por, solicitado_por_nombre
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'GENERADO_INTERNO', ?, ?)");
            $stmtReq->execute([
                $contratoId, $periodoId, $enlaceId, $destinatario, $motivo, $asunto,
                $mensajeTexto, $urlGenerada, $currentUser['id'], $currentUser['nombre']
            ]);
            $reqId = (int)$pdo->lastInsertId();

            despachoLogBitacora(
                $pdo,
                $contratoId,
                'REQUERIMIENTO',
                $reqId,
                'GENERAR_REQUERIMIENTO_INTERNO',
                'USUARIO',
                $currentUser['id'],
                $currentUser['nombre'],
                null,
                ['motivo' => $motivo, 'destinatario' => $destinatario, 'enlace_id' => $enlaceId]
            );

            echo json_encode([
                'success' => true,
                'message' => 'Requerimiento interno registrado con trazabilidad (sin envío externo simulado).',
                'requerimiento_id' => $reqId,
                'asunto' => $asunto,
                'mensaje_generado' => $mensajeTexto,
                'url_carga_generada' => $urlGenerada
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'create_manual_alert') {
            $contratoId = (int)($jsonBody['contrato_id'] ?? 0);
            $categoriaAlerta = strtoupper(trim((string)($jsonBody['categoria_alerta'] ?? 'RIESGO_CONTRACTUAL'))); // RIESGO_CONTRACTUAL o FALTA_REPORTE
            $criterioCodigo = strtoupper(trim((string)($jsonBody['criterio_codigo'] ?? 'MANUAL_JUSTIFICADA')));
            $nivelAlerta = strtoupper(trim((string)($jsonBody['nivel_alerta'] ?? 'ALTA')));
            $tipoAlerta = trim((string)($jsonBody['tipo_alerta'] ?? 'Alerta manual de Despacho'));
            $descripcion = trim((string)($jsonBody['descripcion_alerta'] ?? ''));
            $condicion = trim((string)($jsonBody['condicion_evaluada'] ?? 'Evaluación cualitativa manual por Despacho'));
            $datoPorValidar = trim((string)($jsonBody['dato_por_validar'] ?? '')) ?: null;
            $actualizarNivelContrato = !empty($jsonBody['actualizar_nivel_contrato']);

            if ($contratoId <= 0 || $descripcion === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Contrato y descripción justificada de la alerta son obligatorios.']);
                exit;
            }

            $stmtC = $pdo->prepare("SELECT * FROM despacho_contratos WHERE id = ? LIMIT 1");
            $stmtC->execute([$contratoId]);
            $con = $stmtC->fetch();
            if (!$con) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Contrato no encontrado.']);
                exit;
            }

            $datosUsados = json_encode([
                'fecha_ultimo_reporte' => $con['fecha_ultimo_reporte_texto'],
                'fecha_fin_vigente' => $con['fecha_fin_vigente_texto'],
                'ejecucion_fisica' => $con['ejecucion_fisica_texto'],
                'ejecucion_financiera' => $con['ejecucion_financiera_texto'],
                'estado_contractual' => $con['estado_contractual'],
                'registrada_por' => $currentUser['nombre']
            ], JSON_UNESCAPED_UNICODE);

            $pdo->beginTransaction();

            $stmtIns = $pdo->prepare("INSERT INTO despacho_alertas (
                contrato_id, origen_hoja, categoria_alerta, criterio_codigo, nivel_alerta, tipo_alerta,
                descripcion_alerta, condicion_evaluada, datos_usados_json, fecha_evaluacion, version_regla,
                dato_por_validar, estado_alerta, requiere_accion, fecha_corte
            ) VALUES (?, 'MANUAL_DESPACHO', ?, ?, ?, ?, ?, ?, ?, NOW(), 'MANUAL_DESPACHO_V1', ?, 'ACTIVA', 1, ?)");
            $stmtIns->execute([
                $contratoId, $categoriaAlerta, $criterioCodigo, $nivelAlerta, $tipoAlerta,
                $descripcion, $condicion, $datosUsados, $datoPorValidar, date('d/m/Y')
            ]);
            $alertaId = (int)$pdo->lastInsertId();

            if ($actualizarNivelContrato) {
                $pdo->prepare("UPDATE despacho_contratos SET nivel_alerta = ?, tipo_alerta = ?, alerta_consolidada = ? WHERE id = ?")
                    ->execute([$nivelAlerta, $tipoAlerta, $descripcion, $contratoId]);
            }

            $pdo->prepare("INSERT INTO despacho_eventos_alerta (
                alerta_id, contrato_id, contrato_nombre_referencia, fecha_corte_texto, tipo_evento, cambio_dato, efecto_alerta, usuario_id, usuario_nombre
            ) VALUES (?, ?, ?, ?, 'CREACION', ?, ?, ?, ?)")
                ->execute([
                    $alertaId, $contratoId, $con['titulo_grupo'], date('d/m/Y'),
                    "Alerta manual ($categoriaAlerta / $criterioCodigo): $descripcion",
                    "Se registra alerta nivel $nivelAlerta ($tipoAlerta)",
                    $currentUser['id'], $currentUser['nombre']
                ]);

            despachoLogBitacora(
                $pdo,
                $contratoId,
                'ALERTA',
                $alertaId,
                'CREAR_ALERTA_MANUAL',
                'USUARIO',
                $currentUser['id'],
                $currentUser['nombre'],
                null,
                ['nivel_alerta' => $nivelAlerta, 'categoria_alerta' => $categoriaAlerta, 'criterio_codigo' => $criterioCodigo, 'descripcion' => $descripcion]
            );

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Alerta manual registrada con trazabilidad de condición y datos usados.',
                'alerta_id' => $alertaId
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'update_alert_status') {
            $alertaId = (int)($jsonBody['alerta_id'] ?? 0);
            $nuevoEstado = strtoupper(trim((string)($jsonBody['estado_alerta'] ?? ''))); // ACTIVA, EN_GESTION, RESUELTA, DESCARTADA
            $motivo = trim((string)($jsonBody['motivo'] ?? ''));
            $nuevoNivelContrato = !empty($jsonBody['nuevo_nivel_contrato']) ? strtoupper(trim((string)$jsonBody['nuevo_nivel_contrato'])) : null;

            if ($alertaId <= 0 || !in_array($nuevoEstado, ['ACTIVA', 'EN_GESTION', 'RESUELTA', 'DESCARTADA'], true) || $motivo === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Debe indicar alerta_id, estado_alerta válido y motivo justificado.']);
                exit;
            }

            $stmtA = $pdo->prepare("SELECT a.*, c.titulo_grupo FROM despacho_alertas a INNER JOIN despacho_contratos c ON c.id = a.contrato_id WHERE a.id = ?");
            $stmtA->execute([$alertaId]);
            $al = $stmtA->fetch();
            if (!$al) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Alerta no encontrada.']);
                exit;
            }

            $contratoId = (int)$al['contrato_id'];
            $pdo->beginTransaction();

            if (in_array($nuevoEstado, ['RESUELTA', 'DESCARTADA'], true)) {
                $pdo->prepare("UPDATE despacho_alertas SET estado_alerta = ?, requiere_accion = 0, cerrada_por = ?, fecha_cierre = NOW(), motivo_cierre = ? WHERE id = ?")
                    ->execute([$nuevoEstado, $currentUser['id'], $motivo, $alertaId]);
            } else {
                $pdo->prepare("UPDATE despacho_alertas SET estado_alerta = ?, motivo_cierre = ? WHERE id = ?")
                    ->execute([$nuevoEstado, $motivo, $alertaId]);
            }

            if ($nuevoNivelContrato !== null) {
                $pdo->prepare("UPDATE despacho_contratos SET nivel_alerta = ? WHERE id = ?")
                    ->execute([$nuevoNivelContrato, $contratoId]);
            }

            $pdo->prepare("INSERT INTO despacho_eventos_alerta (
                alerta_id, contrato_id, contrato_nombre_referencia, fecha_corte_texto, tipo_evento, cambio_dato, efecto_alerta, usuario_id, usuario_nombre
            ) VALUES (?, ?, ?, ?, 'CAMBIO_ESTADO', ?, ?, ?, ?)")
                ->execute([
                    $alertaId, $contratoId, $al['titulo_grupo'], date('d/m/Y'),
                    $motivo,
                    "Alerta #{$alertaId} pasa de {$al['estado_alerta']} a {$nuevoEstado}",
                    $currentUser['id'], $currentUser['nombre']
                ]);

            despachoLogBitacora(
                $pdo,
                $contratoId,
                'ALERTA',
                $alertaId,
                'CAMBIO_ESTADO_ALERTA_' . $nuevoEstado,
                'USUARIO',
                $currentUser['id'],
                $currentUser['nombre'],
                ['estado_alerta' => $al['estado_alerta']],
                ['estado_alerta' => $nuevoEstado, 'motivo' => $motivo]
            );

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => "Estado de alerta actualizado a $nuevoEstado.",
                'alerta_id' => $alertaId,
                'estado_alerta' => $nuevoEstado
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'update_alert_rule_config') {
            $criterioCodigo = trim((string)($jsonBody['criterio_codigo'] ?? ''));
            $parametros = $jsonBody['parametros'] ?? [];
            $estadoValidacion = trim((string)($jsonBody['estado_validacion'] ?? 'PENDIENTE_VALIDACION_DESPACHO'));
            $activaAutomatica = !empty($jsonBody['activa_automatica']) ? 1 : 0;

            $stmtR = $pdo->prepare("SELECT * FROM despacho_reglas_alerta_config WHERE criterio_codigo = ? LIMIT 1");
            $stmtR->execute([$criterioCodigo]);
            $reglaAnt = $stmtR->fetch();
            if (!$reglaAnt) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Criterio de alerta no encontrado.']);
                exit;
            }

            $nuevaVersion = (int)$reglaAnt['version_regla'] + 1;
            $paramJson = json_encode($parametros, JSON_UNESCAPED_UNICODE);

            $pdo->prepare("UPDATE despacho_reglas_alerta_config SET parametros_json = ?, estado_validacion = ?, activa_automatica = ?, version_regla = ?, actualizado_por = ? WHERE id = ?")
                ->execute([$paramJson, $estadoValidacion, $activaAutomatica, $nuevaVersion, $currentUser['id'], (int)$reglaAnt['id']]);

            despachoLogBitacora(
                $pdo,
                null,
                'REGLA_ALERTA_CONFIG',
                (int)$reglaAnt['id'],
                'ACTUALIZAR_CONFIG_CRITERIO_ALERTA',
                'USUARIO',
                $currentUser['id'],
                $currentUser['nombre'],
                $reglaAnt,
                ['parametros' => $parametros, 'estado_validacion' => $estadoValidacion, 'activa_automatica' => $activaAutomatica, 'version_regla' => $nuevaVersion]
            );

            echo json_encode([
                'success' => true,
                'message' => "Configuración del criterio '$criterioCodigo' actualizada (versión $nuevaVersion).",
                'version_regla' => $nuevaVersion
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'update_contract_ficha') {
            $contratoId = (int)($jsonBody['contrato_id'] ?? 0);
            if ($contratoId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de contrato inválido.']);
                exit;
            }

            $stmtC = $pdo->prepare("SELECT * FROM despacho_contratos WHERE id = ? LIMIT 1");
            $stmtC->execute([$contratoId]);
            $conAnt = $stmtC->fetch();
            if (!$conAnt) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Contrato no encontrado.']);
                exit;
            }

            $responsable = array_key_exists('responsable_nombre', $jsonBody) ? (trim((string)$jsonBody['responsable_nombre']) ?: null) : $conAnt['responsable_nombre'];
            $fechaFinTxt = array_key_exists('fecha_fin_vigente_texto', $jsonBody) ? (trim((string)$jsonBody['fecha_fin_vigente_texto']) ?: null) : $conAnt['fecha_fin_vigente_texto'];
            $fechaFinIso = array_key_exists('fecha_fin_vigente_iso', $jsonBody) ? (trim((string)$jsonBody['fecha_fin_vigente_iso']) ?: null) : $conAnt['fecha_fin_vigente_iso'];
            $estadoCon = !empty($jsonBody['estado_contractual']) ? strtoupper(trim((string)$jsonBody['estado_contractual'])) : $conAnt['estado_contractual'];
            $nivelAlerta = !empty($jsonBody['nivel_alerta']) ? strtoupper(trim((string)$jsonBody['nivel_alerta'])) : $conAnt['nivel_alerta'];
            $datoConflicto = array_key_exists('dato_conflicto', $jsonBody) ? (trim((string)$jsonBody['dato_conflicto']) ?: null) : $conAnt['dato_conflicto'];
            $cronogramaPct = array_key_exists('cronograma_aprobado_pct', $jsonBody) && $jsonBody['cronograma_aprobado_pct'] !== '' && $jsonBody['cronograma_aprobado_pct'] !== null
                ? (float)$jsonBody['cronograma_aprobado_pct']
                : $conAnt['cronograma_aprobado_pct'];
            $areaId = !empty($jsonBody['area_id']) ? (int)$jsonBody['area_id'] : $conAnt['area_id'];
            $motivoCambio = trim((string)($jsonBody['motivo_cambio'] ?? 'Actualización validada por Despacho'));

            $fuenteNueva = "Validación Despacho ({$currentUser['nombre']}, " . date('d/m/Y') . ")";

            $pdo->beginTransaction();

            $pdo->prepare("UPDATE despacho_contratos SET
                responsable_nombre = ?, fecha_fin_vigente_texto = ?, fecha_fin_vigente_iso = ?,
                estado_contractual = ?, nivel_alerta = ?, dato_conflicto = ?, cronograma_aprobado_pct = ?,
                area_id = ?, fuente_actualizacion = ?
                WHERE id = ?")
                ->execute([
                    $responsable, $fechaFinTxt, $fechaFinIso,
                    $estadoCon, $nivelAlerta, $datoConflicto, $cronogramaPct,
                    $areaId, $fuenteNueva, $contratoId
                ]);

            if (isset($jsonBody['proyectos_ids']) && is_array($jsonBody['proyectos_ids'])) {
                $pdo->prepare("DELETE FROM despacho_contrato_proyectos WHERE contrato_id = ?")->execute([$contratoId]);
                $stmtCP = $pdo->prepare("INSERT INTO despacho_contrato_proyectos (contrato_id, proyecto_id, nota_asociacion) VALUES (?, ?, ?)");
                $notaP = count($jsonBody['proyectos_ids']) > 1
                    ? 'Contrato transversal asociado a múltiples proyectos sin duplicar totales'
                    : 'Asociado por validación de Despacho';
                foreach ($jsonBody['proyectos_ids'] as $pid) {
                    if ((int)$pid > 0) {
                        $stmtCP->execute([$contratoId, (int)$pid, $notaP]);
                    }
                }
            }

            despachoLogBitacora(
                $pdo,
                $contratoId,
                'CONTRATO',
                $contratoId,
                'ACTUALIZAR_FICHA_VALIDADA',
                'USUARIO',
                $currentUser['id'],
                $currentUser['nombre'],
                [
                    'responsable_nombre' => $conAnt['responsable_nombre'],
                    'fecha_fin_vigente_texto' => $conAnt['fecha_fin_vigente_texto'],
                    'fecha_fin_vigente_iso' => $conAnt['fecha_fin_vigente_iso'],
                    'estado_contractual' => $conAnt['estado_contractual'],
                    'nivel_alerta' => $conAnt['nivel_alerta'],
                    'dato_conflicto' => $conAnt['dato_conflicto'],
                    'cronograma_aprobado_pct' => $conAnt['cronograma_aprobado_pct'],
                ],
                [
                    'responsable_nombre' => $responsable,
                    'fecha_fin_vigente_texto' => $fechaFinTxt,
                    'fecha_fin_vigente_iso' => $fechaFinIso,
                    'estado_contractual' => $estadoCon,
                    'nivel_alerta' => $nivelAlerta,
                    'dato_conflicto' => $datoConflicto,
                    'cronograma_aprobado_pct' => $cronogramaPct,
                    'motivo_cambio' => $motivoCambio,
                ]
            );

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Ficha contractual actualizada conservando historial en bitácora.',
                'contrato_id' => $contratoId
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción POST no reconocida: ' . $action]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método HTTP no permitido.']);
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno en el servidor de Despacho: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
