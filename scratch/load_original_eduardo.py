import json
import urllib.request

path = r'C:\Users\sotoc\Downloads\JSON Eduin Eduardo Parada.json'
with open(path, 'r', encoding='utf-8', errors='ignore') as f:
    data = json.load(f)

print("Original JSON parsed cleanly! Keys:", list(data.keys()))

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

# Verify get_pmapc for ID 338
get_url = 'http://localhost:8000/api/get_pmapc.php?id=338'
with urllib.request.urlopen(get_url) as g_resp:
    res = json.loads(g_resp.read().decode('utf-8'))
    print("get_pmapc response for ID 338:", res.get('success'), res.get('exists'))
