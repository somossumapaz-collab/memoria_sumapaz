<?php
/**
 * Test script for verifying chatbot calculation of puntaje_ajustado
 */

$testUrl = 'http://localhost:8000/api/chat.php';

function sendChatQuery($url, $message) {
    $data = ['message' => $message];
    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($data),
            'ignore_errors' => true
        ]
    ];
    $context  = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    return json_decode($result, true);
}

$q = "¿Quién tiene el puntaje ajustado más alto de caracterización, de qué vereda es, cuántos productores hay en esa vereda y cuál es el valor del puntaje ajustado calculado?";
echo "PREGUNTA: \"$q\"\n";
$response = sendChatQuery($testUrl, $q);
if (isset($response['reply'])) {
    echo "RESPUESTA BOT:\n" . $response['reply'] . "\n";
} else {
    echo "ERROR/RESPUESTA: " . json_encode($response) . "\n";
}
?>
