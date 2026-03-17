<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = ltrim($path, '/');

// Check if the file exists when .html is appended
if (!empty($path) && file_exists(__DIR__ . '/' . $path . '.html')) {
    include __DIR__ . '/' . $path . '.html';
    exit;
}

// Fallback to exactly matching file request
if (file_exists(__DIR__ . '/' . $path)) {
    return false;    // serve the requested resource as-is.
} else {
    // Optionally return 404 or redirect to index
    http_response_code(404);
    echo "404 Not Found";
}
?>