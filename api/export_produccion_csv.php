<?php
/**
 * API to export producer production data in CSV format separated by commas (,)
 */
require_once 'db_config.php';

// Prevent error output from messing up CSV output
ini_set('display_errors', '0');

try {
    $mode = isset($_GET['mode']) ? $_GET['mode'] : 'detallado'; // 'detallado' or 'resumen'

    $filename = ($mode === 'resumen') ? "productores_resumen_produccion.csv" : "produccion_por_productor.csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Output UTF-8 BOM for Microsoft Excel compatibility
    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');

    if ($mode === 'resumen') {
        // One row per producer, with products concatenated by comma
        $headers = [
            'ID Productor',
            'Nombre Completo',
            'Tipo Documento',
            'Número Documento',
            'Teléfono',
            'Correo Electrónico',
            'Vereda',
            'Cuenca',
            'Nombre Predio',
            'Productos Producidos'
        ];
        fputcsv($output, $headers, ',', '"', "\\");

        $sql = "
            SELECT 
                p.id,
                p.nombre_completo,
                p.tipo_documento,
                p.numero_documento,
                p.telefono,
                p.correo_electronico,
                p.vereda,
                p.cuenca,
                p.nombre_predio,
                GROUP_CONCAT(DISTINCT pp.nombre SEPARATOR ', ') AS productos_list
            FROM productores_sumapaz p
            LEFT JOIN productor_productos pp ON p.id = pp.productor_id
            GROUP BY p.id
            ORDER BY p.nombre_completo ASC
        ";

        $stmt = $pdo->query($sql);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'],
                $row['nombre_completo'],
                $row['tipo_documento'],
                $row['numero_documento'],
                $row['telefono'],
                $row['correo_electronico'],
                $row['vereda'],
                $row['cuenca'],
                $row['nombre_predio'],
                $row['productos_list'] ?: 'Sin registro de productos'
            ], ',', '"', "\\");
        }
    } else {
        // Detailed mode: Each row is a product of a producer
        $headers = [
            'ID Productor',
            'Nombre Completo Productor',
            'Tipo Documento',
            'Número Documento',
            'Teléfono',
            'Correo Electrónico',
            'Vereda',
            'Cuenca',
            'Nombre Predio',
            'Producto / Producción',
            'Categoría',
            'Volumen',
            'Unidad Volumen',
            'Frecuencia',
            'Presentación',
            'Calidad',
            'Precio',
            'Unidad Precio'
        ];
        fputcsv($output, $headers, ',', '"', "\\");

        $sql = "
            SELECT 
                p.id AS productor_id,
                p.nombre_completo,
                p.tipo_documento,
                p.numero_documento,
                p.telefono,
                p.correo_electronico,
                p.vereda,
                p.cuenca,
                p.nombre_predio,
                COALESCE(pp.nombre, 'Sin registro de productos') AS producto_nombre,
                COALESCE(pp.categoria, '') AS categoria,
                COALESCE(pp.volumen, '') AS volumen,
                COALESCE(pp.unidad_volumen, '') AS unidad_volumen,
                COALESCE(pp.frecuencia, '') AS frecuencia,
                COALESCE(pp.presentacion, '') AS presentacion,
                COALESCE(pp.calidad, '') AS calidad,
                COALESCE(pp.precio, '') AS precio,
                COALESCE(pp.unidad_precio, '') AS unidad_precio
            FROM productores_sumapaz p
            LEFT JOIN productor_productos pp ON p.id = pp.productor_id
            ORDER BY p.nombre_completo ASC, pp.nombre ASC
        ";

        $stmt = $pdo->query($sql);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['productor_id'],
                $row['nombre_completo'],
                $row['tipo_documento'],
                $row['numero_documento'],
                $row['telefono'],
                $row['correo_electronico'],
                $row['vereda'],
                $row['cuenca'],
                $row['nombre_predio'],
                $row['producto_nombre'],
                $row['categoria'],
                $row['volumen'],
                $row['unidad_volumen'],
                $row['frecuencia'],
                $row['presentacion'],
                $row['calidad'],
                $row['precio'],
                $row['unidad_precio']
            ], ',', '"', "\\");
        }
    }

    fclose($output);
    exit;
} catch (\Exception $e) {
    http_response_code(500);
    echo "Error al generar el archivo CSV: " . $e->getMessage();
}
