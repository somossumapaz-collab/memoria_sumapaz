import json

path = r'C:\Users\sotoc\Downloads\gemini-code-1785179712231.json'
with open(path, 'r', encoding='utf-8') as f:
    data = json.load(f)

print("=== INSPECTING GEMINI-CODE-1785179712231.JSON ===")
print("Top-level keys:", list(data.keys()))

for k, v in data.items():
    print(f"\nFORMAT [{k}]:")
    if isinstance(v, dict):
        for subk, subv in v.items():
            if isinstance(subv, list):
                print(f"  - Array [{subk}] ({len(subv)} items):")
                if len(subv) > 0 and isinstance(subv[0], dict):
                    print(f"    Item 0 keys: {list(subv[0].keys())}")
                    print(f"    Item 0 content: {subv[0]}")
            else:
                print(f"  - Key [{subk}]: {str(subv)[:80]}")
    elif isinstance(v, list):
        print(f"  - List of {len(v)} items")
    else:
        print(f"  - {type(v).__name__} = {str(v)[:60]}")
