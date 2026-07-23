<?php
/**
 * API Endpoint: Submit / Update PMAPC Form Data into Relational MySQL Tables
 * Saves master data, relational tables (F01 to F26), and dedicated comments table.
 * Populates any missing fields with 'NaN'.
 */

require_once 'db_config.php';
require_once 'setup_pmapc_db_schema.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

// Helper to return 'NaN' for missing or empty values
function nan_val($val) {
    if ($val === null || $val === '' || (is_string($val) && trim($val) === '')) {
        return 'NaN';
    }
    return is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : (string)$val;
}

// 1. Parse input data
$inputRaw = file_get_contents('php://input');
$inputData = json_decode($inputRaw, true);

if (!$inputData) {
    $productor_id = $_POST['productor_id'] ?? null;
    $pmapc_data = $_POST['data'] ?? null;
} else {
    $productor_id = $inputData['productor_id'] ?? null;
    $pmapc_data = $inputData['data'] ?? null;
}

if (!$productor_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El ID del productor es requerido.']);
    exit;
}

if (!$pmapc_data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Los datos del PMAPC son requeridos.']);
    exit;
}

$dataArr = is_array($pmapc_data) ? $pmapc_data : json_decode($pmapc_data, true);
if (!is_array($dataArr)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Estructura de datos inválida. Debe ser JSON.']);
    exit;
}
$pmapc_data_json = json_encode($dataArr, JSON_UNESCAPED_UNICODE);

