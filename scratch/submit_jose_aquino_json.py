import json
import urllib.request

path = r'C:\Users\sotoc\Downloads\gemini-code-1785185540986.json'
with open(path, 'r', encoding='utf-8') as f:
    jose_aquino_data = json.load(f)

print("=== INSPECTING JOSE AQUINO MUÑOZ JSON ===")
print("Keys in JSON:", list(jose_aquino_data.keys()))
print("Persona entrevistada:", jose_aquino_data.get('PMAPC_F01', {}).get('persona_entrevistada'))
print("Unidad productiva:", jose_aquino_data.get('PMAPC_F01', {}).get('nombre_unidad_productiva'))

# Find Jose Aquino Muñoz in Producers API
prods_url = "http://localhost:8000/api/get_productores.php"
jose_aquino_id = None

with urllib.request.urlopen(prods_url) as resp:
    res = json.loads(resp.read().decode('utf-8'))
    for p in res.get('data', []):
        name = p.get('nombre_completo', '')
        if ('jose' in name.lower() or 'aquino' in name.lower()) and 'muñoz' in name.lower() or 'munoz' in name.lower():
            print(f"Found Match: ID={p['id']}, Name='{name}', Document='{p.get('numero_documento')}', Vereda='{p.get('vereda')}'")
            if 'aquino' in name.lower() or 'jose' in name.lower():
                jose_aquino_id = p['id']
                break

if not jose_aquino_id:
    # Try broader match for Aquino / Muñoz
    with urllib.request.urlopen(prods_url) as resp:
        res = json.loads(resp.read().decode('utf-8'))
        for p in res.get('data', []):
            name = p.get('nombre_completo', '')
            if 'aquino' in name.lower() or 'muñoz' in name.lower() or 'munoz' in name.lower():
                print(f"Found Candidate: ID={p['id']}, Name='{name}', Document='{p.get('numero_documento')}'")
                jose_aquino_id = p['id']

if not jose_aquino_id:
    raise Exception("Jose Aquino Muñoz not found in database!")

# Submit to API for Jose Aquino Muñoz
payload = {
    'productor_id': jose_aquino_id,
    'data': jose_aquino_data
}

req = urllib.request.Request('http://localhost:8000/api/submit_pmapc.php', 
                             data=json.dumps(payload).encode('utf-8'), 
                             headers={'Content-Type': 'application/json'})

with urllib.request.urlopen(req) as resp:
    res_text = resp.read().decode('utf-8')
    print(f"\nSubmit API Response for Jose Aquino (ID {jose_aquino_id}):", res_text)

# Verify get_pmapc for Jose Aquino
get_url = f'http://localhost:8000/api/get_pmapc.php?id={jose_aquino_id}'
with urllib.request.urlopen(get_url) as g_resp:
    res = json.loads(g_resp.read().decode('utf-8'))
    print(f"get_pmapc response for ID {jose_aquino_id}: success={res.get('success')}, exists={res.get('exists')}")

# Test PDF Download for Jose Aquino
pdf_url = f'http://localhost:8000/api/download_pmapc_pdf.php?id={jose_aquino_id}'
with urllib.request.urlopen(pdf_url) as pdf_resp:
    html = pdf_resp.read().decode('utf-8')

print(f"PDF HTML Length for Jose Aquino Muñoz ({jose_aquino_id}):", len(html), "bytes")
print("PDF Contains Jose / Aquino?", "Jose" in html or "Aquino" in html or "Muñoz" in html or "Munoz" in html)
