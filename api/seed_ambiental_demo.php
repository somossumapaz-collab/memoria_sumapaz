<?php
/**
 * Seed script for initial Ambientales demo data (Agrícola and Pecuaria)
 */

require_once __DIR__ . '/db_config.php';

header('Content-Type: application/json; charset=utf-8');

// SVG Signatures formatted directly as SVG strings & data URLs
$firma_prof = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 100" width="280" height="85"><path d="M 20 60 Q 60 10 100 60 T 150 30 T 200 75 T 260 25" stroke="#1A365D" stroke-width="3" fill="none" stroke-linecap="round"/><path d="M 40 80 Q 130 95 250 70" stroke="#1A365D" stroke-width="2" fill="none"/><text x="160" y="92" font-family="Arial, sans-serif" font-size="10" font-weight="bold" fill="#2B6CB0">Ing. D. Rojas - TP 98765</text></svg>';

$firma_oper = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 100" width="280" height="85"><path d="M 15 50 Q 55 90 95 25 T 165 70 T 225 35 T 275 80" stroke="#2B6CB0" stroke-width="3" fill="none" stroke-linecap="round"/><path d="M 35 35 L 255 85" stroke="#2B6CB0" stroke-width="2" stroke-dasharray="4,4" fill="none"/><text x="150" y="92" font-family="Arial, sans-serif" font-size="10" font-weight="bold" fill="#4A5568">Téc. J. Martínez - C.C. 1023456</text></svg>';

$firma_user = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 100" width="280" height="85"><path d="M 30 70 Q 80 15 120 70 T 180 30 T 240 75" stroke="#2D3748" stroke-width="3" fill="none" stroke-linecap="round"/><circle cx="120" cy="35" r="4" fill="#2D3748"/><path d="M 50 85 L 230 85" stroke="#2D3748" stroke-width="1.5" fill="none"/><text x="140" y="94" font-family="Arial, sans-serif" font-size="10" font-weight="bold" fill="#2D3748">Pedro A. Gómez - C.C. 79123456</text></svg>';

