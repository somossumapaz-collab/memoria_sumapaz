import json

path = r'C:\Users\sotoc\Downloads\JSON Eduin Eduardo Parada.json'
with open(path, 'r', encoding='utf-8', errors='ignore') as f:
    lines = f.readlines()

# Line 1167 (index 1166) is '  ],\n' -> '  },\n'
lines[1166] = '  },\n'

fixed_text = ''.join(lines)
data = json.loads(fixed_text)
print("SUCCESSFULLY PARSED Eduin Eduardo Parada JSON! Top keys:", list(data.keys()))

with open(path, 'w', encoding='utf-8') as f:
    json.dump(data, f, indent=2, ensure_ascii=False)

print("Saved clean JSON back to C:\\Users\\sotoc\\Downloads\\JSON Eduin Eduardo Parada.json")

# Submit to DB for Eduin Eduardo Parada Macana (ID 338)
payload = {
    'productor_id': 338,
    'data': data
}

req = urllib.request.Request('http://localhost:8000/api/submit_pmapc.php', 
                             data=json.dumps(payload).encode('utf-8'), 
                             headers={'Content-Type': 'application/json'})

with urllib.request.urlopen(req) as resp:
    res_text = resp.read().decode('utf-8')
    print("Submit API Response for ID 338:", res_text)
