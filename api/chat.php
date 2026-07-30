<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Catch errors and return as JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (E_DEPRECATED === $errno || E_USER_DEPRECATED === $errno) {
        return false;
    }
    echo json_encode(['error' => "PHP Error [$errno]: $errstr in $errfile on line $errline"]);
    exit;
});
set_exception_handler(function($e) {
    echo json_encode(['error' => "PHP Exception: " . $e->getMessage()]);
    exit;
});

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

// 1. Recibir el mensaje del frontend
$data = json_decode(file_get_contents('php://input'), true);
$userMessage = isset($data['message']) ? trim($data['message']) : '';

if (empty($userMessage)) {
    echo json_encode(['error' => 'Mensaje vacío.']);
    exit;
}

// 2. Conectarse a la base de datos
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/env_loader.php';

$openAiApiKey = getenv('OPENAI_API_KEY');
$geminiApiKey = getenv('GEMINI_API_KEY');

if (empty($openAiApiKey) && empty($geminiApiKey)) {
    echo json_encode(['error' => 'API Key (Gemini u OpenAI) no configurada en las variables de entorno.']);
    exit;
}

$url = 'https://api.openai.com/v1/chat/completions';

/**
 * Obtención dinámica y automatizada del esquema de la base de datos MySQL
 */
function getDynamicDatabaseSchema($pdo) {
    $cacheFile = __DIR__ . '/../tmp/db_schema_cache.json';
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 600)) {
        $cachedSchema = file_get_contents($cacheFile);
        if (!empty($cachedSchema)) {
            return $cachedSchema;
        }
    }

    try {
        $dbNameStmt = $pdo->query("SELECT DATABASE()");
        $dbName = $dbNameStmt->fetchColumn();

        // 1. Obtener tablas
        $tablesStmt = $pdo->prepare("
            SELECT TABLE_NAME, TABLE_COMMENT 
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = :dbname AND TABLE_TYPE = 'BASE TABLE'
            ORDER BY TABLE_NAME
        ");
        $tablesStmt->execute(['dbname' => $dbName]);
        $tables = $tablesStmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Obtener columnas
        $colsStmt = $pdo->prepare("
            SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, COLUMN_COMMENT
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = :dbname
            ORDER BY TABLE_NAME, ORDINAL_POSITION
        ");
        $colsStmt->execute(['dbname' => $dbName]);
        $allCols = $colsStmt->fetchAll(PDO::FETCH_ASSOC);

        $colsByTable = [];
        foreach ($allCols as $col) {
            $colsByTable[$col['TABLE_NAME']][] = $col;
        }

        // 3. Obtener Claves Foráneas
        $fkStmt = $pdo->prepare("
            SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = :dbname AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $fkStmt->execute(['dbname' => $dbName]);
        $allFks = $fkStmt->fetchAll(PDO::FETCH_ASSOC);

        $fksByTable = [];
        foreach ($allFks as $fk) {
            $fksByTable[$fk['TABLE_NAME']][$fk['COLUMN_NAME']] = $fk['REFERENCED_TABLE_NAME'] . '.' . $fk['REFERENCED_COLUMN_NAME'];
        }

        $schemaLines = [];
        $schemaLines[] = "### ESQUEMA COMPLETO Y DINÁMICO DE LA BASE DE DATOS ($dbName):";
        $schemaLines[] = "Nota: El esquema se detecta automáticamente en tiempo real. No requiere mantenimiento manual.";

        foreach ($tables as $t) {
            $tName = $t['TABLE_NAME'];
            $tComment = !empty($t['TABLE_COMMENT']) ? " (" . $t['TABLE_COMMENT'] . ")" : "";
            $schemaLines[] = "\n- Tabla: `$tName`$tComment";

            if (isset($colsByTable[$tName])) {
                $colStrings = [];
                foreach ($colsByTable[$tName] as $col) {
                    $cName = $col['COLUMN_NAME'];
                    $cType = $col['DATA_TYPE'];
                    $cKey = $col['COLUMN_KEY'];
                    $str = "`$cName` ($cType)";
                    if ($cKey === 'PRI') {
                        $str .= " [PK]";
                    }
                    if (isset($fksByTable[$tName][$cName])) {
                        $str .= " [FK -> " . $fksByTable[$tName][$cName] . "]";
                    }
                    if (!empty($col['COLUMN_COMMENT'])) {
                        $str .= " -- " . $col['COLUMN_COMMENT'];
                    }
                    $colStrings[] = $str;
                }
                $schemaLines[] = "  Columnas: " . implode(', ', $colStrings);
            }
        }

        $formattedSchema = implode("\n", $schemaLines);

        $tmpDir = __DIR__ . '/../tmp';
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0777, true);
        }
        @file_put_contents($cacheFile, $formattedSchema);

        return $formattedSchema;
    } catch (Exception $e) {
        return "Error obteniendo esquema dinámico: " . $e->getMessage();
    }
}

