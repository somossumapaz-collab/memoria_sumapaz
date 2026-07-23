<?php
$_GET['id'] = 1;
ob_start();
include __DIR__ . '/api/download_pmapc_pdf.php';
$html = ob_get_clean();
echo "PDF HTML GENERATED! LENGTH: " . strlen($html) . "\n";
echo "CONTAINS 'PMAPC': " . (strpos($html, 'PMAPC') !== false ? 'YES' : 'NO') . "\n";
?>
