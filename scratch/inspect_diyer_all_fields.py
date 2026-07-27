import json

json_path = r'C:\Users\sotoc\Downloads\gemini-code-1785181025347.json'
with open(json_path, 'r', encoding='utf-8') as f:
    data = json.load(f)

print("=== ALL FORMAT ARRAYS & KEYS IN DIYER JSON ===")
for fmt, obj in data.items():
    print(f"\n--- {fmt} ---")
    if isinstance(obj, dict):
        for k, v in obj.items():
            if isinstance(v, list):
                if len(v) > 0 and isinstance(v[0], dict):
                    print(f"  [Array] '{k}' ({len(v)} rows). Columns: {list(v[0].keys())}")
                else:
                    print(f"  [Array] '{k}' ({len(v)} items)")
            elif isinstance(v, dict):
                print(f"  [Dict] '{k}'. Keys: {list(v.keys())}")
            else:
                print(f"  [Field] '{k}': {str(v)[:60]}")
