<?php
/**
 * Setup Schema for Ambientales (Visitas Pecuarias, Agrícolas y Personas)
 */

require_once __DIR__ . '/db_config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // 1. Tabla ambiental_persona
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ambiental_persona (
            id INT AUTO_INCREMENT PRIMARY KEY,
            documento VARCHAR(50) UNIQUE NOT NULL,
            nombre VARCHAR(255) NOT NULL,
            telefono VARCHAR(50) DEFAULT NULL,
            finca VARCHAR(255) DEFAULT NULL,
            vereda VARCHAR(255) DEFAULT NULL,
            corregimiento VARCHAR(255) DEFAULT NULL,
            cuenca VARCHAR(255) DEFAULT NULL,
            tipo_persona VARCHAR(50) DEFAULT 'Productor',
            tarjeta_profesional VARCHAR(100) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 2. Tabla ambiental_visitas_pecuarias
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ambiental_visitas_pecuarias (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fecha VARCHAR(50) NOT NULL,
            corregimiento VARCHAR(255),
            vereda VARCHAR(255),
            finca VARCHAR(255),
            cuenca VARCHAR(255),
            hora_inicio VARCHAR(50),
            hora_fin VARCHAR(50),
            latitud DOUBLE DEFAULT NULL,
            longitud DOUBLE DEFAULT NULL,
            usuario VARCHAR(255),
            primera_vez TINYINT(1) DEFAULT 1,
            seguimiento TINYINT(1) DEFAULT 0,
            fecha_visita_anterior VARCHAR(50) DEFAULT NULL,
            diagnostico TEXT,
            procedimiento TEXT,
            recomendaciones TEXT,
            acepta_corresponsabilidad TINYINT(1) DEFAULT 1,
            proxima_visita VARCHAR(50) DEFAULT NULL,
            profesional VARCHAR(255),
            tarjeta_profesional VARCHAR(255),
            cedula_operario VARCHAR(255),
            cedula_usuario VARCHAR(255),
            firma_profesional LONGTEXT DEFAULT NULL,
            firma_operario LONGTEXT DEFAULT NULL,
            firma_usuario LONGTEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 3. Tabla ambiental_visita_pecuaria_especies
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ambiental_visita_pecuaria_especies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            visita_id INT NOT NULL,
            especie VARCHAR(255) NOT NULL,
            FOREIGN KEY (visita_id) REFERENCES ambiental_visitas_pecuarias(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 4. Tabla ambiental_visitas_agricolas
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ambiental_visitas_agricolas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fecha VARCHAR(50) NOT NULL,
            nombre VARCHAR(255) NOT NULL,
            finca VARCHAR(255),
            vereda VARCHAR(255),
            corregimiento VARCHAR(255),
            cuenca VARCHAR(255),
            telefono VARCHAR(255),
            hora_inicio VARCHAR(50),
            hora_fin VARCHAR(50),
            numero_registro VARCHAR(255),
            objetivo_visita TEXT,
            recomendaciones TEXT,
            muestra_suelo TINYINT(1) DEFAULT 0,
            numero_muestra VARCHAR(255) DEFAULT NULL,
            latitud DOUBLE DEFAULT NULL,
            longitud DOUBLE DEFAULT NULL,
            altitud DOUBLE DEFAULT NULL,
            observaciones_geo TEXT DEFAULT NULL,
            area_intervenir DOUBLE DEFAULT NULL,
            acepta_corresponsabilidad TINYINT(1) DEFAULT 1,
            proxima_visita VARCHAR(50) DEFAULT NULL,
            profesional VARCHAR(255),
            tarjeta_profesional VARCHAR(255),
            cedula_operario VARCHAR(255),
            cedula_usuario VARCHAR(255),
            firma_profesional LONGTEXT DEFAULT NULL,
            firma_operario LONGTEXT DEFAULT NULL,
            firma_usuario LONGTEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 5. Tabla ambiental_motivos_visita_agricola
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ambiental_motivos_visita_agricola (
            id INT AUTO_INCREMENT PRIMARY KEY,
            visita_id INT NOT NULL,
            motivo VARCHAR(255) NOT NULL,
            FOREIGN KEY (visita_id) REFERENCES ambiental_visitas_agricolas(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 6. Tabla ambiental_tipo_huerta
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ambiental_tipo_huerta (
            id INT AUTO_INCREMENT PRIMARY KEY,
            visita_id INT NOT NULL,
            tipo_huerta VARCHAR(255) NOT NULL,
            FOREIGN KEY (visita_id) REFERENCES ambiental_visitas_agricolas(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 7. Tabla ambiental_cultivos_visita
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ambiental_cultivos_visita (
            id INT AUTO_INCREMENT PRIMARY KEY,
            visita_id INT NOT NULL,
            categoria VARCHAR(255),
            tipo VARCHAR(255),
            especie VARCHAR(255),
            area_m2 DOUBLE,
            produccion_kg DOUBLE,
            observaciones TEXT,
            FOREIGN KEY (visita_id) REFERENCES ambiental_visitas_agricolas(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 8. Tabla ambiental_materiales_entregados
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ambiental_materiales_entregados (
            id INT AUTO_INCREMENT PRIMARY KEY,
            visita_id INT NOT NULL,
            material VARCHAR(255),
            cantidad DOUBLE,
            unidad VARCHAR(255),
            FOREIGN KEY (visita_id) REFERENCES ambiental_visitas_agricolas(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    echo json_encode([
        'status' => 'success',
        'message' => 'Tablas ambiental_* creadas correctamente en la base de datos.'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al crear las tablas: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>
