import re

with open('pmapc.html', 'r', encoding='utf-8') as f:
    html = f.read()

# 1. Replace HTML for Modulo 7 (from F19 to end of F22)
new_html = """                <!-- F19 Huellas -->
                <div class="format-block">
                    <div class="format-title"><span>F19</span> Indicadores de Huella Hídrica y de Carbono</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f19">
                            <thead>
                                <tr>
                                    <th style="width: 20%;">Indicador</th>
                                    <th style="width: 25%;">Qué se mide</th>
                                    <th>Dato inicial</th>
                                    <th>Meta de reducción o mejora</th>
                                    <th>Frecuencia de medición</th>
                                    <th>Responsable</th>
                                    <th>Evidencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Consumo de agua</strong></td>
                                    <td>Litros usados por ciclo, mes o unidad de producto</td>
                                    <td><input type="text" name="f19_ini_0" placeholder="..."></td>
                                    <td><input type="text" name="f19_meta_0" placeholder="..."></td>
                                    <td><input type="text" name="f19_frec_0" placeholder="..."></td>
                                    <td><input type="text" name="f19_resp_0" placeholder="..."></td>
                                    <td><input type="text" name="f19_evi_0" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Fuente de agua</strong></td>
                                    <td>Origen del agua y medidas de protección</td>
                                    <td><input type="text" name="f19_ini_1" placeholder="..."></td>
                                    <td><input type="text" name="f19_meta_1" placeholder="..."></td>
                                    <td><input type="text" name="f19_frec_1" placeholder="..."></td>
                                    <td><input type="text" name="f19_resp_1" placeholder="..."></td>
                                    <td><input type="text" name="f19_evi_1" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Uso de energía</strong></td>
                                    <td>Consumo mensual o uso de combustibles</td>
                                    <td><input type="text" name="f19_ini_2" placeholder="..."></td>
                                    <td><input type="text" name="f19_meta_2" placeholder="..."></td>
                                    <td><input type="text" name="f19_frec_2" placeholder="..."></td>
                                    <td><input type="text" name="f19_resp_2" placeholder="..."></td>
                                    <td><input type="text" name="f19_evi_2" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Transporte</strong></td>
                                    <td>Distancia recorrida y tipo de transporte para ventas o insumos</td>
                                    <td><input type="text" name="f19_ini_3" placeholder="..."></td>
                                    <td><input type="text" name="f19_meta_3" placeholder="..."></td>
                                    <td><input type="text" name="f19_frec_3" placeholder="..."></td>
                                    <td><input type="text" name="f19_resp_3" placeholder="..."></td>
                                    <td><input type="text" name="f19_evi_3" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Uso de insumos</strong></td>
                                    <td>Cantidad y tipo de insumos orgánicos o convencionales</td>
                                    <td><input type="text" name="f19_ini_4" placeholder="..."></td>
                                    <td><input type="text" name="f19_meta_4" placeholder="..."></td>
                                    <td><input type="text" name="f19_frec_4" placeholder="..."></td>
                                    <td><input type="text" name="f19_resp_4" placeholder="..."></td>
                                    <td><input type="text" name="f19_evi_4" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Estimación de huella de carbono</strong></td>
                                    <td>Emisiones asociadas a energía, transporte e insumos</td>
                                    <td><input type="text" name="f19_ini_5" placeholder="..."></td>
                                    <td><input type="text" name="f19_meta_5" placeholder="..."></td>
                                    <td><input type="text" name="f19_frec_5" placeholder="..."></td>
                                    <td><input type="text" name="f19_resp_5" placeholder="..."></td>
                                    <td><input type="text" name="f19_evi_5" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Estimación de huella hídrica</strong></td>
                                    <td>Agua usada por unidad de producto o ciclo productivo</td>
                                    <td><input type="text" name="f19_ini_6" placeholder="..."></td>
                                    <td><input type="text" name="f19_meta_6" placeholder="..."></td>
                                    <td><input type="text" name="f19_frec_6" placeholder="..."></td>
                                    <td><input type="text" name="f19_resp_6" placeholder="..."></td>
                                    <td><input type="text" name="f19_evi_6" placeholder="..."></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top: 15px;">
                        <label for="f19_conclusion">Conclusión de datos obtenidos</label>
                        <textarea id="f19_conclusion" name="f19_conclusion" rows="2" placeholder="..."></textarea>
                    </div>
                </div>

                <div class="format-block">
                    <div class="format-title"><span>F19A</span> Variable Hídrica</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f19a">
                            <thead>
                                <tr>
                                    <th style="width: 25%;">Variable hídrica</th>
                                    <th style="width: 25%;">Descripción / estado</th>
                                    <th>¿Cuántas? (Si aplica, o "No aplica")</th>
                                    <th>Impacto en la producción (1-5)</th>
                                    <th>Acción de mejora</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Reutilización del agua</strong></td>
                                    <td><input type="text" name="f19a_desc_0" placeholder="..."></td>
                                    <td><input type="text" name="f19a_cant_0" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f19a_impacto_0" placeholder="1-5" oninput="calcTotalF19A()"></td>
                                    <td><input type="text" name="f19a_mejora_0" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Cercanía a fuentes hídricas</strong></td>
                                    <td><input type="text" name="f19a_desc_1" placeholder="..."></td>
                                    <td><input type="text" name="f19a_cant_1" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f19a_impacto_1" placeholder="1-5" oninput="calcTotalF19A()"></td>
                                    <td><input type="text" name="f19a_mejora_1" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Disponibilidad del recurso hídrico</strong></td>
                                    <td><input type="text" name="f19a_desc_2" placeholder="..."></td>
                                    <td><input type="text" name="f19a_cant_2" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f19a_impacto_2" placeholder="1-5" oninput="calcTotalF19A()"></td>
                                    <td><input type="text" name="f19a_mejora_2" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Uso en el proceso productivo</strong></td>
                                    <td><input type="text" name="f19a_desc_3" placeholder="..."></td>
                                    <td><input type="text" name="f19a_cant_3" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f19a_impacto_3" placeholder="1-5" oninput="calcTotalF19A()"></td>
                                    <td><input type="text" name="f19a_mejora_3" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Riesgo de contaminación</strong></td>
                                    <td><input type="text" name="f19a_desc_4" placeholder="..."></td>
                                    <td><input type="text" name="f19a_cant_4" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f19a_impacto_4" placeholder="1-5" oninput="calcTotalF19A()"></td>
                                    <td><input type="text" name="f19a_mejora_4" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Consumo en el proceso productivo</strong></td>
                                    <td><input type="text" name="f19a_desc_5" placeholder="..."></td>
                                    <td><input type="text" name="f19a_cant_5" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f19a_impacto_5" placeholder="1-5" oninput="calcTotalF19A()"></td>
                                    <td><input type="text" name="f19a_mejora_5" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Dependencia de fuentes externas</strong></td>
                                    <td><input type="text" name="f19a_desc_6" placeholder="..."></td>
                                    <td><input type="text" name="f19a_cant_6" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f19a_impacto_6" placeholder="1-5" oninput="calcTotalF19A()"></td>
                                    <td><input type="text" name="f19a_mejora_6" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Aprovechamiento de agua-lluvia (canecas, pocetas, albercas, tanques con geomembrana)</strong></td>
                                    <td><input type="text" name="f19a_desc_7" placeholder="..."></td>
                                    <td><input type="text" name="f19a_cant_7" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f19a_impacto_7" placeholder="1-5" oninput="calcTotalF19A()"></td>
                                    <td><input type="text" name="f19a_mejora_7" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>¿El predio cuenta con bebederos? (para el ganado)</strong></td>
                                    <td><input type="text" name="f19a_desc_8" placeholder="..."></td>
                                    <td><input type="text" name="f19a_cant_8" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f19a_impacto_8" placeholder="1-5" oninput="calcTotalF19A()"></td>
                                    <td><input type="text" name="f19a_mejora_8" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>¿Se cuenta con equipos eficientes de riego?</strong></td>
                                    <td><input type="text" name="f19a_desc_9" placeholder="..."></td>
                                    <td><input type="text" name="f19a_cant_9" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f19a_impacto_9" placeholder="1-5" oninput="calcTotalF19A()"></td>
                                    <td><input type="text" name="f19a_mejora_9" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td colspan="3" style="text-align: right;"><strong>Total</strong></td>
                                    <td><input type="number" id="f19a_total_impacto" readonly placeholder="Sumatoria"></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top: 15px;">
                        <label for="f19a_conclusion">Conclusión de resultados obtenidos</label>
                        <textarea id="f19a_conclusion" name="f19a_conclusion" rows="2" placeholder="..."></textarea>
                    </div>
                </div>

                <!-- F20 Economia Circular -->
                <div class="format-block">
                    <div class="format-title"><span>F20</span> Manejo de residuos, subproductos y empaques</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f20">
                            <thead>
                                <tr>
                                    <th style="width: 20%;">Residuo, subproducto o insumo</th>
                                    <th>Cantidad estimada de uso (kg)</th>
                                    <th>Manejo actual</th>
                                    <th style="width: 25%;">Acción circular propuesta</th>
                                    <th>Destino final</th>
                                    <th>Responsable</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Residuos orgánicos de cosecha</strong></td>
                                    <td><input type="number" name="f20_cant_0" placeholder="..." oninput="calcTotalF20()"></td>
                                    <td><input type="text" name="f20_manejo_0" placeholder="..."></td>
                                    <td>Compostaje, lumbricultura, reincorporación al suelo</td>
                                    <td><input type="text" name="f20_destino_0" placeholder="..."></td>
                                    <td><input type="text" name="f20_resp_0" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Residuos aprovechables (Plástico, vidrios, botellas, empaques, etc.)</strong></td>
                                    <td><input type="number" name="f20_cant_1" placeholder="..." oninput="calcTotalF20()"></td>
                                    <td><input type="text" name="f20_manejo_1" placeholder="..."></td>
                                    <td>Uso de empaques biodegradables, empaques retornables y reciclables</td>
                                    <td><input type="text" name="f20_destino_1" placeholder="..."></td>
                                    <td><input type="text" name="f20_resp_1" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Residuos no aprovechables</strong></td>
                                    <td><input type="number" name="f20_cant_2" placeholder="..." oninput="calcTotalF20()"></td>
                                    <td><input type="text" name="f20_manejo_2" placeholder="..."></td>
                                    <td>Separación y disposición responsable</td>
                                    <td><input type="text" name="f20_destino_2" placeholder="..."></td>
                                    <td><input type="text" name="f20_resp_2" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Subproductos con valor comercial</strong></td>
                                    <td><input type="number" name="f20_cant_3" placeholder="..." oninput="calcTotalF20()"></td>
                                    <td><input type="text" name="f20_manejo_3" placeholder="..."></td>
                                    <td>Transformación o venta asociativa</td>
                                    <td><input type="text" name="f20_destino_3" placeholder="..."></td>
                                    <td><input type="text" name="f20_resp_3" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Residuo peligroso</strong></td>
                                    <td><input type="number" name="f20_cant_4" placeholder="..." oninput="calcTotalF20()"></td>
                                    <td><input type="text" name="f20_manejo_4" placeholder="..."></td>
                                    <td>Residuos peligrosos (aceites, envases contaminados, sustancias químicas, agroquímicos, fertilizantes, plaguicidas, entre otros.)</td>
                                    <td><input type="text" name="f20_destino_4" placeholder="..."></td>
                                    <td><input type="text" name="f20_resp_4" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Total, de cantidad estimada</strong></td>
                                    <td><input type="number" id="f20_total_cant" readonly placeholder="Calculado"></td>
                                    <td colspan="4"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top: 15px;">
                        <label for="f20_conclusion">Conclusión de datos obtenidos</label>
                        <textarea id="f20_conclusion" name="f20_conclusion" rows="2" placeholder="..."></textarea>
                    </div>
                </div>

                <!-- F21 Evaluacion Regenerativa -->
                <div class="format-block">
                    <div class="format-title"><span>F21</span> Evaluación Ambiental Regenerativa</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f21">
                            <thead>
                                <tr>
                                    <th style="width: 10%;">Factor</th>
                                    <th style="width: 15%;">Indicador</th>
                                    <th style="width: 25%;">Descripción</th>
                                    <th>Estado actual</th>
                                    <th style="width: 10%;">Calificación 1-5</th>
                                    <th>Acción de mejora</th>
                                    <th>Evidencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Agua</strong></td>
                                    <td>Uso eficiente</td>
                                    <td>Prácticas de ahorro, riego eficiente, control de desperdicio</td>
                                    <td><input type="text" name="f21_estado_0" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f21_cal_0" placeholder="..." oninput="calcTotalF21()"></td>
                                    <td><input type="text" name="f21_mejora_0" placeholder="..."></td>
                                    <td><input type="text" name="f21_evi_0" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Agua</strong></td>
                                    <td>Protección de fuentes</td>
                                    <td>Conservación de nacimientos, rondas hídricas y zonas de recarga</td>
                                    <td><input type="text" name="f21_estado_1" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f21_cal_1" placeholder="..." oninput="calcTotalF21()"></td>
                                    <td><input type="text" name="f21_mejora_1" placeholder="..."></td>
                                    <td><input type="text" name="f21_evi_1" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Suelo</strong></td>
                                    <td>Conservación</td>
                                    <td>Cobertura vegetal, rotación, abonos orgánicos, prevención de compactación</td>
                                    <td><input type="text" name="f21_estado_2" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f21_cal_2" placeholder="..." oninput="calcTotalF21()"></td>
                                    <td><input type="text" name="f21_mejora_2" placeholder="..."></td>
                                    <td><input type="text" name="f21_evi_2" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Suelo</strong></td>
                                    <td>Erosión</td>
                                    <td>Control de escorrentía, barreras vivas, manejo de pendientes</td>
                                    <td><input type="text" name="f21_estado_3" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f21_cal_3" placeholder="..." oninput="calcTotalF21()"></td>
                                    <td><input type="text" name="f21_mejora_3" placeholder="..."></td>
                                    <td><input type="text" name="f21_evi_3" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Biodiversidad</strong></td>
                                    <td>Protección ecosistémica</td>
                                    <td>Conservación de flora, fauna, cercas vivas, no expansión a zonas sensibles</td>
                                    <td><input type="text" name="f21_estado_4" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f21_cal_4" placeholder="..." oninput="calcTotalF21()"></td>
                                    <td><input type="text" name="f21_mejora_4" placeholder="..."></td>
                                    <td><input type="text" name="f21_evi_4" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Energía</strong></td>
                                    <td>Uso eficiente</td>
                                    <td>Ahorro energético o alternativas limpias</td>
                                    <td><input type="text" name="f21_estado_5" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f21_cal_5" placeholder="..." oninput="calcTotalF21()"></td>
                                    <td><input type="text" name="f21_mejora_5" placeholder="..."></td>
                                    <td><input type="text" name="f21_evi_5" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Residuos</strong></td>
                                    <td>Manejo adecuado</td>
                                    <td>Separación, reciclaje, compostaje y disposición final</td>
                                    <td><input type="text" name="f21_estado_6" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f21_cal_6" placeholder="..." oninput="calcTotalF21()"></td>
                                    <td><input type="text" name="f21_mejora_6" placeholder="..."></td>
                                    <td><input type="text" name="f21_evi_6" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Insumos</strong></td>
                                    <td>Producción limpia</td>
                                    <td>Uso de insumos orgánicos o de bajo impacto</td>
                                    <td><input type="text" name="f21_estado_7" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f21_cal_7" placeholder="..." oninput="calcTotalF21()"></td>
                                    <td><input type="text" name="f21_mejora_7" placeholder="..."></td>
                                    <td><input type="text" name="f21_evi_7" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Normativa</strong></td>
                                    <td>Cumplimiento responsable</td>
                                    <td>Conocimiento y aplicación de permisos, restricciones y buenas prácticas</td>
                                    <td><input type="text" name="f21_estado_8" placeholder="..."></td>
                                    <td><input type="number" min="1" max="5" name="f21_cal_8" placeholder="..." oninput="calcTotalF21()"></td>
                                    <td><input type="text" name="f21_mejora_8" placeholder="..."></td>
                                    <td><input type="text" name="f21_evi_8" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td colspan="4" style="text-align: right;"><strong>Total Calificación</strong></td>
                                    <td><input type="number" id="f21_total_cal" readonly placeholder="Calculado"></td>
                                    <td colspan="2"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top: 15px;">
                        <label for="f21_conclusion">Resultados obtenidos</label>
                        <textarea id="f21_conclusion" name="f21_conclusion" rows="2" placeholder="..."></textarea>
                    </div>
                </div>

                <!-- F22 Plan Ambiental -->
                <div class="format-block">
                    <div class="format-title"><span>F22</span> Plan de Acción y Mitigación Ambiental</div>
                    <div class="table-input-container">
                        <table class="table-input" id="tbl-f22">
                            <thead>
                                <tr>
                                    <th style="width: 20%;">Impacto identificado</th>
                                    <th>Acción preventiva o correctiva</th>
                                    <th>Plazo</th>
                                    <th>Responsable</th>
                                    <th>Recursos necesarios</th>
                                    <th>Indicador</th>
                                    <th>Evidencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Uso excesivo de agua</strong></td>
                                    <td><input type="text" name="f22_accion_0" placeholder="..."></td>
                                    <td><input type="text" name="f22_plazo_0" placeholder="..."></td>
                                    <td><input type="text" name="f22_resp_0" placeholder="..."></td>
                                    <td><input type="text" name="f22_rec_0" placeholder="..."></td>
                                    <td><input type="text" name="f22_ind_0" placeholder="..."></td>
                                    <td><input type="text" name="f22_evi_0" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Afectación de fuentes hídricas</strong></td>
                                    <td><input type="text" name="f22_accion_1" placeholder="..."></td>
                                    <td><input type="text" name="f22_plazo_1" placeholder="..."></td>
                                    <td><input type="text" name="f22_resp_1" placeholder="..."></td>
                                    <td><input type="text" name="f22_rec_1" placeholder="..."></td>
                                    <td><input type="text" name="f22_ind_1" placeholder="..."></td>
                                    <td><input type="text" name="f22_evi_1" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Degradación del suelo</strong></td>
                                    <td><input type="text" name="f22_accion_2" placeholder="..."></td>
                                    <td><input type="text" name="f22_plazo_2" placeholder="..."></td>
                                    <td><input type="text" name="f22_resp_2" placeholder="..."></td>
                                    <td><input type="text" name="f22_rec_2" placeholder="..."></td>
                                    <td><input type="text" name="f22_ind_2" placeholder="..."></td>
                                    <td><input type="text" name="f22_evi_2" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Generación de residuos</strong></td>
                                    <td><input type="text" name="f22_accion_3" placeholder="..."></td>
                                    <td><input type="text" name="f22_plazo_3" placeholder="..."></td>
                                    <td><input type="text" name="f22_resp_3" placeholder="..."></td>
                                    <td><input type="text" name="f22_rec_3" placeholder="..."></td>
                                    <td><input type="text" name="f22_ind_3" placeholder="..."></td>
                                    <td><input type="text" name="f22_evi_3" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Uso inadecuado de insumos</strong></td>
                                    <td><input type="text" name="f22_accion_4" placeholder="..."></td>
                                    <td><input type="text" name="f22_plazo_4" placeholder="..."></td>
                                    <td><input type="text" name="f22_resp_4" placeholder="..."></td>
                                    <td><input type="text" name="f22_rec_4" placeholder="..."></td>
                                    <td><input type="text" name="f22_ind_4" placeholder="..."></td>
                                    <td><input type="text" name="f22_evi_4" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Pérdida de biodiversidad</strong></td>
                                    <td><input type="text" name="f22_accion_5" placeholder="..."></td>
                                    <td><input type="text" name="f22_plazo_5" placeholder="..."></td>
                                    <td><input type="text" name="f22_resp_5" placeholder="..."></td>
                                    <td><input type="text" name="f22_rec_5" placeholder="..."></td>
                                    <td><input type="text" name="f22_ind_5" placeholder="..."></td>
                                    <td><input type="text" name="f22_evi_5" placeholder="..."></td>
                                </tr>
                                <tr>
                                    <td><strong>Emisiones por transporte</strong></td>
                                    <td><input type="text" name="f22_accion_6" placeholder="..."></td>
                                    <td><input type="text" name="f22_plazo_6" placeholder="..."></td>
                                    <td><input type="text" name="f22_resp_6" placeholder="..."></td>
                                    <td><input type="text" name="f22_rec_6" placeholder="..."></td>
                                    <td><input type="text" name="f22_ind_6" placeholder="..."></td>
                                    <td><input type="text" name="f22_evi_6" placeholder="..."></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>"""

