<?php
/**
 * API: Despachar Solicitud de Insumos (Salida de Inventario)
 * Validaciones:
 * - REGLA ESTRICTA DE PAZ Y SALVO: Bloquea si el productor o proyecto tiene entregas previas sin Paz y Salvo.
 * - Validación de stock suficiente en kardex_productos.
 * - Registro fotográfico obligatorio de la entrega en bodega.
 * - Generación automática de los movimientos tipo SALIDA en kardex_movimientos.
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!$input && !empty($_POST)) {
    $input = $_POST;
    if (isset($input['items']) && is_string($input['items'])) {
        $input['items'] = json_decode($input['items'], true);
    }
}

$solicitudId = isset($input['solicitud_id']) ? (int)$input['solicitud_id'] : 0;
$fechaEntrega = isset($input['fecha_entrega']) && !empty($input['fecha_entrega']) ? trim($input['fecha_entrega']) : date('Y-m-d H:i:s');
$fotoEntregaUrl = isset($input['foto_entrega_url']) ? trim($input['foto_entrega_url']) : '';
$items = isset($input['items']) && is_array($input['items']) ? $input['items'] : [];
$usuarioId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 14;

if ($solicitudId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de solicitud inválido']);
    exit;
}

if (empty($fotoEntregaUrl)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Es obligatorio adjuntar la foto / evidencia de entrega de insumos en bodega.']);
    exit;
}

if (empty($items)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Debe relacionar las cantidades entregadas de los insumos.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Fetch solicitud and lock it
    $stmtSol = $pdo->prepare("SELECT * FROM kardex_solicitudes WHERE id = :id FOR UPDATE");
    $stmtSol->execute(['id' => $solicitudId]);
    $solicitud = $stmtSol->fetch(PDO::FETCH_ASSOC);

    if (!$solicitud) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'La solicitud no existe']);
        exit;
    }

    if ($solicitud['estado'] !== 'PENDIENTE') {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'error' => "Esta solicitud ya se encuentra en estado '{$solicitud['estado']}' y no puede ser despachada nuevamente."
        ]);
        exit;
    }

    // 2. CONTROL ESTRICTO DE PAZ Y SALVO POR PRODUCTOR Y PROYECTO
    $stmtPazCheck = $pdo->prepare("
        SELECT id, codigo_solicitud, nombre_productor, numero_proyecto, fecha_entrega, estado
        FROM kardex_solicitudes
        WHERE (cedula_productor = :cedula OR numero_proyecto = :proyecto)
          AND estado IN ('DESPACHADO', 'EVIDENCIAS_CARGADAS')
          AND paz_y_salvo = 0
          AND id != :curr_id
        LIMIT 1
    ");
    $stmtPazCheck->execute([
        'cedula' => $solicitud['cedula_productor'],
        'proyecto' => $solicitud['numero_proyecto'],
        'curr_id' => $solicitudId
    ]);
    $solicitudBloqueante = $stmtPazCheck->fetch(PDO::FETCH_ASSOC);

    if ($solicitudBloqueante) {
        $pdo->rollBack();
        http_response_code(400);
        $fechaBloq = !empty($solicitudBloqueante['fecha_entrega']) ? date('d/m/Y', strtotime($solicitudBloqueante['fecha_entrega'])) : 'anterior';
        echo json_encode([
            'success' => false,
            'error' => "BLOQUEO DE SALIDA: El productor '{$solicitudBloqueante['nombre_productor']}' o el proyecto '{$solicitudBloqueante['numero_proyecto']}' tiene una entrega pendiente sin Paz y Salvo (Solicitud {$solicitudBloqueante['codigo_solicitud']} del {$fechaBloq}). Debe cargarse el acta firmada y evidencias, y aprobarse el Paz y Salvo antes de autorizar nuevos despachos."
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 3. Process each item dispatch
    $stmtUpdItem = $pdo->prepare("
        UPDATE kardex_solicitud_items 
        SET producto_id = :producto_id, cantidad_entregada = :cantidad_entregada 
        WHERE id = :item_id AND solicitud_id = :sol_id
    ");

    $stmtGetProd = $pdo->prepare("SELECT id, nombre, stock_actual, unidad_medida FROM kardex_productos WHERE id = :id FOR UPDATE");
    $stmtUpdProdStock = $pdo->prepare("UPDATE kardex_productos SET stock_actual = :new_stock WHERE id = :id");

    $stmtMov = $pdo->prepare("
        INSERT INTO kardex_movimientos (
            producto_id, tipo, cantidad, stock_anterior, stock_resultante, 
            fecha_movimiento, fecha_salida, usuario_id, 
            responsable, cedula_responsable, vereda, concepto, 
            documento_referencia, comprobante_url, solicitud_id, numero_proyecto, observaciones
        ) VALUES (
            :producto_id, 'SALIDA', :cantidad, :stock_anterior, :stock_resultante, 
            :fecha_movimiento, :fecha_salida, :usuario_id, 
            :responsable, :cedula_responsable, :vereda, :concepto, 
            :documento_referencia, :comprobante_url, :solicitud_id, :numero_proyecto, :observaciones
        )
    ");

    $totalDespachados = 0;

    foreach ($items as $it) {
        $itemId = isset($it['item_id']) ? (int)$it['item_id'] : 0;
        $prodId = isset($it['producto_id']) ? (int)$it['producto_id'] : 0;
        $cantEntregada = isset($it['cantidad_entregada']) ? (float)$it['cantidad_entregada'] : 0.0;

        if ($cantEntregada <= 0) {
            continue;
        }

        if ($prodId <= 0) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Debe asociar cada insumo a un producto del catálogo de inventario.']);
            exit;
        }

        // Lock product
        $stmtGetProd->execute(['id' => $prodId]);
        $producto = $stmtGetProd->fetch(PDO::FETCH_ASSOC);

        if (!$producto) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => "El producto de inventario con ID {$prodId} no existe."]);
            exit;
        }

        $stockAnterior = (float)$producto['stock_actual'];

        if ($cantEntregada > $stockAnterior) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => "Stock insuficiente para '{$producto['nombre']}'. Disponible en inventario: {$stockAnterior} {$producto['unidad_medida']}. Cantidad a entregar: {$cantEntregada} {$producto['unidad_medida']}."
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stockResultante = $stockAnterior - $cantEntregada;

        // Update product stock
        $stmtUpdProdStock->execute([
            'new_stock' => $stockResultante,
            'id' => $prodId
        ]);

        // Update request item
        $stmtUpdItem->execute([
            'producto_id' => $prodId,
            'cantidad_entregada' => $cantEntregada,
            'item_id' => $itemId,
            'sol_id' => $solicitudId
        ]);

        // Insert SALIDA movement in Kardex
        $stmtMov->execute([
            'producto_id' => $prodId,
            'cantidad' => $cantEntregada,
            'stock_anterior' => $stockAnterior,
            'stock_resultante' => $stockResultante,
            'fecha_movimiento' => $fechaEntrega,
            'fecha_salida' => $fechaEntrega,
            'usuario_id' => $usuarioId,
            'responsable' => $solicitud['nombre_productor'],
            'cedula_responsable' => $solicitud['cedula_productor'],
            'vereda' => $solicitud['vereda'],
            'concepto' => "Salida por Despacho de Solicitud {$solicitud['codigo_solicitud']}",
            'documento_referencia' => $solicitud['codigo_solicitud'],
            'comprobante_url' => $fotoEntregaUrl,
            'solicitud_id' => $solicitudId,
            'numero_proyecto' => $solicitud['numero_proyecto'],
            'observaciones' => "Entrega para predio {$solicitud['nombre_predio']}, Vereda {$solicitud['vereda']}. Solicitado por: {$solicitud['solicitante_nombre']}"
        ]);

        $totalDespachados++;
    }

    if ($totalDespachados === 0) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No se indicó ninguna cantidad válida para despachar.']);
        exit;
    }

    // 4. Update solicitud status to DESPACHADO
    $stmtUpdSol = $pdo->prepare("
        UPDATE kardex_solicitudes 
        SET estado = 'DESPACHADO', 
            fecha_entrega = :fecha_entrega, 
            foto_entrega_url = :foto_entrega_url, 
            despachado_por = :usuario_id 
        WHERE id = :id
    ");

    $stmtUpdSol->execute([
        'fecha_entrega' => $fechaEntrega,
        'foto_entrega_url' => $fotoEntregaUrl,
        'usuario_id' => $usuarioId,
        'id' => $solicitudId
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => "Solicitud {$solicitud['codigo_solicitud']} despachada exitosamente. Se descontó el inventario y se generaron los movimientos de salida.",
        'solicitud_id' => $solicitudId,
        'codigo_solicitud' => $solicitud['codigo_solicitud'],
        'estado' => 'DESPACHADO'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al despachar solicitud: ' . $e->getMessage()]);
}
