import re

with open('pmapc.html', 'r', encoding='utf-8') as f:
    html = f.read()

# 1. Replace HTML for Modulo 8 (from F23 to end of F26)
new_html = """                <!-- F23 Riesgos -->
                <div class="format-block">
                    <div class="format-title"><span>F23</span> Matriz de Riesgos y Mitigación</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f23">
                            <thead>
                                <tr>
                                    <th>Tipo de riesgo</th>
                                    <th>Riesgo identificado</th>
                                    <th>Causa</th>
                                    <th>Consecuencia</th>
                                    <th style="width: 10%;">Nivel de riesgo</th>
                                    <th>Acción de prevención</th>
                                    <th>Acción de respuesta</th>
                                    <th style="width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="dynamic-row-f23">
                                    <td><input type="text" name="f23_tipo[]" placeholder="..."></td>
                                    <td><input type="text" name="f23_riesgo[]" placeholder="..."></td>
                                    <td><input type="text" name="f23_causa[]" placeholder="..."></td>
                                    <td><input type="text" name="f23_cons[]" placeholder="..."></td>
                                    <td>
                                        <select name="f23_nivel[]">
                                            <option value="Alto">Alto</option>
                                            <option value="Medio" selected>Medio</option>
                                            <option value="Bajo">Bajo</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="f23_prev[]" placeholder="..."></td>
                                    <td><input type="text" name="f23_resp[]" placeholder="..."></td>
                                    <td>
                                        <button type="button" class="row-action-btn" onclick="removeRow(this)">
                                            <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="add-row-btn" onclick="addRowF23()">+ Añadir Riesgo</button>
                </div>

                <!-- F24 Plan de accion final -->
                <div class="format-block">
                    <div class="format-title"><span>F24</span> Plan de Acción de Fortalecimiento</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f24">
                            <thead>
                                <tr>
                                    <th>Actividad</th>
                                    <th style="width: 15%;">Componente</th>
                                    <th>Responsable</th>
                                    <th>Tiempo</th>
                                    <th>Recursos necesarios</th>
                                    <th>Resultado esperado</th>
                                    <th>Evidencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input type="text" name="f24_act_0" placeholder="..."></td>
                                    <td><strong>Productivo</strong></td>
                                    <td><input type="text" name="f24_resp_0" placeholder="..."></td>
                                    <td><input type="text" name="f24_tiempo_0" placeholder="..."></td>
                                    <td><input type="text" name="f24_rec_0" placeholder="..."></td>
                                    <td><input type="text" name="f24_res_0" placeholder="..."></td>
                                    <td><input type="text" name="f24_evi_0" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><input type="text" name="f24_act_1" placeholder="..."></td>
                                    <td><strong>Comercial</strong></td>
                                    <td><input type="text" name="f24_resp_1" placeholder="..."></td>
                                    <td><input type="text" name="f24_tiempo_1" placeholder="..."></td>
                                    <td><input type="text" name="f24_rec_1" placeholder="..."></td>
                                    <td><input type="text" name="f24_res_1" placeholder="..."></td>
                                    <td><input type="text" name="f24_evi_1" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><input type="text" name="f24_act_2" placeholder="..."></td>
                                    <td><strong>Financiero</strong></td>
                                    <td><input type="text" name="f24_resp_2" placeholder="..."></td>
                                    <td><input type="text" name="f24_tiempo_2" placeholder="..."></td>
                                    <td><input type="text" name="f24_rec_2" placeholder="..."></td>
                                    <td><input type="text" name="f24_res_2" placeholder="..."></td>
                                    <td><input type="text" name="f24_evi_2" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><input type="text" name="f24_act_3" placeholder="..."></td>
                                    <td><strong>Ambiental</strong></td>
                                    <td><input type="text" name="f24_resp_3" placeholder="..."></td>
                                    <td><input type="text" name="f24_tiempo_3" placeholder="..."></td>
                                    <td><input type="text" name="f24_rec_3" placeholder="..."></td>
                                    <td><input type="text" name="f24_res_3" placeholder="..."></td>
                                    <td><input type="text" name="f24_evi_3" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><input type="text" name="f24_act_4" placeholder="..."></td>
                                    <td><strong>Digital / trazabilidad</strong></td>
                                    <td><input type="text" name="f24_resp_4" placeholder="..."></td>
                                    <td><input type="text" name="f24_tiempo_4" placeholder="..."></td>
                                    <td><input type="text" name="f24_rec_4" placeholder="..."></td>
                                    <td><input type="text" name="f24_res_4" placeholder="..."></td>
                                    <td><input type="text" name="f24_evi_4" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><input type="text" name="f24_act_5" placeholder="..."></td>
                                    <td><strong>Cooperación territorial</strong></td>
                                    <td><input type="text" name="f24_resp_5" placeholder="..."></td>
                                    <td><input type="text" name="f24_tiempo_5" placeholder="..."></td>
                                    <td><input type="text" name="f24_rec_5" placeholder="..."></td>
                                    <td><input type="text" name="f24_res_5" placeholder="..."></td>
                                    <td><input type="text" name="f24_evi_5" placeholder="..."></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- F25 Indicadores -->
                <div class="format-block">
                    <div class="format-title"><span>F25</span> Indicadores de Gestión Integrales</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f25">
                            <thead>
                                <tr>
                                    <th style="width: 15%;">Dimensión</th>
                                    <th>Indicador</th>
                                    <th>Meta</th>
                                    <th>Frecuencia</th>
                                    <th>Responsable</th>
                                    <th>Evidencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Ventas</strong></td>
                                    <td><input type="text" name="f25_ind_0" placeholder="..."></td>
                                    <td><input type="text" name="f25_meta_0" placeholder="..."></td>
                                    <td><input type="text" name="f25_frec_0" placeholder="..."></td>
                                    <td><input type="text" name="f25_resp_0" placeholder="..."></td>
                                    <td><input type="text" name="f25_evi_0" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Producción</strong></td>
                                    <td><input type="text" name="f25_ind_1" placeholder="..."></td>
                                    <td><input type="text" name="f25_meta_1" placeholder="..."></td>
                                    <td><input type="text" name="f25_frec_1" placeholder="..."></td>
                                    <td><input type="text" name="f25_resp_1" placeholder="..."></td>
                                    <td><input type="text" name="f25_evi_1" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Financiera</strong></td>
                                    <td><input type="text" name="f25_ind_2" placeholder="..."></td>
                                    <td><input type="text" name="f25_meta_2" placeholder="..."></td>
                                    <td><input type="text" name="f25_frec_2" placeholder="..."></td>
                                    <td><input type="text" name="f25_resp_2" placeholder="..."></td>
                                    <td><input type="text" name="f25_evi_2" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Cliente</strong></td>
                                    <td><input type="text" name="f25_ind_3" placeholder="..."></td>
                                    <td><input type="text" name="f25_meta_3" placeholder="..."></td>
                                    <td><input type="text" name="f25_frec_3" placeholder="..."></td>
                                    <td><input type="text" name="f25_resp_3" placeholder="..."></td>
                                    <td><input type="text" name="f25_evi_3" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Digital</strong></td>
                                    <td><input type="text" name="f25_ind_4" placeholder="..."></td>
                                    <td><input type="text" name="f25_meta_4" placeholder="..."></td>
                                    <td><input type="text" name="f25_frec_4" placeholder="..."></td>
                                    <td><input type="text" name="f25_resp_4" placeholder="..."></td>
                                    <td><input type="text" name="f25_evi_4" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Logística</strong></td>
                                    <td><input type="text" name="f25_ind_5" placeholder="..."></td>
                                    <td><input type="text" name="f25_meta_5" placeholder="..."></td>
                                    <td><input type="text" name="f25_frec_5" placeholder="..."></td>
                                    <td><input type="text" name="f25_resp_5" placeholder="..."></td>
                                    <td><input type="text" name="f25_evi_5" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Ambiental</strong></td>
                                    <td><input type="text" name="f25_ind_6" placeholder="..."></td>
                                    <td><input type="text" name="f25_meta_6" placeholder="..."></td>
                                    <td><input type="text" name="f25_frec_6" placeholder="..."></td>
                                    <td><input type="text" name="f25_resp_6" placeholder="..."></td>
                                    <td><input type="text" name="f25_evi_6" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Regenerativa</strong></td>
                                    <td><input type="text" name="f25_ind_7" placeholder="..."></td>
                                    <td><input type="text" name="f25_meta_7" placeholder="..."></td>
                                    <td><input type="text" name="f25_frec_7" placeholder="..."></td>
                                    <td><input type="text" name="f25_resp_7" placeholder="..."></td>
                                    <td><input type="text" name="f25_evi_7" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Cooperación</strong></td>
                                    <td><input type="text" name="f25_ind_8" placeholder="..."></td>
                                    <td><input type="text" name="f25_meta_8" placeholder="..."></td>
                                    <td><input type="text" name="f25_frec_8" placeholder="..."></td>
                                    <td><input type="text" name="f25_resp_8" placeholder="..."></td>
                                    <td><input type="text" name="f25_evi_8" placeholder="..."></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- F26 Coherencia -->
                <div class="format-block">
                    <div class="format-title"><span>F26</span> Matriz de Coherencia Sistémica del PMAPC</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f26">
                            <thead>
                                <tr>
                                    <th style="width: 20%;">Decisión del negocio</th>
                                    <th>Efecto productivo</th>
                                    <th>Efecto comercial</th>
                                    <th>Efecto financiero</th>
                                    <th>Efecto ambiental</th>
                                    <th>Ajuste necesario</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Aumentar producción</strong></td>
                                    <td><input type="text" name="f26_prod_0" placeholder="..."></td>
                                    <td><input type="text" name="f26_com_0" placeholder="..."></td>
                                    <td><input type="text" name="f26_fin_0" placeholder="..."></td>
                                    <td><input type="text" name="f26_amb_0" placeholder="..."></td>
                                    <td><input type="text" name="f26_aju_0" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Cambiar insumos</strong></td>
                                    <td><input type="text" name="f26_prod_1" placeholder="..."></td>
                                    <td><input type="text" name="f26_com_1" placeholder="..."></td>
                                    <td><input type="text" name="f26_fin_1" placeholder="..."></td>
                                    <td><input type="text" name="f26_amb_1" placeholder="..."></td>
                                    <td><input type="text" name="f26_aju_1" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Ampliar canales de venta</strong></td>
                                    <td><input type="text" name="f26_prod_2" placeholder="..."></td>
                                    <td><input type="text" name="f26_com_2" placeholder="..."></td>
                                    <td><input type="text" name="f26_fin_2" placeholder="..."></td>
                                    <td><input type="text" name="f26_amb_2" placeholder="..."></td>
                                    <td><input type="text" name="f26_aju_2" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Comprar maquinaria</strong></td>
                                    <td><input type="text" name="f26_prod_3" placeholder="..."></td>
                                    <td><input type="text" name="f26_com_3" placeholder="..."></td>
                                    <td><input type="text" name="f26_fin_3" placeholder="..."></td>
                                    <td><input type="text" name="f26_amb_3" placeholder="..."></td>
                                    <td><input type="text" name="f26_aju_3" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Transformar producto</strong></td>
                                    <td><input type="text" name="f26_prod_4" placeholder="..."></td>
                                    <td><input type="text" name="f26_com_4" placeholder="..."></td>
                                    <td><input type="text" name="f26_fin_4" placeholder="..."></td>
                                    <td><input type="text" name="f26_amb_4" placeholder="..."></td>
                                    <td><input type="text" name="f26_aju_4" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Participar en ferias</strong></td>
                                    <td><input type="text" name="f26_prod_5" placeholder="..."></td>
                                    <td><input type="text" name="f26_com_5" placeholder="..."></td>
                                    <td><input type="text" name="f26_fin_5" placeholder="..."></td>
                                    <td><input type="text" name="f26_amb_5" placeholder="..."></td>
                                    <td><input type="text" name="f26_aju_5" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Implementar QR</strong></td>
                                    <td><input type="text" name="f26_prod_6" placeholder="..."></td>
                                    <td><input type="text" name="f26_com_6" placeholder="..."></td>
                                    <td><input type="text" name="f26_fin_6" placeholder="..."></td>
                                    <td><input type="text" name="f26_amb_6" placeholder="..."></td>
                                    <td><input type="text" name="f26_aju_6" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Usar empaques biodegradables</strong></td>
                                    <td><input type="text" name="f26_prod_7" placeholder="..."></td>
                                    <td><input type="text" name="f26_com_7" placeholder="..."></td>
                                    <td><input type="text" name="f26_fin_7" placeholder="..."></td>
                                    <td><input type="text" name="f26_amb_7" placeholder="..."></td>
                                    <td><input type="text" name="f26_aju_7" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Asociarse con otros productores</strong></td>
                                    <td><input type="text" name="f26_prod_8" placeholder="..."></td>
                                    <td><input type="text" name="f26_com_8" placeholder="..."></td>
                                    <td><input type="text" name="f26_fin_8" placeholder="..."></td>
                                    <td><input type="text" name="f26_amb_8" placeholder="..."></td>
                                    <td><input type="text" name="f26_aju_8" placeholder="..."></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="form-grid" style="margin-top: 15px;">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="f26_coherencia">Análisis Adicional de Coherencia</label>
                            <textarea id="f26_coherencia" name="f26_coherencia" rows="3" placeholder="..."></textarea>
                        </div>
                    </div>
                </div>"""

