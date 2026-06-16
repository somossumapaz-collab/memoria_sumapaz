<?php
$file = __DIR__ . '/../productores_registrados.html';
if (!file_exists($file)) {
    die("File not found\n");
}

$content = file_get_contents($file);

$target = '                    const row = {' . "\r\n" .
          '                        "Puesto General": generalRank,' . "\r\n" .
          '                        "Ranking Vereda": veredaRank,' . "\r\n" .
          '                        "Productor": p.nombre_completo || \'\-\',' . "\r\n" .
          '                        "Vereda": p.vereda || \'\-\',' . "\r\n" .
          '                        "Puntaje Total": p.puntaje,';

// Try standard CRLF replacement
$targetClean = '                    const row = {' . "\r\n" .
               '                        "Puesto General": generalRank,' . "\r\n" .
               '                        "Ranking Vereda": veredaRank,' . "\r\n" .
               '                        "Productor": p.nombre_completo || \'-\',' . "\r\n" .
               '                        "Vereda": p.vereda || \'-\',' . "\r\n" .
               '                        "Puntaje Total": p.puntaje,';

$replacementClean = '                    const row = {' . "\r\n" .
                    '                        "Puesto General": generalRank,' . "\r\n" .
                    '                        "Ranking Vereda": veredaRank,' . "\r\n" .
                    '                        "Productor": p.nombre_completo || \'-\',' . "\r\n" .
                    '                        "Vereda": p.vereda || \'-\',' . "\r\n" .
                    '                        "Estado Caracterización": p.is_complete ? \'Completa\' : \'Incompleta\',' . "\r\n" .
                    '                        "Puntaje Total": p.puntaje,';

$newContent = str_replace($targetClean, $replacementClean, $content, $count);

if ($count === 0) {
    // Try LF line endings
    $targetLF = str_replace("\r\n", "\n", $targetClean);
    $replacementLF = str_replace("\r\n", "\n", $replacementClean);
    $newContent = str_replace($targetLF, $replacementLF, $content, $count);
}

if ($count > 0) {
    file_put_contents($file, $newContent);
    echo "Successfully updated row object with Estado Caracterización! Count: $count\n";
} else {
    echo "Target block not found. Checking alternate search...\n";
    // Search for a smaller substring
    $targetAlt = '"Vereda": p.vereda || \'-\',';
    if (strpos($content, $targetAlt) !== false) {
        $newContent = str_replace($targetAlt, $targetAlt . "\n" . '                        "Estado Caracterización": p.is_complete ? \'Completa\' : \'Incompleta\',', $content, $count);
        file_put_contents($file, $newContent);
        echo "Successfully updated using alternative replacement! Count: $count\n";
    } else {
        echo "Alternative target not found.\n";
    }
}
