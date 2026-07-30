<?php
// Test runner for api/chat.php by launching PHP built-in web server on port 8999
$serverCmd = "php -S 127.0.0.1:8999 -t " . escapeshellarg(__DIR__ . '/..');
$serverProc = proc_open($serverCmd, [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w']
], $pipes);

// Wait 1 sec for server to start
usleep(1000000);

$question = $argv[1] ?? "¿Cuáles son las necesidades más frecuentes de los productores de papa y qué organizaciones presentan mayores niveles de priorización?";

echo "=================================================================\n";
echo "PREGUNTA: $question\n";
echo "=================================================================\n";

$ch = curl_init('http://127.0.0.1:8999/api/chat.php');
$postData = json_encode(['message' => $question], JSON_UNESCAPED_UNICODE);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Terminate server process
proc_terminate($serverProc);

echo "HTTP Code: $httpCode\n";
echo "RESPUESTA BACKEND:\n";
$json = json_decode($response, true);
if ($json && isset($json['reply'])) {
    echo $json['reply'] . "\n\n";
} else {
    echo $response . "\n\n";
}
?>
