<?php
require_once __DIR__ . '/../api/db_config.php';

$tables = [
    'productores_sumapaz',
    'caracterizacion_productor',
    'categorias_productivas',
    'productor_categoria',
    'productor_productos',
    'pmapc_registros',
    'pmapc_estrategico',
    'pmapc_clientes',
    'pmapc_aliados',
    'pmapc_productos',
    'pmapc_equipos_bienes',
    'pmapc_insumos',
    'pmapc_costos_precios',
    'pmapc_ventas',
    'pmapc_inversiones',
    'pmapc_costos_fijos',
    'pmapc_economia_circular',
    'pmapc_plan_trabajo',
    'pmapc_comentarios',
    'dificultades',
    'productor_dificultad',
    'canales_venta',
    'productor_canal',
    'financiamiento',
    'productor_financiamiento',
    'grupos_poblacionales',
    'productor_grupo',
    'transporte_viajes',
    'proveedores_avituallamiento',
    'ambiental_visitas_agricolas',
    'ambiental_visitas_pecuarias'
];

echo "CONTEO DE REGISTROS POR TABLA:\n";
foreach ($tables as $t) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `$t`");
        $cnt = $stmt->fetchColumn();
        echo " - $t: $cnt registros\n";
    } catch (Exception $e) {
        echo " - $t: (Error o no existe)\n";
    }
}
?>
