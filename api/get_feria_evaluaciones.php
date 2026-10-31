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
    $stmtC = $pdo->query("SELECT id, categoria, nombre_concurso FROM estructura_concursos_AMBIENTAL WHERE id != 25 ORDER BY id ASC");
    while ($r = $stmtC->fetch(PDO::FETCH_ASSOC)) {
        $cid = (int)$r['id'];
        $concursosMap[$cid] = [
            'id_concurso' => $cid,
            'concurso' => trim($r['nombre_concurso']),
            'categoria' => trim($r['categoria']),
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

        $score = 0;
        $puntajeMax = 0;
        $criteriosCount = 0;
        $json = json_decode($row['resultados_json'] ?? '', true);
        if (is_array($json)) {
            if (isset($json['totalScore'])) {
                $score = (float)$json['totalScore'];
            } elseif (isset($json['puntaje_total'])) {
                $score = (float)$json['puntaje_total'];
            }

            // Desglose de calificaciones por criterio
            $rawCriterios = [];
            if (isset($json['calificaciones']) && is_array($json['calificaciones'])) {
                $rawCriterios = $json['calificaciones'];
            } elseif (isset($json['criteria']) && is_array($json['criteria'])) {
                $rawCriterios = $json['criteria'];
            }

            foreach ($rawCriterios as $cr) {
                $pMax = isset($cr['puntajeMaximo']) ? (float)$cr['puntajeMaximo'] : (isset($cr['maxPoints']) ? (float)$cr['maxPoints'] : (isset($cr['puntaje_maximo']) ? (float)$cr['puntaje_maximo'] : 0));
                $puntajeMax += $pMax;
                $criteriosCount++;
            }
        }

        if ($puntajeMax <= 0) {
            $puntajeMax = 100;
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
