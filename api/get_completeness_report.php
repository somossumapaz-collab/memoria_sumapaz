<?php
require_once 'db_config.php';
header('Content-Type: application/json');

// Increase time limit for large datasets
set_time_limit(60);

try {
    // 1. Get counts for all detail tables in one go to avoid N+1 problem
    // This is much faster than doing subqueries in a loop
    
    $counts = [];
    
    // Categories count
    $stmt = $pdo->query("SELECT productor_id, COUNT(*) as total FROM productor_categoria GROUP BY productor_id");
    while ($row = $stmt->fetch()) { $counts[$row['productor_id']]['categorias'] = $row['total']; }
    
    // Products count
    $stmt = $pdo->query("SELECT productor_id, COUNT(*) as total FROM productor_productos GROUP BY productor_id");
    while ($row = $stmt->fetch()) { $counts[$row['productor_id']]['productos'] = $row['total']; }
    
    // Services count
    $stmt = $pdo->query("SELECT productor_id, COUNT(*) as total FROM productor_servicios GROUP BY productor_id");
    while ($row = $stmt->fetch()) { $counts[$row['productor_id']]['servicios'] = $row['total']; }

    // 2. Get main characterization data
    $stmt = $pdo->query("
        SELECT 
            p.id, 
            p.nombre_completo, 
            p.numero_documento,
            cp.tipo_organizacion,
            cp.extension_predio,
            cp.tiempo_implementacion,
            cp.tipo_tenencia,
            cp.numero_personas,
            cp.mano_obra,
            cp.tipo_proceso,
            cp.destino,
            cp.transporte,
            cp.forma_pago,
            cp.define_precio,
            cp.sistema_diferenciado,
            cp.descripcion,
            cp.en_tramite_bool,
            cp.en_tramite
        FROM productores_sumapaz p
        INNER JOIN caracterizacion_productor cp ON p.id = cp.productor_id
    ");
    
    $report = [];
    $isEmpty = function($val) {
        return $val === null || $val === '' || $val === 'Ninguno' || $val === 'Ninguna' || $val === 'Seleccione...';
    };

    while ($f = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $missing = [];
        $pid = $f['id'];

        // Organizational
        if ($isEmpty($f['tipo_organizacion'])) $missing[] = "Tipo organización";
        if ($isEmpty($f['extension_predio'])) $missing[] = "Extensión predio";
        if ($isEmpty($f['tiempo_implementacion'])) $missing[] = "Tiempo implementación";
        if ($isEmpty($f['tipo_tenencia'])) $missing[] = "Tipo tenencia";
        if ($isEmpty($f['numero_personas'])) $missing[] = "Número personas";
        
        // System
        if ($isEmpty($f['mano_obra'])) $missing[] = "Tipo mano de obra";
        if ($isEmpty($f['tipo_proceso'])) $missing[] = "Proceso productivo";
        
        if ($f['sistema_diferenciado'] == "1" && $isEmpty($f['descripcion'])) {
            $missing[] = "Descripción sistema diferenciado";
        }

        // Comercialización
        if ($isEmpty($f['destino'])) $missing[] = "Destino";
        if ($isEmpty($f['transporte'])) $missing[] = "Transporte";
        if ($isEmpty($f['forma_pago'])) $missing[] = "Forma de pago";
        if ($isEmpty($f['define_precio'])) $missing[] = "Fijación precio";
        
        if ($f['en_tramite_bool'] === "Sí" && $isEmpty($f['en_tramite'])) {
            $missing[] = "Permiso en trámite";
        }

        // Details from pre-fetched counts
        $c_cat = isset($counts[$pid]['categorias']) ? $counts[$pid]['categorias'] : 0;
        $c_prod = isset($counts[$pid]['productos']) ? $counts[$pid]['productos'] : 0;
        $c_serv = isset($counts[$pid]['servicios']) ? $counts[$pid]['servicios'] : 0;

        if ($c_cat == 0) $missing[] = "Categorías productivas";
        if ($c_prod == 0 && $c_serv == 0) {
            $missing[] = "Productos o Servicios ofertados";
        }

        $report[$f['numero_documento']] = [
            'nombre' => $f['nombre_completo'],
            'cedula' => $f['numero_documento'],
            'missing' => $missing,
            'is_complete' => count($missing) === 0
        ];
    }

    echo json_encode(['success' => true, 'data' => $report]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
