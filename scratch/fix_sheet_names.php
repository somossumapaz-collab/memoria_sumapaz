<?php
$file = __DIR__ . '/../productores_registrados.html';
if (!file_exists($file)) {
    die("File not found\n");
}

$content = file_get_contents($file);

$target = '                const wb = XLSX.utils.book_new();' . "\r\n" .
          '                XLSX.utils.book_append_sheet(wb, wsProducts, "Productos Ofertados (A Llenar)");' . "\r\n" .
          '                XLSX.utils.book_append_sheet(wb, wsServices, "Servicios y Actividades (A Llenar)");';

$replacement = '                const wb = XLSX.utils.book_new();' . "\r\n" .
               '                XLSX.utils.book_append_sheet(wb, wsBase, "Ficha Base");' . "\r\n" .
               '                XLSX.utils.book_append_sheet(wb, wsProducts, "Productos Ofertados");' . "\r\n" .
               '                XLSX.utils.book_append_sheet(wb, wsServices, "Servicios y Actividades");';

// Try replacing with CRLF
$newContent = str_replace($target, $replacement, $content, $count);

if ($count === 0) {
    // Try replacing with LF
    $targetLF = str_replace("\r\n", "\n", $target);
    $replacementLF = str_replace("\r\n", "\n", $replacement);
    $newContent = str_replace($targetLF, $replacementLF, $content, $count);
}

if ($count > 0) {
    file_put_contents($file, $newContent);
    echo "Successfully replaced sheet name code. Count: $count\n";
} else {
    echo "Could not find the target code block for replacement.\n";
    // Let's do a more flexible search
    $targetFlexible = 'wsProducts, "Productos Ofertados (A Llenar)"';
    if (strpos($content, $targetFlexible) !== false) {
        echo "Found the flexible target. Replacing lines manually...\n";
        // Let's replace the block using regex
        $pattern = '/const\s+wb\s*=\s*XLSX\.utils\.book_new\(\);\s*XLSX\.utils\.book_append_sheet\(\s*wb\s*,\s*wsProducts\s*,\s*"Productos\s+Ofertados\s*\(A\s+Llenar\)"\s*\);\s*XLSX\.utils\.book_append_sheet\(\s*wb\s*,\s*wsServices\s*,\s*"Servicios\s+y\s+Actividades\s*\(A\s+Llenar\)"\s*\);/i';
        $newContent = preg_replace($pattern, 'const wb = XLSX.utils.book_new();' . "\n" . '                XLSX.utils.book_append_sheet(wb, wsBase, "Ficha Base");' . "\n" . '                XLSX.utils.book_append_sheet(wb, wsProducts, "Productos Ofertados");' . "\n" . '                XLSX.utils.book_append_sheet(wb, wsServices, "Servicios y Actividades");', $content, -1, $regCount);
        if ($regCount > 0) {
            file_put_contents($file, $newContent);
            echo "Successfully replaced using regex pattern. Count: $regCount\n";
        } else {
            echo "Regex replacement failed as well.\n";
        }
    } else {
        echo "Flexible target not found either!\n";
    }
}
