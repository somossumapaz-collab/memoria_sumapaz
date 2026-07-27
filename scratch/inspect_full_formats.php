<?php
require_once __DIR__ . '/../api/db_config.php';

$stmt = $pdo->query("SELECT id, productor_id, CHAR_LENGTH(data) as len FROM pmapc_registros ORDER BY len DESC LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $r) {
    $pid = $r['productor_id'];
    $stmt2 = $pdo->prepare("SELECT data FROM pmapc_registros WHERE productor_id = ?");
    $stmt2->execute([$pid]);
    $data_json = $stmt2->fetchColumn();
    $data = json_decode($data_json, true);

    echo "=== PRODUCTOR ID $pid (LEN {$r['len']}) ===\n";
    if (is_array($data)) {
        foreach ($data as $k => $v) {
            if ($k === 'preguntas_respuestas') {
                echo "  $k => Array of " . count($v) . " items\n";
            } elseif (is_array($v)) {
                echo "  $k => Array (" . count($v) . " items)\n";
            } else {
                echo "  $k => " . substr((string)$v, 0, 50) . "...\n";
            }
        }
    }
    echo "\n";
}
