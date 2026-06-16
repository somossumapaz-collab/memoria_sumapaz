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

// 1. Recibir el mensaje del frontend
$data = json_decode(file_get_contents('php://input'), true);
$userMessage = isset($data['message']) ? trim($data['message']) : '';

if (empty($userMessage)) {
    echo json_encode(['error' => 'Mensaje vacío.']);
    exit;
}

// 2. Conectarse a la base de datos
require_once 'db_config.php';
require_once __DIR__ . '/env_loader.php';

$apiKey = getenv('OPENAI_API_KEY');
if (empty($apiKey)) {
    echo json_encode(['error' => 'API Key de OpenAI no configurada en las variables de entorno.']);
    exit;
}

$url = 'https://api.openai.com/v1/chat/completions';

// 3. System prompt detallado con el esquema de la base de datos
$systemPrompt = "Eres el asistente virtual inteligente de 'Somos Sumapaz', una plataforma que conecta a productores campesinos locales de la Localidad de Sumapaz (Bogotá) con compradores y comerciantes. Tu tono es amable, respetuoso y muy útil.

Solo debes responder preguntas relacionadas con el campo, la agricultura, los productores locales, convocatorias de la plataforma, el territorio de Sumapaz y estadísticas de la base de datos. Si te preguntan algo fuera de este contexto, amablemente indica que tu propósito es ayudar con la plataforma Somos Sumapaz.

Tienes acceso completo a la base de datos de la plataforma en tiempo real. Para responder preguntas de conteos, listados, estadísticas y detalles específicos, DEBES usar la herramienta 'ejecutar_consulta_sql'. Formula consultas SQL SELECT precisas para obtener los datos necesarios.

### ESQUEMA DE LA BASE DE DATOS (MySQL):
1. **productores_sumapaz**: Datos básicos de productores registrados.
   - `id` (bigint, PK)
   - `nombre_completo` (varchar)
   - `tipo_documento` (varchar), `numero_documento` (varchar)
   - `fecha_nacimiento` (date) - Usa esto para la edad.
   - `telefono` (varchar), `correo_electronico` (varchar)
   - `vereda` (varchar) - Nombre de la vereda donde reside el productor.
   - `nombre_predio` (varchar) - Nombre de la finca/predio.
   - `mypime` (tinyint), `efectividad_2025` (tinyint), `panaca` (tinyint), `ferias` (tinyint) - Indicadores booleanos (1 o 0).
   - `cuenca` (varchar) - Cuenca hidrográfica (ej. Rio Blanco, Rio Sumapaz).

2. **caracterizacion_productor**: Formulario de caracterización técnica y social, y puntajes del productor.
   - `productor_id` (bigint, FK a productores_sumapaz.id)
   - `fecha_caracterizacion` (date)
   - `coordenadas` (text) - Ubicación GPS.
   - `tipo_organizacion` (varchar), `extension_predio` (varchar), `tiempo_implementacion` (varchar) (meses en operación).
   - `numero_personas` (int), `usa_abonos` (varchar), `sistemas_asociados` (text), `sistema_diferenciado` (varchar).
   - `puntaje` (int) - Puntaje general de la caracterización (0 a 100).
   - Subpuntajes: `puntaje_social`, `puntaje_organizacional`, `puntaje_productivo`, `puntaje_comercial`, `puntaje_ambiental`, `puntaje_impacto` (todos int).

3. **categorias_productivas**: Categorías productivas disponibles.
   - `id` (bigint, PK), `tipo` (varchar), `nombre` (text)
4. **productor_categoria**: Relación N:M de productor y categoría.
   - `productor_id` (bigint, FK), `categoria_id` (bigint, FK)

5. **productor_productos**: Productos ofertados por productores.
   - `productor_id` (bigint, FK), `nombre` (varchar), `volumen` (decimal), `unidad_volumen` (varchar), `frecuencia` (varchar), `presentacion` (varchar), `calidad` (varchar), `precio` (decimal), `unidad_precio` (varchar), `categoria` (varchar)

