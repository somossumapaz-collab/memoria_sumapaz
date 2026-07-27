import json

path = r'C:\Users\sotoc\Downloads\gemini-code-1785182513911.json'
try:
    with open(path, 'r', encoding='utf-8', errors='ignore') as f:
        data = json.load(f)
    print("=== INSPECTING gemini-code-1785182513911.json ===")
    print("Keys in JSON:", list(data.keys()))
    print("Persona entrevistada:", data.get('PMAPC_F01', {}).get('persona_entrevistada'))
    print("Unidad productiva:", data.get('PMAPC_F01', {}).get('nombre_unidad_productiva'))
except Exception as e:
    print("Error parsing gemini-code-1785182513911.json:", e)