try {
    // Clear old demo data if needed to refresh signatures
    $pdo->exec("DELETE FROM ambiental_visitas_agricolas");
    $pdo->exec("DELETE FROM ambiental_visitas_pecuarias");

    // 1. Insert Personas
    $stmtP = $pdo->prepare("INSERT INTO ambiental_persona (documento, nombre, telefono, finca, vereda, corregimiento, cuenca, tipo_persona) 
        VALUES (:doc, :nom, :tel, :finca, :vereda, :correg, :cuenca, 'Productor') 
        ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), telefono=VALUES(telefono), finca=VALUES(finca)");
    
    $stmtP->execute([
        ':doc' => '79123456',
        ':nom' => 'Pedro Alcántara Gómez',
        ':tel' => '3109876543',
        ':finca' => 'La Esperanza',
        ':vereda' => 'San Juan',
        ':correg' => 'San Juan de Sumapaz',
        ':cuenca' => 'Cuenca Río Sumapaz'
    ]);

    $stmtP->execute([
        ':doc' => '19876543',
        ':nom' => 'Jaime Alberto Romero',
        ':tel' => '3124567890',
        ':finca' => 'El Paraíso',
        ':vereda' => 'Las Sopas',
        ':correg' => 'Nazareth',
        ':cuenca' => 'Cuenca Río Blanco'
    ]);

    // 2. Insert Agrícola
    $stmtA = $pdo->prepare("INSERT INTO ambiental_visitas_agricolas (
        fecha, nombre, finca, vereda, corregimiento, cuenca, telefono, hora_inicio, hora_fin, numero_registro,
        objetivo_visita, recomendaciones, muestra_suelo, numero_muestra, latitud, longitud, altitud,
        observaciones_geo, area_intervenir, acepta_corresponsabilidad, proxima_visita, profesional,
        tarjeta_profesional, cedula_operario, cedula_usuario, firma_profesional, firma_operario, firma_usuario
    ) VALUES (
        :fecha, :nombre, :finca, :vereda, :corregimiento, :cuenca, :telefono, :hora_inicio, :hora_fin, :numero_registro,
        :objetivo_visita, :recomendaciones, :muestra_suelo, :numero_muestra, :latitud, :longitud, :altitud,
        :observaciones_geo, :area_intervenir, :acepta_corresponsabilidad, :proxima_visita, :profesional,
        :tarjeta_profesional, :cedula_operario, :cedula_usuario, :firma_profesional, :firma_operario, :firma_usuario
    )");

    $stmtA->execute([
        ':fecha' => '2026-07-24',
        ':nombre' => 'Pedro Alcántara Gómez',
        ':finca' => 'La Esperanza',
        ':vereda' => 'San Juan',
        ':corregimiento' => 'San Juan de Sumapaz',
        ':cuenca' => 'Cuenca Río Sumapaz',
        ':telefono' => '3109876543',
        ':hora_inicio' => '08:30',
        ':hora_fin' => '11:30',
        ':numero_registro' => 'VAGR-2026-001',
        ':objetivo_visita' => 'Monitoreo y asistencia técnica en nutrición de cultivos de papa nativa y manejo agroecológico de plagas en alta montaña.',
        ':recomendaciones' => 'Aplicación de biofertilizantes a base de compostaje local. Mantenimiento de curvas de nivel y rotación de suelo con leguminosas.',
        ':muestra_suelo' => 1,
        ':numero_muestra' => 'MS-2026-042',
        ':latitud' => 3.9621,
        ':longitud' => -74.3182,
        ':altitud' => 3520,
        ':observaciones_geo' => 'Predio ubicado sobre cota 3500 m.s.n.m., sector norte del páramo.',
        ':area_intervenir' => 1.5,
        ':acepta_corresponsabilidad' => 1,
        ':proxima_visita' => '2026-08-20',
        ':profesional' => 'Ing. Diana Marcela Rojas',
        ':tarjeta_profesional' => 'TP-98765-AGR',
        ':cedula_operario' => '1023456789',
        ':cedula_usuario' => '79123456',
        ':firma_profesional' => $firma_prof,
        ':firma_operario' => $firma_oper,
        ':firma_usuario' => $firma_user
    ]);
    $visitaAgrId = $pdo->lastInsertId();

    $pdo->exec("INSERT INTO ambiental_motivos_visita_agricola (visita_id, motivo) VALUES ($visitaAgrId, 'Asistencia Técnica Agroecológica'), ($visitaAgrId, 'Muestreo de Suelos')");
    $pdo->exec("INSERT INTO ambiental_tipo_huerta (visita_id, tipo_huerta) VALUES ($visitaAgrId, 'Huerta Familiar Diversificada')");
    $pdo->exec("INSERT INTO ambiental_cultivos_visita (visita_id, categoria, tipo, especie, area_m2, produccion_kg, observaciones) 
        VALUES ($visitaAgrId, 'Tubérculos', 'Papa Nativa', 'Solanum tuberosum (Papa Cacho)', 2500, 1800, 'Excelente vigor foliar')");
    $pdo->exec("INSERT INTO ambiental_materiales_entregados (visita_id, material, cantidad, unidad) 
        VALUES ($visitaAgrId, 'Biofertilizante Folie de Montaña', 20, 'Litros'), ($visitaAgrId, 'Semilla Certificada Papa Cacho', 50, 'Kg')");

    // 3. Insert Pecuaria
    $stmtPec = $pdo->prepare("INSERT INTO ambiental_visitas_pecuarias (
        fecha, corregimiento, vereda, finca, cuenca, hora_inicio, hora_fin, latitud, longitud, usuario,
        primera_vez, seguimiento, fecha_visita_anterior, diagnostico, procedimiento, recomendaciones,
        acepta_corresponsabilidad, proxima_visita, profesional, tarjeta_profesional, cedula_operario,
        cedula_usuario, firma_profesional, firma_operario, firma_usuario
    ) VALUES (
        :fecha, :corregimiento, :vereda, :finca, :cuenca, :hora_inicio, :hora_fin, :latitud, :longitud, :usuario,
        :primera_vez, :seguimiento, :fecha_visita_anterior, :diagnostico, :procedimiento, :recomendaciones,
        :acepta_corresponsabilidad, :proxima_visita, :profesional, :tarjeta_profesional, :cedula_operario,
        :cedula_usuario, :firma_profesional, :firma_operario, :firma_usuario
    )");

    $stmtPec->execute([
        ':fecha' => '2026-07-24',
        ':corregimiento' => 'Nazareth',
        ':vereda' => 'Las Sopas',
        ':finca' => 'El Paraíso',
        ':cuenca' => 'Cuenca Río Blanco',
        ':hora_inicio' => '09:00',
        ':hora_fin' => '12:00',
        ':latitud' => 3.8923,
        ':longitud' => -74.2815,
        ':usuario' => 'Jaime Alberto Romero',
        ':primera_vez' => 1,
        ':seguimiento' => 0,
        ':fecha_visita_anterior' => null,
        ':diagnostico' => 'Evaluación integral del estado sanitario del hato bovino normando. Buena condición corporal general.',
        ':procedimiento' => 'Examen clínico general a 14 bovinos. Desparasitación interna/externa y aplicación de complejo vitamínico ADE.',
        ':recomendaciones' => 'Aislamiento temporal de nacimientos de agua mediante cerca viva para evitar pisoteo. Control periódico de ectoparásitos.',
        ':acepta_corresponsabilidad' => 1,
        ':proxima_visita' => '2026-09-15',
        ':profesional' => 'Dr. Carlos Eduardo Páez',
        ':tarjeta_profesional' => 'TP-54321-MVZ',
        ':cedula_operario' => '1019876543',
        ':cedula_usuario' => '19876543',
        ':firma_profesional' => $firma_prof,
        ':firma_operario' => $firma_oper,
        ':firma_usuario' => $firma_user
    ]);
    $visitaPecId = $pdo->lastInsertId();

    $pdo->exec("INSERT INTO ambiental_visita_pecuaria_especies (visita_id, especie) VALUES ($visitaPecId, 'Bovinos Normando (14 cabezas)'), ($visitaPecId, 'Ovinos Criollos (6 cabezas)')");

    echo json_encode([
        'status' => 'success',
        'message' => 'Datos demo iniciales actualizados correctamente con firmas SVG directas.'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al actualizar datos demo: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
