<?php
// router.php - Enrutador para PHP Local Server simulando .htaccess
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$ext = pathinfo($path, PATHINFO_EXTENSION);

header("X-Router-Debug-Path: " . $path);
header("X-Router-Debug-Ext: " . $ext);

if ($ext && file_exists($_SERVER["DOCUMENT_ROOT"] . $path)) {
    return false;
}

$htmlFile = $_SERVER["DOCUMENT_ROOT"] . rtrim($path, '/') . ".html";
header("X-Router-Debug-HtmlFile: " . $htmlFile);

if (file_exists($htmlFile)) {
    header("X-Router-Debug-Match: YES");
    include $htmlFile;
    return true;
} else {
    header("X-Router-Debug-Match: NO");
}

if ($path === '/' || $path === '/index') {
    include $_SERVER["DOCUMENT_ROOT"] . "/index.html";
    return true;
}

return false;
?>