6. **transporte_viajes**: Registro de viajes de logística.
   - `id` (bigint, PK)
   - `fecha_hora` (timestamp), `funcionario` (varchar), `telefono` (varchar), `email` (varchar), `proposito` (varchar), `proyecto_nombre` (text), `origen` (varchar), `destino_final` (varchar), `actividad` (text), `meta` (text)
7. **transporte_recorridos**: Paradas y rutas asociadas a cada viaje.
   - `viaje_id` (bigint, FK a transporte_viajes.id), `orden` (int), `vereda` (varchar), `es_parada` (tinyint)
8. **transporte_vehiculos**: Vehículos de la flota de transporte.
   - `id` (bigint, PK), `tipo_transporte` (varchar), `tipo_vehiculo` (varchar), `placa` (varchar)
9. **transporte_viaje_vehiculo**: Relación de viajes y vehículos.
   - `viaje_id` (bigint, FK), `vehiculo_id` (bigint, FK)

10. **proveedores_avituallamiento**: Proveedores inscritos para el módulo de avituallamiento.
    - `id` (int, PK), `nombre_completo` (varchar), `numero_documento` (varchar), `fecha_nacimiento` (date), `telefono` (varchar), `correo_electronico` (varchar), `vereda` (varchar), `nombre_predio` (varchar)

11. **pmapc_registros**: Planes de Manejo Ambiental y Productivo (PMAPC).
    - `productor_id` (bigint, FK, Unique), `data` (longtext) - Contiene los datos estructurados en formato JSON.

12. **veredas_coordenadas**: Ubicación de veredas de Sumapaz.
    - `scanombre` (varchar) - Nombre de la vereda, `lat` (double), `lon` (double)

13. Tablas secundarias:
    - `certificaciones` y `productor_certificacion` (certificaciones del productor)
    - `dificultades` y `productor_dificultad` (dificultades del productor)
    - `canales_venta` y `productor_canal` (canales de venta del productor)
    - `financiamiento` y `productor_financiamiento` (financiamiento del productor)
    - `grupos_poblacionales` y `productor_grupo` (grupos poblacionales a los que pertenece)

### REGLAS DE CÁLCULO DE EDAD:
- Calcula la edad a partir de `fecha_nacimiento` usando el año 2026 como base.
- IMPORTANTE: Ignora o descarta fechas ficticias por defecto como `1900-01-01` o vacías. No las incluyas en promedios o conteos por rangos de edad.

### REGLA DEL PUNTAJE AJUSTADO:
- La fórmula del puntaje ajustado es:
  `puntaje_ajustado = puntaje * (1 + 1 / cantidad_personas_vereda)`
- Donde `cantidad_personas_vereda` es el número total de productores registrados en la misma vereda del productor.
- Para calcular esto de forma dinámica en SQL, debes agrupar y contar por vereda. Ejemplo de cálculo:
  `SELECT p.nombre_completo, p.vereda, cp.puntaje, cp.puntaje * (1 + 1.0 / (SELECT COUNT(*) FROM productores_sumapaz p2 WHERE UPPER(TRIM(p2.vereda)) = UPPER(TRIM(p.vereda)))) as puntaje_ajustado FROM productores_sumapaz p JOIN caracterizacion_productor cp ON p.id = cp.productor_id`

### REGLAS DE SEGURIDAD SQL Y MEJORES PRÁCTICAS:
- Escribe consultas SQL SELECT legibles y correctas.
- Para búsquedas por vereda, normaliza la comparación de cadenas usando `UPPER(TRIM(columna))` y tratando tildes o usando `LIKE '%nombre%'` para evitar fallos (ej. Concepción y Concepcion).
- Limita siempre los resultados para no sobrecargar el sistema (agrega `LIMIT 100` si es necesario).
- Si la consulta SQL falla o devuelve un error, corrige la consulta y vuelve a intentar.
- IMPORTANTE: No inventes datos. Si no hay registros que coincidan, informa amablemente al usuario.
";

// Inicializar el historial de mensajes
$messages = [
    ['role' => 'system', 'content' => $systemPrompt],
    ['role' => 'user', 'content' => $userMessage]
];

