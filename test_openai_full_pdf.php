<?php
$_SERVER['REQUEST_METHOD'] = 'POST';

$pdfPath = 'C:\\Users\\sotoc\\Downloads\\PMAPC Johana Gutierrez.pdf';
$jsonData = json_encode(['pdf_path' => $pdfPath]);
file_put_contents(__DIR__ . '/php_input_mock.json', $jsonData);

ob_start();
include __DIR__ . '/api/analyze_pdf.php';
$output = ob_get_clean();

@unlink(__DIR__ . '/php_input_mock.json');

$json = json_decode($output, true);
echo "SUCCESS: " . ($json['success'] ? 'YES' : 'NO') . "\n";
if (isset($json['data'])) {
    echo "KEYS RETURNED BY AI & PARSER:\n";
    foreach ($json['data'] as $k => $v) {
        if (is_array($v)) {
            echo "  - $k (Array len: " . count($v) . ")\n";
        } else {
            echo "  - $k: " . substr((string)$v, 0, 80) . "\n";
        }
    }
} else {
    echo "ERROR: " . ($json['error'] ?? 'Unknown error') . "\n";
}
?>
