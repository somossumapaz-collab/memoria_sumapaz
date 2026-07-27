<?php
require_once __DIR__ . '/../api/db_config.php';

$stmt = $pdo->query("SELECT pr.id as registro_id, pr.productor_id, p.nombre_completo, p.vereda, CHAR_LENGTH(pr.data) as bytes FROM pmapc_registros pr JOIN productores_sumapaz p ON pr.productor_id = p.id ORDER BY pr.id ASC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "=== CURRENT PMAPC RECORDS IN DATABASE ===\n";
foreach ($rows as $r) {
    echo "Registro #{$r['registro_id']} | Producer ID {$r['productor_id']} | Name: {$r['nombre_completo']} | Vereda: {$r['vereda']} | Payload: {$r['bytes']} bytes\n";
}
