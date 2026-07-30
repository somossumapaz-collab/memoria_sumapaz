<?php
require_once __DIR__ . '/../api/db_config.php';
require_once __DIR__ . '/../api/env_loader.php';

$question = $argv[1] ?? "¿Cuáles son los productos más cultivados y qué necesidades principales presentan los productores por vereda?";

echo "=================================================================\n";
echo "PREGUNTA: $question\n";
echo "=================================================================\n";

// Execute chat logic by passing payload to api/chat.php via local cURL or PHP input mock
$payload = json_encode(['message' => $question]);

// Temporary mock file for test
$tmpInput = __DIR__ . '/../tmp/test_input.json';
@file_put_contents($tmpInput, $payload);

$cmd = "php -r \"\$_SERVER['REQUEST_METHOD'] = 'POST'; function file_get_contents_mock(\$path) { if (\$path === 'php://input') return file_get_contents('$tmpInput'); return file_get_contents(\$path); } require '" . str_replace('\\', '/', __DIR__ . '/../api/chat.php') . "';\"";

// Or run chat.php directly in a subprocess with mocked php://input
$process = proc_open('php ' . escapeshellarg(__DIR__ . '/../api/chat.php'), [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w']
], $pipes);

if (is_resource($process)) {
    fwrite($pipes[0], $payload);
    fclose($pipes[0]);

    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    proc_close($process);

    if (!empty($stderr)) {
        echo "STDERR:\n$stderr\n";
    }

    $json = json_decode($stdout, true);
    if ($json && isset($json['reply'])) {
        echo "RESPUESTA ANALÍTICA DEL ASISTENTE:\n\n";
        echo $json['reply'] . "\n\n";
    } else {
        echo "RESPUESTA CRUDAS/ERROR:\n$stdout\n\n";
    }
}
?>
