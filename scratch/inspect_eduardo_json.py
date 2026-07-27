import json

path = r'C:\Users\sotoc\Downloads\JSON Eduin Eduardo Parada.json'
with open(path, 'r', encoding='utf-8') as f:
    data = json.load(f)

print("=== INSPECTING JSON Eduin Eduardo Parada.json ===")
print("Top keys:", list(data.keys()))
print("Persona entrevistada:", data.get('PMAPC_F01', {}).get('persona_entrevistada'))
print("Unidad productiva:", data.get('PMAPC_F01', {}).get('nombre_unidad_productiva'))
