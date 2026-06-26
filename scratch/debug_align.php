<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../api/db_config.php';

try {
    echo "Fetching producers...\n";
    $stmt = $pdo->query("
        SELECT p.id, p.beneficiario_2026, IFNULL(cp.puntaje, -1) as puntaje
        FROM productores_sumapaz p
        LEFT JOIN caracterizacion_productor cp ON p.id = cp.productor_id
    ");
    $producers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    usort($producers, function($a, $b) {
        return intval($b['puntaje']) - intval($a['puntaje']);
    });

    $top_151_ids = [];
    foreach (array_slice($producers, 0, 151) as $p) {
        $top_151_ids[] = intval($p['id']);
    }

    $stmt_update_to_1 = $pdo->prepare("UPDATE productores_sumapaz SET beneficiario_2026 = 1 WHERE id = ?");
    $stmt_update_to_0 = $pdo->prepare("UPDATE productores_sumapaz SET beneficiario_2026 = 0 WHERE id = ?");

    $count_to_1 = 0;
    $count_to_0 = 0;

    foreach ($producers as $p) {
        $pid = intval($p['id']);
        $status = intval($p['beneficiario_2026']);
        $in_top = in_array($pid, $top_151_ids);

        if ($in_top) {
            if ($status != 1 && $status != 2) {
                echo "Updating ID: $pid to 1 (in top 151, current status is $status)\n";
                $stmt_update_to_1->execute([$pid]);
                $count_to_1++;
            }
        } else {
            if ($status == 1) {
                echo "Updating ID: $pid to 0 (not in top 151, current status is $status)\n";
                $stmt_update_to_0->execute([$pid]);
                $count_to_0++;
            }
        }
    }

    echo "Finished! Updated to 1: $count_to_1, Updated to 0: $count_to_0\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