pattern_html = r'<!-- F19 Huellas -->.*?(?=<!-- ================= STEP 8: RIESGOS ================= -->)'
html = re.sub(pattern_html, new_html + '\n            </div>\n\n            ', html, flags=re.DOTALL)

# 2. Loading Logic
new_load_logic = """            // Module 7 (Sostenibilidad)
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
            }"""

pattern_load = r'// Module 7 \(Sostenibilidad\).*?(?=\s*// Module 8 \(Riesgos\))'
html = re.sub(pattern_load, new_load_logic, html, flags=re.DOTALL)

# 3. Form saving logic changes
json_update = """                    f19_conclusion: document.getElementById('f19_conclusion').value,
                    f19: getTableDataF19(),
                    f19a_conclusion: document.getElementById('f19a_conclusion').value,
                    f19a: getTableDataF19A(),
                    f20_conclusion: document.getElementById('f20_conclusion').value,
                    f20: getTableDataF20(),
                    f21_conclusion: document.getElementById('f21_conclusion').value,
                    f21: getTableDataF21(),
                    f22: getTableDataF22(),"""

# Note: Using getTableDataF18() regex to anchor
pattern_save = r'f19:\s*\{[^}]*\},\s*f20:\s*\{[^}]*\},\s*f21:\s*\{[^}]*\},\s*f22:\s*\{[^}]*\},'
html = re.sub(pattern_save, json_update, html, flags=re.DOTALL)


# 4. Javascript functions
new_funcs = """        function getTableDataF19() {
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
"""

html = html.replace('// Calculations helper', new_funcs + '\n        // Calculations helper')

with open('pmapc.html', 'w', encoding='utf-8') as f:
    f.write(html)
