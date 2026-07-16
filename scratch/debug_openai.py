import urllib.request
import json
import sys

# Load API Key from .env
api_key = None
try:
    with open(".env", "r") as f:
        for line in f:
            if line.startswith("OPENAI_API_KEY="):
                api_key = line.split("=", 1)[1].strip()
except Exception as e:
    print(f"Error reading .env: {e}")
    sys.exit(1)

if not api_key:
    print("API Key not found in .env")
    sys.exit(1)

file_path = "C:/Users/sotoc/Downloads/0715 (2).txt"
try:
    with open(file_path, "r", encoding="utf-8") as f:
        transcript = f.read()
except Exception as e:
    print(f"Error reading file: {e}")
    sys.exit(1)

# Merged prompt
system_prompt = """Actúas como un profesional experto en desarrollo rural, formulación de proyectos productivos, agronegocios, gestión ambiental, economía circular y fortalecimiento de unidades productivas campesinas, con amplia experiencia en el diligenciamiento del Plan de Manejo Ambiental, Productivo y Comercial (PMAPC) para la Localidad de Sumapaz.

Tu función es analizar la transcripción de una entrevista con un productor rural y extraer la información para completar el formato JSON del PMAPC, utilizando una redacción técnica, clara, coherente y profesional.

Debes retornar EXCLUSIVAMENTE un objeto JSON válido, sin bloques de código markdown. Para evitar respuestas truncadas y optimizar el tiempo, sigue estas reglas estrictas:
1. OMITIR por completo cualquier formato/módulo (f01, f02, f03, etc.) si no hay ninguna información REAL sobre él en la entrevista. Si todos los campos de un formato entero serían rellenados con respuestas de reserva (como 'No informado durante la entrevista.'), debes OMITIR por completo ese formato/módulo del JSON.
2. Dentro de los formatos que sí incluyas (porque tienen al menos un dato real), si no hay información en la entrevista para un campo de texto específico, utiliza expresiones técnicas de reserva en lugar de inventar datos:
   - 'No informado durante la entrevista.'
   - 'Pendiente de verificar en visita técnica.'
   - 'Requiere validación en campo.'
   - 'No fue posible determinar con la información disponible.'
3. No debes copiar literalmente lo que dice el productor. Debes interpretar la información, organizarla y convertirla en respuestas técnicas profesionales.
   - Ejemplo (Venta/Producto): Si el productor dice 'Yo vendo pollos porque la gente dice que saben ricos', debes redactar: 'La unidad productiva comercializa pollos de engorde alimentados mediante un sistema complementario con concentrado, maíz y pasto, lo que genera un producto con características diferenciadas y una alta aceptación entre los consumidores locales debido a su sabor y calidad.'
   - Ejemplo (Residuos/Economía Circular): Si el productor dice 'La gallinaza la echo a la huerta', debes redactar: 'La gallinaza generada durante el proceso productivo es aprovechada como abono orgánico en la huerta familiar, favoreciendo el reciclaje de nutrientes, la reducción de residuos pecuarios y el fortalecimiento de prácticas de economía circular.'
   - Ejemplo (Inversiones): Si el productor dice 'Comprar un congelador', debes redactar: 'Adquisición de un equipo de refrigeración que garantine la conservación del producto bajo condiciones adecuadas de inocuidad, fortaleciendo la cadena de frío, disminuyendo pérdidas poscosecha y mejorando la capacidad de comercialización de la unidad productiva.'
4. No uses respuestas de una sola palabra. Cada casilla de texto debe quedar suficientemente desarrollada de forma técnica.
5. Los campos de Valores (f02) y FODA (f04) deben ser cadenas de texto descriptivas simples (valores separados por coma en f02), nunca arreglos.

Esquema de claves posibles:
- f01: nombre_organizacion, tipo_actividad ('agricola'/'pecuaria'/'artesanal'/'agroindustrial'/'servicios'/'otra'), ubicacion, coordenadas, producto_principal, estado_actual ('idea'/'produccion_inicial'/'negocio_marcha'/'asociacion'/'otro'), descripcion_general.
- f02: mision, vision, valores (lista de valores separados por coma, ej. 'Respeto, Honestidad').
- f03: problema, solucion, diferencial, valor_ambiental, valor_social, demostracion.
- f04: fortalezas, oportunidades, debilidades, amenazas (textos explicados técnicamente, no arreglos).
- f05: array de clientes: [ { actor, perfil, ubicacion, necesidad, frecuencia, criterio, canal } ]
- f06: necesidad, como_sabe, a_quien_afecta, evidencia, oportunidad_organicos, cambio, dificultad.
- f07: array de aliados: [ { actor, aporta, recibe, trabajo, ambiental, accion } ]
- f08: validaciones de mercado: quien_degus, resultado_degus, motivacion_degus, evidencia_degus, quien_ventas, resultado_ventas... (sufijos _degus, _ventas, _cartas, _encuesta, _entrevista, _feria, _otro).
- f09: array de productos: [ { producto, descripcion, unidad, insumos, almacenamiento, presentacion, diferencial } ]
- f10: array de bienes: [ { bien, unidades, actividad, tiempo } ]
- f11: array de insumos: [ { insumo, cantidad, frecuencia, proveedor, toxicidad ('N/A'/'Baja'/'Media'/'Alta'), impacto, manejo } ]
- f12: produccion_estimada, produccion_maxima, area, limitantes_prod, limitantes_amb, capacidad_instalada, capacidad_utilizada, misma_cantidad, alcanza_demanda, necesidad_sostenible.
- f12a: recursos (estado_agua, limite_agua, efecto_agua, accion_agua, estado_fuentes, limite_fuentes, efecto_fuentes, accion_fuentes, estado_suelo, limite_suelo, efecto_suelo, accion_suelo, estado_pendiente, limite_pendiente, efecto_pendiente, accion_pendiente, estado_clima, limite_clima, efecto_clima, accion_clima, estado_bio, limite_bio, efecto_bio, accion_bio, estado_insumos, limite_insumos, efecto_insumos, accion_insumos, estado_residuos, limite_residuos, efecto_residuos, accion_residuos).
- f12b: peligros (virus, bacterias, picaduras, mordeduras, temperatura, radiacion, ruido, polvos, gases, particulado, posturas, movimientos, cargas, mecanico, locativo, electrico, transito). Cada uno con: si, no, f_alta, f_media, f_baja, controles, mejora.
- f12c: controles. Objeto con claves '1' a '7' con resp, frec, evidencia.
- f13: array de 8 objetos con aplica (Si/No), detalles, frec, resp.
- f14: costos/precios: [ { producto, costo, margen, pmin, pmercado, logistica, precio, justificacion } ]
- f15: ventas: [ { producto, cantidad, precio, ingresos, pago, cliente } ]
- f15a: fidelización: [ { est, med, frec, resp } ]
- f15b: logística: [ { prod, canal, tiempo, transporte, condicion, capacidad, costo, resp } ]
- f15c: trazabilidad: [ { resp, med, frec, evi } ]
- f16: inversiones: [ { desc, valunit, cant, total, req (Si/No), fuente } ]
- f17: gastos fijos: [ { desc, val, obs } ]
- f18: flujo de caja (ingreso_m1 a ingreso_m6, gprod_m1 a gprod_m6, gamb_m1 a gamb_m6, glog_m1 a glog_m6, obs_m1 a obs_m6).
- f19_conclusion (string), f19: array de [ { ini, meta, frec, resp, evi } ]
- f19a_conclusion (string), f19a: array de [ { desc, cant, impacto, mejora } ]
- f20_conclusion (string), f20: array de [ { cant, manejo, destino, resp } ]
- f21_conclusion (string), f21: array de [ { estado, cal, mejora, evi } ]
- f22: array de [ { accion, plazo, resp, rec, ind, evi } ]
- f22a_conclusion (string), f22a: array de [ { resp, riesgo, mejora } ]
- f23: causa_clima, cons_clima, nivel_clima, prev_clima, causa_costos, cons_costos, nivel_costos, prev_costos.
- f24: array de [ { actividad, componente ('Digital'/'Productivo'/'Organizacional'/'Comercial'/'Ambiental'), responsable, tiempo, resultado } ]
- f25: array de [ { ind, meta, frec, resp, evi } ]
- f26: array de [ { prod, com, fin, amb, aju } ]
- f26_coherencia: string.
"""

