<?php
/**
 * API to toggle producer's beneficiary status (exclude or set to eligible)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_config.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? intval($input['id']) : 0;
$status = isset($input['status']) ? intval($input['status']) : 0;

if ($id <= 0 || !in_array($status, [0, 2])) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos']);
    exit;
}

function normalizeVereda($vereda) {
    if (!$vereda) return '';
    $normalized = trim($vereda);
    $normalized = function_exists('mb_strtoupper') ? mb_strtoupper($normalized, 'UTF-8') : strtoupper($normalized);
    $unwanted_array = array(
        'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C',
        'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
        'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a',
        'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i',
        'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u',
        'û'=>'u', 'ü'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y'
    );
    $normalized = strtr($normalized, $unwanted_array);
    $normalized = preg_replace('/\s+/', ' ', $normalized);
    return trim($normalized);
}

function recalculate_beneficiarios($pdo) {
    // 1. Fetch basic info of all producers to calculate vereda counts
    $stmt = $pdo->query("SELECT id, vereda, beneficiario_2026 FROM productores_sumapaz");
    $all_productores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count veredas
    $vereda_counts = [];
    foreach ($all_productores as $p) {
        $vNorm = normalizeVereda($p['vereda']);
        if ($vNorm) {
            if (!isset($vereda_counts[$vNorm])) {
                $vereda_counts[$vNorm] = 0;
            }
            $vereda_counts[$vNorm]++;
        }
    }

    // 2. Fetch characterized producers to calculate scores
    $stmt = $pdo->query("
        SELECT 
            p.id, 
            p.vereda, 
            p.beneficiario_2026, 
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
        JOIN caracterizacion_productor cp ON p.id = cp.productor_id
        WHERE cp.puntaje IS NOT NULL
    ");
    $characterized = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate adjusted scores
    foreach ($characterized as &$p) {
        $vNorm = normalizeVereda($p['vereda']);
        $count = isset($vereda_counts[$vNorm]) ? $vereda_counts[$vNorm] : 1;
        $p['puntaje_ajustado'] = floatval($p['puntaje']) * (1.0 + 1.0 / $count);

        // Precompute tie-breaker values
        $p['puntaje'] = floatval($p['puntaje']);
        $p['puntaje_ambiental'] = intval($p['puntaje_ambiental']);
        $p['puntaje_comercial'] = intval($p['puntaje_comercial']);
        $p['puntaje_social'] = intval($p['puntaje_social']);
        $p['tiempo_implementacion'] = intval($p['tiempo_implementacion']);
        
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

    // Filter out manually excluded (beneficiario_2026 = 2)
    $eligible = array_filter($characterized, function($p) {
        return intval($p['beneficiario_2026']) !== 2;
    });

    // Sort eligible by puntaje_ajustado descending, then by tie-breakers, then by id ascending (to be stable)
    usort($eligible, function($a, $b) {
        if ($b['puntaje_ajustado'] != $a['puntaje_ajustado']) {
            return ($b['puntaje_ajustado'] > $a['puntaje_ajustado']) ? 1 : -1;
        }
        
        // --- CRITERIOS DE DESEMPATE ---
        // 1. Sostenibilidad Ambiental (Componente 5)
        if ($b['puntaje_ambiental'] !== $a['puntaje_ambiental']) {
            return $b['puntaje_ambiental'] - $a['puntaje_ambiental'];
        }
        // 2. Comercialización (Componente 4)
        if ($b['puntaje_comercial'] !== $a['puntaje_comercial']) {
            return $b['puntaje_comercial'] - $a['puntaje_comercial'];
        }
        // 3. Enfoque Diferencial y Social (Componente 1)
        if ($b['puntaje_social'] !== $a['puntaje_social']) {
            return $b['puntaje_social'] - $a['puntaje_social'];
        }
        // 4. Población prioritaria
        if ($b['poblacion_prioritaria'] !== $a['poblacion_prioritaria']) {
            return $b['poblacion_prioritaria'] - $a['poblacion_prioritaria'];
        }
        // 5. Tiempo de implementación
        if ($b['tiempo_implementacion'] !== $a['tiempo_implementacion']) {
            return $b['tiempo_implementacion'] - $a['tiempo_implementacion'];
        }
        // 6. Estar en organización
        if ($b['tiene_organizacion'] !== $a['tiene_organizacion']) {
            return $b['tiene_organizacion'] - $a['tiene_organizacion'];
        }
        
        return $a['id'] - $b['id'];
    });

    // Slice the top 152
    $top_152 = array_slice($eligible, 0, 152);
    $top_152_ids = array_map(function($p) { return intval($p['id']); }, $top_152);
    $top_152_set = array_flip($top_152_ids);

    // Identify updates
    $to_set_1 = [];
    $to_set_0 = [];

    foreach ($characterized as $p) {
        $id = intval($p['id']);
        $current_status = intval($p['beneficiario_2026']);
        
        if (isset($top_152_set[$id])) {
            if ($current_status !== 1) {
                $to_set_1[] = $id;
            }
        } else {
            if ($current_status === 1) {
                $to_set_0[] = $id;
            }
        }
    }

    // Perform database updates
    if (!empty($to_set_1)) {
        $in = implode(',', $to_set_1);
        $pdo->query("UPDATE productores_sumapaz SET beneficiario_2026 = 1 WHERE id IN ($in)");
    }
    if (!empty($to_set_0)) {
        $in = implode(',', $to_set_0);
        $pdo->query("UPDATE productores_sumapaz SET beneficiario_2026 = 0 WHERE id IN ($in)");
    }
}

try {
    // 1. Update the target producer's eligibility status
    $stmt = $pdo->prepare("UPDATE productores_sumapaz SET beneficiario_2026 = ? WHERE id = ?");
    $stmt->execute([$status, $id]);

    // 2. Recalculate beneficiaries lists
    recalculate_beneficiarios($pdo);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al actualizar el estado: ' . $e->getMessage()]);
}
?>
