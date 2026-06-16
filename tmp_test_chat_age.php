<?php
$_POST['message'] = '¿Qué productores tienen más de 70 años de edad?';
$userMessage = '¿Qué productores tienen más de 70 años de edad?';

require 'api/db_config.php';

try {
    $sql = "
        SELECT 
            p.nombre_completo,
            p.vereda,
            p.cuenca,
            p.nombre_predio,
            p.fecha_nacimiento,
            cp.coordenadas,
            GROUP_CONCAT(DISTINCT cat.nombre SEPARATOR ', ') as categorias,
            GROUP_CONCAT(DISTINCT pp.nombre SEPARATOR ', ') as productos_ofertados
        FROM productores_sumapaz p
        LEFT JOIN caracterizacion_productor cp ON p.id = cp.productor_id
        LEFT JOIN productor_categoria pcat ON pcat.productor_id = p.id
        LEFT JOIN categorias_productivas cat ON cat.id = pcat.categoria_id
        LEFT JOIN productor_productos pp ON pp.productor_id = p.id
        GROUP BY p.id
        ORDER BY p.nombre_completo ASC
    ";

    $stmt = $pdo->query($sql);
    $productores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $dbContext = "INFORMACIÓN EN TIEMPO REAL DE LA BASE DE DATOS DE PRODUCTORES:\n";
    $dbContext .= "Total Inscritos: " . count($productores) . "\n";
    $dbContext .= "Listado de Productores (Formato: Nombre | Vereda | Cuenca | Predio | Coordenadas | Edad en 2026 | Categorías | Productos):\n";
    
    foreach ($productores as $p) {
        $coords = !empty($p['coordenadas']) ? $p['coordenadas'] : 'Sin Coordenadas';
        
        // Calcular la edad exacta en PHP para evitar errores de cálculo en la IA
        $edad = 'No registrada';
        if (!empty($p['fecha_nacimiento']) && $p['fecha_nacimiento'] !== '1900-01-01') {
            $birthYear = intval(substr($p['fecha_nacimiento'], 0, 4));
            if ($birthYear > 0) {
                $edad = 2026 - $birthYear;
            }
        }
        
        $cats = !empty($p['categorias']) ? $p['categorias'] : 'Ninguna';
        $prods = !empty($p['productos_ofertados']) ? $p['productos_ofertados'] : 'Ninguno';
        $dbContext .= "- {$p['nombre_completo']} | {$p['vereda']} | {$p['cuenca']} | {$p['nombre_predio']} | {$coords} | {$edad} | {$cats} | {$prods}\n";
    }

} catch (\PDOException $e) {
    $dbContext = "Error: " . $e->getMessage();
}

require_once 'api/env_loader.php';
$apiKey = getenv('OPENAI_API_KEY');
$url = 'https://api.openai.com/v1/chat/completions';

$systemPrompt = "Eres el asistente virtual de 'Somos Sumapaz', una plataforma que conecta a productores campesinos locales de la Localidad de Sumapaz (Bogotá) con compradores y comerciantes. Tu tono es amable, respetuoso y muy útil.

Solo debes responder preguntas relacionadas con el campo, la agricultura, los productores locales, convocatorias de la plataforma, el territorio de Sumapaz y estadísticas de la base de datos. Si te preguntan algo fuera de este contexto, amablemente indica que tu propósito es ayudar con la plataforma Somos Sumapaz.

Tienes acceso completo a los datos reales y actuales de la base de datos de la plataforma. Úsalos para responder preguntas del usuario como conteos, veredas, categorías, productos, ubicación geográfica (coordenadas) y edad.

REGLAS DE EDAD:
- La edad de cada productor ya está precalculada en el listado adjunto (columna 'Edad en 2026'). Úsala directamente.
- Si la edad dice 'No registrada', significa que el productor no tiene registrada su fecha de nacimiento real o es una fecha ficticia (como 1900-01-01). No los incluyas en conteos de edad, rangos (por ejemplo, 'mayores de X años'), ni promedios de edad.
Sé preciso con los números y los nombres. Mantén tus respuestas amigables, concisas y fáciles de leer.

$dbContext";

$messages = [
    ['role' => 'system', 'content' => $systemPrompt],
    ['role' => 'user', 'content' => $userMessage]
];

$postData = [
    'model' => 'gpt-4o-mini',
    'messages' => $messages,
    'temperature' => 0.3,
    'max_tokens' => 300
];

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

echo $response;
?>
