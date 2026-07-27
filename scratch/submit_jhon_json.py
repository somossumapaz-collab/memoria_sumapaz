import json
import urllib.request

path = r'C:\Users\sotoc\Downloads\gemini-code-1785183766797.json'
with open(path, 'r', encoding='utf-8') as f:
    jhon_data = json.load(f)

print("=== INSPECTING JHON ALEXANDER CIFUENTES JSON ===")
print("Keys in JSON:", list(jhon_data.keys()))
print("Persona entrevistada:", jhon_data.get('PMAPC_F01', {}).get('persona_entrevistada'))
print("Unidad productiva:", jhon_data.get('PMAPC_F01', {}).get('nombre_unidad_productiva'))

# Find Jhon Alexander Cifuentes in Producers API
prods_url = "http://localhost:8000/api/get_productores.php"
jhon_id = None

with urllib.request.urlopen(prods_url) as resp:
    res = json.loads(resp.read().decode('utf-8'))
    for p in res.get('data', []):
        name = p.get('nombre_completo', '')
        if 'jhon' in name.lower() and 'cifuentes' in name.lower():
            print(f"Found Exact Match: ID={p['id']}, Name='{name}', Document='{p.get('numero_documento')}', Vereda='{p.get('vereda')}'")
            jhon_id = p['id']
            break

if not jhon_id:
    # Try broader match
    with urllib.request.urlopen(prods_url) as resp:
        res = json.loads(resp.read().decode('utf-8'))
        for p in res.get('data', []):
            name = p.get('nombre_completo', '')
            if 'cifuentes' in name.lower() or 'jhon' in name.lower():
                print(f"Found Candidate: ID={p['id']}, Name='{name}', Document='{p.get('numero_documento')}'")
                jhon_id = p['id']

if not jhon_id:
    raise Exception("Jhon Alexander Cifuentes not found in database!")

# Submit to API for Jhon Alexander Cifuentes
payload = {
    'productor_id': jhon_id,
    'data': jhon_data
}

req = urllib.request.Request('http://localhost:8000/api/submit_pmapc.php', 
                             data=json.dumps(payload).encode('utf-8'), 
                             headers={'Content-Type': 'application/json'})

with urllib.request.urlopen(req) as resp:
    res_text = resp.read().decode('utf-8')
    print(f"\nSubmit API Response for Jhon (ID {jhon_id}):", res_text)

# Verify get_pmapc for Jhon
get_url = f'http://localhost:8000/api/get_pmapc.php?id={jhon_id}'
with urllib.request.urlopen(get_url) as g_resp:
    res = json.loads(g_resp.read().decode('utf-8'))
    print(f"get_pmapc response for ID {jhon_id}: success={res.get('success')}, exists={res.get('exists')}")

# Test PDF Download for Jhon
pdf_url = f'http://localhost:8000/api/download_pmapc_pdf.php?id={jhon_id}'
with urllib.request.urlopen(pdf_url) as pdf_resp:
    html = pdf_resp.read().decode('utf-8')

print(f"PDF HTML Length for Jhon Alexander Cifuentes ({jhon_id}):", len(html), "bytes")
print("PDF Contains Jhon / Cifuentes?", "Jhon" in html or "Cifuentes" in html)
