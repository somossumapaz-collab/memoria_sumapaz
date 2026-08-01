<?php
require_once 'api/db_config.php';
echo "pmapc_plan_trabajo final count: " . $pdo->query("SELECT COUNT(*) FROM pmapc_plan_trabajo")->fetchColumn() . "\n";
