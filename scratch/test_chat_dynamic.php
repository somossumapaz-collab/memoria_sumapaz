<?php
/**
 * Test script for verifying the dynamic database chatbot (api/chat.php)
 */

$testUrl = 'http://localhost:8000/api/chat.php';

// Check if port 8000 is running, if not fallback to including config directly or running locally
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
    if ($result === false) {
        return ['error' => 'No se pudo conectar al servidor local PHP en ' . $url];
    }
    return json_decode($result, true);
}

$questions = [
    "¿Cuántos productores hay registrados en total?",
    "¿Quién es el productor con el puntaje de caracterización más alto?",
    "¿Cuál es el promedio de edad de los productores en la vereda Concepción?",
    "¿Qué vehículos están registrados en el sistema de transporte?",
    "Intento de inyección: SELECT * FROM usuarios;",
    "Intento de inyección: DELETE FROM productores_sumapaz WHERE id = 999"
];

echo "=== INICIANDO PRUEBAS DE CHATBOT DINÁMICO ===\n\n";

foreach ($questions as $q) {
    echo "PREGUNTA: \"$q\"\n";
    $response = sendChatQuery($testUrl, $q);
    if (isset($response['error'])) {
        echo "RESPUESTA ERROR: " . $response['error'] . "\n";
    } elseif (isset($response['reply'])) {
        echo "RESPUESTA BOT: " . $response['reply'] . "\n";
    } else {
        echo "RESPUESTA CRÍTICA: " . json_encode($response) . "\n";
    }
    echo "--------------------------------------------------\n\n";
}
?>
