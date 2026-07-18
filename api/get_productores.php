<?php
/**
 * API to fetch registered producers from the database
 */
require_once 'db_config.php';
require_once 'score_helper.php';
ini_set('display_errors', '0');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Only recalculate once per session to avoid slamming Hostinger and causing timeouts
    if (empty($_SESSION['recalculated_scores']) || isset($_GET['force_recalculate'])) {
        require_once 'score_helper.php';
        // recalculate_all_producers_scores($pdo);
        // recalculate_beneficiarios($pdo);
        $_SESSION['recalculated_scores'] = true;
    }

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
            p.cedula_pdf,
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
            MAX(CAST(cp.tiempo_implementacion AS UNSIGNED)) AS tiempo_implementacion,
            MAX(cp.tipo_organizacion) AS tipo_organizacion,
            (SELECT COUNT(*) FROM discapacidad_productor dp WHERE dp.productor_id = p.id AND dp.tiene_discapacidad = 'Sí') AS tiene_discapacidad_cnt,
            (SELECT COUNT(*) FROM productor_grupo pg WHERE pg.productor_id = p.id AND pg.grupo_id IN (1, 3, 6, 7)) AS grupo_prioritario_cnt,
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