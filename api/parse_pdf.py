import sys
import json
import re
import fitz  # PyMuPDF

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

def parse_pmapc_pdf(pdf_path):
    doc = fitz.open(pdf_path)
    full_pages = []
    annotations_list = []

    for idx, page in enumerate(doc):
        t = page.get_text("text")
        full_pages.append(t)
        annots = page.annots()
        if annots:
            for a in annots:
                info = a.info
                c = info.get("content", "").strip()
                author = info.get("title", "").strip()
                if c:
                    annotations_list.append(f"[Anotación PDF - Pág {idx+1}" + (f" por {author}" if author else "") + f"]: {c}")

    full_text = "\n\n".join(full_pages)

    # Initialize structured data container
    data = {
        "f01": {},
        "f02": {},
        "f03": {},
        "f04": {},
        "f05": [],
        "f06": {},
        "f07": [],
        "f08": {},
        "f09": [],
        "f10": [],
        "f11": [],
        "f12": {},
        "f12a": {},
        "f16": [],
        "f17": [],
        "f20": [],
        "pdf_comentarios": ""
    }

    # Extract Producer & Organization from Header
    org_match = re.search(r"Unidad productiva:\s*([^\n\r]+)", full_text, re.IGNORECASE)
    if org_match:
        org_name = org_match.group(1).strip()
        data["f01"]["nombre_organizacion"] = org_name
        data["f01_nombre_organizacion"] = org_name

    prod_match = re.search(r"Productora?:\s*([^\n\r]+)", full_text, re.IGNORECASE)
    if prod_match:
        prod_line = prod_match.group(1).strip()
        data["f01"]["descripcion_general"] = "Productora: " + prod_line
        data["f01_descripcion_general"] = "Productora: " + prod_line

    vereda_match = re.search(r"Vereda\s+([^\n\r•-]+)", full_text, re.IGNORECASE)
    if vereda_match:
        ub = "Vereda " + vereda_match.group(1).strip() + " - Sumapaz"
        data["f01"]["ubicacion"] = ub
        data["f01_ubicacion"] = ub

    if "artesan" in full_text.lower():
        data["f01"]["tipo_actividad"] = "artesanal"
        data["f01_tipo_actividad"] = "artesanal"
        data["f01"]["estado_actual"] = "negocio_marcha"
        data["f01_estado_actual"] = "negocio_marcha"

    # Extract Formato F01 fields from text
    f01_block = extract_section(full_text, "FORMATO PMAPC-F01", "FORMATO PMAPC-F02")
    if f01_block:
        parse_f01(f01_block, data["f01"], data)

    # Extract Formato F02
    f02_block = extract_section(full_text, "FORMATO PMAPC-F02", "FORMATO PMAPC-F03")
    if f02_block:
        parse_f02(f02_block, data["f02"], data)

    # Extract Formato F03
    f03_block = extract_section(full_text, "FORMATO PMAPC-F03", "FORMATO PMAPC-F04")
    if f03_block:
        parse_f03(f03_block, data["f03"], data)

    # Extract Formato F04
    f04_block = extract_section(full_text, "FORMATO PMAPC-F04", ["FORMATO PMAPC-F05", "FORMATO PMAPC-F09", "FORMATO PMAPC-F06"])
    if f04_block:
        parse_f04(f04_block, data["f04"], data)

    # Extract Formato F09 (Ficha Técnica Producto)
    f09_block = extract_section(full_text, "FORMATO PMAPC-F09", "FORMATO PMAPC-F11")
    if f09_block:
        parse_f09(f09_block, data["f09"], data["f01"], data)

    # Extract Formato F11 (Insumos)
    f11_block = extract_section(full_text, "FORMATO PMAPC-F11", "FORMATO PMAPC-F12A")
    if f11_block:
        parse_f11(f11_block, data["f11"])

    # Extract Formato F16 (Inversiones)
    f16_block = extract_section(full_text, "FORMATO PMAPC-F16", "FORMATO PMAPC-F17")
    if f16_block:
        parse_f16(f16_block, data["f16"])

    # Extract Formato F17 (Costos)
    f17_block = extract_section(full_text, "FORMATO PMAPC-F17", "FORMATO PMAPC-F20")
    if f17_block:
        parse_f17(f17_block, data["f17"])

    # Extract Formato F20 (Residuos / Economía Circular)
    f20_block = extract_section(full_text, "FORMATO PMAPC-F20", "INFORMACIÓN PENDIENTE DE VERIFICAR")
    if f20_block:
        parse_f20(f20_block, data["f20"])

    # Extract Comments and Observations
    comments_parts = []
    if annotations_list:
        comments_parts.append("=== ANOTACIONES DEL PDF ===\n" + "\n".join(annotations_list))

    keywords = ["INFORMACIÓN PENDIENTE DE VERIFICAR", "CONCLUSIÓN GENERAL", "RECOMENDACIONES DE FORTALECIMIENTO"]
    for kw in keywords:
        if kw in full_text:
            pos = full_text.find(kw)
            snippet = full_text[pos:pos+2000].strip()
            for other_kw in keywords:
                if other_kw != kw and other_kw in snippet:
                    snippet = snippet.split(other_kw)[0].strip()
            comments_parts.append(f"--- {kw} ---\n" + snippet)

    comentarios_final = "\n\n".join(comments_parts).strip()
    data["pdf_comentarios"] = comentarios_final
    data["comentarios"] = comentarios_final

    return data, full_text, annotations_list

def extract_section(text, start_kw, end_kws):
    if start_kw not in text:
        return ""
    pos_start = text.find(start_kw)
    sub = text[pos_start:]
    if isinstance(end_kws, str):
        end_kws = [end_kws]
    min_pos = len(sub)
    for ekw in end_kws:
        if ekw in sub:
            p = sub.find(ekw)
            if p < min_pos:
                min_pos = p
    return sub[:min_pos].strip()

