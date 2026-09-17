<?php
/**
 * API to export producer production data in native Excel (.xls) format
 */
require_once 'db_config.php';

ini_set('display_errors', '0');

try {
    $mode = isset($_GET['mode']) ? $_GET['mode'] : 'detallado'; // 'detallado' or 'resumen'

    $filename = ($mode === 'resumen') ? "productores_resumen_produccion.xls" : "produccion_por_productor.xls";

    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
    echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Produccion</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
    echo '<style>';
    echo '  body { font-family: Calibri, Arial, sans-serif; }';
    echo '  table { border-collapse: collapse; width: 100%; }';
    echo '  th { background-color: #2E7D32; color: #FFFFFF; font-weight: bold; border: 1px solid #1B5E20; padding: 8px; text-align: left; font-size: 11pt; }';
    echo '  td { border: 1px solid #D0D0D0; padding: 6px 8px; font-size: 10pt; vertical-align: middle; }';
    echo '  .title { font-size: 16pt; font-weight: bold; color: #2E7D32; padding: 10px 0 5px 0; }';
    echo '  .subtitle { font-size: 10pt; color: #555555; padding-bottom: 15px; }';
    echo '  .number { text-align: right; mso-number-format:"\#\,\#\#0\.00"; }';
    echo '  .currency { text-align: right; mso-number-format:"\$\#\,\#\#0"; }';
    echo '  .text { mso-number-format:"\@"; }';
    echo '  .alt-row { background-color: #F9FBF8; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';

    if ($mode === 'resumen') {
        echo '<div class="title">REPORTE RESUMIDO DE PRODUCCIÓN POR PRODUCTOR</div>';
        echo '<div class="subtitle">Somos Sumapaz - Localidad de Sumapaz | Generado: ' . date('d/m/Y H:i') . '</div>';
        echo '<table>';
        echo '<thead>';
        echo '<tr>';
        echo '<th>ID</th>';
        echo '<th>Productor</th>';
        echo '<th>Tipo Documento</th>';
        echo '<th>Número Documento</th>';
        echo '<th>Teléfono</th>';
        echo '<th>Correo Electrónico</th>';
        echo '<th>Vereda</th>';
        echo '<th>Cuenca</th>';
        echo '<th>Nombre Predio</th>';
        echo '<th>Productos Producidos</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

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
        $i = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $class = ($i++ % 2 === 1) ? ' class="alt-row"' : '';
            echo '<tr' . $class . '>';
            echo '<td>' . htmlspecialchars($row['id']) . '</td>';
            echo '<td><b>' . htmlspecialchars($row['nombre_completo']) . '</b></td>';
            echo '<td>' . htmlspecialchars($row['tipo_documento']) . '</td>';
            echo '<td class="text">' . htmlspecialchars($row['numero_documento']) . '</td>';
            echo '<td class="text">' . htmlspecialchars($row['telefono']) . '</td>';
            echo '<td>' . htmlspecialchars($row['correo_electronico']) . '</td>';
            echo '<td>' . htmlspecialchars($row['vereda']) . '</td>';
            echo '<td>' . htmlspecialchars($row['cuenca']) . '</td>';
            echo '<td>' . htmlspecialchars($row['nombre_predio']) . '</td>';
            echo '<td>' . htmlspecialchars($row['productos_list'] ?: 'Sin registro de productos') . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<div class="title">REPORTE DETALLADO DE PRODUCCIÓN POR PRODUCTOR</div>';
        echo '<div class="subtitle">Somos Sumapaz - Localidad de Sumapaz | Generado: ' . date('d/m/Y H:i') . '</div>';
        echo '<table>';
        echo '<thead>';
        echo '<tr>';
        echo '<th>ID Productor</th>';
        echo '<th>Productor</th>';
        echo '<th>Tipo Doc.</th>';
        echo '<th>Nro. Documento</th>';
        echo '<th>Teléfono</th>';
        echo '<th>Correo Electrónico</th>';
        echo '<th>Vereda</th>';
        echo '<th>Cuenca</th>';
        echo '<th>Nombre Predio</th>';
        echo '<th>Producto / Producción</th>';
        echo '<th>Categoría</th>';
        echo '<th>Volumen</th>';
        echo '<th>Unidad Vol.</th>';
        echo '<th>Frecuencia</th>';
        echo '<th>Presentación</th>';
        echo '<th>Calidad</th>';
        echo '<th>Precio</th>';
        echo '<th>Unidad Precio</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

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
        $i = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $class = ($i++ % 2 === 1) ? ' class="alt-row"' : '';
            echo '<tr' . $class . '>';
            echo '<td>' . htmlspecialchars($row['productor_id']) . '</td>';
            echo '<td><b>' . htmlspecialchars($row['nombre_completo']) . '</b></td>';
            echo '<td>' . htmlspecialchars($row['tipo_documento']) . '</td>';
            echo '<td class="text">' . htmlspecialchars($row['numero_documento']) . '</td>';
            echo '<td class="text">' . htmlspecialchars($row['telefono']) . '</td>';
            echo '<td>' . htmlspecialchars($row['correo_electronico']) . '</td>';
            echo '<td>' . htmlspecialchars($row['vereda']) . '</td>';
            echo '<td>' . htmlspecialchars($row['cuenca']) . '</td>';
            echo '<td>' . htmlspecialchars($row['nombre_predio']) . '</td>';
            echo '<td>' . htmlspecialchars($row['producto_nombre']) . '</td>';
            echo '<td>' . htmlspecialchars($row['categoria']) . '</td>';
            echo '<td class="number">' . ($row['volumen'] !== '' ? number_format((float)$row['volumen'], 2, '.', '') : '') . '</td>';
            echo '<td>' . htmlspecialchars($row['unidad_volumen']) . '</td>';
            echo '<td>' . htmlspecialchars($row['frecuencia']) . '</td>';
            echo '<td>' . htmlspecialchars($row['presentacion']) . '</td>';
            echo '<td>' . htmlspecialchars($row['calidad']) . '</td>';
            echo '<td class="currency">' . ($row['precio'] !== '' ? '$' . number_format((float)$row['precio'], 0, ',', '.') : '') . '</td>';
            echo '<td>' . htmlspecialchars($row['unidad_precio']) . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    }

    echo '</body>';
    echo '</html>';
    exit;
} catch (\Exception $e) {
    http_response_code(500);
    echo "Error al generar el archivo Excel: " . $e->getMessage();
}
