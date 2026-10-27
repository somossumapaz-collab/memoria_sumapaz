<?php
// router.php - Enrutador para PHP Local Server
$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$path = rtrim($uri, '/');

// Normalizar la ruta para Windows/Linux
$base_dir = str_replace('\\', '/', __DIR__);
$path = str_replace('\\', '/', $path);

// Log para depuración (opcional, puedes borrarlo luego)
// file_put_contents(__DIR__ . '/router_log.txt', "URI: $uri | Path: $path\n", FILE_APPEND);

// Bloquear acceso directo a carpeta protegida de soportes y scripts CLI
if (strpos($path, '/storage/') === 0 || $path === '/storage' || $path === '/api/cli_migrate_despacho.php') {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Acceso directo no autorizado.']);
    return true;
}

// 1. Si es un archivo físico (imagen, css, etc.), dejar que PHP lo sirva
if ($uri !== '/' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    return false;
}

// 2. Intentar encontrar el archivo .html para rutas limpias
$target_html = (empty($path)) ? "/index.html" : $path . ".html";
$full_path = __DIR__ . $target_html;

if (file_exists($full_path)) {
    include $full_path;
    return true;
}

// 3. Fallback especial para index
if ($path === "" || $path === "/index") {
    include __DIR__ . "/index.html";
    return true;
}

// 4. Si nada funciona, dejar que el servidor maneje el 404
return false;
?>