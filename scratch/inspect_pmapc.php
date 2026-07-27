<?php
require_once __DIR__ . '/../api/db_config.php';

try {
    $stmt = $pdo->query("DESCRIBE pmapc_registros");
    echo "Columns of pmapc_registros:\n";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

    $stmt = $pdo->query("SELECT p.id, p.nombre_completo, pr.id as pmapc_id, CHAR_LENGTH(pr.data) as data_len FROM pmapc_registros pr JOIN productores_sumapaz p ON pr.productor_id = p.id LIMIT 10");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\n=== Sample PMAPC records in DB ===\n";
    print_r($rows);

    if (!empty($rows)) {
        $sample_id = $rows[0]['id'];
        $stmt2 = $pdo->prepare("SELECT data FROM pmapc_registros WHERE productor_id = ?");
        $stmt2->execute([$sample_id]);
        $data_json = $stmt2->fetchColumn();
        $parsed = json_decode($data_json, true);
        echo "\n=== Keys in sample PMAPC data (Productor ID $sample_id) ===\n";
        if (is_array($parsed)) {
            echo "Top-level keys: " . implode(', ', array_keys($parsed)) . "\n";
            if (!empty($parsed['preguntas_respuestas']) && is_array($parsed['preguntas_respuestas'])) {
                echo "Total preguntas_respuestas: " . count($parsed['preguntas_respuestas']) . "\n";
                $formats = [];
                foreach ($parsed['preguntas_respuestas'] as $pr) {
                    $f = $pr['formato'] ?? 'SinFormato';
                    $formats[$f] = ($formats[$f] ?? 0) + 1;
                }
                echo "Formats in preguntas_respuestas: ";
                print_r($formats);
            }
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
