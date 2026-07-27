<?php
require_once __DIR__ . '/../api/db_config.php';

$raw_levels = [
    // Nivel 1
    "Asosumapaz" => 1,
    "Procampsu" => 1,
    "Saul Bautista Mican" => 1,
    "Asociación Asoarcopaz" => 1,
    "Hadaly Esperanza Rubiano Benavides" => 1,
    "Dary Esmerralda Palacios Moreno" => 1,
    "German Oswaldo Rodriguez Duarte" => 1,
    "William Mauricio Palacios Rey" => 1,
    "Mayerly Romero Hilarion" => 1,
    "Lina Marcela Guzmán Prada" => 1,
    "Jorge Orlando Gutierrez Gomez" => 1,
    "Jose Benjamin Hortua Mican" => 1,
    "Luis Eduardo Dimate" => 1,
    "Niyired Dimate Rios" => 1,
    "Enrique Diaz" => 1,
    "Fanny Baquero Molina" => 1,
    "Ruben Dario Vasquez Huertas" => 1,
    "Lidia Sanabria Lopez" => 1,
    "Doris Stella Villalba Baquero" => 1,
    "Ivan Dario Chingate Mican" => 1,
    "Elzon Ferney Delgado Morales" => 1,
    "Maria Sabina Mican" => 1,

    // Nivel 2
    "Haiden Romero" => 2,
    "Alejandro Pinilla" => 2,
    "Erasmos Vaquero Romero" => 2,
    "Héctor Rubio López" => 2,
    "Ingrid Rocio Castro Carillo" => 2,
    "Marlen Lizeth Baquero Acosta" => 2,
    "Miriam Romero Palacios" => 2,
    "Leopoldo Romero Herrera" => 2,
    "Heriberto Bernal Muñoz" => 2,
    "Salomón Romero Moreno" => 2,
    "Albeiro Mican Romero" => 2,
    "Jhon Alexander Cifuentes" => 2,
    "Laura Jimena Gonzalez Cruz" => 2,
    "Isaac David Mican Mican" => 2,
    "Liliana Briyid Rivera Achury" => 2,
    "Raquel Sofía Cifuentes Ramirez" => 2,
    "José Antonio Gonzales Barbosa" => 2,
    "Saul Chavarro" => 2,
    "Jose Adonai Peñalosa Celeita" => 2,
    "Nancy Velasquez Morales" => 2,
    "Alonso Penagos Susa" => 2,
    "Clelia Palacios" => 2,
    "Nohely Santana Sanchez" => 2,
    "Daniela Rojas Suarez" => 2,
    "Carmen Rosa Moreno Moreno" => 2,
    "Nuvia Astrid Prieto Penagos" => 2,
    "Jose Alejandro Pulido Moreno" => 2,
    "Yaneth Motavita" => 2,
    "Bonifacio Hernández" => 2,
    "Guillermo Villalba Quintín" => 2,
    "Josué Torres Riveros" => 2,
    "Gonzalo Romero Lopez" => 2,
    "Fabio Nelson Gutiérrez Chingate" => 2,
    "Blanca Aurora Diaz Meneses" => 2,
    "John Alexander Sorza Leon" => 2,
    "Dina Yurley González Salazar" => 2,
    "Johana Gutiérrez Castro" => 2,
    "Sandra Brigitte Ardila Delgado" => 2,
    "Paula Daniela Larrota Borray" => 2,
    "Angie Alejandra Tautiva Vergara" => 2,
    "Clara Bersalid Gonzalez Caro" => 2,
    "Ferney Penagos" => 2,
    "Diana Marcela Romero" => 2,
    "Ilma Nieves Baquero Hortua" => 2,
    "Fani Carrillo Rodriguez" => 2,
    "Carlos Arturo Pulido Torres" => 2,
    "Flor Marina Baquero Romero" => 2,
    "Doris Sofia Mora Urrea" => 2,
    "Blanca Cecilia Balbuena Cifeuntes" => 2,
    "Martha Cecilia Vergara González" => 2,
    "Darwin Smith Rubiano Pulido" => 2,
    "Claudia Zambrano Torres" => 2,
    "Fermey Perez Bustos" => 2,
    "Erismendi Castellanos Vásquez" => 2,
    "Yolanda Morales Pabon" => 2,
    "Arnidia Runza Pinilla" => 2,
    "Alba Nery Mican Poveda" => 2,
    "Pedro Alfonso Castro Morales" => 2,
    "Edgar Giovanni González Moreno" => 2,
    "Eduin Eduardo Parada Macana" => 2,

    // Nivel 3
    "Laura Morales" => 3,
    "María Arcenia Martinez Hernandez" => 3,
    "Nivardo Romero Torres" => 3,
    "Ivan Andres Peñaloza Valbuena" => 3,
    "Vitelma Quevedo Chingate" => 3,
    "Lucila Torres Peñaloza" => 3,
    "Maira Michell Torres Guzman" => 3,
    "Yury Jimena Dimate Sanchez" => 3,
    "Yaira Yisela Cifuentes Vázquez" => 3,
    "Jose Evangelio Rey Montañez" => 3,
    "Filiberto Baquero López" => 3,
    "Martha Yaneth Cabrera Arte" => 3,
    "Cristian Alonso Morales Palacios" => 3,
    "Elsa Lucia Meneses Mora" => 3,
    "Yina Fonseca Acosta" => 3,
    "Marcela Martínez" => 3,
    "Carlos Julio Macana Romero" => 3,
    "Robinson Arley Ramos Polo" => 3,
    "Jhon Wilson Riveros Espinoza" => 3,
    "Jessica Lorena Salgado Contreras" => 3,
    "Ruth Esperanza Valbuena Vergara" => 3,
    "Luz Helena Diosa" => 3,
    "Jose Aquino Muñoz" => 3,
    "Gladermis Parra" => 3,
    "Mónica Diaz" => 3,
    "Fernando Riveros Espinosa" => 3,
    "Leidy Johana Poveda" => 3,
    "William Dimaté Rico" => 3,
    "Rito Audel Tautiva Romero" => 3,
    "Diyer Gerardo Prieto Hurtado" => 3,
    "Maria Yolanda Perez Ussa" => 3,
    "Jessica Andrea Palacios Hurtado" => 3,
    "Edilson Hernando Melo Arevalo" => 3,
    "Nancy Viviana Pastor Alejo" => 3,
    "Celmira Molina Rey" => 3,
    "Yuli Alexandra Guzman Prada" => 3,
    "Yeni Lorena Parra Gallo" => 3,
    "Myriam Milady Tautiva Cruz" => 3,
    "Beatriz Romero López" => 3,
    "Yeimy Paola Lancheros" => 3,
    "Yuliana Andrea Torres Guzman" => 3,
    "Pedro Ignacio Rico Chavez" => 3,
    "Nubia Vergara" => 3,
    "Ruth Yaneth Ardila Villalba" => 3,
    "Fredesminda Pérez Castro" => 3,
    "Sadi Melo Espinosa" => 3,
    "Gilma Rocio Romero Guzman" => 3,

    // Nivel 4
    "Flor Libia Beltrán Dimate" => 4,
    "Yury Graciela Romero Baquero" => 4,
    "Juan Andres Gomez Gomez" => 4,
    "Eva Sanchez Conejo" => 4,
    "Paula Andrea Morales Orjuela" => 4,
    "Elías Mican Mican" => 4,
    "Janeth Guzmán Cruz" => 4,
    "Maria Luz Dary Zambrano Torres" => 4,
    "Victor Alonso Diaz" => 4,
    "Andrea Paola Ramirez Parra" => 4,
    "Yira Yaneth Ardila Morales" => 4,
    "Ana Margot Morales Orjuela" => 4,
    "Carlos Julio Tautiva Vergara" => 4,
    "Ivan Dario Perez Ortiz" => 4,
    "Hipolito Dimate Diaz" => 4,
    "Yeison Efren Sánchez Pastor" => 4,
    "Rosalba Rojas Torres" => 4,
    "Mauricio Pabon Morales" => 4,
    "Fabio Agusto Romero Hortia" => 4,
    "Gina Paola Orjuela Jimenez" => 4,
    "Freddy Clavijo" => 4
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

// 1. Ensure column exists
try {
    $pdo->exec("ALTER TABLE productores_sumapaz ADD COLUMN IF NOT EXISTS nivel_priorizacion TINYINT NULL DEFAULT NULL");
    echo "Column nivel_priorizacion verified/added.\n";
} catch (Exception $e) {
    // If MariaDB/MySQL doesn't support IF NOT EXISTS on ADD COLUMN:
    try {
        $pdo->exec("ALTER TABLE productores_sumapaz ADD COLUMN nivel_priorizacion TINYINT NULL DEFAULT NULL");
        echo "Column nivel_priorizacion added.\n";
    } catch (Exception $ex) {
        echo "Column already exists or error: " . $ex->getMessage() . "\n";
    }
}

// 2. Fetch all producers
$stmt = $pdo->query("SELECT id, nombre_completo, beneficiario_2026 FROM productores_sumapaz");
$producers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$db_map = [];
foreach ($producers as $p) {
    $norm = normalizeStr($p['nombre_completo']);
    $db_map[$p['id']] = [
        'id' => (int)$p['id'],
        'nombre_completo' => $p['nombre_completo'],
        'norm' => $norm,
        'beneficiario_2026' => (int)$p['beneficiario_2026']
    ];
}

// 3. Match provided level list to DB IDs
$level_by_id = [];
$unmatched_level_names = [];

foreach ($raw_levels as $name => $lvl) {
    $norm_input = normalizeStr($name);
    
    $found_id = null;
    foreach ($db_map as $id => $p) {
        if ($p['norm'] === $norm_input) {
            $found_id = $id;
            break;
        }
    }
    
    if (!$found_id) {
        $input_tokens = explode(' ', $norm_input);
        sort($input_tokens);
        foreach ($db_map as $id => $p) {
            $db_tokens = explode(' ', $p['norm']);
            sort($db_tokens);
            if ($input_tokens === $db_tokens) {
                $found_id = $id;
                break;
            }
        }
    }

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
            }
        }
    }

    if ($found_id) {
        $level_by_id[$found_id] = $lvl;
    } else {
        $unmatched_level_names[] = $name;
    }
}

