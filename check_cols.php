<?php
require_once 'api/db_config.php';
try {
    $stmt = $pdo->query("DESCRIBE veredas_coordenadas");
    while($row = $stmt->fetch()) {
        echo $row['Field'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
