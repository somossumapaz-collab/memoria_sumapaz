const fs = require('fs');

// Load the local XLSX library
const xlsxPath = 'c:/Users/sotoc/OneDrive/somos_sumapaz/memoria_sumapaz/assets/xlsx.full.min.js';
const xlsxContent = fs.readFileSync(xlsxPath, 'utf8');

// Evaluate XLSX in global scope
const vm = require('vm');
const context = {
    console: console,
    newDate: () => new Date(),
    setTimeout: setTimeout
};
vm.createContext(context);
vm.runInContext(xlsxContent, context);
const XLSX = context.XLSX;

console.log("XLSX library loaded. Version:", XLSX.version);

// Simulate downloadTemplateWithAnswers
function downloadTemplateWithAnswers() {
    try {
        if (typeof XLSX === 'undefined') {
            console.error('XLSX is undefined!');
            return;
        }
        // Sheet 1: Ficha Base
        const baseForm = [
            // Section I
            {
                "Sección": "I. Información General (Complementaria)",
                "Pregunta / Campo": "Teléfono",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Especifique el número de teléfono del productor (ej. 3123456789)"
            },
            {
                "Sección": "I. Información General (Complementaria)",
                "Pregunta / Campo": "Correo electrónico",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Especifique el correo electrónico (ej. productor@email.com)"
            },
            {
                "Sección": "I. Información General (Complementaria)",
                "Pregunta / Campo": "Coordenadas GPS del Predio",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Coordenadas en formato Latitud, Longitud (ej. 4.2592, -74.2255)"
            },
            {
                "Sección": "I. Información General (Complementaria)",
                "Pregunta / Campo": "Fecha de Caracterización",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Fecha del diligenciamiento en formato AAAA-MM-DD (ej. 2026-06-14)"
            },
            // Section II
            {
                "Sección": "II. Información Sociodemográfica",
                "Pregunta / Campo": "¿Hace parte de estos grupos poblacionales?",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Escriba los números de las opciones que apliquen separados por comas (ej. 1, 3):\n1: Mujer cabeza de hogar\n2: LGBTIQ+\n3: Víctima del conflicto armado\n4: Afrodescendiente\n5: Indígena\n6: Mujer < 25 o > 60\n7: Joven rural\n8: Ninguna"
            },
            {
                "Sección": "II. Información Sociodemográfica",
                "Pregunta / Campo": "¿Presenta algún tipo de discapacidad?",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Escriba una de las siguientes opciones:\n- No\n- Sí"
            },
            {
                "Sección": "II. Información Sociodemográfica",
                "Pregunta / Campo": "Si respondió sí, ¿qué tipo?",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Especifique el tipo de discapacidad si la respuesta anterior fue 'Sí'"
            },
            // Section III
            {
                "Sección": "III. Información Organizacional",
                "Pregunta / Campo": "Tipo de organización",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Escriba una de las siguientes opciones:\n- Productor individual\n- Gremio / Federación\n- Asociación campesina\n- Sociedad por acciones simplificada\n- Fundación\n- Organización\n- Ninguna"
            },
            {
                "Sección": "III. Información Organizacional",
                "Pregunta / Campo": "Nombre de la organización",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Escriba el nombre de la organización a la que pertenece"
            },
            {
                "Sección": "III. Información Organizacional",
                "Pregunta / Campo": "Extensión del predio (Hectáreas)",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Escriba la extensión del predio en hectáreas utilizando números decimales (ej. 3.5)"
            },
            {
                "Sección": "III. Información Organizacional",
                "Pregunta / Campo": "Tiempo de implementación (meses)",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Escriba el tiempo de funcionamiento de la activity en meses (ej. 48)"
            },
            {
                "Sección": "III. Información Organizacional",
                "Pregunta / Campo": "Tipo de tenencia del predio",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Escriba una de las siguientes opciones:\n- Arrendamiento\n- Posesión\n- Propio"
            },
            {
                "Sección": "III. Información Organizacional",
                "Pregunta / Campo": "¿Cuántas personas están vinculadas a su organización?",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Escriba el número entero de personas vinculadas (ej. 4)"
            },
            // Section IV
            {
                "Sección": "IV. Categorías Productivas",
                "Pregunta / Campo": "Subcategorías productivas en las que participa",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Escriba los números de las subcategorías correspondientes separados por comas (ej. 1, 8, 11):\n\nPECUARIA/LÁCTEOS:\n1: Leche fresca\n2: Quesos/yogurt/arequipe\n3: Carne de res\n4: Carne de cerdo\n5: Carne de pollo\n6: Trucha\n7: Otras especies (cuy, conejo)\n8: Huevos\n9: Chorizos/embutidos\n10: Apicultura (miel, polen)\n\nAGRÍCOLA:\n11: Hortalizas (zanahoria, lechuga, cebolla, ajo, etc.)\n12: Tubérculos/raíces (papa, arracacha, yuca, cubios)\n13: Leguminosas/cereales (arveja, frijol, maíz, trigo, etc.)\n\nFRUTÍCOLA:\n14: Frutas (mora, fresa, durazno, tomate de árbol, etc.)\n15: Subproductos (mermeladas, pulpas, deshidratados)\n\nAROMÁTICAS/NATURALES:\n16: Plantas (manzanilla, menta, caléndula, etc.)\n17: Productos (infusiones, aceites, jabones, cremas)\n\nTRANSFORMACIÓN ARTESANAL:\n18: Panadería/repostería (panes, bizcochos, galletas)\n19: Harinas/derivados (arepas, avena, mezclas)\n20: Conservas/salsas/encurtidos\n\nARTESANÍAS/MANUFACTURAS:\n21: Tejidos, alpargatas, cestería, carpintería\n22: Artículos utilitarios o decorativos\n23: Forja y ornamentación metálica\n\nARTÍSTICO/CULTURAL:\n24: Actividades culturales\n25: Actividades deportivas\n\nSERVICIOS RURALES/GASTRONOMÍA:\n26: Restaurantes, panaderías y tiendas veredales\n27: Preparación de alimentos/catering\n28: Turismo rural, paseos, hospedaje\n29: Alquiler de maquinaria, transporte rural"
            },
            // Section V - Canales
            {
                "Sección": "V. Información de Producción - Canales de Venta / Entrega",
                "Pregunta / Campo": "¿Cómo vende o entrega sus productos/servicios?",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Escriba los números correspondientes separados por comas (ej. 2, 4):\n1: Los vendo en mi vereda o zona rural\n2: Lo recogen directamente en la finca\n3: Los llevo a mercados o ferias campesinas\n4: Por pedido directo o conocidos o clientes\n5: A través de intermediarios o revendedores\n6: Participación en mesas del sector\n7: A entidades públicas (PAE, escuelas, hospitales)\n8: Difusión por redes sociales"
            },
            // Section VI
            {
                "Sección": "VI. Caracterización del Sistema Productivo",
                "Pregunta / Campo": "Tipo de mano de obra",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Escriba una de las siguientes opciones:\n- FAMILIAR\n- EXTERNA\n- MIXTA (FAMILIA Y APOYO EXTERNO)"
            },
            {
                "Sección": "VI. Caracterización del Sistema Productivo",
                "Pregunta / Campo": "Proceso productivo mayoritario",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Escriba una de las siguientes opciones:\n- MANUAL (HERRAMIENTAS BÁSICAS O MANUAL)\n- CON MAQUINARIA O EQUIPOS ESPECIALIZADOS\n- NO APLICA"
            },
            {
                "Sección": "VI. Caracterización del Sistema Productivo",
                "Pregunta / Campo": "¿Utiliza abonos orgánicos o prácticas agroecológicas?",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Escriba una de las siguientes opciones:\n- SÍ\n- NO"
            },
            {
                "Sección": "VI. Caracterización del Sistema Productivo",
                "Pregunta / Campo": "¿Tiene sistemas productivos asociados o complementarios?",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Escriba una de las siguientes opciones:\n- SÍ\n- NO"
            },
            {
                "Sección": "VI. Caracterización del Sistema Productivo",
                "Pregunta / Campo": "¿Maneja algún tipo de sistema de producción diferenciado?",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Escriba una de las siguientes opciones:\n- SÍ\n- NO"
            },
            {
                "Sección": "VI. Caracterización del Sistema Productivo",
                "Pregunta / Campo": "Si respondió sí, ¿cuál?",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Especifique el sistema de producción diferenciado si la respuesta anterior fue 'SÍ'"
            },
            {
                "Sección": "VI. Caracterización del Sistema Productivo",
                "Pregunta / Campo": "Si su actividad es cultural/deportiva ¿cuál es su valor agregado?",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Escriba una breve descripción del valor agregado de su actividad"
            },
            // Section VII
            {
                "Sección": "VII. Comercialización y Dificultades",
                "Pregunta / Campo": "Destino principal de la producción",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Escriba una de las siguientes opciones:\n- Para el autoconsumo\n- Para autoconsumo y venta de excedentes\n- Para venta total o comercialización completa\n- Otro"
            },
            {
                "Sección": "VII. Comercialización y Dificultades",
                "Pregunta / Campo": "Transporte utilizado",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Escriba una de las siguientes opciones:\n- PROPIO\n- ALQUILADO\n- DEPENDO DE TRANSPORTE COMUNAL, FAMILIAR O DE AMIGOS (SIN PAGO)\n- NO TENGO TRANSPORTE"
            },
            {
                "Sección": "VII. Comercialización y Dificultades",
                "Pregunta / Campo": "Forma de pago",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Escriba una de las siguientes opciones:\n- ANTES DE ENTREGARLOS / ANTES DE PRESENTARSE\n- EN EL MOMENTO DE LA ENTREGA / PRESENTACIÓN\n- DESPUÉS DE LA ENTREGA / PRESENTACIÓN (A CRÉDITO O PLAZO)"
            },
            {
                "Sección": "VII. Comercialización y Dificultades",
                "Pregunta / Campo": "Fijación del precio",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Escriba una de las siguientes opciones:\n- EL COMPRADOR\n- YO LO ESTABLEZCO DIRECTAMENTE\n- SE ACUERDA MEDIANTE NEGOCIACIÓN"
            },
            {
                "Sección": "VII. Comercialización y Dificultades",
                "Pregunta / Campo": "¿Cuáles son las principales dificultades o limitaciones? (Marque todas las que apliquen)",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Escriba los números correspondientes separados por comas (ej. 1, 3, 6):\n1: ALTOS COSTOS DE MATERIAS PRIMAS O INSUMOS\n2: DESCONOCIMIENTO O LIMITACIONES EN LOS PROCESOS DE PRODUCCIÓN\n3: FALTA DE FINANCIACIÓN O ACCESO AL CRÉDITO\n4: ESCASO APOYO INSTITUCIONAL O ASISTENCIA TÉCNICA\n5: INFRAESTRUCTURA INADECUADA\n6: PROBLEMAS CLIMÁTICOS O DE PLAGAS\n7: DIFICULTADES DE TRANSPORTE O ACCESO AL PREDIO / LUGARES DE FORMACIÓN/PRESENTACIÓN\n8: SOLICITUD DE PERMISOS PARA ESPACIOS PÚBLICOS (SUGA)"
            },
            {
                "Sección": "VII. Comercialización y Dificultades",
                "Pregunta / Campo": "¿Cuenta con alguno de los siguientes registros, permisos o certificaciones? (Marque todas las que apliquen)",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Escriba los números correspondientes separados por comas (ej. 6, 9):\n1: REGISTRO INVIMA\n2: REGISTRO ICA - PREDIO AGRÍCOLA\n3: REGISTRO ICA - PREDIO PECUARIO\n4: BUENAS PRÁCTICAS AGRÍCOLAS (BPA)\n5: BUENAS PRÁCTICAS BPP O BPM\n6: CERTIFICADO EN MANIPULACIÓN DE ALIMENTOS\n7: RECONOCIMIENTO O CERTIFICADO IDRD\n8: REGISTRO MIN CULTURA\n9: NINGUNO"
            },
            {
                "Sección": "VII. Comercialización y Dificultades",
                "Pregunta / Campo": "¿Se encuentra en proceso de trámite o gestión de alguno de los anteriores?",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Escriba una de las siguientes opciones:\n- No\n- Sí"
            },
            {
                "Sección": "VII. Comercialización y Dificultades",
                "Pregunta / Campo": "Si respondió sí, ¿cuál o cuáles?",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Especifique cuál o cuáles permisos se encuentran en trámite si la respuesta anterior fue 'Sí'"
            },
            // Section VIII
            {
                "Sección": "VIII. Financiamiento",
                "Pregunta / Campo": "¿Ha tenido acceso a procesos de financiamiento?",
                "Respuesta (Diligenciar aquí)": "",
                "Instrucciones / Opciones de Respuesta": "Escriba el número de la opción si aplica (ej. 1):\n1: CRÉDITOS AGROPECUARIOS\n2: COOPERATIVAS\n3: LÍNEAS ESPECIALES DE CRÉDITO\n4: PROGRAMAS DE APOYO CAMPESINO\n5: CRÉDITOS BANCARIOS"
            }
        ];

        // Sheet 2: Productos Ofertados
        const productsTemplate = [
            {
                "Nombre del Producto": "Ej. Papa Pastusa",
                "Volumen Producido (Número)": 150,
                "Unidad de Volumen": "Bultos",
                "Frecuencia de Producción": "Mensual",
                "Presentación": "Fresco / Natural",
                "Calidad": "Primera / Excelente",
                "Precio Unitario ($)": 80000,
                "Unidad de Medida del Precio": "Por Bulto"
            },
            {
                "Nombre del Producto": "",
                "Volumen Producido (Número)": "",
                "Unidad de Volumen": "",
                "Frecuencia de Producción": "",
                "Presentación": "",
                "Calidad": "",
                "Precio Unitario ($)": "",
                "Unidad de Medida del Precio": ""
            },
            {
                "Nombre del Producto": "",
                "Volumen Producido (Número)": "",
                "Unidad de Volumen": "",
                "Frecuencia de Producción": "",
                "Presentación": "",
                "Calidad": "",
                "Precio Unitario ($)": "",
                "Unidad de Medida del Precio": ""
            }
        ];

        const productsInstructions = [
            "INSTRUCCIONES:",
            "- Diligencie cada fila con un producto ofertado.",
            "- Unidad de Volumen válidas: Kilos, Litros, Libras, Toneladas, Unidades, Docenas, Atados, Canastillas, Bultos, Cajas, Pacas",
            "- Frecuencia válidas: Diarios, Semanal, Quincenal, Mensual, Bimestral, Trimestral, Semestral, Anual, Cosecha principal, Cosecha mitaca",
            "- Presentación válidas: Fresco / Natural, Procesado / Empacado, Envasado, Seco / Deshidratado, A granel, En bandeja, Bolsa plástica, Frasco de vidrio, Caja de cartón",
            "- Calidad válidas: Primera / Excelente, Segunda / Estándar, Tercera / Económica",
            "- Unidad de Medida del Precio válidas: Por Kilo, Por Litro, Por Libra, Por Tonelada, Por Unidad, Por Docena, Por Atado, Por Canastilla, Por Bulto, Por Caja, Por Paca"
        ];

        // Sheet 3: Servicios
        const servicesTemplate = [
            {
                "Nombre de la Actividad / Servicio": "Ej. Paseo Ecológico Guiado",
                "Frecuencia de la Actividad": "Fin de semana",
                "Población Objetivo (Texto)": "Turistas y familias",
                "Tipo de Contrato / Venta (Texto)": "Venta directa de boleto",
                "Lugar de Prestación (Texto)": "Vereda Las Sopas, Finca El Mirador",
                "Recursos / Equipamiento Necesario": "Bastones de caminata, equipo de primeros auxilios"
            },
            {
                "Nombre de la Actividad / Servicio": "",
                "Frecuencia de la Actividad": "",
                "Población Objetivo (Texto)": "",
                "Tipo de Contrato / Venta (Texto)": "",
                "Lugar de Prestación (Texto)": "",
                "Recursos / Equipamiento Necesario": ""
            },
            {
                "Nombre de la Actividad / Servicio": "",
                "Frecuencia de la Actividad": "",
                "Población Objetivo (Texto)": "",
                "Tipo de Contrato / Venta (Texto)": "",
                "Lugar de Prestación (Texto)": "",
                "Recursos / Equipamiento Necesario": ""
            }
        ];

        const servicesInstructions = [
            "INSTRUCCIONES:",
            "- Diligencie cada fila con un servicio o actividad ofertada.",
            "- Frecuencia de la Actividad válidas: Diaria, Semanal, Mensual, Eventos esporádicos, Por temporada, Bajo demanda, Fin de semana, Feriados"
        ];

        // Create Sheets
        const wsBase = XLSX.utils.json_to_sheet(baseForm);
        const wsProducts = XLSX.utils.json_to_sheet(productsTemplate);
        const wsServices = XLSX.utils.json_to_sheet(servicesTemplate);

        // Add instructions to sheets using explicit origins
        const productsRange = XLSX.utils.decode_range(wsProducts['!ref']);
        const nextRowP = productsRange.e.r + 1;
        XLSX.utils.sheet_add_aoa(wsProducts, [[""]], { origin: "A" + (nextRowP + 1) });
        XLSX.utils.sheet_add_aoa(wsProducts, productsInstructions.map(i => [i]), { origin: "A" + (nextRowP + 2) });
        
        const servicesRange = XLSX.utils.decode_range(wsServices['!ref']);
        const nextRowS = servicesRange.e.r + 1;
        XLSX.utils.sheet_add_aoa(wsServices, [[""]], { origin: "A" + (nextRowS + 1) });
        XLSX.utils.sheet_add_aoa(wsServices, servicesInstructions.map(i => [i]), { origin: "A" + (nextRowS + 2) });

        // Auto-adjust column widths for base form
        wsBase['!cols'] = [
            { wch: 35 }, // Sección
            { wch: 45 }, // Pregunta / Campo
            { wch: 30 }, // Respuesta
            { wch: 65 }  // Instrucciones / Opciones
        ];

        // Enable wrapping for base form multi-line text
        const baseRange = XLSX.utils.decode_range(wsBase['!ref']);
        for (let R = baseRange.s.r; R <= baseRange.e.r; ++R) {
            const cellRefInst = XLSX.utils.encode_cell({ r: R, c: 3 }); // Column 3: Instrucciones
            if (wsBase[cellRefInst] && wsBase[cellRefInst].v && typeof wsBase[cellRefInst].v === 'string' && wsBase[cellRefInst].v.includes('\n')) {
                if (!wsBase[cellRefInst].s) wsBase[cellRefInst].s = {};
                wsBase[cellRefInst].s.alignment = { wrapText: true, vertical: 'top' };
            }
        }

        // Columns widths for Products and Services
        wsProducts['!cols'] = [
            { wch: 25 }, // Nombre
            { wch: 28 }, // Volumen
            { wch: 20 }, // Unidad
            { wch: 22 }, // Frecuencia
            { wch: 22 }, // Presentación
            { wch: 20 }, // Calidad
            { wch: 20 }, // Precio
            { wch: 28 }  // Unidad Precio
        ];

        wsServices['!cols'] = [
            { wch: 30 }, // Nombre
            { wch: 25 }, // Frecuencia
            { wch: 30 }, // Población
            { wch: 30 }, // Tipo Contrato
            { wch: 35 }, // Lugar
            { wch: 45 }  // Recursos
        ];

        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, wsBase, "Ficha Base (A Llenar)");
        XLSX.utils.book_append_sheet(wb, wsProducts, "Productos Ofertados (A Llenar)");
        XLSX.utils.book_append_sheet(wb, wsServices, "Servicios y Actividades (A Llenar)");
        
        console.log("Workbook created successfully. Writing to buffer...");
        const buf = XLSX.write(wb, { type: 'buffer', bookType: 'xlsx' });
        fs.writeFileSync('c:/Users/sotoc/OneDrive/somos_sumapaz/memoria_sumapaz/scratch/test_out.xlsx', buf);
        console.log("Success! File written to scratch/test_out.xlsx");
    } catch (error) {
        console.error("Error al descargar la plantilla:", error);
    }
}

downloadTemplateWithAnswers();
