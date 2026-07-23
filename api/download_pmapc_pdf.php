<?php
/**
 * API Endpoint: Export/Download Complete PMAPC as PDF / Printable Document
 * Supports GET ?id=X (from DB) and POST with live form JSON.
 */

require_once __DIR__ . '/db_config.php';

$productor_id = $_GET['id'] ?? ($_POST['productor_id'] ?? null);

$data = [];
$producer = [
    'nombre_completo' => 'Productor / Unidad Productiva',
    'vereda' => 'Sumapaz',
    'nombre_organizacion' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $postInput = json_decode($rawInput, true) ?: $_POST;
    if (!empty($postInput['data'])) {
        $data = is_array($postInput['data']) ? $postInput['data'] : (json_decode($postInput['data'], true) ?: []);
    } elseif (is_array($postInput) && !isset($postInput['data'])) {
        $data = $postInput;
    }
    if (!empty($postInput['productor_id'])) {
        $productor_id = $postInput['productor_id'];
    }
}

if ($productor_id) {
    // Fetch Producer Info from DB
    try {
        $stmtProd = $pdo->prepare("SELECT * FROM productores_sumapaz WHERE id = ?");
        $stmtProd->execute([$productor_id]);
        $fetchedProducer = $stmtProd->fetch();
        if ($fetchedProducer) {
            $producer = array_merge($producer, $fetchedProducer);
        }

        if (empty($data)) {
            $stmtPmapc = $pdo->prepare("SELECT data FROM pmapc_registros WHERE productor_id = ?");
            $stmtPmapc->execute([$productor_id]);
            $pmapcRow = $stmtPmapc->fetch();
            if ($pmapcRow && !empty($pmapcRow['data'])) {
                $data = json_decode($pmapcRow['data'], true) ?: [];
            }
        }
    } catch (Exception $e) {}
}