pattern_html = r'<!-- F23 Riesgos -->.*?(?=<!-- Navigation Buttons -->)'
html = re.sub(pattern_html, new_html + '\n            </div>\n\n            ', html, flags=re.DOTALL)

# 2. Loading Logic
new_load_logic = """            // Module 8 (Riesgos)
            if (data.f23) {
                const tbody = document.querySelector('#tbl-f23 tbody');
                tbody.innerHTML = '';
                if (data.f23.length === 0) {
                    addRowF23();
                } else {
                    data.f23.forEach(row => {
                        const tr = document.createElement('tr');
                        tr.className = 'dynamic-row-f23';
                        tr.innerHTML = `
                            <td><input type="text" name="f23_tipo[]" value="${row.tipo || ''}"></td>
                            <td><input type="text" name="f23_riesgo[]" value="${row.riesgo || ''}"></td>
                            <td><input type="text" name="f23_causa[]" value="${row.causa || ''}"></td>
                            <td><input type="text" name="f23_cons[]" value="${row.cons || ''}"></td>
                            <td>
                                <select name="f23_nivel[]">
                                    <option value="Alto" ${row.nivel === 'Alto' ? 'selected' : ''}>Alto</option>
                                    <option value="Medio" ${row.nivel === 'Medio' ? 'selected' : ''}>Medio</option>
                                    <option value="Bajo" ${row.nivel === 'Bajo' ? 'selected' : ''}>Bajo</option>
                                </select>
                            </td>
                            <td><input type="text" name="f23_prev[]" value="${row.prev || ''}"></td>
                            <td><input type="text" name="f23_resp[]" value="${row.resp || ''}"></td>
                            <td>
                                <button type="button" class="row-action-btn" onclick="removeRow(this)">
                                    <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
            }

            if (data.f24) {
                for (let i = 0; i <= 5; i++) {
                    const row = data.f24[i];
                    if (row) {
                        setInputByName(`f24_act_${i}`, row.act);
                        setInputByName(`f24_resp_${i}`, row.resp);
                        setInputByName(`f24_tiempo_${i}`, row.tiempo);
                        setInputByName(`f24_rec_${i}`, row.rec);
                        setInputByName(`f24_res_${i}`, row.res);
                        setInputByName(`f24_evi_${i}`, row.evi);
                    }
                }
            }

            if (data.f25) {
                for (let i = 0; i <= 8; i++) {
                    const row = data.f25[i];
                    if (row) {
                        setInputByName(`f25_ind_${i}`, row.ind);
                        setInputByName(`f25_meta_${i}`, row.meta);
                        setInputByName(`f25_frec_${i}`, row.frec);
                        setInputByName(`f25_resp_${i}`, row.resp);
                        setInputByName(`f25_evi_${i}`, row.evi);
                    }
                }
            }

            if (data.f26) {
                for (let i = 0; i <= 8; i++) {
                    const row = data.f26[i];
                    if (row) {
                        setInputByName(`f26_prod_${i}`, row.prod);
                        setInputByName(`f26_com_${i}`, row.com);
                        setInputByName(`f26_fin_${i}`, row.fin);
                        setInputByName(`f26_amb_${i}`, row.amb);
                        setInputByName(`f26_aju_${i}`, row.aju);
                    }
                }
            }
            if (data.f26_coherencia) setVal('f26_coherencia', data.f26_coherencia);"""

