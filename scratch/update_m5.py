import re

with open('pmapc.html', 'r', encoding='utf-8') as f:
    html = f.read()

# 1. HTML Replacement
new_html = """                <!-- F13 Canales de venta -->
                <div class="format-block">
                    <div class="format-title"><span>F13</span> Canales de Comercialización</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f13">
                            <thead>
                                <tr>
                                    <th style="width: 25%;">Canal</th>
                                    <th style="width: 10%;">¿Aplica?</th>
                                    <th>¿Cuáles / Detalles?</th>
                                    <th>Frecuencia</th>
                                    <th>Responsable</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Ferias campesinas</strong></td>
                                    <td><select name="f13_aplica_0"><option value="No">No</option><option value="Sí">Sí</option></select></td>
                                    <td><input type="text" name="f13_detalles_0" placeholder="..."></td>
                                    <td><input type="text" name="f13_frec_0" placeholder="..."></td>
                                    <td><input type="text" name="f13_resp_0" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Venta directa</strong></td>
                                    <td><select name="f13_aplica_1"><option value="No">No</option><option value="Sí">Sí</option></select></td>
                                    <td><input type="text" name="f13_detalles_1" placeholder="..."></td>
                                    <td><input type="text" name="f13_frec_1" placeholder="..."></td>
                                    <td><input type="text" name="f13_resp_1" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Redes sociales</strong></td>
                                    <td><select name="f13_aplica_2"><option value="No">No</option><option value="Sí">Sí</option></select></td>
                                    <td><input type="text" name="f13_detalles_2" placeholder="..."></td>
                                    <td><input type="text" name="f13_frec_2" placeholder="..."></td>
                                    <td><input type="text" name="f13_resp_2" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>WhatsApp / llamadas</strong></td>
                                    <td><select name="f13_aplica_3"><option value="No">No</option><option value="Sí">Sí</option></select></td>
                                    <td><input type="text" name="f13_detalles_3" placeholder="..."></td>
                                    <td><input type="text" name="f13_frec_3" placeholder="..."></td>
                                    <td><input type="text" name="f13_resp_3" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Tiendas / restaurantes / plazas</strong></td>
                                    <td><select name="f13_aplica_4"><option value="No">No</option><option value="Sí">Sí</option></select></td>
                                    <td><input type="text" name="f13_detalles_4" placeholder="..."></td>
                                    <td><input type="text" name="f13_frec_4" placeholder="..."></td>
                                    <td><input type="text" name="f13_resp_4" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Compras institucionales</strong></td>
                                    <td><select name="f13_aplica_5"><option value="No">No</option><option value="Sí">Sí</option></select></td>
                                    <td><input type="text" name="f13_detalles_5" placeholder="..."></td>
                                    <td><input type="text" name="f13_frec_5" placeholder="..."></td>
                                    <td><input type="text" name="f13_resp_5" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Comercialización asociativa</strong></td>
                                    <td><select name="f13_aplica_6"><option value="No">No</option><option value="Sí">Sí</option></select></td>
                                    <td><input type="text" name="f13_detalles_6" placeholder="..."></td>
                                    <td><input type="text" name="f13_frec_6" placeholder="..."></td>
                                    <td><input type="text" name="f13_resp_6" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Otro</strong></td>
                                    <td><select name="f13_aplica_7"><option value="No">No</option><option value="Sí">Sí</option></select></td>
                                    <td><input type="text" name="f13_detalles_7" placeholder="..."></td>
                                    <td><input type="text" name="f13_frec_7" placeholder="..."></td>
                                    <td><input type="text" name="f13_resp_7" placeholder="..."></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- F14 Estrategia de Precios -->
                <div class="format-block">
                    <div class="format-title"><span>F14</span> Estrategia de Precios (Dynamic)</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f14">
                            <thead>
                                <tr>
                                    <th>Producto / servicio</th>
                                    <th>Costo por unidad</th>
                                    <th>Margen deseado %</th>
                                    <th>Precio mínimo</th>
                                    <th>Precio de mercado</th>
                                    <th>Costo logístico</th>
                                    <th>Precio de venta final</th>
                                    <th>Justificación</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="add-row-btn" onclick="addRowF14()">+ Añadir Producto</button>
                </div>

                <!-- F15 Ventas y Trazabilidad -->
                <div class="format-block">
                    <div class="format-title"><span>F15</span> Proyección de Ventas (Dynamic)</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f15">
                            <thead>
                                <tr>
                                    <th>Producto / servicio</th>
                                    <th>Cantidad vendida mensual</th>
                                    <th>Precio unitario</th>
                                    <th>Ingresos mensuales</th>
                                    <th>Forma de pago</th>
                                    <th>Cliente o canal principal</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="add-row-btn" onclick="addRowF15()">+ Añadir Venta</button>
                </div>

                <div class="format-block">
                    <div class="format-title"><span>F15A</span> Fidelización</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f15a">
                            <thead>
                                <tr>
                                    <th style="width: 25%;">Tipo de cliente o comprador</th>
                                    <th>Estrategia de fidelización</th>
                                    <th>Medio de comunicación</th>
                                    <th>Frecuencia de contacto</th>
                                    <th>Responsable</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Cliente directo</strong></td>
                                    <td><input type="text" name="f15a_est_0" placeholder="..."></td>
                                    <td><input type="text" name="f15a_med_0" placeholder="..."></td>
                                    <td><input type="text" name="f15a_frec_0" placeholder="..."></td>
                                    <td><input type="text" name="f15a_resp_0" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Comprador local</strong></td>
                                    <td><input type="text" name="f15a_est_1" placeholder="..."></td>
                                    <td><input type="text" name="f15a_med_1" placeholder="..."></td>
                                    <td><input type="text" name="f15a_frec_1" placeholder="..."></td>
                                    <td><input type="text" name="f15a_resp_1" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Comprador institucional</strong></td>
                                    <td><input type="text" name="f15a_est_2" placeholder="..."></td>
                                    <td><input type="text" name="f15a_med_2" placeholder="..."></td>
                                    <td><input type="text" name="f15a_frec_2" placeholder="..."></td>
                                    <td><input type="text" name="f15a_resp_2" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Consumidor final</strong></td>
                                    <td><input type="text" name="f15a_est_3" placeholder="..."></td>
                                    <td><input type="text" name="f15a_med_3" placeholder="..."></td>
                                    <td><input type="text" name="f15a_frec_3" placeholder="..."></td>
                                    <td><input type="text" name="f15a_resp_3" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Aliado comercial</strong></td>
                                    <td><input type="text" name="f15a_est_4" placeholder="..."></td>
                                    <td><input type="text" name="f15a_med_4" placeholder="..."></td>
                                    <td><input type="text" name="f15a_frec_4" placeholder="..."></td>
                                    <td><input type="text" name="f15a_resp_4" placeholder="..."></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="format-block">
                    <div class="format-title"><span>F15B</span> Logística de Última Milla (Dynamic)</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f15b">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Canal de entrega</th>
                                    <th>Tiempo máximo de entrega</th>
                                    <th>Medio de transporte</th>
                                    <th>Condición para mantener frescura</th>
                                    <th>Capacidad de carga</th>
                                    <th>Costo estimado</th>
                                    <th>Responsable</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="add-row-btn" onclick="addRowF15B()">+ Añadir Logística</button>
                </div>

                <div class="format-block">
                    <div class="format-title"><span>F15C</span> Trazabilidad Digital</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f15c">
                            <thead>
                                <tr>
                                    <th style="width: 25%;">Elemento de trazabilidad</th>
                                    <th style="width: 30%;">Información que debe contener</th>
                                    <th>Responsable de actualización</th>
                                    <th>Medio o plataforma</th>
                                    <th>Frecuencia de actualización</th>
                                    <th>Evidencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Código QR en empaque o ficha técnica</strong></td>
                                    <td>Origen del producto, vereda, productor, fecha de cosecha o elaboración</td>
                                    <td><input type="text" name="f15c_resp_0" placeholder="..."></td>
                                    <td><input type="text" name="f15c_med_0" placeholder="..."></td>
                                    <td><input type="text" name="f15c_frec_0" placeholder="..."></td>
                                    <td><input type="text" name="f15c_evi_0" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Ficha técnica digital</strong></td>
                                    <td>Descripción, unidad de medida, condiciones de uso, almacenamiento y diferencial</td>
                                    <td><input type="text" name="f15c_resp_1" placeholder="..."></td>
                                    <td><input type="text" name="f15c_med_1" placeholder="..."></td>
                                    <td><input type="text" name="f15c_frec_1" placeholder="..."></td>
                                    <td><input type="text" name="f15c_evi_1" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Prácticas sostenibles</strong></td>
                                    <td>Uso eficiente del agua, manejo de residuos, compostaje, empaques biodegradables</td>
                                    <td><input type="text" name="f15c_resp_2" placeholder="..."></td>
                                    <td><input type="text" name="f15c_med_2" placeholder="..."></td>
                                    <td><input type="text" name="f15c_frec_2" placeholder="..."></td>
                                    <td><input type="text" name="f15c_evi_2" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Lote o fecha de producción</strong></td>
                                    <td>Información para seguimiento de calidad e inocuidad</td>
                                    <td><input type="text" name="f15c_resp_3" placeholder="..."></td>
                                    <td><input type="text" name="f15c_med_3" placeholder="..."></td>
                                    <td><input type="text" name="f15c_frec_3" placeholder="..."></td>
                                    <td><input type="text" name="f15c_evi_3" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Contacto comercial</strong></td>
                                    <td>WhatsApp, redes, correo o punto de venta</td>
                                    <td><input type="text" name="f15c_resp_4" placeholder="..."></td>
                                    <td><input type="text" name="f15c_med_4" placeholder="..."></td>
                                    <td><input type="text" name="f15c_frec_4" placeholder="..."></td>
                                    <td><input type="text" name="f15c_evi_4" placeholder="..."></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>"""

