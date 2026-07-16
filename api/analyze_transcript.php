<?php
/**
 * API Endpoint: Analyze interview transcript and return PMAPC JSON structure
 * Uses OpenAI GPT-4o-mini in JSON mode.
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('max_execution_time', '120');
set_time_limit(120);

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo json_encode(['success' => false, 'error' => "PHP Error [$errno]: $errstr in $errfile on line $errline"]);
    exit;
});
set_exception_handler(function($e) {
    echo json_encode(['success' => false, 'error' => "PHP Exception: " . $e->getMessage()]);
    exit;
});

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

// 1. Load env variables
require_once __DIR__ . '/env_loader.php';

$apiKey = getenv('OPENAI_API_KEY');
if (empty($apiKey)) {
    echo json_encode(['success' => false, 'error' => 'API Key de OpenAI no configurada en las variables de entorno.']);
    exit;
}

// 2. Parse input data
$inputRaw = file_get_contents('php://input');
$inputData = json_decode($inputRaw, true);
$transcriptText = isset($inputData['transcript']) ? trim($inputData['transcript']) : '';

if (empty($transcriptText)) {
    echo json_encode(['success' => false, 'error' => 'La transcripción está vacía.']);
    exit;
}

// 3. Define the Prompt & Schema for OpenAI
$systemPrompt = "Eres una inteligencia artificial experta en análisis de datos agrícolas y redacción de planes de manejo para 'Somos Sumapaz'. Tu tarea es analizar la transcripción de una entrevista con un productor rural y extraer la información relevante para completar el Plan de Manejo Ambiental, Productivo y Comercial (PMAPC).

Debes retornar EXCLUSIVAMENTE un objeto JSON válido. Para evitar respuestas truncadas y optimizar el tiempo, sigue estas reglas estrictas:
1. OMITIR por completo cualquier formato/módulo (f01, f02, f03, etc.) si no encontraste información sobre él en la entrevista.
2. OMITIR claves individuales dentro de los formatos si no tienen datos extraídos. No devuelvas cadenas vacías ni estructuras vacías para lo que no se mencione.
3. Solo genera las secciones que tengan información útil.
4. Conserva estrictamente los nombres de las claves del esquema si decides incluirlas.

Esquema de claves posibles:
- f01: nombre_organizacion, tipo_actividad (seleccionar de: 'agricola', 'pecuaria', 'artesanal', 'agroindustrial', 'servicios', 'otra'), ubicacion, coordenadas, producto_principal, estado_actual (seleccionar de: 'idea', 'produccion_inicial', 'negocio_marcha', 'asociacion', 'otro'), descripcion_general.
- f02: mision, vision, valores (lista de valores en una sola cadena de texto separados por coma, no arreglos, ej. 'Respeto, Honestidad').
- f03: problema, solucion, diferencial, valor_ambiental, valor_social, demostracion.
- f04: fortalezas, oportunidades, debilidades, amenazas (deben ser cadenas de texto descriptivas simples, no arreglos).
- f05: array de objetos con clientes: [ { actor, perfil, ubicacion, necesidad, frecuencia, criterio, canal } ]
- f06: necesidad, como_sabe, a_quien_afecta, evidencia, oportunidad_organicos, cambio, dificultad.
- f07: array de aliados: [ { actor, aporta, recibe, trabajo, ambiental, accion } ]
- f08: validaciones de mercado: quien_degus, resultado_degus, motivacion_degus, evidencia_degus, quien_ventas, resultado_ventas, motivacion_ventas, evidencia_ventas, quien_cartas, resultado_cartas, motivacion_cartas, evidencia_cartas, quien_encuesta, resultado_encuesta, motivacion_encuesta, evidencia_encuesta, quien_entrevista, resultado_entrevista, motivacion_entrevista, evidencia_entrevista, quien_feria, resultado_feria, motivacion_feria, evidencia_feria, metodo_otro, quien_otro, resultado_otro, motivacion_otro, evidencia_otro.
- f09: array de productos: [ { producto, descripcion, unidad, insumos, almacenamiento, presentacion, diferencial } ]
- f10: array de herramientas/bienes: [ { bien, unidades, actividad, tiempo } ]
- f11: array de insumos: [ { insumo, cantidad, frecuencia, proveedor, toxicidad ('N/A'/'Baja'/'Media'/'Alta'), impacto, manejo } ]
- f12: produccion_estimada, produccion_maxima, area, limitantes_prod, limitantes_amb, capacidad_instalada, capacidad_utilizada, misma_cantidad, alcanza_demanda, necesidad_sostenible.
- f12a: recursos (estado_agua, limite_agua, efecto_agua, accion_agua, estado_fuentes, limite_fuentes, efecto_fuentes, accion_fuentes, estado_suelo, limite_suelo, efecto_suelo, accion_suelo, estado_pendiente, limite_pendiente, efecto_pendiente, accion_pendiente, estado_clima, limite_clima, efecto_clima, accion_clima, estado_bio, limite_bio, efecto_bio, accion_bio, estado_insumos, limite_insumos, efecto_insumos, accion_insumos, estado_residuos, limite_residuos, efecto_residuos, accion_residuos).
- f12b: peligros (virus, bacterias, picaduras, mordeduras, temperatura, radiacion, ruido, polvos, gases, particulado, posturas, movimientos, cargas, mecanico, locativo, electrico, transito). Cada uno es un objeto con: si, no, f_alta, f_media, f_baja, controles, mejora. (Incluye solo los peligros que sí apliquen).
- f12c: controles de inocuidad. Objeto con claves '1' a '7' con resp, frec, evidencia.
- f13: array de 8 objetos con aplica (Si/No), detalles, frec, resp.
- f14: costos/precios: [ { producto, costo, margen, pmin, pmercado, logistica, precio, justificacion } ]
- f15: ventas: [ { producto, cantidad, precio, ingresos, pago, cliente } ]
- f15a: fidelización: [ { est, med, frec, resp } ]
- f15b: logística: [ { prod, canal, tiempo, transporte, condicion, capacidad, costo, resp } ]
- f15c: trazabilidad: [ { resp, med, frec, evi } ]
- f16: inversiones: [ { desc, valunit, cant, total, req (Si/No), fuente } ]
- f17: gastos fijos: [ { desc, val, obs } ]
- f18: flujo de caja (ingreso_m1 a ingreso_m6, gprod_m1 a gprod_m6, gamb_m1 a gamb_m6, glog_m1 a glog_m6, obs_m1 a obs_m6).
- f19_conclusion (string), f19: array de [ { ini, meta, frec, resp, evi } ]
- f19a_conclusion (string), f19a: array de [ { desc, cant, impacto, mejora } ]
- f20_conclusion (string), f20: array de [ { cant, manejo, destino, resp } ]
- f21_conclusion (string), f21: array de [ { estado, cal, mejora, evi } ]
- f22: array de [ { accion, plazo, resp, rec, ind, evi } ]
- f22a_conclusion (string), f22a: array de [ { resp, riesgo, mejora } ]
- f23: causa_clima, cons_clima, nivel_clima, prev_clima, causa_costos, cons_costos, nivel_costos, prev_costos.
- f24: array de [ { actividad, componente ('Digital'/'Productivo'/'Organizacional'/'Comercial'/'Ambiental'), responsable, tiempo, resultado } ]
- f25: array de [ { ind, meta, frec, resp, evi } ]
- f26: array de [ { prod, com, fin, amb, aju } ]
- f26_coherencia: string.
";

// 4. Connect to OpenAI
$url = 'https://api.openai.com/v1/chat/completions';

$messages = [
    ['role' => 'system', 'content' => $systemPrompt],
    ['role' => 'user', 'content' => "Aquí está la transcripción de la entrevista:\n\n" . $transcriptText]
];

$postData = [
    'model' => 'gpt-4o-mini',
    'messages' => $messages,
    'temperature' => 0.1,
    'max_tokens' => 4000,
    'response_format' => ['type' => 'json_object']
];

$options = [
    'http' => [
        'header'  => "Content-Type: application/json\r\n" .
                     "Authorization: Bearer " . $apiKey . "\r\n",
        'method'  => 'POST',
        'content' => json_encode($postData),
        'timeout' => 90,
        'ignore_errors' => true
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ],
];

try {
    $context  = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        throw new Exception("Error al conectar con el servicio de IA de OpenAI.");
    }
    
    $responseData = json_decode($response, true);
    if (isset($responseData['error'])) {
        throw new Exception("OpenAI Error: " . $responseData['error']['message']);
    }
    
    if (!isset($responseData['choices'][0]['message']['content'])) {
        throw new Exception("Respuesta inválida o vacía de la API de OpenAI.");
    }
    
    $aiContent = trim($responseData['choices'][0]['message']['content']);
    $parsedJson = json_decode($aiContent, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("La respuesta de la IA no es un JSON válido: " . json_last_error_msg() . ". Contenido recibido: " . substr($aiContent, 0, 1500));
    }
    
    echo json_encode([
        'success' => true,
        'data' => $parsedJson
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
