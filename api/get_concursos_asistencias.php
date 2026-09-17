<?php
/**
 * API Endpoint: Get Fair Participants Attendance List with Signatures and Filters
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_config.php';

try {
    $vereda = isset($_GET['vereda']) ? trim($_GET['vereda']) : null;
    $asistio = isset($_GET['asistio']) ? trim($_GET['asistio']) : null;
    $search = isset($_GET['search']) ? trim($_GET['search']) : null;

    // 1. Fetch unique veredas for dropdown filter
    $stmtVeredas = $pdo->query("SELECT DISTINCT vereda FROM feria_participantes WHERE vereda IS NOT NULL AND vereda != '' ORDER BY vereda ASC");
    $veredas = $stmtVeredas->fetchAll(PDO::FETCH_COLUMN);

    // 2. Build dynamic WHERE clause
    $where = ["1=1"];
    $params = [];

    if ($vereda && $vereda !== 'all') {
        $where[] = "vereda = ?";
        $params[] = $vereda;
    }

    if ($asistio !== null && $asistio !== '' && $asistio !== 'all') {
        $where[] = "asistio = ?";
        $params[] = ($asistio === '1' || $asistio === 'true') ? 1 : 0;
    }

    if ($search) {
        $where[] = "(nombre_completo LIKE ? OR documento_identidad LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $whereClause = implode(" AND ", $where);

    // 3. Stats for filtered set
    $sqlStats = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN asistio = 1 THEN 1 ELSE 0 END) as asistieron,
            SUM(CASE WHEN asistio = 0 THEN 1 ELSE 0 END) as no_asistieron,
            SUM(CASE WHEN firma_asistencia IS NOT NULL AND firma_asistencia != '' THEN 1 ELSE 0 END) as con_firma
        FROM feria_participantes
        WHERE $whereClause
    ";
    $stmtS = $pdo->prepare($sqlStats);
    $stmtS->execute($params);
    $stats = $stmtS->fetch(PDO::FETCH_ASSOC);

    // 4. Fetch list of participants
    $sqlList = "
        SELECT 
            id,
            feria_id,
            documento_identidad,
            tipo_documento,
            nombre_completo,
            vereda,
            edad,
            telefono,
            asistio,
            firma_asistencia,
            fecha_registro
        FROM feria_participantes
        WHERE $whereClause
        ORDER BY nombre_completo ASC
    ";

    $stmtL = $pdo->prepare($sqlList);
    $stmtL->execute($params);
    $rows = $stmtL->fetchAll(PDO::FETCH_ASSOC);

    $participantes = [];
    foreach ($rows as $r) {
        $participantes[] = [
            'id' => (int)$r['id'],
            'feria_id' => (int)$r['feria_id'],
            'documento_identidad' => $r['documento_identidad'],
            'tipo_documento' => $r['tipo_documento'] ?: 'Cédula de Ciudadanía',
            'nombre_completo' => $r['nombre_completo'],
            'vereda' => $r['vereda'],
            'edad' => $r['edad'] ? (int)$r['edad'] : null,
            'telefono' => $r['telefono'],
            'asistio' => (bool)$r['asistio'],
            'firma_asistencia' => $r['firma_asistencia'] ?: '',
            'fecha_registro' => $r['fecha_registro']
        ];
    }

    echo json_encode([
        'success' => true,
        'veredas' => $veredas,
        'stats' => [
            'total' => (int)$stats['total'],
            'asistieron' => (int)$stats['asistieron'],
            'no_asistieron' => (int)$stats['no_asistieron'],
            'con_firma' => (int)$stats['con_firma']
        ],
        'participantes' => $participantes
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al obtener asistencias: ' . $e->getMessage()]);
}
