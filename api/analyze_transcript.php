<?php
/**
 * API Endpoint: Analyze interview transcript and return PMAPC JSON structure
 * Uses OpenAI GPT-4o-mini in parallel groups.
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('max_execution_time', '180');
set_time_limit(180);

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

// Clean and normalize UTF-8 for transcript input
if (!preg_match('//u', $transcriptText)) {
    $transcriptText = iconv('Windows-1252', 'UTF-8//IGNORE', $transcriptText);
} else {
    $transcriptText = iconv('UTF-8', 'UTF-8//IGNORE', $transcriptText);
}

// 3. Define the Prompt & Schema for OpenAI
$baseSystemPrompt = "Actúas como un profesional experto en desarrollo rural, formulación de proyectos productivos, agronegocios, gestión ambiental, economía circular y fortalecimiento de unidades productivas campesinas, con amplia experiencia en el diligenciamiento del Plan de Manejo Ambiental, Productivo y Comercial (PMAPC) para la Localidad de Sumapaz.

Tu función es analizar la transcripción de una entrevista con un productor rural y extraer la información para completar una parte del formato JSON del PMAPC, utilizando una redacción técnica, clara, coherente y profesional.

Debes retornar EXCLUSIVAMENTE un objeto JSON válido, sin bloques de código markdown. Sigue estas reglas estrictas:
1. OMITIR por completo cualquier formato/módulo si no hay ninguna información REAL sobre él en la entrevista. Si todos los campos del formato serían rellenados con respuestas de reserva (como 'No informado durante la entrevista.'), debes OMITIRLO por completo del JSON.
2. Para los campos de texto específicos que sí incluyas pero que no tengan datos en la entrevista, utiliza estas expresiones técnicas de reserva en lugar de inventar datos:
   - 'No informado durante la entrevista.'
   - 'Pendiente de verificar en visita técnica.'
   - 'Requiere validación en campo.'
   - 'No fue posible determinar con la información disponible.'
3. No debes copiar literalmente lo que dice el productor. Debes interpretar la información, organizarla y convertirla en respuestas técnicas profesionales.
   - Ejemplo (Venta/Producto): Si el productor dice 'Yo vendo pollos porque la gente dice que saben ricos', debes redactar: 'La unidad productiva comercializa pollos de engorde alimentados mediante un sistema complementario con concentrado, maíz y pasto, lo que genera un producto con características diferenciadas y una alta aceptación entre los consumidores locales debido a su sabor y calidad.'
   - Ejemplo (Residuos/Economía Circular): Si el productor dice 'La gallinaza la echo a la huerta', debes redactar: 'La gallinaza generada durante el proceso productivo es aprovechada como abono orgánico en la huerta familiar, favoreciendo el reciclaje de nutrientes, la reducción de residuos pecuarios y el fortalecimiento de prácticas de economía circular.'
   - Ejemplo (Inversiones): Si el productor dice 'Comprar un congelador', debes redactar: 'Adquisición de un equipo de refrigeración que garantice la conservación del producto bajo condiciones adecuadas de inocuidad, fortaleciendo la cadena de frío, disminuyendo pérdidas poscosecha y mejorando la capacidad de comercialización de la unidad productiva.'
4. No uses respuestas de una sola palabra. Cada casilla de texto debe quedar suficientemente desarrollada de forma técnica.
5. Los campos de Valores (f02) y FODA (f04) deben ser cadenas de texto descriptivas simples (valores separados por coma en f02), nunca arreglos.
";

$groups = [
    'grupo1' => $baseSystemPrompt . "
Debes extraer la información correspondiente a los formatos f01, f02, f03, f04 y f05.
Esquema de claves posibles para este grupo:
- f01: nombre_organizacion, tipo_actividad ('agricola'/'pecuaria'/'artesanal'/'agroindustrial'/'servicios'/'otra'), ubicacion, coordenadas, producto_principal, estado_actual ('idea'/'produccion_inicial'/'negocio_marcha'/'asociacion'/'otro'), descripcion_general.
- f02: mision, vision, valores (lista de valores separados por coma, ej. 'Responsabilidad, Honestidad').
- f03: problema, solucion, diferencial, valor_ambiental, valor_social, demostracion.
- f04: fortalezas, oportunidades, debilidades, amenazas (textos explicados técnicamente, no arreglos).
- f05: array de objetos con clientes: [ { actor, perfil, ubicacion, necesidad, frecuencia, criterio, canal } ]
",
    'grupo2' => $baseSystemPrompt . "
Debes extraer la información correspondiente a los formatos f06, f07, f08, f09 y f10.
Esquema de claves posibles para este grupo:
- f06: necesidad, como_sabe, a_quien_afecta, evidencia, oportunidad_organicos, cambio, dificultad.
- f07: array de aliados: [ { actor, aporta, recibe, trabajo, ambiental, accion } ]
- f08: validaciones de mercado: quien_degus, resultado_degus, motivacion_degus, evidencia_degus, quien_ventas, resultado_ventas... (sufijos _degus, _ventas, _cartas, _encuesta, _entrevista, _feria, _otro).
- f09: array de productos: [ { producto, descripcion, unidad, insumos, almacenamiento, presentacion, diferencial } ]
- f10: array de bienes: [ { bien, unidades, actividad, tiempo } ]
",
    'grupo3' => $baseSystemPrompt . "
Debes extraer la información correspondiente a los formatos f11, f12, f12a, f12b y f12c.
Esquema de claves posibles para este grupo:
- f11: array de insumos: [ { insumo, cantidad, frecuencia, proveedor, toxicidad ('N/A'/'Baja'/'Media'/'Alta'), impacto, manejo } ]
- f12: produccion_estimada, produccion_maxima, area, limitantes_prod, limitantes_amb, capacidad_instalada, capacidad_utilizada, misma_cantidad, alcanza_demanda, necesidad_sostenible.
- f12a: recursos (estado_agua, limite_agua, efecto_agua, accion_agua, estado_fuentes, limite_fuentes, efecto_fuentes, accion_fuentes, estado_suelo, limite_suelo, efecto_suelo, accion_suelo, estado_pendiente, limite_pendiente, efecto_pendiente, accion_pendiente, estado_clima, limite_clima, efecto_clima, accion_clima, estado_bio, limite_bio, efecto_bio, accion_bio, estado_insumos, limite_insumos, efecto_insumos, accion_insumos, estado_residuos, limite_residuos, efecto_residuos, accion_residuos).
- f12b: peligros (virus, bacterias, picaduras, mordeduras, temperatura, radiacion, ruido, polvos, gases, particulado, posturas, movimientos, cargas, mecanico, locativo, electrico, transito). Cada uno con: si, no, f_alta, f_media, f_baja, controles, mejora.
- f12c: controles. Objeto con claves '1' a '7' con resp, frec, evidencia.
",
    'grupo4' => $baseSystemPrompt . "
Debes extraer la información correspondiente a los formatos f13, f14, f15, f15a, f15b, f15c, f16 y f17.
Esquema de claves posibles para este grupo:
- f13: array de 8 objetos con aplica (Si/No), detalles, frec, resp.
- f14: costos/precios: [ { producto, costo, margen, pmin, pmercado, logistica, precio, justificacion } ]
- f15: ventas: [ { producto, cantidad, precio, ingresos, pago, cliente } ]
- f15a: fidelización: [ { est, med, frec, resp } ]
- f15b: logística: [ { prod, canal, tiempo, transporte, condicion, capacidad, costo, resp } ]
- f15c: trazabilidad: [ { resp, med, frec, evi } ]
- f16: inversiones: [ { desc, valunit, cant, total, req (Si/No), fuente } ]
- f17: gastos fijos: [ { desc, val, obs } ]
",
    'grupo5' => $baseSystemPrompt . "
Debes extraer la información correspondiente a los formatos f18, f19, f19a, f20, f21, f22 y f22a.
Esquema de claves posibles para este grupo:
- f18: flujo de caja (ingreso_m1 a ingreso_m6, gprod_m1 a gprod_m6, gamb_m1 a gamb_m6, glog_m1 a glog_m6, obs_m1 a obs_m6).
- f19_conclusion (string), f19: array de [ { ini, meta, frec, resp, evi } ]
- f19a_conclusion (string), f19a: array de [ { desc, cant, impacto, mejora } ]
- f20_conclusion (string), f20: array de [ { cant, manejo, destino, resp } ]
- f21_conclusion (string), f21: array de [ { estado, cal, mejora, evi } ]
- f22: array de [ { accion, plazo, resp, rec, ind, evi } ]
- f22a_conclusion (string), f22a: array de [ { resp, riesgo, mejora } ]
",
    'grupo6' => $baseSystemPrompt . "
Debes extraer la información correspondiente a los formatos f23, f24, f25 y f26.
Esquema de claves posibles para este grupo:
- f23: causa_clima, cons_clima, nivel_clima, prev_clima, causa_costos, cons_costos, nivel_costos, prev_costos.
- f24: array de [ { actividad, componente ('Digital'/'Productivo'/'Organizacional'/'Comercial'/'Ambiental'), responsable, tiempo, resultado } ]
- f25: array de [ { ind, meta, frec, resp, evi } ]
- f26: array de [ { prod, com, fin, amb, aju } ]
- f26_coherencia: string.
"
];

// 4. Exec Parallel Requests to OpenAI
$mh = curl_multi_init();
$ch_list = [];

foreach ($groups as $group_name => $group_prompt) {
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    
    $messages = [
        ['role' => 'system', 'content' => $group_prompt],
        ['role' => 'user', 'content' => "Aquí está la transcripción de la entrevista:\n\n" . $transcriptText]
    ];
    
    $postData = [
        'model' => 'gpt-4o-mini',
        'messages' => $messages,
        'temperature' => 0.1,
        'max_tokens' => 4000,
        'response_format' => ['type' => 'json_object']
    ];
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_TIMEOUT, 90);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    curl_multi_add_handle($mh, $ch);
    $ch_list[$group_name] = $ch;
}

// Execute parallel requests
$active = null;
do {
    $mrc = curl_multi_exec($mh, $active);
} while ($mrc == CURLM_CALL_MULTI_PERFORM);

while ($active && $mrc == CURLM_OK) {
    if (curl_multi_select($mh) != -1) {
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);
    }
}

// Retrieve and merge responses
$mergedData = [];
$errors = [];

foreach ($ch_list as $group_name => $ch) {
    $response = curl_multi_getcontent($ch);
    
    if (curl_errno($ch)) {
        $errors[] = "$group_name curl error: " . curl_error($ch);
    } else {
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($statusCode !== 200) {
            $errors[] = "$group_name HTTP error: $statusCode. Response: " . substr($response, 0, 500);
        } else {
            $responseData = json_decode($response, true);
            if (isset($responseData['error'])) {
                $errors[] = "$group_name OpenAI Error: " . $responseData['error']['message'];
            } elseif (isset($responseData['choices'][0]['message']['content'])) {
                $aiContent = trim($responseData['choices'][0]['message']['content']);
                $aiContent = iconv('UTF-8', 'UTF-8//IGNORE', $aiContent);
                $parsedJson = json_decode($aiContent, true);
                
                if (json_last_error() === JSON_ERROR_NONE && is_array($parsedJson)) {
                    // Merge formats into the final dataset
                    foreach ($parsedJson as $key => $value) {
                        $mergedData[$key] = $value;
                    }
                } else {
                    $errors[] = "$group_name JSON decode error: " . json_last_error_msg() . ". Content: " . substr($aiContent, 0, 1000);
                }
            } else {
                $errors[] = "$group_name invalid OpenAI response structure.";
            }
        }
    }
    
    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);
}
curl_multi_close($mh);

// Check if we extracted anything at all
if (empty($mergedData)) {
    echo json_encode([
        'success' => false,
        'error' => "No se pudo extraer ningún módulo. Errores: " . implode(" | ", $errors)
    ]);
} else {
    echo json_encode([
        'success' => true,
        'data' => $mergedData,
        'warnings' => !empty($errors) ? $errors : null
    ], JSON_UNESCAPED_UNICODE);
}
?>
