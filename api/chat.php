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

$apiKey = getenv('OPENAI_API_KEY');
if (empty($apiKey)) {
    echo json_encode(['error' => 'API Key de OpenAI no configurada en las variables de entorno.']);
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

// 3. System prompt analítico detallado
$systemPrompt = "Eres el Analista Senior de Datos e Inteligencia de Información de 'Somos Sumapaz', la plataforma tecnológica y social de la Localidad de Sumapaz (Bogotá).

Tu propósito NO es ser un simple chatbot conversacional de soporte, sino un **ANALISTA DE INFORMACIÓN DE ALTO NIVEL** capaz de responder preguntas complejas cruzando automáticamente toda la información de la base de datos en tiempo real.

### OBJETIVO Y COMPORTAMIENTO ESPERADO:
1. **Comprensión e Identificación de Tablas**:
   - Comprende la intención del usuario e identifica todas las tablas relevantes.
   - NUNCA respondas leyendo únicamente una tabla aislada si existen relaciones o contextos adicionales en la base de datos.
   - Cruza automáticamente la información usando claves foráneas o campos comunes (por ejemplo, `productor_id`, `registro_id`, `vereda`, `categoria_id`, `viaje_id`).

2. **Uso de la Herramienta `ejecutar_consulta_sql`**:
   - Tienes acceso completo a la base de datos de la plataforma. Para responder conteos, agregaciones, estadísticas, listas o resúmenes, DEBES ejecutar consultas SQL de solo lectura (SELECT).
   - Puedes realizar MÚLTIPLES consultas en pasos sucesivos si necesitas cruzar datos complejos de distintas áreas (PMAPC, caracterización, avituallamiento, transporte, visitas ambientales, productos, etc.).
   - Para búsquedas por texto o nombres de veredas/organizaciones, normaliza con `UPPER(TRIM(columna))` o utiliza comparaciones permisivas con `LIKE '%texto%'` para evitar inconsistencias de tildes o mayúsculas.

3. **Respuestas Analíticas e Integradas**:
   - No te limites a devolver listas o registros sueltos. **Realiza análisis cuantitativos y cualitativos**:
     - Frecuencias y distribuciones (por vereda, rango de edad, tipo de productor, línea productiva).
     - Porcentajes y proporciones sobre los totales.
     - Promedios y comparaciones (ej. agrícolas vs pecuarios, jóvenes vs adultos, priorizados vs no priorizados).
     - Rankings de necesidades, problemáticas, actividades propuestas y recomendaciones comunes (especialmente de las tablas `pmapc_*`, `caracterizacion_productor`, `dificultades`, `canales_venta`, `financiamiento`).
     - Identificación de patrones ocultos o relaciones entre la caracterización socioeconómica y los planes PMAPC.
     - Diagnósticos y resúmenes ejecutivos del municipio.

4. **Reglas de Cálculo Especiales**:
   - **Edad**: Calcula la edad desde `fecha_nacimiento` tomando 2026 como año de referencia. Ignora fechas vacías o por defecto como `1900-01-01`.
   - **Puntaje Ajustado**: `puntaje_ajustado = puntaje * (1 + 1.0 / (SELECT COUNT(*) FROM productores_sumapaz p2 WHERE UPPER(TRIM(p2.vereda)) = UPPER(TRIM(p.vereda))))`.

5. **Calidad y Rigor de la Respuesta**:
   - Genera respuestas claras, estructuradas en Markdown, con títulos, secciones analíticas, tablas sintéticas o listas de hallazgos, y recomendaciones finales.
   - Basate estrictamente en los datos devueltos por la base de datos. NUNCA inventes o alucines datos.
   - Si no existen datos suficientes para responder una pregunta, indícalo con total claridad y transparencia.

---
$dynamicSchema
";

// Inicializar el historial de mensajes
$messages = [
    ['role' => 'system', 'content' => $systemPrompt],
    ['role' => 'user', 'content' => $userMessage]
];

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
    
    // Limitar máximo 100 filas devueltas a la IA por consulta
    if (count($rows) > 100) {
        $rows = array_slice($rows, 0, 100);
    }
    
    return json_encode($rows, JSON_UNESCAPED_UNICODE);
}

/**
 * Función para llamar a la API de OpenAI
 */
function callOpenAI($messages, $tools, $apiKey, $url) {
    $postData = [
        'model' => 'gpt-4o-mini',
        'messages' => $messages,
        'temperature' => 0.1,
        'max_tokens' => 2500
    ];
    if (!empty($tools)) {
        $postData['tools'] = $tools;
        $postData['tool_choice'] = 'auto';
    }
    
    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n" .
                         "Authorization: Bearer " . $apiKey . "\r\n",
            'method'  => 'POST',
            'content' => json_encode($postData),
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ];
    
    $context  = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        throw new Exception("Error al conectar con el servicio de IA de OpenAI.");
    }
    
    $responseData = json_decode($response, true);
    if (isset($responseData['error'])) {
        throw new Exception("OpenAI Error: " . $responseData['error']['message']);
    }
    
    return $responseData;
}

// Bucle principal de ejecución de Chat Completions con Function Calling
$loopCount = 0;
$maxLoops = 6;
$finished = false;
$botReply = '';

while ($loopCount < $maxLoops && !$finished) {
    $loopCount++;
    
    $responseData = callOpenAI($messages, $tools, $apiKey, $url);
    
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
