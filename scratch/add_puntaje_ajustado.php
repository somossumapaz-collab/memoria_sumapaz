<?php
$file = __DIR__ . '/../productores_registrados.html';
if (!file_exists($file)) {
    die("File not found\n");
}

$content = file_get_contents($file);

// 1. Add table header
$thTarget = '<th style="text-align: center; width: 100px;">Puntaje</th>';
$thReplacement = '<th style="text-align: center; width: 100px;">Puntaje</th>' . "\n" .
                 '                        <th style="text-align: center; width: 120px;">Puntaje Ajustado</th>';

$content = str_replace($thTarget, $thReplacement, $content, $c1);
echo "Replacement 1 (th): $c1\n";

// 2. Add normalizeVereda helper and veredaCounts calculation in renderTable
$funcTarget = "        function renderTable(dataToDraw) {\n            const tbody = document.getElementById('productores-tbody');\n            tbody.innerHTML = ''; // Clear loading/existing text";
$funcTargetLF = str_replace("\r\n", "\n", $funcTarget);

$funcReplacement = "        function renderTable(dataToDraw) {\n            const tbody = document.getElementById('productores-tbody');\n            tbody.innerHTML = ''; // Clear loading/existing text\n\n            // Helper to normalize veredas\n            const normalizeVereda = (vereda) => {\n                if (!vereda) return '';\n                return vereda\n                    .trim()\n                    .toUpperCase()\n                    .normalize(\"NFD\")\n                    .replace(/[\u0300-\u036f]/g, \"\")\n                    .replace(/\s+/g, ' ');\n            };\n\n            // Build vereda counts map from allProducers\n            const registeredVeredaCounts = {};\n            allProducers.forEach(p => {\n                const vNorm = normalizeVereda(p.vereda);\n                if (vNorm) {\n                    registeredVeredaCounts[vNorm] = (registeredVeredaCounts[vNorm] || 0) + 1;\n                }\n            });";

$content = str_replace($funcTarget, $funcReplacement, $content, $c2);
if ($c2 === 0) {
    $content = str_replace($funcTargetLF, str_replace("\r\n", "\n", $funcReplacement), $content, $c2);
}
echo "Replacement 2 (renderTable helper): $c2\n";

// 3. Add calculations inside row rendering loop
$calcTarget = '                const hasChar = (productor.tiene_caracterizacion == 1);' . "\n" .
              '                const puntaje = (hasChar && productor.puntaje !== null) ? parseInt(productor.puntaje) : null;';
$calcTargetLF = str_replace("\r\n", "\n", $calcTarget);

$calcReplacement = '                const hasChar = (productor.tiene_caracterizacion == 1);' . "\n" .
                   '                const puntaje = (hasChar && productor.puntaje !== null) ? parseInt(productor.puntaje) : null;' . "\n" .
                   '                const vNorm = normalizeVereda(productor.vereda);' . "\n" .
                   '                const countVereda = registeredVeredaCounts[vNorm] || 1;' . "\n" .
                   '                const puntajeAjustado = puntaje !== null ? (puntaje * (1 + 1 / countVereda)) : null;' . "\n" .
                   '                const puntajeAjustadoStr = puntajeAjustado !== null ? ' . "\n" .
                   '                    (Number.isInteger(puntajeAjustado) ? puntajeAjustado : puntajeAjustado.toFixed(2)) : ' . "\n" .
                   '                    \'N/A\';';

$content = str_replace($calcTarget, $calcReplacement, $content, $c3);
if ($c3 === 0) {
    $content = str_replace($calcTargetLF, str_replace("\r\n", "\n", $calcReplacement), $content, $c3);
}
echo "Replacement 3 (calculation): $c3\n";

// 4. Add adjusted score cell in mainTr.innerHTML
$tdTarget = '                    <td style="text-align: center;">${estadoHtml}</td>' . "\n" .
            '                    <td style="text-align: center; font-size: 1.15rem; color: ${colorPuntaje}; font-weight: bold;">${puntajeStr}</td>' . "\n" .
            '                    <td style="text-align: center;">${elegibleHtml}</td>';
$tdTargetLF = str_replace("\r\n", "\n", $tdTarget);

$tdReplacement = '                    <td style="text-align: center;">${estadoHtml}</td>' . "\n" .
                 '                    <td style="text-align: center; font-size: 1.15rem; color: ${colorPuntaje}; font-weight: bold;">${puntajeStr}</td>' . "\n" .
                 '                    <td style="text-align: center; font-size: 1.15rem; color: #444F2F; font-weight: bold;">${puntajeAjustadoStr}</td>' . "\n" .
                 '                    <td style="text-align: center;">${elegibleHtml}</td>';

$content = str_replace($tdTarget, $tdReplacement, $content, $c4);
if ($c4 === 0) {
    $content = str_replace($tdTargetLF, str_replace("\r\n", "\n", $tdReplacement), $content, $c4);
}
echo "Replacement 4 (td cell): $c4\n";

// 5. Update colspan of detail row
$csTarget = '                    <td colspan="7" style="padding: 1.5rem 2rem; border-top: none; border-bottom: 2px solid #e0dcd5;">';
$csTargetLF = str_replace("\r\n", "\n", $csTarget);

$csReplacement = '                    <td colspan="8" style="padding: 1.5rem 2rem; border-top: none; border-bottom: 2px solid #e0dcd5;">';

$content = str_replace($csTarget, $csReplacement, $content, $c5);
if ($c5 === 0) {
    $content = str_replace($csTargetLF, str_replace("\r\n", "\n", $csReplacement), $content, $c5);
}
echo "Replacement 5 (colspan): $c5\n";

// Save file
file_put_contents($file, $content);
echo "File updated successfully.\n";
