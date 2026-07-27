<?php
// Load formato_Cargue.json
$json_path = 'C:\\Users\\sotoc\\Downloads\\formato_Cargue.json';
$raw = file_get_contents($json_path);
$schema = json_decode($raw, true);

// Create sample instance based on properties in formato_Cargue.json
$sampleInstance = [
    "nombre_organizacion" => "Hotel Paramo de Sumapaz Test",
    "PMAPC_F01" => [
        "nombre_unidad_productiva" => "Hotel Paramo de Sumapaz",
        "persona_entrevistada" => "Eduin Parada",
        "tipo_actividad" => "Servicios turísticos y de alojamiento rural",
        "ubicacion_especifica" => "Vereda San Juan, localidad de Sumapaz",
        "producto_servicio_principal" => "Alojamiento por noche",
        "estado_actual" => "Negocio en marcha",
        "personas_vinculadas" => "1 persona de apoyo",
        "coordenadas" => "Lat 3.9, Lon -74.3",
        "descripcion_general" => "Unidad productiva de alojamiento rural en Sumapaz.",
        "observaciones_o_comentarios" => "Confirmar datos."
    ],
    "PMAPC_F02" => [
        "mision" => "Brindar alojamiento rural cómodo.",
        "vision" => "Consolidar el hotel como referente.",
        "valores" => "Servicio, respeto, hospitalidad."
    ],
    "PMAPC_F05" => [
        "perfiles_cliente_multiples_filas" => [
            [
                "tipo_actor" => "Cliente institucional",
                "perfil_que_busca" => "Contratistas",
                "ubicacion" => "Sumapaz",
                "necesidad" => "Permanecer cerca del trabajo",
                "frecuencia" => "Por proyecto",
                "criterio_compra" => "Ubicación y precio",
                "canal" => "Voz a voz"
            ]
        ]
    ]
];

$_POST['data'] = $sampleInstance;
$_SERVER['REQUEST_METHOD'] = 'POST';

ob_start();
require __DIR__ . '/../api/download_pmapc_pdf.php';
$html = ob_get_clean();

echo "Rendered HTML length: " . strlen($html) . " bytes\n";
echo "Contains PMAPC-F01? " . (strpos($html, 'FORMATO PMAPC-F01') !== false ? 'YES' : 'NO') . "\n";
echo "Contains PMAPC-F02? " . (strpos($html, 'FORMATO PMAPC-F02') !== false ? 'YES' : 'NO') . "\n";
echo "Contains PMAPC-F05? " . (strpos($html, 'FORMATO PMAPC-F05') !== false ? 'YES' : 'NO') . "\n";
echo "Contains Hotel Paramo de Sumapaz? " . (strpos($html, 'Hotel Paramo de Sumapaz') !== false ? 'YES' : 'NO') . "\n";
