<?php
$dir = __DIR__ . '/..';
$htmlFiles = glob($dir . '/*.html');

$cacheMeta = "\n    <meta http-equiv=\"Cache-Control\" content=\"no-cache, no-store, must-revalidate\" />\n    <meta http-equiv=\"Pragma\" content=\"no-cache\" />\n    <meta http-equiv=\"Expires\" content=\"0\" />";

foreach ($htmlFiles as $file) {
    $content = file_get_contents($file);
    
    // Check if Cache-Control meta already exists
    if (strpos($content, 'http-equiv="Cache-Control"') !== false || strpos($content, 'http-equiv=\'Cache-Control\'') !== false) {
        echo "File already has cache control meta: " . basename($file) . "\n";
        continue;
    }
    
    // Insert after <head> or after viewport meta
    $pos = strpos($content, '<head>');
    if ($pos !== false) {
        $newContent = substr_replace($content, '<head>' . $cacheMeta, $pos, 6);
        file_put_contents($file, $newContent);
        echo "Injected cache control meta into: " . basename($file) . "\n";
    } else {
        echo "Could not find <head> in: " . basename($file) . "\n";
    }
}
echo "Done.\n";
