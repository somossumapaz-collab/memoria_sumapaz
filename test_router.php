<?php
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$htmlFile = $_SERVER["DOCUMENT_ROOT"] . rtrim($path, '/') . ".html";
echo "DOCUMENT_ROOT: " . $_SERVER["DOCUMENT_ROOT"] . "\n";
echo "PATH: " . $path . "\n";
echo "HTML_FILE: " . $htmlFile . "\n";
echo "EXISTS: " . (file_exists($htmlFile) ? "Yes" : "No") . "\n";
?>
