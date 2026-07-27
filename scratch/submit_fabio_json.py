import json
import urllib.request

path = r'C:\Users\sotoc\Downloads\gemini-code-1785183129744.json'
with open(path, 'r', encoding='utf-8') as f:
    fabio_data = json.load(f)

print("=== INSPECTING FABIO AGUSTO ROMERO HORTIA JSON ===")
print("Keys in JSON:", list(fabio_data.keys()))
print("Persona entrevistada:", fabio_data.get('PMAPC_F01', {}).get('persona_entrevistada'))
print("Unidad productiva:", fabio_data.get('PMAPC_F01', {}).get('nombre_unidad_productiva'))

# Find Fabio Agusto Romero Hortia in Producers API
prods_url = "http://localhost:8000/api/get_productores.php"
fabio_id = None

with urllib.request.urlopen(prods_url) as resp:
    res = json.loads(resp.read().decode('utf-8'))
    for p in res.get('data', []):
        name = p.get('nombre_completo', '')
        if 'fabio' in name.lower() or 'augusto' in name.lower() or 'agusto' in name.lower():
            print(f"Found Producer: ID={p['id']}, Name='{name}', Document='{p.get('numero_documento')}', Vereda='{p.get('vereda')}'")
            if 'fabio' in name.lower():
                fabio_id = p['id']

if not fabio_id:
    raise Exception("Fabio Agusto Romero Hortia not found in database!")

# Submit to API for Fabio Agusto Romero Hortia
payload = {
    'productor_id': fabio_id,
    'data': fabio_data
}

req = urllib.request.Request('http://localhost:8000/api/submit_pmapc.php', 
                             data=json.dumps(payload).encode('utf-8'), 
                             headers={'Content-Type': 'application/json'})

with urllib.request.urlopen(req) as resp:
    res_text = resp.read().decode('utf-8')
    print(f"\nSubmit API Response for Fabio (ID {fabio_id}):", res_text)

# Verify get_pmapc for Fabio
get_url = f'http://localhost:8000/api/get_pmapc.php?id={fabio_id}'
with urllib.request.urlopen(get_url) as g_resp:
    res = json.loads(g_resp.read().decode('utf-8'))
    print(f"get_pmapc response for ID {fabio_id}: success={res.get('success')}, exists={res.get('exists')}")

# Test PDF Download for Fabio
pdf_url = f'http://localhost:8000/api/download_pmapc_pdf.php?id={fabio_id}'
with urllib.request.urlopen(pdf_url) as pdf_resp:
    html = pdf_resp.read().decode('utf-8')

print(f"PDF HTML Length for Fabio Agusto Romero Hortia ({fabio_id}):", len(html), "bytes")
print("PDF Contains Fabio / Romero?", "Fabio" in html or "Romero" in html)
