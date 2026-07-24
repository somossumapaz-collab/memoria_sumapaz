<?php
/**
 * Helper to manage ambiental_persona records
 */

function upsertPersona($pdo, $data) {
    if (empty($data['documento']) || empty($data['nombre'])) {
        return null;
    }

    $stmt = $pdo->prepare("
        INSERT INTO ambiental_persona (documento, nombre, telefono, finca, vereda, corregimiento, cuenca, tipo_persona, tarjeta_profesional)
        VALUES (:documento, :nombre, :telefono, :finca, :vereda, :corregimiento, :cuenca, :tipo_persona, :tarjeta_profesional)
        ON DUPLICATE KEY UPDATE
            nombre = VALUES(nombre),
            telefono = COALESCE(VALUES(telefono), telefono),
            finca = COALESCE(VALUES(finca), finca),
            vereda = COALESCE(VALUES(vereda), vereda),
            corregimiento = COALESCE(VALUES(corregimiento), corregimiento),
            cuenca = COALESCE(VALUES(cuenca), cuenca),
            tipo_persona = VALUES(tipo_persona),
            tarjeta_profesional = COALESCE(VALUES(tarjeta_profesional), tarjeta_profesional)
    ");

    return $stmt->execute([
        ':documento' => $data['documento'],
        ':nombre' => $data['nombre'],
        ':telefono' => $data['telefono'] ?? null,
        ':finca' => $data['finca'] ?? null,
        ':vereda' => $data['vereda'] ?? null,
        ':corregimiento' => $data['corregimiento'] ?? null,
        ':cuenca' => $data['cuenca'] ?? null,
        ':tipo_persona' => $data['tipo_persona'] ?? 'Productor',
        ':tarjeta_profesional' => $data['tarjeta_profesional'] ?? null
    ]);
}
?>
