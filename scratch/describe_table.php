<?php
require_once 'api/db_config.php';
try {
    $stmt = $pdo->query("DESCRIBE productores_sumapaz");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
