import json

path = r'C:\Users\sotoc\Downloads\gemini-code-1785181025347.json'
with open(path, 'r', encoding='utf-8') as f:
    data = json.load(f)

print("=== INSPECTING DIYER JSON (gemini-code-1785181025347.json) ===")
print("Top keys:", list(data.keys()))

for k, v in data.items():
    print(f"\nFORMAT [{k}]:")
    if isinstance(v, dict):
        for subk, subv in v.items():
            if isinstance(subv, list):
                print(f"  - Array [{subk}] ({len(subv)} items)")
                if len(subv) > 0 and isinstance(subv[0], dict):
                    print(f"    Item 0 keys: {list(subv[0].keys())}")
                    print(f"    Item 0 sample: {subv[0]}")
            elif isinstance(subv, dict):
                print(f"  - Dict [{subk}] keys: {list(subv.keys())}")
            else:
                print(f"  - Key [{subk}]: {str(subv)[:70]}")
    elif isinstance(v, list):
        print(f"  - Direct list of {len(v)} items")
    else:
        print(f"  - {type(v).__name__} = {str(v)[:70]}")
