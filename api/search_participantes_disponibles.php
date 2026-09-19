<?php
/**
 * API Endpoint: Search available candidates to add to a contest
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_config.php';

$concursoId = isset($_GET['concurso_id']) ? (int)$_GET['concurso_id'] : 0;
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (!$concursoId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de concurso requerido']);
    exit;
}

try {
    // 1. Get IDs of participants already in this contest
    $stmtEnrolled = $pdo->prepare("SELECT participante_id FROM concurso_participaciones WHERE concurso_id = ?");
    $stmtEnrolled->execute([$concursoId]);
    $enrolledIds = $stmtEnrolled->fetchAll(PDO::FETCH_COLUMN);
    $enrolledSet = array_flip(array_map('intval', $enrolledIds));

    // 2. Fetch candidates from feria_participantes
    $sql = "SELECT id, documento_identidad, nombre_completo, vereda, telefono FROM feria_participantes";
    $params = [];

    if ($query !== '') {
        $sql .= " WHERE nombre_completo LIKE ? OR documento_identidad LIKE ? OR vereda LIKE ?";
        $qTerm = "%$query%";
        $params = [$qTerm, $qTerm, $qTerm];
    }
    $sql .= " ORDER BY nombre_completo ASC LIMIT 50";

    $stmtP = $pdo->prepare($sql);
    $stmtP->execute($params);
    $rows = $stmtP->fetchAll(PDO::FETCH_ASSOC);

    $candidates = [];
    $seenDocs = [];

    foreach ($rows as $r) {
        $pid = (int)$r['id'];
        $doc = trim($r['documento_identidad'] ?? '');
        if (isset($enrolledSet[$pid])) continue; // Already in contest

        if ($doc) $seenDocs[$doc] = true;
        $candidates[] = [
            'id' => $pid,
            'documento_identidad' => $r['documento_identidad'],
            'nombre_completo' => $r['nombre_completo'],
            'vereda' => $r['vereda'],
            'telefono' => $r['telefono'],
            'origen' => 'feria_participantes'
        ];
    }

    // 3. Also check productores_sumapaz if query provided and limit not reached
    if (count($candidates) < 50) {
        $sqlProd = "SELECT id, numero_documento as documento_identidad, nombre_completo, vereda, telefono FROM productores_sumapaz";
        $paramsProd = [];
        if ($query !== '') {
            $sqlProd .= " WHERE nombre_completo LIKE ? OR numero_documento LIKE ? OR vereda LIKE ?";
            $qTerm = "%$query%";
            $paramsProd = [$qTerm, $qTerm, $qTerm];
        }
        $sqlProd .= " ORDER BY nombre_completo ASC LIMIT 50";

        $stmtProd = $pdo->prepare($sqlProd);
        $stmtProd->execute($paramsProd);
        $prods = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

        foreach ($prods as $p) {
            $doc = trim($p['documento_identidad'] ?? '');
            if ($doc && isset($seenDocs[$doc])) continue;

            $candidates[] = [
                'id' => null, // Not in feria_participantes yet (will be created automatically on add)
                'documento_identidad' => $p['documento_identidad'],
                'nombre_completo' => $p['nombre_completo'],
                'vereda' => $p['vereda'],
                'telefono' => $p['telefono'],
                'origen' => 'productores_sumapaz'
            ];
            if (count($candidates) >= 50) break;
        }
    }

    echo json_encode([
        'success' => true,
        'candidates' => $candidates
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
