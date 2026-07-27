import json
import urllib.request

path = r'C:\Users\sotoc\Downloads\JSON Eduin Eduardo Parada.json'
with open(path, 'r', encoding='utf-8', errors='ignore') as f:
    text = f.read()

lines = text.splitlines()
lines[1166] = '  },'
lines[1250] = '  },'

clean_text = "\n".join(lines)
data = json.loads(clean_text)

print("PARSED EDUARDO PARADA JSON SUCCESSFULLY! Keys:", len(data))

with open(path, 'w', encoding='utf-8') as f:
    json.dump(data, f, indent=2, ensure_ascii=False)

print("Saved repaired JSON to C:\\Users\\sotoc\\Downloads\\JSON Eduin Eduardo Parada.json")

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

# Test get_pmapc for ID 338
get_url = 'http://localhost:8000/api/get_pmapc.php?id=338'
with urllib.request.urlopen(get_url) as g_resp:
    res = json.loads(g_resp.read().decode('utf-8'))
    print("get_pmapc response for ID 338:", res.get('success'), res.get('exists'))

# Test PDF Download for ID 338
pdf_url = 'http://localhost:8000/api/download_pmapc_pdf.php?id=338'
with urllib.request.urlopen(pdf_url) as pdf_resp:
    html = pdf_resp.read().decode('utf-8')

print("PDF HTML Length for Eduin Eduardo Parada Macana (338):", len(html), "bytes")
