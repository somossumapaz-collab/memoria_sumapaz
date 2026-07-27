import json
import urllib.request

path = r'C:\Users\sotoc\Downloads\gemini-code-1785182863120.json'
with open(path, 'r', encoding='utf-8') as f:
    erasmos_data = json.load(f)

print("=== INSPECTING ERASMOS VAQUERO ROMERO JSON ===")
print("Keys in JSON:", list(erasmos_data.keys()))
print("Persona entrevistada:", erasmos_data.get('PMAPC_F01', {}).get('persona_entrevistada'))
print("Unidad productiva:", erasmos_data.get('PMAPC_F01', {}).get('nombre_unidad_productiva'))

# Find Erasmos Vaquero Romero in Producers API
prods_url = "http://localhost:8000/api/get_productores.php"
erasmos_id = None

with urllib.request.urlopen(prods_url) as resp:
    res = json.loads(resp.read().decode('utf-8'))
    for p in res.get('data', []):
        name = p.get('nombre_completo', '')
        if 'erasmo' in name.lower() or 'vaquero' in name.lower():
            print(f"Found Producer: ID={p['id']}, Name='{name}', Document='{p.get('numero_documento')}', Vereda='{p.get('vereda')}'")
            erasmos_id = p['id']

if not erasmos_id:
    raise Exception("Erasmos Vaquero Romero not found in database!")

# Submit to API for Erasmos Vaquero Romero
payload = {
    'productor_id': erasmos_id,
    'data': erasmos_data
}

req = urllib.request.Request('http://localhost:8000/api/submit_pmapc.php', 
                             data=json.dumps(payload).encode('utf-8'), 
                             headers={'Content-Type': 'application/json'})

with urllib.request.urlopen(req) as resp:
    res_text = resp.read().decode('utf-8')
    print(f"\nSubmit API Response for Erasmos (ID {erasmos_id}):", res_text)

# Verify get_pmapc for Erasmos
get_url = f'http://localhost:8000/api/get_pmapc.php?id={erasmos_id}'
with urllib.request.urlopen(get_url) as g_resp:
    res = json.loads(g_resp.read().decode('utf-8'))
    print(f"get_pmapc response for ID {erasmos_id}: success={res.get('success')}, exists={res.get('exists')}")

# Test PDF Download for Erasmos
pdf_url = f'http://localhost:8000/api/download_pmapc_pdf.php?id={erasmos_id}'
with urllib.request.urlopen(pdf_url) as pdf_resp:
    html = pdf_resp.read().decode('utf-8')

print(f"PDF HTML Length for Erasmos Vaquero Romero ({erasmos_id}):", len(html), "bytes")
print("PDF Contains Erasmos / Vaquero?", "Erasmos" in html or "Vaquero" in html or "Baquero" in html)
