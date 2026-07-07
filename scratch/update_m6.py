import re

with open('pmapc.html', 'r', encoding='utf-8') as f:
    html = f.read()

# 1. Replace F16, F17, and F18 HTML
new_html = """                <!-- F16 Inversion -->
                <div class="format-block">
                    <div class="format-title"><span>F16</span> Inversión Inicial Necesaria</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f16">
                            <thead>
                                <tr>
                                    <th style="width: 25%;">Tipo de activo o recurso</th>
                                    <th>Descripción</th>
                                    <th style="width: 15%;">Valor unitario ($)</th>
                                    <th style="width: 10%;">Cantidad</th>
                                    <th style="width: 15%;">Valor total ($)</th>
                                    <th>Requisito técnico o ambiental</th>
                                    <th>Fuente de financiación</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Maquinaria y equipo</strong></td>
                                    <td><input type="text" name="f16_desc_0" placeholder="..."></td>
                                    <td><input type="number" step="0.01" name="f16_valunit_0" placeholder="..." oninput="calcInv(0)"></td>
                                    <td><input type="number" name="f16_cant_0" placeholder="..." oninput="calcInv(0)"></td>
                                    <td><input type="number" name="f16_total_0" placeholder="Calculado" readonly></td>
                                    <td><input type="text" name="f16_req_0" placeholder="..."></td>
                                    <td><input type="text" name="f16_fuente_0" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Infraestructura / adecuaciones</strong></td>
                                    <td><input type="text" name="f16_desc_1" placeholder="..."></td>
                                    <td><input type="number" step="0.01" name="f16_valunit_1" placeholder="..." oninput="calcInv(1)"></td>
                                    <td><input type="number" name="f16_cant_1" placeholder="..." oninput="calcInv(1)"></td>
                                    <td><input type="number" name="f16_total_1" placeholder="Calculado" readonly></td>
                                    <td><input type="text" name="f16_req_1" placeholder="..."></td>
                                    <td><input type="text" name="f16_fuente_1" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Muebles y enseres</strong></td>
                                    <td><input type="text" name="f16_desc_2" placeholder="..."></td>
                                    <td><input type="number" step="0.01" name="f16_valunit_2" placeholder="..." oninput="calcInv(2)"></td>
                                    <td><input type="number" name="f16_cant_2" placeholder="..." oninput="calcInv(2)"></td>
                                    <td><input type="number" name="f16_total_2" placeholder="Calculado" readonly></td>
                                    <td><input type="text" name="f16_req_2" placeholder="..."></td>
                                    <td><input type="text" name="f16_fuente_2" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Herramientas y varios</strong></td>
                                    <td><input type="text" name="f16_desc_3" placeholder="..."></td>
                                    <td><input type="number" step="0.01" name="f16_valunit_3" placeholder="..." oninput="calcInv(3)"></td>
                                    <td><input type="number" name="f16_cant_3" placeholder="..." oninput="calcInv(3)"></td>
                                    <td><input type="number" name="f16_total_3" placeholder="Calculado" readonly></td>
                                    <td><input type="text" name="f16_req_3" placeholder="..."></td>
                                    <td><input type="text" name="f16_fuente_3" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Equipos de comunicación y TIC</strong></td>
                                    <td><input type="text" name="f16_desc_4" placeholder="..."></td>
                                    <td><input type="number" step="0.01" name="f16_valunit_4" placeholder="..." oninput="calcInv(4)"></td>
                                    <td><input type="number" name="f16_cant_4" placeholder="..." oninput="calcInv(4)"></td>
                                    <td><input type="number" name="f16_total_4" placeholder="Calculado" readonly></td>
                                    <td><input type="text" name="f16_req_4" placeholder="..."></td>
                                    <td><input type="text" name="f16_fuente_4" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Empaques o sistema QR</strong></td>
                                    <td><input type="text" name="f16_desc_5" placeholder="..."></td>
                                    <td><input type="number" step="0.01" name="f16_valunit_5" placeholder="..." oninput="calcInv(5)"></td>
                                    <td><input type="number" name="f16_cant_5" placeholder="..." oninput="calcInv(5)"></td>
                                    <td><input type="number" name="f16_total_5" placeholder="Calculado" readonly></td>
                                    <td><input type="text" name="f16_req_5" placeholder="..."></td>
                                    <td><input type="text" name="f16_fuente_5" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Gastos preoperativos</strong></td>
                                    <td><input type="text" name="f16_desc_6" placeholder="..."></td>
                                    <td><input type="number" step="0.01" name="f16_valunit_6" placeholder="..." oninput="calcInv(6)"></td>
                                    <td><input type="number" name="f16_cant_6" placeholder="..." oninput="calcInv(6)"></td>
                                    <td><input type="number" name="f16_total_6" placeholder="Calculado" readonly></td>
                                    <td><input type="text" name="f16_req_6" placeholder="..."></td>
                                    <td><input type="text" name="f16_fuente_6" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Acciones ambientales iniciales</strong></td>
                                    <td><input type="text" name="f16_desc_7" placeholder="..."></td>
                                    <td><input type="number" step="0.01" name="f16_valunit_7" placeholder="..." oninput="calcInv(7)"></td>
                                    <td><input type="number" name="f16_cant_7" placeholder="..." oninput="calcInv(7)"></td>
                                    <td><input type="number" name="f16_total_7" placeholder="Calculado" readonly></td>
                                    <td><input type="text" name="f16_req_7" placeholder="..."></td>
                                    <td><input type="text" name="f16_fuente_7" placeholder="..."></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- F17 Costos -->
                <div class="format-block">
                    <div class="format-title"><span>F17</span> Costos Mensuales del Negocio</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f17">
                            <thead>
                                <tr>
                                    <th style="width: 30%;">Tipo de costo</th>
                                    <th>Descripción</th>
                                    <th style="width: 20%;">Valor mensual ($)</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Costos fijos</strong></td>
                                    <td><input type="text" name="f17_desc_0" placeholder="..."></td>
                                    <td><input type="number" name="f17_val_0" placeholder="..."></td>
                                    <td><input type="text" name="f17_obs_0" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Costos variables</strong></td>
                                    <td><input type="text" name="f17_desc_1" placeholder="..."></td>
                                    <td><input type="number" name="f17_val_1" placeholder="..."></td>
                                    <td><input type="text" name="f17_obs_1" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Costos logísticos</strong></td>
                                    <td><input type="text" name="f17_desc_2" placeholder="..."></td>
                                    <td><input type="number" name="f17_val_2" placeholder="..."></td>
                                    <td><input type="text" name="f17_obs_2" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Costos de empaque / etiqueta / QR</strong></td>
                                    <td><input type="text" name="f17_desc_3" placeholder="..."></td>
                                    <td><input type="number" name="f17_val_3" placeholder="..."></td>
                                    <td><input type="text" name="f17_obs_3" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Costos ambientales: compostaje, manejo de residuos, ahorro de agua</strong></td>
                                    <td><input type="text" name="f17_desc_4" placeholder="..."></td>
                                    <td><input type="number" name="f17_val_4" placeholder="..."></td>
                                    <td><input type="text" name="f17_obs_4" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Costos digitales: redes, internet, plataforma, diseño</strong></td>
                                    <td><input type="text" name="f17_desc_5" placeholder="..."></td>
                                    <td><input type="number" name="f17_val_5" placeholder="..."></td>
                                    <td><input type="text" name="f17_obs_5" placeholder="..."></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- F18 Flujo de caja simple -->
                <div class="format-block">
                    <div class="format-title"><span>F18</span> Flujo de Caja Proyectado (Primer Semestre)</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f18">
                            <thead>
                                <tr>
                                    <th>Mes</th>
                                    <th>Ingresos por Ventas ($)</th>
                                    <th>Gastos de Producción ($)</th>
                                    <th>Gastos Ambientales ($)</th>
                                    <th>Gastos Logísticos ($)</th>
                                    <th>Balance Neto ($)</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Mes 1</strong></td>
                                    <td><input type="number" name="f18_ingreso_m1" placeholder="Ej. 2000000" oninput="calcNeto(1)"></td>
                                    <td><input type="number" name="f18_gprod_m1" placeholder="Ej. 1200000" oninput="calcNeto(1)"></td>
                                    <td><input type="number" name="f18_gamb_m1" placeholder="Ej. 100000" oninput="calcNeto(1)"></td>
                                    <td><input type="number" name="f18_glog_m1" placeholder="Ej. 200000" oninput="calcNeto(1)"></td>
                                    <td><input type="number" name="f18_neto_m1" readonly placeholder="Calculado"></td>
                                    <td><input type="text" name="f18_obs_m1" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Mes 2</strong></td>
                                    <td><input type="number" name="f18_ingreso_m2" oninput="calcNeto(2)"></td>
                                    <td><input type="number" name="f18_gprod_m2" oninput="calcNeto(2)"></td>
                                    <td><input type="number" name="f18_gamb_m2" oninput="calcNeto(2)"></td>
                                    <td><input type="number" name="f18_glog_m2" oninput="calcNeto(2)"></td>
                                    <td><input type="number" name="f18_neto_m2" readonly></td>
                                    <td><input type="text" name="f18_obs_m2" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Mes 3</strong></td>
                                    <td><input type="number" name="f18_ingreso_m3" oninput="calcNeto(3)"></td>
                                    <td><input type="number" name="f18_gprod_m3" oninput="calcNeto(3)"></td>
                                    <td><input type="number" name="f18_gamb_m3" oninput="calcNeto(3)"></td>
                                    <td><input type="number" name="f18_glog_m3" oninput="calcNeto(3)"></td>
                                    <td><input type="number" name="f18_neto_m3" readonly></td>
                                    <td><input type="text" name="f18_obs_m3" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Mes 4</strong></td>
                                    <td><input type="number" name="f18_ingreso_m4" oninput="calcNeto(4)"></td>
                                    <td><input type="number" name="f18_gprod_m4" oninput="calcNeto(4)"></td>
                                    <td><input type="number" name="f18_gamb_m4" oninput="calcNeto(4)"></td>
                                    <td><input type="number" name="f18_glog_m4" oninput="calcNeto(4)"></td>
                                    <td><input type="number" name="f18_neto_m4" readonly></td>
                                    <td><input type="text" name="f18_obs_m4" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Mes 5</strong></td>
                                    <td><input type="number" name="f18_ingreso_m5" oninput="calcNeto(5)"></td>
                                    <td><input type="number" name="f18_gprod_m5" oninput="calcNeto(5)"></td>
                                    <td><input type="number" name="f18_gamb_m5" oninput="calcNeto(5)"></td>
                                    <td><input type="number" name="f18_glog_m5" oninput="calcNeto(5)"></td>
                                    <td><input type="number" name="f18_neto_m5" readonly></td>
                                    <td><input type="text" name="f18_obs_m5" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Mes 6</strong></td>
                                    <td><input type="number" name="f18_ingreso_m6" oninput="calcNeto(6)"></td>
                                    <td><input type="number" name="f18_gprod_m6" oninput="calcNeto(6)"></td>
                                    <td><input type="number" name="f18_gamb_m6" oninput="calcNeto(6)"></td>
                                    <td><input type="number" name="f18_glog_m6" oninput="calcNeto(6)"></td>
                                    <td><input type="number" name="f18_neto_m6" readonly></td>
                                    <td><input type="text" name="f18_obs_m6" placeholder="..."></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>"""