try {
    $pdo->beginTransaction();

    // A. Master Table UPSERT
    $stmtMaster = $pdo->prepare("
        INSERT INTO pmapc_registros (productor_id, nombre_organizacion, estado_actual, data) 
        VALUES (?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
            nombre_organizacion = VALUES(nombre_organizacion),
            estado_actual = VALUES(estado_actual),
            data = VALUES(data),
            updated_at = CURRENT_TIMESTAMP
    ");

    $nombreOrg = nan_val($dataArr['f01']['nombre_organizacion'] ?? ($dataArr['f01_nombre_organizacion'] ?? ''));
    $estadoAct = nan_val($dataArr['f01']['estado_actual'] ?? ($dataArr['f01_estado_actual'] ?? ''));

    $stmtMaster->execute([$productor_id, $nombreOrg, $estadoAct, $pmapc_data_json]);

    // Fetch registro_id
    $stmtRegId = $pdo->prepare("SELECT id FROM pmapc_registros WHERE productor_id = ?");
    $stmtRegId->execute([$productor_id]);
    $registro_id = $stmtRegId->fetchColumn();

    // B. Table pmapc_estrategico (F01, F02, F03, F04)
    $f01 = $dataArr['f01'] ?? [];
    $f02 = $dataArr['f02'] ?? [];
    $f03 = $dataArr['f03'] ?? [];
    $f04 = $dataArr['f04'] ?? [];

    $stmtEst = $pdo->prepare("
        INSERT INTO pmapc_estrategico (
            registro_id, productor_id,
            f01_nombre_organizacion, f01_tipo_actividad, f01_ubicacion, f01_coordenadas, f01_producto_principal, f01_estado_actual, f01_descripcion_general,
            f02_mision, f02_vision, f02_valores,
            f03_problema, f03_solucion, f03_diferencial, f03_valor_ambiental, f03_valor_social, f03_demostracion,
            f04_fortalezas, f04_oportunidades, f04_debilidades, f04_amenazas
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    // Clear old strategy record if exists
    $pdo->prepare("DELETE FROM pmapc_estrategico WHERE registro_id = ?")->execute([$registro_id]);

    $stmtEst->execute([
        $registro_id, $productor_id,
        nan_val($f01['nombre_organizacion'] ?? ($dataArr['f01_nombre_organizacion'] ?? '')),
        nan_val($f01['tipo_actividad'] ?? ($dataArr['f01_tipo_actividad'] ?? '')),
        nan_val($f01['ubicacion'] ?? ($dataArr['f01_ubicacion'] ?? '')),
        nan_val($f01['coordenadas'] ?? ($dataArr['f01_coordenadas'] ?? '')),
        nan_val($f01['producto_principal'] ?? ($dataArr['f01_producto_principal'] ?? '')),
        nan_val($f01['estado_actual'] ?? ($dataArr['f01_estado_actual'] ?? '')),
        nan_val($f01['descripcion_general'] ?? ($dataArr['f01_descripcion_general'] ?? '')),
        nan_val($f02['mision'] ?? ($dataArr['f02_mision'] ?? '')),
        nan_val($f02['vision'] ?? ($dataArr['f02_vision'] ?? '')),
        nan_val($f02['valores'] ?? ($dataArr['f02_valores'] ?? '')),
        nan_val($f03['problema'] ?? ($dataArr['f03_problema'] ?? '')),
        nan_val($f03['solucion'] ?? ($dataArr['f03_solucion'] ?? '')),
        nan_val($f03['diferencial'] ?? ($dataArr['f03_diferencial'] ?? '')),
        nan_val($f03['valor_ambiental'] ?? ($dataArr['f03_valor_ambiental'] ?? '')),
        nan_val($f03['valor_social'] ?? ($dataArr['f03_valor_social'] ?? '')),
        nan_val($f03['demostracion'] ?? ($dataArr['f03_demostracion'] ?? '')),
        nan_val($f04['fortalezas'] ?? ($dataArr['f04_fortalezas'] ?? '')),
        nan_val($f04['oportunidades'] ?? ($dataArr['f04_oportunidades'] ?? '')),
        nan_val($f04['debilidades'] ?? ($dataArr['f04_debilidades'] ?? '')),
        nan_val($f04['amenazas'] ?? ($dataArr['f04_amenazas'] ?? ''))
    ]);

    // C. Table pmapc_clientes (F05)
    $pdo->prepare("DELETE FROM pmapc_clientes WHERE registro_id = ?")->execute([$registro_id]);
    if (!empty($dataArr['f05']) && is_array($dataArr['f05'])) {
        $stmtF05 = $pdo->prepare("INSERT INTO pmapc_clientes (registro_id, productor_id, actor, perfil, ubicacion, necesidad, frecuencia, criterio, canal) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($dataArr['f05'] as $row) {
            $stmtF05->execute([
                $registro_id, $productor_id,
                nan_val($row['actor'] ?? ''),
                nan_val($row['perfil'] ?? ''),
                nan_val($row['ubicacion'] ?? ''),
                nan_val($row['necesidad'] ?? ''),
                nan_val($row['frecuencia'] ?? ''),
                nan_val($row['criterio'] ?? ''),
                nan_val($row['canal'] ?? '')
            ]);
        }
    }

    // D. Table pmapc_aliados (F07)
    $pdo->prepare("DELETE FROM pmapc_aliados WHERE registro_id = ?")->execute([$registro_id]);
    if (!empty($dataArr['f07']) && is_array($dataArr['f07'])) {
        $stmtF07 = $pdo->prepare("INSERT INTO pmapc_aliados (registro_id, productor_id, actor, aporta, recibe, trabajo, ambiental, accion) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($dataArr['f07'] as $row) {
            $stmtF07->execute([
                $registro_id, $productor_id,
                nan_val($row['actor'] ?? ''),
                nan_val($row['aporta'] ?? ''),
                nan_val($row['recibe'] ?? ''),
                nan_val($row['trabajo'] ?? ''),
                nan_val($row['ambiental'] ?? ''),
                nan_val($row['accion'] ?? '')
            ]);
        }
    }

    // E. Table pmapc_productos (F09)
    $pdo->prepare("DELETE FROM pmapc_productos WHERE registro_id = ?")->execute([$registro_id]);
    if (!empty($dataArr['f09']) && is_array($dataArr['f09'])) {
        $stmtF09 = $pdo->prepare("INSERT INTO pmapc_productos (registro_id, productor_id, producto, descripcion, unidad, insumos, almacenamiento, presentacion, diferencial) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($dataArr['f09'] as $row) {
            $stmtF09->execute([
                $registro_id, $productor_id,
                nan_val($row['producto'] ?? ''),
                nan_val($row['descripcion'] ?? ''),
                nan_val($row['unidad'] ?? ''),
                nan_val($row['insumos'] ?? ''),
                nan_val($row['almacenamiento'] ?? ''),
                nan_val($row['presentacion'] ?? ''),
                nan_val($row['diferencial'] ?? '')
            ]);
        }
    }

    // F. Table pmapc_equipos_bienes (F10)
    $pdo->prepare("DELETE FROM pmapc_equipos_bienes WHERE registro_id = ?")->execute([$registro_id]);
    if (!empty($dataArr['f10']) && is_array($dataArr['f10'])) {
        $stmtF10 = $pdo->prepare("INSERT INTO pmapc_equipos_bienes (registro_id, productor_id, bien, unidades, actividad, tiempo) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($dataArr['f10'] as $row) {
            $stmtF10->execute([
                $registro_id, $productor_id,
                nan_val($row['bien'] ?? ''),
                nan_val($row['unidades'] ?? ''),
                nan_val($row['actividad'] ?? ''),
                nan_val($row['tiempo'] ?? '')
            ]);
        }
    }

    // G. Table pmapc_insumos (F11)
    $pdo->prepare("DELETE FROM pmapc_insumos WHERE registro_id = ?")->execute([$registro_id]);
    if (!empty($dataArr['f11']) && is_array($dataArr['f11'])) {
        $stmtF11 = $pdo->prepare("INSERT INTO pmapc_insumos (registro_id, productor_id, insumo, cantidad, frecuencia, proveedor, toxicidad, impacto, manejo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($dataArr['f11'] as $row) {
            $stmtF11->execute([
                $registro_id, $productor_id,
                nan_val($row['insumo'] ?? ''),
                nan_val($row['cantidad'] ?? ''),
                nan_val($row['frecuencia'] ?? ''),
                nan_val($row['proveedor'] ?? ''),
                nan_val($row['toxicidad'] ?? ''),
                nan_val($row['impacto'] ?? ''),
                nan_val($row['manejo'] ?? '')
            ]);
        }
    }

    // H. Table pmapc_costos_precios (F14)
    $pdo->prepare("DELETE FROM pmapc_costos_precios WHERE registro_id = ?")->execute([$registro_id]);
    if (!empty($dataArr['f14']) && is_array($dataArr['f14'])) {
        $stmtF14 = $pdo->prepare("INSERT INTO pmapc_costos_precios (registro_id, productor_id, producto, costo, margen, pmin, pmercado, logistica, precio, justificacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($dataArr['f14'] as $row) {
            $stmtF14->execute([
                $registro_id, $productor_id,
                nan_val($row['producto'] ?? ''),
                nan_val($row['costo'] ?? ''),
                nan_val($row['margen'] ?? ''),
                nan_val($row['pmin'] ?? ''),
                nan_val($row['pmercado'] ?? ''),
                nan_val($row['logistica'] ?? ''),
                nan_val($row['precio'] ?? ''),
                nan_val($row['justificacion'] ?? '')
            ]);
        }
    }

    // I. Table pmapc_ventas (F15)
    $pdo->prepare("DELETE FROM pmapc_ventas WHERE registro_id = ?")->execute([$registro_id]);
    if (!empty($dataArr['f15']) && is_array($dataArr['f15'])) {
        $stmtF15 = $pdo->prepare("INSERT INTO pmapc_ventas (registro_id, productor_id, producto, cantidad, precio, ingresos, pago, cliente) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($dataArr['f15'] as $row) {
            $stmtF15->execute([
                $registro_id, $productor_id,
                nan_val($row['producto'] ?? ''),
                nan_val($row['cantidad'] ?? ''),
                nan_val($row['precio'] ?? ''),
                nan_val($row['ingresos'] ?? ''),
                nan_val($row['pago'] ?? ''),
                nan_val($row['cliente'] ?? '')
            ]);
        }
    }

    // J. Table pmapc_inversiones (F16)
    $pdo->prepare("DELETE FROM pmapc_inversiones WHERE registro_id = ?")->execute([$registro_id]);
    if (!empty($dataArr['f16']) && is_array($dataArr['f16'])) {
        $stmtF16 = $pdo->prepare("INSERT INTO pmapc_inversiones (registro_id, productor_id, descripcion, valunit, cant, total, req, fuente) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($dataArr['f16'] as $row) {
            $stmtF16->execute([
                $registro_id, $productor_id,
                nan_val($row['desc'] ?? ($row['descripcion'] ?? '')),
                nan_val($row['valunit'] ?? ''),
                nan_val($row['cant'] ?? ''),
                nan_val($row['total'] ?? ''),
                nan_val($row['req'] ?? ''),
                nan_val($row['fuente'] ?? '')
            ]);
        }
    }

    // K. Table pmapc_costos_fijos (F17)
    $pdo->prepare("DELETE FROM pmapc_costos_fijos WHERE registro_id = ?")->execute([$registro_id]);
    if (!empty($dataArr['f17']) && is_array($dataArr['f17'])) {
        $stmtF17 = $pdo->prepare("INSERT INTO pmapc_costos_fijos (registro_id, productor_id, descripcion, val, obs) VALUES (?, ?, ?, ?, ?)");
        foreach ($dataArr['f17'] as $row) {
            $stmtF17->execute([
                $registro_id, $productor_id,
                nan_val($row['desc'] ?? ($row['descripcion'] ?? '')),
                nan_val($row['val'] ?? ''),
                nan_val($row['obs'] ?? '')
            ]);
        }
    }

    // L. Table pmapc_economia_circular (F20)
    $pdo->prepare("DELETE FROM pmapc_economia_circular WHERE registro_id = ?")->execute([$registro_id]);
    if (!empty($dataArr['f20']) && is_array($dataArr['f20'])) {
        $stmtF20 = $pdo->prepare("INSERT INTO pmapc_economia_circular (registro_id, productor_id, cant, manejo, destino, resp) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($dataArr['f20'] as $row) {
            $stmtF20->execute([
                $registro_id, $productor_id,
                nan_val($row['cant'] ?? ''),
                nan_val($row['manejo'] ?? ''),
                nan_val($row['destino'] ?? ''),
                nan_val($row['resp'] ?? '')
            ]);
        }
    }

    // M. Table pmapc_plan_trabajo (F24)
    $pdo->prepare("DELETE FROM pmapc_plan_trabajo WHERE registro_id = ?")->execute([$registro_id]);
    if (!empty($dataArr['f24']) && is_array($dataArr['f24'])) {
        $stmtF24 = $pdo->prepare("INSERT INTO pmapc_plan_trabajo (registro_id, productor_id, actividad, componente, responsable, tiempo, resultado) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($dataArr['f24'] as $row) {
            $stmtF24->execute([
                $registro_id, $productor_id,
                nan_val($row['actividad'] ?? ''),
                nan_val($row['componente'] ?? ''),
                nan_val($row['responsable'] ?? ''),
                nan_val($row['tiempo'] ?? ''),
                nan_val($row['resultado'] ?? '')
            ]);
        }
    }

    // N. Dedicated Comments Table: pmapc_comentarios
    $pdo->prepare("DELETE FROM pmapc_comentarios WHERE registro_id = ?")->execute([$registro_id]);
    $stmtCom = $pdo->prepare("
        INSERT INTO pmapc_comentarios (registro_id, productor_id, origen_archivo, comentarios_texto, informacion_pendiente, conclusion_general, recomendaciones)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $comentariosFull = nan_val($dataArr['pdf_comentarios'] ?? ($dataArr['comentarios'] ?? ''));
    $stmtCom->execute([
        $registro_id,
        $productor_id,
        nan_val($dataArr['origen_archivo'] ?? 'JSON / PDF Upload'),
        $comentariosFull,
        $comentariosFull, // Stores observations text
        nan_val($dataArr['conclusion_general'] ?? ''),
        nan_val($dataArr['recomendaciones'] ?? '')
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'registro_id' => $registro_id,
        'message' => 'PMAPC y sus 14 tablas relacionales (incluyendo pmapc_comentarios) guardados exitosamente con valores por defecto NaN.'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("PMAPC relational save error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Error al guardar el modelo relacional del PMAPC: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
