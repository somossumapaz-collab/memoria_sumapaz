<?php
$file = __DIR__ . '/../productores_registrados.html';
if (!file_exists($file)) {
    die("File not found\n");
}

$content = file_get_contents($file);

// 1. Inject CSS styles for sortable table headers and top-150-row highlight
$cssTarget = '        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>';

$cssReplacement = '        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
        th.sortable:hover {
            background-color: rgba(68, 79, 47, 0.1) !important;
        }
        .top-150-row {
            background-color: #E8F5E9 !important; /* Elegant light green */
            border-left: 4px solid #2A9D8F;
        }
        .top-150-row:hover {
            background-color: #C8E6C9 !important; /* Slightly darker light green on hover */
        }
    </style>';

$content = str_replace($cssTarget, $cssReplacement, $content, $count1);
if ($count1 === 0) {
    $cssTargetCRLF = str_replace("\n", "\r\n", $cssTarget);
    $cssReplacementCRLF = str_replace("\n", "\r\n", $cssReplacement);
    $content = str_replace($cssTargetCRLF, $cssReplacementCRLF, $content, $count1);
}
echo "Replacement 1 (CSS injections): $count1\n";


// 2. Remove the filters HTML blocks
$filtersTarget = '                <div style="flex: 1; min-width: 160px;">
                    <label for="filter-efectividad"
                        style="font-weight: 600; display: block; margin-bottom: 0.5rem; color: #444F2F;">Efectividad 2025</label>
                    <select id="filter-efectividad"
                        style="width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 8px;">
                        <option value="">Todos</option>
                        <option value="1">Sí</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div style="flex: 1; min-width: 150px;">
                    <label for="filter-panaca"
                        style="font-weight: 600; display: block; margin-bottom: 0.5rem; color: #444F2F;">Panaca</label>
                    <select id="filter-panaca"
                        style="width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 8px;">
                        <option value="">Todos</option>
                        <option value="1">Sí</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div style="flex: 1; min-width: 160px;">
                    <label for="filter-elegibilidad"
                        style="font-weight: 600; display: block; margin-bottom: 0.5rem; color: #444F2F;">Elegibilidad</label>
                    <select id="filter-elegibilidad"
                        style="width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 8px;">
                        <option value="">Todos</option>
                        <option value="si">Elegible (>= 60)</option>
                        <option value="no">No Elegible (< 60)</option>
                        <option value="null">Sin caracterizar</option>
                    </select>
                </div>';

$filtersReplacement = '';

$content = str_replace($filtersTarget, $filtersReplacement, $content, $count2);
if ($count2 === 0) {
    $filtersTargetCRLF = str_replace("\n", "\r\n", $filtersTarget);
    $content = str_replace($filtersTargetCRLF, $filtersReplacement, $content, $count2);
}
echo "Replacement 2 (Filters HTML removal): $count2\n";


// 3. Update Table Headers (removing Elegible column, adding sort triggers)
$headerTarget = '                    <tr>
                        <th style="text-align: center; width: 90px;">Ranking</th>
                        <th>Productor</th>
                        <th>Vereda</th>
                        <th style="text-align: center;">Estado Caracterización</th>
                        <th style="text-align: center; width: 100px;">Puntaje</th>
                        <th style="text-align: center; width: 120px;">Puntaje Ajustado</th>
                        <th style="text-align: center; width: 100px;">Elegible</th>
                        <th style="text-align: center; width: 120px;">Acción</th>
                    </tr>';

$headerReplacement = '                    <tr>
                        <th style="text-align: center; width: 90px;">Ranking</th>
                        <th>Productor</th>
                        <th>Vereda</th>
                        <th style="text-align: center;">Estado Caracterización</th>
                        <th class="sortable" style="text-align: center; width: 110px; cursor: pointer; user-select: none;" onclick="toggleSort(\'puntaje\')">Puntaje<span id="sort-icon-puntaje">▼</span></th>
                        <th class="sortable" style="text-align: center; width: 140px; cursor: pointer; user-select: none;" onclick="toggleSort(\'puntaje_ajustado\')">Puntaje Ajustado<span id="sort-icon-puntaje-ajustado"></span></th>
                        <th style="text-align: center; width: 120px;">Acción</th>
                    </tr>';

