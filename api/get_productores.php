<?php
/**
 * API to fetch registered producers from the database
 */
require_once 'db_config.php';
ini_set('display_errors', '0');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
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
        SELECT p.id, p.vereda, p.beneficiario_2026, cp.puntaje 
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
    }
    unset($p);

    // Filter out manually excluded (beneficiario_2026 = 2)
    $eligible = array_filter($characterized, function($p) {
        return intval($p['beneficiario_2026']) !== 2;
    });

    // Sort eligible by puntaje_ajustado descending, then by id ascending (to be stable)
    usort($eligible, function($a, $b) {
        if ($b['puntaje_ajustado'] == $a['puntaje_ajustado']) {
            return $a['id'] - $b['id'];
        }
        return ($b['puntaje_ajustado'] > $a['puntaje_ajustado']) ? 1 : -1;
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
    // Automatically recalculate beneficiaries dynamically
    recalculate_beneficiarios($pdo);

    $stmt = $pdo->query("
        SELECT 
            p.id,
            p.nombre_completo,
            p.tipo_documento,
            p.numero_documento,
            p.fecha_nacimiento,
            p.telefono,
            p.correo_electronico,
            p.vereda,
            p.nombre_predio,
            p.fecha_creacion,
            p.mypime,
            p.efectividad_2025,
            p.panaca,
            p.ferias,
            p.beneficiario_2026,
            p.cuenca,
            CASE 
                WHEN MAX(cp.id) IS NOT NULL THEN 1
                ELSE 0
            END AS tiene_caracterizacion,
            MAX(cp.nombre_organizacion) AS nombre_organizacion,
            MAX(cp.puntaje_social) AS puntaje_social,
            MAX(cp.puntaje_organizacional) AS puntaje_organizacional,
            MAX(cp.puntaje_productivo) AS puntaje_productivo,
            MAX(cp.puntaje_comercial) AS puntaje_comercial,
            MAX(cp.puntaje_ambiental) AS puntaje_ambiental,
            MAX(cp.puntaje_impacto) AS puntaje_impacto,
            MAX(cp.puntaje) AS puntaje,
            GROUP_CONCAT(DISTINCT cpcat.categoria_id) AS categorias_ids,
            GROUP_CONCAT(DISTINCT c.id) AS certificaciones_ids,
            GROUP_CONCAT(DISTINCT c.nombre ORDER BY c.nombre SEPARATOR ', ') AS certificaciones_nombres
        FROM productores_sumapaz p
        LEFT JOIN caracterizacion_productor cp ON p.id = cp.productor_id
        LEFT JOIN productor_categoria cpcat ON p.id = cpcat.productor_id
        LEFT JOIN productor_certificacion pc ON p.id = pc.productor_id
        LEFT JOIN certificaciones c ON pc.certificacion_id = c.id
        GROUP BY p.id
        ORDER BY p.fecha_creacion DESC
    ");

    $productores = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $productores]);
} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Database error in get_productores.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error al obtener los productores.']);
}
?>