<?php
/**
 * Setup PMAPC Relational MySQL Database Schema
 */

require_once __DIR__ . '/db_config.php';

try {
    // 1. Master Header Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pmapc_registros (
            id INT AUTO_INCREMENT PRIMARY KEY,
            productor_id BIGINT UNSIGNED NOT NULL,
            data LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_productor (productor_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    try { $pdo->exec("ALTER TABLE pmapc_registros ADD COLUMN nombre_organizacion VARCHAR(255) DEFAULT 'NaN'"); } catch (\PDOException $e) {}
    try { $pdo->exec("ALTER TABLE pmapc_registros ADD COLUMN estado_actual VARCHAR(100) DEFAULT 'NaN'"); } catch (\PDOException $e) {}

    // 2. Estratégico Table (F01, F02, F03, F04)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pmapc_estrategico (
            id INT AUTO_INCREMENT PRIMARY KEY,
            registro_id INT NOT NULL,
            productor_id BIGINT UNSIGNED NOT NULL,
            f01_nombre_organizacion VARCHAR(255) DEFAULT 'NaN',
            f01_tipo_actividad VARCHAR(100) DEFAULT 'NaN',
            f01_ubicacion TEXT,
            f01_coordenadas VARCHAR(255) DEFAULT 'NaN',
            f01_producto_principal TEXT,
            f01_estado_actual VARCHAR(100) DEFAULT 'NaN',
            f01_descripcion_general TEXT,
            f02_mision TEXT,
            f02_vision TEXT,
            f02_valores TEXT,
            f03_problema TEXT,
            f03_solucion TEXT,
            f03_diferencial TEXT,
            f03_valor_ambiental TEXT,
            f03_valor_social TEXT,
            f03_demostracion TEXT,
            f04_fortalezas TEXT,
            f04_oportunidades TEXT,
            f04_debilidades TEXT,
            f04_amenazas TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (registro_id) REFERENCES pmapc_registros(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 3. Clientes Table (F05)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pmapc_clientes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            registro_id INT NOT NULL,
            productor_id BIGINT UNSIGNED NOT NULL,
            actor VARCHAR(255) DEFAULT 'NaN',
            perfil TEXT,
            ubicacion TEXT,
            necesidad TEXT,
            frecuencia VARCHAR(100) DEFAULT 'NaN',
            criterio TEXT,
            canal VARCHAR(255) DEFAULT 'NaN',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (registro_id) REFERENCES pmapc_registros(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 4. Aliados y Cooperación (F07)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pmapc_aliados (
            id INT AUTO_INCREMENT PRIMARY KEY,
            registro_id INT NOT NULL,
            productor_id BIGINT UNSIGNED NOT NULL,
            actor VARCHAR(255) DEFAULT 'NaN',
            aporta TEXT,
            recibe TEXT,
            trabajo TEXT,
            ambiental TEXT,
            accion TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (registro_id) REFERENCES pmapc_registros(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 5. Productos (F09)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pmapc_productos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            registro_id INT NOT NULL,
            productor_id BIGINT UNSIGNED NOT NULL,
            producto VARCHAR(255) DEFAULT 'NaN',
            descripcion TEXT,
            unidad VARCHAR(100) DEFAULT 'NaN',
            insumos TEXT,
            almacenamiento TEXT,
            presentacion TEXT,
            diferencial TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (registro_id) REFERENCES pmapc_registros(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 6. Equipos y Bienes (F10)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pmapc_equipos_bienes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            registro_id INT NOT NULL,
            productor_id BIGINT UNSIGNED NOT NULL,
            bien VARCHAR(255) DEFAULT 'NaN',
            unidades VARCHAR(100) DEFAULT 'NaN',
            actividad TEXT,
            tiempo VARCHAR(100) DEFAULT 'NaN',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (registro_id) REFERENCES pmapc_registros(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 7. Insumos (F11)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pmapc_insumos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            registro_id INT NOT NULL,
            productor_id BIGINT UNSIGNED NOT NULL,
            insumo VARCHAR(255) DEFAULT 'NaN',
            cantidad VARCHAR(100) DEFAULT 'NaN',
            frecuencia VARCHAR(100) DEFAULT 'NaN',
            proveedor VARCHAR(255) DEFAULT 'NaN',
            toxicidad VARCHAR(100) DEFAULT 'NaN',
            impacto TEXT,
            manejo TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (registro_id) REFERENCES pmapc_registros(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 8. Costos y Precios (F14)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pmapc_costos_precios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            registro_id INT NOT NULL,
            productor_id BIGINT UNSIGNED NOT NULL,
            producto VARCHAR(255) DEFAULT 'NaN',
            costo VARCHAR(100) DEFAULT 'NaN',
            margen VARCHAR(100) DEFAULT 'NaN',
            pmin VARCHAR(100) DEFAULT 'NaN',
            pmercado VARCHAR(100) DEFAULT 'NaN',
            logistica VARCHAR(100) DEFAULT 'NaN',
            precio VARCHAR(100) DEFAULT 'NaN',
            justificacion TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (registro_id) REFERENCES pmapc_registros(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 9. Ventas (F15)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pmapc_ventas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            registro_id INT NOT NULL,
            productor_id BIGINT UNSIGNED NOT NULL,
            producto VARCHAR(255) DEFAULT 'NaN',
            cantidad VARCHAR(100) DEFAULT 'NaN',
            precio VARCHAR(100) DEFAULT 'NaN',
            ingresos VARCHAR(100) DEFAULT 'NaN',
            pago VARCHAR(100) DEFAULT 'NaN',
            cliente VARCHAR(255) DEFAULT 'NaN',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (registro_id) REFERENCES pmapc_registros(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 10. Inversiones Iniciales (F16)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pmapc_inversiones (
            id INT AUTO_INCREMENT PRIMARY KEY,
            registro_id INT NOT NULL,
            productor_id BIGINT UNSIGNED NOT NULL,
            descripcion VARCHAR(255) DEFAULT 'NaN',
            valunit VARCHAR(100) DEFAULT 'NaN',
            cant VARCHAR(100) DEFAULT 'NaN',
            total VARCHAR(100) DEFAULT 'NaN',
            req VARCHAR(100) DEFAULT 'NaN',
            fuente VARCHAR(255) DEFAULT 'NaN',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (registro_id) REFERENCES pmapc_registros(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 11. Costos Fijos y Variables (F17)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pmapc_costos_fijos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            registro_id INT NOT NULL,
            productor_id BIGINT UNSIGNED NOT NULL,
            descripcion VARCHAR(255) DEFAULT 'NaN',
            val VARCHAR(100) DEFAULT 'NaN',
            obs TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (registro_id) REFERENCES pmapc_registros(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 12. Economía Circular y Residuos (F20)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pmapc_economia_circular (
            id INT AUTO_INCREMENT PRIMARY KEY,
            registro_id INT NOT NULL,
            productor_id BIGINT UNSIGNED NOT NULL,
            cant VARCHAR(100) DEFAULT 'NaN',
            manejo TEXT,
            destino TEXT,
            resp VARCHAR(255) DEFAULT 'NaN',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (registro_id) REFERENCES pmapc_registros(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 13. Plan de Trabajo (F24)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pmapc_plan_trabajo (
            id INT AUTO_INCREMENT PRIMARY KEY,
            registro_id INT NOT NULL,
            productor_id BIGINT UNSIGNED NOT NULL,
            actividad TEXT,
            componente VARCHAR(100) DEFAULT 'NaN',
            responsable VARCHAR(255) DEFAULT 'NaN',
            tiempo VARCHAR(100) DEFAULT 'NaN',
            resultado TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (registro_id) REFERENCES pmapc_registros(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 14. Dedicated Base/Table for Comments & Observations
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pmapc_comentarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            registro_id INT NOT NULL,
            productor_id BIGINT UNSIGNED NOT NULL,
            origen_archivo VARCHAR(255) DEFAULT 'NaN',
            comentarios_texto LONGTEXT,
            informacion_pendiente LONGTEXT,
            conclusion_general LONGTEXT,
            recomendaciones LONGTEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (registro_id) REFERENCES pmapc_registros(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (\PDOException $e) {
    error_log("Error creating schema: " . $e->getMessage());
}
?>
