import re
import json

path = r'C:\Users\sotoc\Downloads\JSON Eduin Eduardo Parada.json'
with open(path, 'r', encoding='utf-8', errors='ignore') as f:
    text = f.read()

# Fix "f15a": { ... ], -> "f15a": [ ... ],
fixed = text.replace('"f15a": {\n    "0":', '"f15a": [\n    {\n      "id": "0",')
fixed = re.sub(r'\},(\s*)"(\d+)":\s*\{', r'},\1{\n      "id": "\2",', fixed)

try:
    data = json.loads(fixed)
    print("SUCCESSFULLY FIXED AND PARSED Eduin Eduardo Parada JSON!")
except Exception as e:
    print("Failed to parse fixed text:", e)
    err_pos = getattr(e, 'pos', None)
    if err_pos:
        print("Snippet around error:", fixed[max(0, err_pos-80):min(len(fixed), err_pos+80)])
    raise e

with open(path, 'w', encoding='utf-8') as f:
    json.dump(data, f, indent=2, ensure_ascii=False)

print("Saved repaired JSON back to C:\\Users\\sotoc\\Downloads\\JSON Eduin Eduardo Parada.json")
