<?php
/**
 * API to remove/withdraw a beneficiary without PMAPC and recalculate top 150 with replacement
 */
require_once 'db_config.php';
require_once 'score_helper.php';
ini_set('display_errors', '0');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    if (!$data) {
        $data = $_POST;
    }

    $productor_id = isset($data['productor_id']) ? intval($data['productor_id']) : 0;
    $comentario = isset($data['comentario']) ? trim($data['comentario']) : '';

    if ($productor_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID de productor inválido.']);
        exit;
    }

    if (empty($comentario)) {
        echo json_encode(['success' => false, 'error' => 'El comentario/motivo de retiro es obligatorio.']);
        exit;
    }

    // 1. Fetch producer details
    $stmt = $pdo->prepare("SELECT id, nombre_completo, fecha_beneficio, beneficiario_2026, observacion FROM productores_sumapaz WHERE id = ?");
    $stmt->execute([$productor_id]);
    $productor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$productor) {
        echo json_encode(['success' => false, 'error' => 'Productor no encontrado.']);
        exit;
    }

    // 2. Verify producer does NOT have a PMAPC completed
    $stmtPmapc = $pdo->prepare("SELECT COUNT(*) FROM pmapc_registros WHERE productor_id = ? AND LENGTH(data) > 2");
    $stmtPmapc->execute([$productor_id]);
    $hasPmapc = $stmtPmapc->fetchColumn() > 0;

    if ($hasPmapc) {
        echo json_encode(['success' => false, 'error' => 'No se puede quitar este productor porque ya cuenta con un PMAPC diligenciado.']);
        exit;
    }

    $nombreSaliente = $productor['nombre_completo'];
    $cohortSaliente = strval($productor['fecha_beneficio']);

    // 3. Mark producer as withdrawn
    $obsSaliente = "Desistió: " . $comentario;
    $stmtUpdateSaliente = $pdo->prepare("
        UPDATE productores_sumapaz 
        SET observacion = ?, 
            beneficiario_2026 = 0, 
            fecha_beneficio = NULL 
        WHERE id = ?
    ");
    $stmtUpdateSaliente->execute([$obsSaliente, $productor_id]);

    // 4. Recalculate scores for all producers
    recalculate_all_producers_scores($pdo);

    // 5. Fetch vereda counts
    $stmtVeredas = $pdo->query("SELECT id, vereda FROM productores_sumapaz");
    $allVeredas = $stmtVeredas->fetchAll(PDO::FETCH_ASSOC);

    $vereda_counts = [];
    foreach ($allVeredas as $p) {
        $vNorm = normalizeVereda($p['vereda']);
        if ($vNorm) {
            if (!isset($vereda_counts[$vNorm])) {
                $vereda_counts[$vNorm] = 0;
            }
            $vereda_counts[$vNorm]++;
        }
    }

    // 6. Fetch all producers with scores & details
    $stmtAll = $pdo->query("
        SELECT 
            p.id, 
            p.nombre_completo,
            p.numero_documento,
            p.vereda, 
            p.nombre_predio,
            p.telefono,
            p.fecha_beneficio,
            p.beneficiario_2026,
            p.observacion,
            cp.puntaje,
            IFNULL(cp.puntaje_ambiental, 0) as puntaje_ambiental,
            IFNULL(cp.puntaje_comercial, 0) as puntaje_comercial,
            IFNULL(cp.puntaje_social, 0) as puntaje_social,
            CAST(IFNULL(cp.tiempo_implementacion, 0) AS UNSIGNED) as tiempo_implementacion,
            cp.tipo_organizacion,
            (SELECT COUNT(*) FROM discapacidad_productor dp WHERE dp.productor_id = p.id AND dp.tiene_discapacidad = 'Sí') as tiene_discapacidad_cnt,
            (SELECT COUNT(*) FROM productor_grupo pg WHERE pg.productor_id = p.id AND pg.grupo_id IN (1, 3, 6, 7)) as grupo_prioritario_cnt,
            p.fecha_nacimiento
        FROM productores_sumapaz p
        LEFT JOIN caracterizacion_productor cp ON p.id = cp.productor_id
    ");
    $allProducers = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

    // Precompute tie-breaker and adjusted score values
    foreach ($allProducers as &$p) {
        $vNorm = normalizeVereda($p['vereda']);
        $count = isset($vereda_counts[$vNorm]) ? $vereda_counts[$vNorm] : 1;
        $score = floatval($p['puntaje'] ?? 0);
        $p['puntaje_ajustado'] = $score * (1.0 + 1.0 / $count);
        $p['puntaje'] = floatval($score);

        $tipo_org = $p['tipo_organizacion'];
        $p['tiene_organizacion'] = ($tipo_org && $tipo_org !== 'Ninguna' && $tipo_org !== 'Productor individual') ? 1 : 0;
        
        $is_priority = 0;
        if (intval($p['tiene_discapacidad_cnt']) > 0 || intval($p['grupo_prioritario_cnt']) > 0) {
            $is_priority = 1;
        } else {
            $birth = $p['fecha_nacimiento'];
            if ($birth && $birth !== '1900-01-01') {
                $birthYear = intval(substr($birth, 0, 4));
                if ($birthYear > 0) {
                    $age = 2026 - $birthYear;
                    if (($age >= 18 && $age <= 28) || $age >= 60) {
                        $is_priority = 1;
                    }
                }
            }
        }
        $p['poblacion_prioritaria'] = $is_priority;
    }
    unset($p);

    // Comparator function for sorting
    $sorter = function($a, $b) {
        if (abs($b['puntaje_ajustado'] - $a['puntaje_ajustado']) > 0.0001) {
            return ($b['puntaje_ajustado'] > $a['puntaje_ajustado']) ? 1 : -1;
        }
        if ($b['puntaje_ambiental'] !== $a['puntaje_ambiental']) {
            return $b['puntaje_ambiental'] - $a['puntaje_ambiental'];
        }
        if ($b['puntaje_comercial'] !== $a['puntaje_comercial']) {
            return $b['puntaje_comercial'] - $a['puntaje_comercial'];
        }
        if ($b['puntaje_social'] !== $a['puntaje_social']) {
            return $b['puntaje_social'] - $a['puntaje_social'];
        }
        if ($b['poblacion_prioritaria'] !== $a['poblacion_prioritaria']) {
            return $b['poblacion_prioritaria'] - $a['poblacion_prioritaria'];
        }
        if ($b['tiempo_implementacion'] !== $a['tiempo_implementacion']) {
            return $b['tiempo_implementacion'] - $a['tiempo_implementacion'];
        }
        if ($b['tiene_organizacion'] !== $a['tiene_organizacion']) {
            return $b['tiene_organizacion'] - $a['tiene_organizacion'];
        }
        return $a['id'] - $b['id'];
    };

    // Store replacement info
    $replacementProducer = null;

    // CASE A: Withdrawn producer was in 2025 cohort
    if ($cohortSaliente === '2025') {
        // Active 2025 producers (excluding the withdrawn one)
        $active_2025 = array_filter($allProducers, function($p) use ($productor_id) {
            return strval($p['fecha_beneficio']) === '2025' 
                && intval($p['id']) !== $productor_id 
                && !str_starts_with($p['observacion'] ?? '', 'Desistió');
        });
        
        $active_2025_ids = array_map(function($p) { return intval($p['id']); }, $active_2025);

        // Find 2025 replacement candidate
        $eligible_2025_pool = array_filter($allProducers, function($p) use ($active_2025_ids) {
            $id = intval($p['id']);
            $obs = $p['observacion'] ?? '';
            if (in_array($id, $active_2025_ids)) return false;
            if (str_starts_with($obs, 'Desistió')) return false;
            return true;
        });

        usort($eligible_2025_pool, $sorter);

        if (!empty($eligible_2025_pool)) {
            $replacementProducer = $eligible_2025_pool[0];
            $rep2025Id = intval($replacementProducer['id']);

            $newObs2025 = "Reemplazo 2025: Entra por " . $nombreSaliente;
            $stmtSet2025 = $pdo->prepare("UPDATE productores_sumapaz SET fecha_beneficio = '2025', beneficiario_2026 = 0, observacion = ? WHERE id = ?");
            $stmtSet2025->execute([$newObs2025, $rep2025Id]);

            $active_2025_ids[] = $rep2025Id;
            $replacementProducer['nivel_2026'] = 1;
        }

        $final_2025_ids = $active_2025_ids;

    } else { // CASE B: Withdrawn producer was in 2026 cohort (or not in 2025)
        $final_2025_ids = [];
        foreach ($allProducers as $p) {
            if (strval($p['fecha_beneficio']) === '2025' && !str_starts_with($p['observacion'] ?? '', 'Desistió')) {
                $final_2025_ids[] = intval($p['id']);
            }
        }
    }

    // Previous 2026 beneficiary IDs (before recalculation)
    $prev_2026_ids = [];
    foreach ($allProducers as $p) {
        if ((strval($p['fecha_beneficio']) === '2026' || intval($p['beneficiario_2026']) === 1) && intval($p['id']) !== $productor_id) {
            $prev_2026_ids[] = intval($p['id']);
        }
    }

    // Filter eligible pool for 2026
    $pool_2026 = array_filter($allProducers, function($p) use ($final_2025_ids) {
        $id = intval($p['id']);
        $obs = $p['observacion'] ?? '';
        if (in_array($id, $final_2025_ids)) return false;
        if (str_starts_with($obs, 'Desistió')) return false;
        return true;
    });

    usort($pool_2026, $sorter);

    // Slice top 150 for 2026
    $top_150_2026 = array_slice($pool_2026, 0, 150);

    if ($cohortSaliente !== '2025') {
        // Find replacement for 2026
        foreach ($top_150_2026 as $idx => $p) {
            if (!in_array(intval($p['id']), $prev_2026_ids)) {
                $replacementProducer = $p;
                $replacementProducer['rank_2026'] = $idx + 1;
                break;
            }
        }
        if (!$replacementProducer && !empty($top_150_2026)) {
            $lastIdx = count($top_150_2026) - 1;
            $replacementProducer = $top_150_2026[$lastIdx];
            $replacementProducer['rank_2026'] = 150;
        }
    }

    // Reset 2026 status for producers not in final 2025
    $stmtReset = $pdo->query("UPDATE productores_sumapaz SET beneficiario_2026 = 0 WHERE (fecha_beneficio != '2025' OR fecha_beneficio IS NULL)");

    // Update DB for top 150 for 2026
    $stmtUpdate2026 = $pdo->prepare("
        UPDATE productores_sumapaz 
        SET beneficiario_2026 = 1,
            fecha_beneficio = '2026',
            nivel_priorizacion_2026 = ?
        WHERE id = ?
    ");

    foreach ($top_150_2026 as $idx => $p) {
        $rank = $idx + 1;
        $nivel = 4;
        if ($rank <= 20) $nivel = 1;
        else if ($rank <= 75) $nivel = 2;
        else if ($rank <= 125) $nivel = 3;

        $stmtUpdate2026->execute([$nivel, $p['id']]);

        if ($replacementProducer && intval($p['id']) === intval($replacementProducer['id'])) {
            $replacementProducer['nivel_2026'] = $nivel;
        }
    }

    if ($replacementProducer && $cohortSaliente !== '2025') {
        $repId = intval($replacementProducer['id']);
        $existingObs = $replacementProducer['observacion'] ?? '';
        if (empty($existingObs) || !str_contains($existingObs, 'Reemplazo')) {
            $newObs = "Reemplazo 2026: Entra por " . $nombreSaliente;
            $stmtObsRep = $pdo->prepare("UPDATE productores_sumapaz SET observacion = ? WHERE id = ?");
            $stmtObsRep->execute([$newObs, $repId]);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "Productor {$nombreSaliente} retirado exitosamente.",
        'removed' => [
            'id' => $productor_id,
            'nombre_completo' => $nombreSaliente,
            'comentario' => $comentario
        ],
        'replacement' => $replacementProducer ? [
            'id' => $replacementProducer['id'],
            'nombre_completo' => $replacementProducer['nombre_completo'],
            'numero_documento' => $replacementProducer['numero_documento'] ?? '',
            'vereda' => $replacementProducer['vereda'] ?? '',
            'nombre_predio' => $replacementProducer['nombre_predio'] ?? '',
            'nivel_2026' => $replacementProducer['nivel_2026'] ?? 4,
            'puntaje_ajustado' => round($replacementProducer['puntaje_ajustado'], 2)
        ] : null
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Error in quitar_beneficiario.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error al procesar el retiro: ' . $e->getMessage()]);
}
