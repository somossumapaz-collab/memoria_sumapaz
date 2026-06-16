<?php
$url = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
$dest = __DIR__ . '/../assets/xlsx.full.min.js';

echo "Downloading SheetJS from: $url\n";
$content = file_get_contents($url);
if ($content === false) {
    echo "Error downloading from URL.\n";
    exit(1);
}

$bytes = file_put_contents($dest, $content);
if ($bytes === false) {
    echo "Error saving to destination: $dest\n";
    exit(1);
}

echo "Successfully saved $bytes bytes to $dest\n";
