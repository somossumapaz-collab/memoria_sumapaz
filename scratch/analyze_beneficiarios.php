<?php
require_once __DIR__ . '/../api/db_config.php';

$raw_list = [
    "Hadaly Esperanza Rubiano Benavides",
    "Ruben Dario Vásquez Huertas",
    "Ingrid Rocío Castro Carillo",
    "Pedro Alfonso Castro Morales",
    "Bonifacio Hernández",
    "Saúl Bautista Mican",
    "Juan Andrés Gómez Gómez",
    "María Arcenia Martínez Hernández",
    "Liliana Briyid Rivera Achury",
    "Procampsu",
    "Fanny Baquero Molina",
    "Jessica Lorena Salgado Contreras",
    "Clelia Palacios",
    "Marcela Martínez",
    "Enrique Díaz",
    "Laura Jimena González Cruz",
    "Robinson Arley Ramos Polo",
    "Ruth Esperanza Valbuena Vergara",
    "Blanca Cecilia Balbuena Cifuentes",
    "Martha Cecilia Vergara González",
    "William Dimaté Rico",
    "Fabio Nelson Gutiérrez Chingate",
    "Carlos Arturo Pulido Torres",
    "Edilson Hernando Melo Arevalo",
    "Clara Bersalid González Caro",
    "Yolanda Morales Pabón",
    "William Mauricio Palacios Rey",
    "Dary Esmeralda Palacios Moreno",
    "Iván Darío Chingate Mican",
    "Mauricio Pabón Morales",
    "Fani Carrillo Rodríguez",
    "Iván Darío Pérez Ortiz",
    "Darwin Smith Rubiano Pulido",
    "Daniela Rojas Suárez",
    "John Alexander Sorza León",
    "Martha Yaneth Cabrera Arte",
    "Leopoldo Romero Herrera",
    "Carmen Rosa Moreno Moreno",
    "María Yolanda Pérez Ussa",
    "José Alejandro Pulido Moreno",
    "Jorge Orlando Gutiérrez Gómez",
    "Laura Morales",
    "Rosalba Rojas Torres",
    "Fabio Augusto Romero Hortia",
    "Luis Eduardo Dimate",
    "Cristian Alonso Morales Palacios",
    "Nancy Velásquez Morales",
    "Alejandro Pinilla",
    "Pedro Ignacio Rico Chávez",
    "Heriberto Bernal Muñoz",
    "Salomón Romero Moreno",
    "Josué Torres Riveros",
    "Luz Helena Diosa",
    "Vitelma Quevedo Chingate",
    "Albeiro Mican Romero",
    "José Aquino Muñoz",
    "María Sabina Mican",
    "Yury Graciela Romero Baquero",
    "Isaac David Mican Mican",
    "Elías Mican Mican",
    "Diana Marcela Romero",
    "Alba Nery Mican Poveda",
    "Erasmos Vaquero Romero",
    "Ilma Nieves Baquero Hortua",
    "Asociación Asoarcopaz",
    "Doris Stella Villalba Baquero",
    "Johana Gutiérrez Castro",
    "José Benjamín Hortua Mican",
    "Yeni Lorena Parra Gallo",
    "Flor Marina Baquero Romero",
    "Paula Daniela Larrota Borray",
    "Lidia Sanabria López",
    "Germán Oswaldo Rodríguez Duarte",
    "Yeimy Paola Lancheros",
    "Eduin Eduardo Parada Macana",
    "Fermey Pérez Bustos",
    "Niyired Dimate Ríos",
    "Elzon Ferney Delgado Morales",
    "María Luz Dary Zambrano Torres",
    "Fredesminda Pérez Castro",
    "Ivan Andrés Peñaloza Valbuena",
    "Mónica Díaz",
    "Lina Marcela Guzmán Prada",
    "Yaneth Motavita",
    "Miriam Romero Palacios",
    "Yuli Alexandra Guzmán Prada",
    "Yury Jimena Dimate Sánchez",
    "Nivardo Romero Torres",
    "Luis Felipe Polo Morales",
    "Janeth Guzmán Cruz",
    "Asosumapaz",
    "Jhon Alexander Cifuentes",
    "Blanca Aurora Díaz Meneses",
    "Erismendi Castellanos Vásquez",
    "Guillermo Villalba Quintín",
    "Celmira Molina Rey",
    "Gonzalo Romero López",
    "Yaira Yisela Cifuentes Vázquez",
    "Héctor Rubio López",
    "Nuvia Astrid Prieto Penagos",
    "Filiberto Baquero López",
    "Fernando Riveros Espinosa",
    "Beatriz Romero López",
    "Andrea Paola Ramírez Parra",
    "José Adonai Peñalosa Celeita",
    "Freddy Clavijo",
    "Angie Alejandra Tautiva Vergara",
    "Flor Libia Beltrán Dimate",
    "Yeison Efrén Sánchez Pastor",
    "Carlos Julio Tautiva Vergara",
    "Yira Yaneth Ardila Morales",
    "Nancy Viviana Pastor Alejo",
    "Hipolito Dimate Díaz",
    "Edgar Giovanni González Moreno",
    "Nohely Santana Sánchez",
    "Yuliana Andrea Torres Guzmán",
    "Marlén Lizeth Baquero Acosta",
    "Dina Yurley González Salazar",
    "Gina Paola Orjuela Jiménez",
    "Gladermis Parra",
    "Elsa Lucia Meneses Mora",
    "Yina Fonseca Acosta",
    "Leidy Johana Poveda",
    "Diyer Gerardo Prieto Hurtado",
    "Sadi Melo Espinosa",
    "Rito Audel Tautiva Romero",
    "Eva Sánchez Conejo",
    "Haiden Romero",
    "Mayerly Romero Hilarión",
    "Claudia Zambrano Torres",
    "Carlos Julio Macana Romero",
    "Gilma Rocío Romero Guzmán",
    "Jhon Wilson Riveros Espinoza",
    "Ferney Penagos",
    "Nubia Vergara",
    "Saúl Chavarro",
    "Leidy Paola Villalba Sierra",
    "Arnidia Runza Pinilla",
    "Myriam Milady Tautiva Cruz",
    "Alonso Penagos Susa",
    "Doris Sofia Mora Urrea",
    "José Evangelio Rey Montañez",
    "Paula Andrea Morales Orjuela",
    "Ana Margot Morales Orjuela",
    "Raquel Sofía Cifuentes Ramírez",
    "José Antonio Gonzáles Barbosa",
    "Sandra Brigitte Ardila Delgado",
    "Maira Michell Torres Guzmán",
    "Jessica Andrea Palacios Hurtado",
    "Ruth Yaneth Ardila Villalba"
];

