<?php
require_once 'api/db_config.php';

try {
    echo "--- Veredas in Coordenadas ---\n";
    $stmt = $pdo->query("SELECT scanombre FROM veredas_coordenadas LIMIT 10");
    while($row = $stmt->fetch()) {
        echo "[" . $row['scanombre'] . "]\n";
    }

    echo "\n--- Origenes in Viajes ---\n";
    $stmt = $pdo->query("SELECT DISTINCT origen FROM transporte_viajes LIMIT 10");
    while($row = $stmt->fetch()) {
        echo "[" . $row['origen'] . "]\n";
    }

    echo "\n--- Veredas in Recorridos ---\n";
    $stmt = $pdo->query("SELECT DISTINCT vereda FROM transporte_recorridos LIMIT 10");
    while($row = $stmt->fetch()) {
        echo "[" . $row['vereda'] . "]\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