pattern_load = r'// Module 8 \(Riesgos\).*?(?=\s*// Generic Data loader logic)'
html = re.sub(pattern_load, new_load_logic, html, flags=re.DOTALL)

# 3. Form saving logic changes
json_update = """                    f23: getTableDataF23(),
                    f24: getTableDataF24(),
                    f25: getTableDataF25(),
                    f26: getTableDataF26(),
                    f26_coherencia: document.getElementById('f26_coherencia').value"""

pattern_save = r'f23:\s*\{[^}]*\},\s*f24:\s*getTableDataF24\(\),\s*f26_coherencia:\s*formData.get\(\'f26_coherencia\'\)'
html = re.sub(pattern_save, json_update, html, flags=re.DOTALL)


# 4. Javascript functions
new_funcs = """        function getTableDataF23() {
            const arr = [];
            document.querySelectorAll('.dynamic-row-f23').forEach(row => {
                arr.push({
                    tipo: row.querySelector('[name="f23_tipo[]"]')?.value || '',
                    riesgo: row.querySelector('[name="f23_riesgo[]"]')?.value || '',
                    causa: row.querySelector('[name="f23_causa[]"]')?.value || '',
                    cons: row.querySelector('[name="f23_cons[]"]')?.value || '',
                    nivel: row.querySelector('[name="f23_nivel[]"]')?.value || 'Medio',
                    prev: row.querySelector('[name="f23_prev[]"]')?.value || '',
                    resp: row.querySelector('[name="f23_resp[]"]')?.value || ''
                });
            });
            return arr;
        }

        function getTableDataF24() {
            const arr = [];
            for (let i = 0; i <= 5; i++) {
                arr.push({
                    act: document.querySelector(`[name="f24_act_${i}"]`)?.value || '',
                    resp: document.querySelector(`[name="f24_resp_${i}"]`)?.value || '',
                    tiempo: document.querySelector(`[name="f24_tiempo_${i}"]`)?.value || '',
                    rec: document.querySelector(`[name="f24_rec_${i}"]`)?.value || '',
                    res: document.querySelector(`[name="f24_res_${i}"]`)?.value || '',
                    evi: document.querySelector(`[name="f24_evi_${i}"]`)?.value || ''
                });
            }
            return arr;
        }

        function getTableDataF25() {
            const arr = [];
            for (let i = 0; i <= 8; i++) {
                arr.push({
                    ind: document.querySelector(`[name="f25_ind_${i}"]`)?.value || '',
                    meta: document.querySelector(`[name="f25_meta_${i}"]`)?.value || '',
                    frec: document.querySelector(`[name="f25_frec_${i}"]`)?.value || '',
                    resp: document.querySelector(`[name="f25_resp_${i}"]`)?.value || '',
                    evi: document.querySelector(`[name="f25_evi_${i}"]`)?.value || ''
                });
            }
            return arr;
        }

        function getTableDataF26() {
            const arr = [];
            for (let i = 0; i <= 8; i++) {
                arr.push({
                    prod: document.querySelector(`[name="f26_prod_${i}"]`)?.value || '',
                    com: document.querySelector(`[name="f26_com_${i}"]`)?.value || '',
                    fin: document.querySelector(`[name="f26_fin_${i}"]`)?.value || '',
                    amb: document.querySelector(`[name="f26_amb_${i}"]`)?.value || '',
                    aju: document.querySelector(`[name="f26_aju_${i}"]`)?.value || ''
                });
            }
            return arr;
        }"""