pattern_html = r'<!-- F16 Inversion -->.*?(?=<!-- ================= STEP 7: SOSTENIBILIDAD ================= -->)'
html = re.sub(pattern_html, new_html + '\n            </div>\n\n            ', html, flags=re.DOTALL)

# 2. Loading Logic
new_load_logic = """            // Module 6 (Finanzas)
            if (data.f16) {
                for (let i = 0; i <= 7; i++) {
                    const row = data.f16[i];
                    if (row) {
                        setInputByName(`f16_desc_${i}`, row.desc);
                        setInputByName(`f16_valunit_${i}`, row.valunit);
                        setInputByName(`f16_cant_${i}`, row.cant);
                        setInputByName(`f16_req_${i}`, row.req);
                        setInputByName(`f16_fuente_${i}`, row.fuente);
                        calcInv(i);
                    }
                }
            }

            if (data.f17) {
                for (let i = 0; i <= 5; i++) {
                    const row = data.f17[i];
                    if (row) {
                        setInputByName(`f17_desc_${i}`, row.desc);
                        setInputByName(`f17_val_${i}`, row.val);
                        setInputByName(`f17_obs_${i}`, row.obs);
                    }
                }
            }

            if (data.f18) {
                for (let i = 1; i <= 6; i++) {
                    setInputByName(`f18_ingreso_m${i}`, data.f18[`ingreso_m${i}`]);
                    setInputByName(`f18_gprod_m${i}`, data.f18[`gprod_m${i}`]);
                    setInputByName(`f18_gamb_m${i}`, data.f18[`gamb_m${i}`]);
                    setInputByName(`f18_glog_m${i}`, data.f18[`glog_m${i}`]);
                    setInputByName(`f18_obs_m${i}`, data.f18[`obs_m${i}`]);
                    calcNeto(i);
                }
            }"""

