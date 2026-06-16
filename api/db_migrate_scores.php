<?php
/**
 * Migration script to add score columns to caracterizacion_productor and calculate initial scores.
 */

require_once 'db_config.php';
require_once 'score_helper.php';

header('Content-Type: text/plain');

echo "Starting database migration for scoring columns...\n";

// 1. Add columns to caracterizacion_productor if they do not exist
$cols = [
    'puntaje_social' => 'INT DEFAULT NULL',
    'puntaje_organizacional' => 'INT DEFAULT NULL',
    'puntaje_productivo' => 'INT DEFAULT NULL',
    'puntaje_comercial' => 'INT DEFAULT NULL',
    'puntaje_ambiental' => 'INT DEFAULT NULL',
    'puntaje_impacto' => 'INT DEFAULT NULL',
    'puntaje' => 'INT DEFAULT NULL'
];

foreach ($cols as $colName => $colDef) {
    try {
        $pdo->exec("ALTER TABLE caracterizacion_productor ADD COLUMN `$colName` $colDef");
        echo "Successfully added column `$colName`.\n";
    } catch (\PDOException $e) {
        // Code 42S21 is column already exists in MySQL
        if ($e->getCode() == '42S21' || strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "Column `$colName` already exists, skipping.\n";
        } else {
            echo "Error adding column `$colName`: " . $e->getMessage() . "\n";
        }
    }
}

// 2. Fetch all characterizations in the database to calculate their scores
try {
    $stmt = $pdo->query("SELECT productor_id FROM caracterizacion_productor");
    $productores = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Found " . count($productores) . " characterization records. Recalculating scores...\n";
    
    $successCount = 0;
    foreach ($productores as $pid) {
        if (recalculate_and_save_score($pdo, $pid)) {
            $successCount++;
        }
    }
    
    echo "Recalculation finished. Successfully calculated and saved scores for $successCount producers.\n";
    echo "Migration completed successfully.\n";

} catch (Exception $e) {
    echo "Error during recalculation: " . $e->getMessage() . "\n";
}
?>
