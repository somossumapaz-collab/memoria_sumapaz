<?php
/**
 * API Endpoint: Download ZIP containing all complete PMAPC PDFs
 */

require_once __DIR__ . '/db_config.php';

$pdf_dir = __DIR__ . '/../PMAPCs_PDF';
$zip_file = $pdf_dir . '/PMAPCs_Completos_Somos_Sumapaz.zip';

// If ZIP doesn't exist yet or is empty, create a dynamic ZIP of existing files
if (!file_exists($zip_file) || filesize($zip_file) === 0) {
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $files = glob($pdf_dir . '/*.pdf');
            foreach ($files as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();
        }
    }
}

if (!file_exists($zip_file) || filesize($zip_file) === 0) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'El paquete ZIP de PMAPCs aún se está generando o no se encontraron archivos PDF.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Download headers
header('Content-Description: File Transfer');
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="PMAPCs_Completos_Somos_Sumapaz.zip"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($zip_file));
readfile($zip_file);
exit;
?>
