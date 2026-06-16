<?php
$file = __DIR__ . '/../productores_registrados.html';
if (!file_exists($file)) {
    die("File not found\n");
}

$content = file_get_contents($file);

// 1. Update global variables declaration
$globalTarget = '        let completenessReport = {};
        let cuencaChartInstance = null;
        let veredaChartInstance = null;';

$globalReplacement = '        let completenessReport = {};
        let veredaTotalChartInstance = null;
        let veredaNormalChartInstance = null;
        let veredaAjustadoChartInstance = null;';

$content = str_replace($globalTarget, $globalReplacement, $content, $count1);
if ($count1 === 0) {
    $globalTargetCRLF = str_replace("\n", "\r\n", $globalTarget);
    $globalReplacementCRLF = str_replace("\n", "\r\n", $globalReplacement);
    $content = str_replace($globalTargetCRLF, $globalReplacementCRLF, $content, $count1);
}
echo "Replacement 1 (Global variables): $count1\n";


// 2. Update HTML Charts Section
$htmlTarget = '        <!-- Charts Section -->
        <div style="display: flex; gap: 2rem; margin-top: 3rem; margin-bottom: 3rem; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 300px; background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); text-align: center;">
                <h3 style="font-family: \'Inter\', sans-serif; color: #444F2F; margin-bottom: 1rem;">Productores por Cuenca</h3>
                <canvas id="cuencaChart" width="400" height="400" style="max-height: 300px;"></canvas>
            </div>
            <div style="flex: 1; min-width: 300px; max-width: 100%; background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); text-align: center; overflow: hidden;">
                <h3 style="font-family: \'Inter\', sans-serif; color: #444F2F; margin-bottom: 1rem;">Productores por Vereda</h3>
                <div style="width: 100%; overflow-x: auto; padding-bottom: 1rem;">
                    <div id="veredaChartContainer" style="min-width: 600px; height: 300px; position: relative;">
                        <canvas id="veredaChart"></canvas>
                    </div>
                </div>
            </div>
        </div>';

$htmlReplacement = '        <!-- Charts Section -->
        <div style="display: flex; gap: 1.5rem; margin-top: 3rem; margin-bottom: 3rem; flex-wrap: wrap; justify-content: center;">
            <!-- Chart 1: Total Participants -->
            <div style="flex: 1; min-width: 300px; max-width: calc(33.33% - 1rem); background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); text-align: center; overflow: hidden;">
                <h3 style="font-family: \'Inter\', sans-serif; color: #444F2F; margin-bottom: 1rem; font-size: 1.1rem; font-weight: 600;">Participantes por Vereda</h3>
                <div style="width: 100%; overflow-x: auto; padding-bottom: 1rem;">
                    <div id="veredaTotalChartContainer" style="min-width: 450px; height: 320px; position: relative;">
                        <canvas id="veredaTotalChart"></canvas>
                    </div>
                </div>
            </div>
            <!-- Chart 2: Selected by Normal Score -->
            <div style="flex: 1; min-width: 300px; max-width: calc(33.33% - 1rem); background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); text-align: center; overflow: hidden;">
                <h3 style="font-family: \'Inter\', sans-serif; color: #444F2F; margin-bottom: 1rem; font-size: 1.1rem; font-weight: 600;">Seleccionados (Top 150) - Puntaje Normal</h3>
                <div style="width: 100%; overflow-x: auto; padding-bottom: 1rem;">
                    <div id="veredaNormalChartContainer" style="min-width: 450px; height: 320px; position: relative;">
                        <canvas id="veredaNormalChart"></canvas>
                    </div>
                </div>
            </div>
            <!-- Chart 3: Selected by Adjusted Score -->
            <div style="flex: 1; min-width: 300px; max-width: calc(33.33% - 1rem); background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); text-align: center; overflow: hidden;">
                <h3 style="font-family: \'Inter\', sans-serif; color: #444F2F; margin-bottom: 1rem; font-size: 1.1rem; font-weight: 600;">Seleccionados (Top 150) - Puntaje Ajustado</h3>
                <div style="width: 100%; overflow-x: auto; padding-bottom: 1rem;">
                    <div id="veredaAjustadoChartContainer" style="min-width: 450px; height: 320px; position: relative;">
                        <canvas id="veredaAjustadoChart"></canvas>
                    </div>
                </div>
            </div>
        </div>';

