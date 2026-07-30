import json
import re
import urllib.request
import os

path = r'C:\Users\sotoc\Downloads\JSON William Palacios Rey.json'
with open(path, 'r', encoding='utf-8') as f:
    raw = f.read()

# Remove [cite: ...] or similar markdown artifacts if present
raw_clean = re.sub(r'\[cite:\s*\d+\]', '', raw)

try:
    data = json.loads(raw_clean)
    print("Cleaned JSON parsed successfully!")
except Exception as e:
    print(f"JSON Decode Error after initial clean: {e}")
    # Try regex fixing trailing commas or bad chars
    raw_clean = re.sub(r',\s*([\]}])', r'\1', raw_clean)
    data = json.loads(raw_clean)
    print("Fixed JSON with trailing comma clean successfully!")

print("=== INSPECTING WILLIAM MAURICIO PALACIOS REY JSON ===")
print("Keys count:", len(data))
print("Persona entrevistada:", data.get('PMAPC_F01', {}).get('persona_entrevistada'))
print("Unidad productiva:", data.get('PMAPC_F01', {}).get('nombre_unidad_productiva'))

# Write cleaned file back or update DB via PHP helper
fixed_json_path = r'C:\Users\sotoc\Downloads\JSON William Palacios Rey Clean.json'
with open(fixed_json_path, 'w', encoding='utf-8') as f:
    json.dump(data, f, ensure_ascii=False, indent=2)

print(f"Saved clean JSON to {fixed_json_path}")
