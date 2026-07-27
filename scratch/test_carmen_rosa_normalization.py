import json

path = r'C:\Users\sotoc\Downloads\extracted_data.json'
with open(path, 'r', encoding='utf-8') as f:
    raw = json.load(f)

# Python version of normalizePmapcJson
def get_block(data, code):
    code_upper = f"PMAPC_{code.upper()}"
    code_lower = f"pmapc_{code.lower()}"
    return data.get(code_upper) or data.get(code_lower) or data.get(code.lower()) or data.get(code.upper())

b01 = get_block(raw, 'F01')
b03 = get_block(raw, 'F03')
b05 = get_block(raw, 'F05')
b07 = get_block(raw, 'F07')
b09 = get_block(raw, 'F09')
b10 = get_block(raw, 'F10')

print("=== NORMALIZATION TEST FOR CARMEN ROSA ===")
print("F01 Nombre:", b01.get('nombre_unidad_productiva') if b01 else None)
print("F01 Persona:", b01.get('persona_entrevistada') if b01 else None)
print("F03 Problema:", b03.get('por_que_adquieren_el_servicio') if b03 else None)
print("F05 Filas:", len(b05.get('perfiles_cliente_multiples_filas', [])) if b05 else 0)
print("F07 Filas:", len(b07.get('alianzas_cooperacion_multiples_filas', [])) if b07 else 0)
print("F09 Filas:", len(b09.get('fichas_tecnicas_multiples_filas', [])) if b09 else 0)
print("F10 Filas:", len(b10.get('proceso_prestacion_servicio_multiples_filas', [])) if b10 else 0)
