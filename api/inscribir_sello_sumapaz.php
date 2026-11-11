<?php
/**
 * API Endpoint: Registro a la Mesa de Cocreacion del Sello Somos Sumapaz
 * Alcaldia Local de Sumapaz - 18 de noviembre de 2026
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_config.php';

$cuencasVeredasRef = [
    'SAN JOSE' => 'Rio Sumapaz', 'NUEVA GRANADA' => 'Rio Sumapaz', 'CONCEPCION' => 'Rio Sumapaz',
    'TUNAL ALTO' => 'Rio Sumapaz', 'LAGUNITAS' => 'Rio Sumapaz', 'TUNAL BAJO' => 'Rio Sumapaz',
    'LA UNIÓN' => 'Rio Sumapaz', 'LA UNION' => 'Rio Sumapaz', 'CHORRERAS' => 'Rio Sumapaz',
    'SANTO DOMINGO' => 'Rio Sumapaz', 'LAS VEGAS' => 'Rio Sumapaz', 'SAN JUAN' => 'Rio Sumapaz',
    'CAPITOLIO' => 'Rio Sumapaz', 'EL TOLDO' => 'Rio Sumapaz', 'TOLDO' => 'Rio Sumapaz',
    'SAN ANTONIO' => 'Rio Sumapaz',

    'LAS SOPAS' => 'Rio Blanco', 'LOS RIOS' => 'Rio Blanco', 'LAS PALMAS' => 'Rio Blanco',
    'LAS ANIMAS' => 'Rio Blanco', 'LASANIMAS' => 'Rio Blanco', 'LAS AURAS' => 'Rio Blanco',
    'LASURAS' => 'Rio Blanco', 'NAZARETH' => 'Rio Blanco',
    'NAZARET' => 'Rio Blanco', 'TAQUECITOS' => 'Rio Blanco', 'PENALISA' => 'Rio Blanco',
    'PEÑALISA' => 'Rio Blanco', 'LAGUNA VERDE' => 'Rio Blanco', 'RAIZAL' => 'Rio Blanco',
    'EL RAIZAL' => 'Rio Blanco', 'SANTA ROSA' => 'Rio Blanco', 'SANTA ROSA BAJA' => 'Rio Blanco',
    'SANTA ROSA ALTA' => 'Rio Blanco', 'BETANIA' => 'Rio Blanco', 'EL ITSMO' => 'Rio Blanco',
    'ITSMO' => 'Rio Blanco', 'ISTMO' => 'Rio Blanco', 'EL TABACO' => 'Rio Blanco',
    'TABACO' => 'Rio Blanco'
];

function resolverCuenca(string $vereda, array $map): string {
    $vUpper = strtoupper(trim($vereda));
    $vUpper = trim(preg_replace('/\bURBANO\b/i', '', $vUpper));
    if ($vUpper === 'LA UNION') $vUpper = 'LA UNIÓN';
    if ($vUpper === 'CEDRAL') $vUpper = 'NAZARETH';
    $vClean = preg_replace('~[^\pL\pN\s]+~u', '', $vUpper);
    $vClean = strtoupper(trim(preg_replace('/\s+/', ' ', $vClean)));
    return $map[$vClean] ?? 'Rio Sumapaz';
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'catalogos';

    if ($action === 'catalogos') {
        try {
            $categorias = $pdo->query("SELECT id, tipo, nombre FROM categorias_productivas ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
            $grouped = [];
            foreach ($categorias as $cat) {
                $grouped[$cat['tipo']][] = [
                    'id' => (int)$cat['id'],
                    'nombre' => $cat['nombre']
                ];
            }

            echo json_encode([
                'success' => true,
                'categorias_agrupadas' => $grouped,
                'total_categorias' => count($categorias)
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error al cargar catalogo: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    if ($action === 'listado') {
        try {
            $stmt = $pdo->query("SELECT id, nombre_completo, tipo_documento, numero_documento, telefono, vereda, cuenca, nombre_predio, nicho_productivo, requiere_transporte, observaciones, fecha_registro FROM inscripciones_sello_sumapaz ORDER BY id DESC");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode([
                'success' => true,
                'total' => count($rows),
                'inscritos' => $rows
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error al consultar inscritos: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}

if ($method === 'POST') {
    try {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $data = $_POST;
        }

        $nombreCompleto = trim((string)($data['nombre_completo'] ?? ''));
        $tipoDoc = trim((string)($data['tipo_documento'] ?? 'CC'));
        $numeroDoc = trim((string)($data['numero_documento'] ?? ($data['cedula'] ?? '')));
        $telefono = trim((string)($data['telefono'] ?? ''));
        $vereda = trim((string)($data['vereda'] ?? ''));
        $nombrePredio = trim((string)($data['nombre_predio'] ?? ''));
        $nichoProductivo = trim((string)($data['nicho_productivo'] ?? ''));
        $catId = isset($data['categoria_productiva_id']) && is_numeric($data['categoria_productiva_id']) ? (int)$data['categoria_productiva_id'] : null;
        $reqTransporte = strtoupper(trim((string)($data['requiere_transporte'] ?? 'NO'))) === 'SI' ? 'Si' : 'No';
        $observaciones = trim((string)($data['observaciones'] ?? ''));
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        if ($nombreCompleto === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'El nombre completo es obligatorio.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($numeroDoc === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'El numero de documento (cedula) es obligatorio.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($telefono === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'El telefono de contacto es obligatorio.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($vereda === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Debe seleccionar la vereda.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($nombrePredio === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'El nombre del predio o finca es obligatorio.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($nichoProductivo === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Debe indicar el nicho productivo o linea de produccion.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $cuenca = resolverCuenca($vereda, $cuencasVeredasRef);

        // Verificar si ya existe inscripcion con este documento
        $stmtCheck = $pdo->prepare("SELECT id FROM inscripciones_sello_sumapaz WHERE numero_documento = ? LIMIT 1");
        $stmtCheck->execute([$numeroDoc]);
        $existingId = $stmtCheck->fetchColumn();

        if ($existingId) {
            // Actualizar registro existente
            $stmtUpd = $pdo->prepare("
                UPDATE inscripciones_sello_sumapaz
                SET nombre_completo = ?, tipo_documento = ?, telefono = ?, vereda = ?, cuenca = ?,
                    nombre_predio = ?, nicho_productivo = ?, categoria_productiva_id = ?,
                    requiere_transporte = ?, observaciones = ?, ip_registro = ?
                WHERE id = ?
            ");
            $stmtUpd->execute([
                $nombreCompleto, $tipoDoc, $telefono, $vereda, $cuenca,
                $nombrePredio, $nichoProductivo, $catId,
                $reqTransporte, $observaciones, $ip, (int)$existingId
            ]);

            echo json_encode([
                'success' => true,
                'action' => 'updated',
                'id' => (int)$existingId,
                'message' => 'Su inscripcion fue actualizada exitosamente para la Mesa de Cocreacion del Sello Somos Sumapaz.',
                'nombre' => $nombreCompleto,
                'vereda' => $vereda,
                'cuenca' => $cuenca,
                'requiere_transporte' => $reqTransporte
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Insertar nuevo registro
        $stmtIns = $pdo->prepare("
            INSERT INTO inscripciones_sello_sumapaz
            (nombre_completo, tipo_documento, numero_documento, telefono, vereda, cuenca, nombre_predio, nicho_productivo, categoria_productiva_id, requiere_transporte, observaciones, ip_registro)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtIns->execute([
            $nombreCompleto, $tipoDoc, $numeroDoc, $telefono, $vereda, $cuenca,
            $nombrePredio, $nichoProductivo, $catId,
            $reqTransporte, $observaciones, $ip
        ]);

        $newId = (int)$pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'action' => 'created',
            'id' => $newId,
            'message' => 'Inscripcion registrada exitosamente para la Mesa de Cocreacion del Sello Somos Sumapaz.',
            'nombre' => $nombreCompleto,
            'vereda' => $vereda,
            'cuenca' => $cuenca,
            'requiere_transporte' => $reqTransporte
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error al procesar la inscripcion: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Metodo no permitido'], JSON_UNESCAPED_UNICODE);
