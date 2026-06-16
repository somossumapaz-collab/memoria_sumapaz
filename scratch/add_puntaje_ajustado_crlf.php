<?php
$file = __DIR__ . '/../productores_registrados.html';
if (!file_exists($file)) {
    die("File not found\n");
}

$content = file_get_contents($file);

// 1. Add normalizeVereda helper and veredaCounts calculation in renderTable
$funcTarget = '        function renderTable(dataToDraw) {' . "\r\n" .
              '            const tbody = document.getElementById(\'productores-tbody\');' . "\r\n" .
              '            tbody.innerHTML = \'\'; // Clear loading/existing text';

$funcReplacement = '        function renderTable(dataToDraw) {' . "\r\n" .
                   '            const tbody = document.getElementById(\'productores-tbody\');' . "\r\n" .
                   '            tbody.innerHTML = \'\'; // Clear loading/existing text' . "\r\n" .
                   "\r\n" .
                   '            // Helper to normalize veredas' . "\r\n" .
                   '            const normalizeVereda = (vereda) => {' . "\r\n" .
                   '                if (!vereda) return \'\';' . "\r\n" .
                   '                return vereda' . "\r\n" .
                   '                    .trim()' . "\r\n" .
                   '                    .toUpperCase()' . "\r\n" .
                   '                    .normalize("NFD")' . "\r\n" .
                   '                    .replace(/[\u0300-\u036f]/g, "")' . "\r\n" .
                   '                    .replace(/\s+/g, \' \');' . "\r\n" .
                   '            };' . "\r\n" .
                   "\r\n" .
                   '            // Build vereda counts map from allProducers' . "\r\n" .
                   '            const registeredVeredaCounts = {};' . "\r\n" .
                   '            allProducers.forEach(p => {' . "\r\n" .
                   '                const vNorm = normalizeVereda(p.vereda);' . "\r\n" .
                   '                if (vNorm) {' . "\r\n" .
                   '                    registeredVeredaCounts[vNorm] = (registeredVeredaCounts[vNorm] || 0) + 1;' . "\r\n" .
                   '                }' . "\r\n" .
                   '            });';

$newContent = str_replace($funcTarget, $funcReplacement, $content, $c2);

if ($c2 === 0) {
    // Try LF
    $funcTargetLF = str_replace("\r\n", "\n", $funcTarget);
    $funcReplacementLF = str_replace("\r\n", "\n", $funcReplacement);
    $newContent = str_replace($funcTargetLF, $funcReplacementLF, $content, $c2);
}
echo "Replacement 2 (renderTable helper): $c2\n";
if ($c2 > 0) {
    $content = $newContent;
}

// 3. Add calculations inside row rendering loop
$calcTarget = '                const hasChar = (productor.tiene_caracterizacion == 1);' . "\r\n" .
              '                const puntaje = (hasChar && productor.puntaje !== null) ? parseInt(productor.puntaje) : null;';

$calcReplacement = '                const hasChar = (productor.tiene_caracterizacion == 1);' . "\r\n" .
                   '                const puntaje = (hasChar && productor.puntaje !== null) ? parseInt(productor.puntaje) : null;' . "\r\n" .
                   '                const vNorm = normalizeVereda(productor.vereda);' . "\r\n" .
                   '                const countVereda = registeredVeredaCounts[vNorm] || 1;' . "\r\n" .
                   '                const puntajeAjustado = puntaje !== null ? (puntaje * (1 + 1 / countVereda)) : null;' . "\r\n" .
                   '                const puntajeAjustadoStr = puntajeAjustado !== null ? ' . "\r\n" .
                   '                    (Number.isInteger(puntajeAjustado) ? puntajeAjustado : puntajeAjustado.toFixed(2)) : ' . "\n" .
                   '                    \'N/A\';';

$newContent = str_replace($calcTarget, $calcReplacement, $content, $c3);
if ($c3 === 0) {
    // Try LF
    $calcTargetLF = str_replace("\r\n", "\n", $calcTarget);
    $calcReplacementLF = str_replace("\r\n", "\n", $calcReplacement);
    $newContent = str_replace($calcTargetLF, $calcReplacementLF, $content, $c3);
}
echo "Replacement 3 (calculation): $c3\n";
if ($c3 > 0) {
    $content = $newContent;
}

// 4. Add adjusted score cell in mainTr.innerHTML
$tdTarget = '                    <td style="text-align: center;">${estadoHtml}</td>' . "\r\n" .
            '                    <td style="text-align: center; font-size: 1.15rem; color: ${colorPuntaje}; font-weight: bold;">${puntajeStr}</td>' . "\r\n" .
            '                    <td style="text-align: center;">${elegibleHtml}</td>';

$tdReplacement = '                    <td style="text-align: center;">${estadoHtml}</td>' . "\r\n" .
                 '                    <td style="text-align: center; font-size: 1.15rem; color: ${colorPuntaje}; font-weight: bold;">${puntajeStr}</td>' . "\r\n" .
                 '                    <td style="text-align: center; font-size: 1.15rem; color: #444F2F; font-weight: bold;">${puntajeAjustadoStr}</td>' . "\r\n" .
                 '                    <td style="text-align: center;">${elegibleHtml}</td>';

$newContent = str_replace($tdTarget, $tdReplacement, $content, $c4);
if ($c4 === 0) {
    // Try LF
    $tdTargetLF = str_replace("\r\n", "\n", $tdTarget);
    $tdReplacementLF = str_replace("\r\n", "\n", $tdReplacement);
    $newContent = str_replace($tdTargetLF, $tdReplacementLF, $content, $c4);
}
echo "Replacement 4 (td cell): $c4\n";
if ($c4 > 0) {
    $content = $newContent;
}

file_put_contents($file, $content);
echo "Done.\n";
