<?php
/**
 * API Endpoint: Upload PDF file, extract text, tables & comments, and populate PMAPC structure.
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

// 2. Handle uploaded file or file path
$pdfPath = null;
$isTempFile = false;

if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }
    $tempFileName = 'upload_pmapc_' . time() . '_' . uniqid() . '.pdf';
    $pdfPath = $uploadDir . $tempFileName;
    if (!move_uploaded_file($_FILES['pdf_file']['tmp_name'], $pdfPath)) {
        echo json_encode(['success' => false, 'error' => 'No se pudo guardar el archivo PDF subido.']);
        exit;
    }
    $isTempFile = true;
} else {
    $inputRaw = file_get_contents('php://input');
    if (empty($inputRaw) && file_exists(__DIR__ . '/../php_input_mock.json')) {
        $inputRaw = file_get_contents(__DIR__ . '/../php_input_mock.json');
    }
    $inputData = json_decode($inputRaw, true);
    if (isset($inputData['pdf_path']) && !empty($inputData['pdf_path'])) {
        $pdfPath = $inputData['pdf_path'];
    }
}

if (!$pdfPath || !file_exists($pdfPath)) {
    echo json_encode(['success' => false, 'error' => 'No se recibió ningún archivo PDF válido.']);
    exit;
}

// 3. Execute python parse_pdf.py to extract text & structured data
$pythonScript = __DIR__ . '/parse_pdf.py';
$command = "python " . escapeshellarg($pythonScript) . " " . escapeshellarg($pdfPath) . " 2>&1";

$pythonOutput = shell_exec($command);

// Clean up temp file if created
if ($isTempFile && file_exists($pdfPath)) {
    @unlink($pdfPath);
}

if (empty($pythonOutput)) {
    echo json_encode(['success' => false, 'error' => 'Fallo en el script de extracción de PDF (salida vacía).']);
    exit;
}

$parsedPdf = json_decode($pythonOutput, true);
if (!$parsedPdf || empty($parsedPdf['success'])) {
    $err = $parsedPdf['error'] ?? 'Error desconocido al analizar la estructura del PDF.';
    echo json_encode(['success' => false, 'error' => $err]);
    exit;
}

// Baseline structured data extracted directly from PDF Python parser
$mergedData = $parsedPdf['data'] ?? [];
$pdfText = $parsedPdf['text'] ?? '';
$pdfCommentsText = $parsedPdf['formatted_comments_text'] ?? '';

if (empty($pdfText) && empty($mergedData)) {
    echo json_encode(['success' => false, 'error' => 'El PDF no contiene texto ni datos extraíbles.']);
    exit;
}

// 4. Try OpenAI enrichment if API key is present
$apiKey = getenv('OPENAI_API_KEY');
if (!empty($apiKey)) {
    $combinedPromptText = "DOCUMENTO PDF DILIGENCIADO DEL PMAPC:\n\n" . $pdfText;
    if (!empty($pdfCommentsText)) {
        $combinedPromptText .= "\n\nCOMENTARIOS Y OBSERVACIONES EXTRAÍDAS DEL PDF:\n\n" . $pdfCommentsText;
    }

    $baseSystemPrompt = "Actúas como un profesional experto en desarrollo rural y gestión de proyectos productivos en Sumapaz.
Tu función es analizar el texto y comentarios extraídos de un documento PDF del PMAPC y estructurarlo en el formato JSON esperado.
Retornar EXCLUSIVAMENTE un objeto JSON válido, sin bloques de código markdown.";

    $groups = [
        'grupo1' => $baseSystemPrompt . "
Debes extraer la información correspondiente a los formatos f01, f02, f03, f04, f05 y pdf_comentarios.
Esquema:
- f01: { nombre_organizacion, tipo_actividad, ubicacion, coordenadas, producto_principal, estado_actual, descripcion_general }
- f02: { mision, vision, valores }
- f03: { problema, solucion, diferencial, valor_ambiental, valor_social, demostracion }
- f04: { fortalezas, oportunidades, debilidades, amenazas }
- f05: array de [ { actor, perfil, ubicacion, necesidad, frecuencia, criterio, canal } ]
- pdf_comentarios: resumen técnico estructurado de comentarios, observaciones e información pendiente.
",
        'grupo2' => $baseSystemPrompt . "
Debes extraer f06, f07, f08, f09, f10.
Esquema:
- f06: { necesidad, como_sabe, a_quien_afecta, evidencia, oportunidad_organicos, cambio, dificultad }
- f07: array de [ { actor, aporta, recibe, trabajo, ambiental, accion } ]
- f08: validaciones (quien_degus, resultado_degus, motivacion_degus, evidencia_degus...)
- f09: array de [ { producto, descripcion, unidad, insumos, almacenamiento, presentacion, diferencial } ]
- f10: array de [ { bien, unidades, actividad, tiempo } ]
",
        'grupo3' => $baseSystemPrompt . "
Debes extraer f11, f12, f12a, f12b, f12c.
Esquema:
- f11: array de [ { insumo, cantidad, frecuencia, proveedor, toxicidad, impacto, manejo } ]
- f12: { produccion_estimada, produccion_maxima, area, limitantes_prod, limitantes_amb... }
- f12a: recursos
- f12b: peligros
- f12c: controles
",
        'grupo4' => $baseSystemPrompt . "
Debes extraer f13, f14, f15, f15a, f15b, f15c, f16, f17.
Esquema:
- f14: costos/precios
- f15: ventas
- f16: inversiones [ { desc, valunit, cant, total, req, fuente } ]
- f17: gastos fijos [ { desc, val, obs } ]
",
        'grupo5' => $baseSystemPrompt . "
Debes extraer f18, f19, f19a, f20, f21, f22, f22a.
Esquema:
- f18: flujo de caja
- f20: array de [ { cant, manejo, destino, resp } ]
",
        'grupo6' => $baseSystemPrompt . "
Debes extraer f23, f24, f25, f26.
"
    ];

    $mh = curl_multi_init();
    $ch_list = [];

    foreach ($groups as $group_name => $group_prompt) {
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        $messages = [
            ['role' => 'system', 'content' => $group_prompt],
            ['role' => 'user', 'content' => $combinedPromptText]
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_multi_add_handle($mh, $ch);
        $ch_list[$group_name] = $ch;
    }

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

    foreach ($ch_list as $group_name => $ch) {
        $response = curl_multi_getcontent($ch);
        if (!curl_errno($ch)) {
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($statusCode === 200) {
                $responseData = json_decode($response, true);
                if (isset($responseData['choices'][0]['message']['content'])) {
                    $aiContent = trim($responseData['choices'][0]['message']['content']);
                    $parsedJson = json_decode($aiContent, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($parsedJson)) {
                        foreach ($parsedJson as $key => $value) {
                            if (!empty($value)) {
                                $mergedData[$key] = $value;
                            }
                        }
                    }
                }
            }
        }
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);
}

// Ensure pdf_comentarios is present
if (empty($mergedData['pdf_comentarios']) && !empty($pdfCommentsText)) {
    $mergedData['pdf_comentarios'] = $pdfCommentsText;
}

echo json_encode([
    'success' => true,
    'data' => $mergedData,
    'raw_comments' => $pdfCommentsText
], JSON_UNESCAPED_UNICODE);
?>