url = "https://api.openai.com/v1/chat/completions"
post_data = {
    "model": "gpt-4o-mini",
    "messages": [
        {"role": "system", "content": system_prompt},
        {"role": "user", "content": f"Aquí está la transcripción de la entrevista:\n\n{transcript}"}
    ],
    "temperature": 0.1,
    "max_tokens": 8000,
    "response_format": {"type": "json_object"}
}

req = urllib.request.Request(
    url,
    data=json.dumps(post_data).encode("utf-8"),
    headers={
        "Content-Type": "application/json",
        "Authorization": f"Bearer {api_key}"
    },
    method="POST"
)

try:
    with urllib.request.urlopen(req) as response:
        body = response.read().decode("utf-8")
        resp_data = json.loads(body)
        ai_content = resp_data["choices"][0]["message"]["content"]
        
        print("Raw AI Content Length:", len(ai_content))
        
        # Test python json decode
        parsed = json.loads(ai_content)
        print("Successfully parsed in Python!")
        
        # Check if there are any unescaped control characters in ai_content
        for idx, char in enumerate(ai_content):
            code = ord(char)
            if code < 32 and char not in ('\n', '\r', '\t'):
                print(f"Control character found at index {idx}: ASCII {code}")
                
except Exception as e:
    print(f"Error during Direct OpenAI Call: {e}")