$dynamicSchema = getDynamicDatabaseSchema($pdo);

$rawHistory = isset($data['history']) && is_array($data['history']) ? $data['history'] : [];

// 3. System prompt analítico detallado
$systemPrompt = "Eres el Analista Senior de Datos e Inteligencia de Información de 'Somos Sumapaz', la plataforma tecnológica y social de la Localidad de Sumapaz (Bogotá).

Tu propósito NO es ser un simple chatbot conversacional de soporte, sino un **ANALISTA DE INFORMACIÓN DE ALTO NIVEL** capaz de responder preguntas complejas cruzando automáticamente toda la información de la base de datos en tiempo real.

### OBJETIVO Y COMPORTAMIENTO ESPERADO:
1. **BÚSQUEDA PERMISIVA POR COINCIDENCIAS Y ACLARACIÓN DE RESULTADOS (CRÍTICO)**:
   - Cuando el usuario mencione nombres de personas (`nombre_completo`), veredas, productos, organizaciones, dificultades o categorías, **NUNCA utilices un signo de igualdad exacta '=' en SQL** (ej. NO hagas `WHERE nombre_completo = 'Juan'`).
   - Usa SIEMPRE búsquedas por coincidencias flexibles mediante `LIKE '%termino%'` o `UPPER(TRIM(columna)) LIKE UPPER(TRIM('%termino%'))` o separando términos de búsqueda.
   - Si la consulta retorna varios registros o coincidencias parciales (por ejemplo, buscar 'Perez' encuentra 'Juan Perez', 'Carlos Perez', etc.), DEBES indicar explícitamente en tu respuesta a cuál o cuáles te estás refiriendo o mostrar la lista de coincidencias encontradas para que el usuario tenga total claridad sobre la información analizada.

2. **RESPUESTAS EXTENSAS, EXHAUSTIVAS Y DE ALTO VALOR ('RESPUESTAS GRANDES Y BUENAS, NO CORTANTE')**:
   - Está **estrictamente prohibido dar respuestas cortas, secas o resumidas**. El usuario exige informes grandes, detallados, profundos y de gran calidad técnica.
   - Cada respuesta debe ser un informe completo estructurado en Markdown que incluya:
     - **Resumen Ejecutivo / Introducción**: Explicando qué datos se analizaron y qué intención se abordó.
     - **Aclaración de Coincidencias**: Listando los nombres, veredas u organizaciones encontradas que coincidieron con la búsqueda del usuario.
     - **Tablas Completas y Desgloses Numéricos**: Mostrando conteos, porcentajes, promedios y métricas sin recortar datos útiles.
     - **Análisis Cruzado Multidimensional**: Integrando tablas relacionales (`productores_sumapaz`, `caracterizacion_productor`, `pmapc_*`, `categorias`, `dificultades`, `visitas`, etc.).
     - **Patrones y Hallazgos Clave**: Explicando correlaciones, tendencias, diferencias (ej. jóvenes vs adultos, agrícola vs pecuario).
     - **Conclusiones y Recomendaciones Estratégicas**: Con recomendaciones aplicables para el desarrollo local de Sumapaz.