echo "Matched level entries: " . count($level_by_id) . " / " . count($raw_levels) . "\n";
if (!empty($unmatched_level_names)) {
    echo "Unmatched names in level list:\n";
    foreach ($unmatched_level_names as $u) {
        echo "  - $u\n";
    }
}

// 4. Determine final nivel_priorizacion for every producer in DB
// Rules:
// - If beneficiario_2026 != 1 => NULL
// - If beneficiario_2026 == 1 => level from list if present, else 4

$updates = [
    'null' => [],
    1 => [],
    2 => [],
    3 => [],
    4 => []
];

foreach ($db_map as $id => $p) {
    if ($p['beneficiario_2026'] != 1) {
        $updates['null'][] = $id;
    } else {
        if (isset($level_by_id[$id])) {
            $lvl = $level_by_id[$id];
            $updates[$lvl][] = $id;
        } else {
            // Beneficiary not in list => level 4
            $updates[4][] = $id;
        }
    }
}

echo "\nUpdate Summary Plan:\n";
echo "  Set NULL (Non-beneficiaries): " . count($updates['null']) . "\n";
echo "  Set Level 1: " . count($updates[1]) . "\n";
echo "  Set Level 2: " . count($updates[2]) . "\n";
echo "  Set Level 3: " . count($updates[3]) . "\n";
echo "  Set Level 4: " . count($updates[4]) . "\n";

// Execute DB updates
$pdo->beginTransaction();
try {
    // Set NULL
    if (!empty($updates['null'])) {
        $in = implode(',', $updates['null']);
        $pdo->query("UPDATE productores_sumapaz SET nivel_priorizacion = NULL WHERE id IN ($in)");
    }
    // Set 1, 2, 3, 4
    for ($l = 1; $l <= 4; $l++) {
        if (!empty($updates[$l])) {
            $in = implode(',', $updates[$l]);
            $pdo->query("UPDATE productores_sumapaz SET nivel_priorizacion = $l WHERE id IN ($in)");
        }
    }

    $pdo->commit();
    echo "\nSUCCESS: nivel_priorizacion updated for all producers!\n";

    // Verify DB counts
    $stmt = $pdo->query("SELECT IFNULL(nivel_priorizacion, 'NULL') as lvl, COUNT(*) as cnt FROM productores_sumapaz GROUP BY nivel_priorizacion ORDER BY lvl");
    echo "\nFinal DB Counts for nivel_priorizacion:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  Nivel " . $row['lvl'] . " => " . $row['cnt'] . " productores\n";
    }

} catch (Exception $e) {
    $pdo->rollBack();
    die("DB Error: " . $e->getMessage() . "\n");
}
