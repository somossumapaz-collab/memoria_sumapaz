<?php
/**
 * API: Get Kardex Solicitudes de Insumos
 * Supports:
 * - Admin view: List all with filters (estado, numero_proyecto, search, vereda)
 * - Public single view: by `token` OR `codigo` + `cedula`
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_config.php';

try {
    $token = isset($_GET['token']) ? trim($_GET['token']) : '';
    $codigo = isset($_GET['codigo']) ? trim($_GET['codigo']) : '';
    $cedula = isset($_GET['cedula']) ? trim($_GET['cedula']) : '';
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    // Public single lookup
    if (!empty($token) || (!empty($codigo) && !empty($cedula)) || $id > 0) {
        $where = [];
        $params = [];

        if (!empty($token)) {
            $where[] = "s.token_seguimiento = :token";
            $params['token'] = $token;
        } elseif (!empty($codigo) && !empty($cedula)) {
            $where[] = "s.codigo_solicitud = :codigo AND s.cedula_productor = :cedula";
            $params['codigo'] = $codigo;
            $params['cedula'] = $cedula;
        } elseif ($id > 0) {
            $where[] = "s.id = :id";
            $params['id'] = $id;
        }

        $sql = "
            SELECT 
                s.*,
                COALESCE(u_desp.nombre, 'Admin') as despachado_por_nombre,
                COALESCE(u_paz.nombre, 'Admin') as paz_y_salvo_por_nombre
            FROM kardex_solicitudes s
            LEFT JOIN usuarios u_desp ON u_desp.id = s.despachado_por
            LEFT JOIN usuarios u_paz ON u_paz.id = s.usuario_paz_y_salvo
            WHERE " . implode(" AND ", $where) . "
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$solicitud) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'No se encontró la solicitud de insumos']);
            exit;
        }

        // Fetch items
        $stmtItems = $pdo->prepare("
            SELECT 
                i.*,
                p.nombre as producto_nombre,
                p.stock_actual,
                p.imagen_url as producto_imagen_url
            FROM kardex_solicitud_items i
            LEFT JOIN kardex_productos p ON p.id = i.producto_id
            WHERE i.solicitud_id = :sol_id
            ORDER BY i.id ASC
        ");
        $stmtItems->execute(['sol_id' => $solicitud['id']]);
        $solicitud['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        // Decode evidencias_fotos if present
        $solicitud['evidencias_fotos_array'] = !empty($solicitud['evidencias_fotos']) ? json_decode($solicitud['evidencias_fotos'], true) : [];

        // Check if there are unclosed previous dispatches for this producer / project
        $stmtCheckBlock = $pdo->prepare("
            SELECT id, codigo_solicitud, fecha_entrega 
            FROM kardex_solicitudes 
            WHERE (cedula_productor = :cedula OR numero_proyecto = :proyecto) 
              AND estado IN ('DESPACHADO', 'EVIDENCIAS_CARGADAS')
              AND paz_y_salvo = 0 
              AND id != :curr_id
            LIMIT 1
        ");
        $stmtCheckBlock->execute([
            'cedula' => $solicitud['cedula_productor'],
            'proyecto' => $solicitud['numero_proyecto'],
            'curr_id' => $solicitud['id']
        ]);
        $blockInfo = $stmtCheckBlock->fetch(PDO::FETCH_ASSOC);
        $solicitud['tiene_bloqueo_paz_y_salvo'] = !empty($blockInfo);
        $solicitud['solicitud_bloqueante'] = $blockInfo;

        echo json_encode([
            'success' => true,
            'solicitud' => $solicitud
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // Admin List view
    $estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';
    $proyecto = isset($_GET['numero_proyecto']) ? trim($_GET['numero_proyecto']) : '';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $vereda = isset($_GET['vereda']) ? trim($_GET['vereda']) : '';

    $where = ["1=1"];
    $params = [];

    if ($estado !== '' && $estado !== 'all') {
        if ($estado === 'PENDIENTES_EVIDENCIA') {
            $where[] = "s.estado = 'DESPACHADO'";
        } elseif ($estado === 'PENDIENTES_PAZ_Y_SALVO') {
            $where[] = "s.estado = 'EVIDENCIAS_CARGADAS'";
        } else {
            $where[] = "s.estado = :estado";
            $params['estado'] = $estado;
        }
    }

    if ($proyecto !== '' && $proyecto !== 'all') {
        $where[] = "s.numero_proyecto LIKE :proyecto";
        $params['proyecto'] = "%$proyecto%";
    }

    if ($vereda !== '' && $vereda !== 'all') {
        $where[] = "s.vereda = :vereda";
        $params['vereda'] = $vereda;
    }

    if ($search !== '') {
        $where[] = "(s.codigo_solicitud LIKE :search OR s.nombre_productor LIKE :search OR s.cedula_productor LIKE :search OR s.nombre_predio LIKE :search OR s.solicitante_nombre LIKE :search OR s.numero_proyecto LIKE :search)";
        $params['search'] = "%$search%";
    }

    $sql = "
        SELECT 
            s.id,
            s.codigo_solicitud,
            s.token_seguimiento,
            s.fecha_solicitud,
            s.fecha_retiro,
            s.numero_proyecto,
            s.vereda,
            s.nombre_predio,
            s.nombre_productor,
            s.cedula_productor,
            s.telefono_productor,
            s.solicitante_nombre,
            s.solicitante_cps,
            s.estado,
            s.fecha_entrega,
            s.foto_entrega_url,
            s.acta_entrega_url,
            s.evidencias_fotos,
            s.paz_y_salvo,
            s.fecha_paz_y_salvo,
            s.created_at,
            COUNT(i.id) as total_items,
            SUM(i.cantidad_solicitada) as suma_cantidades
        FROM kardex_solicitudes s
        LEFT JOIN kardex_solicitud_items i ON i.solicitud_id = s.id
        WHERE " . implode(" AND ", $where) . "
        GROUP BY s.id
        ORDER BY s.id DESC
        LIMIT 250
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Summary counts
    $stmtCounts = $pdo->query("
        SELECT 
            COUNT(id) as total,
            SUM(CASE WHEN estado = 'PENDIENTE' THEN 1 ELSE 0 END) as pendientes,
            SUM(CASE WHEN estado = 'DESPACHADO' THEN 1 ELSE 0 END) as despachados_sin_evidencias,
            SUM(CASE WHEN estado = 'EVIDENCIAS_CARGADAS' THEN 1 ELSE 0 END) as listas_paz_y_salvo,
            SUM(CASE WHEN estado = 'PAZ_Y_SALVO' THEN 1 ELSE 0 END) as con_paz_y_salvo
        FROM kardex_solicitudes
    ");
    $counts = $stmtCounts->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'counts' => $counts,
        'solicitudes' => $solicitudes
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al consultar solicitudes: ' . $e->getMessage()]);
}
