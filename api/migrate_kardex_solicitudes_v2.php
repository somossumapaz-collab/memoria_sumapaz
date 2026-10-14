<?php
/**
 * Database Migration: Kardex Solicitudes, Imágenes y Paz y Salvo
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_config.php';

try {
    // 1. Alter kardex_productos to add imagen_url
    try {
        $pdo->exec("ALTER TABLE `kardex_productos` ADD COLUMN `imagen_url` VARCHAR(255) NULL AFTER `notas`;");
    } catch (Exception $e) {
        // Already exists or ignore
    }

    // 2. Alter kardex_movimientos to add comprobante_url, solicitud_id, numero_proyecto
    try {
        $pdo->exec("ALTER TABLE `kardex_movimientos` ADD COLUMN `comprobante_url` VARCHAR(255) NULL AFTER `documento_referencia`;");
    } catch (Exception $e) {}

    try {
        $pdo->exec("ALTER TABLE `kardex_movimientos` ADD COLUMN `solicitud_id` INT NULL AFTER `comprobante_url`;");
    } catch (Exception $e) {}

    try {
        $pdo->exec("ALTER TABLE `kardex_movimientos` ADD COLUMN `numero_proyecto` VARCHAR(100) NULL AFTER `solicitud_id`;");
    } catch (Exception $e) {}

    // 3. Create kardex_solicitudes table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `kardex_solicitudes` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `codigo_solicitud` VARCHAR(50) UNIQUE NOT NULL,
            `token_seguimiento` VARCHAR(64) UNIQUE NOT NULL,
            `fecha_solicitud` DATE NOT NULL,
            `fecha_retiro` DATE NULL,
            `numero_proyecto` VARCHAR(100) NOT NULL,
            `linea_inversion` VARCHAR(255) NULL,
            `meta` TEXT NULL,
            `vereda` VARCHAR(150) NOT NULL,
            `nombre_predio` VARCHAR(255) NULL,
            `nombre_productor` VARCHAR(255) NOT NULL,
            `cedula_productor` VARCHAR(50) NOT NULL,
            `telefono_productor` VARCHAR(50) NULL,
            `actividad` TEXT NULL,
            `solicitante_nombre` VARCHAR(255) NOT NULL,
            `solicitante_cps` VARCHAR(100) NULL,
            `solicitante_cargo` VARCHAR(150) NULL,
            `solicitante_firma` LONGTEXT NULL,
            `observaciones` TEXT NULL,
            `estado` ENUM('PENDIENTE', 'DESPACHADO', 'EVIDENCIAS_CARGADAS', 'PAZ_Y_SALVO', 'RECHAZADO') DEFAULT 'PENDIENTE',
            `fecha_entrega` DATETIME NULL,
            `foto_entrega_url` VARCHAR(255) NULL,
            `despachado_por` INT NULL,
            `acta_entrega_url` VARCHAR(255) NULL,
            `evidencias_fotos` TEXT NULL,
            `fecha_evidencias` DATETIME NULL,
            `paz_y_salvo` TINYINT(1) DEFAULT 0,
            `fecha_paz_y_salvo` DATETIME NULL,
            `usuario_paz_y_salvo` INT NULL,
            `observaciones_paz_y_salvo` TEXT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (`cedula_productor`),
            INDEX (`numero_proyecto`),
            INDEX (`estado`),
            INDEX (`paz_y_salvo`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 4. Create kardex_solicitud_items table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `kardex_solicitud_items` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `solicitud_id` INT NOT NULL,
            `producto_id` INT NULL,
            `codigo_insumo` VARCHAR(100) NULL,
            `descripcion` VARCHAR(255) NOT NULL,
            `unidad_medida` VARCHAR(100) NOT NULL,
            `cantidad_solicitada` DECIMAL(10,2) NOT NULL,
            `cantidad_entregada` DECIMAL(10,2) DEFAULT 0.00,
            FOREIGN KEY (`solicitud_id`) REFERENCES `kardex_solicitudes`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 5. Ensure upload directories exist
    $baseUploadDir = dirname(__DIR__) . '/uploads/kardex';
    $subDirs = ['productos', 'comprobantes', 'entregas', 'actas', 'evidencias'];
    
    if (!file_exists($baseUploadDir)) {
        @mkdir($baseUploadDir, 0777, true);
    }
    
    foreach ($subDirs as $sub) {
        $path = $baseUploadDir . '/' . $sub;
        if (!file_exists($path)) {
            @mkdir($path, 0777, true);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Migración de Kardex Solicitudes y tablas auxiliares completada con éxito.'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error durante la migración: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