pattern = r'<!-- F13 Canales de venta -->.*?(?=<!-- ================= STEP 6: FINANZAS ================= -->)'
html = re.sub(pattern, new_html + '\n            </div>\n\n            ', html, flags=re.DOTALL)

# 2. JS Loading logic
new_js_load = """            // Module 5 (Comercial)
            if (data.f13) {
                for (let i = 0; i <= 7; i++) {
                    const row = data.f13[i];
                    if (row) {
                        setInputByName(`f13_aplica_${i}`, row.aplica);
                        setInputByName(`f13_detalles_${i}`, row.detalles);
                        setInputByName(`f13_frec_${i}`, row.frec);
                        setInputByName(`f13_resp_${i}`, row.resp);
                    }
                }
            }

            // Dynamic F14 (Precios)
            const tbl14 = document.getElementById('tbl-f14').getElementsByTagName('tbody')[0];
            tbl14.innerHTML = '';
            if (data.f14 && data.f14.length > 0) {
                data.f14.forEach(item => {
                    addRowF14(item.producto, item.costo, item.margen, item.pmin, item.pmercado, item.logistica, item.precio, item.justificacion);
                });
            } else {
                addRowF14();
            }

            // Dynamic F15 (Ventas)
            const tbl15 = document.getElementById('tbl-f15').getElementsByTagName('tbody')[0];
            tbl15.innerHTML = '';
            if (data.f15 && data.f15.length > 0) {
                data.f15.forEach(item => {
                    addRowF15(item.producto, item.cantidad, item.precio, item.ingresos, item.pago, item.cliente);
                });
            } else {
                addRowF15();
            }

            // F15A Fidelización
            if (data.f15a) {
                for (let i = 0; i <= 4; i++) {
                    const row = data.f15a[i];
                    if (row) {
                        setInputByName(`f15a_est_${i}`, row.est);
                        setInputByName(`f15a_med_${i}`, row.med);
                        setInputByName(`f15a_frec_${i}`, row.frec);
                        setInputByName(`f15a_resp_${i}`, row.resp);
                    }
                }
            }

            // Dynamic F15B Logística
            const tbl15b = document.getElementById('tbl-f15b').getElementsByTagName('tbody')[0];
            tbl15b.innerHTML = '';
            if (data.f15b && data.f15b.length > 0) {
                data.f15b.forEach(item => {
                    addRowF15B(item.prod, item.canal, item.tiempo, item.transporte, item.condicion, item.capacidad, item.costo, item.resp);
                });
            } else {
                addRowF15B();
            }

            // F15C Trazabilidad
            if (data.f15c) {
                for (let i = 0; i <= 4; i++) {
                    const row = data.f15c[i];
                    if (row) {
                        setInputByName(`f15c_resp_${i}`, row.resp);
                        setInputByName(`f15c_med_${i}`, row.med);
                        setInputByName(`f15c_frec_${i}`, row.frec);
                        setInputByName(`f15c_evi_${i}`, row.evi);
                    }
                }
            }"""

