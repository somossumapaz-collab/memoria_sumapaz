import re

file_path = r'c:\Users\sotoc\OneDrive\somos_sumapaz\memoria_sumapaz\pmapc.html'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

normalize_js_code = """
        function normalizePmapcJson(raw) {
            if (!raw || typeof raw !== 'object') return raw;
            const data = JSON.parse(JSON.stringify(raw));

            const getBlock = (code) => {
                const u = "PMAPC_" + code.toUpperCase();
                const l = code.toLowerCase();
                return data[u] || data["pmapc_" + l] || data[l] || data[code.toUpperCase()] || null;
            };

            // F01
            const b01 = getBlock("F01");
            if (b01) {
                data.f01 = data.f01 || {};
                data.f01.nombre_organizacion = b01.nombre_unidad_productiva || b01.nombre_organizacion || data.f01.nombre_organizacion;
                data.f01.tipo_actividad = b01.tipo_actividad || data.f01.tipo_actividad;
                data.f01.ubicacion = b01.ubicacion_especifica || b01.ubicacion || data.f01.ubicacion;
                data.f01.coordenadas = b01.coordenadas || data.f01.coordenadas;
                data.f01.producto_principal = b01.producto_servicio_principal || b01.producto_principal || data.f01.producto_principal;
                data.f01.estado_actual = b01.estado_actual || data.f01.estado_actual;
                data.f01.descripcion_general = b01.descripcion_general || data.f01.descripcion_general;
                data.productora = data.productora || b01.persona_entrevistada;
                data.nombre_organizacion = data.nombre_organizacion || data.f01.nombre_organizacion;
            }

            // F02
            const b02 = getBlock("F02");
            if (b02) {
                data.f02 = data.f02 || {};
                data.f02.mision = b02.mision || data.f02.mision;
                data.f02.vision = b02.vision || data.f02.vision;
                data.f02.valores = b02.valores || data.f02.valores;
            }

            // F03
            const b03 = getBlock("F03");
            if (b03) {
                data.f03 = data.f03 || {};
                data.f03.problema = b03.por_que_adquieren_el_servicio || b03.problema || data.f03.problema;
                data.f03.solucion = b03.beneficio_cliente || b03.solucion || data.f03.solucion;
                data.f03.diferencial = b03.diferencial || data.f03.diferencial;
                data.f03.valor_ambiental = b03.valor_ambiental || data.f03.valor_ambiental;
                data.f03.valor_social = b03.valor_social_comunitario || b03.valor_social || data.f03.valor_social;
                data.f03.demostracion = b03.evidencia || b03.demostracion || data.f03.demostracion;
            }

            // F04
            const b04 = getBlock("F04");
            if (b04) {
                data.f04 = data.f04 || {};
                data.f04.fortalezas = b04.fortalezas || data.f04.fortalezas;
                data.f04.oportunidades = b04.oportunidades || data.f04.oportunidades;
                data.f04.debilidades = b04.debilidades || data.f04.debilidades;
                data.f04.amenazas = b04.amenazas || data.f04.amenazas;
            }

            // F05
            const b05 = getBlock("F05");
            if (b05) {
                const rows = b05.perfiles_cliente_multiples_filas || (Array.isArray(b05) ? b05 : []);
                if (Array.isArray(rows) && rows.length > 0) {
                    data.f05 = rows.map(r => ({
                        actor: r.tipo_actor || r.actor || '',
                        perfil: r.perfil_que_busca || r.perfil || '',
                        ubicacion: r.ubicacion || '',
                        necesidad: r.necesidad || '',
                        frecuencia: r.frecuencia || '',
                        criterio: r.criterio_compra || r.criterio || '',
                        canal: r.canal || ''
                    }));
                }
            }

            // F06
            const b06 = getBlock("F06");
            if (b06) {
                data.f06 = data.f06 || {};
                data.f06.necesidad = b06.que_buscan_los_compradores || b06.necesidad || data.f06.necesidad;
                data.f06.como_sabe = b06.como_se_conoce_la_necesidad || b06.como_sabe || data.f06.como_sabe;
                data.f06.a_quien_afecta = b06.quienes_compran_o_podrian_comprar || b06.a_quien_afecta || data.f06.a_quien_afecta;
                data.f06.evidencia = b06.evidencia || data.f06.evidencia;
                data.f06.oportunidad_organicos = b06.ventaja_territorial || b06.oportunidad_organicos || data.f06.oportunidad_organicos;
                data.f06.cambio = b06.cambios_demanda || b06.cambio || data.f06.cambio;
                data.f06.dificultad = b06.dificultades || b06.dificultad || data.f06.dificultad;
            }

            // F07
            const b07 = getBlock("F07");
            if (b07) {
                const rows = b07.alianzas_cooperacion_multiples_filas || (Array.isArray(b07) ? b07 : []);
                if (Array.isArray(rows) && rows.length > 0) {
                    data.f07 = rows.map(r => ({
                        actor: r.actor_aliado || r.actor || '',
                        aporta: r.que_aporta || r.aporta || '',
                        recibe: r.que_recibe || r.recibe || '',
                        trabajo: r.trabajo_conjunto || r.trabajo || '',
                        ambiental: r.aporte_ambiental || r.ambiental || '',
                        accion: r.accion || ''
                    }));
                }
            }

            // F08
            const b08 = getBlock("F08");
            if (b08) {
                const rows = b08.validaciones_mercado_multiples_filas || (Array.isArray(b08) ? b08 : []);
                if (Array.isArray(rows) && rows.length > 0) {
                    data.f08 = data.f08 || {};
                    const tagMap = ['degus', 'ventas', 'cartas', 'encuesta', 'entrevista', 'feria', 'otro'];
                    rows.forEach((r, idx) => {
                        const tag = tagMap[idx] || 'otro';
                        data.f08['quien_' + tag] = r.a_quien || r.quien || '';
                        data.f08['resultado_' + tag] = r.resultado || '';
                        data.f08['motivacion_' + tag] = r.motivacion || '';
                        data.f08['evidencia_' + tag] = r.evidencia || '';
                    });
                }
            }

            // F09
            const b09 = getBlock("F09");
            if (b09) {
                const rows = b09.fichas_tecnicas_multiples_filas || (Array.isArray(b09) ? b09 : []);
                if (Array.isArray(rows) && rows.length > 0) {
                    data.f09 = rows.map(r => ({
                        producto: r.servicio_o_producto || r.servicio || r.producto || '',
                        descripcion: r.descripcion_tecnica || r.descripcion || '',
                        unidad: r.unidad || '',
                        insumos: r.insumos_principales || r.insumos || '',
                        almacenamiento: r.condiciones_especiales || r.almacenamiento || '',
                        presentacion: r.presentacion || '',
                        diferencial: r.diferencial || ''
                    }));
                }
            }

            // F10
            const b10 = getBlock("F10");
            if (b10) {
                const rows = b10.proceso_prestacion_servicio_multiples_filas || (Array.isArray(b10) ? b10 : []);
                if (Array.isArray(rows) && rows.length > 0) {
                    data.f10 = rows.map(r => ({
                        bien: r.servicio || r.bien || '',
                        unidades: r.unidad || r.unidades || '',
                        actividad: r.actividad_proceso || r.actividad_del_proceso || r.actividad || '',
                        tiempo: r.tiempo_estimado || r.tiempo || ''
                    }));
                }
            }

            // F11
            const b11 = getBlock("F11");
            if (b11) {
                const rows = b11.insumos_multiples_filas || (Array.isArray(b11) ? b11 : []);
                if (Array.isArray(rows) && rows.length > 0) {
                    data.f11 = rows.map(r => ({
                        insumo: r.insumo || '',
                        cantidad: r.cantidad_unidad || r.cantidad || '',
                        frecuencia: r.frecuencia || '',
                        proveedor: r.proveedor_fuente || r.proveedor || '',
                        toxicidad: r.toxicidad || '',
                        impacto: r.impacto_potencial || r.impacto || '',
                        manejo: r.manejo_ambiental || r.manejo || ''
                    }));
                }
            }

            // F12
            const b12 = getBlock("F12");
            if (b12) {
                data.f12 = data.f12 || {};
                data.f12.produccion_estimada = b12.produccion_mensual_real || b12.produccion_estimada || data.f12.produccion_estimada;
                data.f12.produccion_maxima = b12.produccion_maxima_posible || b12.capacidad_mensual_teorica || b12.produccion_maxima || data.f12.produccion_maxima;
                data.f12.area = b12.area_numero_habitaciones || b12.area || data.f12.area;
                data.f12.limitantes_prod = b12.limitantes_productivos || b12.limitantes_prod || data.f12.limitantes_prod;
                data.f12.limitantes_amb = b12.limitantes_ambientales || b12.limitantes_amb || data.f12.limitantes_amb;
                data.f12.capacidad_instalada = b12.capacidad_instalada || data.f12.capacidad_instalada;
                data.f12.capacidad_utilizada = b12.capacidad_utilizada || data.f12.capacidad_utilizada;
                data.f12.misma_cantidad = b12.produce_mismo_todo_ano !== undefined ? b12.produce_mismo_todo_ano : data.f12.misma_cantidad;
                data.f12.alcanza_demanda = b12.alcanza_para_demanda !== undefined ? b12.alcanza_para_demanda : data.f12.alcanza_demanda;
                data.f12.necesidad_sostenible = b12.necesidades_aumentar_sosteniblemente || b12.necesidad_sostenible || data.f12.necesidad_sostenible;
            }

            return data;
        }

        function populateForm(rawData) {
            if (!rawData) return;
            const data = normalizePmapcJson(rawData);
"""

# Replace start of populateForm
content = content.replace("        function populateForm(data) {\n            if (!data) return;", normalize_js_code)

# Replace JSON upload parse block to normalize before producer matching
old_parse = """                            const parsed = parseJsonSafely(loadedTranscriptText);
                            const pmapcData = parsed.data || parsed;"""

new_parse = """                            const parsed = parseJsonSafely(loadedTranscriptText);
                            const pmapcData = normalizePmapcJson(parsed.data || parsed);"""

content = content.replace(old_parse, new_parse)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Successfully updated pmapc.html with normalizePmapcJson!")
