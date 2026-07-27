<?php
require_once __DIR__ . '/../api/db_config.php';

$stmt = $pdo->query("SELECT pe.id_productor, p.nombre_completo, GROUP_CONCAT(DISTINCT pe.nombre_evento ORDER BY pe.fecha_evento DESC SEPARATOR ', ') as circuitos FROM participacion_eventos pe JOIN productores_sumapaz p ON pe.id_productor = p.id GROUP BY pe.id_productor LIMIT 10");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "=== SAMPLE EVENT PARTICIPATIONS BY PRODUCER ===\n";
print_r($rows);