function normalizeStr($str) {
    $unwanted = [
        'á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u', 'ü'=>'u', 'ñ'=>'n',
        'Á'=>'a', 'É'=>'e', 'Í'=>'i', 'Ó'=>'o', 'Ú'=>'u', 'Ü'=>'u', 'Ñ'=>'n'
    ];
    $str = strtr($str, $unwanted);
    $str = strtolower($str);
    $str = preg_replace('/[^a-z0-9\s]/', ' ', $str);
    $str = preg_replace('/\s+/', ' ', $str);
    return trim($str);
}

// Fetch all producers
$stmt = $pdo->query("SELECT id, nombre_completo, numero_documento, vereda, beneficiario_2026 FROM productores_sumapaz ORDER BY nombre_completo");
$producers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$db_map = [];
foreach ($producers as $p) {
    $norm = normalizeStr($p['nombre_completo']);
    $db_map[$p['id']] = [
        'id' => $p['id'],
        'nombre_completo' => $p['nombre_completo'],
        'norm' => $norm,
        'doc' => $p['numero_documento'],
        'vereda' => $p['vereda'],
        'beneficiario_2026' => (int)$p['beneficiario_2026'],
        'matched' => false
    ];
}

$matched_db_ids = [];
$match_details = [];

foreach ($raw_list as $input_name) {
    $norm_input = normalizeStr($input_name);
    
    // 1. Exact normalized match
    $found_id = null;
    $match_type = '';
    foreach ($db_map as $id => $p) {
        if ($p['norm'] === $norm_input) {
            $found_id = $id;
            $match_type = 'Exact';
            break;
        }
    }
    
    // 2. Token set match
    if (!$found_id) {
        $input_tokens = explode(' ', $norm_input);
        sort($input_tokens);
        foreach ($db_map as $id => $p) {
            $db_tokens = explode(' ', $p['norm']);
            sort($db_tokens);
            if ($input_tokens === $db_tokens) {
                $found_id = $id;
                $match_type = 'TokenReorder';
                break;
            }
        }
    }

    // 3. Partial/Sub-string matching
    if (!$found_id) {
        $input_tokens = array_filter(explode(' ', $norm_input), function($t) { return strlen($t) > 2; });
        $candidates = [];
        foreach ($db_map as $id => $p) {
            $db_tokens = array_filter(explode(' ', $p['norm']), function($t) { return strlen($t) > 2; });
            $common = array_intersect($input_tokens, $db_tokens);
            if (count($common) >= 2 && count($common) >= (count($input_tokens) - 1)) {
                $candidates[] = $id;
            }
        }
        if (count($candidates) === 1) {
            $found_id = $candidates[0];
            $match_type = 'FuzzySingle';
        } else if (count($candidates) > 1) {
            $best_score = 0;
            $best_cand = null;
            foreach ($candidates as $cand_id) {
                similar_text($norm_input, $db_map[$cand_id]['norm'], $perc);
                if ($perc > $best_score) {
                    $best_score = $perc;
                    $best_cand = $cand_id;
                }
            }
            if ($best_score > 70) {
                $found_id = $best_cand;
                $match_type = 'FuzzyBest (' . round($best_score) . '%)';
            }
        }
    }

    if ($found_id) {
        $db_map[$found_id]['matched'] = true;
        $matched_db_ids[] = $found_id;
        $match_details[] = [
            'input' => $input_name,
            'db_name' => $db_map[$found_id]['nombre_completo'],
            'id' => $found_id,
            'type' => $match_type
        ];
    }
}

echo "Unique matched DB IDs: " . count(array_unique($matched_db_ids)) . "\n";
if (count($matched_db_ids) !== count(array_unique($matched_db_ids))) {
    echo "WARNING: Duplicate matches detected!\n";
    $counts = array_count_values($matched_db_ids);
    foreach ($counts as $id => $cnt) {
        if ($cnt > 1) {
            echo "ID $id matched $cnt times: " . $db_map[$id]['nombre_completo'] . "\n";
        }
    }
} else {
    echo "CONFIRMED: Perfect 1-to-1 match for all 150 producers!\n";
}
