import re

file_path = r'c:\Users\sotoc\OneDrive\somos_sumapaz\memoria_sumapaz\pmapc.html'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

dom_pop_code = """
            // DOM Population for F19..F26 static input fields
            const getArr = (formatKey, arrayKey) => {
                const b = getBlock(formatKey);
                if (!b) return [];
                if (Array.isArray(b)) return b;
                if (arrayKey && Array.isArray(b[arrayKey])) return b[arrayKey];
                for (let k in b) {
                    if (Array.isArray(b[k])) return b[k];
                }
                return [];
            };

            // F19
            const rowsF19 = getArr("F19", "seguimiento_huella_multiples_filas");
            rowsF19.forEach((item, i) => {
                setInputByName(`f19_ini_${i}`, item.dato_inicial || item.estado || '');
                setInputByName(`f19_meta_${i}`, item.meta || item.impacto_1_a_5 || '');
                setInputByName(`f19_frec_${i}`, item.frecuencia || item.cantidad || '');
                setInputByName(`f19_resp_${i}`, item.responsable || '');
                setInputByName(`f19_evi_${i}`, item.evidencia || item.accion || '');
            });

            // F19A / Indicadores
            const rowsF19A = getArr("F19", "indicadores_huella_multiples_filas");
            rowsF19A.forEach((item, i) => {
                setInputByName(`f19a_desc_${i}`, item.variable || item.descripcion || '');
                setInputByName(`f19a_cant_${i}`, item.cantidad || item.estado || '');
                setInputByName(`f19a_impacto_${i}`, item.impacto_1_a_5 || item.impacto || '');
                setInputByName(`f19a_mejora_${i}`, item.accion || item.mejora || '');
            });

            // F20
            const rowsF20 = getArr("F20", "economia_circular_residuos_multiples_filas");
            rowsF20.forEach((item, i) => {
                setInputByName(`f20_cant_${i}`, item.cantidad || '');
                setInputByName(`f20_manejo_${i}`, item.manejo_actual || item.manejo || '');
                setInputByName(`f20_destino_${i}`, item.destino || '');
                setInputByName(`f20_resp_${i}`, item.responsable || '');
            });

            // F21
            const rowsF21 = getArr("F21", "evaluacion_ambiental_regenerativa_multiples_filas");
            rowsF21.forEach((item, i) => {
                setInputByName(`f21_diag_${i}`, item.estado || item.descripcion || '');
                setInputByName(`f21_nivel_${i}`, item.calificacion_1_a_5 || item.calificacion || '');
                setInputByName(`f21_accion_${i}`, item.accion || '');
                setInputByName(`f21_evi_${i}`, item.evidencia || '');
            });

            // F22
            const rowsF22 = getArr("F22", "plan_manejo_mitigacion_multiples_filas");
            rowsF22.forEach((item, i) => {
                setInputByName(`f22_accion_${i}`, item.accion || '');
                setInputByName(`f22_prio_${i}`, item.prioridad || '');
                setInputByName(`f22_plazo_${i}`, item.plazo || '');
                setInputByName(`f22_resp_${i}`, item.responsable || '');
                setInputByName(`f22_rec_${i}`, item.recursos || '');
                setInputByName(`f22_ind_${i}`, item.indicador || '');
                setInputByName(`f22_evi_${i}`, item.evidencia || '');
            });

            // F22A
            const rowsF22A = getArr("F22A", "adaptacion_cambio_climatico_multiples_filas");
            rowsF22A.forEach((item, i) => {
                setInputByName(`f22a_sit_${i}`, item.situacion || '');
                setInputByName(`f22a_riesgo_${i}`, item.riesgo || '');
                setInputByName(`f22a_accion_${i}`, item.accion_propuesta || item.accion || '');
            });

            // F23
            const rowsF23 = getArr("F23", "matriz_riesgos_integrales_multiples_filas");
            rowsF23.forEach((item, i) => {
                setInputByName(`f23_causa_${i}`, item.causa || '');
                setInputByName(`f23_cons_${i}`, item.consecuencia || '');
                setInputByName(`f23_nivel_${i}`, item.nivel || '');
                setInputByName(`f23_prev_${i}`, item.prevencion || '');
                setInputByName(`f23_resp_${i}`, item.respuesta || item.responsable || '');
            });

            // F24
            const rowsF24 = getArr("F24", "plan_accion_multiples_filas");
            rowsF24.forEach((item, i) => {
                setInputByName(`f24_comp_${i}`, item.componente || '');
                setInputByName(`f24_resp_${i}`, item.responsable || '');
                setInputByName(`f24_tiempo_${i}`, item.tiempo || '');
                setInputByName(`f24_rec_${i}`, item.recursos || '');
                setInputByName(`f24_res_${i}`, item.resultado || '');
                setInputByName(`f24_evi_${i}`, item.evidencia || '');
            });

            // F25
            const rowsF25 = getArr("F25", "indicadores_integrales_multiples_filas");
            rowsF25.forEach((item, i) => {
                setInputByName(`f25_meta_${i}`, item.meta || '');
                setInputByName(`f25_frec_${i}`, item.frecuencia || '');
                setInputByName(`f25_resp_${i}`, item.responsable || '');
                setInputByName(`f25_evi_${i}`, item.evidencia || '');
            });

            // F26
            const rowsF26 = getArr("F26", "matriz_coherencia_sistemica_multiples_filas");
            rowsF26.forEach((item, i) => {
                setInputByName(`f26_prod_${i}`, item.efecto_productivo || '');
                setInputByName(`f26_com_${i}`, item.efecto_comercial || '');
                setInputByName(`f26_fin_${i}`, item.efecto_financiero || '');
                setInputByName(`f26_amb_${i}`, item.efecto_ambiental || '');
                setInputByName(`f26_ajuste_${i}`, item.ajuste_necesario || '');
            });
"""

# Insert before end of populateForm function
old_pop_end = "updateFormCompletionProgress();\n        }"
new_pop_end = dom_pop_code + "\n            updateFormCompletionProgress();\n        }"

content = content.replace(old_pop_end, new_pop_end)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated populateForm in pmapc.html with DOM input population for F19..F26!")
