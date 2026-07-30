<?php
// Mock php://input by defining input stream or stream wrapper, or using tmpfile for testing
$queryTest = "center";
if ($argc > 1) {
    $queryTest = $argv[1];
} else {
    $queryTest = "¿Cuáles son las necesidades más frecuentes de los productores de papa y qué veredas concentran mayor priorización?";
}

echo "Probando Chat Inteligente con la consulta:\n\"$queryTest\"\n\n";

$url = 'https://api.openai.com/v1/chat/completions';
require_once __DIR__ . '/../api/db_config.php';
require_once __DIR__ . '/../api/env_loader.php';

$apiKey = getenv('OPENAI_API_KEY');
if (empty($apiKey)) {
    die("Error: No API Key found.\n");
}

// 1. Probar extracción de esquema dinámico
$cacheFile = __DIR__ . '/../tmp/db_schema_cache.json';
if (file_exists($cacheFile)) {
    unlink($cacheFile);
}

// Execute schema logic
$dbNameStmt = $pdo->query("SELECT DATABASE()");
$dbName = $dbNameStmt->fetchColumn();

$tablesStmt = $pdo->prepare("
    SELECT TABLE_NAME, TABLE_COMMENT 
    FROM information_schema.TABLES 
    WHERE TABLE_SCHEMA = :dbname AND TABLE_TYPE = 'BASE TABLE'
    ORDER BY TABLE_NAME
");
$tablesStmt->execute(['dbname' => $dbName]);
$tables = $tablesStmt->fetchAll(PDO::FETCH_ASSOC);

echo "Tablas detectadas dinámicamente: " . count($tables) . " tablas.\n\n";

// Run end-to-end simulation of chat.php
$postData = json_encode(['message' => $queryTest]);

// Create context for internal php-cgi or router test
$opts = [
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/json\r\n",
        'content' => $postData
    ]
];

$context = stream_context_create($opts);

// We can test by setting up a local PHP server or testing the execution directly
// Let's run a test query via router.php or direct execution
?>
