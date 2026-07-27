import json

path = r'C:\Users\sotoc\Downloads\extracted_data.json'
with open(path, 'r', encoding='utf-8') as f:
    data = json.load(f)

print("=== CARMEN ROSA MORENO MORENO DATA IN EXTRACTED_DATA.JSON ===")
for k, v in data.items():
    print(f"\n{k}:")
    if isinstance(v, dict):
        for subk, subv in v.items():
            if isinstance(subv, list):
                print(f"  {subk} ({len(subv)} items):")
                for row in subv[:3]:
                    print(f"    {row}")
            else:
                print(f"  {subk}: {str(subv)[:80]}")
