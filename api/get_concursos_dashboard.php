<?php
/**
 * API Endpoint: Get Concursos Dashboard Overview
 * Returns stats, category list, and all contests with participant & evaluation counts.
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_config.php';

try {
    // 1. General Stats
    $totalConcursos = (int)$pdo->query("SELECT COUNT(*) FROM concursos")->fetchColumn();
    $totalParticipantes = (int)$pdo->query("SELECT COUNT(*) FROM feria_participantes")->fetchColumn();
    $totalAsistieron = (int)$pdo->query("SELECT COUNT(*) FROM feria_participantes WHERE asistio = 1")->fetchColumn();
    $totalEvaluaciones = (int)$pdo->query("SELECT COUNT(*) FROM evaluaciones")->fetchColumn();

    // 2. Fetch Contests with counts
    $sql = "
        SELECT 
            c.id,
            c.sheet_name,
            c.title,
            c.category,
            c.total_max_points,
            c.created_at,
            COUNT(DISTINCT crit.id) as criterios_count,
            COUNT(DISTINCT cp.participante_id) as participantes_count,
            COUNT(DISTINCT e.id) as evaluaciones_count,
            COALESCE(AVG(e.puntaje_total), 0) as promedio_puntaje,
            COALESCE(MAX(e.puntaje_total), 0) as max_puntaje
        FROM concursos c
        LEFT JOIN criterios crit ON crit.concurso_id = c.id
        LEFT JOIN concurso_participaciones cp ON cp.concurso_id = c.id
        LEFT JOIN evaluaciones e ON e.concurso_participacion_id = cp.id
        GROUP BY c.id
        ORDER BY c.id ASC
    ";
    
    $stmt = $pdo->query($sql);
    $concursos = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $evalCount = (int)$row['evaluaciones_count'];
        $partCount = (int)$row['participantes_count'];
        
        $status = 'Pendiente';
        if ($evalCount > 0 && $evalCount >= $partCount && $partCount > 0) {
            $status = 'Finalizado';
        } elseif ($evalCount > 0) {
            $status = 'En Evaluación';
        }

        $concursos[] = [
            'id' => (int)$row['id'],
            'sheet_name' => $row['sheet_name'],
            'title' => $row['title'],
            'category' => $row['category'] ?: 'General / Tradicional',
            'total_max_points' => (int)$row['total_max_points'],
            'criterios_count' => (int)$row['criterios_count'],
            'participantes_count' => $partCount,
            'evaluaciones_count' => $evalCount,
            'promedio_puntaje' => round((float)$row['promedio_puntaje'], 1),
            'max_puntaje' => (int)$row['max_puntaje'],
            'status' => $status
        ];
    }

    // 3. Categories
    $stmtCat = $pdo->query("SELECT DISTINCT category FROM concursos WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
    $categories = $stmtCat->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_concursos' => $totalConcursos,
            'total_participantes' => $totalParticipantes,
            'total_asistieron' => $totalAsistieron,
            'total_evaluaciones' => $totalEvaluaciones
        ],
        'categories' => $categories,
        'concursos' => $concursos
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener resumen de concursos: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
