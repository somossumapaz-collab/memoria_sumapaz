<?php
/**
 * API to fetch registered producers with coordinates along with their characterization and categories
 */
require_once 'db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

try {
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
            p.cuenca,
            cp.fecha_caracterizacion,
            cp.coordenadas,
            cp.tipo_organizacion,
            cp.extension_predio,
            cp.tiempo_implementacion,
            cp.tipo_tenencia,
            cp.numero_personas,
            cp.nombre_organizacion,
            cp.mano_obra,
            cp.tipo_proceso,
            cp.usa_abonos,
            cp.sistemas_asociados,
            cp.sistema_diferenciado,
            cp.descripcion,
            cp.valor_agregado,
            cp.destino,
            cp.transporte,
            cp.forma_pago,
            cp.define_precio,
            cp.en_tramite_bool,
            cp.en_tramite,
            GROUP_CONCAT(DISTINCT cat.nombre SEPARATOR ', ') AS categorias
        FROM productores_sumapaz p
        INNER JOIN caracterizacion_productor cp ON p.id = cp.productor_id
        LEFT JOIN productor_categoria pcat ON pcat.productor_id = p.id
        LEFT JOIN categorias_productivas cat ON cat.id = pcat.categoria_id
        WHERE cp.coordenadas IS NOT NULL AND TRIM(cp.coordenadas) != ''
        GROUP BY p.id
        ORDER BY p.nombre_completo ASC
    ");

    $productores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $productores]);
} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Database error in get_productores_coordenadas.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error al obtener los productores con coordenadas: ' . $e->getMessage()]);
}
?>
