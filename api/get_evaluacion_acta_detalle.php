<?php
/**
 * API Endpoint: Detalle completo de un Acta Oficial de Juzgamiento y Evaluación
 * Origen exclusivo: Tablas _AMBIENTAL (resultado_concurso_AMBIENTAL)
 */
@ini_set('display_errors', '0');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_config.php';

$isLocal = (php_sapi_name() === 'cli') || (isset($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']));
$rolId = isset($_SESSION['rol_id']) ? (int)$_SESSION['rol_id'] : null;

if (!$isLocal && !isset($_SESSION['user_id']) && !in_array($rolId, [1, 13, 14], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!function_exists('splitImages')) {
    function splitImages($raw) {
        $raw = trim((string)$raw);
        if ($raw === '') return [];
        if (strpos($raw, 'data:image') === false) return [];
        $parts = preg_split('/(?=data:image\/)/', $raw, -1, PREG_SPLIT_NO_EMPTY);
        $out = [];
        foreach ($parts as $p) {
            $p = rtrim(trim($p), ",;| \n\r\t");
            if (strpos($p, 'data:image/') === 0) $out[] = $p;
        }
        return $out;
    }
}

$rawUid = $_GET['uid'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($rawUid !== '') {
    if (strpos($rawUid, 'amb_') === 0) {
        $id = (int)substr($rawUid, 4);
    } else {
        $id = (int)$rawUid;
    }
}

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Identificador de evaluación inválido'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            r.*,
            p.nombre_productor, p.apellido_productor, p.tipo_documento, p.numero_documento,
            p.nombre_predio, p.extension_finca, p.vereda, p.cuenca, p.telefono, p.email,
            p.nombre_inscriptor, p.fecha_inscripcion,
            c.nombre_concurso, c.categoria, c.preguntas_json
        FROM resultado_concurso_AMBIENTAL r
        LEFT JOIN productor_predio_AMBIENTAL p ON p.id = r.id_productor_predio
        LEFT JOIN estructura_concursos_AMBIENTAL c ON c.id = r.id_concurso
        WHERE r.id = ?
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Evaluación agroambiental no encontrada'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $nombreCompleto = trim(($row['nombre_productor'] ?? '') . ' ' . ($row['apellido_productor'] ?? ''));
    if ($nombreCompleto === '') $nombreCompleto = 'Participante #' . $row['id_productor_predio'];

    $json = json_decode($row['resultados_json'] ?? '', true);
    if (!is_array($json)) {
        $json = [];
    }

    // 1. Jurado Evaluador: prioridad absoluta a judgeName de resultados_json
    $jurado = '';
    if (!empty($json['judgeName'])) {
        $jurado = trim((string)$json['judgeName']);
    } elseif (!empty($row['nombre_inscriptor'])) {
        $jurado = trim((string)$row['nombre_inscriptor']);
    }
    if ($jurado === '') $jurado = 'Jurado Agroambiental Oficial';

    // 2. Concurso y Categoría
    $concursoNom = trim((string)($row['nombre_concurso'] ?? ''));
    if (empty($concursoNom) && !empty($json['concurso'])) {
        $concursoNom = trim((string)$json['concurso']);
    }
    if (empty($concursoNom)) {
        $concursoNom = 'Concurso #' . $row['id_concurso'];
    }

    $categoriaNom = trim((string)($row['categoria'] ?? ''));
    if (empty($categoriaNom)) {
        $categoriaNom = 'Categoría Oficial Feria Agroambiental';
    }

    // 3. Fecha oficial: limpiar si contenía coordenadas o tomar de resultados_json
    $fecha = trim((string)($row['fecha'] ?? ''));
    if (stripos($fecha, 'lat:') !== false || stripos($fecha, 'lon:') !== false || empty($fecha)) {
        if (!empty($json['fecha']) && stripos($json['fecha'], 'lat:') === false) {
            $fecha = trim((string)$json['fecha']);
        } else {
            $fecha = $row['created_at'] ?? '';
        }
    }

    // 4. Coordenadas GPS
    $coords = trim((string)($row['coordenadas'] ?? ''));
    if (empty($coords) || $coords === 'N/A') {
        if (!empty($json['coordenadas'])) {
            $coords = trim((string)$json['coordenadas']);
        } elseif (!empty($row['fecha']) && (stripos($row['fecha'], 'lat:') !== false || stripos($row['fecha'], 'lon:') !== false)) {
            $coords = trim((string)$row['fecha']);
        }
    }

    // 5. Catálogo oficial de preguntas/criterios de estructura_concursos_AMBIENTAL
    $catalogoCriterios = [];
    if (!empty($row['preguntas_json'])) {
        $pj = json_decode($row['preguntas_json'], true);
        if (is_array($pj) && isset($pj['criterios']) && is_array($pj['criterios'])) {
            $catalogoCriterios = $pj['criterios'];
        }
    }

    $rawCriterios = [];
    if (isset($json['calificaciones']) && is_array($json['calificaciones'])) {
        $rawCriterios = $json['calificaciones'];
    } elseif (isset($json['criteria']) && is_array($json['criteria'])) {
        $rawCriterios = $json['criteria'];
    }

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

    $criteriaList = [];
    $maxPosible = 0;
    $calculatedTotal = 0;
    $hasMatchedScores = false;

    if (!empty($catalogoCriterios) && !empty($rawCriterios)) {
        $sortedRaw = $rawCriterios;
        usort($sortedRaw, function ($a, $b) {
            $idA = isset($a['criterionId']) ? (int)$a['criterionId'] : (isset($a['id']) ? (int)$a['id'] : 0);
            $idB = isset($b['criterionId']) ? (int)$b['criterionId'] : (isset($b['id']) ? (int)$b['id'] : 0);
            return $idA <=> $idB;
        });

        $N = count($catalogoCriterios);
        $M = count($sortedRaw);
        $bestStart = -1;
        $bestWindowScore = -1;

        if ($M >= $N) {
            for ($start = 0; $start <= $M - $N; $start++) {
                $sumSc = 0;
                $allOk = true;
                for ($k = 0; $k < $N; $k++) {
                    $sc = $matchCritScore($sortedRaw[$start + $k], $catalogoCriterios[$k], $k);
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
        for ($k = 0; $k < $N; $k++) {
            $catCr = $catalogoCriterios[$k];
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
                $hasMatchedScores = true;
                $cId = $matchedCr['criterionId'] ?? ($matchedCr['id'] ?? $cId);
                $pObt = isset($matchedCr['puntaje']) ? (float)$matchedCr['puntaje'] : (isset($matchedCr['score']) ? (float)$matchedCr['score'] : (isset($matchedCr['puntaje_obtenido']) ? (float)$matchedCr['puntaje_obtenido'] : 0.0));
                if ($pObt > $pMax) $pObt = $pMax;
                if ($pObt < 0) $pObt = 0.0;
            }

            $criterioNom = $catCr['name'] ?? ('Criterio ' . ($k + 1));
            $criteriaList[] = [
                'criterion_id' => $cId,
                'criterio' => $criterioNom,
                'descripcion' => $catCr['description'] ?? '',
                'puntaje_maximo' => $pMax,
                'puntaje_obtenido' => $pObt,
            ];
            $cleanCalificaciones[] = [
                'criterionId' => $cId,
                'criterio' => $criterioNom,
                'puntaje' => $pObt,
                'puntajeMaximo' => $pMax,
            ];
            $maxPosible += $pMax;
            $calculatedTotal += $pObt;
        }

        // Si el JSON en BD tenía criterios infiltrados o puntaje desfasado, corregir automáticamente
        if ($hasMatchedScores && (count($rawCriterios) !== $N || (float)($json['totalScore'] ?? -1) !== (float)$calculatedTotal)) {
            $json['totalScore'] = $calculatedTotal;
            $json['calificaciones'] = $cleanCalificaciones;
            $pdo->prepare("UPDATE resultado_concurso_AMBIENTAL SET resultados_json = ? WHERE id = ?")
                ->execute([json_encode($json, JSON_UNESCAPED_UNICODE), (int)$row['id']]);
        }
    } elseif (!empty($catalogoCriterios)) {
        foreach ($catalogoCriterios as $idx => $catCr) {
            $pMax = (float)($catCr['max_points_value'] ?? 3);
            $criteriaList[] = [
                'criterion_id' => $catCr['id'] ?? ($idx + 1),
                'criterio' => $catCr['name'] ?? ('Criterio ' . ($idx + 1)),
                'descripcion' => $catCr['description'] ?? '',
                'puntaje_maximo' => $pMax,
                'puntaje_obtenido' => null,
            ];
            $maxPosible += $pMax;
        }
    } elseif (!empty($rawCriterios)) {
        foreach ($rawCriterios as $idx => $cr) {
            $cId = $cr['criterionId'] ?? ($cr['id'] ?? ($idx + 1));
            $criterioNom = $cr['criterio'] ?? ($cr['name'] ?? ('Criterio ' . ($idx + 1)));
            $pMax = isset($cr['puntajeMaximo']) ? (float)$cr['puntajeMaximo'] : (isset($cr['maxPoints']) ? (float)$cr['maxPoints'] : 3);
            $pObt = isset($cr['puntaje']) ? (float)$cr['puntaje'] : (isset($cr['score']) ? (float)$cr['score'] : 0);
            $criteriaList[] = [
                'criterion_id' => $cId,
                'criterio' => $criterioNom,
                'descripcion' => '',
                'puntaje_maximo' => $pMax,
                'puntaje_obtenido' => $pObt,
            ];
            $maxPosible += $pMax;
            $calculatedTotal += $pObt;
            $hasMatchedScores = true;
        }
    }

    $totalScore = $hasMatchedScores
        ? $calculatedTotal
        : (isset($json['totalScore']) ? (float)$json['totalScore'] : (isset($json['puntaje_total']) ? (float)$json['puntaje_total'] : 0.0));

    if ($maxPosible <= 0) {
        $maxPosible = 100;
    }

    $firmaJurado = splitImages($row['firma_evaluador'])[0] ?? null;
    $firmaConcursante = splitImages($row['firma_concursante'])[0] ?? null;
    $fotos = splitImages($row['imagenes']);

    echo json_encode([
        'success' => true,
        'acta' => [
            'uid' => 'amb_' . $row['id'],
            'fuente' => 'ambiental',
            'fuente_label' => 'XXV Feria Agroambiental de Sumapaz (Rol 13)',
            'titulo_documento' => 'ACTA OFICIAL DE JUZGAMIENTO Y EVALUACION',
            'id_evaluacion' => (int)$row['id'],
            'id_concurso' => (int)$row['id_concurso'],
            'concurso' => $concursoNom,
            'categoria' => $categoriaNom,
            'fecha' => $fecha,
            'coordenadas' => $coords,
            'jurado' => $jurado,
            'participante' => [
                'id' => (int)$row['id_productor_predio'],
                'nombre' => $nombreCompleto,
                'tipo_documento' => $row['tipo_documento'] ?: 'Cédula de Ciudadanía',
                'numero_documento' => $row['numero_documento'] ?: '',
                'vereda' => $row['vereda'] ?: 'Sumapaz',
                'cuenca' => $row['cuenca'] ?: 'Rio Sumapaz',
                'predio' => $row['nombre_predio'] ?: '',
                'extension' => $row['extension_finca'] ? ($row['extension_finca'] . ' fanegadas') : '',
                'telefono' => $row['telefono'] ?: '',
            ],
            'puntaje_total' => $totalScore,
            'puntaje_max_posible' => $maxPosible,
            'criterios' => $criteriaList,
            'observaciones' => trim((string)($row['observaciones'] ?? '')),
            'firma_jurado' => $firmaJurado,
            'firma_concursante' => $firmaConcursante,
            'fotos' => $fotos,
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al obtener detalle del acta oficial: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