// Helper to safely display value or 'NaN'
function val($v, $default = 'NaN') {
    if ($v === null || $v === '' || (is_string($v) && trim($v) === '')) return $default;
    return htmlspecialchars(is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string)$v);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>PMAPC - <?php echo val($producer['nombre_completo']); ?></title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1A1A1A;
            line-height: 1.5;
            font-size: 12px;
            background: #fff;
            margin: 0;
            padding: 20px;
        }
        .header-bg {
            background-color: #2E7D32;
            color: #FFFFFF;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .header-bg h1 {
            margin: 0 0 5px 0;
            font-size: 20px;
        }
        .header-bg p {
            margin: 0;
            font-size: 13px;
            opacity: 0.9;
        }
        .card {
            border: 1px solid #E0E0E0;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        .card-title {
            font-size: 14px;
            font-weight: bold;
            color: #2E7D32;
            border-bottom: 2px solid #81C784;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .grid-2 {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .grid-item {
            flex: 1 1 45%;
            margin-bottom: 8px;
        }
        .label {
            font-weight: bold;
            color: #555;
            display: block;
            font-size: 11px;
            text-transform: uppercase;
        }
        .value {
            font-size: 12px;
            color: #000;
            word-wrap: break-word;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            font-size: 11px;
        }
        table th {
            background: #F1F8E9;
            color: #2E7D32;
            text-align: left;
            padding: 6px;
            border: 1px solid #C8E6C9;
        }
        table td {
            padding: 6px;
            border: 1px solid #E0E0E0;
            word-wrap: break-word;
        }
        .comments-box {
            background-color: #FFFDE7;
            border: 1px solid #FFF59D;
            padding: 12px;
            border-radius: 6px;
            margin-top: 15px;
            white-space: pre-wrap;
        }
        .no-print-bar {
            background: #333;
            color: #fff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: -20px -20px 20px -20px;
        }
        .btn-print {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
        }
        @media print {
            .no-print-bar { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

<div class="no-print-bar">
    <span>Documento Oficial PMAPC - Somos Sumapaz</span>
    <button class="btn-print" onclick="window.print()">Guardar como PDF / Imprimir</button>
</div>

<div class="header-bg">
    <h1>Plan de Manejo Ambiental, Productivo y Comercial (PMAPC)</h1>
    <p>Unidad Productiva: <strong><?php echo val($producer['nombre_organizacion'] ?? ($data['f01']['nombre_organizacion'] ?? ($data['f01_nombre_organizacion'] ?? 'Unidad Productiva'))); ?></strong></p>
    <p>Productor(a): <?php echo val($producer['nombre_completo']); ?> | Vereda: <?php echo val($producer['vereda']); ?></p>
</div>

<!-- MÓDULO 1 -->
<div class="card">
    <div class="card-title">Módulo 1: Identificación y Direccionamiento Estratégico</div>
    <div class="grid-2">
        <div class="grid-item">
            <span class="label">Nombre de la Organización</span>
            <span class="value"><?php echo val($data['f01']['nombre_organizacion'] ?? ($data['f01_nombre_organizacion'] ?? '')); ?></span>
        </div>
        <div class="grid-item">
            <span class="label">Tipo de Actividad</span>
            <span class="value"><?php echo val($data['f01']['tipo_actividad'] ?? ($data['f01_tipo_actividad'] ?? '')); ?></span>
        </div>
        <div class="grid-item">
            <span class="label">Ubicación Específica</span>
            <span class="value"><?php echo val($data['f01']['ubicacion'] ?? ($data['f01_ubicacion'] ?? '')); ?></span>
        </div>
        <div class="grid-item">
            <span class="label">Coordenadas</span>
            <span class="value"><?php echo val($data['f01']['coordenadas'] ?? ($data['f01_coordenadas'] ?? '')); ?></span>
        </div>
        <div class="grid-item">
            <span class="label">Producto Principal</span>
            <span class="value"><?php echo val($data['f01']['producto_principal'] ?? ($data['f01_producto_principal'] ?? '')); ?></span>
        </div>
        <div class="grid-item">
            <span class="label">Estado Actual</span>
            <span class="value"><?php echo val($data['f01']['estado_actual'] ?? ($data['f01_estado_actual'] ?? '')); ?></span>
        </div>
    </div>
    
    <div style="margin-top: 10px;">
        <span class="label">Misión</span>
        <div class="value"><?php echo val($data['f02']['mision'] ?? ($data['f02_mision'] ?? '')); ?></div>
    </div>
    <div style="margin-top: 8px;">
        <span class="label">Visión</span>
        <div class="value"><?php echo val($data['f02']['vision'] ?? ($data['f02_vision'] ?? '')); ?></div>
    </div>
    <div style="margin-top: 8px;">
        <span class="label">Valores</span>
        <div class="value"><?php echo val($data['f02']['valores'] ?? ($data['f02_valores'] ?? '')); ?></div>
    </div>
</div>

<!-- FODA -->
<div class="card">
    <div class="card-title">FODA Sistémico (Formatos F04)</div>
    <div class="grid-2">
        <div class="grid-item">
            <span class="label">Fortalezas</span>
            <div class="value"><?php echo val($data['f04']['fortalezas'] ?? ($data['f04_fortalezas'] ?? '')); ?></div>
        </div>
        <div class="grid-item">
            <span class="label">Oportunidades</span>
            <div class="value"><?php echo val($data['f04']['oportunidades'] ?? ($data['f04_oportunidades'] ?? '')); ?></div>
        </div>
        <div class="grid-item">
            <span class="label">Debilidades</span>
            <div class="value"><?php echo val($data['f04']['debilidades'] ?? ($data['f04_debilidades'] ?? '')); ?></div>
        </div>
        <div class="grid-item">
            <span class="label">Amenazas</span>
            <div class="value"><?php echo val($data['f04']['amenazas'] ?? ($data['f04_amenazas'] ?? '')); ?></div>
        </div>
    </div>
</div>

<!-- PRODUCTOS F09 -->
<?php if (!empty($data['f09']) && is_array($data['f09'])): ?>
<div class="card">
    <div class="card-title">Módulo 3: Ficha Técnica de Productos (F09)</div>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Descripción</th>
                <th>Unidad</th>
                <th>Insumos</th>
                <th>Presentación</th>
                <th>Diferencial</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data['f09'] as $p): ?>
            <tr>
                <td><?php echo val($p['producto'] ?? ''); ?></td>
                <td><?php echo val($p['descripcion'] ?? ''); ?></td>
                <td><?php echo val($p['unidad'] ?? ''); ?></td>
                <td><?php echo val($p['insumos'] ?? ''); ?></td>
                <td><?php echo val($p['presentacion'] ?? ''); ?></td>
                <td><?php echo val($p['diferencial'] ?? ''); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- INSUMOS F11 -->
<?php if (!empty($data['f11']) && is_array($data['f11'])): ?>
<div class="card">
    <div class="card-title">Insumos Requeridos (F11)</div>
    <table>
        <thead>
            <tr>
                <th>Insumo</th>
                <th>Cantidad</th>
                <th>Frecuencia</th>
                <th>Proveedor</th>
                <th>Toxicidad</th>
                <th>Manejo Sostenible</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data['f11'] as $ins): ?>
            <tr>
                <td><?php echo val($ins['insumo'] ?? ''); ?></td>
                <td><?php echo val($ins['cantidad'] ?? ''); ?></td>
                <td><?php echo val($ins['frecuencia'] ?? ''); ?></td>
                <td><?php echo val($ins['proveedor'] ?? ''); ?></td>
                <td><?php echo val($ins['toxicidad'] ?? ''); ?></td>
                <td><?php echo val($ins['manejo'] ?? ''); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- INVERSIONES F16 -->
<?php if (!empty($data['f16']) && is_array($data['f16'])): ?>
<div class="card">
    <div class="card-title">Módulo 6: Inversión Inicial Requerida (F16)</div>
    <table>
        <thead>
            <tr>
                <th>Descripción</th>
                <th>Valor Unitario</th>
                <th>Cantidad</th>
                <th>Total</th>
                <th>Fuente</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data['f16'] as $inv): ?>
            <tr>
                <td><?php echo val($inv['desc'] ?? ($inv['descripcion'] ?? '')); ?></td>
                <td><?php echo val($inv['valunit'] ?? ''); ?></td>
                <td><?php echo val($inv['cant'] ?? ''); ?></td>
                <td><?php echo val($inv['total'] ?? ''); ?></td>
                <td><?php echo val($inv['fuente'] ?? ''); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- COMENTARIOS Y OBSERVACIONES DEL PDF -->
<div class="card">
    <div class="card-title">Comentarios, Observaciones e Información Pendiente de Verificar</div>
    <div class="comments-box">
        <?php echo val($data['pdf_comentarios'] ?? ($data['comentarios'] ?? 'Sin comentarios registrados.')); ?>
    </div>
</div>

<script>
    // Trigger browser print to download as PDF immediately
    window.onload = function() {
        setTimeout(function() {
            window.print();
        }, 500);
    };
</script>

</body>
</html>
