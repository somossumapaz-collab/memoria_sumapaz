<?php
$file = __DIR__ . '/../productores_registrados.html';
if (!file_exists($file)) {
    die("File not found\n");
}

$content = file_get_contents($file);

// We replace the block inside #productores-tbody
$target = '<tbody id="productores-tbody">
                    <!-- Data will be loaded via JS -->
                    <tr>
                        <td colspan="13" style="text-align: center; padding: 2rem;">Cargando productores...</td>
                    </tr>
                </tbody>';

$replacement = '<tbody id="productores-tbody">
                    <!-- Data will be loaded via JS -->
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem;">Cargando productores...</td>
                    </tr>
                </tbody>';

$content = str_replace($target, $replacement, $content, $count);
if ($count === 0) {
    $targetCRLF = str_replace("\n", "\r\n", $target);
    $replacementCRLF = str_replace("\n", "\r\n", $replacement);
    $content = str_replace($targetCRLF, $replacementCRLF, $content, $count);
}

echo "Colspan replaced: $count\n";
file_put_contents($file, $content);
?>
