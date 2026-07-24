<?php
/**
 * API Endpoint to Submit Visita Agrícola
 */

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/persona_helper.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Registrar / actualizar persona (Productor)
    if (!empty($data['cedula_usuario']) && !empty($data['nombre'])) {
        upsertPersona($pdo, [
            'documento' => $data['cedula_usuario'],
            'nombre' => $data['nombre'],
            'telefono' => $data['telefono'] ?? null,
            'finca' => $data['finca'] ?? null,
            'vereda' => $data['vereda'] ?? null,
            'corregimiento' => $data['corregimiento'] ?? null,
            'cuenca' => $data['cuenca'] ?? null,
            'tipo_persona' => 'Productor'
        ]);
    }

    // Registrar profesional si aplica
    if (!empty($data['profesional'])) {
        upsertPersona($pdo, [
            'documento' => $data['tarjeta_profesional'] ?? $data['profesional'],
            'nombre' => $data['profesional'],
            'tipo_persona' => 'Profesional',
            'tarjeta_profesional' => $data['tarjeta_profesional'] ?? null
        ]);
    }

    // 2. Insertar visita agrícola
    $stmt = $pdo->prepare("
        INSERT INTO ambiental_visitas_agricolas (
            fecha, nombre, finca, vereda, corregimiento, cuenca, telefono, hora_inicio, hora_fin,
            numero_registro, objetivo_visita, recomendaciones, muestra_suelo, numero_muestra,
            latitud, longitud, altitud, observaciones_geo, area_intervenir, acepta_corresponsabilidad,
            proxima_visita, profesional, tarjeta_profesional, cedula_operario, cedula_usuario,
            firma_profesional, firma_operario, firma_usuario
        ) VALUES (
            :fecha, :nombre, :finca, :vereda, :corregimiento, :cuenca, :telefono, :hora_inicio, :hora_fin,
            :numero_registro, :objetivo_visita, :recomendaciones, :muestra_suelo, :numero_muestra,
            :latitud, :longitud, :altitud, :observaciones_geo, :area_intervenir, :acepta_corresponsabilidad,
            :proxima_visita, :profesional, :tarjeta_profesional, :cedula_operario, :cedula_usuario,
            :firma_profesional, :firma_operario, :firma_usuario
        )
    ");

    $stmt->execute([
        ':fecha' => $data['fecha'] ?? date('Y-m-d'),
        ':nombre' => $data['nombre'] ?? '',
        ':finca' => $data['finca'] ?? '',
        ':vereda' => $data['vereda'] ?? '',
        ':corregimiento' => $data['corregimiento'] ?? '',
        ':cuenca' => $data['cuenca'] ?? '',
        ':telefono' => $data['telefono'] ?? '',
        ':hora_inicio' => $data['hora_inicio'] ?? '',
        ':hora_fin' => $data['hora_fin'] ?? '',
        ':numero_registro' => $data['numero_registro'] ?? '',
        ':objetivo_visita' => $data['objetivo_visita'] ?? '',
        ':recomendaciones' => $data['recomendaciones'] ?? '',
        ':muestra_suelo' => !empty($data['muestra_suelo']) ? 1 : 0,
        ':numero_muestra' => $data['numero_muestra'] ?? null,
        ':latitud' => isset($data['latitud']) ? (float)$data['latitud'] : null,
        ':longitud' => isset($data['longitud']) ? (float)$data['longitud'] : null,
        ':altitud' => isset($data['altitud']) ? (float)$data['altitud'] : null,
        ':observaciones_geo' => $data['observaciones_geo'] ?? null,
        ':area_intervenir' => isset($data['area_intervenir']) ? (float)$data['area_intervenir'] : null,
        ':acepta_corresponsabilidad' => !empty($data['acepta_corresponsabilidad']) ? 1 : 0,
        ':proxima_visita' => $data['proxima_visita'] ?? null,
        ':profesional' => $data['profesional'] ?? '',
        ':tarjeta_profesional' => $data['tarjeta_profesional'] ?? '',
        ':cedula_operario' => $data['cedula_operario'] ?? '',
        ':cedula_usuario' => $data['cedula_usuario'] ?? '',
        ':firma_profesional' => $data['firma_profesional'] ?? null,
        ':firma_operario' => $data['firma_operario'] ?? null,
        ':firma_usuario' => $data['firma_usuario'] ?? null
    ]);

    $visitaId = $pdo->lastInsertId();

    // 3. Insertar motivos
    if (!empty($data['motivos']) && is_array($data['motivos'])) {
        $motStmt = $pdo->prepare("INSERT INTO ambiental_motivos_visita_agricola (visita_id, motivo) VALUES (:visita_id, :motivo)");
        foreach ($data['motivos'] as $motivo) {
            $motStmt->execute([':visita_id' => $visitaId, ':motivo' => $motivo]);
        }
    }

    // 4. Insertar tipos de huerta (soporta tiposHuerta o tipos_huerta)
    $huertas = $data['tiposHuerta'] ?? $data['tipos_huerta'] ?? [];
    if (!empty($huertas) && is_array($huertas)) {
        $hStmt = $pdo->prepare("INSERT INTO ambiental_tipo_huerta (visita_id, tipo_huerta) VALUES (:visita_id, :tipo_huerta)");
        foreach ($huertas as $huerta) {
            $hStmt->execute([':visita_id' => $visitaId, ':tipo_huerta' => $huerta]);
        }
    }

    // 5. Insertar cultivos
    if (!empty($data['cultivos']) && is_array($data['cultivos'])) {
        $cStmt = $pdo->prepare("
            INSERT INTO ambiental_cultivos_visita (visita_id, categoria, tipo, especie, area_m2, produccion_kg, observaciones)
            VALUES (:visita_id, :categoria, :tipo, :especie, :area_m2, :produccion_kg, :observaciones)
        ");
        foreach ($data['cultivos'] as $cultivo) {
            $cStmt->execute([
                ':visita_id' => $visitaId,
                ':categoria' => $cultivo['categoria'] ?? '',
                ':tipo' => $cultivo['tipo'] ?? '',
                ':especie' => $cultivo['especie'] ?? '',
                ':area_m2' => isset($cultivo['areaM2']) ? (float)$cultivo['areaM2'] : (isset($cultivo['area_m2']) ? (float)$cultivo['area_m2'] : 0),
                ':produccion_kg' => isset($cultivo['produccionKg']) ? (float)$cultivo['produccionKg'] : (isset($cultivo['produccion_kg']) ? (float)$cultivo['produccion_kg'] : 0),
                ':observaciones' => $cultivo['observaciones'] ?? ''
            ]);
        }
    }

    // 6. Insertar materiales entregados
    if (!empty($data['materiales']) && is_array($data['materiales'])) {
        $mStmt = $pdo->prepare("
            INSERT INTO ambiental_materiales_entregados (visita_id, material, cantidad, unidad)
            VALUES (:visita_id, :material, :cantidad, :unidad)
        ");
        foreach ($data['materiales'] as $mat) {
            $mStmt->execute([
                ':visita_id' => $visitaId,
                ':material' => $mat['material'] ?? '',
                ':cantidad' => isset($mat['cantidad']) ? (float)$mat['cantidad'] : 0,
                ':unidad' => $mat['unidad'] ?? ''
            ]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'id' => (int)$visitaId,
        'message' => 'Visita agrícola guardada exitosamente'
    ], JSON_UNESCAPED_UNICODE);

} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al guardar visita agrícola: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
