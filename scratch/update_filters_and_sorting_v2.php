<?php
$file = __DIR__ . '/../productores_registrados.html';
if (!file_exists($file)) {
    die("File not found\n");
}

$content = file_get_contents($file);

// 1. Regex to replace the table headers (eliminates spacing issues)
$content = preg_replace(
    '/<table class="data-table" id="productores-table">.*?<thead>.*?<tr>(.*?)<\/tr>.*?<\/thead>/is',
    '<table class="data-table" id="productores-table">
                <thead>
                    <tr>
                        <th style="text-align: center; width: 90px;">Ranking</th>
                        <th>Productor</th>
                        <th>Vereda</th>
                        <th style="text-align: center;">Estado Caracterización</th>
                        <th class="sortable" style="text-align: center; width: 110px; cursor: pointer; user-select: none;" onclick="toggleSort(\'puntaje\')">Puntaje<span id="sort-icon-puntaje">▼</span></th>
                        <th class="sortable" style="text-align: center; width: 140px; cursor: pointer; user-select: none;" onclick="toggleSort(\'puntaje_ajustado\')">Puntaje Ajustado<span id="sort-icon-puntaje-ajustado"></span></th>
                        <th style="text-align: center; width: 120px;">Acción</th>
                    </tr>
                </thead>',
    $content,
    1,
    $count3
);
echo "Replacement 3 (Regex Table Headers): $count3\n";

