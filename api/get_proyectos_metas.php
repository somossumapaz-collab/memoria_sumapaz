<?php
/**
 * API: Proyectos y Metas de Sumapaz
 * Retorna el catálogo oficial de proyectos y metas del territorio.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=3600');

$jsonPath = dirname(__DIR__) . '/assets/proyectos_metas_sumapaz.json';

if (!file_exists($jsonPath)) {
    $fallbackPath = 'C:/Users/sotoc/Downloads/proyectos_metas_sumapaz.json';
    if (file_exists($fallbackPath)) {
        $jsonPath = $fallbackPath;
    }
}

if (file_exists($jsonPath)) {
    echo file_get_contents($jsonPath);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'No se encontró el archivo de proyectos y metas']);
}