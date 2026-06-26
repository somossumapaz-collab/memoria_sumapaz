<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../api/db_config.php';
require_once __DIR__ . '/../api/score_helper.php';

echo "Database connected.\n";
echo "Running global beneficiary realignment...\n";

try {
    update_global_beneficiaries($pdo);
    echo "Alignment complete! Database is now synchronized with top 151 score rankings.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
