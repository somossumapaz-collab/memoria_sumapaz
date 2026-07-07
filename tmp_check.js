
        let currentStep = 1;
        const totalSteps = 8;
        let allProducers = [];
        let selectedProducerId = null;

        document.addEventListener('DOMContentLoaded', async () => {
            // Check Auth Status on Load
            try {
                const res = await fetch('api/check_auth.php');
                const result = await res.json();
                if (res.ok && result.authenticated) {
                    document.getElementById('header-auth-container').style.display = 'flex';
                }
            } catch (error) {
                console.error('Error checking auth:', error);
            }

            // Load registered producers
            await loadProducers();

            // Check URL query parameters
            const urlParams = new URLSearchParams(window.location.search);
            const idParam = urlParams.get('id');

            if (idParam) {
                selectedProducerId = idParam;
                document.getElementById('select-producer').value = idParam;
                document.getElementById('productor_id').value = idParam;
                document.getElementById('producer-selector-box').style.display = 'none';
                
                // Show prefilled card
                const prod = allProducers.find(p => p.id == idParam);
                if (prod) {
                    document.getElementById('select-producer-input').value = prod.nombre_completo;
                    showProducerDetails(prod);
                }
                
                // Fetch saved PMAPC
                await loadSavedPmapc(idParam);
            }

            // Search and autocomplete logic
            const searchInput = document.getElementById('select-producer-input');
            const resultsDiv = document.getElementById('select-producer-results');

            if (searchInput && resultsDiv) {
                const renderMatches = (query = '') => {
                    const queryClean = query.toLowerCase().trim();
                    const filtered = queryClean === '' ? allProducers : allProducers.filter(p => {
                        return p.nombre_completo.toLowerCase().includes(queryClean) || 
                               (p.numero_documento && p.numero_documento.includes(queryClean));
                    });

                    resultsDiv.innerHTML = '';
                    if (filtered.length === 0) {
                        resultsDiv.innerHTML = '<div style="padding: 0.8rem 1rem; color: #888; font-size: 0.9rem;">No se encontraron beneficiarios.</div>';
                        resultsDiv.style.display = 'block';
                        return;
                    }

                    filtered.forEach(p => {
                        const div = document.createElement('div');
                        div.style.padding = '0.8rem 1rem';
                        div.style.cursor = 'pointer';
                        div.style.borderBottom = '1px solid #eee';
                        div.style.fontSize = '0.95rem';
                        div.style.transition = 'background-color 0.2s';
                        div.innerHTML = `<strong>${p.nombre_completo}</strong> <span style="color: #666; font-size: 0.85rem;">(${p.tipo_documento} ${p.numero_documento})</span>`;
                        
                        div.onmouseover = () => { div.style.backgroundColor = '#F0F2F5'; };
                        div.onmouseout = () => { div.style.backgroundColor = 'transparent'; };
                        
                        div.onclick = async () => {
                            searchInput.value = p.nombre_completo;
                            document.getElementById('select-producer').value = p.id;
                            selectedProducerId = p.id;
                            document.getElementById('productor_id').value = p.id;
                            showProducerDetails(p);
                            resultsDiv.style.display = 'none';
                            await loadSavedPmapc(p.id);
                        };
                        resultsDiv.appendChild(div);
                    });
                    resultsDiv.style.display = 'block';
                };

                searchInput.addEventListener('input', (e) => {
                    renderMatches(e.target.value);
                });

                searchInput.addEventListener('focus', (e) => {
                    renderMatches(e.target.value);
                });

                document.addEventListener('click', (e) => {
                    if (!e.target.closest('#producer-selector-box')) {
                        resultsDiv.style.display = 'none';
                    }
                });
            }

            // Listen for change producer click
            const btnChange = document.getElementById('btn-change-producer');
            if (btnChange) {
                btnChange.addEventListener('click', () => {
                    document.getElementById('producer-selector-box').style.display = 'block';
                    document.getElementById('producer-info-box').style.display = 'none';
                    const progressCard = document.getElementById('form-progress-card');
                    if (progressCard) {
                        progressCard.style.display = 'none';
                    }
                    document.getElementById('select-producer').value = '';
                    document.getElementById('select-producer-input').value = '';
                    selectedProducerId = null;
                    document.getElementById('productor_id').value = '';
                    document.getElementById('pmapc-form').reset();
                    clearDynamicRows();
                    updateFormCompletionProgress();
                });
            }

            // Progress bar event listeners
            const form = document.getElementById('pmapc-form');
            if (form) {
                form.addEventListener('input', updateFormCompletionProgress);
                form.addEventListener('change', updateFormCompletionProgress);
            }
            const fastInputs = ['edit-vereda', 'edit-predio', 'edit-fecha-nacimiento', 'edit-telefono', 'edit-correo'];
            fastInputs.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', updateFormCompletionProgress);
                    el.addEventListener('change', updateFormCompletionProgress);
                }
            });

            // Close Modal Logic
            document.getElementById('modal-close-btn').addEventListener('click', function () {
                document.getElementById('success-modal').classList.remove('active');
                window.location.href = 'productores_registrados.html';
            });

            document.getElementById('error-close-btn').addEventListener('click', function () {
                document.getElementById('error-modal').classList.remove('active');
            });
        });

        async function loadProducers() {
            try {
                const res = await fetch('api/get_productores.php');
                const result = await res.json();
                
                if (result.success) {
                    allProducers = result.data;
                    document.getElementById('select-producer-input').placeholder = "Escriba el nombre o documento para buscar...";
                } else {
                    document.getElementById('select-producer-input').placeholder = "Error al cargar productores";
                }
            } catch (err) {
                console.error("Error loading producers:", err);
                document.getElementById('select-producer-input').placeholder = "Error de red";
            }
        }

        function showProducerDetails(p) {
            document.getElementById('lbl-nombre').textContent = p.nombre_completo;
            document.getElementById('lbl-documento').textContent = `${p.tipo_documento} ${p.numero_documento}`;
            document.getElementById('edit-vereda').value = p.vereda || '';
            document.getElementById('edit-predio').value = p.nombre_predio || '';
            document.getElementById('edit-fecha-nacimiento').value = p.fecha_nacimiento || '';
            document.getElementById('edit-telefono').value = p.telefono || '';
            document.getElementById('edit-correo').value = p.correo_electronico || '';
            
            // Autocomplete organization name in Step 1
            const orgInput = document.getElementById('f01_nombre_organizacion');
            if (orgInput) {
                orgInput.value = p.nombre_organizacion || '';
            }

            document.getElementById('producer-info-box').style.display = 'block';
            const progressCard = document.getElementById('form-progress-card');
            if (progressCard) {
                progressCard.style.display = 'block';
            }
        }

        async function loadSavedPmapc(id) {
            try {
                const res = await fetch(`api/get_pmapc.php?id=${id}`);
                const result = await res.json();
                
                if (result.success && result.exists) {
                    populateForm(result.data);
                } else {
                    // Reset form to defaults
                    document.getElementById('pmapc-form').reset();
                    document.getElementById('productor_id').value = id;
                    // Clear dynamic rows to 1 default
                    clearDynamicRows();

                    // Re-populate f01_nombre_organizacion since reset() cleared it
                    const prod = allProducers.find(p => p.id == id);
                    if (prod) {
                        const orgInput = document.getElementById('f01_nombre_organizacion');
                        if (orgInput) {
                            orgInput.value = prod.nombre_organizacion || '';
                        }
                    }
                }
                updateFormCompletionProgress();
            } catch (err) {
                console.error("Error loading PMAPC data:", err);
            }
        }

        function populateForm(data) {
            // Helper to set field value if it exists in JSON
            const setVal = (id, val) => {
                const el = document.getElementById(id);
                if (el && val !== undefined) el.value = val;
            };

            const setInputByName = (name, val) => {
                const el = document.querySelector(`[name="${name}"]`);
                if (el && val !== undefined) el.value = val;
            };

            // Module 1 (Estratégico)
            if (data.f01) {
                setVal('f01_nombre_organizacion', data.f01.nombre_organizacion);
                setVal('f01_tipo_actividad', data.f01.tipo_actividad);
                setVal('f01_ubicacion', data.f01.ubicacion);
                setVal('f01_coordenadas', data.f01.coordenadas);
                setVal('f01_producto_principal', data.f01.producto_principal);
                setVal('f01_estado_actual', data.f01.estado_actual);
                setVal('f01_descripcion_general', data.f01.descripcion_general);
            }

            if (data.f02) {
                setVal('f02_mision', data.f02.mision);
                setVal('f02_vision', data.f02.vision);
                setVal('f02_valores', data.f02.valores);
            }

            if (data.f03) {
                setVal('f03_problema', data.f03.problema);
                setVal('f03_solucion', data.f03.solucion);
                setVal('f03_diferencial', data.f03.diferencial);
                setVal('f03_valor_ambiental', data.f03.valor_ambiental);
                setVal('f03_valor_social', data.f03.valor_social);
                setVal('f03_demostracion', data.f03.demostracion);
            }

            if (data.f04) {
                setVal('f04_fortalezas', data.f04.fortalezas);
                setVal('f04_oportunidades', data.f04.oportunidades);
                setVal('f04_debilidades', data.f04.debilidades);
                setVal('f04_amenazas', data.f04.amenazas);
            }

            // Module 2 (Mercado)
            const tbl05 = document.getElementById('tbl-f05').getElementsByTagName('tbody')[0];
            tbl05.innerHTML = '';
            if (data.f05 && Array.isArray(data.f05) && data.f05.length > 0) {
                data.f05.forEach(item => {
                    addRowF05(item.actor, item.perfil, item.ubicacion, item.necesidad, item.frecuencia, item.criterio, item.canal);
                });
            } else if (data.f05 && !Array.isArray(data.f05) && Object.keys(data.f05).length > 0) {
                // Backwards compatibility for old saved F05 data
                if (data.f05.perfil_directo) addRowF05('Cliente directo', data.f05.perfil_directo, data.f05.ubicacion_directo, data.f05.necesidad_directo, data.f05.frecuencia_directo, data.f05.criterio_directo, '');
                if (data.f05.perfil_final) addRowF05('Consumidor final', data.f05.perfil_final, data.f05.ubicacion_final, data.f05.necesidad_final, data.f05.frecuencia_final, data.f05.criterio_final, '');
                if (data.f05.perfil_local) addRowF05('Comprador local', data.f05.perfil_local, data.f05.ubicacion_local, data.f05.necesidad_local, data.f05.frecuencia_local, data.f05.criterio_local, '');
                if (data.f05.perfil_inst) addRowF05('Comprador institucional', data.f05.perfil_inst, data.f05.ubicacion_inst, data.f05.necesidad_inst, data.f05.frecuencia_inst, data.f05.criterio_inst, '');
                if (data.f05.perfil_rest) addRowF05('Restaurantes / plazas', data.f05.perfil_rest, data.f05.ubicacion_rest, data.f05.necesidad_rest, data.f05.frecuencia_rest, data.f05.criterio_rest, '');
            } else {
                addRowF05('Cliente directo');
                addRowF05('Consumidor final');
            }

            if (data.f06) {
                setVal('f06_necesidad', data.f06.necesidad);
                setVal('f06_como_sabe', data.f06.como_sabe);
                setVal('f06_a_quien_afecta', data.f06.a_quien_afecta);
                setVal('f06_evidencia', data.f06.evidencia);
                setVal('f06_oportunidad_organicos', data.f06.oportunidad_organicos);
            }

            const tbl07 = document.getElementById('tbl-f07').getElementsByTagName('tbody')[0];
            tbl07.innerHTML = '';
            if (data.f07 && Array.isArray(data.f07) && data.f07.length > 0) {
                data.f07.forEach(item => {
                    addRowF07(item.actor, item.aporta, item.recibe, item.trabajo, item.ambiental, item.accion);
                });
            } else if (data.f07 && !Array.isArray(data.f07) && Object.keys(data.f07).length > 0) {
                // Backwards compatibility for old saved F07 data
                if (data.f07.aporta_vecinos) addRowF07('Productores vecinos', data.f07.aporta_vecinos, data.f07.recibe_vecinos, '', '', data.f07.accion_vecinos);
                if (data.f07.aporta_asoc) addRowF07('Asociación campesina', data.f07.aporta_asoc, data.f07.recibe_asoc, '', '', data.f07.accion_asoc);
                if (data.f07.aporta_jac) addRowF07('Junta de Acción Comunal', data.f07.aporta_jac, data.f07.recibe_jac, '', '', data.f07.accion_jac);
                if (data.f07.aporta_ferias) addRowF07('Ferias campesinas', data.f07.aporta_ferias, data.f07.recibe_ferias, '', '', data.f07.accion_ferias);
                if (data.f07.aporta_inst) addRowF07('Instituciones (SENA, Alcaldía)', data.f07.aporta_inst, data.f07.recibe_inst, '', '', data.f07.accion_inst);
            } else {
                addRowF07('Productores vecinos');
                addRowF07('Asociación campesina');
                addRowF07('Junta de Acción Comunal');
                addRowF07('Ferias campesinas');
                addRowF07('Instituciones (SENA, Alcaldía)');
                addRowF07('Compradores locales');
                addRowF07('Aliados logísticos o digitales');
                addRowF07('Otros aliados');
            }

            if (data.f08) {
                setInputByName('f08_quien_degus', data.f08.quien_degus);
                setInputByName('f08_resultado_degus', data.f08.resultado_degus);
                setInputByName('f08_evidencia_degus', data.f08.evidencia_degus);

                setInputByName('f08_quien_ventas', data.f08.quien_ventas);
                setInputByName('f08_resultado_ventas', data.f08.resultado_ventas);
                setInputByName('f08_evidencia_ventas', data.f08.evidencia_ventas);

                setInputByName('f08_quien_cartas', data.f08.quien_cartas);
                setInputByName('f08_resultado_cartas', data.f08.resultado_cartas);
                setInputByName('f08_evidencia_cartas', data.f08.evidencia_cartas);

                setInputByName('f08_quien_encuesta', data.f08.quien_encuesta);
                setInputByName('f08_resultado_encuesta', data.f08.resultado_encuesta);
                setInputByName('f08_evidencia_encuesta', data.f08.evidencia_encuesta);

                setInputByName('f08_quien_entrevista', data.f08.quien_entrevista);
                setInputByName('f08_resultado_entrevista', data.f08.resultado_entrevista);
                setInputByName('f08_evidencia_entrevista', data.f08.evidencia_entrevista);

                setInputByName('f08_quien_feria', data.f08.quien_feria);
                setInputByName('f08_resultado_feria', data.f08.resultado_feria);
                setInputByName('f08_evidencia_feria', data.f08.evidencia_feria);

                setInputByName('f08_metodo_otro', data.f08.metodo_otro);
                setInputByName('f08_quien_otro', data.f08.quien_otro);
                setInputByName('f08_resultado_otro', data.f08.resultado_otro);
                setInputByName('f08_evidencia_otro', data.f08.evidencia_otro);
            }

            // Module 3 (Producción) - Dynamic F09 & F10
            const tbl09 = document.getElementById('tbl-f09').getElementsByTagName('tbody')[0];
            tbl09.innerHTML = '';
            if (data.f09 && data.f09.length > 0) {
                data.f09.forEach(item => {
                    addRowF09(item.producto, item.descripcion, item.unidad, item.insumos, item.almacenamiento, item.presentacion);
                });
            } else {
                addRowF09();
            }

            const tbl10 = document.getElementById('tbl-f10').getElementsByTagName('tbody')[0];
            tbl10.innerHTML = '';
            if (data.f10 && data.f10.length > 0) {
                data.f10.forEach((item, index) => {
                    addRowF10(index + 1, item.actividad, item.tiempo, item.responsable);
                });
            } else {
                addRowF10();
            }

            const tbl11 = document.getElementById('tbl-f11').getElementsByTagName('tbody')[0];
            tbl11.innerHTML = '';
            if (data.f11 && Array.isArray(data.f11) && data.f11.length > 0) {
                data.f11.forEach(item => {
                    addRowF11(item.insumo, item.cantidad, item.frecuencia, item.proveedor, item.sostenible);
                });
            } else if (data.f11 && !Array.isArray(data.f11) && Object.keys(data.f11).length > 0) {
                // Backwards compatibility for old saved F11 data
                if (data.f11.cant_abono || data.f11.prov_abono) addRowF11('Abono / Semillas', data.f11.cant_abono, data.f11.frec_abono, data.f11.prov_abono, data.f11.sost_abono);
                if (data.f11.cant_agua || data.f11.prov_agua) addRowF11('Agua de riego', data.f11.cant_agua, data.f11.frec_agua, data.f11.prov_agua, data.f11.sost_agua);
                if (data.f11.cant_emp || data.f11.prov_emp) addRowF11('Empaques', data.f11.cant_emp, data.f11.frec_emp, data.f11.prov_emp, data.f11.sost_emp);
            } else {
                addRowF11();
            }

            if (data.f12) {
                setVal('f12_produccion_estimada', data.f12.produccion_estimada);
                setVal('f12_produccion_maxima', data.f12.produccion_maxima);
                setVal('f12_limitantes_prod', data.f12.limitantes_prod);
                setVal('f12_limitantes_amb', data.f12.limitantes_amb);
            }

            // Module 4 (Límites)
            if (data.f12a) {
                setInputByName('f12a_estado_agua', data.f12a.estado_agua);
                setInputByName('f12a_limite_agua', data.f12a.limite_agua);
                setInputByName('f12a_efecto_agua', data.f12a.efecto_agua);
                setInputByName('f12a_accion_agua', data.f12a.accion_agua);

                setInputByName('f12a_estado_fuentes', data.f12a.estado_fuentes);
                setInputByName('f12a_limite_fuentes', data.f12a.limite_fuentes);
                setInputByName('f12a_efecto_fuentes', data.f12a.efecto_fuentes);
                setInputByName('f12a_accion_fuentes', data.f12a.accion_fuentes);

                setInputByName('f12a_estado_suelo', data.f12a.estado_suelo);
                setInputByName('f12a_limite_suelo', data.f12a.limite_suelo);
                setInputByName('f12a_efecto_suelo', data.f12a.efecto_suelo);
                setInputByName('f12a_accion_suelo', data.f12a.accion_suelo);

                setInputByName('f12a_estado_pendiente', data.f12a.estado_pendiente);
                setInputByName('f12a_limite_pendiente', data.f12a.limite_pendiente);
                setInputByName('f12a_efecto_pendiente', data.f12a.efecto_pendiente);
                setInputByName('f12a_accion_pendiente', data.f12a.accion_pendiente);

                setInputByName('f12a_estado_clima', data.f12a.estado_clima);
                setInputByName('f12a_limite_clima', data.f12a.limite_clima);
                setInputByName('f12a_efecto_clima', data.f12a.efecto_clima);
                setInputByName('f12a_accion_clima', data.f12a.accion_clima);

                setInputByName('f12a_estado_bio', data.f12a.estado_bio);
                setInputByName('f12a_limite_bio', data.f12a.limite_bio);
                setInputByName('f12a_efecto_bio', data.f12a.efecto_bio);
                setInputByName('f12a_accion_bio', data.f12a.accion_bio);

                setInputByName('f12a_estado_insumos', data.f12a.estado_insumos);
                setInputByName('f12a_limite_insumos', data.f12a.limite_insumos);
                setInputByName('f12a_efecto_insumos', data.f12a.efecto_insumos);
                setInputByName('f12a_accion_insumos', data.f12a.accion_insumos);

                setInputByName('f12a_estado_residuos', data.f12a.estado_residuos);
                setInputByName('f12a_limite_residuos', data.f12a.limite_residuos);
                setInputByName('f12a_efecto_residuos', data.f12a.efecto_residuos);
                setInputByName('f12a_accion_residuos', data.f12a.accion_residuos);
            }

            if (data.f12b) {
                const keys = ['virus', 'bacterias', 'picaduras', 'mordeduras', 'temperatura', 'radiacion', 'ruido', 'polvos', 'gases', 'particulado', 'posturas', 'movimientos', 'cargas', 'mecanico', 'locativo', 'electrico', 'transito'];
                keys.forEach(k => {
                    const row = data.f12b[k];
                    if (row) {
                        const siBox = document.querySelector(`[name="f12b_${k}_si"]`);
                        if (siBox) siBox.checked = row.si;
                        const noBox = document.querySelector(`[name="f12b_${k}_no"]`);
                        if (noBox) noBox.checked = row.no;
                        const altaBox = document.querySelector(`[name="f12b_${k}_f_alta"]`);
                        if (altaBox) altaBox.checked = row.f_alta;
                        const mediaBox = document.querySelector(`[name="f12b_${k}_f_media"]`);
                        if (mediaBox) mediaBox.checked = row.f_media;
                        const bajaBox = document.querySelector(`[name="f12b_${k}_f_baja"]`);
                        if (bajaBox) bajaBox.checked = row.f_baja;
                        
                        setInputByName(`f12b_${k}_controles`, row.controles);
                        setInputByName(`f12b_${k}_mejora`, row.mejora);
                    }
                });
            }

            if (data.f12c) {
                for (let i = 1; i <= 7; i++) {
                    const row = data.f12c[i];
                    if (row) {
                        setInputByName(`f12c_resp_${i}`, row.resp);
                        setInputByName(`f12c_frec_${i}`, row.frec);
                        setInputByName(`f12c_evidencia_${i}`, row.evidencia);
                    }
                }
            }

                        // Module 5 (Comercial)
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
            }

                        // Module 6 (Finanzas)
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
            }

                        // Module 7 (Sostenibilidad)
            if (data.f19_conclusion) setVal('f19_conclusion', data.f19_conclusion);
            if (data.f19) {
                for (let i = 0; i <= 6; i++) {
                    const row = data.f19[i];
                    if (row) {
                        setInputByName(`f19_ini_${i}`, row.ini);
                        setInputByName(`f19_meta_${i}`, row.meta);
                        setInputByName(`f19_frec_${i}`, row.frec);
                        setInputByName(`f19_resp_${i}`, row.resp);
                        setInputByName(`f19_evi_${i}`, row.evi);
                    }
                }
            }

            if (data.f19a_conclusion) setVal('f19a_conclusion', data.f19a_conclusion);
            if (data.f19a) {
                for (let i = 0; i <= 9; i++) {
                    const row = data.f19a[i];
                    if (row) {
                        setInputByName(`f19a_desc_${i}`, row.desc);
                        setInputByName(`f19a_cant_${i}`, row.cant);
                        setInputByName(`f19a_impacto_${i}`, row.impacto);
                        setInputByName(`f19a_mejora_${i}`, row.mejora);
                    }
                }
                calcTotalF19A();
            }

            if (data.f20_conclusion) setVal('f20_conclusion', data.f20_conclusion);
            if (data.f20) {
                for (let i = 0; i <= 4; i++) {
                    const row = data.f20[i];
                    if (row) {
                        setInputByName(`f20_cant_${i}`, row.cant);
                        setInputByName(`f20_manejo_${i}`, row.manejo);
                        setInputByName(`f20_destino_${i}`, row.destino);
                        setInputByName(`f20_resp_${i}`, row.resp);
                    }
                }
                calcTotalF20();
            }

            if (data.f21_conclusion) setVal('f21_conclusion', data.f21_conclusion);
            if (data.f21) {
                for (let i = 0; i <= 8; i++) {
                    const row = data.f21[i];
                    if (row) {
                        setInputByName(`f21_estado_${i}`, row.estado);
                        setInputByName(`f21_cal_${i}`, row.cal);
                        setInputByName(`f21_mejora_${i}`, row.mejora);
                        setInputByName(`f21_evi_${i}`, row.evi);
                    }
                }
                calcTotalF21();
            }

            if (data.f22) {
                for (let i = 0; i <= 6; i++) {
                    const row = data.f22[i];
                    if (row) {
                        setInputByName(`f22_accion_${i}`, row.accion);
                        setInputByName(`f22_plazo_${i}`, row.plazo);
                        setInputByName(`f22_resp_${i}`, row.resp);
                        setInputByName(`f22_rec_${i}`, row.rec);
                        setInputByName(`f22_ind_${i}`, row.ind);
                        setInputByName(`f22_evi_${i}`, row.evi);
                    }
                }
            }

            // Module 8 (Riesgos)
            if (data.f23) {
                setInputByName('f23_causa_clima', data.f23.causa_clima);
                setInputByName('f23_cons_clima', data.f23.cons_clima);
                setInputByName('f23_nivel_clima', data.f23.nivel_clima);
                setInputByName('f23_prev_clima', data.f23.prev_clima);

                setInputByName('f23_causa_costos', data.f23.causa_costos);
                setInputByName('f23_cons_costos', data.f23.cons_costos);
                setInputByName('f23_nivel_costos', data.f23.nivel_costos);
                setInputByName('f23_prev_costos', data.f23.prev_costos);
            }

            // Dynamic F24 (Plan Acción)
            const tbl24 = document.getElementById('tbl-f24').getElementsByTagName('tbody')[0];
            tbl24.innerHTML = '';
            if (data.f24 && data.f24.length > 0) {
                data.f24.forEach(item => {
                    addRowF24(item.actividad, item.componente, item.responsable, item.tiempo, item.resultado);
                });
            } else {
                addRowF24();
            }

            if (data.f26_coherencia) setVal('f26_coherencia', data.f26_coherencia);
            updateFormCompletionProgress();
        }

        // Form Completion Progress Bar
        function updateFormCompletionProgress() {
            if (!selectedProducerId) {
                const progressBar = document.getElementById('form-progress-bar');
                const progressText = document.getElementById('form-progress-text');
                if (progressBar) progressBar.style.width = '0%';
                if (progressText) progressText.innerText = '0% completado';
                return;
            }

            const form = document.getElementById('pmapc-form');
            if (!form) return;

            // 1. Gather all inputs inside the form
            const allInputs = Array.from(form.querySelectorAll('input, textarea, select'));
            
            // 2. Gather edit fields from info box
            const editFields = [
                document.getElementById('edit-vereda'),
                document.getElementById('edit-predio'),
                document.getElementById('edit-fecha-nacimiento'),
                document.getElementById('edit-telefono'),
                document.getElementById('edit-correo')
            ];

            let totalFields = 0;
            let filledFields = 0;

            const checkAndCount = (el) => {
                if (!el) return;
                
                // Skip hidden inputs
                if (el.tagName === 'INPUT' && el.type === 'hidden') return;
                
                // Skip submit or button type inputs
                if (el.tagName === 'INPUT' && (el.type === 'submit' || el.type === 'button')) return;

                totalFields++;
                
                // Check if filled
                if (el.tagName === 'SELECT') {
                    if (el.value !== '') {
                        filledFields++;
                    }
                } else if (el.type === 'checkbox') {
                    if (el.checked) {
                        filledFields++;
                    }
                } else if (el.type === 'radio') {
                    // Handled just in case, though there are none
                } else {
                    if (el.value && el.value.trim() !== '') {
                        filledFields++;
                    }
                }
            };

            allInputs.forEach(checkAndCount);
            editFields.forEach(checkAndCount);

            const percentage = totalFields > 0 ? Math.round((filledFields / totalFields) * 100) : 0;

            const progressBar = document.getElementById('form-progress-bar');
            const progressText = document.getElementById('form-progress-text');

            if (progressBar) {
                progressBar.style.width = percentage + '%';
            }
            if (progressText) {
                progressText.innerText = percentage + '% completado';
            }
        }

        function clearDynamicRows() {
            document.getElementById('tbl-f09').getElementsByTagName('tbody')[0].innerHTML = '';
            document.getElementById('tbl-f10').getElementsByTagName('tbody')[0].innerHTML = '';
            document.getElementById('tbl-f14').getElementsByTagName('tbody')[0].innerHTML = '';
            document.getElementById('tbl-f16').getElementsByTagName('tbody')[0].innerHTML = '';
            document.getElementById('tbl-f23').getElementsByTagName('tbody')[0].innerHTML = '';
            document.getElementById('tbl-f24').getElementsByTagName('tbody')[0].innerHTML = '';
            addRowF09();
            addRowF10();
            addRowF14();
            addRowF16();
            addRowF23();
            addRowF24();
        }

        // Stepper Navigation
        function goToStep(step) {
            // Se elimina la validación obligatoria de selectedProducerId para permitir navegación libre
            if (step < 1 || step > totalSteps) return;
            
            // Hide current section
            document.getElementById(`step-${currentStep}`).classList.remove('active');
            
            // Update node classes
            const nodes = document.querySelectorAll('.step-node');
            nodes[currentStep - 1].classList.remove('active');
            
            // Add completed classes
            for (let i = 0; i < step - 1; i++) {
                nodes[i].classList.add('completed');
            }
            for (let i = step - 1; i < totalSteps; i++) {
                nodes[i].classList.remove('completed');
            }

            currentStep = step;
            
            // Show new section
            document.getElementById(`step-${currentStep}`).classList.add('active');
            nodes[currentStep - 1].classList.add('active');

            // Scroll to top of form
            window.scrollTo({ top: document.querySelector('.stepper-container').offsetTop - 90, behavior: 'smooth' });

            // Button configurations
            document.getElementById('btn-prev').style.display = currentStep === 1 ? 'none' : 'block';
            
            const nextBtn = document.getElementById('btn-next');
            if (currentStep === totalSteps) {
                nextBtn.textContent = "Guardar Plan de Manejo";
                nextBtn.style.backgroundColor = "var(--primary-green)";
            } else {
                nextBtn.textContent = "Siguiente →";
                nextBtn.style.backgroundColor = "";
            }

            // Stepper Line Progress
            const progress = ((step - 1) / (totalSteps - 1)) * 90;
            document.getElementById('stepper-progress').style.width = `${progress}%`;
        }

        function changeStep(direction) {
            const nextStep = currentStep + direction;
            if (nextStep === totalSteps + 1) {
                // Submit Form
                submitForm();
            } else {
                goToStep(nextStep);
            }
        }

        // Add/Remove Rows logic
        function removeRow(btn) {
            const row = btn.closest('tr');
            const tbody = row.parentNode;
            if (tbody.rows.length > 1) {
                row.parentNode.removeChild(row);
                // Reindex steps for F10
                if (tbody.parentNode.id === 'tbl-f10') {
                    reindexSteps();
                }
                updateFormCompletionProgress();
            } else {
                alert("Debe mantener al menos una fila en el formulario.");
            }
        }

        function addRowF05(actor='', perfil='', ubicacion='', necesidad='', frecuencia='', criterio='', canal='') {
            const tbody = document.getElementById('tbl-f05').getElementsByTagName('tbody')[0];
            const tr = document.createElement('tr');
            tr.className = "dynamic-row-f05";
            tr.innerHTML = `
                <td><input type="text" name="f05_actor[]" value="${actor}" placeholder="Ej. Cliente directo"></td>
                <td><input type="text" name="f05_perfil[]" value="${perfil}" placeholder="Ej. Familias"></td>
                <td><input type="text" name="f05_ubicacion[]" value="${ubicacion}" placeholder="Ej. Bogotá"></td>
                <td><input type="text" name="f05_necesidad[]" value="${necesidad}" placeholder="Ej. Salud"></td>
                <td><input type="text" name="f05_frecuencia[]" value="${frecuencia}" placeholder="Ej. Semanal"></td>
                <td><input type="text" name="f05_criterio[]" value="${criterio}" placeholder="Ej. Precio"></td>
                <td><input type="text" name="f05_canal[]" value="${canal}" placeholder="Ej. WhatsApp"></td>
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

        function addRowF07(actor='', aporta='', recibe='', trabajo='', ambiental='', accion='') {
            const tbody = document.getElementById('tbl-f07').getElementsByTagName('tbody')[0];
            const tr = document.createElement('tr');
            tr.className = "dynamic-row-f07";
            tr.innerHTML = `
                <td><input type="text" name="f07_actor[]" value="${actor}" placeholder="Ej. Vecinos"></td>
                <td><input type="text" name="f07_aporta[]" value="${aporta}" placeholder="Ej. Flete"></td>
                <td><input type="text" name="f07_recibe[]" value="${recibe}" placeholder="Ej. Apoyo"></td>
                <td><input type="text" name="f07_trabajo[]" value="${trabajo}" placeholder="Ej. Compartir gastos"></td>
                <td><input type="text" name="f07_ambiental[]" value="${ambiental}" placeholder="Ej. Reciclaje"></td>
                <td><input type="text" name="f07_accion[]" value="${accion}" placeholder="Ej. Coordinar salidas"></td>
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

        function addRowF09(prod='', desc='', unit='', ins='', storage='', pres='') {
            const tbody = document.getElementById('tbl-f09').getElementsByTagName('tbody')[0];
            const tr = document.createElement('tr');
            tr.className = "dynamic-row-f09";
            tr.innerHTML = `
                <td><input type="text" name="f09_producto[]" value="${prod}" placeholder="Ej. Queso Fresco"></td>
                <td><input type="text" name="f09_descripcion[]" value="${desc}"></td>
                <td><input type="text" name="f09_unidad[]" value="${unit}" placeholder="Ej. kg"></td>
                <td><input type="text" name="f09_insumos[]" value="${ins}"></td>
                <td><input type="text" name="f09_almacenamiento[]" value="${storage}"></td>
                <td><input type="text" name="f09_presentacion[]" value="${pres}"></td>
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

        function addRowF10(step='', act='', time='', resp='') {
            const tbody = document.getElementById('tbl-f10').getElementsByTagName('tbody')[0];
            const tr = document.createElement('tr');
            tr.className = "dynamic-row-f10";
            const index = step || (tbody.rows.length + 1);
            tr.innerHTML = `
                <td><input type="text" name="f10_paso[]" value="${index}" style="text-align: center;"></td>
                <td><input type="text" name="f10_actividad[]" value="${act}"></td>
                <td><input type="text" name="f10_tiempo[]" value="${time}" placeholder="Ej. 1 hora"></td>
                <td><input type="text" name="f10_responsable[]" value="${resp}"></td>
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

        function reindexSteps() {
            const rows = document.querySelectorAll('#tbl-f10 tbody tr');
            rows.forEach((row, i) => {
                row.querySelector('[name="f10_paso[]"]').value = i + 1;
            });
        }

        function addRowF11(insumo='', cantidad='', frecuencia='', proveedor='', sostenible='') {
            const tbody = document.getElementById('tbl-f11').getElementsByTagName('tbody')[0];
            const tr = document.createElement('tr');
            tr.className = "dynamic-row-f11";
            tr.innerHTML = `
                <td><input type="text" name="f11_insumo[]" value="${insumo}" placeholder="Ej. Abono / Semillas"></td>
                <td><input type="text" name="f11_cantidad[]" value="${cantidad}"></td>
                <td><input type="text" name="f11_frecuencia[]" value="${frecuencia}"></td>
                <td><input type="text" name="f11_proveedor[]" value="${proveedor}"></td>
                <td><input type="text" name="f11_sostenible[]" value="${sostenible}"></td>
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

                function addRowF14(prod='', cost='', margin='', pmin='', pmercado='', log='', price='', just='') {
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
        }

        

        function addRowF23(tipo='Biológico', riesgo='', causa='', consecuencia='', nivel='Medio', prev='', resp='') {
            const tbody = document.getElementById('tbl-f23').getElementsByTagName('tbody')[0];
            const tr = document.createElement('tr');
            tr.className = "dynamic-row-f23";
            tr.innerHTML = `
                <td>
                    <select name="f23_tipo[]">
                        <option value="Biológico" ${tipo === 'Biológico' ? 'selected' : ''}>Biológico</option>
                        <option value="Físico" ${tipo === 'Físico' ? 'selected' : ''}>Físico</option>
                        <option value="Químico" ${tipo === 'Químico' ? 'selected' : ''}>Químico</option>
                        <option value="Psicosocial" ${tipo === 'Psicosocial' ? 'selected' : ''}>Psicosocial</option>
                        <option value="Biomecánico" ${tipo === 'Biomecánico' ? 'selected' : ''}>Biomecánico</option>
                        <option value="Condiciones de seguridad" ${tipo === 'Condiciones de seguridad' ? 'selected' : ''}>Condiciones de seguridad</option>
                        <option value="Fenómenos naturales" ${tipo === 'Fenómenos naturales' ? 'selected' : ''}>Fenómenos naturales</option>
                    </select>
                </td>
                <td><input type="text" name="f23_riesgo[]" value="${riesgo}" placeholder="Ej. Lluvias fuertes"></td>
                <td><input type="text" name="f23_causa[]" value="${causa}" placeholder="..."></td>
                <td><input type="text" name="f23_consecuencia[]" value="${consecuencia}" placeholder="..."></td>
                <td>
                    <select name="f23_nivel[]">
                        <option value="Alto" ${nivel === 'Alto' ? 'selected' : ''}>Alto</option>
                        <option value="Medio" ${nivel === 'Medio' ? 'selected' : ''}>Medio</option>
                        <option value="Bajo" ${nivel === 'Bajo' ? 'selected' : ''}>Bajo</option>
                    </select>
                </td>
                <td><input type="text" name="f23_prevencion[]" value="${prev}" placeholder="..."></td>
                <td><input type="text" name="f23_respuesta[]" value="${resp}" placeholder="..."></td>
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

        function addRowF24(act='', comp='Digital', resp='', time='', res='') {
            const tbody = document.getElementById('tbl-f24').getElementsByTagName('tbody')[0];
            const tr = document.createElement('tr');
            tr.className = "dynamic-row-f24";
            tr.innerHTML = `
                <td><input type="text" name="f24_actividad[]" value="${act}" placeholder="Ej. Etiquetas con QR"></td>
                <td>
                    <select name="f24_componente[]">
                        <option value="Digital" ${comp === 'Digital' ? 'selected' : ''}>Digital / Trazabilidad</option>
                        <option value="Productivo" ${comp === 'Productivo' ? 'selected' : ''}>Productivo</option>
                        <option value="Comercial" ${comp === 'Comercial' ? 'selected' : ''}>Comercial</option>
                        <option value="Financiero" ${comp === 'Financiero' ? 'selected' : ''}>Financiero</option>
                        <option value="Ambiental" ${comp === 'Ambiental' ? 'selected' : ''}>Ambiental</option>
                        <option value="Cooperacion" ${comp === 'Cooperacion' ? 'selected' : ''}>Cooperación Territorial</option>
                    </select>
                </td>
                <td><input type="text" name="f24_responsable[]" value="${resp}"></td>
                <td><input type="text" name="f24_tiempo[]" value="${time}" placeholder="Ej. Mes 2"></td>
                <td><input type="text" name="f24_resultado[]" value="${res}"></td>
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

                function getTableDataF19() {
            const arr = [];
            for (let i = 0; i <= 6; i++) {
                arr.push({
                    ini: document.querySelector(`[name="f19_ini_${i}"]`)?.value || '',
                    meta: document.querySelector(`[name="f19_meta_${i}"]`)?.value || '',
                    frec: document.querySelector(`[name="f19_frec_${i}"]`)?.value || '',
                    resp: document.querySelector(`[name="f19_resp_${i}"]`)?.value || '',
                    evi: document.querySelector(`[name="f19_evi_${i}"]`)?.value || ''
                });
            }
            return arr;
        }

        function getTableDataF19A() {
            const arr = [];
            for (let i = 0; i <= 9; i++) {
                arr.push({
                    desc: document.querySelector(`[name="f19a_desc_${i}"]`)?.value || '',
                    cant: document.querySelector(`[name="f19a_cant_${i}"]`)?.value || '',
                    impacto: document.querySelector(`[name="f19a_impacto_${i}"]`)?.value || '',
                    mejora: document.querySelector(`[name="f19a_mejora_${i}"]`)?.value || ''
                });
            }
            return arr;
        }

        function getTableDataF20() {
            const arr = [];
            for (let i = 0; i <= 4; i++) {
                arr.push({
                    cant: document.querySelector(`[name="f20_cant_${i}"]`)?.value || '',
                    manejo: document.querySelector(`[name="f20_manejo_${i}"]`)?.value || '',
                    destino: document.querySelector(`[name="f20_destino_${i}"]`)?.value || '',
                    resp: document.querySelector(`[name="f20_resp_${i}"]`)?.value || ''
                });
            }
            return arr;
        }

        function getTableDataF21() {
            const arr = [];
            for (let i = 0; i <= 8; i++) {
                arr.push({
                    estado: document.querySelector(`[name="f21_estado_${i}"]`)?.value || '',
                    cal: document.querySelector(`[name="f21_cal_${i}"]`)?.value || '',
                    mejora: document.querySelector(`[name="f21_mejora_${i}"]`)?.value || '',
                    evi: document.querySelector(`[name="f21_evi_${i}"]`)?.value || ''
                });
            }
            return arr;
        }

        function getTableDataF22() {
            const arr = [];
            for (let i = 0; i <= 6; i++) {
                arr.push({
                    accion: document.querySelector(`[name="f22_accion_${i}"]`)?.value || '',
                    plazo: document.querySelector(`[name="f22_plazo_${i}"]`)?.value || '',
                    resp: document.querySelector(`[name="f22_resp_${i}"]`)?.value || '',
                    rec: document.querySelector(`[name="f22_rec_${i}"]`)?.value || '',
                    ind: document.querySelector(`[name="f22_ind_${i}"]`)?.value || '',
                    evi: document.querySelector(`[name="f22_evi_${i}"]`)?.value || ''
                });
            }
            return arr;
        }

        function calcTotalF19A() {
            let sum = 0;
            for (let i = 0; i <= 9; i++) {
                const val = parseFloat(document.querySelector(`[name="f19a_impacto_${i}"]`)?.value) || 0;
                sum += val;
            }
            document.getElementById('f19a_total_impacto').value = sum > 0 ? sum : '';
        }

        function calcTotalF20() {
            let sum = 0;
            for (let i = 0; i <= 4; i++) {
                const val = parseFloat(document.querySelector(`[name="f20_cant_${i}"]`)?.value) || 0;
                sum += val;
            }
            document.getElementById('f20_total_cant').value = sum > 0 ? sum : '';
        }

        function calcTotalF21() {
            let sum = 0;
            for (let i = 0; i <= 8; i++) {
                const val = parseFloat(document.querySelector(`[name="f21_cal_${i}"]`)?.value) || 0;
                sum += val;
            }
            document.getElementById('f21_total_cal').value = sum > 0 ? sum : '';
        }

        // Calculations helper
        function calcPrecio(input) {
            const row = input.closest('tr');
            const costo = parseFloat(row.querySelector('[name="f14_costo[]"]').value) || 0;
            const margen = parseFloat(row.querySelector('[name="f14_margen[]"]').value) || 0;
            const logistica = parseFloat(row.querySelector('[name="f14_logistica[]"]').value) || 0;

            // Price = Costo + (Costo * Margen/100) + Costo Logístico
            const precio = Math.round(costo + (costo * (margen / 100)) + logistica);
            row.querySelector('[name="f14_precio[]"]').value = precio > 0 ? precio : '';
        }

                function calcIngresos(input) {
            const row = input.closest('tr');
            const cant = parseFloat(row.querySelector('[name="f15_cantidad[]"]').value) || 0;
            const precio = parseFloat(row.querySelector('[name="f15_precio[]"]').value) || 0;
            const ingresos = Math.round(cant * precio);
            row.querySelector('[name="f15_ingresos[]"]').value = ingresos > 0 ? ingresos : '';
        }\n\n                function calcInv(i) {
            const valUnit = parseFloat(document.querySelector(`[name="f16_valunit_${i}"]`).value) || 0;
            const qty = parseFloat(document.querySelector(`[name="f16_cant_${i}"]`).value) || 0;
            const valTotal = Math.round(valUnit * qty);
            document.querySelector(`[name="f16_total_${i}"]`).value = valTotal > 0 ? valTotal : '';
        }

        function calcInversion(input) { // Keep just in case
            const row = input.closest('tr');
            const valUnit = parseFloat(row.querySelector('[name="f16_val_unit[]"]').value) || 0;
            const qty = parseFloat(row.querySelector('[name="f16_cantidad[]"]').value) || 0;
            const valTotal = Math.round(valUnit * qty);
            row.querySelector('[name="f16_val_total[]"]').value = valTotal > 0 ? valTotal : '';
        }

        function calcNeto(mesNum) {
            const ingreso = parseFloat(document.querySelector(`[name="f18_ingreso_m${mesNum}"]`).value) || 0;
            const gprod = parseFloat(document.querySelector(`[name="f18_gprod_m${mesNum}"]`).value) || 0;
            const gamb = parseFloat(document.querySelector(`[name="f18_gamb_m${mesNum}"]`).value) || 0;
            const glog = parseFloat(document.querySelector(`[name="f18_glog_m${mesNum}"]`).value) || 0;

            const neto = ingreso - (gprod + gamb + glog);
            document.querySelector(`[name="f18_neto_m${mesNum}"]`).value = neto;
        }

        // Form Submit
        async function submitForm() {
            if (!selectedProducerId) {
                alert("Por favor selecciona un productor antes de guardar.");
                return;
            }

            const submitBtn = document.getElementById('btn-next');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = "Guardando...";

            const prod = allProducers.find(p => p.id == selectedProducerId);
            if (!prod) {
                alert("Error: No se encontró la información del productor seleccionado.");
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                return;
            }

            try {
                // 1. Guardar/actualizar los datos del productor en la base de datos (si se editaron)
                const editPayload = {
                    id: selectedProducerId,
                    nombre: prod.nombre_completo,
                    tipo_documento: prod.tipo_documento,
                    cedula: prod.numero_documento,
                    fecha_nacimiento: document.getElementById('edit-fecha-nacimiento').value || '1900-01-01',
                    telefono: document.getElementById('edit-telefono').value || '-',
                    correo: document.getElementById('edit-correo').value || '',
                    vereda: document.getElementById('edit-vereda').value || '-',
                    nombre_predio: document.getElementById('edit-predio').value || '-',
                    nombre_organizacion: document.getElementById('f01_nombre_organizacion').value
                };

                const updateRes = await fetch('api/update_productor.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(editPayload)
                });
                
                const updateResult = await updateRes.json();
                if (!updateRes.ok || !updateResult.success) {
                    throw new Error(updateResult.error || 'Error al actualizar los datos del productor.');
                }

                // Update the local allProducers array with the new edited values so they remain updated in memory
                prod.vereda = editPayload.vereda;
                prod.nombre_predio = editPayload.nombre_predio;
                prod.fecha_nacimiento = editPayload.fecha_nacimiento;
                prod.telefono = editPayload.telefono;
                prod.correo_electronico = editPayload.correo;
                prod.nombre_organizacion = editPayload.nombre_organizacion;

            } catch (err) {
                console.error("Error updating producer:", err);
                document.getElementById('error-modal-text').textContent = 'Error al actualizar los datos del productor: ' + err.message;
                document.getElementById('error-modal').classList.add('active');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                return;
            }

            // Construct structured JSON object
            const formData = new FormData(document.getElementById('pmapc-form'));
            const payload = {
                productor_id: selectedProducerId,
                data: {
                    f01: {
                        nombre_organizacion: formData.get('f01_nombre_organizacion'),
                        tipo_actividad: formData.get('f01_tipo_actividad'),
                        ubicacion: formData.get('f01_ubicacion'),
                        coordenadas: formData.get('f01_coordenadas'),
                        producto_principal: formData.get('f01_producto_principal'),
                        estado_actual: formData.get('f01_estado_actual'),
                        descripcion_general: formData.get('f01_descripcion_general')
                    },
                    f02: {
                        mision: formData.get('f02_mision'),
                        vision: formData.get('f02_vision'),
                        valores: formData.get('f02_valores')
                    },
                    f03: {
                        problema: formData.get('f03_problema'),
                        solucion: formData.get('f03_solucion'),
                        diferencial: formData.get('f03_diferencial'),
                        valor_ambiental: formData.get('f03_valor_ambiental'),
                        valor_social: formData.get('f03_valor_social'),
                        demostracion: formData.get('f03_demostracion')
                    },
                    f04: {
                        fortalezas: formData.get('f04_fortalezas'),
                        oportunidades: formData.get('f04_oportunidades'),
                        debilidades: formData.get('f04_debilidades'),
                        amenazas: formData.get('f04_amenazas')
                    },
                    f05: getTableDataF05(),
                    f06: {
                        necesidad: formData.get('f06_necesidad'),
                        como_sabe: formData.get('f06_como_sabe'),
                        a_quien_afecta: formData.get('f06_a_quien_afecta'),
                        evidencia: formData.get('f06_evidencia'),
                        oportunidad_organicos: formData.get('f06_oportunidad_organicos')
                    },
                    f07: getTableDataF07(),
                    f08: {
                        quien_degus: formData.get('f08_quien_degus'),
                        resultado_degus: formData.get('f08_resultado_degus'),
                        evidencia_degus: formData.get('f08_evidencia_degus'),

                        quien_ventas: formData.get('f08_quien_ventas'),
                        resultado_ventas: formData.get('f08_resultado_ventas'),
                        evidencia_ventas: formData.get('f08_evidencia_ventas'),

                        quien_cartas: formData.get('f08_quien_cartas'),
                        resultado_cartas: formData.get('f08_resultado_cartas'),
                        evidencia_cartas: formData.get('f08_evidencia_cartas'),

                        quien_encuesta: formData.get('f08_quien_encuesta'),
                        resultado_encuesta: formData.get('f08_resultado_encuesta'),
                        evidencia_encuesta: formData.get('f08_evidencia_encuesta'),

                        quien_entrevista: formData.get('f08_quien_entrevista'),
                        resultado_entrevista: formData.get('f08_resultado_entrevista'),
                        evidencia_entrevista: formData.get('f08_evidencia_entrevista'),

                        quien_feria: formData.get('f08_quien_feria'),
                        resultado_feria: formData.get('f08_resultado_feria'),
                        evidencia_feria: formData.get('f08_evidencia_feria'),

                        metodo_otro: formData.get('f08_metodo_otro'),
                        quien_otro: formData.get('f08_quien_otro'),
                        resultado_otro: formData.get('f08_resultado_otro'),
                        evidencia_otro: formData.get('f08_evidencia_otro')
                    },
                    f09: getTableDataF09(),
                    f10: getTableDataF10(),
                    f11: getTableDataF11(),
                    f12: {
                        produccion_estimada: formData.get('f12_produccion_estimada'),
                        produccion_maxima: formData.get('f12_produccion_maxima'),
                        limitantes_prod: formData.get('f12_limitantes_prod'),
                        limitantes_amb: formData.get('f12_limitantes_amb')
                    },
                    f12a: {
                        estado_agua: formData.get('f12a_estado_agua'),
                        limite_agua: formData.get('f12a_limite_agua'),
                        efecto_agua: formData.get('f12a_efecto_agua'),
                        accion_agua: formData.get('f12a_accion_agua'),

                        estado_fuentes: formData.get('f12a_estado_fuentes'),
                        limite_fuentes: formData.get('f12a_limite_fuentes'),
                        efecto_fuentes: formData.get('f12a_efecto_fuentes'),
                        accion_fuentes: formData.get('f12a_accion_fuentes'),

                        estado_suelo: formData.get('f12a_estado_suelo'),
                        limite_suelo: formData.get('f12a_limite_suelo'),
                        efecto_suelo: formData.get('f12a_efecto_suelo'),
                        accion_suelo: formData.get('f12a_accion_suelo'),

                        estado_pendiente: formData.get('f12a_estado_pendiente'),
                        limite_pendiente: formData.get('f12a_limite_pendiente'),
                        efecto_pendiente: formData.get('f12a_efecto_pendiente'),
                        accion_pendiente: formData.get('f12a_accion_pendiente'),

                        estado_clima: formData.get('f12a_estado_clima'),
                        limite_clima: formData.get('f12a_limite_clima'),
                        efecto_clima: formData.get('f12a_efecto_clima'),
                        accion_clima: formData.get('f12a_accion_clima'),

                        estado_bio: formData.get('f12a_estado_bio'),
                        limite_bio: formData.get('f12a_limite_bio'),
                        efecto_bio: formData.get('f12a_efecto_bio'),
                        accion_bio: formData.get('f12a_accion_bio'),

                        estado_insumos: formData.get('f12a_estado_insumos'),
                        limite_insumos: formData.get('f12a_limite_insumos'),
                        efecto_insumos: formData.get('f12a_efecto_insumos'),
                        accion_insumos: formData.get('f12a_accion_insumos'),

                        estado_residuos: formData.get('f12a_estado_residuos'),
                        limite_residuos: formData.get('f12a_limite_residuos'),
                        efecto_residuos: formData.get('f12a_efecto_residuos'),
                        accion_residuos: formData.get('f12a_accion_residuos')
                    },
                    f12b: getTableDataF12B(),
                    f12c: getTableDataF12C(),
                                        f13: getTableDataF13(),
                    f14: getTableDataF14(),
                    f15: getTableDataF15(),
                    f15a: getTableDataF15A(),
                    f15b: getTableDataF15B(),
                    f15c: getTableDataF15C(),
                    f16: getTableDataF16(),
                    f17: {
                        desc_fijos: formData.get('f17_desc_fijos'),
                        val_fijos: formData.get('f17_val_fijos'),
                        desc_variables: formData.get('f17_desc_variables'),
                        val_variables: formData.get('f17_val_variables'),
                        desc_amb: formData.get('f17_desc_amb'),
                        val_amb: formData.get('f17_val_amb'),
                        desc_log: formData.get('f17_desc_log'),
                        val_log: formData.get('f17_val_log')
                    },
                    f18: getTableDataF18(),
                                        f19_conclusion: document.getElementById('f19_conclusion').value,
                    f19: getTableDataF19(),
                    f19a_conclusion: document.getElementById('f19a_conclusion').value,
                    f19a: getTableDataF19A(),
                    f20_conclusion: document.getElementById('f20_conclusion').value,
                    f20: getTableDataF20(),
                    f21_conclusion: document.getElementById('f21_conclusion').value,
                    f21: getTableDataF21(),
                    f22: getTableDataF22(),
                                        f23: getTableDataF23(),
                    f24: getTableDataF24(),
                    f25: getTableDataF25(),
                    f26: getTableDataF26(),
                    f26_coherencia: document.getElementById('f26_coherencia').value
                }
            };

            try {
                const res = await fetch('api/submit_pmapc.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                
                const result = await res.json();
                if (res.ok && result.success) {
                    document.getElementById('success-modal').classList.add('active');
                } else {
                    document.getElementById('error-modal-text').textContent = result.error || 'Ocurrió un error al guardar.';
                    document.getElementById('error-modal').classList.add('active');
                }
            } catch (err) {
                console.error("Submit error:", err);
                document.getElementById('error-modal-text').textContent = 'Error de red al intentar guardar los datos.';
                document.getElementById('error-modal').classList.add('active');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        }

        // Table array parsing helpers
        function getTableDataF05() {
            const data = [];
            document.querySelectorAll('.dynamic-row-f05').forEach(row => {
                const actor = row.querySelector('[name="f05_actor[]"]').value.trim();
                if (actor) {
                    data.push({
                        actor: actor,
                        perfil: row.querySelector('[name="f05_perfil[]"]').value,
                        ubicacion: row.querySelector('[name="f05_ubicacion[]"]').value,
                        necesidad: row.querySelector('[name="f05_necesidad[]"]').value,
                        frecuencia: row.querySelector('[name="f05_frecuencia[]"]').value,
                        criterio: row.querySelector('[name="f05_criterio[]"]').value,
                        canal: row.querySelector('[name="f05_canal[]"]').value
                    });
                }
            });
            return data;
        }

        function getTableDataF07() {
            const data = [];
            document.querySelectorAll('.dynamic-row-f07').forEach(row => {
                const actor = row.querySelector('[name="f07_actor[]"]').value.trim();
                if (actor) {
                    data.push({
                        actor: actor,
                        aporta: row.querySelector('[name="f07_aporta[]"]').value,
                        recibe: row.querySelector('[name="f07_recibe[]"]').value,
                        trabajo: row.querySelector('[name="f07_trabajo[]"]').value,
                        ambiental: row.querySelector('[name="f07_ambiental[]"]').value,
                        accion: row.querySelector('[name="f07_accion[]"]').value
                    });
                }
            });
            return data;
        }

        function getTableDataF09() {
            const data = [];
            document.querySelectorAll('.dynamic-row-f09').forEach(row => {
                const producto = row.querySelector('[name="f09_producto[]"]').value.trim();
                if (producto) {
                    data.push({
                        producto: producto,
                        descripcion: row.querySelector('[name="f09_descripcion[]"]').value,
                        unidad: row.querySelector('[name="f09_unidad[]"]').value,
                        insumos: row.querySelector('[name="f09_insumos[]"]').value,
                        almacenamiento: row.querySelector('[name="f09_almacenamiento[]"]').value,
                        presentacion: row.querySelector('[name="f09_presentacion[]"]').value
                    });
                }
            });
            return data;
        }

        function getTableDataF10() {
            const data = [];
            document.querySelectorAll('.dynamic-row-f10').forEach(row => {
                const actividad = row.querySelector('[name="f10_actividad[]"]').value.trim();
                if (actividad) {
                    data.push({
                        paso: row.querySelector('[name="f10_paso[]"]').value,
                        actividad: actividad,
                        tiempo: row.querySelector('[name="f10_tiempo[]"]').value,
                        responsable: row.querySelector('[name="f10_responsable[]"]').value
                    });
                }
            });
            return data;
        }

        function getTableDataF11() {
            const data = [];
            document.querySelectorAll('.dynamic-row-f11').forEach(row => {
                const insumo = row.querySelector('[name="f11_insumo[]"]').value.trim();
                if (insumo) {
                    data.push({
                        insumo: insumo,
                        cantidad: row.querySelector('[name="f11_cantidad[]"]').value,
                        frecuencia: row.querySelector('[name="f11_frecuencia[]"]').value,
                        proveedor: row.querySelector('[name="f11_proveedor[]"]').value,
                        sostenible: row.querySelector('[name="f11_sostenible[]"]').value
                    });
                }
            });
            return data;
        }

        function getTableDataF12B() {
            const data = {};
            const keys = ['virus', 'bacterias', 'picaduras', 'mordeduras', 'temperatura', 'radiacion', 'ruido', 'polvos', 'gases', 'particulado', 'posturas', 'movimientos', 'cargas', 'mecanico', 'locativo', 'electrico', 'transito'];
            keys.forEach(k => {
                const siBox = document.querySelector(`[name="f12b_${k}_si"]`);
                const noBox = document.querySelector(`[name="f12b_${k}_no"]`);
                const altaBox = document.querySelector(`[name="f12b_${k}_f_alta"]`);
                const mediaBox = document.querySelector(`[name="f12b_${k}_f_media"]`);
                const bajaBox = document.querySelector(`[name="f12b_${k}_f_baja"]`);
                const controles = document.querySelector(`[name="f12b_${k}_controles"]`)?.value;
                const mejora = document.querySelector(`[name="f12b_${k}_mejora"]`)?.value;
                
                data[k] = {
                    si: siBox ? siBox.checked : false,
                    no: noBox ? noBox.checked : false,
                    f_alta: altaBox ? altaBox.checked : false,
                    f_media: mediaBox ? mediaBox.checked : false,
                    f_baja: bajaBox ? bajaBox.checked : false,
                    controles: controles || '',
                    mejora: mejora || ''
                };
            });
            return data;
        }

        function getTableDataF12C() {
            const data = {};
            for (let i = 1; i <= 7; i++) {
                data[i] = {
                    resp: document.querySelector(`[name="f12c_resp_${i}"]`)?.value || '',
                    frec: document.querySelector(`[name="f12c_frec_${i}"]`)?.value || '',
                    evidencia: document.querySelector(`[name="f12c_evidencia_${i}"]`)?.value || ''
                };
            }
            return data;
        }

                function getTableDataF13() {
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
        }

        function getTableDataF16() {
            const data = [];
            document.querySelectorAll('.dynamic-row-f16').forEach(row => {
                const desc = row.querySelector('[name="f16_descripcion[]"]').value.trim();
                if (desc) {
                    data.push({
                        tipo: row.querySelector('[name="f16_tipo[]"]').value,
                        descripcion: desc,
                        val_unit: row.querySelector('[name="f16_val_unit[]"]').value,
                        cantidad: row.querySelector('[name="f16_cantidad[]"]').value,
                        val_total: row.querySelector('[name="f16_val_total[]"]').value,
                        fuente: row.querySelector('[name="f16_fuente[]"]').value
                    });
                }
            });
            return data;
        }

        function getTableDataF18() {
            const data = {};
            for (let i = 1; i <= 6; i++) {
                data[`ingreso_m${i}`] = document.querySelector(`[name="f18_ingreso_m${i}"]`).value;
                data[`gprod_m${i}`] = document.querySelector(`[name="f18_gprod_m${i}"]`).value;
                data[`gamb_m${i}`] = document.querySelector(`[name="f18_gamb_m${i}"]`).value;
                data[`glog_m${i}`] = document.querySelector(`[name="f18_glog_m${i}"]`).value;
                data[`neto_m${i}`] = document.querySelector(`[name="f18_neto_m${i}"]`).value;
            }
            return data;
        }

        function getTableDataF23() {
            const arr = [];
            document.querySelectorAll('.dynamic-row-f23').forEach(row => {
                const riesgo = row.querySelector('[name="f23_riesgo[]"]').value.trim();
                if (riesgo) {
                    arr.push({
                        tipo: row.querySelector('[name="f23_tipo[]"]').value,
                        riesgo: riesgo,
                        causa: row.querySelector('[name="f23_causa[]"]').value,
                        consecuencia: row.querySelector('[name="f23_consecuencia[]"]').value,
                        nivel: row.querySelector('[name="f23_nivel[]"]').value,
                        prevencion: row.querySelector('[name="f23_prevencion[]"]').value,
                        respuesta: row.querySelector('[name="f23_respuesta[]"]').value
                    });
                }
            });
            return arr;
        }

        function getTableDataF24() {
            const data = [];
            document.querySelectorAll('.dynamic-row-f24').forEach(row => {
                const act = row.querySelector('[name="f24_actividad[]"]').value.trim();
                if (act) {
                    data.push({
                        actividad: act,
                        componente: row.querySelector('[name="f24_componente[]"]').value,
                        responsable: row.querySelector('[name="f24_responsable[]"]').value,
                        tiempo: row.querySelector('[name="f24_tiempo[]"]').value,
                        resultado: row.querySelector('[name="f24_resultado[]"]').value
                    });
                }
            });
            return data;
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
        }

        async function logout() {
            try {
                await fetch('api/logout.php');
                window.location.reload();
            } catch (error) {
                console.error('Logout error:', error);
            }
        }
    