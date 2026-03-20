<?php
/**
 * API to fetch a specific producer's detailed characterization record by Document Number
 */
require_once 'db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$doc = isset($_GET['doc']) ? trim($_GET['doc']) : '';

if (empty($doc)) {
    echo json_encode(['success' => false, 'error' => 'Document number is required.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            p.id as productor_base_id,
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
            cp.id as caracterizacion_id,
            cp.*
        FROM productores_sumapaz p
        LEFT JOIN caracterizacion_productor cp ON p.id = cp.productor_id
        WHERE p.numero_documento = :doc
        LIMIT 1
    ");

    $stmt->execute(['doc' => $doc]);
    $ficha = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ficha) {
        // Evaluate if they actually have characterization properties instead of just base data
        $ficha['tiene_caracterizacion'] = isset($ficha['caracterizacion_id']) ? 1 : 0;
        echo json_encode(['success' => true, 'data' => $ficha]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se encontró productor con este documento.', 'data' => null]);
    }

} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Database error in get_ficha_by_documento.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error al obtener la ficha.']);
}
?>
