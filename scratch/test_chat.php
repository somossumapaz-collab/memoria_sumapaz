<?php
// Mock php://input by defining input data before loading chat.php
function sendChatMessage($message) {
    $url = 'http://127.0.0.1:8000/api/chat.php';
    
    // We can also test directly via internal execution
    $_POST_RAW = json_encode(['message' => $message]);
    
    // Execute directly using php CLI simulation script
    $cmd = 'php -r ' . escapeshellarg('$data = json_encode(["message" => "' . addslashes($message) . '"]); $opts = ["http" => ["method" => "POST", "header" => "Content-Type: application/json\r\n", "content" => $data]]; echo file_get_contents("http://127.0.0.1:8000/api/chat.php", false, stream_context_create($opts));');
    return shell_exec($cmd);
}

// Direct function test
require_once __DIR__ . '/../api/db_config.php';
require_once __DIR__ . '/../api/env_loader.php';

echo "1. Probando getDynamicDatabaseSchema...\n";
// Read schema cache or generate
$cacheFile = __DIR__ . '/../tmp/db_schema_cache.json';
if (file_exists($cacheFile)) {
    unlink($cacheFile);
}

// Re-generate schema
require_once __DIR__ . '/../api/chat.php';
?>