// 2. Replacement for the filters at the start of exportToExcel()
$exportFiltersTarget = '            const efectividadVal = document.getElementById(\'filter-efectividad\').value;
            const panacaVal = document.getElementById(\'filter-panaca\').value;
            const elegibilidadVal = document.getElementById(\'filter-elegibilidad\').value;
            const actividadVal = document.getElementById(\'filter-actividad\').value;

            const dataToExport = allProducers.filter(p => {
                let matchNombre = !nombreVal || p.nombre_completo.toLowerCase().includes(nombreVal);
                let matchVereda = !veredaVal || p.vereda === veredaVal;
                let matchCuenca = !cuencaVal || p.cuenca === cuencaVal;
                let matchFecha = !fechaVal || (p.fecha_creacion && p.fecha_creacion.startsWith(fechaVal));
                let matchCaracterizado = caracterizadoVal === "" || p.tiene_caracterizacion == caracterizadoVal;
                let matchMypime = mypimeVal === "" || p.mypime == mypimeVal;
                let matchEfectividad = efectividadVal === "" || p.efectividad_2025 == efectividadVal;
                let matchPanaca = panacaVal === "" || p.panaca == panacaVal;

                let matchElegibilidad = true;
                if (elegibilidadVal === \'si\') {
                    matchElegibilidad = (p.tiene_caracterizacion == 1 && p.puntaje >= 60);
                } else if (elegibilidadVal === \'no\') {
                    matchElegibilidad = (p.tiene_caracterizacion == 1 && p.puntaje < 60);
                } else if (elegibilidadVal === \'null\') {
                    matchElegibilidad = (p.tiene_caracterizacion == 0);
                }

                let matchActividad = true;
                if (actividadVal) {
                    if (p.categorias_ids) {
                        const catIds = p.categorias_ids.split(\',\');
                        matchActividad = catIds.includes(actividadVal);
                    } else {
                        matchActividad = false;
                    }
                }

                return matchNombre && matchVereda && matchCuenca && matchFecha && matchCaracterizado && 
                       matchMypime && matchEfectividad && matchPanaca && matchElegibilidad && matchActividad;
            });';

$exportFiltersReplacement = '            const actividadVal = document.getElementById(\'filter-actividad\').value;

            const dataToExport = allProducers.filter(p => {
                let matchNombre = !nombreVal || p.nombre_completo.toLowerCase().includes(nombreVal);
                let matchVereda = !veredaVal || p.vereda === veredaVal;
                let matchCuenca = !cuencaVal || p.cuenca === cuencaVal;
                let matchFecha = !fechaVal || (p.fecha_creacion && p.fecha_creacion.startsWith(fechaVal));
                let matchCaracterizado = caracterizadoVal === "" || p.tiene_caracterizacion == caracterizadoVal;
                let matchMypime = mypimeVal === "" || p.mypime == mypimeVal;

                let matchActividad = true;
                if (actividadVal) {
                    if (p.categorias_ids) {
                        const catIds = p.categorias_ids.split(\',\');
                        matchActividad = catIds.includes(actividadVal);
                    } else {
                        matchActividad = false;
                    }
                }

                return matchNombre && matchVereda && matchCuenca && matchFecha && matchCaracterizado && 
                       matchMypime && matchActividad;
            });';

$content = str_replace($exportFiltersTarget, $exportFiltersReplacement, $content, $count11a);
if ($count11a === 0) {
    $exportFiltersTargetCRLF = str_replace("\n", "\r\n", $exportFiltersTarget);
    $exportFiltersReplacementCRLF = str_replace("\n", "\r\n", $exportFiltersReplacement);
    $content = str_replace($exportFiltersTargetCRLF, $exportFiltersReplacementCRLF, $content, $count11a);
}
echo "Replacement 11a (exportToExcel filters): $count11a\n";

// 3. Replacement for the CSV generation inside exportToExcel()
$exportCsvTarget = '            // Create a standard CSV flat file (archivo plano)
            const headers = [
                \'Ranking\', \'Puntaje Total\', \'Puntaje Ajustado\', \'Elegible\', \'Estado Caracterización\', \'Fecha Inscripción\', 
                \'Nombre Completo\', \'Tipo Documento\', \'Número Documento\', \'Vereda\', \'Cuenca\', \'Teléfono\', 
                \'Correo Electrónico\', \'Nombre Predio\', \'Fecha Nacimiento\', \'Mypime\', \'Efectividad 2025\', \'Panaca\'
            ];

            // BOM (Byte Order Mark) forces Excel to read UTF-8 properly (accents)
            let csvString = \'\uFEFF\' + headers.join(\';\') + \'\r\n\';

            dataToExport.forEach(p => {
                const puntaje = (p.tiene_caracterizacion == 1 && p.puntaje !== null) ? p.puntaje : null;
                const estado = !p.tiene_caracterizacion ? \'No Caracterizado\' : \'Caracterizado\';
                const elegible = puntaje === null ? \'-\' : (puntaje >= 60 ? \'Sí\' : \'No\');

                const vNorm = normalizeVereda(p.vereda);
                const countVereda = registeredVeredaCounts[vNorm] || 1;
                const puntajeAjustado = puntaje !== null ? (puntaje * (1 + 1 / countVereda)) : null;
                const puntajeAjustadoStr = puntajeAjustado !== null ? 
                    (Number.isInteger(puntajeAjustado) ? puntajeAjustado : puntajeAjustado.toFixed(2)) : 
                    \'N/A\';

                const row = [
                    p.ranking || \'-\',
                    puntaje !== null ? puntaje : \'N/A\',
                    puntajeAjustadoStr,
                    elegible,
                    estado,
                    p.fecha_creacion || \'-\',
                    `"${p.nombre_completo || \'-\'}"`,
                    p.tipo_documento || \'-\',
                    p.numero_documento || \'-\',
                    `"${p.vereda || \'-\'}"`,
                    `"${p.cuenca || \'-\'}"`,
                    p.telefono || \'-\',
                    `"${p.correo_electronico || \'-\'}"`,
                    `"${p.nombre_predio || \'-\'}"`,
                    p.fecha_nacimiento || \'-\',
                    p.mypime == 1 ? \'Sí\' : \'No\',
                    p.efectividad_2025 == 1 ? \'Sí\' : \'No\',
                    p.panaca == 1 ? \'Sí\' : \'No\'
                ];';

$exportCsvReplacement = '            // Create a standard CSV flat file (archivo plano)
            const headers = [
                \'Ranking\', \'Puntaje Total\', \'Puntaje Ajustado\', \'Estado Caracterización\', \'Fecha Inscripción\', 
                \'Nombre Completo\', \'Tipo Documento\', \'Número Documento\', \'Vereda\', \'Cuenca\', \'Teléfono\', 
                \'Correo Electrónico\', \'Nombre Predio\', \'Fecha Nacimiento\', \'Mypime\', \'Efectividad 2025\', \'Panaca\'
            ];

            // BOM (Byte Order Mark) forces Excel to read UTF-8 properly (accents)
            let csvString = \'\uFEFF\' + headers.join(\';\') + \'\r\n\';

            dataToExport.forEach(p => {
                const puntaje = (p.tiene_caracterizacion == 1 && p.puntaje !== null) ? p.puntaje : null;
                const estado = !p.tiene_caracterizacion ? \'No Caracterizado\';

                const vNorm = normalizeVereda(p.vereda);
                const countVereda = registeredVeredaCounts[vNorm] || 1;
                const puntajeAjustado = puntaje !== null ? (puntaje * (1 + 1 / countVereda)) : null;
                const puntajeAjustadoStr = puntajeAjustado !== null ? 
                    (Number.isInteger(puntajeAjustado) ? puntajeAjustado : puntajeAjustado.toFixed(2)) : 
                    \'N/A\';

                const row = [
                    p.ranking || \'-\',
                    puntaje !== null ? puntaje : \'N/A\',
                    puntajeAjustadoStr,
                    estado,
                    p.fecha_creacion || \'-\',
                    `"${p.nombre_completo || \'-\'}"`,
                    p.tipo_documento || \'-\',
                    p.numero_documento || \'-\',
                    `"${p.vereda || \'-\'}"`,
                    `"${p.cuenca || \'-\'}"`,
                    p.telefono || \'-\',
                    `"${p.correo_electronico || \'-\'}"`,
                    `"${p.nombre_predio || \'-\'}"`,
                    p.fecha_nacimiento || \'-\',
                    p.mypime == 1 ? \'Sí\' : \'No\',
                    p.efectividad_2025 == 1 ? \'Sí\' : \'No\',
                    p.panaca == 1 ? \'Sí\' : \'No\'
                ];';

// Wait, let's look at a potential error in $exportCsvReplacement:
// In the original:
// const estado = !p.tiene_caracterizacion ? 'No Caracterizado' : 'Caracterizado';
// In my $exportCsvReplacement, I wrote:
// const estado = !p.tiene_caracterizacion ? 'No Caracterizado';
// This is missing the else branch `: 'Caracterizado'`!
// That would be a syntax error in JS: `const estado = !p.tiene_caracterizacion ? 'No Caracterizado';`
// Let's fix that!
$exportCsvReplacement = '            // Create a standard CSV flat file (archivo plano)
            const headers = [
                \'Ranking\', \'Puntaje Total\', \'Puntaje Ajustado\', \'Estado Caracterización\', \'Fecha Inscripción\', 
                \'Nombre Completo\', \'Tipo Documento\', \'Número Documento\', \'Vereda\', \'Cuenca\', \'Teléfono\', 
                \'Correo Electrónico\', \'Nombre Predio\', \'Fecha Nacimiento\', \'Mypime\', \'Efectividad 2025\', \'Panaca\'
            ];

            // BOM (Byte Order Mark) forces Excel to read UTF-8 properly (accents)
            let csvString = \'\uFEFF\' + headers.join(\';\') + \'\r\n\';

            dataToExport.forEach(p => {
                const puntaje = (p.tiene_caracterizacion == 1 && p.puntaje !== null) ? p.puntaje : null;
                const estado = !p.tiene_caracterizacion ? \'No Caracterizado\' : \'Caracterizado\';

                const vNorm = normalizeVereda(p.vereda);
                const countVereda = registeredVeredaCounts[vNorm] || 1;
                const puntajeAjustado = puntaje !== null ? (puntaje * (1 + 1 / countVereda)) : null;
                const puntajeAjustadoStr = puntajeAjustado !== null ? 
                    (Number.isInteger(puntajeAjustado) ? puntajeAjustado : puntajeAjustado.toFixed(2)) : 
                    \'N/A\';

                const row = [
                    p.ranking || \'-\',
                    puntaje !== null ? puntaje : \'N/A\',
                    puntajeAjustadoStr,
                    estado,
                    p.fecha_creacion || \'-\',
                    `"${p.nombre_completo || \'-\'}"`,
                    p.tipo_documento || \'-\',
                    p.numero_documento || \'-\',
                    `"${p.vereda || \'-\'}"`,
                    `"${p.cuenca || \'-\'}"`,
                    p.telefono || \'-\',
                    `"${p.correo_electronico || \'-\'}"`,
                    `"${p.nombre_predio || \'-\'}"`,
                    p.fecha_nacimiento || \'-\',
                    p.mypime == 1 ? \'Sí\' : \'No\',
                    p.efectividad_2025 == 1 ? \'Sí\' : \'No\',
                    p.panaca == 1 ? \'Sí\' : \'No\'
                ];';

$content = str_replace($exportCsvTarget, $exportCsvReplacement, $content, $count11b);
if ($count11b === 0) {
    $exportCsvTargetCRLF = str_replace("\n", "\r\n", $exportCsvTarget);
    $exportCsvReplacementCRLF = str_replace("\n", "\r\n", $exportCsvReplacement);
    $content = str_replace($exportCsvTargetCRLF, $exportCsvReplacementCRLF, $content, $count11b);
}
echo "Replacement 11b (exportToExcel CSV generation): $count11b\n";

file_put_contents($file, $content);
echo "All done!\n";
