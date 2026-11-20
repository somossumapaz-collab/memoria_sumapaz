<?php
/**
 * Script de creación de tablas para Control de Actividades y Contratistas
 * Prefijo/Sufijo: actividades_contratista_...
 */
require_once __DIR__ . '/db_config.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `actividades_contratistas` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `tipo_documento` VARCHAR(20) NOT NULL DEFAULT 'CC',
            `numero_documento` VARCHAR(30) NOT NULL,
            `nombres_apellidos` VARCHAR(150) NOT NULL,
            `nombre_identitario` VARCHAR(150) NULL,
            `telefono` VARCHAR(50) NULL,
            `correo` VARCHAR(120) NULL,
            `dependencia` VARCHAR(150) NULL DEFAULT 'Alcaldía Local de Sumapaz',
            `cargo` VARCHAR(50) NULL DEFAULT 'Profesional',
            `tipo_vinculacion` VARCHAR(50) NULL DEFAULT 'Contratista',
            `firma_digital` LONGTEXT NULL,
            `activo` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uk_act_contratista_doc` (`numero_documento`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `actividades_contratista_proyectos` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `contratista_id` INT NOT NULL,
            `proyecto_id` INT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uk_act_con_proy` (`contratista_id`, `proyecto_id`),
            KEY `idx_act_con_proy_p` (`proyecto_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `actividades_contratista_reportes` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `codigo_acta` VARCHAR(50) NULL,
            `contratista_id` INT NOT NULL,
            `proyecto_id` INT NOT NULL,
            `objeto_reunion` TEXT NOT NULL,
            `fecha` DATE NOT NULL,
            `hora_inicio` VARCHAR(20) NOT NULL,
            `hora_fin` VARCHAR(20) NOT NULL,
            `modalidad` VARCHAR(20) NOT NULL DEFAULT 'Presencial',
            `lugar` VARCHAR(255) NULL,
            `veredas_visitadas` TEXT NULL,
            `es_virtual` TINYINT(1) NOT NULL DEFAULT 0,
            `link_reunion` VARCHAR(255) NULL,
            `dependencia` VARCHAR(150) NULL,
            `nombre_responsable` VARCHAR(150) NULL,
            `requirio_transporte` TINYINT(1) NOT NULL DEFAULT 0,
            `formato_transporte_diligenciado` TINYINT(1) NOT NULL DEFAULT 0,
            `detalle_transporte` TEXT NULL,
            `desarrollo_conclusiones` LONGTEXT NULL,
            `caso_hola` VARCHAR(50) NULL DEFAULT '70103',
            `estado` VARCHAR(30) NOT NULL DEFAULT 'Registrada',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uk_act_codigo_acta` (`codigo_acta`),
            KEY `idx_act_rep_contratista` (`contratista_id`),
            KEY `idx_act_rep_proyecto` (`proyecto_id`),
            KEY `idx_act_rep_fecha` (`fecha`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `actividades_contratista_participantes` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `reporte_id` INT NOT NULL,
            `tipo_documento` VARCHAR(20) NOT NULL DEFAULT 'CC',
            `numero_documento` VARCHAR(30) NOT NULL,
            `nombres_apellidos` VARCHAR(150) NOT NULL,
            `nombre_identitario` VARCHAR(150) NULL,
            `entidad_dependencia` VARCHAR(150) NULL,
            `cargo` VARCHAR(50) NULL DEFAULT 'Profesional',
            `tipo_vinculacion` VARCHAR(50) NULL DEFAULT 'Contratista',
            `correo` VARCHAR(120) NULL,
            `telefono` VARCHAR(50) NULL,
            `firma_digital` LONGTEXT NULL,
            `es_responsable` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_act_part_rep` (`reporte_id`),
            KEY `idx_act_part_doc` (`numero_documento`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `actividades_contratista_compromisos` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `reporte_id` INT NOT NULL,
            `numero` INT NOT NULL DEFAULT 1,
            `actividad` TEXT NOT NULL,
            `responsable` VARCHAR(150) NOT NULL,
            `fecha_entrega` DATE NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_act_comp_rep` (`reporte_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `actividades_contratista_evidencias` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `reporte_id` INT NOT NULL,
            `archivo_url` VARCHAR(255) NOT NULL,
            `descripcion` VARCHAR(255) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_act_evid_rep` (`reporte_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    echo "Tablas creadas exitosamente.\n";
} catch (\Exception $e) {
    echo "Error al crear tablas: " . $e->getMessage() . "\n";
}
