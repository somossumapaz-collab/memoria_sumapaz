<?php
/**
 * API to fetch all details of a specific producer
 */
require_once 'db_config.php';

header('Content-Type: application/json');

if (empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de productor requerido']);
    exit;
}

$id = intval($_GET['id']);

try {
    // 1. Basic Info & Characterization
    $stmt = $pdo->prepare("
        SELECT p.*, cp.* 
        FROM productores_sumapaz p
        LEFT JOIN caracterizacion_productor cp ON p.id = cp.productor_id
        WHERE p.id = ?
    ");
    $stmt->execute([$id]);
    $productor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$productor) {
        http_response_code(404);
        echo json_encode(['error' => 'Productor no encontrado']);
        exit;
    }

    // 2. Financing
    $stmt = $pdo->prepare("
        SELECT f.nombre 
        FROM productor_financiamiento pf
        JOIN financiamiento f ON pf.financiamiento_id = f.id
        WHERE pf.productor_id = ?
    ");
    $stmt->execute([$id]);
    $financiamiento = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 3. Population Groups
    $stmt = $pdo->prepare("
        SELECT g.nombre 
        FROM productor_grupo pg
        JOIN grupos_poblacionales g ON pg.grupo_id = g.id
        WHERE pg.productor_id = ?
    ");
    $stmt->execute([$id]);
    $grupos = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 4. Difficulties
    $stmt = $pdo->prepare("
        SELECT d.nombre 
        FROM productor_dificultad pd
        JOIN dificultades d ON pd.dificultad_id = d.id
        WHERE pd.productor_id = ?
    ");
    $stmt->execute([$id]);
    $dificultades = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 5. Certifications
    $stmt = $pdo->prepare("
        SELECT c.nombre 
        FROM productor_certificacion pc
        JOIN certificaciones c ON pc.certificacion_id = c.id
        WHERE pc.productor_id = ?
    ");
    $stmt->execute([$id]);
    $certificaciones = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 6. Disabilities
    $stmt = $pdo->prepare("
        SELECT discapacidad 
        FROM discapacidad_productor 
        WHERE productor_id = ?
    ");
    $stmt->execute([$id]);
    $discapacidades = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'data' => [
            'info' => $productor,
            'financiamiento' => $financiamiento,
            'grupos' => $grupos,
            'dificultades' => $dificultades,
            'certificaciones' => $certificaciones,
            'discapacidades' => $discapacidades
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener el detalle: ' . $e->getMessage()]);
}
?>
