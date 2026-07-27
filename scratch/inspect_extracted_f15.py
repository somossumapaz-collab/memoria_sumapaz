import json

path = r'C:\Users\sotoc\Downloads\extracted_data.json'
with open(path, 'r', encoding='utf-8') as f:
    raw = json.load(f)

print("=== RAW PMAPC_F15 IN EXTRACTED_DATA.JSON ===")
print(json.dumps(raw.get('PMAPC_F15'), indent=2, ensure_ascii=False))
