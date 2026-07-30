<?php
require_once __DIR__ . '/../api/db_config.php';
require_once __DIR__ . '/../api/env_loader.php';

// Include helper functions from chat logic
$schema = getDynamicDatabaseSchema($pdo);

echo "=== ESQUEMA DINÁMICO EXTRAÍDO ===\n";
echo substr($schema, 0, 1500) . "\n...\n";

echo "\n=== PROBANDO VALIDADOR SEGURIDAD SQL ===\n";

$queriesToTest = [
    "SELECT p.nombre_completo, p.vereda, cp.puntaje FROM productores_sumapaz p JOIN caracterizacion_productor cp ON p.id = cp.productor_id LIMIT 5;",
    "UPDATE productores_sumapaz SET nombre_completo = 'Hack'",
    "SELECT * FROM productores_sumapaz; DROP TABLE usuarios;",
    "SELECT * FROM productores_sumapaz WHERE id=1 -- comment",
    "SELECT COUNT(*) as total FROM pmapc_registros"
];

foreach ($queriesToTest as $q) {
    echo "\nTesting SQL: $q\n";
    try {
        $res = runSecureQuery($pdo, $q);
        echo "RESULT (Success): " . substr($res, 0, 150) . "...\n";
    } catch (Exception $e) {
        echo "RESULT (Blocked correctly): " . $e->getMessage() . "\n";
    }
}
?>
