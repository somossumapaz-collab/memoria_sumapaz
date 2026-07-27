import re

file_path = r'c:\Users\sotoc\OneDrive\somos_sumapaz\memoria_sumapaz\pmapc.html'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

full_normalize_code = """
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

            // F12A
            const b12a = getBlock("F12A");
            if (b12a) {
                const rows = b12a.limites_ambientales_multiples_filas || (Array.isArray(b12a) ? b12a : []);
                if (Array.isArray(rows) && rows.length > 0) {
                    data.f12a = data.f12a || {};
                    rows.forEach(r => {
                        const cond = (r.condicion || '').toLowerCase();
                        let tag = '';
                        if (cond.includes('agua') && !cond.includes('fuentes')) tag = 'agua';
                        else if (cond.includes('fuentes')) tag = 'fuentes';
                        else if (cond.includes('suelo')) tag = 'suelo';
                        else if (cond.includes('pendiente')) tag = 'pendiente';
                        else if (cond.includes('clima')) tag = 'clima';
                        else if (cond.includes('bio')) tag = 'bio';
                        else if (cond.includes('insumos')) tag = 'insumos';
                        else if (cond.includes('residuos')) tag = 'residuos';

                        if (tag) {
                            data.f12a['estado_' + tag] = r.estado_actual || r.estado || '';
                            data.f12a['limite_' + tag] = r.limite_restriccion || r.limite || '';
                            data.f12a['efecto_' + tag] = r.efecto || r.afectacion || '';
                            data.f12a['accion_' + tag] = r.accion_mejora || r.accion || '';
                        }
                    });
                }
            }

            // F12B
            const b12b = getBlock("F12B");
            if (b12b) {
                const rows = b12b.riesgos_sst_multiples_filas || (Array.isArray(b12b) ? b12b : []);
                if (Array.isArray(rows) && rows.length > 0) {
                    data.f12b = data.f12b || {};
                    rows.forEach(r => {
                        const peligro = (r.peligro || r.tipo || '').toLowerCase();
                        const keys = ['virus', 'bacterias', 'picaduras', 'mordeduras', 'temperatura', 'radiacion', 'ruido', 'polvos', 'gases', 'particulado', 'posturas', 'movimientos', 'cargas', 'mecanico', 'locativo', 'electrico', 'transito'];
                        let foundKey = keys.find(k => peligro.includes(k));
                        if (!foundKey && r.tipo) foundKey = r.tipo.toLowerCase();
                        if (foundKey) {
                            data.f12b[foundKey] = {
                                si: r.si || (r.aplica === 'Sí' || r.aplica === true),
                                no: r.no || (r.aplica === 'No'),
                                f_alta: r.alta || (r.nivel_riesgo === 'Alto'),
                                f_media: r.media || (r.nivel_riesgo === 'Medio'),
                                f_baja: r.baja || (r.nivel_riesgo === 'Bajo'),
                                controles: r.controles_actuales || r.controles || '',
                                mejora: r.accion || ''
                            };
                        }
                    });
                }
            }

            // F12C
            const b12c = getBlock("F12C");
            if (b12c) {
                const rows = b12c.plan_acciones_sst_multiples_filas || (Array.isArray(b12c) ? b12c : []);
                if (Array.isArray(rows) && rows.length > 0) {
                    data.f12c = data.f12c || {};
                    rows.forEach((r, idx) => {
                        if (idx < 7) {
                            data.f12c[idx + 1] = {
                                resp: r.responsable || '',
                                frec: r.frecuencia || '',
                                evidencia: r.evidencia || ''
                            };
                        }
                    });
                }
            }

            // F13
            const b13 = getBlock("F13");
            if (b13) {
                const rows = b13.canales_venta_multiples_filas || (Array.isArray(b13) ? b13 : []);
                if (Array.isArray(rows) && rows.length > 0) {
                    data.f13 = rows.map(r => ({
                        canal: r.canal || '',
                        activo: r.activo || r.si || false,
                        descripcion: r.descripcion || r.cual || '',
                        responsable: r.responsable || '',
                        frecuencia: r.frecuencia || ''
                    }));
                }
            }

            // F14
            const b14 = getBlock("F14");
            if (b14) {
                const rows = b14.estrategia_precios_multiples_filas || (Array.isArray(b14) ? b14 : []);
                if (Array.isArray(rows) && rows.length > 0) {
                    data.f14 = rows.map(r => ({
                        producto: r.servicio_o_producto || r.servicio || r.producto || '',
                        costo: r.costo_unitario || r.costo || '',
                        margen: r.margen || '',
                        pmin: r.precio_minimo || r.pmin || '',
                        referencia: r.referencia || '',
                        logistica: r.logistica_plataforma || r.logistica || '',
                        precio: r.precio_final || r.precio || '',
                        justificacion: r.justificacion || ''
                    }));
                }
            }

            // F15
            const b15 = getBlock("F15");
            if (b15) {
                const rows = b15.proyeccion_ventas_multiples_filas || (Array.isArray(b15) ? b15 : []);
                if (Array.isArray(rows) && rows.length > 0) {
                    data.f15 = rows.map(r => ({
                        producto: r.concepto_servicio || r.servicio || r.producto || '',
                        cantidad: r.cantidad_mensual || r.cantidad || '',
                        precio: r.precio || '',
                        ingresos: r.ingreso_mensual || r.ingresos || '',
                        pago: r.forma_pago || r.pago || '',
                        canal: r.canal || ''
                    }));
                }
            }

            // F15A
            const b15a = getBlock("F15A");
            if (b15a) {
                const rows = b15a.estrategia_fidelizacion_multiples_filas || (Array.isArray(b15a) ? b15a : []);
                if (Array.isArray(rows) && rows.length > 0) {
                    data.f15a = rows.map(r => ({
                        cliente: r.cliente || '',
                        estrategia: r.estrategia || '',
                        medio: r.medio || '',
                        frecuencia: r.frecuencia || '',
                        responsable: r.responsable || ''
                    }));
                }
            }

            // F15B
            const b15b = getBlock("F15B");
            if (b15b) {
                const rows = b15b.logistica_ultima_milla_multiples_filas || (Array.isArray(b15b) ? b15b : []);
                if (Array.isArray(rows) && rows.length > 0) {
                    data.f15b = rows.map(r => ({
                        servicio: r.servicio_insumo || r.servicio || '',
                        canal: r.canal || '',
                        tiempo: r.tiempo || '',
                        transporte: r.transporte_operacion || r.transporte || '',
                        condicion: r.condicion_calidad || r.condicion || '',
                        capacidad: r.capacidad || '',
                        costo: r.costo || '',
                        responsable: r.responsable || ''
                    }));
                }
            }

            // F15C
            const b15c = getBlock("F15C");
            if (b15c) {
                const rows = b15c.trazabilidad_qr_multiples_filas || (Array.isArray(b15c) ? b15c : []);
                if (Array.isArray(rows) && rows.length > 0) {
                    data.f15c = rows.map(r => ({
                        elemento: r.elemento || '',
                        informacion: r.informacion || '',
                        responsable: r.responsable || '',
                        medio: r.medio || '',
                        frecuencia: r.frecuencia || '',
                        evidencia: r.evidencia || ''
                    }));
                }
            }

            // F16
            const b16 = getBlock("F16");
            if (b16) {
                const rows = b16.inversion_requerida_multiples_filas || (Array.isArray(b16) ? b16 : []);
                if (Array.isArray(rows) && rows.length > 0) {
                    data.f16 = rows.map(r => ({
                        tipo: r.tipo || '',
                        descripcion: r.descripcion || r.desc || '',
                        valunit: r.valor_unitario || r.valunit || '',
                        cant: r.cantidad || r.cant || '',
                        total: r.total || '',
                        req: r.requisito || r.req || '',
                        fuente: r.fuente || ''
                    }));
                }
            }

            // F17
            const b17 = getBlock("F17");
            if (b17) {
                const rows = b17.costos_multiples_filas || (Array.isArray(b17) ? b17 : []);
                if (Array.isArray(rows) && rows.length > 0) {
                    data.f17 = rows.map(r => ({
                        tipo: r.tipo || '',
                        descripcion: r.descripcion || '',
                        valor: r.valor_mensual_ciclo || r.valor || '',
                        observaciones: r.observaciones || ''
                    }));
                }
            }

            // F18
            const b18 = getBlock("F18");
            if (b18) {
                const rows = b18.flujo_caja_mensual_12_filas_meses || (Array.isArray(b18) ? b18 : []);
                if (Array.isArray(rows) && rows.length > 0) {
                    data.f18 = rows.map(r => ({
                        mes: r.mes || '',
                        ingresos: r.ingresos || '',
                        gastos_op: r.gastos_operativos || r.gastos_op || '',
                        gastos_com: r.gastos_comerciales || r.gastos_com || '',
                        gastos_amb: r.gastos_ambientales || r.gastos_amb || '',
                        balance: r.balance || '',
                        observaciones: r.observaciones || ''
                    }));
                }
            }

            // F19
            const b19 = getBlock("F19");
            if (b19) {
                const rows = b19.indicadores_huella_multiples_filas || b19.seguimiento_huella_multiples_filas || (Array.isArray(b19) ? b19 : []);
                if (Array.isArray(rows) && rows.length > 0) {
                    data.f19 = rows.map(r => ({
                        variable: r.variable || r.indicador || '',
                        estado: r.estado || r.que_se_mide || '',
                        cantidad: r.cantidad || r.dato_inicial || '',
                        impacto: r.impacto_1_a_5 || r.meta || '',
                        accion: r.accion || r.evidencia || ''
                    }));
                }
            }

            // F20
            const b20 = getBlock("F20");
            if (b20) {
                const rows = b20.economia_circular_residuos_multiples_filas || (Array.isArray(b20) ? b20 : []);
                if (Array.isArray(rows) && rows.length > 0) {
                    data.f20 = rows.map(r => ({
                        residuo: r.residuo_recurso || r.residuo || '',
                        cantidad: r.cantidad || '',
                        manejo: r.manejo_actual || r.manejo || '',
                        accion: r.accion_circular || r.accion || '',
                        destino: r.destino || '',
                        responsable: r.responsable || ''
                    }));
                }
            }

            // F21
            const b21 = getBlock("F21");
            if (b21) {
                const rows = b21.evaluacion_ambiental_regenerativa_multiples_filas || (Array.isArray(b21) ? b21 : []);
                if (Array.isArray(rows) && rows.length > 0) {
                    data.f21 = rows.map(r => ({
                        factor: r.factor || r.criterio || '',
                        indicador: r.indicador || '',
                        descripcion: r.descripcion || r.pregunta_guia || '',
                        estado: r.estado || r.hallazgo_diagnostico || '',
                        calificacion: r.calificacion_1_a_5 || r.puntaje || '',
                        accion: r.accion || r.accion_recomendada || '',
                        evidencia: r.evidencia || r.evidencia_verificacion || ''
                    }));
                }
            }

            // F22
            const b22 = getBlock("F22");
            if (b22) {
                const rows = b22.plan_manejo_mitigacion_multiples_filas || (Array.isArray(b22) ? b22 : []);
                if (Array.isArray(rows) && rows.length > 0) {
                    data.f22 = rows.map(r => ({
                        impacto: r.impacto || '',
                        accion: r.accion || '',
                        prioridad: r.prioridad || '',
                        plazo: r.plazo || '',
                        responsable: r.responsable || '',
                        recursos: r.recursos || '',
                        indicador: r.indicador || '',
                        evidencia: r.evidencia || ''
                    }));
                }
            }

            // F22A
            const b22a = getBlock("F22A");
            if (b22a) {
                const rows = b22a.adaptacion_cambio_climatico_multiples_filas || (Array.isArray(b22a) ? b22a : []);
                if (Array.isArray(rows) && rows.length > 0) {
                    data.f22a = rows.map(r => ({
                        aspecto: r.aspecto || '',
                        situacion: r.situacion || '',
                        riesgo: r.riesgo || '',
                        accion: r.accion_propuesta || r.accion || ''
                    }));
                }
            }

            // F23
            const b23 = getBlock("F23");
            if (b23) {
                const rows = b23.matriz_riesgos_integrales_multiples_filas || (Array.isArray(b23) ? b23 : []);
                if (Array.isArray(rows) && rows.length > 0) {
                    data.f23 = rows.map(r => ({
                        tipo: r.tipo || '',
                        riesgo: r.riesgo || '',
                        causa: r.causa || '',
                        consecuencia: r.consecuencia || '',
                        nivel: r.nivel || '',
                        prevencion: r.prevencion || '',
                        respuesta: r.respuesta || ''
                    }));
                }
            }

            // F24
            const b24 = getBlock("F24");
            if (b24) {
                const rows = b24.plan_accion_multiples_filas || (Array.isArray(b24) ? b24 : []);
                if (Array.isArray(rows) && rows.length > 0) {
                    data.f24 = rows.map(r => ({
                        actividad: r.actividad || '',
                        componente: r.componente || '',
                        responsable: r.responsable || '',
                        tiempo: r.tiempo || '',
                        recursos: r.recursos || '',
                        resultado: r.resultado || '',
                        evidencia: r.evidencia || ''
                    }));
                }
            }

            // F25
            const b25 = getBlock("F25");
            if (b25) {
                const rows = b25.indicadores_integrales_multiples_filas || (Array.isArray(b25) ? b25 : []);
                if (Array.isArray(rows) && rows.length > 0) {
                    data.f25 = rows.map(r => ({
                        dimension: r.dimension || r.dimencion || '',
                        indicador: r.indicador || '',
                        meta: r.meta || '',
                        frecuencia: r.frecuencia || '',
                        responsable: r.responsable || '',
                        evidencia: r.evidencia || ''
                    }));
                }
            }

            // F26
            const b26 = getBlock("F26");
            if (b26) {
                const rows = b26.matriz_coherencia_sistemica_multiples_filas || (Array.isArray(b26) ? b26 : []);
                if (Array.isArray(rows) && rows.length > 0) {
                    data.f26 = rows.map(r => ({
                        decision: r.decision || r.variable_matriz || '',
                        efecto_productivo: r.efecto_productivo || '',
                        efecto_comercial: r.efecto_comercial || '',
                        efecto_financiero: r.efecto_financiero || '',
                        efecto_ambiental: r.efecto_ambiental || '',
                        ajuste_necesario: r.ajuste_necesario || r.accion_propuesta || ''
                    }));
                }
            }

            return data;
        }
"""

# Replace the normalizePmapcJson function in pmapc.html
pattern = r"function normalizePmapcJson\(raw\) \{.*?\n        \}"
content = re.sub(pattern, full_normalize_code.strip(), content, flags=re.DOTALL)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated pmapc.html with complete F01..F26 normalizePmapcJson function!")