$content = str_replace($headerTarget, $headerReplacement, $content, $count3);
if ($count3 === 0) {
    $headerTargetCRLF = str_replace("\n", "\r\n", $headerTarget);
    $headerReplacementCRLF = str_replace("\n", "\r\n", $headerReplacement);
    $content = str_replace($headerTargetCRLF, $headerReplacementCRLF, $content, $count3);
}
echo "Replacement 3 (Table Headers update): $count3\n";


// 4. Add global sorting variables and functions
$globalTarget = '        let registeredVeredaCounts = {};

        const normalizeVereda = (vereda) => {';

$globalReplacement = '        let registeredVeredaCounts = {};
        let currentSortField = \'puntaje\'; // \'puntaje\' or \'puntaje_ajustado\'
        let currentSortOrder = \'desc\'; // \'desc\' or \'asc\'

        function toggleSort(field) {
            if (currentSortField === field) {
                currentSortOrder = currentSortOrder === \'desc\' ? \'asc\' : \'desc\';
            } else {
                currentSortField = field;
                currentSortOrder = \'desc\';
            }
            updateSortIndicators();
            applyFilters();
        }

        function updateSortIndicators() {
            const puntajeIndicator = document.getElementById(\'sort-icon-puntaje\');
            const adjustedIndicator = document.getElementById(\'sort-icon-puntaje-ajustado\');
            if (puntajeIndicator && adjustedIndicator) {
                if (currentSortField === \'puntaje\') {
                    puntajeIndicator.innerHTML = currentSortOrder === \'desc\' ? \' ▼\' : \' ▲\';
                    adjustedIndicator.innerHTML = \'\';
                } else if (currentSortField === \'puntaje_ajustado\') {
                    adjustedIndicator.innerHTML = currentSortOrder === \'desc\' ? \' ▼\' : \' ▲\';
                    puntajeIndicator.innerHTML = \'\';
                }
            }
        }

        const normalizeVereda = (vereda) => {';

$content = str_replace($globalTarget, $globalReplacement, $content, $count4);
if ($count4 === 0) {
    $globalTargetCRLF = str_replace("\n", "\r\n", $globalTarget);
    $globalReplacementCRLF = str_replace("\n", "\r\n", $globalReplacement);
    $content = str_replace($globalTargetCRLF, $globalReplacementCRLF, $content, $count4);
}
echo "Replacement 4 (Global sorting helper): $count4\n";


// 5. Initialize sort indicators on DOMContentLoaded
$domTarget = '        document.addEventListener(\'DOMContentLoaded\', async () => {
            const tbody = document.getElementById(\'productores-tbody\');';

$domReplacement = '        document.addEventListener(\'DOMContentLoaded\', async () => {
            const tbody = document.getElementById(\'productores-tbody\');
            updateSortIndicators();';

$content = str_replace($domTarget, $domReplacement, $content, $count5);
if ($count5 === 0) {
    $domTargetCRLF = str_replace("\n", "\r\n", $domTarget);
    $domReplacementCRLF = str_replace("\n", "\r\n", $domReplacement);
    $content = str_replace($domTargetCRLF, $domReplacementCRLF, $content, $count5);
}
echo "Replacement 5 (DOMContentLoaded initialization): $count5\n";


// 6. Remove DOMContentLoaded listeners and reset buttons
$listenersTarget = '            filterCaracterizado.addEventListener(\'change\', applyFilters);
            filterMypime.addEventListener(\'change\', applyFilters);
            document.getElementById(\'filter-efectividad\').addEventListener(\'change\', applyFilters);
            document.getElementById(\'filter-panaca\').addEventListener(\'change\', applyFilters);
            document.getElementById(\'filter-elegibilidad\').addEventListener(\'change\', applyFilters);
            document.getElementById(\'filter-actividad\').addEventListener(\'change\', applyFilters);

            btnReset.addEventListener(\'click\', () => {
                filterNombre.value = \'\';
                filterVereda.value = \'\';
                if (filterCuenca) filterCuenca.value = \'\';
                filterFecha.value = \'\';
                filterCaracterizado.value = \'\';
                filterMypime.value = \'\';
                document.getElementById(\'filter-efectividad\').value = \'\';
                document.getElementById(\'filter-panaca\').value = \'\';
                document.getElementById(\'filter-elegibilidad\').value = \'\';
                document.getElementById(\'filter-actividad\').value = \'\';
                renderTable(allProducers);
            });';

$listenersReplacement = '            filterCaracterizado.addEventListener(\'change\', applyFilters);
            filterMypime.addEventListener(\'change\', applyFilters);
            document.getElementById(\'filter-actividad\').addEventListener(\'change\', applyFilters);

            btnReset.addEventListener(\'click\', () => {
                filterNombre.value = \'\';
                filterVereda.value = \'\';
                if (filterCuenca) filterCuenca.value = \'\';
                filterFecha.value = \'\';
                filterCaracterizado.value = \'\';
                filterMypime.value = \'\';
                document.getElementById(\'filter-actividad\').value = \'\';
                renderTable(allProducers);
            });';

$content = str_replace($listenersTarget, $listenersReplacement, $content, $count6);
if ($count6 === 0) {
    $listenersTargetCRLF = str_replace("\n", "\r\n", $listenersTarget);
    $listenersReplacementCRLF = str_replace("\n", "\r\n", $listenersReplacement);
    $content = str_replace($listenersTargetCRLF, $listenersReplacementCRLF, $content, $count6);
}
echo "Replacement 6 (EventListeners/Reset buttons cleanup): $count6\n";


// 7. Clean up passesGlobalFilters()
$passesTarget = '        function passesGlobalFilters(numDoc, tipoNumDocStr = null) {
            const nombreVal = document.getElementById(\'filter-nombre\').value.toLowerCase();
            const veredaVal = document.getElementById(\'filter-vereda\').value;
            const cuencaVal = document.getElementById(\'filter-cuenca\') ? document.getElementById(\'filter-cuenca\').value : \'\';
            const fechaVal = document.getElementById(\'filter-fecha\').value;
            const caracterizadoVal = document.getElementById(\'filter-caracterizado\').value;
            const mypimeVal = document.getElementById(\'filter-mypime\').value;
            const efectividadVal = document.getElementById(\'filter-efectividad\').value;
            const panacaVal = document.getElementById(\'filter-panaca\').value;
            const elegibilidadVal = document.getElementById(\'filter-elegibilidad\').value;
            const actividadVal = document.getElementById(\'filter-actividad\').value;

            let fullP = null;
            if (numDoc) {
                fullP = allProducers.find(p => p.numero_documento === numDoc);
            } else if (tipoNumDocStr) {
                fullP = allProducers.find(p => (p.tipo_documento + \' \' + p.numero_documento) === tipoNumDocStr);
            }

            if (!fullP) return true; 

            let matchNombre = !nombreVal || fullP.nombre_completo.toLowerCase().includes(nombreVal);
            let matchVereda = !veredaVal || fullP.vereda === veredaVal;
            let matchCuenca = !cuencaVal || fullP.cuenca === cuencaVal;
            let matchFecha = !fechaVal || (fullP.fecha_creacion && fullP.fecha_creacion.startsWith(fechaVal));
            let matchCaracterizado = caracterizadoVal === "" || fullP.tiene_caracterizacion == caracterizadoVal;
            let matchMypime = mypimeVal === "" || fullP.mypime == mypimeVal;
            let matchEfectividad = efectividadVal === "" || fullP.efectividad_2025 == efectividadVal;
            let matchPanaca = panacaVal === "" || fullP.panaca == panacaVal;

            let matchElegibilidad = true;
            if (elegibilidadVal === \'si\') {
                matchElegibilidad = (fullP.tiene_caracterizacion == 1 && fullP.puntaje >= 60);
            } else if (elegibilidadVal === \'no\') {
                matchElegibilidad = (fullP.tiene_caracterizacion == 1 && fullP.puntaje < 60);
            } else if (elegibilidadVal === \'null\') {
                matchElegibilidad = (fullP.tiene_caracterizacion == 0);
            }

            let matchActividad = true;
            if (actividadVal) {
                if (fullP.categorias_ids) {
                    const catIds = fullP.categorias_ids.split(\',\');
                    matchActividad = catIds.includes(actividadVal);
                } else {
                    matchActividad = false;
                }
            }

            return matchNombre && matchVereda && matchCuenca && matchFecha && matchCaracterizado && 
                   matchMypime && matchEfectividad && matchPanaca && matchElegibilidad && matchActividad;
        }';

$passesReplacement = '        function passesGlobalFilters(numDoc, tipoNumDocStr = null) {
            const nombreVal = document.getElementById(\'filter-nombre\').value.toLowerCase();
            const veredaVal = document.getElementById(\'filter-vereda\').value;
            const cuencaVal = document.getElementById(\'filter-cuenca\') ? document.getElementById(\'filter-cuenca\').value : \'\';
            const fechaVal = document.getElementById(\'filter-fecha\').value;
            const caracterizadoVal = document.getElementById(\'filter-caracterizado\').value;
            const mypimeVal = document.getElementById(\'filter-mypime\').value;
            const actividadVal = document.getElementById(\'filter-actividad\').value;

            let fullP = null;
            if (numDoc) {
                fullP = allProducers.find(p => p.numero_documento === numDoc);
            } else if (tipoNumDocStr) {
                fullP = allProducers.find(p => (p.tipo_documento + \' \' + p.numero_documento) === tipoNumDocStr);
            }

            if (!fullP) return true; 

            let matchNombre = !nombreVal || fullP.nombre_completo.toLowerCase().includes(nombreVal);
            let matchVereda = !veredaVal || fullP.vereda === veredaVal;
            let matchCuenca = !cuencaVal || fullP.cuenca === cuencaVal;
            let matchFecha = !fechaVal || (fullP.fecha_creacion && fullP.fecha_creacion.startsWith(fechaVal));
            let matchCaracterizado = caracterizadoVal === "" || fullP.tiene_caracterizacion == caracterizadoVal;
            let matchMypime = mypimeVal === "" || fullP.mypime == mypimeVal;

            let matchActividad = true;
            if (actividadVal) {
                if (fullP.categorias_ids) {
                    const catIds = fullP.categorias_ids.split(\',\');
                    matchActividad = catIds.includes(actividadVal);
                } else {
                    matchActividad = false;
                }
            }

            return matchNombre && matchVereda && matchCuenca && matchFecha && matchCaracterizado && 
                   matchMypime && matchActividad;
        }';

$content = str_replace($passesTarget, $passesReplacement, $content, $count7);
if ($count7 === 0) {
    $passesTargetCRLF = str_replace("\n", "\r\n", $passesTarget);
    $passesReplacementCRLF = str_replace("\n", "\r\n", $passesReplacement);
    $content = str_replace($passesTargetCRLF, $passesReplacementCRLF, $content, $count7);
}
echo "Replacement 7 (passesGlobalFilters cleanup): $count7\n";


// 8. Clean up applyFilters()
$applyTarget = '        function applyFilters() {
            const nombreVal = document.getElementById(\'filter-nombre\').value.toLowerCase();
            const veredaVal = document.getElementById(\'filter-vereda\').value;
            const cuencaVal = document.getElementById(\'filter-cuenca\') ? document.getElementById(\'filter-cuenca\').value : \'\';
            const fechaVal = document.getElementById(\'filter-fecha\').value; // format: YYYY-MM-DD
            const caracterizadoVal = document.getElementById(\'filter-caracterizado\').value;
            const mypimeVal = document.getElementById(\'filter-mypime\').value;
            const efectividadVal = document.getElementById(\'filter-efectividad\').value;
            const panacaVal = document.getElementById(\'filter-panaca\').value;
            const elegibilidadVal = document.getElementById(\'filter-elegibilidad\').value;
            const actividadVal = document.getElementById(\'filter-actividad\').value;

            const filteredData = allProducers.filter(p => {
                let matchNombre = true;
                let matchVereda = true;
                let matchCuenca = true;
                let matchFecha = true;
                let matchCaracterizado = true;
                let matchMypime = true;
                let matchEfectividad = true;
                let matchPanaca = true;
                let matchElegibilidad = true;
                let matchActividad = true;

                if (nombreVal) {
                    matchNombre = p.nombre_completo.toLowerCase().includes(nombreVal);
                }

                if (veredaVal) {
                    matchVereda = p.vereda === veredaVal;
                }

                if (cuencaVal) {
                    matchCuenca = p.cuenca === cuencaVal;
                }

                if (fechaVal && p.fecha_creacion) {
                    matchFecha = p.fecha_creacion.startsWith(fechaVal);
                }

                if (caracterizadoVal !== "") {
                    matchCaracterizado = p.tiene_caracterizacion == caracterizadoVal;
                }

                if (mypimeVal !== "") {
                    matchMypime = p.mypime == mypimeVal;
                }

                if (efectividadVal !== "") {
                    matchEfectividad = p.efectividad_2025 == efectividadVal;
                }

                if (panacaVal !== "") {
                    matchPanaca = p.panaca == panacaVal;
                }

                if (elegibilidadVal === \'si\') {
                    matchElegibilidad = (p.tiene_caracterizacion == 1 && p.puntaje >= 60);
                } else if (elegibilidadVal === \'no\') {
                    matchElegibilidad = (p.tiene_caracterizacion == 1 && p.puntaje < 60);
                } else if (elegibilidadVal === \'null\') {
                    matchElegibilidad = (p.tiene_caracterizacion == 0);
                }

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

$applyReplacement = '        function applyFilters() {
            const nombreVal = document.getElementById(\'filter-nombre\').value.toLowerCase();
            const veredaVal = document.getElementById(\'filter-vereda\').value;
            const cuencaVal = document.getElementById(\'filter-cuenca\') ? document.getElementById(\'filter-cuenca\').value : \'\';
            const fechaVal = document.getElementById(\'filter-fecha\').value; // format: YYYY-MM-DD
            const caracterizadoVal = document.getElementById(\'filter-caracterizado\').value;
            const mypimeVal = document.getElementById(\'filter-mypime\').value;
            const actividadVal = document.getElementById(\'filter-actividad\').value;

            const filteredData = allProducers.filter(p => {
                let matchNombre = true;
                let matchVereda = true;
                let matchCuenca = true;
                let matchFecha = true;
                let matchCaracterizado = true;
                let matchMypime = true;
                let matchActividad = true;

                if (nombreVal) {
                    matchNombre = p.nombre_completo.toLowerCase().includes(nombreVal);
                }

                if (veredaVal) {
                    matchVereda = p.vereda === veredaVal;
                }

                if (cuencaVal) {
                    matchCuenca = p.cuenca === cuencaVal;
                }

                if (fechaVal && p.fecha_creacion) {
                    matchFecha = p.fecha_creacion.startsWith(fechaVal);
                }

                if (caracterizadoVal !== "") {
                    matchCaracterizado = p.tiene_caracterizacion == caracterizadoVal;
                }

                if (mypimeVal !== "") {
                    matchMypime = p.mypime == mypimeVal;
                }

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

$content = str_replace($applyTarget, $applyReplacement, $content, $count8);
if ($count8 === 0) {
    $applyTargetCRLF = str_replace("\n", "\r\n", $applyTarget);
    $applyReplacementCRLF = str_replace("\n", "\r\n", $applyReplacement);
    $content = str_replace($applyTargetCRLF, $applyReplacementCRLF, $content, $count8);
}
echo "Replacement 8 (applyFilters cleanup): $count8\n";


// 9. Update renderTable sorting logic & row rendering (highlighting top 150, removing Elegible cell and detail colspan)
$sortTarget = '            // Create a copy for ranking sort so we don\'t mutate the original order of the second table
            const rankingSortedData = [...dataToDraw];
            rankingSortedData.sort((a, b) => {
                if (a.ranking === null && b.ranking === null) return 0;
                if (a.ranking === null) return 1;
                if (b.ranking === null) return -1;
                return a.ranking - b.ranking;
            });';

$sortReplacement = '            // Create a copy for ranking sort so we don\'t mutate the original order of the second table
            const rankingSortedData = [...dataToDraw];
            rankingSortedData.sort((a, b) => {
                const getVal = (item, field) => {
                    if (field === \'puntaje\') {
                        return item.tiene_caracterizacion == 1 && item.puntaje !== null ? parseFloat(item.puntaje) : -1;
                    } else if (field === \'puntaje_ajustado\') {
                        const puntaje = item.tiene_caracterizacion == 1 && item.puntaje !== null ? parseFloat(item.puntaje) : null;
                        if (puntaje === null) return -1;
                        const vNorm = normalizeVereda(item.vereda);
                        const countVereda = registeredVeredaCounts[vNorm] || 1;
                        return puntaje * (1 + 1 / countVereda);
                    }
                    return -1;
                };

                const valA = getVal(a, currentSortField);
                const valB = getVal(b, currentSortField);

                // Always put N/A (value = -1) at the bottom
                if (valA === -1 && valB === -1) return 0;
                if (valA === -1) return 1;
                if (valB === -1) return -1;

                if (currentSortOrder === \'asc\') {
                    return valA - valB;
                } else {
                    return valB - valA;
                }
            });';

$content = str_replace($sortTarget, $sortReplacement, $content, $count9);
if ($count9 === 0) {
    $sortTargetCRLF = str_replace("\n", "\r\n", $sortTarget);
    $sortReplacementCRLF = str_replace("\n", "\r\n", $sortReplacement);
    $content = str_replace($sortTargetCRLF, $sortReplacementCRLF, $content, $count9);
}
echo "Replacement 9 (renderTable sorting logic): $count9\n";


// 10. Table row rendering: removing Elegible and adding highlight class for top 150
$rowTarget = '                const rankingStr = productor.ranking ? `<strong>${productor.ranking}</strong>` : \'-\';
                const puntajeStr = puntaje !== null ? `<strong>${puntaje}</strong>` : \'N/A\';
                const colorPuntaje = (puntaje !== null) ? (puntaje >= 60 ? \'#2A9D8F\' : \'#e76f51\') : \'#999\';

                mainTr.innerHTML = `
                    <td style="text-align: center; font-size: 1.1rem; color: #444F2F;">${rankingStr}</td>
                    <td>
                        <div style="font-weight: 600; color: #444F2F;">${productor.nombre_completo}</div>
                        <div style="font-size: 0.8rem; color: #666; margin-top: 2px;">${productor.tipo_documento} ${productor.numero_documento}</div>
                    </td>
                    <td>${productor.vereda}</td>
                    <td style="text-align: center;">${estadoHtml}</td>
                    <td style="text-align: center; font-size: 1.15rem; color: ${colorPuntaje}; font-weight: bold;">${puntajeStr}</td>
                    <td style="text-align: center; font-size: 1.15rem; color: #444F2F; font-weight: bold;">${puntajeAjustadoStr}</td>
                    <td style="text-align: center;">${elegibleHtml}</td>
                    <td style="text-align: center; vertical-align: middle;">
                        <button class="btn-icon" style="background: transparent; border: none; color: #444F2F; cursor: pointer; padding: 0;" onclick="event.stopPropagation(); toggleRow(${productor.id})">
                            <svg id="arrow-${productor.id}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="width: 18px; height: 18px; transition: transform 0.2s;"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                    </td>
                `;

                const detailTr = document.createElement(\'tr\');
                detailTr.id = `detail-row-${productor.id}`;
                detailTr.className = \'detail-row\';
                detailTr.style.display = \'none\';
                detailTr.style.backgroundColor = \'#fdfdfd\';

                detailTr.innerHTML = `
                    <td colspan="8" style="padding: 1.5rem 2rem; border-top: none; border-bottom: 2px solid #e0dcd5;">';

$rowReplacement = '                const rankingStr = productor.ranking ? `<strong>${productor.ranking}</strong>` : \'-\';
                const puntajeStr = puntaje !== null ? `<strong>${puntaje}</strong>` : \'N/A\';
                const colorPuntaje = (puntaje !== null) ? (puntaje >= 60 ? \'#2A9D8F\' : \'#e76f51\') : \'#999\';

                if (productor.ranking !== null && productor.ranking <= 150) {
                    mainTr.classList.add(\'top-150-row\');
                }

                mainTr.innerHTML = `
                    <td style="text-align: center; font-size: 1.1rem; color: #444F2F;">${rankingStr}</td>
                    <td>
                        <div style="font-weight: 600; color: #444F2F;">${productor.nombre_completo}</div>
                        <div style="font-size: 0.8rem; color: #666; margin-top: 2px;">${productor.tipo_documento} ${productor.numero_documento}</div>
                    </td>
                    <td>${productor.vereda}</td>
                    <td style="text-align: center;">${estadoHtml}</td>
                    <td style="text-align: center; font-size: 1.15rem; color: ${colorPuntaje}; font-weight: bold;">${puntajeStr}</td>
                    <td style="text-align: center; font-size: 1.15rem; color: #444F2F; font-weight: bold;">${puntajeAjustadoStr}</td>
                    <td style="text-align: center; vertical-align: middle;">
                        <button class="btn-icon" style="background: transparent; border: none; color: #444F2F; cursor: pointer; padding: 0;" onclick="event.stopPropagation(); toggleRow(${productor.id})">
                            <svg id="arrow-${productor.id}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="width: 18px; height: 18px; transition: transform 0.2s;"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                    </td>
                `;

                const detailTr = document.createElement(\'tr\');
                detailTr.id = `detail-row-${productor.id}`;
                detailTr.className = \'detail-row\';
                detailTr.style.display = \'none\';
                detailTr.style.backgroundColor = \'#fdfdfd\';

                detailTr.innerHTML = `
                    <td colspan="7" style="padding: 1.5rem 2rem; border-top: none; border-bottom: 2px solid #e0dcd5;">';

$content = str_replace($rowTarget, $rowReplacement, $content, $count10);
if ($count10 === 0) {
    $rowTargetCRLF = str_replace("\n", "\r\n", $rowTarget);
    $rowReplacementCRLF = str_replace("\n", "\r\n", $rowReplacement);
    $content = str_replace($rowTargetCRLF, $rowReplacementCRLF, $content, $count10);
}
echo "Replacement 10 (Row render & colspan): $count10\n";


// 11. exportToExcel() cleanup
$exportTarget = '        function exportToExcel() {
            // Get currently filtered data (or all if no filters)
            const nombreVal = document.getElementById(\'filter-nombre\').value.toLowerCase();
            const veredaVal = document.getElementById(\'filter-vereda\').value;
            const cuencaVal = document.getElementById(\'filter-cuenca\') ? document.getElementById(\'filter-cuenca\').value : \'\';
            const fechaVal = document.getElementById(\'filter-fecha\').value;
            const caracterizadoVal = document.getElementById(\'filter-caracterizado\').value;
            const mypimeVal = document.getElementById(\'filter-mypime\').value;
            const efectividadVal = document.getElementById(\'filter-efectividad\').value;
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
            });

            if (dataToExport.length === 0) {
                alert(\'No hay datos para exportar.\');
                return;
            }

            // Sort by ranking
            dataToExport.sort((a, b) => {
                if (a.ranking === null && b.ranking === null) return 0;
                if (a.ranking === null) return 1;
                if (b.ranking === null) return -1;
                return a.ranking - b.ranking;
            });

            // Create a standard CSV flat file (archivo plano)
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
                const elegible = puntaje === null ? \'-\' : (puntaje >= 60 ? \'Sí\': \'No\');

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

$exportReplacement = '        function exportToExcel() {
            // Get currently filtered data (or all if no filters)
            const nombreVal = document.getElementById(\'filter-nombre\').value.toLowerCase();
            const veredaVal = document.getElementById(\'filter-vereda\').value;
            const cuencaVal = document.getElementById(\'filter-cuenca\') ? document.getElementById(\'filter-cuenca\').value : \'\';
            const fechaVal = document.getElementById(\'filter-fecha\').value;
            const caracterizadoVal = document.getElementById(\'filter-caracterizado\').value;
            const mypimeVal = document.getElementById(\'filter-mypime\').value;
            const actividadVal = document.getElementById(\'filter-actividad\').value;

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
            });

            if (dataToExport.length === 0) {
                alert(\'No hay datos para exportar.\');
                return;
            }

            // Sort by ranking
            dataToExport.sort((a, b) => {
                if (a.ranking === null && b.ranking === null) return 0;
                if (a.ranking === null) return 1;
                if (b.ranking === null) return -1;
                return a.ranking - b.ranking;
            });

            // Create a standard CSV flat file (archivo plano)
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

$content = str_replace($exportTarget, $exportReplacement, $content, $count11);
if ($count11 === 0) {
    $exportTargetCRLF = str_replace("\n", "\r\n", $exportTarget);
    $exportReplacementCRLF = str_replace("\n", "\r\n", $exportReplacement);
    $content = str_replace($exportTargetCRLF, $exportReplacementCRLF, $content, $count11);
}
echo "Replacement 11 (exportToExcel logic cleanup): $count11\n";


file_put_contents($file, $content);
echo "All done!\n";
