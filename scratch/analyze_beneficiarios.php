<?php
require_once __DIR__ . '/../api/db_config.php';

echo "=== ANALYZING PRODUCERS AND PMAPCS ===\n\n";

// Count in pmapc_registros
$stmt_pmapc = $pdo->query("
    SELECT pr.id as registro_id, pr.productor_id, pr.created_at, pr.updated_at, p.nombre_completo, p.vereda, p.numero_documento, p.beneficiario_2026
    FROM pmapc_registros pr
    INNER JOIN productores_sumapaz p ON pr.productor_id = p.id
    ORDER BY pr.id ASC
");
$pmapc_rows = $stmt_pmapc->fetchAll(PDO::FETCH_ASSOC);

echo "1. Total PMAPC registros in DB (pmapc_registros): " . count($pmapc_rows) . "\n";
echo "   Last 10 loaded PMAPC registros:\n";
$last_10 = array_slice($pmapc_rows, -10);
foreach ($last_10 as $r) {
    echo "   - Registro #{$r['registro_id']} | Prod ID {$r['productor_id']} | {$r['nombre_completo']} | Vereda: {$r['vereda']} (Beneficiario: {$r['beneficiario_2026']})\n";
}

// Check total producers in DB marked as beneficiario_2026 = 1
$stmt_ben = $pdo->query("SELECT id, nombre_completo, numero_documento, vereda FROM productores_sumapaz WHERE beneficiario_2026 = 1 ORDER BY id ASC");
$ben_rows = $stmt_ben->fetchAll(PDO::FETCH_ASSOC);
echo "\n2. Total producers in `productores_sumapaz` with `beneficiario_2026 = 1`: " . count($ben_rows) . "\n";

// Producers in DB that DO NOT have a PMAPC registro yet
$stmt_missing = $pdo->query("
    SELECT p.id, p.nombre_completo, p.numero_documento, p.vereda, p.beneficiario_2026
    FROM productores_sumapaz p
    LEFT JOIN pmapc_registros pr ON p.id = pr.productor_id
    WHERE pr.id IS NULL
    ORDER BY p.id ASC
");
$missing_rows = $stmt_missing->fetchAll(PDO::FETCH_ASSOC);
echo "\n3. Total producers in `productores_sumapaz` WITHOUT a PMAPC record in DB: " . count($missing_rows) . "\n";
if (!empty($missing_rows)) {
    echo "   Producers missing PMAPC:\n";
    foreach ($missing_rows as $m) {
        echo "   - ID {$m['id']} | {$m['nombre_completo']} | Doc: {$m['numero_documento']} | Vereda: {$m['vereda']} (Beneficiario: {$m['beneficiario_2026']})\n";
    }
}

// Count total producers in productores_sumapaz
$stmt_total = $pdo->query("SELECT COUNT(*) FROM productores_sumapaz");
$total_prods = $stmt_total->fetchColumn();
echo "\n4. Total producers in `productores_sumapaz` table: " . $total_prods . "\n";
?>
