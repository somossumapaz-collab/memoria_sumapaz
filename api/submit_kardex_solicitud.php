<?php
/**
 * API: Submit Public Kardex Solicitud de Insumos
 * Formato oficial: "Formato_Solicitud_Insumos Wilson Rey Moreno _ septiembre.xlsx"
 * No requiere login previo.
 */
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

// Extract and sanitize header fields
$fechaSolicitud = isset($input['fecha_solicitud']) && !empty($input['fecha_solicitud']) ? trim($input['fecha_solicitud']) : date('Y-m-d');
$fechaRetiro = isset($input['fecha_retiro']) && !empty($input['fecha_retiro']) ? trim($input['fecha_retiro']) : null;
$numeroProyecto = isset($input['numero_proyecto']) ? trim($input['numero_proyecto']) : '';
$lineaInversion = isset($input['linea_inversion']) ? trim($input['linea_inversion']) : '';
$meta = isset($input['meta']) ? trim($input['meta']) : '';
$vereda = isset($input['vereda']) ? trim($input['vereda']) : '';
$nombrePredio = isset($input['nombre_predio']) ? trim($input['nombre_predio']) : '';
$nombreProductor = isset($input['nombre_productor']) ? trim($input['nombre_productor']) : '';
$cedulaProductor = isset($input['cedula_productor']) ? trim($input['cedula_productor']) : '';
$telefonoProductor = isset($input['telefono_productor']) ? trim($input['telefono_productor']) : '';
$actividad = isset($input['actividad']) ? trim($input['actividad']) : '';
$solicitanteNombre = isset($input['solicitante_nombre']) ? trim($input['solicitante_nombre']) : '';
$solicitanteCps = isset($input['solicitante_cps']) ? trim($input['solicitante_cps']) : '';
$solicitanteCargo = isset($input['solicitante_cargo']) ? trim($input['solicitante_cargo']) : '';
$solicitanteFirma = isset($input['solicitante_firma']) ? trim($input['solicitante_firma']) : null;
$observaciones = isset($input['observaciones']) ? trim($input['observaciones']) : '';
$items = isset($input['items']) && is_array($input['items']) ? $input['items'] : [];

// Basic validation
if (empty($numeroProyecto)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Debe indicar el número de proyecto / línea de inversión.']);
    exit;
}

if (empty($vereda)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Debe seleccionar la vereda de destino.']);
    exit;
}

if (empty($nombreProductor) || empty($cedulaProductor)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Los datos del productor / beneficiario (nombre y cédula) son obligatorios.']);
    exit;
}

if (empty($solicitanteNombre)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Debe indicar el nombre de quien solicita.']);
    exit;
}