3. **MANTENIMIENTO DEL CONTEXTO CONVERSACIONAL**:
   - Tienes acceso al historial de la conversación previa.
   - Si el usuario realiza preguntas de seguimiento relativas a lo dicho anteriormente (ej. '¿Y cuáles son sus necesidades?', '¿Dónde viven?', 'Muestra los detalles del primero', '¿Qué diferencia hay con el tema anterior?'), utiliza el contexto previo para inferir los nombres de productores, veredas o temas a los que se refiere.

4. **Uso de la Herramienta `ejecutar_consulta_sql`**:
   - Tienes acceso completo a la base de datos de la plataforma. Para responder conteos, agregaciones, estadísticas, listas o resúmenes, DEBES ejecutar consultas SQL de solo lectura (SELECT).
   - Puedes realizar MÚLTIPLES consultas en pasos sucesivos si necesitas cruzar datos complejos de distintas áreas (PMAPC, caracterización, avituallamiento, transporte, visitas ambientales, productos, etc.).

5. **Reglas de Cálculo Especiales**:
   - **Edad**: Calcula la edad desde `fecha_nacimiento` tomando 2026 como año de referencia. Ignora fechas vacías o por defecto como `1900-01-01`.
   - **Puntaje Ajustado**: `puntaje_ajustado = puntaje * (1 + 1.0 / (SELECT COUNT(*) FROM productores_sumapaz p2 WHERE UPPER(TRIM(p2.vereda)) = UPPER(TRIM(p.vereda))))`.

6. **AUTOCORRECCIÓN DE CONSULTAS Y NOMBRES EXACTOS DE TABLA**:
   - Revisa SIEMPRE el ESQUEMA DINÁMICO provisto abajo antes de escribir SQL. Usa los nombres exactos de tablas (ej. `productores_sumapaz`, `caracterizacion_productor`, `pmapc_registros`, `pmapc_comentarios`).
   - Si una consulta falla con un error de sintaxis o tabla inexistente, NO muestres el error técnico al usuario. Ejecuta inmediatamente un siguiente paso de herramienta corrigiendo la consulta SQL hasta obtener los resultados reales.

7. **Calidad y Rigor de la Respuesta**:
   - Genera respuestas claras, extensas, impecablemente estructuradas en Markdown, con títulos, tablas desglosadas y recomendaciones estratégicas.
   - Basate estrictamente en los datos devueltos por la base de datos. NUNCA inventes o alucines datos.
   - Si no existen datos suficientes para responder una pregunta, indícalo con total claridad y transparencia.

---
$dynamicSchema
";

// Inicializar e integrar el historial conversacional
$messages = [
    ['role' => 'system', 'content' => $systemPrompt]
];

foreach ($rawHistory as $hItem) {
    if (isset($hItem['role'], $hItem['content']) && is_string($hItem['content'])) {
        $r = strtolower(trim($hItem['role']));
        $c = trim($hItem['content']);
        if (($r === 'user' || $r === 'assistant') && !empty($c)) {
            $messages[] = [
                'role' => $r,
                'content' => $c
            ];
        }
    }
}

$lastIdx = count($messages) - 1;
if ($messages[$lastIdx]['role'] !== 'user' || $messages[$lastIdx]['content'] !== $userMessage) {
    $messages[] = [
        'role' => 'user',
        'content' => $userMessage
    ];
}

// Definición de la herramienta de SQL
$tools = [
    [
        'type' => 'function',
        'function' => [
            'name' => 'ejecutar_consulta_sql',
            'description' => 'Ejecuta una consulta SQL SELECT en la base de datos de Somos Sumapaz y devuelve el resultado JSON. Solo se permiten consultas SELECT de solo lectura.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'query' => [
                        'type' => 'string',
                        'description' => 'Consulta SQL SELECT a ejecutar. Debe ser sintácticamente válida para MySQL.'
                    ]
                ],
                'required' => ['query']
            ]
        ]
    ]
];

/**
 * Función segura para ejecutar consultas SELECT de solo lectura
 */
