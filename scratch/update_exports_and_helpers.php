<?php
$file = __DIR__ . '/../productores_registrados.html';
if (!file_exists($file)) {
    die("File not found\n");
}

$content = file_get_contents($file);

// 1. Declare registeredVeredaCounts and normalizeVereda globally at the script start
$globalTarget = '    <script>
        let allProducers = [];
        let completenessReport = {};
        let cuencaChartInstance = null;
        let veredaChartInstance = null;';

$globalReplacement = '    <script>
        let allProducers = [];
        let completenessReport = {};
        let cuencaChartInstance = null;
        let veredaChartInstance = null;
        let registeredVeredaCounts = {};

        const normalizeVereda = (vereda) => {
            if (!vereda) return \'\';
            return vereda
                .trim()
                .toUpperCase()
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .replace(/\s+/g, \' \');
        };';

$newContent = str_replace($globalTarget, $globalReplacement, $content, $count1);
if ($count1 === 0) {
    // Try with CRLF
    $globalTargetCRLF = str_replace("\n", "\r\n", $globalTarget);
    $globalReplacementCRLF = str_replace("\n", "\r\n", $globalReplacement);
    $newContent = str_replace($globalTargetCRLF, $globalReplacementCRLF, $content, $count1);
}
echo "Replacement 1 (Global variables/helper): $count1\n";
if ($count1 > 0) {
    $content = $newContent;
}

// 2. Populate registeredVeredaCounts globally after allProducers is fetched
$loadTarget = '                if (prodResult.success) {
                    allProducers = prodResult.data;
                    if (compResult.success) {
                        completenessReport = compResult.data;
                    }';

$loadReplacement = '                if (prodResult.success) {
                    allProducers = prodResult.data;
                    
                    // Build vereda counts map globally
                    registeredVeredaCounts = {};
                    allProducers.forEach(p => {
                        const vNorm = normalizeVereda(p.vereda);
                        if (vNorm) {
                            registeredVeredaCounts[vNorm] = (registeredVeredaCounts[vNorm] || 0) + 1;
                        }
                    });

                    if (compResult.success) {
                        completenessReport = compResult.data;
                    }';

$newContent = str_replace($loadTarget, $loadReplacement, $content, $count2);
if ($count2 === 0) {
    $loadTargetCRLF = str_replace("\n", "\r\n", $loadTarget);
    $loadReplacementCRLF = str_replace("\n", "\r\n", $loadReplacement);
    $newContent = str_replace($loadTargetCRLF, $loadReplacementCRLF, $content, $count2);
}
echo "Replacement 2 (Populate global map): $count2\n";
if ($count2 > 0) {
    $content = $newContent;
}

// 3. Remove local definitions from renderTable
$renderTableLocalTarget = '        function renderTable(dataToDraw) {
            const tbody = document.getElementById(\'productores-tbody\');
            tbody.innerHTML = \'\'; // Clear loading/existing text

            // Helper to normalize veredas
            const normalizeVereda = (vereda) => {
                if (!vereda) return \'\';
                return vereda
                    .trim()
                    .toUpperCase()
                    .normalize("NFD")
                    .replace(/[\u0300-\u036f]/g, "")
                    .replace(/\s+/g, \' \');
            };

            // Build vereda counts map from allProducers
            const registeredVeredaCounts = {};
            allProducers.forEach(p => {
                const vNorm = normalizeVereda(p.vereda);
                if (vNorm) {
                    registeredVeredaCounts[vNorm] = (registeredVeredaCounts[vNorm] || 0) + 1;
                }
            });';

$renderTableLocalReplacement = '        function renderTable(dataToDraw) {
            const tbody = document.getElementById(\'productores-tbody\');
            tbody.innerHTML = \'\'; // Clear loading/existing text';

$newContent = str_replace($renderTableLocalTarget, $renderTableLocalReplacement, $content, $count3);
if ($count3 === 0) {
    $renderTableLocalTargetCRLF = str_replace("\n", "\r\n", $renderTableLocalTarget);
    $renderTableLocalReplacementCRLF = str_replace("\n", "\r\n", $renderTableLocalReplacement);
    $newContent = str_replace($renderTableLocalTargetCRLF, $renderTableLocalReplacementCRLF, $content, $count3);
}
echo "Replacement 3 (Remove locals in renderTable): $count3\n";
if ($count3 > 0) {
    $content = $newContent;
}

// 4. Update exportToExcel CSV headers and row construction
$exportTarget = '            // Create a standard CSV flat file (archivo plano)
            const headers = [
                \'Ranking\', \'Puntaje Total\', \'Elegible\', \'Estado Caracterización\', \'Fecha Inscripción\', 
                \'Nombre Completo\', \'Tipo Documento\', \'Número Documento\', \'Vereda\', \'Cuenca\', \'Teléfono\', 
                \'Correo Electrónico\', \'Nombre Predio\', \'Fecha Nacimiento\', \'Mypime\', \'Efectividad 2025\', \'Panaca\'
            ];

            // BOM (Byte Order Mark) forces Excel to read UTF-8 properly (accents)
            let csvString = \'\uFEFF\' + headers.join(\';\') + \'\r\n\';

            dataToExport.forEach(p => {
                const puntaje = (p.tiene_caracterizacion == 1 && p.puntaje !== null) ? p.puntaje : null;
                const estado = !p.tiene_caracterizacion ? \'No Caracterizado\' : \'Caracterizado\';
                const elegible = puntaje === null ? \'-\' : (puntaje >= 60 ? \'Sí\' : \'No\');

                const row = [
                    p.ranking || \'-\',
                    puntaje !== null ? puntaje : \'N/A\',
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

$exportReplacement = '            // Create a standard CSV flat file (archivo plano)
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

$newContent = str_replace($exportTarget, $exportReplacement, $content, $count4);
if ($count4 === 0) {
    $exportTargetCRLF = str_replace("\n", "\r\n", $exportTarget);
    $exportReplacementCRLF = str_replace("\n", "\r\n", $exportReplacement);
    $newContent = str_replace($exportTargetCRLF, $exportReplacementCRLF, $content, $count4);
}
echo "Replacement 4 (exportToExcel CSV headers & row): $count4\n";
if ($count4 > 0) {
    $content = $newContent;
}

// 5. Update downloadScoresWithJustifications mapping rows
$justificationTarget = '                // Map data to flat excel rows
                const rows = data.map((p, generalIdx) => {
                    const veredaRank = rankingVeredaMap[p.id] || \'-\';
                    const generalRank = generalIdx + 1;
                    
                    const row = {
                        "Puesto General": generalRank,
                        "Ranking Vereda": veredaRank,
                        "Productor": p.nombre_completo || \'-\',
                        "Vereda": p.vereda || \'-\',
                        "Estado Caracterización": p.is_complete ? \'Completa\' : \'Incompleta\',
                        "Puntaje Total": p.puntaje,
                        "C1. Social (Total/20)": p.scores?.puntaje_social ?? 0,';

$justificationReplacement = '                // Map data to flat excel rows
                const rows = data.map((p, generalIdx) => {
                    const veredaRank = rankingVeredaMap[p.id] || \'-\';
                    const generalRank = generalIdx + 1;

                    const puntaje = p.puntaje !== null ? parseFloat(p.puntaje) : null;
                    const vNorm = normalizeVereda(p.vereda);
                    const countVereda = registeredVeredaCounts[vNorm] || 1;
                    const puntajeAjustado = puntaje !== null ? (puntaje * (1 + 1 / countVereda)) : null;
                    const puntajeAjustadoVal = puntajeAjustado !== null ? 
                        (Number.isInteger(puntajeAjustado) ? puntajeAjustado : parseFloat(puntajeAjustado.toFixed(2))) : 
                        null;
                    
                    const row = {
                        "Puesto General": generalRank,
                        "Ranking Vereda": veredaRank,
                        "Productor": p.nombre_completo || \'-\',
                        "Vereda": p.vereda || \'-\',
                        "Estado Caracterización": p.is_complete ? \'Completa\' : \'Incompleta\',
                        "Puntaje Total": p.puntaje,
                        "Puntaje Ajustado": puntajeAjustadoVal,
                        "C1. Social (Total/20)": p.scores?.puntaje_social ?? 0,';

$newContent = str_replace($justificationTarget, $justificationReplacement, $content, $count5);
if ($count5 === 0) {
    $justificationTargetCRLF = str_replace("\n", "\r\n", $justificationTarget);
    $justificationReplacementCRLF = str_replace("\n", "\r\n", $justificationReplacement);
    $newContent = str_replace($justificationTargetCRLF, $justificationReplacementCRLF, $content, $count5);
}
echo "Replacement 5 (downloadScores Excel mapping): $count5\n";
if ($count5 > 0) {
    $content = $newContent;
}

file_put_contents($file, $content);
echo "All done!\n";