// Definición de las herramientas
$tools = [
    [
        'type' => 'function',
        'function' => [
            'name' => 'ejecutar_consulta_sql',
            'description' => 'Ejecuta una consulta SQL SELECT en la base de datos de Somos Sumapaz y devuelve el resultado JSON. Solo se permiten consultas de tipo SELECT de solo lectura.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'query' => [
                        'type' => 'string',
                        'description' => 'Consulta SQL SELECT a ejecutar. Debe ser válida para MySQL.'
                    ]
                ],
                'required' => ['query']
            ]
        ]
    ]
];

/**
 * Función segura para ejecutar consultas SELECT en la base de datos
 */
function runSecureQuery($pdo, $query) {
    $queryClean = trim($query);
    
    // 1. Debe iniciar con SELECT
    if (stripos($queryClean, 'select') !== 0) {
        throw new Exception("Solo se permiten consultas SQL de tipo SELECT de solo lectura.");
    }
    
    // 2. No permitir punto y coma (para evitar multi-consultas inyectadas)
    if (strpos($queryClean, ';') !== false) {
        throw new Exception("No se permiten múltiples sentencias (punto y coma ';') en la consulta.");
    }
    
    // 3. No permitir comentarios SQL que puedan evadir filtros
    if (strpos($queryClean, '--') !== false || strpos($queryClean, '/*') !== false || strpos($queryClean, '#') !== false) {
        throw new Exception("No se permiten comentarios SQL en la consulta.");
    }
    
    // 4. Palabras clave prohibidas (modificación, metadata del sistema, etc.)
    $forbidden = [
        'insert', 'update', 'delete', 'drop', 'alter', 'truncate', 
        'replace', 'create', 'rename', 'grant', 'revoke', 'load_file', 
        'outfile', 'dumpfile', 'information_schema', 'mysql.user', 'pg_',
        'usuarios', 'password'
    ];
    foreach ($forbidden as $word) {
        if (stripos($queryClean, $word) !== false) {
            throw new Exception("Consulta no permitida. Contiene palabra prohibida o sensible: '$word'.");
        }
    }
    
    // Ejecutar con PDO de manera segura
    $stmt = $pdo->prepare($queryClean);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Limitar filas a un máximo razonable para evitar excesos de consumo de memoria y tokens
    if (count($rows) > 50) {
        $rows = array_slice($rows, 0, 50);
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
        'max_tokens' => 500
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

// Bucle principal de ejecución del Chat Completions
$loopCount = 0;
$maxLoops = 3;
$finished = false;
$botReply = '';

while ($loopCount < $maxLoops && !$finished) {
    $loopCount++;
    
    // Llamar a la API de OpenAI
    $responseData = callOpenAI($messages, $tools, $apiKey, $url);
    
    if (!isset($responseData['choices'][0]['message'])) {
        throw new Exception("Respuesta inválida o vacía de la API de OpenAI.");
    }
    
    $assistantMessage = $responseData['choices'][0]['message'];
    
    if (!empty($assistantMessage['tool_calls'])) {
        // Agregar el mensaje del asistente (que indica que quiere ejecutar herramientas)
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
            
            // Agregar la respuesta de la herramienta al historial
            $messages[] = [
                'role' => 'tool',
                'tool_call_id' => $toolCallId,
                'name' => $functionName,
                'content' => $toolResult
            ];
        }
    } else {
        // Si no hay tool_calls, hemos obtenido la respuesta final en formato texto
        $botReply = $assistantMessage['content'];
        $finished = true;
    }
}

if (!$finished) {
    // Si agotamos los ciclos y no hay respuesta de texto limpia, usar el último content o un mensaje de error
    $botReply = isset($assistantMessage['content']) && !empty($assistantMessage['content']) 
        ? $assistantMessage['content'] 
        : 'Lo siento, he realizado demasiadas consultas a la base de datos y no he podido formular una respuesta definitiva. Por favor, sé más específico.';
}

echo json_encode(['reply' => trim($botReply)], JSON_UNESCAPED_UNICODE);
?>
