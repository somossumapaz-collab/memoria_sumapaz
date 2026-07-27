<?php
require_once __DIR__ . '/../api/db_config.php';

// Test submitting a record for producer ID 1 using the new formato_Cargue.json structure
$json_path = 'C:\\Users\\sotoc\\Downloads\\formato_Cargue.json';
$raw = file_get_contents($json_path);
$schema = json_decode($raw, true);

$testPayload = [
    'productor_id' => 1,
    'data' => [
        'nombre_organizacion' => 'Hotel Paramo de Sumapaz (Test Cargue)',
        'PMAPC_F01' => [
            'nombre_unidad_productiva' => 'Hotel Paramo de Sumapaz',
            'persona_entrevistada' => 'Eduin Parada',
            'tipo_actividad' => 'Servicios turísticos y de alojamiento rural',
            'ubicacion_especifica' => 'Vereda San Juan, localidad de Sumapaz',
            'producto_servicio_principal' => 'Alojamiento por noche',
            'estado_actual' => 'Negocio en marcha',
            'personas_vinculadas' => '1 persona de apoyo',
            'coordenadas' => 'Lat 3.9, Lon -74.3',
            'descripcion_general' => 'Unidad productiva de alojamiento rural en Sumapaz.',
            'observaciones_o_comentarios' => 'Prueba de cargue formato_Cargue.json'
        ],
        'PMAPC_F02' => [
            'mision' => 'Brindar alojamiento rural cómodo y respetuoso.',
            'vision' => 'Consolidar el hotel como referente de alojamiento.',
            'valores' => 'Servicio, respeto, hospitalidad.'
        ]
    ]
];

$ch = curl_init('http://localhost:8000/api/submit_pmapc.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testPayload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);

echo "Submit Response: " . $response . "\n";

// Check if saved in DB
$stmt = $pdo->prepare("SELECT COUNT(*) FROM pmapc_registros WHERE productor_id = 1");
$stmt->execute();
echo "Records in pmapc_registros for productor 1: " . $stmt->fetchColumn() . "\n";