function runSecureQuery($pdo, $query) {
    $queryClean = trim($query);
    
    // Quitar punto y coma al final si existe
    $queryClean = rtrim($queryClean, ';');
    $queryClean = trim($queryClean);

    // 1. Permitir solo SELECT o CTE (WITH ... SELECT)
    if (stripos($queryClean, 'select') !== 0 && stripos($queryClean, 'with') !== 0) {
        throw new Exception("Solo se permiten consultas SQL de tipo SELECT de solo lectura.");
    }
    
    // 2. No permitir punto y coma interno (evitar multi-consultas)
    if (strpos($queryClean, ';') !== false) {
        throw new Exception("No se permiten múltiples sentencias (punto y coma ';') en la consulta.");
    }
    
    // 3. No permitir comentarios SQL que evadan filtros
    if (strpos($queryClean, '--') !== false || strpos($queryClean, '/*') !== false || strpos($queryClean, '#') !== false) {
        throw new Exception("No se permiten comentarios SQL en la consulta.");
    }
    
    // 4. Palabras clave prohibidas de modificación y archivos de sistema
    $forbidden = [
        'insert', 'update', 'delete', 'drop', 'alter', 'truncate', 
        'replace', 'create', 'rename', 'grant', 'revoke', 'load_file', 
        'outfile', 'dumpfile', 'mysql.user', 'sleep', 'benchmark'
    ];
    foreach ($forbidden as $word) {
        if (preg_match('/\b' . preg_quote($word, '/') . '\b/i', $queryClean)) {
            throw new Exception("Consulta rechazada. Contiene comando no permitido o palabra reservada: '$word'.");
        }
    }
    
    // Ejecutar con PDO
    $stmt = $pdo->prepare($queryClean);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Limitar máximo 30 filas devueltas a la IA por consulta y truncar textos extensos para optimizar TPM
    if (count($rows) > 30) {
        $rows = array_slice($rows, 0, 30);
    }
    foreach ($rows as &$row) {
        if (is_array($row)) {
            foreach ($row as $k => &$v) {
                if (is_string($v) && strlen($v) > 250) {
                    $v = (function_exists('mb_substr') ? mb_substr($v, 0, 250) : substr($v, 0, 250)) . '... [truncado]';
                }
            }
        }
    }
    
    return json_encode($rows, JSON_UNESCAPED_UNICODE);
}

/**
 * Función unificada para llamar a Gemini u OpenAI con fallback automático
 */
function callAIProvider($messages, $tools) {
    $provider = strtolower(getenv('AI_PROVIDER') ?: 'gemini');
    $geminiApiKey = getenv('GEMINI_API_KEY');
    $openAiApiKey = getenv('OPENAI_API_KEY');

    // Si está configurado Gemini o hay clave de Gemini
    if (($provider === 'gemini' || !empty($geminiApiKey)) && !empty($geminiApiKey)) {
        try {
            return callGeminiAPI($messages, $tools, $geminiApiKey);
        } catch (Exception $e) {
            // Si Gemini falla o supera la cuota (429), hacer fallback automático a OpenAI
            if (!empty($openAiApiKey)) {
                return callOpenAIAPI($messages, $tools, $openAiApiKey);
            }
            throw $e;
        }
    }

    if (!empty($openAiApiKey)) {
        return callOpenAIAPI($messages, $tools, $openAiApiKey);
    }

    throw new Exception("No hay API Key válida configurada para Gemini ni para OpenAI.");
}

/**
 * Ejecutor HTTP JSON seguro compatible con cualquier hosting (cURL con fallback a stream)
 */
function execHttpJsonPost($url, $postData, $apiKey) {
    $payload = json_encode($postData, JSON_UNESCAPED_UNICODE);
    
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new Exception("Error cURL de red: " . $err);
        }
        return json_decode($response, true);
    } else {
        $options = [
            'http' => [
                'header'  => "Content-Type: application/json\r\n" .
                             "Authorization: Bearer " . $apiKey . "\r\n",
                'method'  => 'POST',
                'content' => $payload,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ];
        $context  = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            throw new Exception("Error HTTP de red en el servidor.");
        }
        return json_decode($response, true);
    }
}