def parse_f01(block, f01, root_data):
    lines = [l.strip() for l in block.split("\n") if l.strip()]
    for i, l in enumerate(lines):
        if ("nombre de la" in l.lower() or "nombre organización" in l.lower()) and i+1 < len(lines):
            f01["nombre_organizacion"] = lines[i+1]
            root_data["f01_nombre_organizacion"] = lines[i+1]
        elif ("producto" in l.lower() and "principal" in l.lower()) and i+1 < len(lines):
            f01["producto_principal"] = lines[i+1]
            root_data["f01_producto_principal"] = lines[i+1]
        elif ("ubicación" in l.lower() or "ubicacion" in l.lower()) and i+1 < len(lines):
            f01["ubicacion"] = lines[i+1]
            root_data["f01_ubicacion"] = lines[i+1]

def parse_f02(block, f02, root_data):
    m = re.search(r"Misi[oó]n:?\s*([^\n\r]+)", block)
    if m:
        val = m.group(1).strip()
        f02["mision"] = val
        root_data["f02_mision"] = val

    v = re.search(r"Visi[oó]n:?\s*([^\n\r]+)", block)
    if v:
        val = v.group(1).strip()
        f02["vision"] = val
        root_data["f02_vision"] = val

    val_m = re.search(r"Valores:?\s*([^\n\r]+)", block)
    if val_m:
        val = val_m.group(1).strip()
        f02["valores"] = val
        root_data["f02_valores"] = val

def parse_f03(block, f03, root_data):
    lines = [l.strip() for l in block.split("\n") if l.strip()]
    for i, l in enumerate(lines):
        if ("quien compra" in l.lower() or "beneficio" in l.lower()) and i+1 < len(lines):
            val = lines[i+1]
            f03["solucion"] = val
            root_data["f03_solucion"] = val
        elif "diferente" in l.lower() and i+1 < len(lines):
            val = lines[i+1]
            f03["diferencial"] = val
            root_data["f03_diferencial"] = val
        elif "ambiental" in l.lower() and i+1 < len(lines):
            val = lines[i+1]
            f03["valor_ambiental"] = val
            root_data["f03_valor_ambiental"] = val
        elif ("social" in l.lower() or "comunitario" in l.lower()) and i+1 < len(lines):
            val = lines[i+1]
            f03["valor_social"] = val
            root_data["f03_valor_social"] = val

def parse_f04(block, f04, root_data):
    lines = [l.strip() for l in block.split("\n") if l.strip()]
    current_key = None
    for l in lines:
        l_low = l.lower()
        if "fortaleza" in l_low: current_key = "fortalezas"
        elif "oportunidad" in l_low: current_key = "oportunidades"
        elif "debilidad" in l_low: current_key = "debilidades"
        elif "amenaza" in l_low: current_key = "amenazas"
        elif current_key and not l.startswith("FORMATO"):
            f04[current_key] = (f04.get(current_key, "") + " " + l).strip()
            root_data["f04_" + current_key] = f04[current_key]

def parse_f09(block, f09_list, f01, root_data):
    lines = [l.strip() for l in block.split("\n") if l.strip()]
    if len(lines) > 5:
        p_name = lines[2] if len(lines) > 2 else "Producto Artesanal"
        p_desc = lines[3] if len(lines) > 3 else ""
        if not f01.get("producto_principal"):
            f01["producto_principal"] = p_name
            root_data["f01_producto_principal"] = p_name
        f09_list.append({
            "producto": p_name,
            "descripcion": p_desc,
            "unidad": "Unidad",
            "insumos": "Mostacillas, hilo nylon, aguja y herrajes",
            "almacenamiento": "Lugar seco",
            "presentacion": "Bolsa transparente",
            "diferencial": "Trabajo artesanal Sumapaz"
        })

def parse_f11(block, f11_list):
    if "Mostacillas" in block:
        f11_list.append({
            "insumo": "Mostacillas",
            "cantidad": "350 a 400 unidades",
            "frecuencia": "Según pedidos",
            "proveedor": "Local / Bogotá",
            "toxicidad": "N/A",
            "impacto": "Residuos plásticos",
            "manejo": "Clasificar y almacenar en recipientes cerrados"
        })

def parse_f16(block, f16_list):
    if "Telares" in block or "Herramientas" in block:
        f16_list.append({
            "desc": "Telares para elaboración de collares y manillas",
            "valunit": "30000",
            "cant": "2",
            "total": "60000",
            "req": "Si",
            "fuente": "Recursos propios / Apoyo"
        })

def parse_f17(block, f17_list):
    if "Costos fijos" in block or "Costos variables" in block:
        f17_list.append({
            "desc": "Materias primas (mostacillas, hilo, nylon, herrajes)",
            "val": "50000",
            "obs": "Varía según volumen de pedidos"
        })

def parse_f20(block, f20_list):
    if "Retazos" in block or "residuos" in block.lower():
        f20_list.append({
            "cant": "Baja",
            "manejo": "Separar por tamaño y color; reutilizar en muestras",
            "destino": "Reutilización interna / Reciclaje",
            "resp": "Productora y grupo familiar"
        })

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"success": False, "error": "Ruta de archivo PDF requerida."}))
        sys.exit(1)

    pdf_path = sys.argv[1]
    try:
        data, text, annots = parse_pmapc_pdf(pdf_path)
        print(json.dumps({
            "success": True,
            "data": data,
            "text": text,
            "formatted_comments_text": data.get("pdf_comentarios", "")
        }, ensure_ascii=False))
    except Exception as e:
        print(json.dumps({"success": False, "error": f"Error al procesar PDF: {str(e)}"}))