html = html.replace('// Initialization', new_funcs + '\n\n        // Initialization')

# 5. Dynamic Row addition
add_row_f23 = """        function addRowF23() {
            const tbody = document.querySelector('#tbl-f23 tbody');
            const tr = document.createElement('tr');
            tr.className = 'dynamic-row-f23';
            tr.innerHTML = `
                <td><input type="text" name="f23_tipo[]" placeholder="..."></td>
                <td><input type="text" name="f23_riesgo[]" placeholder="..."></td>
                <td><input type="text" name="f23_causa[]" placeholder="..."></td>
                <td><input type="text" name="f23_cons[]" placeholder="..."></td>
                <td>
                    <select name="f23_nivel[]">
                        <option value="Alto">Alto</option>
                        <option value="Medio" selected>Medio</option>
                        <option value="Bajo">Bajo</option>
                    </select>
                </td>
                <td><input type="text" name="f23_prev[]" placeholder="..."></td>
                <td><input type="text" name="f23_resp[]" placeholder="..."></td>
                <td>
                    <button type="button" class="row-action-btn" onclick="removeRow(this)">
                        <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        }"""

# Need to replace addRowF24 if it exists, and inject addRowF23.
html = re.sub(r'function addRowF24\(\)\s*\{.*?(?=function showModal)', add_row_f23 + '\n\n        ', html, flags=re.DOTALL)


with open('pmapc.html', 'w', encoding='utf-8') as f:
    f.write(html)
