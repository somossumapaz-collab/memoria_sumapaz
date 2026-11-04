<?php
/**
 * API Endpoint: Evaluaciones Oficiales de la Feria Agroambiental Sumapaz (Rol 13)
 * Origen exclusivo: Tablas _AMBIENTAL
 * - resultado_concurso_AMBIENTAL (calificaciones y actas de juzgamiento)
 * - productor_predio_AMBIENTAL (productores y predios inscritos)
 * - estructura_concursos_AMBIENTAL (catálogo oficial de 24 concursos en finca)
 */
@ini_set('display_errors', '0');
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_config.php';

$isLocal = (php_sapi_name() === 'cli') || (isset($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']));
$rolId = isset($_SESSION['rol_id']) ? (int)$_SESSION['rol_id'] : null;

if (!$isLocal && !isset($_SESSION['user_id']) && !in_array($rolId, [1, 13, 14], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // 1. Catálogo completo de concursos de la Feria Agroambiental
    $concursosMap = [];
    $stmtC = $pdo->query("SELECT id, categoria, nombre_concurso, preguntas_json FROM estructura_concursos_AMBIENTAL WHERE id != 25 ORDER BY id ASC");
    while ($r = $stmtC->fetch(PDO::FETCH_ASSOC)) {
        $cid = (int)$r['id'];
        $pj = json_decode($r['preguntas_json'] ?? '', true);
        $catCriterios = (is_array($pj) && isset($pj['criterios']) && is_array($pj['criterios'])) ? $pj['criterios'] : [];
        $catMaxPoints = (is_array($pj) && isset($pj['total_max_points'])) ? (float)$pj['total_max_points'] : 0;
        $concursosMap[$cid] = [
            'id_concurso' => $cid,
            'concurso' => trim($r['nombre_concurso']),
            'categoria' => trim($r['categoria']),
            'criterios_catalogo' => $catCriterios,
            'max_points_catalogo' => $catMaxPoints,
            'total_evaluaciones' => 0,
            'suma_puntajes' => 0,
            'puntaje_promedio' => 0,
            'puntaje_min' => null,
            'puntaje_max' => null,
            'puntajes' => [],
        ];
    }

    $cuencasVeredasRef = [
        'SAN JOSE' => 'Rio Sumapaz', 'NUEVA GRANADA' => 'Rio Sumapaz', 'CONCEPCION' => 'Rio Sumapaz',
        'TUNAL ALTO' => 'Rio Sumapaz', 'LAGUNITAS' => 'Rio Sumapaz', 'TUNAL BAJO' => 'Rio Sumapaz',
        'LA UNIÓN' => 'Rio Sumapaz', 'LA UNION' => 'Rio Sumapaz', 'CHORRERAS' => 'Rio Sumapaz',
        'SANTO DOMINGO' => 'Rio Sumapaz', 'LAS VEGAS' => 'Rio Sumapaz', 'SAN JUAN' => 'Rio Sumapaz',
        'CAPITOLIO' => 'Rio Sumapaz', 'EL TOLDO' => 'Rio Sumapaz', 'TOLDO' => 'Rio Sumapaz',
        'SAN ANTONIO' => 'Rio Sumapaz',

        'LAS SOPAS' => 'Rio Blanco', 'LOS RIOS' => 'Rio Blanco', 'LAS PALMAS' => 'Rio Blanco',
        'LAS ANIMAS' => 'Rio Blanco', 'LASANIMAS' => 'Rio Blanco', 'LAS AURAS' => 'Rio Blanco',
        'LASURAS' => 'Rio Blanco', 'NAZARETH' => 'Rio Blanco',
        'NAZARET' => 'Rio Blanco', 'TAQUECITOS' => 'Rio Blanco', 'PENALISA' => 'Rio Blanco',
        'PEÑALISA' => 'Rio Blanco', 'LAGUNA VERDE' => 'Rio Blanco', 'RAIZAL' => 'Rio Blanco',
        'EL RAIZAL' => 'Rio Blanco', 'SANTA ROSA' => 'Rio Blanco', 'SANTA ROSA BAJA' => 'Rio Blanco',
        'SANTA ROSA ALTA' => 'Rio Blanco', 'BETANIA' => 'Rio Blanco', 'EL ITSMO' => 'Rio Blanco',
        'ITSMO' => 'Rio Blanco', 'ISTMO' => 'Rio Blanco', 'EL TABACO' => 'Rio Blanco',
        'TABACO' => 'Rio Blanco'
    ];

    $normVereda = function($v) use ($cuencasVeredasRef) {
        $vUpper = strtoupper(trim((string)$v));
        $vUpper = trim(preg_replace('/\bURBANO\b/i', '', $vUpper));
        if ($vUpper === 'LA UNION') $vUpper = 'LA UNIÓN';
        if ($vUpper === 'CEDRAL') $vUpper = 'NAZARETH';
        $vClean = preg_replace('~[^\pL\pN\s]+~u', '', $vUpper);
        $vClean = strtoupper(trim(preg_replace('/\s+/', ' ', (string)$vClean)));
        $cuenca = $cuencasVeredasRef[$vClean] ?? 'Rio Sumapaz';
        return [$vUpper ?: 'Sin vereda', $cuenca];
    };

    // 2. Consultar evaluaciones en resultado_concurso_AMBIENTAL
    $evaluaciones = [];
    $juradosUnicos = [];
    $productoresUnicos = [];
    $sumaPuntajesGlobal = 0;
    $puntajeMaxGlobal = 0;

    $sqlAmb = '
        SELECT 
            r.id,
            r.id_productor_predio,
            r.id_concurso,
            r.resultados_json,
            r.observaciones,
            r.fecha,
            r.coordenadas,
            r.created_at,
            (r.firma_evaluador IS NOT NULL AND r.firma_evaluador != "") AS tiene_firma_jurado,
            (r.firma_concursante IS NOT NULL AND r.firma_concursante != "") AS tiene_firma_concursante,
            (r.imagenes IS NOT NULL AND r.imagenes != "") AS tiene_fotos,
            p.nombre_productor,
            p.apellido_productor,
            p.tipo_documento,
            p.numero_documento,
            p.nombre_predio,
            p.extension_finca,
            p.vereda,
            p.cuenca,
            p.nombre_inscriptor,
            c.nombre_concurso,
            c.categoria
        FROM resultado_concurso_AMBIENTAL r
        LEFT JOIN productor_predio_AMBIENTAL p ON p.id = r.id_productor_predio
        LEFT JOIN estructura_concursos_AMBIENTAL c ON c.id = r.id_concurso
        ORDER BY r.id DESC
    ';

    $stmtAmb = $pdo->query($sqlAmb);
    while ($row = $stmtAmb->fetch(PDO::FETCH_ASSOC)) {
        list($veredaNorm, $cuencaNorm) = $normVereda($row['vereda'] ?? '');
        if (!empty($row['cuenca']) && strcasecmp($row['cuenca'], 'cedral') !== 0 && $row['cuenca'] !== 'Sin cuenca asignada') {
            $cuencaNorm = $row['cuenca'];
        }

        $cid = (int)$row['id_concurso'];
        $json = json_decode($row['resultados_json'] ?? '', true);
        if ($cid <= 0 && is_array($json) && !empty($json['contestId'])) {
            $cid = (int)$json['contestId'];
        }

        $score = 0;
        $puntajeMax = 0;
        $criteriosCount = 0;
        $catCriterios = $concursosMap[$cid]['criterios_catalogo'] ?? [];

        if (is_array($json)) {
            $rawCriterios = [];
            if (isset($json['calificaciones']) && is_array($json['calificaciones'])) {
                $rawCriterios = $json['calificaciones'];
            } elseif (isset($json['criteria']) && is_array($json['criteria'])) {
                $rawCriterios = $json['criteria'];
            }

            if (!empty($catCriterios) && !empty($rawCriterios)) {
                $normCritText = function ($s) {
                    $s = strtolower(trim((string)$s));
                    $s = strtr($s, [
                        'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',
                        'Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u','Ü'=>'u','Ñ'=>'n',
                        '¿'=>'','?'=>''
                    ]);
                    $s = preg_replace('/[^a-z0-9\s\.]/', '', $s);
                    $s = preg_replace('/\s+/', ' ', $s);
                    return trim($s);
                };
                $matchCritScore = function (array $raw, array $cat, int $idx) use ($normCritText) {
                    $rawName = $normCritText($raw['criterio'] ?? ($raw['name'] ?? ''));
                    $catName = $normCritText($cat['name'] ?? '');
                    if ($rawName === '' || $catName === '') return 0;
                    if ($rawName === $catName) return 100;
                    $minLen = min(strlen($rawName), strlen($catName));
                    if ($minLen >= 8 && strncmp($rawName, $catName, $minLen) === 0) {
                        return 90 + min(9, (int)($minLen / 10));
                    }
                    $numPrefix = ($idx + 1) . '.';
                    if (strpos($rawName, $numPrefix) === 0 && strpos($catName, $numPrefix) === 0) {
                        similar_text($rawName, substr($catName, 0, strlen($rawName)), $pct);
                        if ($pct >= 70) return (int)$pct;
                    }
                    return 0;
                };

                $sortedRaw = $rawCriterios;
                usort($sortedRaw, function ($a, $b) {
                    $idA = isset($a['criterionId']) ? (int)$a['criterionId'] : (isset($a['id']) ? (int)$a['id'] : 0);
                    $idB = isset($b['criterionId']) ? (int)$b['criterionId'] : (isset($b['id']) ? (int)$b['id'] : 0);
                    return $idA <=> $idB;
                });

                $N = count($catCriterios);
                $M = count($sortedRaw);
                $bestStart = -1;
                $bestWindowScore = -1;

                if ($M >= $N) {
                    for ($start = 0; $start <= $M - $N; $start++) {
                        $sumSc = 0;
                        $allOk = true;
                        for ($k = 0; $k < $N; $k++) {
                            $sc = $matchCritScore($sortedRaw[$start + $k], $catCriterios[$k], $k);
                            if ($sc < 70) {
                                $allOk = false;
                                break;
                            }
                            $sumSc += $sc;
                        }
                        if ($allOk && $sumSc > $bestWindowScore) {
                            $bestWindowScore = $sumSc;
                            $bestStart = $start;
                        }
                    }
                }

                $usedRaw = [];
                $cleanCalificaciones = [];
                $calcScore = 0.0;
                $calcMax = 0.0;
                $hasMatched = false;

                for ($k = 0; $k < $N; $k++) {
                    $catCr = $catCriterios[$k];
                    $pMax = (float)($catCr['max_points_value'] ?? 3);
                    $matchedCr = null;
                    if ($bestStart >= 0) {
                        $matchedCr = $sortedRaw[$bestStart + $k];
                    } else {
                        $bestIdx = -1;
                        $bestSc = -1;
                        foreach ($sortedRaw as $i => $cr) {
                            if (isset($usedRaw[$i])) continue;
                            $sc = $matchCritScore($cr, $catCr, $k);
                            if ($sc > $bestSc) {
                                $bestSc = $sc;
                                $bestIdx = $i;
                            }
                        }
                        if ($bestIdx >= 0 && $bestSc >= 70) {
                            $usedRaw[$bestIdx] = true;
                            $matchedCr = $sortedRaw[$bestIdx];
                        }
                    }

                    $pObt = 0.0;
                    $cId = $catCr['id'] ?? ($k + 1);
                    if ($matchedCr !== null) {
                        $hasMatched = true;
                        $cId = $matchedCr['criterionId'] ?? ($matchedCr['id'] ?? $cId);
                        $pObt = isset($matchedCr['puntaje']) ? (float)$matchedCr['puntaje'] : (isset($matchedCr['score']) ? (float)$matchedCr['score'] : (isset($matchedCr['puntaje_obtenido']) ? (float)$matchedCr['puntaje_obtenido'] : 0.0));
                        if ($pObt > $pMax) $pObt = $pMax;
                        if ($pObt < 0) $pObt = 0.0;
                    }
                    $cleanCalificaciones[] = [
                        'criterionId' => $cId,
                        'criterio' => $catCr['name'] ?? ('Criterio ' . ($k + 1)),
                        'puntaje' => $pObt,
                        'puntajeMaximo' => $pMax,
                    ];
                    $calcScore += $pObt;
                    $calcMax += $pMax;
                }

                $score = $hasMatched ? $calcScore : (isset($json['totalScore']) ? (float)$json['totalScore'] : 0.0);
                $puntajeMax = $calcMax;
                $criteriosCount = $N;

                if ($hasMatched && (count($rawCriterios) !== $N || (float)($json['totalScore'] ?? -1) !== (float)$calcScore)) {
                    $json['totalScore'] = $calcScore;
                    $json['calificaciones'] = $cleanCalificaciones;
                    $pdo->prepare("UPDATE resultado_concurso_AMBIENTAL SET resultados_json = ? WHERE id = ?")
                        ->execute([json_encode($json, JSON_UNESCAPED_UNICODE), (int)$row['id']]);
                }
            } else {
                if (isset($json['totalScore'])) {
                    $score = (float)$json['totalScore'];
                } elseif (isset($json['puntaje_total'])) {
                    $score = (float)$json['puntaje_total'];
                }
                foreach ($rawCriterios as $cr) {
                    $pMax = isset($cr['puntajeMaximo']) ? (float)$cr['puntajeMaximo'] : (isset($cr['maxPoints']) ? (float)$cr['maxPoints'] : (isset($cr['puntaje_maximo']) ? (float)$cr['puntaje_maximo'] : 0));
                    $puntajeMax += $pMax;
                    $criteriosCount++;
                }
            }
        }

        if ($puntajeMax <= 0) {
            $puntajeMax = !empty($concursosMap[$cid]['max_points_catalogo']) ? (float)$concursosMap[$cid]['max_points_catalogo'] : 100;
        }
        if ($criteriosCount <= 0 && !empty($catCriterios)) {
            $criteriosCount = count($catCriterios);
        }

        $nombreProd = trim(($row['nombre_productor'] ?? '') . ' ' . ($row['apellido_productor'] ?? ''));
        if ($nombreProd === '') {
            $nombreProd = 'Productor #' . $row['id_productor_predio'];
        }

        // Jurado evaluador: prioridad a judgeName dentro de resultados_json
        $jurado = '';
        if (is_array($json) && !empty($json['judgeName'])) {
            $jurado = trim((string)$json['judgeName']);
        } elseif (!empty($row['nombre_inscriptor'])) {
            $jurado = trim((string)$row['nombre_inscriptor']);
        }
        if ($jurado === '') {
            $jurado = 'Jurado Agroambiental';
        }

        $juradosUnicos[strtoupper($jurado)] = true;
        $productoresUnicos[(int)$row['id_productor_predio']] = true;

        $cid = (int)$row['id_concurso'];
        if ($cid <= 0 && is_array($json) && !empty($json['contestId'])) {
            $cid = (int)$json['contestId'];
        }

        $cName = trim((string)($row['nombre_concurso'] ?? ''));
        if (empty($cName) && is_array($json) && !empty($json['concurso'])) {
            $cName = trim((string)$json['concurso']);
        }
        if (empty($cName)) {
            $cName = $concursosMap[$cid]['concurso'] ?? ('Concurso #' . $cid);
        }

        $cCat = trim((string)($row['categoria'] ?? ($concursosMap[$cid]['categoria'] ?? 'Feria Agroambiental')));

        // Fecha de evaluación: limpiar si traía coordenadas erróneas o tomar de resultados_json
        $fechaStr = trim((string)($row['fecha'] ?? ''));
        if (stripos($fechaStr, 'lat:') !== false || stripos($fechaStr, 'lon:') !== false || empty($fechaStr)) {
            if (is_array($json) && !empty($json['fecha']) && stripos($json['fecha'], 'lat:') === false) {
                $fechaStr = trim((string)$json['fecha']);
            } else {
                $fechaStr = $row['created_at'] ?? '';
            }
        }

        // Coordenadas GPS
        $coordenadasStr = trim((string)($row['coordenadas'] ?? ''));
        if (empty($coordenadasStr) || $coordenadasStr === 'N/A') {
            if (is_array($json) && !empty($json['coordenadas'])) {
                $coordenadasStr = trim((string)$json['coordenadas']);
            } elseif (!empty($row['fecha']) && (stripos($row['fecha'], 'lat:') !== false || stripos($row['fecha'], 'lon:') !== false)) {
                $coordenadasStr = trim((string)$row['fecha']);
            }
        }

        $evaluaciones[] = [
            'uid' => 'amb_' . $row['id'],
            'raw_id' => (int)$row['id'],
            'fuente' => 'ambiental',
            'fuente_label' => 'Feria Agroambiental (Rol 13)',
            'id_participante' => (int)$row['id_productor_predio'],
            'concursante' => $nombreProd,
            'documento' => trim((string)($row['numero_documento'] ?? '')),
            'tipo_documento' => trim((string)($row['tipo_documento'] ?? 'C.C.')),
            'vereda' => $veredaNorm,
            'cuenca' => $cuencaNorm,
            'predio' => trim((string)($row['nombre_predio'] ?? '')),
            'extension' => trim((string)($row['extension_finca'] ?? '')),
            'id_concurso' => $cid,
            'concurso' => $cName,
            'categoria' => $cCat,
            'jurado' => $jurado,
            'puntaje' => round($score, 1),
            'puntaje_max' => round($puntajeMax, 1),
            'criterios_count' => $criteriosCount,
            'fecha' => $fechaStr,
            'coordenadas' => $coordenadasStr,
            'tiene_firma_jurado' => (bool)$row['tiene_firma_jurado'],
            'tiene_firma_concursante' => (bool)$row['tiene_firma_concursante'],
            'tiene_fotos' => (bool)$row['tiene_fotos'],
            'observaciones' => trim((string)($row['observaciones'] ?? '')),
        ];

        // Acumular en catálogo de concursos
        if (isset($concursosMap[$cid])) {
            $concursosMap[$cid]['total_evaluaciones']++;
            $concursosMap[$cid]['suma_puntajes'] += $score;
            $concursosMap[$cid]['puntajes'][] = $score;
            if ($concursosMap[$cid]['puntaje_min'] === null || $score < $concursosMap[$cid]['puntaje_min']) {
                $concursosMap[$cid]['puntaje_min'] = $score;
            }
            if ($concursosMap[$cid]['puntaje_max'] === null || $score > $concursosMap[$cid]['puntaje_max']) {
                $concursosMap[$cid]['puntaje_max'] = $score;
            }
        }

        $sumaPuntajesGlobal += $score;
        if ($score > $puntajeMaxGlobal) {
            $puntajeMaxGlobal = $score;
        }
    }

    $totalEvaluaciones = count($evaluaciones);
    $promedioGlobal = $totalEvaluaciones > 0 ? round($sumaPuntajesGlobal / $totalEvaluaciones, 1) : 0;

    $resumenConcursos = [];
    $concursosConEvaluacion = 0;
    foreach ($concursosMap as $cid => $c) {
        $cnt = $c['total_evaluaciones'];
        $prom = $cnt > 0 ? round($c['suma_puntajes'] / $cnt, 1) : 0;
        if ($cnt > 0) $concursosConEvaluacion++;

        $resumenConcursos[] = [
            'id_concurso' => $cid,
            'concurso' => $c['concurso'],
            'categoria' => $c['categoria'],
            'total_evaluaciones' => $cnt,
            'puntaje_promedio' => $prom,
            'puntaje_min' => $c['puntaje_min'] !== null ? round($c['puntaje_min'], 1) : 0,
            'puntaje_max' => $c['puntaje_max'] !== null ? round($c['puntaje_max'], 1) : 0,
            'puntajes' => $c['puntajes'],
        ];
    }

    // Ordenar: primero los que tienen evaluaciones (mayor a menor), luego por id
    usort($resumenConcursos, function($a, $b) {
        if ($b['total_evaluaciones'] === $a['total_evaluaciones']) {
            if ($b['puntaje_promedio'] == $a['puntaje_promedio']) {
                return $a['id_concurso'] <=> $b['id_concurso'];
            }
            return $b['puntaje_promedio'] <=> $a['puntaje_promedio'];
        }
        return $b['total_evaluaciones'] <=> $a['total_evaluaciones'];
    });

    echo json_encode([
        'success' => true,
        'kpis' => [
            'total_evaluaciones' => $totalEvaluaciones,
            'promedio_general' => $promedioGlobal,
            'puntaje_max_global' => round($puntajeMaxGlobal, 1),
            'concursos_evaluados' => $concursosConEvaluacion,
            'total_concursos_catalogo' => count($concursosMap),
            'jurados_activos' => count($juradosUnicos),
            'productores_evaluados' => count($productoresUnicos),
        ],
        'resumen_concursos' => $resumenConcursos,
        'evaluaciones' => $evaluaciones,
        'generado' => date('Y-m-d H:i:s'),
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al consultar evaluaciones de la feria agroambiental: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
