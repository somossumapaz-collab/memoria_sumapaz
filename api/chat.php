<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Catch errors and return as JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo json_encode(['error' => "PHP Error [$errno]: $errstr in $errfile on line $errline"]);
    exit;
});
set_exception_handler(function($e) {
    echo json_encode(['error' => "PHP Exception: " . $e->getMessage()]);
    exit;
});

header('Content-Type: application/json');

// Recibir el mensaje del frontend
$data = json_decode(file_get_contents('php://input'), true);
$userMessage = isset($data['message']) ? trim($data['message']) : '';

if (empty($userMessage)) {
    echo json_encode(['error' => 'Mensaje vacío.']);
    exit;
}

// Configuración de OpenAI
$apiKey = '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';
$url = 'https://api.openai.com/v1/chat/completions';

// System prompt con el contexto
$systemPrompt = "Eres el asistente virtual de 'Somos Sumapaz', una plataforma que conecta a productores campesinos locales de la Localidad de Sumapaz (Bogotá) con compradores y comerciantes. Tu tono es amable, respetuoso y muy útil. Solo respondes preguntas relacionadas con el campo, la agricultura, los productores locales, convocatorias de la plataforma, y el territorio de Sumapaz. Si te preguntan algo fuera de este contexto, amablemente indica que tu propósito es ayudar con la plataforma Somos Sumapaz. Mantén tus respuestas concisas y fáciles de leer.";

$messages = [
    ['role' => 'system', 'content' => $systemPrompt],
    ['role' => 'user', 'content' => $userMessage]
];

// Datos para la API de OpenAI
$postData = [
    'model' => 'gpt-4o-mini',
    'messages' => $messages,
    'temperature' => 0.7,
    'max_tokens' => 250
];

$options = [
    'http' => [
        'header'  => "Content-Type: application/json\r\n" .
                     "Authorization: Bearer " . $apiKey . "\r\n",
        'method'  => 'POST',
        'content' => json_encode($postData),
        'ignore_errors' => true // to fetch body even on 4xx/5xx errors
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ],
];

$context  = stream_context_create($options);
$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo json_encode(['error' => 'Error en la conexión con la IA (file_get_contents falló).']);
    exit;
}

$responseData = json_decode($response, true);

if (isset($responseData['choices'][0]['message']['content'])) {
    $botReply = $responseData['choices'][0]['message']['content'];
    echo json_encode(['reply' => trim($botReply)]);
} else {
    // Si hay algún error en la respuesta de OpenAI
    $errorMsg = isset($responseData['error']['message']) ? $responseData['error']['message'] : 'Respuesta inválida de la IA.';
    echo json_encode(['error' => 'Error de OpenAI: ' . $errorMsg]);
}
?>