pattern2 = r'// Module 5 \(Comercial\).*?(?=\s*// Module 6 \(Finanzas\) - Dynamic F16)'
html = re.sub(pattern2, new_js_load, html, flags=re.DOTALL)

# 3. AddRowF14 replacement
new_addrow = """        function addRowF14(prod='', cost='', margin='', pmin='', pmercado='', log='', price='', just='') {
            const tbody = document.getElementById('tbl-f14').getElementsByTagName('tbody')[0];
            const tr = document.createElement('tr');
            tr.className = "dynamic-row-f14";
            tr.innerHTML = `
                <td><input type="text" name="f14_producto[]" value="${prod}" placeholder="..."></td>
                <td><input type="number" step="0.01" name="f14_costo[]" value="${cost}" oninput="calcPrecio(this)"></td>
                <td><input type="number" step="0.1" name="f14_margen[]" value="${margin}" oninput="calcPrecio(this)"></td>
                <td><input type="number" step="0.01" name="f14_pmin[]" value="${pmin}" placeholder="..."></td>
                <td><input type="number" step="0.01" name="f14_pmercado[]" value="${pmercado}" placeholder="..."></td>
                <td><input type="number" step="0.01" name="f14_logistica[]" value="${log}" oninput="calcPrecio(this)"></td>
                <td><input type="number" name="f14_precio[]" value="${price || ''}" readonly></td>
                <td><input type="text" name="f14_justificacion[]" value="${just}"></td>
                <td>
                    <button type="button" class="row-action-btn" onclick="removeRow(this)">
                        <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
            updateFormCompletionProgress();
        }

        function addRowF15(prod='', cant='', precio='', ingresos='', pago='', cliente='') {
            const tbody = document.getElementById('tbl-f15').getElementsByTagName('tbody')[0];
            const tr = document.createElement('tr');
            tr.className = "dynamic-row-f15";
            tr.innerHTML = `
                <td><input type="text" name="f15_producto[]" value="${prod}" placeholder="..."></td>
                <td><input type="number" name="f15_cantidad[]" value="${cant}" oninput="calcIngresos(this)"></td>
                <td><input type="number" name="f15_precio[]" value="${precio}" oninput="calcIngresos(this)"></td>
                <td><input type="number" name="f15_ingresos[]" value="${ingresos || ''}" readonly></td>
                <td><input type="text" name="f15_pago[]" value="${pago}" placeholder="..."></td>
                <td><input type="text" name="f15_cliente[]" value="${cliente}" placeholder="..."></td>
                <td>
                    <button type="button" class="row-action-btn" onclick="removeRow(this)">
                        <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
            updateFormCompletionProgress();
        }

        function addRowF15B(prod='', canal='', tiempo='', transporte='', condicion='', capacidad='', costo='', resp='') {
            const tbody = document.getElementById('tbl-f15b').getElementsByTagName('tbody')[0];
            const tr = document.createElement('tr');
            tr.className = "dynamic-row-f15b";
            tr.innerHTML = `
                <td><input type="text" name="f15b_prod[]" value="${prod}" placeholder="..."></td>
                <td><input type="text" name="f15b_canal[]" value="${canal}" placeholder="..."></td>
                <td><input type="text" name="f15b_tiempo[]" value="${tiempo}" placeholder="..."></td>
                <td><input type="text" name="f15b_transporte[]" value="${transporte}" placeholder="..."></td>
                <td><input type="text" name="f15b_condicion[]" value="${condicion}" placeholder="..."></td>
                <td><input type="text" name="f15b_capacidad[]" value="${capacidad}" placeholder="..."></td>
                <td><input type="number" step="0.01" name="f15b_costo[]" value="${costo}" placeholder="..."></td>
                <td><input type="text" name="f15b_resp[]" value="${resp}" placeholder="..."></td>
                <td>
                    <button type="button" class="row-action-btn" onclick="removeRow(this)">
                        <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
            updateFormCompletionProgress();
        }"""

