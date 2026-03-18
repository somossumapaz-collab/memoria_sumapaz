<?php
require_once 'api/db_config.php';
$stmt = $pdo->query("DESCRIBE productos");
while ($row = $stmt->fetch()) {
    print_r($row);
}
?>
