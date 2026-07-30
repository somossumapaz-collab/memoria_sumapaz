<?php
require_once __DIR__ . '/../api/db_config.php';
$pdo->exec("DELETE FROM pmapc_registros WHERE id = 77 AND productor_id = 241");
$pdo->exec("DELETE FROM pmapc_estrategico WHERE registro_id = 77");
$pdo->exec("DELETE FROM pmapc_comentarios WHERE registro_id = 77");
echo "Cleaned record 77 successfully.\n";
