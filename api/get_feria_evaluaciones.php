<?php
/**
 * API Endpoint: Evaluaciones de la Feria Agroambiental y Concursos Sumapaz
 * Retorna el listado consolidado de evaluaciones realizadas (Feria Agroambiental y Roles Generales),
 * métricas de comportamiento de puntaje, promedios por concurso y estadísticas globales.
 */
@ini_set('display_errors', '0');
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_config.php';

$isLocal = (php_sapi_name() === 'cli') || (isset($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']));
$rolId = isset($_SESSION['rol_id']) ? (int)$_SESSION['rol_id'] : null;

// Permitir acceso a usuarios autenticados o entorno local
if (!$isLocal && !isset($_SESSION['user_id']) && !in_array($rolId, [1, 13, 14], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
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
        $cuenca = $cuencasVeredasRef[$vClean] ?? 'Sumapaz';
        return [$vUpper ?: 'Sin vereda', $cuenca];
    };

    $evaluaciones = [];
    $juradosUnicos = [];

    // 1. Obtener evaluaciones de la Feria Agroambiental (Rol 13 / resultado_concurso_AMBIENTAL)
    try {
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
                p.numero_documento,
                p.nombre_predio,
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
            if (!empty($row['cuenca'])) {
                $cuencaNorm = $row['cuenca'];
            }

            $score = null;
            $json = json_decode($row['resultados_json'] ?? '', true);
            if (is_array($json)) {
                if (isset($json['totalScore'])) {
                    $score = (float)$json['totalScore'];
                } elseif (isset($json['puntaje_total'])) {
                    $score = (float)$json['puntaje_total'];
                }
            }

            $jurado = trim((string)($row['nombre_inscriptor'] ?? ''));
            if ($jurado === '') {
                $jurado = 'Jurado Agroambiental';
            }
            $juradosUnicos[strtoupper($jurado)] = true;

            $nombreProd = trim(($row['nombre_productor'] ?? '') . ' ' . ($row['apellido_productor'] ?? ''));
            if ($nombreProd === '') {
                $nombreProd = 'Productor #' . $row['id_productor_predio'];
            }

            $fechaStr = !empty($row['fecha']) ? $row['fecha'] : ($row['created_at'] ?? '');

            $evaluaciones[] = [
                'uid' => 'amb_' . $row['id'],
                'raw_id' => (int)$row['id'],
                'fuente' => 'ambiental',
                'fuente_label' => 'Feria Agroambiental (Rol 13)',
                'id_participante' => (int)$row['id_productor_predio'],
                'concursante' => $nombreProd,
                'documento' => trim((string)($row['numero_documento'] ?? '')),
                'vereda' => $veredaNorm,
                'cuenca' => $cuencaNorm,
                'predio' => trim((string)($row['nombre_predio'] ?? '')),
                'id_concurso' => (int)$row['id_concurso'],
                'concurso' => trim((string)($row['nombre_concurso'] ?: ('Concurso #' . $row['id_concurso']))),
                'categoria' => trim((string)($row['categoria'] ?: 'Agroambiental')),
                'jurado' => $jurado,
                'puntaje' => $score !== null ? round($score, 1) : 0,
                'puntaje_max' => 100,
                'fecha' => $fechaStr,
                'coordenadas' => trim((string)($row['coordenadas'] ?? '')),
                'tiene_firma_jurado' => (bool)$row['tiene_firma_jurado'],
                'tiene_firma_concursante' => (bool)$row['tiene_firma_concursante'],
                'tiene_fotos' => (bool)$row['tiene_fotos'],
                'observaciones' => trim((string)($row['observaciones'] ?? '')),
            ];
        }
    } catch (Exception $eAmb) {
        // Tabla ambiental vacía o no disponible
    }

    // 2. Obtener evaluaciones de Concursos Generales (evaluaciones)
    try {
        $sqlGen = '
            SELECT 
                e.id,
                e.concurso_participacion_id,
                e.nombre_jurado,
                e.puntaje_total,
                e.resultado_estado,
                e.observaciones,
                e.desagregado_puntaje,
                (e.firma_jurado IS NOT NULL AND e.firma_jurado != "") AS tiene_firma_jurado,
                (e.firma_participante IS NOT NULL AND e.firma_participante != "") AS tiene_firma_concursante,
                (e.imagen_id IS NOT NULL) AS tiene_fotos,
                e.created_at,
                fp.id as participante_id,
                fp.nombre_completo,
                fp.documento_identidad,
                fp.vereda,
                c.id as concurso_id,
                c.title as nombre_concurso,
                c.category as categoria,
                c.total_max_points
            FROM evaluaciones e
            JOIN concurso_participaciones cp ON cp.id = e.concurso_participacion_id
            JOIN feria_participantes fp ON fp.id = cp.participante_id
            JOIN concursos c ON c.id = cp.concurso_id
            ORDER BY e.id DESC
        ';
        $stmtGen = $pdo->query($sqlGen);
        while ($row = $stmtGen->fetch(PDO::FETCH_ASSOC)) {
            list($veredaNorm, $cuencaNorm) = $normVereda($row['vereda'] ?? '');

            $jurado = trim((string)($row['nombre_jurado'] ?? ''));
            if ($jurado === '') {
                $jurado = 'Jurado General';
            }
            $juradosUnicos[strtoupper($jurado)] = true;

            $maxPts = (int)($row['total_max_points'] ?: 100);
            $evaluaciones[] = [
                'uid' => 'gen_' . $row['id'],
                'raw_id' => (int)$row['id'],
                'fuente' => 'general',
                'fuente_label' => 'Concurso General Estándar',
                'id_participante' => (int)$row['participante_id'],
                'concursante' => trim((string)$row['nombre_completo']),
                'documento' => trim((string)$row['documento_identidad']),
                'vereda' => $veredaNorm,
                'cuenca' => $cuencaNorm,
                'predio' => '',
                'id_concurso' => (int)$row['concurso_id'],
                'concurso' => trim((string)$row['nombre_concurso']),
                'categoria' => trim((string)$row['categoria']),
                'jurado' => $jurado,
                'puntaje' => (float)$row['puntaje_total'],
                'puntaje_max' => $maxPts,
                'fecha' => (string)$row['created_at'],
                'coordenadas' => '',
                'tiene_firma_jurado' => (bool)$row['tiene_firma_jurado'],
                'tiene_firma_concursante' => (bool)$row['tiene_firma_concursante'],
                'tiene_fotos' => (bool)$row['tiene_fotos'],
                'observaciones' => trim((string)($row['observaciones'] ?? '')),
            ];
        }
    } catch (Exception $eGen) {
        // Error consultando evaluaciones generales
    }

    // 3. Cálculo de métricas y comportamiento por concurso
    $concursoStats = [];
    $totalEvaluaciones = count($evaluaciones);
    $sumaPuntajes = 0;
    $puntajeMaxGlobal = 0;
    $countAmbiental = 0;
    $countGeneral = 0;

    foreach ($evaluaciones as $ev) {
        $cKey = $ev['fuente'] . '_' . $ev['id_concurso'];
        $p = (float)$ev['puntaje'];
        $sumaPuntajes += $p;
        if ($p > $puntajeMaxGlobal) {
            $puntajeMaxGlobal = $p;
        }

        if ($ev['fuente'] === 'ambiental') {
            $countAmbiental++;
        } else {
            $countGeneral++;
        }

        if (!isset($concursoStats[$cKey])) {
            $concursoStats[$cKey] = [
                'id_concurso' => $ev['id_concurso'],
                'fuente' => $ev['fuente'],
                'fuente_label' => $ev['fuente_label'],
                'concurso' => $ev['concurso'],
                'categoria' => $ev['categoria'],
                'puntaje_max_posible' => $ev['puntaje_max'],
                'conteo' => 0,
                'suma_puntaje' => 0,
                'puntaje_min' => $p,
                'puntaje_max' => $p,
                'puntajes' => [],
            ];
        }

        $concursoStats[$cKey]['conteo']++;
        $concursoStats[$cKey]['suma_puntaje'] += $p;
        $concursoStats[$cKey]['puntajes'][] = $p;
        if ($p < $concursoStats[$cKey]['puntaje_min']) {
            $concursoStats[$cKey]['puntaje_min'] = $p;
        }
        if ($p > $concursoStats[$cKey]['puntaje_max']) {
            $concursoStats[$cKey]['puntaje_max'] = $p;
        }
    }

    $resumenConcursos = [];
    foreach ($concursoStats as $k => $c) {
        $prom = $c['conteo'] > 0 ? round($c['suma_puntaje'] / $c['conteo'], 1) : 0;
        $resumenConcursos[] = [
            'id_concurso' => $c['id_concurso'],
            'fuente' => $c['fuente'],
            'fuente_label' => $c['fuente_label'],
            'concurso' => $c['concurso'],
            'categoria' => $c['categoria'],
            'puntaje_max_posible' => $c['puntaje_max_posible'],
            'total_evaluaciones' => $c['conteo'],
            'puntaje_promedio' => $prom,
            'puntaje_min' => round($c['puntaje_min'], 1),
            'puntaje_max' => round($c['puntaje_max'], 1),
            'puntajes' => $c['puntajes'],
        ];
    }

    // Ordenar concursos por cantidad de evaluaciones descendente, luego promedio descendente
    usort($resumenConcursos, function($a, $b) {
        if ($b['total_evaluaciones'] === $a['total_evaluaciones']) {
            return $b['puntaje_promedio'] <=> $a['puntaje_promedio'];
        }
        return $b['total_evaluaciones'] <=> $a['total_evaluaciones'];
    });

    $promedioGeneral = $totalEvaluaciones > 0 ? round($sumaPuntajes / $totalEvaluaciones, 1) : 0;

    echo json_encode([
        'success' => true,
        'kpis' => [
            'total_evaluaciones' => $totalEvaluaciones,
            'promedio_general' => $promedioGeneral,
            'puntaje_max_global' => round($puntajeMaxGlobal, 1),
            'concursos_evaluados' => count($resumenConcursos),
            'jurados_activos' => count($juradosUnicos),
            'total_ambiental' => $countAmbiental,
            'total_general' => $countGeneral,
        ],
        'resumen_concursos' => $resumenConcursos,
        'evaluaciones' => $evaluaciones,
        'generado' => date('Y-m-d H:i:s'),
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al consultar evaluaciones: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