if (empty($items)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Debe agregar al menos un insumo en la solicitud.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Generate unique sequential request code
    $year = date('Y', strtotime($fechaSolicitud));
    $stmtCount = $pdo->query("SELECT COUNT(*) FROM kardex_solicitudes WHERE YEAR(fecha_solicitud) = {$year}");
    $seq = (int)$stmtCount->fetchColumn() + 1;
    $codigoSolicitud = sprintf("SOL-%s-%04d", $year, $seq);

    // Verify uniqueness just in case
    $stmtCheck = $pdo->prepare("SELECT id FROM kardex_solicitudes WHERE codigo_solicitud = :cod");
    $stmtCheck->execute(['cod' => $codigoSolicitud]);
    if ($stmtCheck->fetch()) {
        $codigoSolicitud = sprintf("SOL-%s-%04d-%s", $year, $seq, substr(uniqid(), -3));
    }

    $tokenSeguimiento = bin2hex(random_bytes(16));

    // Save signature if sent as data URL
    if ($solicitanteFirma && strpos($solicitanteFirma, 'data:image') === 0) {
        $uploadDirFirma = dirname(__DIR__) . '/uploads/kardex/firmas/';
        if (!file_exists($uploadDirFirma)) {
            @mkdir($uploadDirFirma, 0777, true);
        }
        $parts = explode(',', $solicitanteFirma);
        if (count($parts) === 2) {
            $data = base64_decode($parts[1]);
            $firmaFilename = 'firma_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.png';
            file_put_contents($uploadDirFirma . $firmaFilename, $data);
            $solicitanteFirma = 'uploads/kardex/firmas/' . $firmaFilename;
        }
    }

    $stmtSol = $pdo->prepare("
        INSERT INTO kardex_solicitudes (
            codigo_solicitud, token_seguimiento, fecha_solicitud, fecha_retiro, 
            numero_proyecto, linea_inversion, meta, vereda, nombre_predio, 
            nombre_productor, cedula_productor, telefono_productor, actividad, 
            solicitante_nombre, solicitante_cps, solicitante_cargo, solicitante_firma, 
            observaciones, estado
        ) VALUES (
            :codigo_solicitud, :token_seguimiento, :fecha_solicitud, :fecha_retiro, 
            :numero_proyecto, :linea_inversion, :meta, :vereda, :nombre_predio, 
            :nombre_productor, :cedula_productor, :telefono_productor, :actividad, 
            :solicitante_nombre, :solicitante_cps, :solicitante_cargo, :solicitante_firma, 
            :observaciones, 'PENDIENTE'
        )
    ");

    $stmtSol->execute([
        'codigo_solicitud' => $codigoSolicitud,
        'token_seguimiento' => $tokenSeguimiento,
        'fecha_solicitud' => $fechaSolicitud,
        'fecha_retiro' => $fechaRetiro,
        'numero_proyecto' => $numeroProyecto,
        'linea_inversion' => $lineaInversion,
        'meta' => $meta,
        'vereda' => $vereda,
        'nombre_predio' => $nombrePredio,
        'nombre_productor' => $nombreProductor,
        'cedula_productor' => $cedulaProductor,
        'telefono_productor' => $telefonoProductor,
        'actividad' => $actividad,
        'solicitante_nombre' => $solicitanteNombre,
        'solicitante_cps' => $solicitanteCps,
        'solicitante_cargo' => $solicitanteCargo,
        'solicitante_firma' => $solicitanteFirma,
        'observaciones' => $observaciones
    ]);

    $solicitudId = $pdo->lastInsertId();

    // Insert items
    $stmtItem = $pdo->prepare("
        INSERT INTO kardex_solicitud_items (
            solicitud_id, producto_id, codigo_insumo, descripcion, unidad_medida, cantidad_solicitada
        ) VALUES (
            :solicitud_id, :producto_id, :codigo_insumo, :descripcion, :unidad_medida, :cantidad_solicitada
        )
    ");

    foreach ($items as $it) {
        $desc = isset($it['descripcion']) ? trim($it['descripcion']) : '';
        $cant = isset($it['cantidad_solicitada']) ? (float)$it['cantidad_solicitada'] : 0.0;
        $unid = isset($it['unidad_medida']) && !empty($it['unidad_medida']) ? trim($it['unidad_medida']) : 'Unidad';
        $prodId = isset($it['producto_id']) && !empty($it['producto_id']) ? (int)$it['producto_id'] : null;
        $codIns = isset($it['codigo_insumo']) ? trim($it['codigo_insumo']) : null;

        if (empty($desc) || $cant <= 0) {
            continue;
        }

        $stmtItem->execute([
            'solicitud_id' => $solicitudId,
            'producto_id' => $prodId,
            'codigo_insumo' => $codIns,
            'descripcion' => $desc,
            'unidad_medida' => $unid,
            'cantidad_solicitada' => $cant
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Solicitud de insumos registrada exitosamente.',
        'solicitud_id' => $solicitudId,
        'codigo_solicitud' => $codigoSolicitud,
        'token_seguimiento' => $tokenSeguimiento,
        'nombre_productor' => $nombreProductor,
        'cedula_productor' => $cedulaProductor,
        'numero_proyecto' => $numeroProyecto
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al guardar la solicitud: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
