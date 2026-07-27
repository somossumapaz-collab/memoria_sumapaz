import json
import urllib.request

path = r'C:\Users\sotoc\Downloads\gemini-code-1785184013926.json'
with open(path, 'r', encoding='utf-8') as f:
    jose_data = json.load(f)

print("=== INSPECTING JOSE ALEJANDRO PULIDO MORENO JSON ===")
print("Keys in JSON:", list(jose_data.keys()))
print("Persona entrevistada:", jose_data.get('PMAPC_F01', {}).get('persona_entrevistada'))
print("Unidad productiva:", jose_data.get('PMAPC_F01', {}).get('nombre_unidad_productiva'))

# Find Jose Alejandro Pulido Moreno in Producers API
prods_url = "http://localhost:8000/api/get_productores.php"
jose_id = None

with urllib.request.urlopen(prods_url) as resp:
    res = json.loads(resp.read().decode('utf-8'))
    for p in res.get('data', []):
        name = p.get('nombre_completo', '')
        if 'jose' in name.lower() and 'pulido' in name.lower():
            print(f"Found Match: ID={p['id']}, Name='{name}', Document='{p.get('numero_documento')}', Vereda='{p.get('vereda')}'")
            jose_id = p['id']
            break

if not jose_id:
    # Try broader match for Pulido / Alejandro
    with urllib.request.urlopen(prods_url) as resp:
        res = json.loads(resp.read().decode('utf-8'))
        for p in res.get('data', []):
            name = p.get('nombre_completo', '')
            if 'pulido' in name.lower() or 'alejandro' in name.lower():
                print(f"Found Candidate: ID={p['id']}, Name='{name}', Document='{p.get('numero_documento')}'")
                jose_id = p['id']

if not jose_id:
    raise Exception("Jose Alejandro Pulido Moreno not found in database!")

# Submit to API for Jose Alejandro Pulido Moreno
payload = {
    'productor_id': jose_id,
    'data': jose_data
}

req = urllib.request.Request('http://localhost:8000/api/submit_pmapc.php', 
                             data=json.dumps(payload).encode('utf-8'), 
                             headers={'Content-Type': 'application/json'})

with urllib.request.urlopen(req) as resp:
    res_text = resp.read().decode('utf-8')
    print(f"\nSubmit API Response for Jose (ID {jose_id}):", res_text)

# Verify get_pmapc for Jose
get_url = f'http://localhost:8000/api/get_pmapc.php?id={jose_id}'
with urllib.request.urlopen(get_url) as g_resp:
    res = json.loads(g_resp.read().decode('utf-8'))
    print(f"get_pmapc response for ID {jose_id}: success={res.get('success')}, exists={res.get('exists')}")

# Test PDF Download for Jose
pdf_url = f'http://localhost:8000/api/download_pmapc_pdf.php?id={jose_id}'
with urllib.request.urlopen(pdf_url) as pdf_resp:
    html = pdf_resp.read().decode('utf-8')

print(f"PDF HTML Length for Jose Alejandro Pulido Moreno ({jose_id}):", len(html), "bytes")
print("PDF Contains Jose / Pulido?", "Jose" in html or "Pulido" in html or "Alejandro" in html)
