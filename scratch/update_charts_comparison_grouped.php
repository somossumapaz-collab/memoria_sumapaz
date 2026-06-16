<?php
$file = __DIR__ . '/../productores_registrados.html';
if (!file_exists($file)) {
    die("File not found\n");
}

$content = file_get_contents($file);

// 1. Update global variables declaration
$globalTarget = '        let completenessReport = {};
        let veredaTotalChartInstance = null;
        let veredaNormalChartInstance = null;
        let veredaAjustadoChartInstance = null;';

$globalReplacement = '        let completenessReport = {};
        let veredaComparisonChartInstance = null;';

$content = str_replace($globalTarget, $globalReplacement, $content, $count1);
if ($count1 === 0) {
    $globalTargetCRLF = str_replace("\n", "\r\n", $globalTarget);
    $globalReplacementCRLF = str_replace("\n", "\r\n", $globalReplacement);
    $content = str_replace($globalTargetCRLF, $globalReplacementCRLF, $content, $count1);
}
echo "Replacement 1 (Global variables): $count1\n";


// 2. Update HTML Charts Section to show a single grouped bar chart
$htmlTarget = '        <!-- Charts Section -->
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

$htmlReplacement = '        <!-- Charts Section -->
        <div style="display: flex; gap: 2rem; margin-top: 3rem; margin-bottom: 3rem; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 300px; max-width: 100%; background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); text-align: center; overflow: hidden;">
                <h3 style="font-family: \'Inter\', sans-serif; color: #444F2F; margin-bottom: 1.5rem; font-size: 1.3rem; font-weight: 700;">Comparativa de Distribución por Vereda</h3>
                <div style="width: 100%; overflow-x: auto; padding-bottom: 1rem;">
                    <div id="veredaComparisonChartContainer" style="min-width: 800px; height: 400px; position: relative;">
                        <canvas id="veredaComparisonChart"></canvas>
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


// 3. Update updateCharts function to render a grouped bar chart
$chartsTarget = '        function updateCharts(data) {
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

$chartsReplacement = '        function updateCharts(data) {
            if (typeof Chart === \'undefined\') {
                console.warn("Chart.js is not loaded. Skipping chart rendering.");
                return;
            }

            // 1. Gather counts by vereda
            const veredaTotalCounts = {};
            const veredaNormalCounts = {};
            const veredaAjustadoCounts = {};

            data.forEach(p => {
                const vereda = p.vereda || \'Sin Vereda\';
                veredaTotalCounts[vereda] = (veredaTotalCounts[vereda] || 0) + 1;

                if (p.ranking !== null && p.ranking <= 150) {
                    veredaNormalCounts[vereda] = (veredaNormalCounts[vereda] || 0) + 1;
                }

                if (p.ranking_ajustado !== null && p.ranking_ajustado <= 150) {
                    veredaAjustadoCounts[vereda] = (veredaAjustadoCounts[vereda] || 0) + 1;
                }
            });

            // Get unique veredas, sorted by total participants descending
            const sortedVeredas = Object.keys(veredaTotalCounts).sort((a, b) => veredaTotalCounts[b] - veredaTotalCounts[a]);

            // Map dataset arrays
            const totalData = sortedVeredas.map(v => veredaTotalCounts[v] || 0);
            const normalData = sortedVeredas.map(v => veredaNormalCounts[v] || 0);
            const adjustedData = sortedVeredas.map(v => veredaAjustadoCounts[v] || 0);

            // Render/Update the single comparison chart
            const canvas = document.getElementById(\'veredaComparisonChart\');
            if (canvas) {
                const ctx = canvas.getContext(\'2d\');
                if (veredaComparisonChartInstance) veredaComparisonChartInstance.destroy();

                const container = document.getElementById(\'veredaComparisonChartContainer\');
                if (container) {
                    // Grouped chart has 3 bars per vereda. Allocate ~80px per vereda
                    const newWidth = Math.max(800, sortedVeredas.length * 80);
                    container.style.minWidth = newWidth + \'px\';
                }

                veredaComparisonChartInstance = new Chart(ctx, {
                    type: \'bar\',
                    data: {
                        labels: sortedVeredas,
                        datasets: [
                            {
                                label: \'Participantes Totales\',
                                data: totalData,
                                backgroundColor: \'#444F2F\' // Brand olive green
                            },
                            {
                                label: \'Seleccionados (Top 150) - Puntaje Normal\',
                                data: normalData,
                                backgroundColor: \'#E76F51\' // Terracotta
                            },
                            {
                                label: \'Seleccionados (Top 150) - Puntaje Ajustado\',
                                data: adjustedData,
                                backgroundColor: \'#2A9D8F\' // Teal
                            }
                        ]
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
                                    font: {
                                        size: 11,
                                        weight: \'bold\'
                                    }
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
                                offset: 2,
                                formatter: (value) => {
                                    return value > 0 ? value : \'\'; // Hide zeroes to avoid clutter
                                },
                                color: \'#333\',
                                font: {
                                    weight: \'bold\',
                                    size: 10
                                }
                            }
                        }
                    }
                });
            }
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
