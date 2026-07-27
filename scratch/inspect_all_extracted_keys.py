import json

path = r'C:\Users\sotoc\Downloads\extracted_data.json'
with open(path, 'r', encoding='utf-8') as f:
    data = json.load(f)

print("=== ALL FORMAT KEYS IN EXTRACTED_DATA.JSON ===")
for fmt, content in data.items():
    print(f"\nFORMAT [{fmt}]:")
    if isinstance(content, dict):
        for k, v in content.items():
            if isinstance(v, list):
                print(f"  - Array [{k}] ({len(v)} items)")
                if len(v) > 0 and isinstance(v[0], dict):
                    print(f"    Item keys: {list(v[0].keys())}")
            elif isinstance(v, dict):
                print(f"  - Object [{k}] keys: {list(v.keys())}")
            else:
                print(f"  - Key [{k}]: {type(v).__name__} = {str(v)[:50]}")
    elif isinstance(content, list):
        print(f"  - Direct list of {len(content)} items")