pattern3 = r'function addRowF14\([^)]*\)\s*\{.*?(?=\s*function addRowF16)'
html = re.sub(pattern3, new_addrow, html, flags=re.DOTALL)

# 4. JSON Generation update
json_update = """                    f13: getTableDataF13(),
                    f14: getTableDataF14(),
                    f15: getTableDataF15(),
                    f15a: getTableDataF15A(),
                    f15b: getTableDataF15B(),
                    f15c: getTableDataF15C(),"""

pattern4 = r'f13:\s*\{[^}]*\},\s*f14:\s*getTableDataF14\(\),\s*f15_fidelizacion[^,]+,\s*f15_logistica[^,]+,\s*f15_trazabilidad[^,]+,'
html = re.sub(pattern4, json_update, html, flags=re.DOTALL)

# 5. Functions for getTableData
new_get_funcs = """        function getTableDataF13() {
            const arr = [];
            for (let i = 0; i <= 7; i++) {
                arr.push({
                    aplica: document.querySelector(`[name="f13_aplica_${i}"]`)?.value || 'No',
                    detalles: document.querySelector(`[name="f13_detalles_${i}"]`)?.value || '',
                    frec: document.querySelector(`[name="f13_frec_${i}"]`)?.value || '',
                    resp: document.querySelector(`[name="f13_resp_${i}"]`)?.value || ''
                });
            }
            return arr;
        }

        function getTableDataF14() {
            const arr = [];
            document.querySelectorAll('.dynamic-row-f14').forEach(row => {
                const producto = row.querySelector('[name="f14_producto[]"]').value.trim();
                if (producto) {
                    arr.push({
                        producto: producto,
                        costo: row.querySelector('[name="f14_costo[]"]').value,
                        margen: row.querySelector('[name="f14_margen[]"]').value,
                        pmin: row.querySelector('[name="f14_pmin[]"]').value,
                        pmercado: row.querySelector('[name="f14_pmercado[]"]').value,
                        logistica: row.querySelector('[name="f14_logistica[]"]').value,
                        precio: row.querySelector('[name="f14_precio[]"]').value,
                        justificacion: row.querySelector('[name="f14_justificacion[]"]').value
                    });
                }
            });
            return arr;
        }

        function getTableDataF15() {
            const arr = [];
            document.querySelectorAll('.dynamic-row-f15').forEach(row => {
                const producto = row.querySelector('[name="f15_producto[]"]').value.trim();
                if (producto) {
                    arr.push({
                        producto: producto,
                        cantidad: row.querySelector('[name="f15_cantidad[]"]').value,
                        precio: row.querySelector('[name="f15_precio[]"]').value,
                        ingresos: row.querySelector('[name="f15_ingresos[]"]').value,
                        pago: row.querySelector('[name="f15_pago[]"]').value,
                        cliente: row.querySelector('[name="f15_cliente[]"]').value
                    });
                }
            });
            return arr;
        }

        function getTableDataF15A() {
            const arr = [];
            for (let i = 0; i <= 4; i++) {
                arr.push({
                    est: document.querySelector(`[name="f15a_est_${i}"]`)?.value || '',
                    med: document.querySelector(`[name="f15a_med_${i}"]`)?.value || '',
                    frec: document.querySelector(`[name="f15a_frec_${i}"]`)?.value || '',
                    resp: document.querySelector(`[name="f15a_resp_${i}"]`)?.value || ''
                });
            }
            return arr;
        }

        function getTableDataF15B() {
            const arr = [];
            document.querySelectorAll('.dynamic-row-f15b').forEach(row => {
                const prod = row.querySelector('[name="f15b_prod[]"]').value.trim();
                if (prod) {
                    arr.push({
                        prod: prod,
                        canal: row.querySelector('[name="f15b_canal[]"]').value,
                        tiempo: row.querySelector('[name="f15b_tiempo[]"]').value,
                        transporte: row.querySelector('[name="f15b_transporte[]"]').value,
                        condicion: row.querySelector('[name="f15b_condicion[]"]').value,
                        capacidad: row.querySelector('[name="f15b_capacidad[]"]').value,
                        costo: row.querySelector('[name="f15b_costo[]"]').value,
                        resp: row.querySelector('[name="f15b_resp[]"]').value
                    });
                }
            });
            return arr;
        }

        function getTableDataF15C() {
            const arr = [];
            for (let i = 0; i <= 4; i++) {
                arr.push({
                    resp: document.querySelector(`[name="f15c_resp_${i}"]`)?.value || '',
                    med: document.querySelector(`[name="f15c_med_${i}"]`)?.value || '',
                    frec: document.querySelector(`[name="f15c_frec_${i}"]`)?.value || '',
                    evi: document.querySelector(`[name="f15c_evi_${i}"]`)?.value || ''
                });
            }
            return arr;
        }"""

pattern5 = r'function getTableDataF14\(\)\s*\{.*?(?=\s*function getTableDataF16)'
html = re.sub(pattern5, new_get_funcs, html, flags=re.DOTALL)

# 6. Calc Ingresos Function
new_calc = """        function calcIngresos(input) {
            const row = input.closest('tr');
            const cant = parseFloat(row.querySelector('[name="f15_cantidad[]"]').value) || 0;
            const precio = parseFloat(row.querySelector('[name="f15_precio[]"]').value) || 0;
            const ingresos = Math.round(cant * precio);
            row.querySelector('[name="f15_ingresos[]"]').value = ingresos > 0 ? ingresos : '';
        }"""

html = html.replace('function calcInversion(input) {', new_calc + '\\n\\n        function calcInversion(input) {')

with open('pmapc.html', 'w', encoding='utf-8') as f:
    f.write(html)
