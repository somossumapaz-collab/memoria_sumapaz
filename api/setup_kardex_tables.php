<?php
/**
 * Setup script for Kardex Agroambiental
 * Creates database tables, configures User 14 & Role 14, and imports normalized inventory.
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_config.php';

try {
    // Drop tables if reset is requested to re-create clean schema
    $reset = isset($_GET['reset']) && $_GET['reset'] === '1';
    if ($reset) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("DROP TABLE IF EXISTS kardex_movimientos;");
        $pdo->exec("DROP TABLE IF EXISTS kardex_productos;");
        $pdo->exec("DROP TABLE IF EXISTS kardex_categorias;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    // 1. Create kardex_categorias table (DDL)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `kardex_categorias` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `nombre` VARCHAR(100) NOT NULL UNIQUE,
            `descripcion` TEXT NULL,
            `fecha_creacion` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. Create kardex_productos table (DDL)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `kardex_productos` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `categoria_id` INT NOT NULL,
            `nombre` VARCHAR(255) NOT NULL,
            `unidad_medida` VARCHAR(100) NOT NULL DEFAULT 'Unidad',
            `stock_actual` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `stock_minimo` DECIMAL(10,2) NOT NULL DEFAULT 5.00,
            `estado` ENUM('ACTIVO', 'INACTIVO', 'VENCIDO') DEFAULT 'ACTIVO',
            `notas` TEXT NULL,
            `fecha_creacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `fecha_actualizacion` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`categoria_id`) REFERENCES `kardex_categorias`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 3. Create kardex_movimientos table (DDL)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `kardex_movimientos` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `producto_id` INT NOT NULL,
            `tipo` ENUM('INGRESO', 'SALIDA') NOT NULL,
            `cantidad` DECIMAL(10,2) NOT NULL,
            `stock_anterior` DECIMAL(10,2) NOT NULL,
            `stock_resultante` DECIMAL(10,2) NOT NULL,
            `fecha_movimiento` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `fecha_ingreso` DATETIME NULL,
            `fecha_salida` DATETIME NULL,
            `usuario_id` INT NULL,
            `responsable` VARCHAR(255) NOT NULL DEFAULT 'Sistema Kardex',
            `cedula_responsable` VARCHAR(30) NULL,
            `vereda` VARCHAR(150) NULL,
            `concepto` VARCHAR(255) NOT NULL DEFAULT 'Carga de Inventario Inicial',
            `documento_referencia` VARCHAR(100) NULL,
            `observaciones` TEXT NULL,
            `fecha_registro` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`producto_id`) REFERENCES `kardex_productos`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Add columns if table already exists
    try {
        $pdo->exec("ALTER TABLE `kardex_movimientos` ADD COLUMN `cedula_responsable` VARCHAR(30) NULL AFTER `responsable`;");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE `kardex_movimientos` ADD COLUMN `fecha_ingreso` DATETIME NULL AFTER `fecha_movimiento`;");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE `kardex_movimientos` ADD COLUMN `fecha_salida` DATETIME NULL AFTER `fecha_ingreso`;");
    } catch (Exception $e) {}

    // 4. Ensure Role 14 exists in `roles` table
    $stmtRole = $pdo->prepare("SELECT id FROM roles WHERE id = 14 OR nombre = 'KARDEX AGROAMBIENTAL'");
    $stmtRole->execute();
    if (!$stmtRole->fetch()) {
        $stmtInsRole = $pdo->prepare("INSERT INTO roles (id, nombre, descripcion) VALUES (14, 'KARDEX AGROAMBIENTAL', 'Acceso exclusivo al sistema de Kardex Agroambiental')");
        $stmtInsRole->execute();
    }

    // 5. Ensure User 14 exists in `usuarios` table
    // Password for user 14: kardex123
    $passHash = password_hash('kardex123', PASSWORD_BCRYPT);
    $stmtUser = $pdo->prepare("SELECT id FROM usuarios WHERE id = 14");
    $stmtUser->execute();
    if ($stmtUser->fetch()) {
        $stmtUpdUser = $pdo->prepare("
            UPDATE usuarios 
            SET nombre = 'kardex_agroambiental', email = 'kardex@sumapaz.com', password = :pass, rol_id = 14 
            WHERE id = 14
        ");
        $stmtUpdUser->execute(['pass' => $passHash]);
    } else {
        $stmtInsUser = $pdo->prepare("
            INSERT INTO usuarios (id, nombre, email, password, rol_id) 
            VALUES (14, 'kardex_agroambiental', 'kardex@sumapaz.com', :pass, 14)
        ");
        $stmtInsUser->execute(['pass' => $passHash]);
    }

    $countProd = $pdo->query("SELECT COUNT(*) FROM kardex_productos")->fetchColumn();
    $imported_count = 0;

    $jsonPath = __DIR__ . '/../scratch/normalized_inventory.json';
    if (!file_exists($jsonPath)) {
        $jsonPath = 'C:/Users/sotoc/.gemini/antigravity/brain/b1e06ab4-b094-4f5f-933a-7b78ea675697/scratch/normalized_inventory.json';
    }

    if ($countProd == 0 && file_exists($jsonPath)) {
        $jsonData = file_get_contents($jsonPath);
        $items = json_decode($jsonData, true);

        if (!empty($items)) {
            $catMap = [];
            $stmtCatCheck = $pdo->prepare("SELECT id FROM kardex_categorias WHERE nombre = :nombre");
            $stmtCatIns = $pdo->prepare("INSERT INTO kardex_categorias (nombre, descripcion) VALUES (:nombre, :descripcion)");

            $stmtProdIns = $pdo->prepare("
                INSERT INTO kardex_productos (categoria_id, nombre, unidad_medida, stock_actual, stock_minimo, estado, notas) 
                VALUES (:categoria_id, :nombre, :unidad_medida, :stock_actual, 5.00, :estado, :notas)
            ");

            $stmtMovIns = $pdo->prepare("
                INSERT INTO kardex_movimientos (producto_id, tipo, cantidad, stock_anterior, stock_resultante, fecha_movimiento, usuario_id, responsable, concepto, observaciones) 
                VALUES (:producto_id, 'INGRESO', :cantidad, 0.00, :stock_resultante, NOW(), 14, 'Carga Inicial Excel', 'Inventario Inicial', :obs)
            ");

            foreach ($items as $item) {
                $catName = $item['categoria'];
                if (!isset($catMap[$catName])) {
                    $stmtCatCheck->execute(['nombre' => $catName]);
                    $catId = $stmtCatCheck->fetchColumn();
                    if (!$catId) {
                        $stmtCatIns->execute(['nombre' => $catName, 'descripcion' => 'Categoría de insumos agroambientales']);
                        $catId = $pdo->lastInsertId();
                    }
                    $catMap[$catName] = $catId;
                }

                $catId = $catMap[$catName];
                $nombre = $item['nombre'];
                $unidad = $item['unidad_medida'];
                $cant = (float)$item['cantidad_inicial'];
                $notas = $item['notas'];
                $estado = (strpos(strtolower($nombre), 'vencido') !== false) ? 'VENCIDO' : 'ACTIVO';

                // Insert product
                $stmtProdIns->execute([
                    'categoria_id' => $catId,
                    'nombre' => $nombre,
                    'unidad_medida' => $unidad,
                    'stock_actual' => $cant,
                    'estado' => $estado,
                    'notas' => $notas
                ]);
                $prodId = $pdo->lastInsertId();

                // Insert initial kardex movement
                $stmtMovIns->execute([
                    'producto_id' => $prodId,
                    'cantidad' => $cant,
                    'stock_resultante' => $cant,
                    'obs' => $notas ? "Carga inicial de Excel con nota: $notas" : 'Carga inicial desde inventario Excel'
                ]);

                $imported_count++;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Tablas y usuario 14 de Kardex Agroambiental configurados correctamente.',
        'productos_importados' => $imported_count
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error configurando Kardex: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