pattern_load = r'// Module 6 \(Finanzas\) - Dynamic F16.*?(?=\s*// Module 7 \(Sostenibilidad\))'
html = re.sub(pattern_load, new_load_logic, html, flags=re.DOTALL)

# 3. Form saving logic changes
# We need to replace f16, f17 loading in getFormData()
json_update = """                    f16: getTableDataF16(),
                    f17: getTableDataF17(),
                    f18: getTableDataF18(),"""

pattern_save = r'f16:\s*getTableDataF16\(\),\s*f17:\s*\{[^}]*\},\s*f18:\s*\{[^}]*\},'
html = re.sub(pattern_save, json_update, html, flags=re.DOTALL)


# 4. Javascript functions getTableDataF16, getTableDataF17, getTableDataF18
new_funcs = """        function getTableDataF16() {
            const arr = [];
            for (let i = 0; i <= 7; i++) {
                arr.push({
                    desc: document.querySelector(`[name="f16_desc_${i}"]`)?.value || '',
                    valunit: document.querySelector(`[name="f16_valunit_${i}"]`)?.value || '',
                    cant: document.querySelector(`[name="f16_cant_${i}"]`)?.value || '',
                    total: document.querySelector(`[name="f16_total_${i}"]`)?.value || '',
                    req: document.querySelector(`[name="f16_req_${i}"]`)?.value || '',
                    fuente: document.querySelector(`[name="f16_fuente_${i}"]`)?.value || ''
                });
            }
            return arr;
        }

        function getTableDataF17() {
            const arr = [];
            for (let i = 0; i <= 5; i++) {
                arr.push({
                    desc: document.querySelector(`[name="f17_desc_${i}"]`)?.value || '',
                    val: document.querySelector(`[name="f17_val_${i}"]`)?.value || '',
                    obs: document.querySelector(`[name="f17_obs_${i}"]`)?.value || ''
                });
            }
            return arr;
        }

        function getTableDataF18() {
            const data = {};
            for (let i = 1; i <= 6; i++) {
                data[`ingreso_m${i}`] = document.querySelector(`[name="f18_ingreso_m${i}"]`)?.value || '';
                data[`gprod_m${i}`] = document.querySelector(`[name="f18_gprod_m${i}"]`)?.value || '';
                data[`gamb_m${i}`] = document.querySelector(`[name="f18_gamb_m${i}"]`)?.value || '';
                data[`glog_m${i}`] = document.querySelector(`[name="f18_glog_m${i}"]`)?.value || '';
                data[`neto_m${i}`] = document.querySelector(`[name="f18_neto_m${i}"]`)?.value || '';
                data[`obs_m${i}`] = document.querySelector(`[name="f18_obs_m${i}"]`)?.value || '';
            }
            return data;
        }"""

pattern_funcs = r'function getTableDataF16\(\)\s*\{.*?(?=\s*// Initialization)'
html = re.sub(pattern_funcs, new_funcs, html, flags=re.DOTALL)

# 5. Remove addRowF16
html = re.sub(r'function addRowF16\([^)]*\)\s*\{.*?(?=\s*function addRowF24)', '', html, flags=re.DOTALL)

# 6. Add calcInv
calc_inv = """        function calcInv(i) {
            const valUnit = parseFloat(document.querySelector(`[name="f16_valunit_${i}"]`).value) || 0;
            const qty = parseFloat(document.querySelector(`[name="f16_cant_${i}"]`).value) || 0;
            const valTotal = Math.round(valUnit * qty);
            document.querySelector(`[name="f16_total_${i}"]`).value = valTotal > 0 ? valTotal : '';
        }

        function calcInversion(input) { // Keep just in case"""
html = html.replace('function calcInversion(input) {', calc_inv)

with open('pmapc.html', 'w', encoding='utf-8') as f:
    f.write(html)