function callGeminiAPI($messages, $tools, $apiKey) {
    $url = 'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions';
    $postData = [
        'model' => 'gemini-2.0-flash',
        'messages' => $messages,
        'temperature' => 0.1,
        'max_tokens' => 3800
    ];
    if (!empty($tools)) {
        $postData['tools'] = $tools;
        $postData['tool_choice'] = 'auto';
    }

    $responseData = execHttpJsonPost($url, $postData, $apiKey);
    $errorObj = $responseData['error'] ?? (isset($responseData[0]['error']) ? $responseData[0]['error'] : null);
    if ($errorObj) {
        $code = isset($errorObj['code']) ? $errorObj['code'] : '';
        $msg = isset($errorObj['message']) ? $errorObj['message'] : 'Error desconocido';
        throw new Exception("Gemini Error [$code]: $msg");
    }

    return $responseData;
}

function callOpenAIAPI($messages, $tools, $apiKey) {
    $url = 'https://api.openai.com/v1/chat/completions';
    $postData = [
        'model' => 'gpt-4o-mini',
        'messages' => $messages,
        'temperature' => 0.1,
        'max_tokens' => 3800
    ];
    if (!empty($tools)) {
        $postData['tools'] = $tools;
        $postData['tool_choice'] = 'auto';
    }

    $responseData = execHttpJsonPost($url, $postData, $apiKey);
    if (isset($responseData['error'])) {
        throw new Exception("OpenAI Error: " . $responseData['error']['message']);
    }

    return $responseData;
}

/**
 * Poda inteligente del historial de mensajes para evitar desbordar límites TPM (Tokens Per Minute)
 */
function pruneMessagesContext($messages) {
    if (count($messages) <= 8) {
        return $messages;
    }
    $system = $messages[0];
    $user = $messages[1];

    $tail = array_slice($messages, -6);
    
    // Quitar cualquier mensaje 'tool' huérfano al inicio del corte
    while (!empty($tail) && isset($tail[0]['role']) && $tail[0]['role'] === 'tool') {
        array_shift($tail);
    }

    $pruned = [$system, $user];
    foreach ($tail as $msg) {
        if ($msg !== $system && $msg !== $user) {
            $pruned[] = $msg;
        }
    }
    return $pruned;
}

// Bucle principal de ejecución de Chat Completions con Function Calling
$loopCount = 0;
$maxLoops = 6;
$finished = false;
$botReply = '';

while ($loopCount < $maxLoops && !$finished) {
    $loopCount++;
    
    $prunedMessages = pruneMessagesContext($messages);
    $responseData = callAIProvider($prunedMessages, $tools);
    
    if (!isset($responseData['choices'][0]['message'])) {
        throw new Exception("Respuesta inválida de la API de OpenAI.");
    }
    
    $assistantMessage = $responseData['choices'][0]['message'];
    
    if (!empty($assistantMessage['tool_calls'])) {
        $messages[] = $assistantMessage;
        
        foreach ($assistantMessage['tool_calls'] as $toolCall) {
            $toolCallId = $toolCall['id'];
            $functionName = $toolCall['function']['name'];
            $arguments = json_decode($toolCall['function']['arguments'], true);
            $query = isset($arguments['query']) ? $arguments['query'] : '';
            
            $toolResult = '';
            if ($functionName === 'ejecutar_consulta_sql') {
                try {
                    $toolResult = runSecureQuery($pdo, $query);
                } catch (Exception $e) {
                    $toolResult = json_encode(['error' => $e->getMessage()]);
                }
            } else {
                $toolResult = json_encode(['error' => "Función desconocida: $functionName"]);
            }
            
            $messages[] = [
                'role' => 'tool',
                'tool_call_id' => $toolCallId,
                'name' => $functionName,
                'content' => $toolResult
            ];
        }
    } else {
        $botReply = $assistantMessage['content'];
        $finished = true;
    }
}

if (!$finished) {
    $botReply = isset($assistantMessage['content']) && !empty($assistantMessage['content']) 
        ? $assistantMessage['content'] 
        : 'He procesado las consultas de la base de datos, pero el análisis superó el límite de pasos. Por favor formula una pregunta más específica.';
}

echo json_encode(['reply' => trim($botReply)], JSON_UNESCAPED_UNICODE);
?>
