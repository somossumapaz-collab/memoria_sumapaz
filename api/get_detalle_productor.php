<?php
/**
 * API Endpoint: Get Detailed Producer Information
 * Extracts characterization details using GROUP_CONCAT
 */

require_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de productor no proporcionado']);
    exit;
}

$productor_id = $_GET['id'];

try {
    $sql = "
        SELECT 
            p.id,
            p.nombre_completo,
            p.vereda,
            p.nombre_predio,

            GROUP_CONCAT(DISTINCT cv.nombre SEPARATOR '|') AS canales,
            GROUP_CONCAT(DISTINCT cat.nombre SEPARATOR '|') AS categorias,
            GROUP_CONCAT(DISTINCT cert.nombre SEPARATOR '|') AS certificaciones,
            GROUP_CONCAT(DISTINCT dif.nombre SEPARATOR '|') AS dificultades,
            GROUP_CONCAT(DISTINCT fin.nombre SEPARATOR '|') AS financiamiento,
            GROUP_CONCAT(DISTINCT grp.nombre SEPARATOR '|') AS grupos

        FROM productores_sumapaz p

        LEFT JOIN productor_canal pc ON pc.productor_id = p.id
        LEFT JOIN canales_venta cv ON cv.id = pc.canal_id

        LEFT JOIN productor_categoria pcat ON pcat.productor_id = p.id
        LEFT JOIN categorias_productivas cat ON cat.id = pcat.categoria_id

        LEFT JOIN productor_certificacion pcert ON pcert.productor_id = p.id
        LEFT JOIN certificaciones cert ON cert.id = pcert.certificacion_id

        LEFT JOIN productor_dificultad pdif ON pdif.productor_id = p.id
        LEFT JOIN dificultades dif ON dif.id = pdif.dificultad_id

        LEFT JOIN productor_financiamiento pfin ON pfin.productor_id = p.id
        LEFT JOIN financiamiento fin ON fin.id = pfin.financiamiento_id

        LEFT JOIN productor_grupo pgru ON pgru.productor_id = p.id
        LEFT JOIN grupos_poblacionales grp ON grp.id = pgru.grupo_id

        WHERE p.id = :id
        GROUP BY p.id, p.nombre_completo, p.vereda, p.nombre_predio
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $productor_id]);
    $registro = $stmt->fetch();

    if ($registro) {
        $stmt_disc = $pdo->prepare("SELECT tiene_discapacidad, tipo FROM discapacidad_productor WHERE productor_id = ?");
        $stmt_disc->execute([$productor_id]);
        $registro['discapacidad'] = $stmt_disc->fetch(PDO::FETCH_ASSOC);

        $stmt_prod = $pdo->prepare("SELECT * FROM productor_productos WHERE productor_id = ?");
        $stmt_prod->execute([$productor_id]);
        $registro['productos'] = $stmt_prod->fetchAll(PDO::FETCH_ASSOC);

        $stmt_srv = $pdo->prepare("SELECT * FROM productor_servicios WHERE productor_id = ?");
        $stmt_srv->execute([$productor_id]);
        $registro['servicios'] = $stmt_srv->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $registro]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Productor no encontrado o sin información detallada']);
    }

} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Database error in get_detalle_productor.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error al obtener el detalle del productor.']);
}
?>
