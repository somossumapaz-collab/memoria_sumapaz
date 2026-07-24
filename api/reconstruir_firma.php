<?php
/**
 * API Endpoint to Reconstruct Signature Image
 * Example: reconstruir_firma.php?tipo=pecuaria&id=5&firma=profesional
 */

require_once __DIR__ . '/db_config.php';

$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'pecuaria';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$firmaKey = isset($_GET['firma']) ? $_GET['firma'] : 'profesional'; // profesional, operario, usuario

$table = ($tipo === 'agricola') ? 'ambiental_visitas_agricolas' : 'ambiental_visitas_pecuarias';
$col = 'firma_' . $firmaKey;

if (!in_array($col, ['firma_profesional', 'firma_operario', 'firma_usuario'])) {
    http_response_code(400);
    echo "Firma inválida especificada.";
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT $col FROM $table WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $base64Data = $stmt->fetchColumn();

    if (!$base64Data) {
        http_response_code(404);
        echo "Firma no encontrada.";
        exit;
    }

    // Limpiar prefijo Data URI si existe (ej. data:image/png;base64,...)
    if (strpos($base64Data, ',') !== false) {
        $parts = explode(',', $base64Data);
        $base64Data = $parts[1];
    }

    $imgBytes = base64_decode($base64Data);

    if (!$imgBytes) {
        http_response_code(500);
        echo "Error al decodificar la firma en base64.";
        exit;
    }

    header('Content-Type: image/png');
    header('Content-Length: ' . strlen($imgBytes));
    echo $imgBytes;
    exit;

} catch (\Exception $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}
?>
