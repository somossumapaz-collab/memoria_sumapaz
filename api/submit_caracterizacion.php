<?php
/**
 * API Endpoint: Submit / Update Caracterización Ficha
 * Handles structural items, multiple selects, and dynamic lists.
 */

require_once 'db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Ensure dynamic tables exist to prevent crashing if the user never executed a schema migration
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS productor_productos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            productor_id BIGINT UNSIGNED NOT NULL,
            nombre VARCHAR(255),
            volumen DECIMAL(10,2),
            unidad_volumen VARCHAR(50),
            frecuencia VARCHAR(50),
            presentacion VARCHAR(100),
            calidad VARCHAR(100),
            precio DECIMAL(10,2),
            unidad_precio VARCHAR(50),
            FOREIGN KEY (productor_id) REFERENCES productores_sumapaz(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS productor_servicios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            productor_id BIGINT UNSIGNED NOT NULL,
            nombre_actividad VARCHAR(255),
            frecuencia VARCHAR(50),
            poblacion_objetivo VARCHAR(100),
            tipo_contrato VARCHAR(100),
            lugar VARCHAR(255),
            recursos VARCHAR(255),
            FOREIGN KEY (productor_id) REFERENCES productores_sumapaz(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 2. Ensure missing fields are dynamically added to caracterizacion_productor
    $extra_columns = [
        'mano_obra VARCHAR(255)', 'tipo_proceso VARCHAR(255)', 'usa_abonos VARCHAR(10)', 
        'sistemas_asociados TEXT', 'sistema_diferenciado VARCHAR(255)', 'descripcion TEXT', 
        'valor_agregado TEXT', 'destino VARCHAR(255)', 'transporte VARCHAR(255)', 
        'forma_pago VARCHAR(255)', 'define_precio VARCHAR(255)', 'en_tramite_bool VARCHAR(10)', 
        'en_tramite VARCHAR(255)'
    ];
    foreach($extra_columns as $col) {
        try {
            // Extact column name ignoring type
            $colName = explode(' ', $col)[0];
            $pdo->exec("ALTER TABLE caracterizacion_productor ADD COLUMN $col");
        } catch (\PDOException $e) { /* Ignore Column already exists error */ }
    }

    // 3. Ensure discapacidad_productor exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS discapacidad_productor (
            id INT AUTO_INCREMENT PRIMARY KEY,
            productor_id BIGINT UNSIGNED NOT NULL,
            tiene_discapacidad VARCHAR(50),
            tipo VARCHAR(255),
            FOREIGN KEY (productor_id) REFERENCES productores_sumapaz(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Extract POST Payload
    $productor_id = $_POST['productor_id'] ?? null;
    if (!$productor_id) {
        throw new Exception("El ID del productor es requerido");
    }

    // Capture standard variables
    $fecha_caracterizacion = $_POST['fecha_caracterizacion'] ?? date('Y-m-d H:i:s');
    $coordenadas = $_POST['coordenadas'] ?? null;
    $tipo_organizacion = $_POST['tipo_organizacion'] ?? null;
    $extension_predio = (isset($_POST['extension_predio']) && $_POST['extension_predio'] !== '') ? $_POST['extension_predio'] : null;
    $tiempo_implementacion = (isset($_POST['tiempo_implementacion']) && $_POST['tiempo_implementacion'] !== '') ? $_POST['tiempo_implementacion'] : null;
    $tipo_tenencia = $_POST['tipo_tenencia'] ?? null;
    $numero_personas = (isset($_POST['numero_personas']) && $_POST['numero_personas'] !== '') ? $_POST['numero_personas'] : null;
    $nombre_organizacion = $_POST['nombre_organizacion'] ?? null;
    
    $mano_obra = $_POST['mano_obra'] ?? null;
    $tipo_proceso = $_POST['tipo_proceso'] ?? null;
    $usa_abonos = $_POST['usa_abonos'] ?? '0';
    $sistemas_asociados = $_POST['sistemas_asociados'] ?? '0';
    $sistema_diferenciado = $_POST['sistema_diferenciado'] ?? '0';
    $descripcion = $_POST['descripcion'] ?? null;
    $valor_agregado = $_POST['valor_agregado'] ?? null;
    $destino = $_POST['destino'] ?? null;
    $transporte = $_POST['transporte'] ?? null;
    $forma_pago = $_POST['forma_pago'] ?? null;
    $define_precio = $_POST['define_precio'] ?? null;
    $en_tramite_bool = $_POST['en_tramite_bool'] ?? 'No';
    $en_tramite = $_POST['en_tramite'] ?? null;

    // Ficha Caracterizacion base table
    $stmt = $pdo->prepare("SELECT id FROM caracterizacion_productor WHERE productor_id = ?");
    $stmt->execute([$productor_id]);
    $exist = $stmt->fetchColumn();

    if ($exist) {
        $upd = "UPDATE caracterizacion_productor SET 
            fecha_caracterizacion=?, coordenadas=?, tipo_organizacion=?, extension_predio=?, 
            tiempo_implementacion=?, tipo_tenencia=?, numero_personas=?, nombre_organizacion=?,
            mano_obra=?, tipo_proceso=?, usa_abonos=?, 
            sistemas_asociados=?, sistema_diferenciado=?, descripcion=?, valor_agregado=?, 
            destino=?, transporte=?, forma_pago=?, define_precio=?, en_tramite_bool=?, en_tramite=?
            WHERE productor_id = ?";
        $pdo->prepare($upd)->execute([
            $fecha_caracterizacion, $coordenadas, $tipo_organizacion, $extension_predio, 
            $tiempo_implementacion, $tipo_tenencia, $numero_personas, $nombre_organizacion,
            $mano_obra, $tipo_proceso, $usa_abonos, 
            $sistemas_asociados, $sistema_diferenciado, $descripcion, $valor_agregado, 
            $destino, $transporte, $forma_pago, $define_precio, $en_tramite_bool, $en_tramite,
            $productor_id
        ]);
    } else {
        $ins = "INSERT INTO caracterizacion_productor (
            productor_id, fecha_caracterizacion, coordenadas, tipo_organizacion, extension_predio, 
            tiempo_implementacion, tipo_tenencia, numero_personas, nombre_organizacion,
            mano_obra, tipo_proceso, usa_abonos, 
            sistemas_asociados, sistema_diferenciado, descripcion, valor_agregado, 
            destino, transporte, forma_pago, define_precio, en_tramite_bool, en_tramite
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($ins)->execute([
            $productor_id, $fecha_caracterizacion, $coordenadas, $tipo_organizacion, $extension_predio, 
            $tiempo_implementacion, $tipo_tenencia, $numero_personas, $nombre_organizacion,
            $mano_obra, $tipo_proceso, $usa_abonos, 
            $sistemas_asociados, $sistema_diferenciado, $descripcion, $valor_agregado, 
            $destino, $transporte, $forma_pago, $define_precio, $en_tramite_bool, $en_tramite
        ]);
    }

    // DISCAPACIDAD
    $pdo->prepare("DELETE FROM discapacidad_productor WHERE productor_id = ?")->execute([$productor_id]);
    $discapacidad = $_POST['discapacidad'] ?? 'No';
    $discapacidad_tipo = $_POST['discapacidad_tipo'] ?? null;
    if ($discapacidad === 'Sí') {
        $pdo->prepare("INSERT INTO discapacidad_productor (productor_id, tiene_discapacidad, tipo) VALUES (?, ?, ?)")
            ->execute([$productor_id, $discapacidad, $discapacidad_tipo]);
    }

    // MULTIPLE SELECTS
    // -----------------
    function processMultiple($pdo, $productor_id, $postArray, $tableName, $fkName) {
        $pdo->prepare("DELETE FROM $tableName WHERE productor_id = ?")->execute([$productor_id]);
        if (!empty($postArray) && is_array($postArray)) {
            $stmt = $pdo->prepare("INSERT INTO $tableName (productor_id, $fkName) VALUES (?, ?)");
            foreach ($postArray as $val) {
                if ($val) $stmt->execute([$productor_id, $val]);
            }
        }
    }

    $fin = $_POST['financiamiento'] ?? null;
    processMultiple($pdo, $productor_id, ($fin ? [$fin] : []), 'productor_financiamiento', 'financiamiento_id');

    processMultiple($pdo, $productor_id, $_POST['categorias'] ?? [], 'productor_categoria', 'categoria_id');
    processMultiple($pdo, $productor_id, $_POST['grupos_poblacionales'] ?? [], 'productor_grupo', 'grupo_id');
    processMultiple($pdo, $productor_id, $_POST['certificaciones'] ?? [], 'productor_certificacion', 'certificacion_id');
    processMultiple($pdo, $productor_id, $_POST['canales'] ?? [], 'productor_canal', 'canal_id');
    processMultiple($pdo, $productor_id, $_POST['dificultades'] ?? [], 'productor_dificultad', 'dificultad_id');

    // DYNAMIC ARRAYS
    // -----------------
    // Productos Ofertados
    $pdo->prepare("DELETE FROM productor_productos WHERE productor_id = ?")->execute([$productor_id]);
    if (!empty($_POST['nombre']) && is_array($_POST['nombre'])) {
        $stmt_prod = $pdo->prepare("
            INSERT INTO productor_productos (productor_id, nombre, volumen, unidad_volumen, frecuencia, presentacion, calidad, precio, unidad_precio) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        for ($i = 0; $i < count($_POST['nombre']); $i++) {
            $n = $_POST['nombre'][$i] ?? '';
            if (trim($n) === '') continue; // Skip empty rows
            
            $stmt_prod->execute([
                $productor_id,
                $n,
                (!empty($_POST['volumen'][$i])) ? $_POST['volumen'][$i] : null,
                $_POST['unidad_volumen'][$i] ?? null,
                $_POST['frecuencia'][$i] ?? null,
                $_POST['presentacion'][$i] ?? null,
                $_POST['calidad'][$i] ?? null,
                (!empty($_POST['precio'][$i])) ? $_POST['precio'][$i] : null,
                $_POST['unidad_precio'][$i] ?? null
            ]);
        }
    }

    // Servicios Actividades
    $pdo->prepare("DELETE FROM productor_servicios WHERE productor_id = ?")->execute([$productor_id]);
    if (!empty($_POST['act_nombre']) && is_array($_POST['act_nombre'])) {
        $stmt_srv = $pdo->prepare("
            INSERT INTO productor_servicios (productor_id, nombre_actividad, frecuencia, poblacion_objetivo, tipo_contrato, lugar, recursos) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        for ($i = 0; $i < count($_POST['act_nombre']); $i++) {
            $n = $_POST['act_nombre'][$i] ?? '';
            if (trim($n) === '') continue;

            $stmt_srv->execute([
                $productor_id,
                $n,
                $_POST['act_frecuencia'][$i] ?? null,
                $_POST['act_poblacion_objetivo'][$i] ?? null,
                $_POST['act_tipo_contrato'][$i] ?? null,
                $_POST['act_lugar'][$i] ?? null,
                $_POST['act_recursos'][$i] ?? null
            ]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Ficha técnica actualizada y guardada correctamente.']);

} catch(Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Ficha save error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error al guardar caracterización: ' . $e->getMessage()]);
}
?>