$content = str_replace($htmlTarget, $htmlReplacement, $content, $count2);
if ($count2 === 0) {
    $htmlTargetCRLF = str_replace("\n", "\r\n", $htmlTarget);
    $htmlReplacementCRLF = str_replace("\n", "\r\n", $htmlReplacement);
    $content = str_replace($htmlTargetCRLF, $htmlReplacementCRLF, $content, $count2);
}
echo "Replacement 2 (HTML layout): $count2\n";


// 3. Update DOMContentLoaded ranking calculations (including adjusted scores and adjusted ranking)
$rankingTarget = '                    const characterizedAndScored = allProducers.filter(p => p.tiene_caracterizacion == 1 && p.puntaje !== null);
                    characterizedAndScored.sort((a, b) => b.puntaje - a.puntaje);
                    characterizedAndScored.forEach((p, idx) => {
                        p.ranking = idx + 1;
                    });
                    
                    allProducers.forEach(p => {
                        if (p.tiene_caracterizacion == 1 && p.puntaje !== null) {
                            const found = characterizedAndScored.find(c => c.id === p.id);
                            p.ranking = found ? found.ranking : null;
                        } else {
                            p.ranking = null;
                        }
                    });';

$rankingReplacement = '                    const characterizedAndScored = allProducers.filter(p => p.tiene_caracterizacion == 1 && p.puntaje !== null);
                    characterizedAndScored.sort((a, b) => b.puntaje - a.puntaje);
                    characterizedAndScored.forEach((p, idx) => {
                        p.ranking = idx + 1;
                    });

                    // Calculate adjusted scores and ranking_ajustado globally
                    allProducers.forEach(p => {
                        const puntaje = (p.tiene_caracterizacion == 1 && p.puntaje !== null) ? parseFloat(p.puntaje) : null;
                        if (puntaje !== null) {
                            const vNorm = normalizeVereda(p.vereda);
                            const countVereda = registeredVeredaCounts[vNorm] || 1;
                            p.puntaje_ajustado = puntaje * (1 + 1 / countVereda);
                        } else {
                            p.puntaje_ajustado = null;
                        }
                    });

                    const adjustedScored = allProducers.filter(p => p.puntaje_ajustado !== null);
                    adjustedScored.sort((a, b) => b.puntaje_ajustado - a.puntaje_ajustado);
                    adjustedScored.forEach((p, idx) => {
                        p.ranking_ajustado = idx + 1;
                    });
                    
                    allProducers.forEach(p => {
                        if (p.tiene_caracterizacion == 1 && p.puntaje !== null) {
                            const found = characterizedAndScored.find(c => c.id === p.id);
                            p.ranking = found ? found.ranking : null;
                            const foundAdj = adjustedScored.find(c => c.id === p.id);
                            p.ranking_ajustado = foundAdj ? foundAdj.ranking_ajustado : null;
                        } else {
                            p.ranking = null;
                            p.ranking_ajustado = null;
                        }
                    });';

$content = str_replace($rankingTarget, $rankingReplacement, $content, $count3);
if ($count3 === 0) {
    $rankingTargetCRLF = str_replace("\n", "\r\n", $rankingTarget);
    $rankingReplacementCRLF = str_replace("\n", "\r\n", $rankingReplacement);
    $content = str_replace($rankingTargetCRLF, $rankingReplacementCRLF, $content, $count3);
}
echo "Replacement 3 (Ranking calculations): $count3\n";


