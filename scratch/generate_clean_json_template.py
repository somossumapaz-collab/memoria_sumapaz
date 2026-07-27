import json

template = {
    "titulo": "Plan de Manejo Ambiental, Productivo y Comercial (PMAPC) - Estructura Completa F01 a F26",
    "nombre_organizacion": "Nombre de la Unidad Productiva / Hotel / Finca",
    "productora": "Nombre Completo del Productor(a)",
    "vereda": "Nombre de la Vereda",
    "fecha_cargue": "2026-07-27",
    
    "f01": {
        "nombre_organizacion": "Nombre de la Unidad Productiva",
        "tipo_actividad": "Servicios turísticos / Agrícola / Pecuaria / Agroindustrial",
        "ubicacion": "Vereda, sector y predio",
        "coordenadas": "Coordenadas GPS (Latitud, Longitud)",
        "producto_principal": "Producto o servicio principal",
        "estado_actual": "Negocio en marcha / Idea de negocio / En producción",
        "personas_vinculadas": "Número de personas que integran la unidad",
        "descripcion_general": "Descripción detallada del negocio, origen, propósito y proyección."
    },
    
    "f02": {
        "mision": "Misión actual de la unidad productiva",
        "vision": "Visión a 3 o 5 años",
        "valores": "Valores institucionales (Servicio, Respeto, Cuidado del territorio, etc.)"
    },
    
    "f03": {
        "problema": "¿Por qué las personas adquieren el producto o servicio?",
        "solucion": "Beneficios clave y solución que recibe el cliente",
        "diferencial": "Diferencial competitivo frente a otros productores",
        "valor_ambiental": "Valor agregado en conservación, agua, suelo o biodiversidad",
        "valor_social": "Impacto social, comunitario y generación de empleo local",
        "demostracion": "Evidencias concretas que demuestran el diferencial"
    },

    "f04": {
        "fortalezas": "Fortalezas internas identificadas",
        "oportunidades": "Oportunidades del entorno y mercado",
        "debilidades": "Debilidades operativas o financieras a mejorar",
        "amenazas": "Amenazas climáticas, biológicas o externas"
    },

    "f05": [
        {
            "actor": "Tipo de Cliente (Ej: Cliente institucional / Visitante / Turista)",
            "perfil": "Perfil del cliente y qué busca",
            "ubicacion": "Procedencia u ubicación",
            "necesidad": "Necesidad principal a satisfacer",
            "frecuencia": "Frecuencia de compra o estancia",
            "criterio": "Criterio de selección",
            "canal": "Canal de contacto"
        }
    ],

    "f06": {
        "necesidad": "¿Qué buscan los compradores?",
        "como_sabe": "¿Cómo se comprobó o validó esa necesidad?",
        "a_quien_afecta": "¿Quiénes compran o podrían comprar?",
        "evidencia": "Evidencias comerciales (ventas, testimonios, ocupación)",
        "oportunidad_organicos": "Ventajas del territorio Sumapaz o producción limpia",
        "cambio": "Cambios y tendencias recientes en la demanda",
        "dificultad": "Dificultades principales de venta o comercialización"
    },

    "f07": [
        {
            "actor": "Nombre del Aliado / Organización",
            "tipo": "Tipo de alianza (Proveedor / Cliente / Colectivo)",
            "aporta": "¿Qué aporta el aliado?",
            "recibe": "¿Qué recibe a cambio?",
            "trabajo": "Acción conjunta acordada",
            "ambiental": "Compromiso ambiental asociado",
            "accion": "Acción inmediata de gestión"
        }
    ],

    "f08": [
        {
            "metodo": "Método de validación (Ej: Venta directa / Degustación / Airbnb)",
            "a_quien": "A quién se aplicó",
            "resultado": "Resultado comercial o aceptación",
            "motivacion": "Motivación del cliente",
            "evidencia": "Evidencia registrada (recibos, encuestas, registros)"
        }
    ],

    "f09": [
        {
            "producto": "Nombre del Producto o Servicio",
            "descripcion": "Descripción técnica detallada",
            "unidad": "Unidad de medida (Kg, Litros, Noche, Bolsa)",
            "insumos": "Insumos o materias primas principales",
            "almacenamiento": "Condiciones de almacenamiento y conservación",
            "presentacion": "Presentación y empaque",
            "diferencial": "Diferencial de calidad o presentación"
        }
    ],

    "f10": [
        {
            "bien": "Servicio / Producto",
            "unidades": "Unidades estimadas",
            "actividad": "Paso a paso del proceso productivo o de atención",
            "tiempo": "Tiempo estimado de ejecución"
        }
    ],

    "f11": [
        {
            "insumo": "Nombre del Insumo / Materia Prima",
            "cantidad": "Cantidad requerida",
            "frecuencia": "Frecuencia de compra",
            "proveedor": "Proveedor o fuente de origen",
            "toxicidad": "Nivel de toxicidad / riesgo",
            "impacto": "Impacto ambiental potencial",
            "manejo": "Manejo y prácticas sostenibles",
            "observaciones": "Observaciones adicionales"
        }
    ],

    "f12": {
        "produccion_estimada": "Producción o prestación mensual real",
        "produccion_maxima": "Capacidad máxima o teórica mensual",
        "area": "Área o infraestructura disponible (m2, Ha, N° camas)",
        "limitantes_prod": "Limitantes técnico-productivos clave",
        "limitantes_amb": "Limitantes ambientales y climáticos",
        "capacidad_instalada": "Infraestructura y equipos disponibles",
        "capacidad_utilizada": "Porcentaje de capacidad utilizada actualmente",
        "misma_cantidad": "¿Produce lo mismo todo el año?",
        "alcanza_demanda": "¿La capacidad actual alcanza para cubrir la demanda?",
        "necesidad_sostenible": "Requerimientos clave para escalar de manera sostenible"
    },

    "f12a": [
        {
            "condicion": "Aspecto ambiental (Agua / Suelo / Clima / Residuos)",
            "estado": "Estado actual diagnosticado",
            "limite": "Límite o restricción ecológica",
            "afectacion": "Operación o actividad afectada",
            "efecto": "Efecto o riesgo ambiental",
            "incidencia": "Nivel de incidencia (Alta / Media / Baja)",
            "accion": "Acción de mejora propuesto"
        }
    ],

    "f12b": [
        {
            "tipo": "Tipo de Riesgo SG-SST (Biológico / Químico / Biomecánico / Locativo)",
            "peligro": "Descripción del peligro u origen del riesgo",
            "si_no": "Sí / No",
            "nivel": "Nivel de riesgo (Alto / Medio / Bajo)",
            "controles": "Controles actuales existentes",
            "accion": "Acción correctiva o preventiva"
        }
    ],

    "f12c": [
        {
            "accion": "Acción preventiva SG-SST propuesta",
            "riesgo": "Riesgo que mitiga",
            "responsable": "Responsable de la ejecución",
            "frecuencia": "Frecuencia de control",
            "evidencia": "Evidencia de cumplimiento"
        }
    ],

    "f13": [
        {
            "canal": "Canal de comercialización o costo fijo",
            "activo": "Sí / No",
            "detalle": "Detalle de la estrategia o concepto de costo",
            "responsable": "Responsable",
            "frecuencia": "Frecuencia de pago o gestión"
        }
    ],

    "f14": [
        {
            "producto": "Nombre del Producto o Servicio",
            "costo": "Costo unitario de producción ($)",
            "margen": "Margen de ganancia esperado (%)",
            "pmin": "Precio mínimo de venta ($)",
            "referencia": "Precio de referencia en el mercado ($)",
            "logistica": "Costo de transporte o comisión de plataforma ($)",
            "precio": "Precio final al consumidor ($)",
            "justificacion": "Justificación de la fijación del precio"
        }
    ],

    "f15": [
        {
            "producto": "Producto o Servicio",
            "cantidad": "Cantidad mensual proyectada",
            "precio": "Precio estimado ($)",
            "ingresos": "Ingresos mensuales proyectados ($)",
            "pago": "Forma de pago (Contado / Transferencia / Crédito)",
            "canal": "Canal de venta asignado"
        }
    ],

    "f15a": [
        {
            "cliente": "Tipo de cliente",
            "estrategia": "Estrategia de fidelización",
            "medio": "Canal de comunicación",
            "frecuencia": "Frecuencia de contacto",
            "responsable": "Responsable"
        }
    ],

    "f15b": [
        {
            "servicio": "Producto / Servicio / Insumo",
            "canal": "Canal de distribución",
            "tiempo": "Tiempo de respuesta o entrega",
            "transporte": "Modo de transporte",
            "condicion": "Condiciones de conservación y calidad",
            "capacidad": "Capacidad de transporte",
            "costo": "Costo logístico",
            "responsable": "Responsable"
        }
    ],

    "f15c": [
        {
            "elemento": "Elemento a trazar (Identidad / Lote / Origen / Prácticas)",
            "informacion": "Información contenida",
            "responsable": "Responsable",
            "medio": "Medio de divulgación (Etiqueta QR / Ficha / Web)",
            "frecuencia": "Frecuencia de actualización",
            "evidencia": "Soporte digital"
        }
    ],

    "f16": [
        {
            "desc": "Descripción del activo, máquina, herramienta o inversión",
            "valunit": "Valor unitario ($)",
            "cant": "Cantidad necesaria",
            "total": "Valor total de inversión ($)",
            "req": "Requerimiento o justificación de uso",
            "fuente": "Fuente de recursos (Propios / Proyecto / Crédito)"
        }
    ],

    "f17": [
        {
            "tipo": "Tipo de costo o gasto (Mano de obra / Insumos / Servicios)",
            "descripcion": "Descripción del rubro",
            "valor": "Valor estimado mensual o por ciclo ($)",
            "observaciones": "Observaciones o detalle"
        }
    ],

    "f18": [
        {
            "mes": "Mes / Período (Enero, Febrero, ...)",
            "ingresos": "Ingresos proyectados ($)",
            "gastos_op": "Gastos operativos ($)",
            "gastos_com": "Gastos comerciales ($)",
            "gastos_amb": "Gastos y licencias ambientales ($)",
            "balance": "Balance o flujo neto ($)",
            "observaciones": "Observaciones"
        }
    ],

    "f19": [
        {
            "variable": "Variable ambiental (Agua / Energía / Residuos / Suelo)",
            "estado": "Estado actual de la variable",
            "cantidad": "Consumo o generación estimada",
            "impacto": "Nivel de impacto (1 a 5)",
            "accion": "Acción de reducción o eficiencia"
        }
    ],

    "f20": [
        {
            "residuo": "Tipo de residuo u subproducto generado",
            "cantidad": "Cantidad mensual aproximada",
            "manejo": "Manejo actual aplicado",
            "accion": "Acción de economía circular o aprovechamiento",
            "destino": "Disposición final",
            "responsable": "Responsable"
        }
    ],

    "f21": [
        {
            "criterio": "Criterio de madurez (Gestión hídrica / Suelos / Mercado)",
            "diagnostico": "Diagnóstico actual",
            "nivel": "Nivel de madurez (Inicial / Intermedio / Consolidado)",
            "accion": "Acción de fortalecimiento propuesta",
            "evidencia": "Evidencia de avance"
        }
    ],

    "f22": [
        {
            "impacto": "Impacto ambiental a prevenir o mitigar",
            "accion": "Medida de manejo ambiental propuesta",
            "prioridad": "Prioridad (Alta / Media / Baja)",
            "plazo": "Plazo de ejecución (0-3 meses, 3-6 meses, etc.)",
            "responsable": "Responsable de la medida",
            "recursos": "Recursos requeridos",
            "indicador": "Indicador de cumplimiento",
            "evidencia": "Evidencia o soporte"
        }
    ],

    "f22a": [
        {
            "aspecto": "Fenómeno climático (Lluvias / Heladas / Sequías)",
            "situacion": "Situación o vulnerabilidad identificada",
            "riesgo": "Nivel de riesgo (Alto / Medio / Bajo)",
            "accion": "Medida de adaptación al cambio climático"
        }
    ],

    "f23": [
        {
            "tipo": "Categoría de riesgo (Operativo / Financiero / Ambiental / SST)",
            "riesgo": "Descripción del riesgo",
            "causa": "Causa principal",
            "consecuencia": "Consecuencia potencial",
            "nivel": "Nivel de severidad (Alto / Medio / Bajo)",
            "prevencion": "Medida preventiva de control",
            "respuesta": "Plan de respuesta ante la contingencia"
        }
    ],

    "f24": [
        {
            "actividad": "Actividad o compromiso específico",
            "componente": "Componente (Productivo / Ambiental / Comercial / Social)",
            "responsable": "Responsable directo",
            "tiempo": "Plazo estimado de cumplimiento",
            "recursos": "Recursos o apoyo necesario",
            "resultado": "Resultado tangible esperado",
            "evidencia": "Evidencia verificable de cumplimiento"
        }
    ],

    "f25": [
        {
            "dimension": "Dimensión o categoría de indicador",
            "indicador": "Nombre del indicador",
            "meta": "Meta propuesta",
            "frecuencia": "Frecuencia de medición",
            "responsable": "Responsable",
            "evidencia": "Soporte de medición"
        }
    ],

    "f26": {
        "coherencia_tecnica": "Evaluación de coherencia técnica entre insumos y producción",
        "coherencia_financiera": "Evaluación de coherencia entre costos, precios e ingresos",
        "coherencia_ambiental": "Evaluación del plan de manejo ambiental y sostenibilidad",
        "calificacion_global": "Calificación propuesta (Ej: 35 de 50 puntos)",
        "concepto_tecnico": "Concepto técnico final y recomendaciones de ajuste",
        "evaluador": "Nombre del profesional o técnico evaluador",
        "fecha_evaluacion": "Fecha de la evaluación técnica"
    },

    "pdf_comentarios": "COMENTARIOS, OBSERVACIONES E INFORMACIÓN PENDIENTE DE VERIFICAR:\n- Registro y verificación de escrituras o documentos legales.\n- Inventario físico detallado de equipos y habitaciones.\n- Verificación de facturas y costos exactos de operación."
}

out_path = "PMAPC_Plantilla_Estructura_Completa_F01_F26.json"
with open(out_path, "w", encoding="utf-8") as f:
    json.dump(template, f, indent=2, ensure_ascii=False)

print(f"Generated {out_path} with size {len(json.dumps(template))} bytes.")
