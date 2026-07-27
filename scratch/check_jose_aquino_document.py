import json

path = r'C:\Users\sotoc\Downloads\gemini-code-1785185540986.json'
with open(path, 'r', encoding='utf-8') as f:
    data = json.load(f)

print("=== CHECKING JOSE AQUINO JSON DETAILS ===")
f01 = data.get('PMAPC_F01', {})
f26 = data.get('PMAPC_F26', {})

print("F01 persona_entrevistada:", f01.get('persona_entrevistada'))
print("F01 nombre_unidad_productiva:", f01.get('nombre_unidad_productiva'))
print("F01 ubicacion_especifica:", f01.get('ubicacion_especifica'))
print("F26 observaciones_o_comentarios:", f26.get('observaciones_o_comentarios'))
