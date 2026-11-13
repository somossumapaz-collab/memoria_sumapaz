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
 * Determina si un contrato se encuentra finalizado / terminado / liquidado.
 */
function isContratoFinalizado(array $c): bool {
    $estCon = strtoupper(trim((string)($c['estado_contractual'] ?? '')));
    $estRep = strtoupper(trim((string)($c['estado_reportado_texto'] ?? '')));
    if (in_array($estCon, ['TERMINADO', 'FINALIZADO', 'LIQUIDADO', 'CERRADO'], true)) {
        return true;
    }
    if ($estRep !== '' && strpos($estRep, 'EN LIQUID') === false && strpos($estRep, 'POR TERMINAR') === false && strpos($estRep, 'NO TERMINADO') === false && strpos($estRep, 'SIN TERMINAR') === false) {
        if (strpos($estRep, 'FINALIZADO') !== false || strpos($estRep, 'TERMINADO') !== false || strpos($estRep, 'LIQUIDADO') !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Determina si un contrato se encuentra formalmente liquidado y cerrado.
 */
function isContratoLiquidado(array $c): bool {
    $estCon = strtoupper(trim((string)($c['estado_contractual'] ?? '')));
    $estRep = strtoupper(trim((string)($c['estado_reportado_texto'] ?? '')));
    if ($estCon === 'LIQUIDADO') {
        return true;
    }
    if ($estRep !== '' && strpos($estRep, 'EN LIQUID') === false && strpos($estRep, 'POR LIQUID') === false && strpos($estRep, 'NO LIQUID') === false) {
        if ($estRep === 'LIQUIDADO' || strpos($estRep, 'LIQUIDADO') !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Convierte fechas en texto español ("1 de abril 2025", "31/08/2025") o ISO ("2025-08-31") a formato YYYY-MM-DD.
 */
function parseSpanishOrIsoDate(?string $val): ?string {
    if ($val === null) return null;
    $s = trim($val);
    if ($s === '') return null;
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
        return $s;
    }
    if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $s, $m)) {
        return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
    }
    $meses = [
        'enero' => 1, 'ene' => 1,
        'febrero' => 2, 'feb' => 2,
        'marzo' => 3, 'mar' => 3,
        'abril' => 4, 'abr' => 4,
        'mayo' => 5, 'may' => 5,
        'junio' => 6, 'jun' => 6,
        'julio' => 7, 'jul' => 7,
        'agosto' => 8, 'ago' => 8,
        'septiembre' => 9, 'setiembre' => 9, 'sep' => 9, 'sept' => 9,
        'octubre' => 10, 'oct' => 10,
        'noviembre' => 11, 'nov' => 11,
        'diciembre' => 12, 'dic' => 12,
    ];
    if (preg_match('/(\d{1,2})\s*(?:de|\-|\s)\s*([a-záéíóúñ]+)\s*(?:de|del|\-|\s)?\s*(\d{4})/iu', $s, $m)) {
        $dia = (int)$m[1];
        $mesKey = strtolower(trim($m[2]));
        $anio = (int)$m[3];
        if (isset($meses[$mesKey]) && $dia >= 1 && $dia <= 31 && $anio >= 2000 && $anio <= 2100) {
            return sprintf('%04d-%02d-%02d', $anio, $meses[$mesKey], $dia);
        }
    }
    return null;
}

/**
 * Calcula la duración legible entre la fecha de inicio y la fecha de terminación (ej: "5 Meses", "6 Meses y 15 días").
 */
function calcularDuracionEntreFechas(?string $iniIso, ?string $finIso): ?string {
    if (!$iniIso || !$finIso) return null;
    try {
        $dtIni = new DateTime($iniIso);
        $dtFin = new DateTime($finIso);
        if ($dtFin < $dtIni) return null;

        // Si es el mismo día del mes (ej. 15 de abril a 15 de septiembre), diferencia directa
        if ($dtIni->format('d') === $dtFin->format('d') && $dtIni->format('Y-m') !== $dtFin->format('Y-m')) {
            $diff = $dtIni->diff($dtFin);
        } else {
            // En contratos públicos, del 1 de abril al 31 de agosto incluye el último día (+1 día = 1 de septiembre = 5 meses exactos)
            $dtFinInclusive = (clone $dtFin)->modify('+1 day');
            $diff = $dtIni->diff($dtFinInclusive);
        }

        $totalMonths = ($diff->y * 12) + $diff->m;
        $days = $diff->d;

        if ($totalMonths > 0 && $days === 0) {
            return $totalMonths . ($totalMonths === 1 ? ' Mes' : ' Meses');
        }
        if ($totalMonths > 0 && $days > 0) {
            return $totalMonths . ($totalMonths === 1 ? ' Mes' : ' Meses') . ' y ' . $days . ($days === 1 ? ' día' : ' días');
        }
        $totalDays = (int)$dtIni->diff($dtFin)->format('%a') + 1;
        return $totalDays . ($totalDays === 1 ? ' día' : ' días');
    } catch (\Throwable $e) {
        return null;
    }
}

/**
 * Calcula indicadores temporales y orientativos de un contrato sin inventar umbrales oficiales.
 */
function enrichContratoIndicators(array $c, string $refDateStr = ''): array {
    if ($refDateStr === '') {
        $refDateStr = date('Y-m-d');
    }
    $refTs = strtotime($refDateStr) ?: time();
    $iniIso = !empty($c['fecha_inicio_iso']) ? $c['fecha_inicio_iso'] : parseSpanishOrIsoDate($c['fecha_inicio_texto'] ?? null);
    $finIniIso = !empty($c['fecha_fin_inicial_iso']) ? $c['fecha_fin_inicial_iso'] : parseSpanishOrIsoDate($c['fecha_fin_inicial_texto'] ?? null);
    $finVigIso = !empty($c['fecha_fin_vigente_iso']) ? $c['fecha_fin_vigente_iso'] : parseSpanishOrIsoDate($c['fecha_fin_vigente_texto'] ?? null);
    $finIso = $finVigIso ?: $finIniIso;

    $esFinalizado = isContratoFinalizado($c);
    $estadoCon = $esFinalizado ? 'TERMINADO' : ($c['estado_contractual'] ?? 'EN_EJECUCION');

    $diasRestantes = null;
    $estadoPlazo = 'SIN_FECHA_FIN_VALIDADA';
    if ($esFinalizado) {
        $estadoPlazo = 'FINALIZADO';
        $diasRestantes = 0;
    } elseif ($estadoCon === 'SUSPENDIDO') {
        $estadoPlazo = 'PAUSADO_POR_SUSPENSION';
    } elseif ($estadoCon === 'EN_LIQUIDACION') {
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
    } elseif (!empty($c['fecha_ultimo_reporte_texto'])) {
        $parsedRepIso = parseSpanishOrIsoDate($c['fecha_ultimo_reporte_texto']);
        if ($parsedRepIso) {
            $repTs = strtotime($parsedRepIso);
            if ($repTs !== false) {
                $diasSinReporte = max(0, (int)round(($refTs - $repTs) / 86400));
            }
        }
    }

    // Comparación con cronograma aprobado (si existe) o indicador lineal orientativo explícito
    $cronogramaPct = $c['cronograma_aprobado_pct'] !== null ? (float)$c['cronograma_aprobado_pct'] : null;
    $fisicaPct = $c['ejecucion_fisica_pct'] !== null ? (float)$c['ejecucion_fisica_pct'] : null;
    $financPct = $c['ejecucion_financiera_pct'] !== null ? (float)$c['ejecucion_financiera_pct'] : null;

    $plazoTranscurridoLinealPct = null;
    $notaIndicadorTemporal = null;

    if ($esFinalizado) {
        $notaIndicadorTemporal = 'Contrato finalizado: seguimiento semanal cerrado';
    } elseif ($cronogramaPct !== null) {
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

    $duracionInicialCalc = calcularDuracionEntreFechas($iniIso, $finIniIso);
    $duracionFinalCalc = calcularDuracionEntreFechas($iniIso, $finIso);
    $fechaFinFinalTexto = !empty($c['fecha_fin_vigente_texto'])
        ? $c['fecha_fin_vigente_texto']
        : ($c['fecha_fin_inicial_texto'] ?? null);
    $duracionFinalTexto = $duracionFinalCalc
        ?: (($finVigIso && $finIniIso && $finVigIso !== $finIniIso) ? null : ($c['duracion_texto'] ?? $duracionInicialCalc));

    $c['es_finalizado'] = $esFinalizado;
    $c['fecha_inicio_iso'] = $iniIso;
    $c['fecha_fin_inicial_iso'] = $finIniIso;
    $c['fecha_fin_vigente_iso'] = $finIso;
    $c['fecha_fin_final_texto'] = $fechaFinFinalTexto;
    $c['duracion_inicial_calculada'] = $duracionInicialCalc;
    $c['duracion_final_texto'] = $duracionFinalTexto;
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

            // Catálogos (sincronizando catálogo oficial de proyectos Sumapaz)
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

            // Mapa de variaciones de ejecución física y financiera a partir de las versiones de reporte
            $verRowsAll = $pdo->query("SELECT contrato_id, ejecucion_fisica_pct, ejecucion_financiera_pct, datos_estructurados_json
                FROM despacho_versiones_reporte
                ORDER BY id DESC")->fetchAll();
            $versionesPorContrato = [];
            foreach ($verRowsAll as $vr) {
                $cidV = (int)$vr['contrato_id'];
                if (!isset($versionesPorContrato[$cidV]) || count($versionesPorContrato[$cidV]) < 4) {
                    $versionesPorContrato[$cidV][] = $vr;
                }
            }

            // Calcular KPIs globales sobre los contratos sin duplicar por proyecto y sin que los contratos finalizados alteren KPIs
            $kpis = [
                'total_contratos' => count($allContratos),
                'contratos_finalizados_count' => 0,
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
                'alertas_vigentes_total' => 0,
                'sin_mensaje1_count' => 0,
                'con_conflicto_validar_count' => 0,
                'grupos_obra_interventoria_count' => 0,
                'valor_total_consolidado' => 0.0,
                'ultima_fecha_seguimiento' => 'Sin registros',
                'ultima_fecha_seguimiento_iso' => null,
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
            $fAtraso = isset($_GET['atraso']) && trim($_GET['atraso']) !== '' ? trim($_GET['atraso']) : null;

            $filteredContratos = [];
            $finalizadosIdsMap = [];

            foreach ($allContratos as $idxCon => $c) {
                $cid = (int)$c['id'];
                $c = enrichContratoIndicators($c);
                $esFin = !empty($c['es_finalizado']);
                if ($esFin) {
                    $finalizadosIdsMap[$cid] = true;
                }

                // Determinar variación de porcentaje físico y financiero respecto a la versión anterior
                $antFisPct = null;
                $antFinPct = null;
                $versCon = $versionesPorContrato[$cid] ?? [];
                if (!empty($versCon)) {
                    $latestV = $versCon[0];
                    $estJson = !empty($latestV['datos_estructurados_json']) ? json_decode($latestV['datos_estructurados_json'], true) : null;
                    if (is_array($estJson)) {
                        if (isset($estJson['ejecucion_fisica_anterior_pct']) && $estJson['ejecucion_fisica_anterior_pct'] !== null) {
                            $antFisPct = (float)$estJson['ejecucion_fisica_anterior_pct'];
                        } elseif (isset($estJson['valores_anteriores']['ejecucion_fisica_pct']) && $estJson['valores_anteriores']['ejecucion_fisica_pct'] !== null) {
                            $antFisPct = (float)$estJson['valores_anteriores']['ejecucion_fisica_pct'];
                        }
                        if (isset($estJson['ejecucion_financiera_anterior_pct']) && $estJson['ejecucion_financiera_anterior_pct'] !== null) {
                            $antFinPct = (float)$estJson['ejecucion_financiera_anterior_pct'];
                        } elseif (isset($estJson['valores_anteriores']['ejecucion_financiera_pct']) && $estJson['valores_anteriores']['ejecucion_financiera_pct'] !== null) {
                            $antFinPct = (float)$estJson['valores_anteriores']['ejecucion_financiera_pct'];
                        }
                    }
                    if ($antFisPct === null && isset($versCon[1]) && $versCon[1]['ejecucion_fisica_pct'] !== null) {
                        $antFisPct = (float)$versCon[1]['ejecucion_fisica_pct'];
                    }
                    if ($antFinPct === null && isset($versCon[1]) && $versCon[1]['ejecucion_financiera_pct'] !== null) {
                        $antFinPct = (float)$versCon[1]['ejecucion_financiera_pct'];
                    }
                }
                $c['ejecucion_fisica_anterior_pct'] = $antFisPct;
                $c['variacion_fisica_pct'] = ($c['ejecucion_fisica_pct'] !== null && $antFisPct !== null)
                    ? round((float)$c['ejecucion_fisica_pct'] - $antFisPct, 2)
                    : null;
                $c['ejecucion_financiera_anterior_pct'] = $antFinPct;
                $c['variacion_financiera_pct'] = ($c['ejecucion_financiera_pct'] !== null && $antFinPct !== null)
                    ? round((float)$c['ejecucion_financiera_pct'] - $antFinPct, 2)
                    : null;

                $c['proyectos_asociados'] = $proyectosPorContrato[$cid] ?? [];
                $c['componentes'] = $componentesPorContrato[$cid] ?? [];
                $c['alertas'] = $alertasPorContrato[$cid] ?? [];

                // Obligación en el periodo seleccionado
                $obl = $obligacionesMap[$cid] ?? [
                    'id' => null,
                    'periodo_id' => $selectedPeriodoId,
                    'contrato_id' => $cid,
                    'requiere_mensaje1' => (int)$c['tiene_mensaje1'] === 0 ? 1 : 0,
                    'requiere_mensaje2' => $esFin ? 0 : 1,
                    'fecha_esperada' => null,
                    'estado_entrega' => $esFin ? 'FINALIZADO' : 'FALTANTE',
                    'estado_entrega_efectivo' => $esFin ? 'FINALIZADO' : 'FALTANTE',
                    'estado_revision' => $esFin ? 'FINALIZADO' : 'SIN_REPORTE',
                    'reporte_actual_id' => null,
                    'radicado_ultimo' => null,
                    'fecha_recepcion' => null,
                ];
                if ($esFin) {
                    $obl['estado_entrega_efectivo'] = 'FINALIZADO';
                }
                $c['obligacion_periodo'] = $obl;
                $allContratos[$idxCon] = $c;

                if ((int)$c['es_grupo'] === 1) {
                    $kpis['grupos_obra_interventoria_count']++;
                }
                if ($c['valor_total'] !== null) {
                    $kpis['valor_total_consolidado'] += (float)$c['valor_total'];
                }

                // Los contratos finalizados NO alteran KPIs de seguimiento semanal, atraso ni alertas
                if ($esFin) {
                    $kpis['contratos_finalizados_count']++;
                } else {
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
                    if (!empty($c['fecha_ultimo_reporte_iso'])) {
                        if ($kpis['ultima_fecha_seguimiento_iso'] === null || $c['fecha_ultimo_reporte_iso'] > $kpis['ultima_fecha_seguimiento_iso']) {
                            $kpis['ultima_fecha_seguimiento_iso'] = $c['fecha_ultimo_reporte_iso'];
                            $kpis['ultima_fecha_seguimiento'] = !empty($c['fecha_ultimo_reporte_texto'])
                                ? $c['fecha_ultimo_reporte_texto']
                                : date('d/m/Y', strtotime($c['fecha_ultimo_reporte_iso']));
                        }
                    } elseif ($kpis['ultima_fecha_seguimiento_iso'] === null && !empty($c['fecha_ultimo_reporte_texto'])) {
                        $kpis['ultima_fecha_seguimiento'] = $c['fecha_ultimo_reporte_texto'];
                    }

                    // KPIs del periodo seleccionado (solo contratos activos no finalizados)
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
                if ($fEstadoContractual !== null) {
                    if (in_array($fEstadoContractual, ['TERMINADO', 'FINALIZADO', 'LIQUIDADO'], true)) {
                        if (!$esFin && $c['estado_contractual'] !== $fEstadoContractual) continue;
                    } else {
                        if ($esFin || $c['estado_contractual'] !== $fEstadoContractual) continue;
                    }
                }
                $estEntPerFiltro = $obl['estado_entrega_efectivo'] ?? $obl['estado_entrega'];
                $estRevPerFiltro = $obl['estado_revision'] ?? 'SIN_REPORTE';
                if ($fEstadoEntrega !== null) {
                    if ($estEntPerFiltro !== $fEstadoEntrega && $c['estado_entrega'] !== $fEstadoEntrega) {
                        continue;
                    }
                }
                if ($fEstadoRevision !== null) {
                    if ($estRevPerFiltro !== $fEstadoRevision && $c['estado_revision'] !== $fEstadoRevision) {
                        continue;
                    }
                }
                if ($fSoloFaltantes) {
                    if ($esFin) continue;
                    $isFaltante = (empty($obl['reporte_actual_id']) || in_array($estEntPerFiltro, ['PENDIENTE', 'FALTANTE', 'VENCIDO', 'DESACTUALIZADO'], true) || (int)$c['tiene_mensaje1'] === 0);
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

                if ($fAtraso !== null) {
                    if ($fAtraso === 'finalizado') {
                        if (!$esFin) continue;
                    } else {
                        if ($esFin) continue;
                        $diasRep = $c['dias_desde_ultimo_reporte'] ?? null;
                        if ($fAtraso === 'al_dia') {
                            if ($diasRep === null || $diasRep > 7) continue;
                        } elseif ($fAtraso === 'una_semana') {
                            if ($diasRep === null || $diasRep < 8 || $diasRep > 14) continue;
                        } elseif ($fAtraso === 'mas_de_una_semana') {
                            if ($diasRep !== null && $diasRep <= 14) continue;
                        }
                    }
                }

                $filteredContratos[] = $c;
            }

            // Filtrar alertas vigentes excluyendo contratos finalizados para no alterar KPIs
            $alertasVigentesList = array_values(array_filter($alertasVigentesList, function($al) use ($finalizadosIdsMap) {
                return empty($finalizadosIdsMap[(int)$al['contrato_id']]);
            }));
            $kpis['alertas_vigentes_total'] = count($alertasVigentesList);

            // Calcular resumen de cumplimiento y proyectos por cada responsable (sin penalizar ni alterar KPIs por contratos finalizados)
            $respMap = [];
            foreach ($allContratos as $c) {
                $cid = (int)$c['id'];
                $esFin = !empty($c['es_finalizado']);
                $rName = trim((string)($c['responsable_nombre'] ?? ''));
                if ($rName === '') {
                    $rName = 'Sin responsable asignado';
                }
                if (!isset($respMap[$rName])) {
                    $respMap[$rName] = [
                        'responsable' => $rName,
                        'total_contratos' => 0,
                        'contratos_activos' => 0,
                        'contratos_finalizados' => 0,
                        'proyectos_map' => [],
                        'contratos_reportados' => 0,
                        'contratos_sin_reporte' => 0,
                    ];
                }
                $respMap[$rName]['total_contratos']++;

                // Proyectos que afecta este contrato
                $cProys = $proyectosPorContrato[$cid] ?? [];
                if (empty($cProys)) {
                    $respMap[$rName]['proyectos_map']['SIN_PROYECTO'] = 'Sin proyecto asociado';
                } else {
                    foreach ($cProys as $pItem) {
                        $pCod = $pItem['codigo'] ?? 'SIN_PROYECTO';
                        $respMap[$rName]['proyectos_map'][$pCod] = $pItem['nombre'] ?? ('Proyecto ' . $pCod);
                    }
                }

                if ($esFin) {
                    $respMap[$rName]['contratos_finalizados']++;
                } else {
                    $respMap[$rName]['contratos_activos']++;
                    $diasRep = $c['dias_desde_ultimo_reporte'] ?? null;
                    $haReportado = ($diasRep !== null && $diasRep <= 7);
                    if ($haReportado) {
                        $respMap[$rName]['contratos_reportados']++;
                    } else {
                        $respMap[$rName]['contratos_sin_reporte']++;
                    }
                }
            }

            $resumenResponsables = [];
            foreach ($respMap as $rName => $rData) {
                $proyectosList = [];
                foreach ($rData['proyectos_map'] as $pCod => $pNom) {
                    $proyectosList[] = [
                        'codigo' => $pCod,
                        'nombre' => $pNom
                    ];
                }
                $tot = $rData['total_contratos'];
                $totActivos = $rData['contratos_activos'];
                $rep = $rData['contratos_reportados'];
                $pct = $totActivos > 0 ? round(($rep / $totActivos) * 100, 1) : 100.0;
                $resumenResponsables[] = [
                    'responsable' => $rName,
                    'total_contratos' => $tot,
                    'contratos_activos' => $totActivos,
                    'contratos_finalizados' => $rData['contratos_finalizados'],
                    'total_proyectos' => count($proyectosList),
                    'proyectos' => $proyectosList,
                    'contratos_reportados' => $rep,
                    'contratos_sin_reporte' => $rData['contratos_sin_reporte'],
                    'porcentaje_cumplimiento' => $pct,
                ];
            }
            usort($resumenResponsables, function($a, $b) {
                if ($a['responsable'] === 'Sin responsable asignado') return 1;
                if ($b['responsable'] === 'Sin responsable asignado') return -1;
                return strcasecmp($a['responsable'], $b['responsable']);
            });

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
                'resumen_responsables' => $resumenResponsables,
                'alertas_vigentes' => $alertasVigentesList,
                'historial_alertas' => $historialAlertas,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'export_excel_data') {
            // 1. Contratos completos con proyectos y área
            $stmtC = $pdo->query("
                SELECT c.*,
                       a.nombre AS area_nombre,
                       GROUP_CONCAT(DISTINCT p.codigo ORDER BY p.codigo SEPARATOR ', ') AS proyectos_codigos,
                       GROUP_CONCAT(DISTINCT p.nombre ORDER BY p.codigo SEPARATOR ' | ') AS proyectos_nombres
                FROM despacho_contratos c
                LEFT JOIN despacho_areas a ON a.id = c.area_id
                LEFT JOIN despacho_contrato_proyectos cp ON cp.contrato_id = c.id
                LEFT JOIN despacho_proyectos p ON p.id = cp.proyecto_id
                GROUP BY c.id
                ORDER BY c.numero_contrato ASC
            ");
            $contratos = $stmtC->fetchAll(PDO::FETCH_ASSOC);

            // Mapa de versiones para variaciones físicas y financieras
            $verRowsAll = $pdo->query("SELECT contrato_id, ejecucion_fisica_pct, ejecucion_financiera_pct, datos_estructurados_json
                FROM despacho_versiones_reporte
                ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
            $versionesPorContrato = [];
            foreach ($verRowsAll as $vr) {
                $cidV = (int)$vr['contrato_id'];
                if (!isset($versionesPorContrato[$cidV]) || count($versionesPorContrato[$cidV]) < 4) {
                    $versionesPorContrato[$cidV][] = $vr;
                }
            }

            foreach ($contratos as &$con) {
                $con = enrichContratoIndicators($con);
                $cid = (int)$con['id'];
                $antFisPct = null;
                $antFinPct = null;
                $versCon = $versionesPorContrato[$cid] ?? [];
                if (!empty($versCon)) {
                    $latestV = $versCon[0];
                    $estJson = !empty($latestV['datos_estructurados_json']) ? json_decode($latestV['datos_estructurados_json'], true) : null;
                    if (is_array($estJson)) {
                        if (isset($estJson['ejecucion_fisica_anterior_pct']) && $estJson['ejecucion_fisica_anterior_pct'] !== null) {
                            $antFisPct = (float)$estJson['ejecucion_fisica_anterior_pct'];
                        } elseif (isset($estJson['valores_anteriores']['ejecucion_fisica_pct']) && $estJson['valores_anteriores']['ejecucion_fisica_pct'] !== null) {
                            $antFisPct = (float)$estJson['valores_anteriores']['ejecucion_fisica_pct'];
                        }
                        if (isset($estJson['ejecucion_financiera_anterior_pct']) && $estJson['ejecucion_financiera_anterior_pct'] !== null) {
                            $antFinPct = (float)$estJson['ejecucion_financiera_anterior_pct'];
                        } elseif (isset($estJson['valores_anteriores']['ejecucion_financiera_pct']) && $estJson['valores_anteriores']['ejecucion_financiera_pct'] !== null) {
                            $antFinPct = (float)$estJson['valores_anteriores']['ejecucion_financiera_pct'];
                        }
                    }
                    if ($antFisPct === null && isset($versCon[1]) && $versCon[1]['ejecucion_fisica_pct'] !== null) {
                        $antFisPct = (float)$versCon[1]['ejecucion_fisica_pct'];
                    }
                    if ($antFinPct === null && isset($versCon[1]) && $versCon[1]['ejecucion_financiera_pct'] !== null) {
                        $antFinPct = (float)$versCon[1]['ejecucion_financiera_pct'];
                    }
                }
                $con['variacion_fisica_pct'] = ($con['ejecucion_fisica_pct'] !== null && $antFisPct !== null)
                    ? round((float)$con['ejecucion_fisica_pct'] - $antFisPct, 2)
                    : null;
                $con['variacion_financiera_pct'] = ($con['ejecucion_financiera_pct'] !== null && $antFinPct !== null)
                    ? round((float)$con['ejecucion_financiera_pct'] - $antFinPct, 2)
                    : null;
            }
            unset($con);

            // 2. Trazabilidad de reportes y versiones
            $stmtV = $pdo->query("
                SELECT v.id AS version_id,
                       c.numero_contrato,
                       c.contratista,
                       v.numero_version,
                       v.tipo_mensaje,
                       v.fecha_reporte_texto,
                       v.fecha_reporte_iso,
                       v.reportado_por,
                       v.estado_reportado_texto,
                       v.ejecucion_fisica_pct,
                       v.ejecucion_financiera_pct,
                       v.ejecucion_financiera_valor,
                       v.fecha_fin_reportada_texto,
                       v.observaciones_reporte,
                       v.alertas_contrato,
                       v.estado_version,
                       v.fecha_revision,
                       v.comentario_revision,
                       v.fuente_version,
                       v.created_at AS fecha_recepcion_sistema
                FROM despacho_versiones_reporte v
                INNER JOIN despacho_contratos c ON c.id = v.contrato_id
                ORDER BY c.numero_contrato ASC, v.numero_version ASC
            ");
            $versiones = $stmtV->fetchAll(PDO::FETCH_ASSOC);

            // 3. Bitácora de tracking y auditoría
            $stmtB = $pdo->query("
                SELECT b.id AS bitacora_id,
                       c.numero_contrato,
                       b.created_at AS fecha_evento,
                       b.accion,
                       b.actor_tipo,
                       b.actor_nombre,
                       b.descripcion
                FROM despacho_bitacora b
                LEFT JOIN despacho_contratos c ON c.id = b.contrato_id
                ORDER BY b.id DESC
            ");
            $bitacora = $stmtB->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'contratos' => $contratos,
                'trazabilidad' => $versiones,
                'bitacora' => $bitacora,
                'generado' => date('Y-m-d H:i:s')
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

            $stmtVer = $pdo->prepare("SELECT v.*, COALESCE(r.radicado, CONCAT('EDICION-V', v.numero_version)) AS radicado, COALESCE(r.origen_carga, v.fuente_version) AS origen_carga, p.codigo AS periodo_codigo, p.nombre AS periodo_nombre
                FROM despacho_versiones_reporte v
                LEFT JOIN despacho_reportes r ON r.id = v.reporte_id
                LEFT JOIN despacho_periodos_reporte p ON p.id = v.periodo_id
                WHERE v.contrato_id = ?
                ORDER BY v.id DESC");
            $stmtVer->execute([$contratoId]);
            $versiones = $stmtVer->fetchAll();
            $latestM1Text = null;
            $latestM2Text = null;
            foreach ($versiones as &$ver) {
                $ver['datos_estructurados'] = $ver['datos_estructurados_json'] ? json_decode($ver['datos_estructurados_json'], true) : null;
                if (is_array($ver['datos_estructurados']) && empty($ver['datos_estructurados']['resumen_cambios']) && !empty($ver['datos_estructurados']['cambios'])) {
                    $ver['datos_estructurados']['resumen_cambios'] = $ver['datos_estructurados']['cambios'];
                }
                if (empty($ver['responsable_reportante']) && !empty($ver['reportado_por'])) {
                    $ver['responsable_reportante'] = $ver['reportado_por'];
                }
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

            $antFisPct = null;
            $antFinPct = null;
            if (!empty($versiones)) {
                $latestV = $versiones[0];
                $estJson = $latestV['datos_estructurados'] ?? null;
                if (is_array($estJson)) {
                    if (isset($estJson['ejecucion_fisica_anterior_pct']) && $estJson['ejecucion_fisica_anterior_pct'] !== null) {
                        $antFisPct = (float)$estJson['ejecucion_fisica_anterior_pct'];
                    } elseif (isset($estJson['valores_anteriores']['ejecucion_fisica_pct']) && $estJson['valores_anteriores']['ejecucion_fisica_pct'] !== null) {
                        $antFisPct = (float)$estJson['valores_anteriores']['ejecucion_fisica_pct'];
                    }
                    if (isset($estJson['ejecucion_financiera_anterior_pct']) && $estJson['ejecucion_financiera_anterior_pct'] !== null) {
                        $antFinPct = (float)$estJson['ejecucion_financiera_anterior_pct'];
                    } elseif (isset($estJson['valores_anteriores']['ejecucion_financiera_pct']) && $estJson['valores_anteriores']['ejecucion_financiera_pct'] !== null) {
                        $antFinPct = (float)$estJson['valores_anteriores']['ejecucion_financiera_pct'];
                    }
                }
                if ($antFisPct === null && isset($versiones[1]) && $versiones[1]['ejecucion_fisica_pct'] !== null) {
                    $antFisPct = (float)$versiones[1]['ejecucion_fisica_pct'];
                }
                if ($antFinPct === null && isset($versiones[1]) && $versiones[1]['ejecucion_financiera_pct'] !== null) {
                    $antFinPct = (float)$versiones[1]['ejecucion_financiera_pct'];
                }
            }
            $contrato['ejecucion_fisica_anterior_pct'] = $antFisPct;
            $contrato['variacion_fisica_pct'] = ($contrato['ejecucion_fisica_pct'] !== null && $antFisPct !== null)
                ? round((float)$contrato['ejecucion_fisica_pct'] - $antFisPct, 2)
                : null;
            $contrato['ejecucion_financiera_anterior_pct'] = $antFinPct;
            $contrato['variacion_financiera_pct'] = ($contrato['ejecucion_financiera_pct'] !== null && $antFinPct !== null)
                ? round((float)$contrato['ejecucion_financiera_pct'] - $antFinPct, 2)
                : null;

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
        if ($action === 'update_responsable') {
            if (empty($currentUser['is_real_admin']) && empty($currentUser['is_despacho'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Solo el usuario administrador o despacho puede cambiar o asignar el responsable.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $nuevoResponsable = trim((string)($jsonBody['nuevo_responsable'] ?? ''));
            $contratoId = isset($jsonBody['contrato_id']) ? (int)$jsonBody['contrato_id'] : 0;
            $proyectoCodigo = isset($jsonBody['proyecto_codigo']) ? trim((string)$jsonBody['proyecto_codigo']) : '';

            if ($nuevoResponsable === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Debe ingresar o seleccionar el nombre del nuevo responsable.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($contratoId > 0) {
                $stmtUpd = $pdo->prepare("UPDATE despacho_contratos SET responsable_nombre = ? WHERE id = ?");
                $stmtUpd->execute([$nuevoResponsable, $contratoId]);

                $pdo->prepare("UPDATE despacho_obligaciones_reporte SET responsable_nombre = ? WHERE contrato_id = ?")
                    ->execute([$nuevoResponsable, $contratoId]);

                despachoLogBitacora($pdo, $contratoId, 'CONTRATO', $contratoId, 'CAMBIO_RESPONSABLE', 'USUARIO', (int)$currentUser['id'], "Administrador reasignó responsable a: $nuevoResponsable");

                echo json_encode([
                    'success' => true,
                    'message' => 'Responsable actualizado correctamente para el contrato.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($proyectoCodigo !== '' && $proyectoCodigo !== 'SIN_PROYECTO') {
                $stmtProyC = $pdo->prepare("SELECT cp.contrato_id FROM despacho_contrato_proyectos cp
                    INNER JOIN despacho_proyectos p ON p.id = cp.proyecto_id
                    WHERE p.codigo = ?");
                $stmtProyC->execute([$proyectoCodigo]);
                $cIds = $stmtProyC->fetchAll(PDO::FETCH_COLUMN);

                foreach ($cIds as $cId) {
                    $pdo->prepare("UPDATE despacho_contratos SET responsable_nombre = ? WHERE id = ?")
                        ->execute([$nuevoResponsable, (int)$cId]);
                    $pdo->prepare("UPDATE despacho_obligaciones_reporte SET responsable_nombre = ? WHERE contrato_id = ?")
                        ->execute([$nuevoResponsable, (int)$cId]);
                    despachoLogBitacora($pdo, (int)$cId, 'CONTRATO', (int)$cId, 'CAMBIO_RESPONSABLE_PROYECTO', 'USUARIO', (int)$currentUser['id'], "Administrador reasignó responsable del Proyecto No. $proyectoCodigo a: $nuevoResponsable");
                }

                echo json_encode([
                    'success' => true,
                    'message' => 'Responsable actualizado correctamente para ' . count($cIds) . ' contrato(s) del Proyecto No. ' . $proyectoCodigo,
                    'contratos_actualizados' => count($cIds)
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Debe especificar el contrato o el proyecto para asignar el responsable.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'create_contract') {
            if (empty($currentUser['is_real_admin']) && empty($currentUser['is_despacho'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Solo el usuario administrador o despacho puede crear nuevos contratos.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $numeroContrato = trim((string)($jsonBody['numero_contrato'] ?? ''));
            if ($numeroContrato === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'El número de contrato es obligatorio.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmtCheck = $pdo->prepare("SELECT id FROM despacho_contratos WHERE LOWER(numero_contrato) = LOWER(?) OR LOWER(codigo_normalizado) = LOWER(?) LIMIT 1");
            $stmtCheck->execute([$numeroContrato, $numeroContrato]);
            if ($stmtCheck->fetchColumn()) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Ya existe un contrato registrado con el número ' . $numeroContrato], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $tituloGrupo = trim((string)($jsonBody['titulo_grupo'] ?? '')) ?: ('Contrato ' . $numeroContrato);
            $contratista = trim((string)($jsonBody['contratista'] ?? '')) ?: null;
            $objeto = trim((string)($jsonBody['objeto'] ?? '')) ?: null;
            $responsableNombre = trim((string)($jsonBody['responsable_nombre'] ?? '')) ?: null;
            $areaId = !empty($jsonBody['area_id']) ? (int)$jsonBody['area_id'] : null;
            $proyectoId = !empty($jsonBody['proyecto_id']) ? (int)$jsonBody['proyecto_id'] : null;
            $duracionTexto = trim((string)($jsonBody['duracion_texto'] ?? '')) ?: null;
            $valorInicial = isset($jsonBody['valor_inicial']) && $jsonBody['valor_inicial'] !== '' ? (float)$jsonBody['valor_inicial'] : null;
            $fechaInicioIso = trim((string)($jsonBody['fecha_inicio_iso'] ?? '')) ?: null;
            $fechaFinInicialIso = trim((string)($jsonBody['fecha_fin_inicial_iso'] ?? '')) ?: null;
            $estadoContractual = trim((string)($jsonBody['estado_contractual'] ?? 'EN_EJECUCION')) ?: 'EN_EJECUCION';
            $alertasContrato = trim((string)($jsonBody['alertas_contrato'] ?? '')) ?: null;
            $alertaConsolidada = $alertasContrato ?: 'Registrado directamente desde el panel de Despacho';

            $codNorm = strtoupper(preg_replace('/[^a-zA-Z0-9\-]/', '-', $numeroContrato)) . '-' . substr(uniqid(), -4);

            $stmtIns = $pdo->prepare("INSERT INTO despacho_contratos (
                codigo_normalizado, numero_contrato, titulo_grupo, es_grupo, area_id,
                responsable_nombre, contratista, objeto, duracion_texto, valor_inicial, valor_total,
                fecha_inicio_texto, fecha_inicio_iso, fecha_fin_inicial_texto, fecha_fin_inicial_iso,
                fecha_fin_vigente_texto, fecha_fin_vigente_iso, estado_contractual,
                categoria_origen, nivel_alerta, tipo_alerta, alerta_consolidada, alertas_contrato,
                tiene_mensaje1, estado_entrega, estado_revision
            ) VALUES (
                ?, ?, ?, 0, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?,
                'DESPACHO', 'SEGUIMIENTO', 'En seguimiento', ?, ?,
                1, 'PENDIENTE', 'APROBADO'
            )");

            $stmtIns->execute([
                $codNorm, $numeroContrato, $tituloGrupo, $areaId,
                $responsableNombre, $contratista, $objeto, $duracionTexto, $valorInicial, $valorInicial,
                $fechaInicioIso, $fechaInicioIso, $fechaFinInicialIso, $fechaFinInicialIso,
                $fechaFinInicialIso, $fechaFinInicialIso, $estadoContractual,
                $alertaConsolidada, $alertasContrato
            ]);
            $newId = (int)$pdo->lastInsertId();

            if ($proyectoId > 0) {
                $pdo->prepare("INSERT INTO despacho_contrato_proyectos (contrato_id, proyecto_id, nota_asociacion) VALUES (?, ?, 'Asignado al crear contrato en Despacho')")
                    ->execute([$newId, $proyectoId]);
            }

            despachoLogBitacora(
                $pdo,
                $newId,
                'CONTRATO',
                $newId,
                'CREAR_CONTRATO_DESPACHO',
                'USUARIO',
                (int)$currentUser['id'],
                $currentUser['nombre'] . ' creó el contrato ' . $numeroContrato
            );

            echo json_encode([
                'success' => true,
                'message' => 'Contrato creado exitosamente.',
                'contrato_id' => $newId
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'assign_project') {
            if (empty($currentUser['is_real_admin']) && empty($currentUser['is_despacho'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Solo el usuario administrador o despacho puede asociar proyectos a contratos.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $contratoId = isset($jsonBody['contrato_id']) ? (int)$jsonBody['contrato_id'] : 0;
            $proyectoId = isset($jsonBody['proyecto_id']) ? (int)$jsonBody['proyecto_id'] : 0;

            if ($contratoId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de contrato inválido.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $pdo->prepare("DELETE FROM despacho_contrato_proyectos WHERE contrato_id = ?")->execute([$contratoId]);

            if ($proyectoId > 0) {
                $pdo->prepare("INSERT INTO despacho_contrato_proyectos (contrato_id, proyecto_id, nota_asociacion) VALUES (?, ?, 'Asociado desde panel de Despacho')")
                    ->execute([$contratoId, $proyectoId]);
            }

            despachoLogBitacora(
                $pdo,
                $contratoId,
                'CONTRATO',
                $contratoId,
                'ASIGNAR_PROYECTO',
                'USUARIO',
                (int)$currentUser['id'],
                $currentUser['nombre'] . ' asoció el proyecto ID ' . $proyectoId . ' al contrato ID ' . $contratoId
            );

            echo json_encode([
                'success' => true,
                'message' => 'Proyecto asociado exitosamente al contrato.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'update_contract') {
            if (empty($currentUser['is_real_admin']) && empty($currentUser['is_despacho'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Solo el usuario administrador o despacho puede editar la información del contrato.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $contratoId = isset($jsonBody['contrato_id']) ? (int)$jsonBody['contrato_id'] : 0;
            if ($contratoId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de contrato inválido.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmtPrev = $pdo->prepare("SELECT * FROM despacho_contratos WHERE id = ? LIMIT 1");
            $stmtPrev->execute([$contratoId]);
            $cPrev = $stmtPrev->fetch(PDO::FETCH_ASSOC);
            if (!$cPrev) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Contrato no encontrado.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if (isContratoLiquidado($cPrev)) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Este contrato ya se encuentra LIQUIDADO y cerrado definitivamente, por lo que no puede modificarse.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Proyecto actual
            $stmtCurP = $pdo->prepare("SELECT p.id, p.codigo FROM despacho_contrato_proyectos cp INNER JOIN despacho_proyectos p ON p.id = cp.proyecto_id WHERE cp.contrato_id = ? LIMIT 1");
            $stmtCurP->execute([$contratoId]);
            $curProy = $stmtCurP->fetch(PDO::FETCH_ASSOC) ?: null;
            $curProyId = $curProy ? (int)$curProy['id'] : 0;
            $curProyCodigo = $curProy ? $curProy['codigo'] : 'Sin Proyecto';

            // Regla del usuario: número de contrato, título y objeto permanecen protegidos e inalterables
            $newContratista = array_key_exists('contratista', $jsonBody) ? trim((string)$jsonBody['contratista']) : $cPrev['contratista'];
            $newInterventoria = array_key_exists('interventoria_nombre', $jsonBody) ? trim((string)$jsonBody['interventoria_nombre']) : $cPrev['interventoria_nombre'];
            $newResponsable = array_key_exists('responsable_nombre', $jsonBody) ? trim((string)$jsonBody['responsable_nombre']) : $cPrev['responsable_nombre'];
            $newDuracion = array_key_exists('duracion_texto', $jsonBody) ? trim((string)$jsonBody['duracion_texto']) : $cPrev['duracion_texto'];

            $newValIni = array_key_exists('valor_inicial', $jsonBody) && $jsonBody['valor_inicial'] !== '' && $jsonBody['valor_inicial'] !== null ? (float)$jsonBody['valor_inicial'] : ($cPrev['valor_inicial'] !== null ? (float)$cPrev['valor_inicial'] : null);
            $newValAdic = array_key_exists('valor_adiciones', $jsonBody) && $jsonBody['valor_adiciones'] !== '' && $jsonBody['valor_adiciones'] !== null ? (float)$jsonBody['valor_adiciones'] : ($cPrev['valor_adiciones'] !== null ? (float)$cPrev['valor_adiciones'] : null);
            $newValTot = array_key_exists('valor_total', $jsonBody) && $jsonBody['valor_total'] !== '' && $jsonBody['valor_total'] !== null ? (float)$jsonBody['valor_total'] : null;
            if ($newValTot === null && $newValIni !== null) {
                $newValTot = $newValIni + ($newValAdic ?? 0);
            } elseif ($newValTot === null) {
                $newValTot = $cPrev['valor_total'] !== null ? (float)$cPrev['valor_total'] : null;
            }

            $rawFechaIni = array_key_exists('fecha_inicio_texto', $jsonBody)
                ? trim((string)$jsonBody['fecha_inicio_texto'])
                : (array_key_exists('fecha_inicio', $jsonBody) ? trim((string)$jsonBody['fecha_inicio']) : null);
            $newFechaIniTxt = ($rawFechaIni !== null && $rawFechaIni !== '') ? $rawFechaIni : $cPrev['fecha_inicio_texto'];
            $newFechaIniIso = parseSpanishOrIsoDate($newFechaIniTxt) ?: $cPrev['fecha_inicio_iso'];

            $rawFechaFinIni = array_key_exists('fecha_fin_inicial_texto', $jsonBody)
                ? trim((string)$jsonBody['fecha_fin_inicial_texto'])
                : (array_key_exists('fecha_fin_inicial', $jsonBody) ? trim((string)$jsonBody['fecha_fin_inicial']) : null);
            $newFechaFinIniTxt = ($rawFechaFinIni !== null && $rawFechaFinIni !== '') ? $rawFechaFinIni : $cPrev['fecha_fin_inicial_texto'];
            $newFechaFinIniIso = parseSpanishOrIsoDate($newFechaFinIniTxt) ?: $cPrev['fecha_fin_inicial_iso'];

            $rawFechaFinVig = array_key_exists('fecha_fin_vigente_texto', $jsonBody)
                ? trim((string)$jsonBody['fecha_fin_vigente_texto'])
                : (array_key_exists('fecha_fin_vigente', $jsonBody) ? trim((string)$jsonBody['fecha_fin_vigente']) : null);
            $newFechaFinVigTxt = ($rawFechaFinVig !== null && $rawFechaFinVig !== '') ? $rawFechaFinVig : $cPrev['fecha_fin_vigente_texto'];
            $newFechaFinVigIso = parseSpanishOrIsoDate($newFechaFinVigTxt) ?: $cPrev['fecha_fin_vigente_iso'];

            $newEstadoCon = array_key_exists('estado_contractual', $jsonBody) ? trim((string)$jsonBody['estado_contractual']) : $cPrev['estado_contractual'];
            if ($newEstadoCon === '') $newEstadoCon = $cPrev['estado_contractual'];
            if ($newEstadoCon === 'FINALIZADO') $newEstadoCon = 'TERMINADO';

            $newFisPct = array_key_exists('ejecucion_fisica_pct', $jsonBody) && $jsonBody['ejecucion_fisica_pct'] !== '' && $jsonBody['ejecucion_fisica_pct'] !== null ? (float)$jsonBody['ejecucion_fisica_pct'] : ($cPrev['ejecucion_fisica_pct'] !== null ? (float)$cPrev['ejecucion_fisica_pct'] : null);
            if ($newFisPct !== null && ($newFisPct < 0 || $newFisPct > 100)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'La ejecución física debe ser un número entre 0 y 100.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $newFisTxt = array_key_exists('ejecucion_fisica_texto', $jsonBody) && trim((string)$jsonBody['ejecucion_fisica_texto']) !== '' ? trim((string)$jsonBody['ejecucion_fisica_texto']) : ($newFisPct !== null ? $newFisPct . '%' : $cPrev['ejecucion_fisica_texto']);

            $newFinPct = array_key_exists('ejecucion_financiera_pct', $jsonBody) && $jsonBody['ejecucion_financiera_pct'] !== '' && $jsonBody['ejecucion_financiera_pct'] !== null ? (float)$jsonBody['ejecucion_financiera_pct'] : ($cPrev['ejecucion_financiera_pct'] !== null ? (float)$cPrev['ejecucion_financiera_pct'] : null);
            if ($newFinPct !== null && ($newFinPct < 0 || $newFinPct > 100)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'La ejecución financiera debe ser un número entre 0 y 100.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $newFinTxt = array_key_exists('ejecucion_financiera_texto', $jsonBody) && trim((string)$jsonBody['ejecucion_financiera_texto']) !== '' ? trim((string)$jsonBody['ejecucion_financiera_texto']) : ($newFinPct !== null ? $newFinPct . '%' : $cPrev['ejecucion_financiera_texto']);

            if (strtoupper(trim((string)$newEstadoCon)) === 'LIQUIDADO') {
                if ($newFinPct === null || (float)$newFinPct < 100.0) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'No se puede cambiar el estado a LIQUIDADO hasta que se reporte el 100% de ejecución financiera.'
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
            }

            $newAlertas = array_key_exists('alertas_contrato', $jsonBody) ? trim((string)$jsonBody['alertas_contrato']) : $cPrev['alertas_contrato'];

            $newAreaId = array_key_exists('area_id', $jsonBody) && $jsonBody['area_id'] !== '' && $jsonBody['area_id'] !== null ? (int)$jsonBody['area_id'] : ($cPrev['area_id'] ? (int)$cPrev['area_id'] : null);
            $newProyectoId = array_key_exists('proyecto_id', $jsonBody) && $jsonBody['proyecto_id'] !== '' && $jsonBody['proyecto_id'] !== null ? (int)$jsonBody['proyecto_id'] : $curProyId;

            // Comparar y auditar cada cambio con valores anteriores y nuevos
            $cambios = [];
            $valoresAnteriores = [];
            $valoresNuevos = [];

            if ($newResponsable !== $cPrev['responsable_nombre']) {
                $cambios[] = "Responsable: " . ($cPrev['responsable_nombre'] ?: 'Sin asignar') . " -> " . ($newResponsable ?: 'Sin asignar');
                $valoresAnteriores['responsable_nombre'] = $cPrev['responsable_nombre'];
                $valoresNuevos['responsable_nombre'] = $newResponsable;
            }
            if ($newContratista !== $cPrev['contratista']) {
                $cambios[] = "Contratista: " . ($cPrev['contratista'] ?: 'Sin dato') . " -> " . ($newContratista ?: 'Sin dato');
                $valoresAnteriores['contratista'] = $cPrev['contratista'];
                $valoresNuevos['contratista'] = $newContratista;
            }
            if ($newInterventoria !== $cPrev['interventoria_nombre']) {
                $cambios[] = "Interventoría: " . ($cPrev['interventoria_nombre'] ?: 'Sin dato') . " -> " . ($newInterventoria ?: 'Sin dato');
                $valoresAnteriores['interventoria_nombre'] = $cPrev['interventoria_nombre'];
                $valoresNuevos['interventoria_nombre'] = $newInterventoria;
            }
            if ($newDuracion !== $cPrev['duracion_texto']) {
                $cambios[] = "Duración: " . ($cPrev['duracion_texto'] ?: 'Sin dato') . " -> " . ($newDuracion ?: 'Sin dato');
                $valoresAnteriores['duracion_texto'] = $cPrev['duracion_texto'];
                $valoresNuevos['duracion_texto'] = $newDuracion;
            }
            if ($newAreaId !== ($cPrev['area_id'] ? (int)$cPrev['area_id'] : null)) {
                $cambios[] = "Área ID: " . ($cPrev['area_id'] ?: 'Sin área') . " -> " . ($newAreaId ?: 'Sin área');
                $valoresAnteriores['area_id'] = $cPrev['area_id'];
                $valoresNuevos['area_id'] = $newAreaId;
            }
            if ($newProyectoId !== $curProyId) {
                $stmtNewP = $pdo->prepare("SELECT codigo FROM despacho_proyectos WHERE id = ?");
                $stmtNewP->execute([$newProyectoId]);
                $newCodP = $stmtNewP->fetchColumn() ?: 'Sin Proyecto';
                $cambios[] = "Proyecto: Proy. $curProyCodigo -> Proy. $newCodP";
                $valoresAnteriores['proyecto_id'] = $curProyId;
                $valoresNuevos['proyecto_id'] = $newProyectoId;
            }
            $oldValIniNum = $cPrev['valor_inicial'] !== null ? (float)$cPrev['valor_inicial'] : null;
            if ($newValIni !== $oldValIniNum) {
                $cambios[] = "Valor inicial: $" . number_format((float)$oldValIniNum, 0, ',', '.') . " -> $" . number_format((float)$newValIni, 0, ',', '.');
                $valoresAnteriores['valor_inicial'] = $oldValIniNum;
                $valoresNuevos['valor_inicial'] = $newValIni;
            }
            $oldValAdicNum = $cPrev['valor_adiciones'] !== null ? (float)$cPrev['valor_adiciones'] : null;
            if ($newValAdic !== $oldValAdicNum) {
                $cambios[] = "Valor adiciones: $" . number_format((float)$oldValAdicNum, 0, ',', '.') . " -> $" . number_format((float)$newValAdic, 0, ',', '.');
                $valoresAnteriores['valor_adiciones'] = $oldValAdicNum;
                $valoresNuevos['valor_adiciones'] = $newValAdic;
            }
            $oldValTotNum = $cPrev['valor_total'] !== null ? (float)$cPrev['valor_total'] : null;
            if ($newValTot !== $oldValTotNum) {
                $cambios[] = "Valor total: $" . number_format((float)$oldValTotNum, 0, ',', '.') . " -> $" . number_format((float)$newValTot, 0, ',', '.');
                $valoresAnteriores['valor_total'] = $oldValTotNum;
                $valoresNuevos['valor_total'] = $newValTot;
            }
            if ($newFechaIniTxt !== $cPrev['fecha_inicio_texto']) {
                $cambios[] = "Fecha inicio: " . ($cPrev['fecha_inicio_texto'] ?: 'Sin dato') . " -> " . ($newFechaIniTxt ?: 'Sin dato');
                $valoresAnteriores['fecha_inicio_texto'] = $cPrev['fecha_inicio_texto'];
                $valoresNuevos['fecha_inicio_texto'] = $newFechaIniTxt;
            }
            if ($newFechaFinIniTxt !== $cPrev['fecha_fin_inicial_texto']) {
                $cambios[] = "Fecha fin inicial: " . ($cPrev['fecha_fin_inicial_texto'] ?: 'Sin dato') . " -> " . ($newFechaFinIniTxt ?: 'Sin dato');
                $valoresAnteriores['fecha_fin_inicial_texto'] = $cPrev['fecha_fin_inicial_texto'];
                $valoresNuevos['fecha_fin_inicial_texto'] = $newFechaFinIniTxt;
            }
            if ($newFechaFinVigTxt !== $cPrev['fecha_fin_vigente_texto']) {
                $cambios[] = "Fecha terminación final: " . ($cPrev['fecha_fin_vigente_texto'] ?: 'Sin dato') . " -> " . ($newFechaFinVigTxt ?: 'Sin dato');
                $valoresAnteriores['fecha_fin_vigente_texto'] = $cPrev['fecha_fin_vigente_texto'];
                $valoresNuevos['fecha_fin_vigente_texto'] = $newFechaFinVigTxt;
            }
            if ($newEstadoCon !== $cPrev['estado_contractual']) {
                $cambios[] = "Estado contractual: " . $cPrev['estado_contractual'] . " -> " . $newEstadoCon;
                $valoresAnteriores['estado_contractual'] = $cPrev['estado_contractual'];
                $valoresNuevos['estado_contractual'] = $newEstadoCon;
            }
            $oldFisNum = $cPrev['ejecucion_fisica_pct'] !== null ? (float)$cPrev['ejecucion_fisica_pct'] : null;
            $deltaFis = null;
            if ($newFisPct !== $oldFisNum) {
                $deltaFis = ($newFisPct !== null && $oldFisNum !== null) ? round($newFisPct - $oldFisNum, 2) : null;
                $deltaFisTxt = $deltaFis !== null ? ($deltaFis < 0 ? " (Disminuyó " . abs($deltaFis) . "%)" : " (Aumentó +{$deltaFis}%)") : '';
                $cambios[] = "Ejecución física: " . ($oldFisNum !== null ? $oldFisNum . '%' : ($cPrev['ejecucion_fisica_texto'] ?: 'Sin dato')) . " -> " . ($newFisPct !== null ? $newFisPct . '%' : 'Sin dato') . $deltaFisTxt;
                $valoresAnteriores['ejecucion_fisica_pct'] = $oldFisNum;
                $valoresNuevos['ejecucion_fisica_pct'] = $newFisPct;
            }
            $oldFinNum = $cPrev['ejecucion_financiera_pct'] !== null ? (float)$cPrev['ejecucion_financiera_pct'] : null;
            $deltaFin = null;
            if ($newFinPct !== $oldFinNum) {
                $deltaFin = ($newFinPct !== null && $oldFinNum !== null) ? round($newFinPct - $oldFinNum, 2) : null;
                $deltaFinTxt = $deltaFin !== null ? ($deltaFin < 0 ? " (Disminuyó " . abs($deltaFin) . "%)" : " (Aumentó +{$deltaFin}%)") : '';
                $cambios[] = "Ejecución financiera: " . ($oldFinNum !== null ? $oldFinNum . '%' : ($cPrev['ejecucion_financiera_texto'] ?: 'Sin dato')) . " -> " . ($newFinPct !== null ? $newFinPct . '%' : 'Sin dato') . $deltaFinTxt;
                $valoresAnteriores['ejecucion_financiera_pct'] = $oldFinNum;
                $valoresNuevos['ejecucion_financiera_pct'] = $newFinPct;
            }
            if ($newAlertas !== ($cPrev['alertas_contrato'] ?: '')) {
                $cambios[] = "Alertas del contrato: " . ($newAlertas ? (strlen($newAlertas) > 60 ? substr($newAlertas, 0, 57) . '...' : $newAlertas) : 'Sin alertas');
                $valoresAnteriores['alertas_contrato'] = $cPrev['alertas_contrato'];
                $valoresNuevos['alertas_contrato'] = $newAlertas;
            }

            if (empty($cambios)) {
                echo json_encode(['success' => true, 'message' => 'No se detectaron cambios (los valores son idénticos a los actuales).', 'cambios' => []], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $newEstadoReportado = in_array($newEstadoCon, ['TERMINADO', 'FINALIZADO', 'LIQUIDADO'], true)
                ? 'Finalizado'
                : $cPrev['estado_reportado_texto'];

            // Actualizar tabla principal despacho_contratos
            $stmtUpdC = $pdo->prepare("UPDATE despacho_contratos SET
                contratista = ?,
                interventoria_nombre = ?,
                responsable_nombre = ?,
                area_id = ?,
                duracion_texto = ?,
                valor_inicial = ?,
                valor_adiciones = ?,
                valor_total = ?,
                fecha_inicio_texto = ?,
                fecha_inicio_iso = ?,
                fecha_fin_inicial_texto = ?,
                fecha_fin_inicial_iso = ?,
                fecha_fin_vigente_texto = ?,
                fecha_fin_vigente_iso = ?,
                estado_contractual = ?,
                estado_reportado_texto = ?,
                ejecucion_fisica_pct = ?,
                ejecucion_fisica_texto = ?,
                ejecucion_financiera_pct = ?,
                ejecucion_financiera_texto = ?,
                alertas_contrato = ?,
                alerta_consolidada = COALESCE(?, alerta_consolidada),
                updated_at = NOW()
                WHERE id = ?");

            $stmtUpdC->execute([
                $newContratista ?: null,
                $newInterventoria ?: null,
                $newResponsable ?: null,
                $newAreaId,
                $newDuracion ?: null,
                $newValIni,
                $newValAdic,
                $newValTot,
                $newFechaIniTxt ?: null,
                $newFechaIniIso,
                $newFechaFinIniTxt ?: null,
                $newFechaFinIniIso,
                $newFechaFinVigTxt ?: null,
                $newFechaFinVigIso,
                $newEstadoCon,
                $newEstadoReportado,
                $newFisPct,
                $newFisTxt ?: null,
                $newFinPct,
                $newFinTxt ?: null,
                $newAlertas ?: null,
                $newAlertas ?: null,
                $contratoId
            ]);

            // Actualizar proyecto en despacho_contrato_proyectos si cambió
            if ($newProyectoId !== $curProyId) {
                $pdo->prepare("DELETE FROM despacho_contrato_proyectos WHERE contrato_id = ?")->execute([$contratoId]);
                if ($newProyectoId > 0) {
                    $pdo->prepare("INSERT INTO despacho_contrato_proyectos (contrato_id, proyecto_id, nota_asociacion) VALUES (?, ?, 'Asociado desde edición de contrato en Despacho')")
                        ->execute([$contratoId, $newProyectoId]);
                }
            }

            // Actualizar obligaciones si cambió responsable
            if ($newResponsable !== $cPrev['responsable_nombre']) {
                $pdo->prepare("UPDATE despacho_obligaciones_reporte SET responsable_nombre = ? WHERE contrato_id = ?")
                    ->execute([$newResponsable, $contratoId]);
            }

            // Registrar en despacho_bitacora con fecha exacta, actor, descripción legible y JSON anterior/nuevo
            $detalleBitacora = implode(' | ', $cambios);
            despachoLogBitacora(
                $pdo,
                $contratoId,
                'CONTRATO',
                $contratoId,
                'EDICION_CONTRATO',
                'USUARIO',
                (int)$currentUser['id'],
                $currentUser['nombre'],
                $valoresAnteriores,
                $valoresNuevos,
                $detalleBitacora
            );

            // Registrar versión en despacho_versiones_reporte para que se refleje de inmediato en el tracking y timeline cronológico
            $nuevaVersionNum = (int)$pdo->query("SELECT COALESCE(MAX(numero_version), 0) + 1 FROM despacho_versiones_reporte WHERE contrato_id = $contratoId")->fetchColumn();
            $stmtInsV = $pdo->prepare("INSERT INTO despacho_versiones_reporte (
                reporte_id, contrato_id, periodo_id, numero_version, tipo_mensaje,
                mensaje1_texto_original, mensaje2_texto_original, datos_estructurados_json,
                fecha_reporte_texto, fecha_reporte_iso, fecha_fin_reportada_texto, fecha_fin_reportada_iso,
                ejecucion_fisica_texto, ejecucion_fisica_pct,
                ejecucion_financiera_texto, ejecucion_financiera_pct, ejecucion_financiera_valor,
                estado_reportado_texto, observaciones_reporte, alertas_contrato, reportado_por, ip_origen, fuente_version, estado_version
            ) VALUES (
                NULL, ?, NULL, ?, 'EDICION_DESPACHO',
                ?, ?, ?,
                CURDATE(), CURDATE(), ?, ?,
                ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?, ?, 'Edición de datos Despacho', 'APROBADO'
            )");

            $datosHistJson = json_encode([
                'cambios' => $cambios,
                'resumen_cambios' => $cambios,
                'modificado_por' => $currentUser['nombre'],
                'ejecucion_fisica_anterior_pct' => $oldFisNum,
                'variacion_fisica_pct' => $deltaFis,
                'ejecucion_financiera_anterior_pct' => $oldFinNum,
                'variacion_financiera_pct' => $deltaFin,
                'valores_anteriores' => $valoresAnteriores,
                'valores_nuevos' => $valoresNuevos
            ], JSON_UNESCAPED_UNICODE);

            $stmtInsV->execute([
                $contratoId, $nuevaVersionNum,
                "Ficha contractual actualizada desde panel Despacho",
                "Ajuste registrado por {$currentUser['nombre']}: $detalleBitacora",
                $datosHistJson,
                $newFechaFinVigTxt, $newFechaFinVigIso,
                $newFisTxt, $newFisPct,
                $newFinTxt, $newFinPct, null,
                $newEstadoCon, $detalleBitacora, $newAlertas ?: null, $currentUser['nombre'],
                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Contrato actualizado y registrado en la bitácora de auditoría correctamente.',
                'cambios' => $cambios,
                'total_cambios' => count($cambios)
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

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
