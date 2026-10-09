<?php
/**
 * API Endpoint: Submit Web Registration for Feria Agroambiental Sumapaz
 * Saves participant into productor_predio_AMBIENTAL (origen_registro='web')
 * and inserts contest selections into inscripcion_concurso_AMBIENTAL.
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // 1. Extract POST parameters
    $nombre = trim($_POST['nombre_productor'] ?? '');
    $apellido = trim($_POST['apellido_productor'] ?? '');
    $tipo_doc = trim($_POST['tipo_documento'] ?? '');
    $cedula = trim($_POST['numero_documento'] ?? '');
    $fecha_nac = trim($_POST['fecha_nacimiento'] ?? '');
    $genero = trim($_POST['genero'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $escolaridad = trim($_POST['nivel_escolaridad'] ?? '');
    $es_lgbtiq = isset($_POST['es_lgbtiq']) && ($_POST['es_lgbtiq'] === '1' || $_POST['es_lgbtiq'] === 'true' || $_POST['es_lgbtiq'] === 'Sí') ? 1 : 0;
    $discapacidad = trim($_POST['condicion_discapacidad'] ?? 'Ninguna');
    if ($discapacidad === 'Otra' && !empty($_POST['custom_discapacidad'])) {
        $discapacidad = trim($_POST['custom_discapacidad']);
    }

    $nombre_predio = trim($_POST['nombre_predio'] ?? '');
    $vereda = strtoupper(trim($_POST['vereda'] ?? ''));
    if ($vereda === 'OTRA VEREDA' && !empty($_POST['custom_vereda'])) {
        $vereda = strtoupper(trim($_POST['custom_vereda']));
    }
    $cuenca = trim($_POST['cuenca'] ?? '');
    $extension_finca = trim($_POST['extension_finca'] ?? '');
    $en_parques = isset($_POST['en_parques_nacionales']) && ($_POST['en_parques_nacionales'] === '1' || $_POST['en_parques_nacionales'] === 'true' || $_POST['en_parques_nacionales'] === 'Sí') ? 1 : 0;

    // Optional GPS Coords
    $latitud = !empty($_POST['latitud']) ? (float)$_POST['latitud'] : null;
    $longitud = !empty($_POST['longitud']) ? (float)$_POST['longitud'] : null;
    $altitud = !empty($_POST['altitud_msnm']) ? (float)$_POST['altitud_msnm'] : null;

    $observaciones = trim($_POST['observaciones'] ?? '');
    $concursos = $_POST['concursos'] ?? [];
    if (is_string($concursos)) {
        $concursos = json_decode($concursos, true) ?: [];
    }

    // 2. Validate mandatory fields
    if (!$nombre || !$apellido || !$tipo_doc || !$cedula || !$telefono || !$genero || !$escolaridad || !$nombre_predio || !$vereda || !$cuenca || !$extension_finca) {
        throw new Exception('Faltan campos obligatorios en los datos personales o del predio.');
    }

    if (empty($concursos) || !is_array($concursos)) {
        throw new Exception('Debe seleccionar al menos un concurso para inscribirse.');
    }

    // Calculate age if fecha_nacimiento is provided
    $edad = null;
    if ($fecha_nac) {
        $dob = new DateTime($fecha_nac);
        $now = new DateTime();
        $edad = $now->diff($dob)->y;
    } elseif (!empty($_POST['edad'])) {
        $edad = (int)$_POST['edad'];
    }

    // 3. Check for existing registration with same document number
    $stmtCheck = $pdo->prepare("SELECT id FROM productor_predio_AMBIENTAL WHERE numero_documento = :cedula");
    $stmtCheck->execute(['cedula' => $cedula]);
    if ($stmtCheck->fetchColumn()) {
        throw new Exception("El número de documento $cedula ya se encuentra inscrito en la Feria Agroambiental.");
    }

    // 4. Handle Optional Photos Upload
    $photoPaths = [];
    if (!empty($_FILES['fotos'])) {
        $uploadDir = __DIR__ . '/../uploads/feria_fotos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $files = $_FILES['fotos'];
        $count = is_array($files['name']) ? count($files['name']) : 0;

        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $tmpName = $files['tmp_name'][$i];
                $name = $files['name'][$i];
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    $newName = 'foto_' . preg_replace('/[^A-Za-z0-9]/', '', $cedula) . '_' . time() . '_' . $i . '.' . $ext;
                    $dest = $uploadDir . $newName;
                    if (move_uploaded_file($tmpName, $dest)) {
                        $photoPaths[] = 'uploads/feria_fotos/' . $newName;
                    }
                }
            }
        }
    }

    // Fetch contest names for notes summary
    $contestNames = [];
    if (!empty($concursos)) {
        $inClause = implode(',', array_map('intval', $concursos));
        $stmtConcursos = $pdo->query("SELECT id, nombre_concurso FROM estructura_concursos_AMBIENTAL WHERE id IN ($inClause)");
        while ($row = $stmtConcursos->fetch(PDO::FETCH_ASSOC)) {
            $contestNames[] = "[" . $row['id'] . "] " . $row['nombre_concurso'];
        }
    }

    $notes = "CONCURSOS SELECCIONADOS:\n" . implode("\n", $contestNames);
    if ($observaciones) {
        $notes .= "\n\nOBSERVACIONES:\n" . $observaciones;
    }

    $fechaInscripcion = date('Y-m-d H:i:s');
    $photoJson = !empty($photoPaths) ? json_encode($photoPaths, JSON_UNESCAPED_UNICODE) : null;

    // 5. Insert Producer Row into DB
    $sqlInsert = "
        INSERT INTO productor_predio_AMBIENTAL (
            local_id, nombre_productor, apellido_productor, tipo_documento, numero_documento,
            nombre_predio, extension_finca, vereda, cuenca, latitud, longitud, altitud_msnm,
            telefono, email, genero, edad, nivel_escolaridad, es_lgbtiq, condicion_discapacidad,
            en_parques_nacionales, notes, nombre_inscriptor, firma_inscriptor, firma_productor,
            photo_paths, fecha_inscripcion, origen_registro
        ) VALUES (
            NULL, :nombre, :apellido, :tipo_doc, :cedula,
            :predio, :extension, :vereda, :cuenca, :latitud, :longitud, :altitud,
            :telefono, :email, :genero, :edad, :escolaridad, :lgbtiq, :discapacidad,
            :en_parques, :notes, 'Auto-inscripción Web', NULL, NULL,
            :photo_paths, :fecha_inscripcion, 'web'
        )
    ";

    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->execute([
        'nombre' => $nombre,
        'apellido' => $apellido,
        'tipo_doc' => $tipo_doc,
        'cedula' => $cedula,
        'predio' => $nombre_predio,
        'extension' => $extension_finca,
        'vereda' => $vereda,
        'cuenca' => $cuenca,
        'latitud' => $latitud,
        'longitud' => $longitud,
        'altitud' => $altitud,
        'telefono' => $telefono,
        'email' => $email,
        'genero' => $genero,
        'edad' => $edad,
        'escolaridad' => $escolaridad,
        'lgbtiq' => $es_lgbtiq,
        'discapacidad' => $discapacidad,
        'en_parques' => $en_parques,
        'notes' => $notes,
        'photo_paths' => $photoJson,
        'fecha_inscripcion' => $fechaInscripcion
    ]);

    $producerId = $pdo->lastInsertId();

    // 6. Insert Contest Registration Rows
    $stmtInscripcion = $pdo->prepare("
        INSERT INTO inscripcion_concurso_AMBIENTAL (id_productor_predio, id_concurso, fecha_inscripcion)
        VALUES (:pid, :cid, :fecha)
    ");

    foreach ($concursos as $cid) {
        $stmtInscripcion->execute([
            'pid' => $producerId,
            'cid' => (int)$cid,
            'fecha' => $fechaInscripcion
        ]);
    }

    $numRegistro = "REG-FA-" . str_pad($producerId, 6, "0", STR_PAD_LEFT);

    echo json_encode([
        'success' => true,
        'id' => (int)$producerId,
        'numero_registro' => $numRegistro,
        'nombre_completo' => $nombre . ' ' . $apellido,
        'documento' => $tipo_doc . ' ' . $cedula,
        'fecha_inscripcion' => date('d/m/Y h:i A', strtotime($fechaInscripcion)),
        'concursos_count' => count($concursos),
        'concursos_nombres' => $contestNames
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
