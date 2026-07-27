<?php
require_once __DIR__ . '/../api/db_config.php';

try {
    $stmt = $pdo->query("SELECT id, nombre FROM categorias_productivas ORDER BY id");
    $cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($cats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
