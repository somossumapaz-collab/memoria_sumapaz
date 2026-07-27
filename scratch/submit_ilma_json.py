import json
import urllib.request

path = r'C:\Users\sotoc\Downloads\gemini-code-1785183350513.json'
with open(path, 'r', encoding='utf-8') as f:
    ilma_data = json.load(f)

print("=== INSPECTING ILMA NIEVES BAQUERO HORTUA JSON ===")
print("Keys in JSON:", list(ilma_data.keys()))
print("Persona entrevistada:", ilma_data.get('PMAPC_F01', {}).get('persona_entrevistada'))
print("Unidad productiva:", ilma_data.get('PMAPC_F01', {}).get('nombre_unidad_productiva'))

# Find Ilma Nieves Baquero Hortua in Producers API
prods_url = "http://localhost:8000/api/get_productores.php"
ilma_id = None

with urllib.request.urlopen(prods_url) as resp:
    res = json.loads(resp.read().decode('utf-8'))
    for p in res.get('data', []):
        name = p.get('nombre_completo', '')
        if 'ilma' in name.lower() or 'nieves' in name.lower():
            print(f"Found Producer: ID={p['id']}, Name='{name}', Document='{p.get('numero_documento')}', Vereda='{p.get('vereda')}'")
            if 'ilma' in name.lower():
                ilma_id = p['id']

if not ilma_id:
    raise Exception("Ilma Nieves Baquero Hortua not found in database!")

# Submit to API for Ilma Nieves Baquero Hortua
payload = {
    'productor_id': ilma_id,
    'data': ilma_data
}

req = urllib.request.Request('http://localhost:8000/api/submit_pmapc.php', 
                             data=json.dumps(payload).encode('utf-8'), 
                             headers={'Content-Type': 'application/json'})

with urllib.request.urlopen(req) as resp:
    res_text = resp.read().decode('utf-8')
    print(f"\nSubmit API Response for Ilma (ID {ilma_id}):", res_text)

# Verify get_pmapc for Ilma
get_url = f'http://localhost:8000/api/get_pmapc.php?id={ilma_id}'
with urllib.request.urlopen(get_url) as g_resp:
    res = json.loads(g_resp.read().decode('utf-8'))
    print(f"get_pmapc response for ID {ilma_id}: success={res.get('success')}, exists={res.get('exists')}")

# Test PDF Download for Ilma
pdf_url = f'http://localhost:8000/api/download_pmapc_pdf.php?id={ilma_id}'
with urllib.request.urlopen(pdf_url) as pdf_resp:
    html = pdf_resp.read().decode('utf-8')

print(f"PDF HTML Length for Ilma Nieves Baquero Hortua ({ilma_id}):", len(html), "bytes")
print("PDF Contains Ilma / Baquero?", "Ilma" in html or "Baquero" in html or "Nieves" in html)
