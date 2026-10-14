<?php
/**
 * API: Submit New Kardex Movement (Ingreso o Salida de Inventario)
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
}

$productoId = isset($input['producto_id']) ? (int)$input['producto_id'] : 0;
$tipo = isset($input['tipo']) ? strtoupper(trim($input['tipo'])) : '';
$cantidad = isset($input['cantidad']) ? (float)$input['cantidad'] : 0.0;
$responsable = isset($input['responsable']) ? trim($input['responsable']) : '';
$cedulaResponsable = isset($input['cedula_responsable']) ? trim($input['cedula_responsable']) : '';
$vereda = isset($input['vereda']) ? trim($input['vereda']) : '';
$concepto = isset($input['concepto']) ? trim($input['concepto']) : '';
$docRef = isset($input['documento_referencia']) ? trim($input['documento_referencia']) : '';
$comprobanteUrl = isset($input['comprobante_url']) ? trim($input['comprobante_url']) : null;
$solicitudId = isset($input['solicitud_id']) && !empty($input['solicitud_id']) ? (int)$input['solicitud_id'] : null;
$numeroProyecto = isset($input['numero_proyecto']) ? trim($input['numero_proyecto']) : null;
$observaciones = isset($input['observaciones']) ? trim($input['observaciones']) : '';
$fechaMov = isset($input['fecha_movimiento']) && !empty($input['fecha_movimiento']) ? trim($input['fecha_movimiento']) : date('Y-m-d H:i:s');
$fechaIngreso = isset($input['fecha_ingreso']) && !empty($input['fecha_ingreso']) ? trim($input['fecha_ingreso']) : ($tipo === 'INGRESO' ? $fechaMov : null);
$fechaSalida = isset($input['fecha_salida']) && !empty($input['fecha_salida']) ? trim($input['fecha_salida']) : ($tipo === 'SALIDA' ? $fechaMov : null);

// Validation
if ($productoId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Debe seleccionar un insumo / producto válido.']);
    exit;
}

if (!in_array($tipo, ['INGRESO', 'SALIDA'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El tipo de movimiento debe ser INGRESO o SALIDA.']);
    exit;
}

if ($cantidad <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'La cantidad debe ser un número mayor a 0.']);
    exit;
}

if (empty($responsable)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El nombre del responsable es obligatorio.']);
    exit;
}

if (empty($concepto)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El concepto o motivo del movimiento es obligatorio.']);
    exit;
}

// -------------------------------------------------------------
// CONTROL ESTRICTO DE PAZ Y SALVO POR PROYECTO Y PRODUCTOR
// Si es una SALIDA y se relaciona cédula o número de proyecto,
// verificar que no tenga entregas previas sin Paz y Salvo.
// -------------------------------------------------------------
if ($tipo === 'SALIDA') {
    if (!empty($cedulaResponsable) || !empty($numeroProyecto)) {
        $pazCheckSql = "
            SELECT codigo_solicitud, nombre_productor, numero_proyecto, estado, fecha_entrega 
            FROM kardex_solicitudes 
            WHERE (
                (:cedula != '' AND cedula_productor = :cedula)
                OR (:proyecto != '' AND numero_proyecto = :proyecto)
            )
            AND estado IN ('DESPACHADO', 'EVIDENCIAS_CARGADAS')
            AND paz_y_salvo = 0
            LIMIT 1
        ";
        $stmtPaz = $pdo->prepare($pazCheckSql);
        $stmtPaz->execute([
            'cedula' => $cedulaResponsable ?: '',
            'proyecto' => $numeroProyecto ?: ''
        ]);
        $solicitudBloqueante = $stmtPaz->fetch(PDO::FETCH_ASSOC);

        if ($solicitudBloqueante) {
            http_response_code(400);
            $fechaBloq = !empty($solicitudBloqueante['fecha_entrega']) ? date('d/m/Y', strtotime($solicitudBloqueante['fecha_entrega'])) : 'previa';
            echo json_encode([
                'success' => false,
                'error' => "BLOQUEO POR PAZ Y SALVO: El productor ({$solicitudBloqueante['nombre_productor']}) o el proyecto ({$solicitudBloqueante['numero_proyecto']}) tiene entregas anteriores pendientes sin Paz y Salvo (Solicitud {$solicitudBloqueante['codigo_solicitud']} del {$fechaBloq}). Debe registrarse el acta y evidencias de entrega y concederse el Paz y Salvo antes de autorizar nuevas salidas."
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}

$usuarioId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 14;

try {
    $pdo->beginTransaction();

    // Lock product row for update
    $stmtProd = $pdo->prepare("SELECT id, nombre, stock_actual, unidad_medida FROM kardex_productos WHERE id = :id FOR UPDATE");
    $stmtProd->execute(['id' => $productoId]);
    $producto = $stmtProd->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'El insumo seleccionado no existe.']);
        exit;
    }

    $stockAnterior = (float)$producto['stock_actual'];

    // Check stock for SALIDA
    if ($tipo === 'SALIDA' && $cantidad > $stockAnterior) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'error' => "Stock insuficiente para {$producto['nombre']}. Disponible actual: {$stockAnterior} {$producto['unidad_medida']}. Solicitado: {$cantidad} {$producto['unidad_medida']}."
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Calculate new stock
    if ($tipo === 'INGRESO') {
        $stockResultante = $stockAnterior + $cantidad;
    } else {
        $stockResultante = $stockAnterior - $cantidad;
    }

    // Update product stock
    $stmtUpd = $pdo->prepare("UPDATE kardex_productos SET stock_actual = :new_stock WHERE id = :id");
    $stmtUpd->execute([
        'new_stock' => $stockResultante,
        'id' => $productoId
    ]);

    // Insert movement record
    $stmtMov = $pdo->prepare("
        INSERT INTO kardex_movimientos (
            producto_id, tipo, cantidad, stock_anterior, stock_resultante, 
            fecha_movimiento, fecha_ingreso, fecha_salida, usuario_id, 
            responsable, cedula_responsable, vereda, concepto, 
            documento_referencia, comprobante_url, solicitud_id, numero_proyecto, observaciones
        ) VALUES (
            :producto_id, :tipo, :cantidad, :stock_anterior, :stock_resultante, 
            :fecha_movimiento, :fecha_ingreso, :fecha_salida, :usuario_id, 
            :responsable, :cedula_responsable, :vereda, :concepto, 
            :documento_referencia, :comprobante_url, :solicitud_id, :numero_proyecto, :observaciones
        )
    ");

    $stmtMov->execute([
        'producto_id' => $productoId,
        'tipo' => $tipo,
        'cantidad' => $cantidad,
        'stock_anterior' => $stockAnterior,
        'stock_resultante' => $stockResultante,
        'fecha_movimiento' => $fechaMov,
        'fecha_ingreso' => $fechaIngreso,
        'fecha_salida' => $fechaSalida,
        'usuario_id' => $usuarioId,
        'responsable' => $responsable,
        'cedula_responsable' => $cedulaResponsable,
        'vereda' => $vereda,
        'concepto' => $concepto,
        'documento_referencia' => $docRef,
        'comprobante_url' => $comprobanteUrl,
        'solicitud_id' => $solicitudId,
        'numero_proyecto' => $numeroProyecto,
        'observaciones' => $observaciones
    ]);

    $movimientoId = $pdo->lastInsertId();

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => ($tipo === 'INGRESO') ? "Ingreso de {$cantidad} {$producto['unidad_medida']} registrado con éxito." : "Salida de {$cantidad} {$producto['unidad_medida']} registrada con éxito.",
        'movimiento_id' => $movimientoId,
        'producto' => [
            'id' => $productoId,
            'nombre' => $producto['nombre'],
            'stock_anterior' => $stockAnterior,
            'stock_actual' => $stockResultante,
            'unidad_medida' => $producto['unidad_medida']
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al registrar el movimiento de Kardex: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