// 4. Replace updateCharts function
$chartsTarget = '        function updateCharts(data) {
            if (typeof Chart === \'undefined\') {
                console.warn("Chart.js is not loaded. Skipping chart rendering.");
                return;
            }
            const cuencaCounts = {};
            const veredaCounts = {};

            data.forEach(p => {
                const cuenca = p.cuenca || \'Sin Cuenca\';
                cuencaCounts[cuenca] = (cuencaCounts[cuenca] || 0) + 1;
                
                const vereda = p.vereda || \'Sin Vereda\';
                veredaCounts[vereda] = (veredaCounts[vereda] || 0) + 1;
            });

            // Update Cuenca Pie Chart
            const cuencaCanvas = document.getElementById(\'cuencaChart\');
            if (cuencaCanvas) {
                const cuencaCtx = cuencaCanvas.getContext(\'2d\');
                if (cuencaChartInstance) cuencaChartInstance.destroy();

                const dataValues = Object.values(cuencaCounts);
                const total = dataValues.reduce((acc, val) => acc + val, 0);

                cuencaChartInstance = new Chart(cuencaCtx, {
                    type: \'pie\',
                    data: {
                        labels: Object.keys(cuencaCounts),
                        datasets: [{
                            data: dataValues,
                            backgroundColor: [\'#444F2F\', \'#8E9A5E\', \'#C2C9A5\', \'#EFEBE4\', \'#F4A261\', \'#E76F51\', \'#2A9D8F\', \'#e9c46a\', \'#f4a261\', \'#e76f51\']
                        }]
                    },
                    plugins: [ChartDataLabels],
                    options: { 
                        responsive: true, 
                        maintainAspectRatio: false,
                        plugins: {
                            datalabels: {
                                formatter: (value, ctx) => {
                                    if (total === 0) return \'\';
                                    let percentage = (value * 100 / total).toFixed(1) + "%";
                                    return value + \'\n(\' + percentage + \')\';
                                },
                                textAlign: \'center\',
                                color: \'#fff\',
                                textStrokeColor: \'rgba(0,0,0,0.6)\',
                                textStrokeWidth: 3,
                                font: {
                                    weight: \'bold\',
                                    size: 13
                                }
                            }
                        }
                    }
                });
            }

            // Update Vereda Bar Chart
            const veredaCanvas = document.getElementById(\'veredaChart\');
            if (veredaCanvas) {
                const veredaCtx = veredaCanvas.getContext(\'2d\');
                if (veredaChartInstance) veredaChartInstance.destroy();

                const sortedVeredas = Object.keys(veredaCounts).sort((a, b) => veredaCounts[b] - veredaCounts[a]);
                const dataValues = sortedVeredas.map(v => veredaCounts[v]);
                const total = dataValues.reduce((acc, val) => acc + val, 0);

                const container = document.getElementById(\'veredaChartContainer\');
                if (container) {
                    const newWidth = Math.max(600, sortedVeredas.length * 60);
                    container.style.minWidth = newWidth + \'px\';
                }

                veredaChartInstance = new Chart(veredaCtx, {
                    type: \'bar\',
                    data: {
                        labels: sortedVeredas,
                        datasets: [{
                            label: \'Productores\',
                            data: dataValues,
                            backgroundColor: \'#444F2F\'
                        }]
                    },
                    plugins: [ChartDataLabels],
                    options: { 
                        responsive: true, 
                        maintainAspectRatio: false, 
                        scales: { 
                            y: { beginAtZero: true, ticks: { stepSize: 1 } },
                            x: { ticks: { autoSkip: false, maxRotation: 45, minRotation: 45 } }
                        },
                        layout: {
                            padding: { top: 25 }
                        },
                        plugins: {
                            datalabels: {
                                anchor: \'end\',
                                align: \'top\',
                                offset: 4,
                                formatter: (value, ctx) => {
                                    if (total === 0) return \'\';
                                    let percentage = (value * 100 / total).toFixed(1) + "%";
                                    return value + \' (\' + percentage + \')\';
                                },
                                color: \'#444F2F\',
                                font: {
                                    weight: \'bold\',
                                    size: 11
                                }
                            }
                        }
                    }
                });
            }
        }';

$chartsReplacement = '        function updateCharts(data) {
            if (typeof Chart === \'undefined\') {
                console.warn("Chart.js is not loaded. Skipping chart rendering.");
                return;
            }

            // 1. Vereda Counts for All Participants in data
            const veredaTotalCounts = {};
            // 2. Vereda Counts for Top 150 Normal Score
            const veredaNormalCounts = {};
            // 3. Vereda Counts for Top 150 Adjusted Score
            const veredaAjustadoCounts = {};

            data.forEach(p => {
                const vereda = p.vereda || \'Sin Vereda\';
                
                // Total
                veredaTotalCounts[vereda] = (veredaTotalCounts[vereda] || 0) + 1;

                // Normal Selected (Top 150)
                if (p.ranking !== null && p.ranking <= 150) {
                    veredaNormalCounts[vereda] = (veredaNormalCounts[vereda] || 0) + 1;
                }

                // Adjusted Selected (Top 150)
                if (p.ranking_ajustado !== null && p.ranking_ajustado <= 150) {
                    veredaAjustadoCounts[vereda] = (veredaAjustadoCounts[vereda] || 0) + 1;
                }
            });

            // Helper to render/update a bar chart
            const renderVeredaBarChart = (canvasId, containerId, countsObj, label, chartInstanceVar, setInstanceFn, color) => {
                const canvas = document.getElementById(canvasId);
                if (!canvas) return;

                const ctx = canvas.getContext(\'2d\');
                if (chartInstanceVar) chartInstanceVar.destroy();

                const sortedLabels = Object.keys(countsObj).sort((a, b) => countsObj[b] - countsObj[a]);
                const dataValues = sortedLabels.map(v => countsObj[v]);
                const total = dataValues.reduce((acc, val) => acc + val, 0);

                const container = document.getElementById(containerId);
                if (container) {
                    const newWidth = Math.max(450, sortedLabels.length * 50);
                    container.style.minWidth = newWidth + \'px\';
                }

                const newInstance = new Chart(ctx, {
                    type: \'bar\',
                    data: {
                        labels: sortedLabels,
                        datasets: [{
                            label: label,
                            data: dataValues,
                            backgroundColor: color
                        }]
                    },
                    plugins: [ChartDataLabels],
                    options: { 
                        responsive: true, 
                        maintainAspectRatio: false, 
                        scales: { 
                            y: { 
                                beginAtZero: true, 
                                ticks: { stepSize: 1 } 
                            },
                            x: { 
                                ticks: { 
                                    autoSkip: false, 
                                    maxRotation: 45, 
                                    minRotation: 45,
                                    font: { size: 10 }
                                } 
                            }
                        },
                        layout: {
                            padding: { top: 25 }
                        },
                        plugins: {
                            datalabels: {
                                anchor: \'end\',
                                align: \'top\',
                                offset: 4,
                                formatter: (value, ctx) => {
                                    if (total === 0) return \'\';
                                    let percentage = (value * 100 / total).toFixed(1) + "%";
                                    return value + \' (\' + percentage + \')\';
                                },
                                color: \'#444F2F\',
                                font: {
                                    weight: \'bold\',
                                    size: 10
                                }
                            }
                        }
                    }
                });
                setInstanceFn(newInstance);
            };

            // Render/Update the three charts
            renderVeredaBarChart(
                \'veredaTotalChart\', 
                \'veredaTotalChartContainer\', 
                veredaTotalCounts, 
                \'Total Participantes\', 
                veredaTotalChartInstance, 
                (inst) => { veredaTotalChartInstance = inst; },
                \'#444F2F\'
            );

            renderVeredaBarChart(
                \'veredaNormalChart\', 
                \'veredaNormalChartContainer\', 
                veredaNormalCounts, 
                \'Seleccionados Normal\', 
                veredaNormalChartInstance, 
                (inst) => { veredaNormalChartInstance = inst; },
                \'#E76F51\'
            );

            renderVeredaBarChart(
                \'veredaAjustadoChart\', 
                \'veredaAjustadoChartContainer\', 
                veredaAjustadoCounts, 
                \'Seleccionados Ajustado\', 
                veredaAjustadoChartInstance, 
                (inst) => { veredaAjustadoChartInstance = inst; },
                \'#2A9D8F\'
            );
        }';

$content = str_replace($chartsTarget, $chartsReplacement, $content, $count4);
if ($count4 === 0) {
    $chartsTargetCRLF = str_replace("\n", "\r\n", $chartsTarget);
    $chartsReplacementCRLF = str_replace("\n", "\r\n", $chartsReplacement);
    $content = str_replace($chartsTargetCRLF, $chartsReplacementCRLF, $content, $count4);
}
echo "Replacement 4 (updateCharts function): $count4\n";

file_put_contents($file, $content);
echo "All done!\n";
?>
