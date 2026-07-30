import json
import re
import urllib.request

path = r'C:\Users\sotoc\Downloads\JSON Jenny Parra.json'
with open(path, 'r', encoding='utf-8', errors='ignore') as f:
    text = f.read()

# Fix invalid backslashes if any (like \$ -> $)
fixed_text = re.sub(r'\\([^\/\\"' + "'" + r'bfnrtu])', r'\1', text)

try:
    data = json.loads(fixed_text)
    print("SUCCESSFULLY PARSED Jenny Parra JSON!")
    print("Top keys in JSON:", len(data))
    print("Persona entrevistada:", data.get('PMAPC_F01', {}).get('persona_entrevistada') or data.get('f01', {}).get('persona_entrevistada'))
    print("Unidad productiva:", data.get('PMAPC_F01', {}).get('nombre_unidad_productiva') or data.get('f01', {}).get('nombre_unidad_productiva'))
except Exception as e:
    print("Parse error:", e)
    err_pos = getattr(e, 'pos', None)
    if err_pos:
        print("Snippet:", fixed_text[max(0, err_pos-100):min(len(fixed_text), err_pos+100)])
    raise e

# Save cleaned JSON back to disk
with open(path, 'w', encoding='utf-8') as f:
    json.dump(data, f, indent=2, ensure_ascii=False)

# Find producer ID in API
prods_url = "http://localhost:8000/api/get_productores.php"
yeni_id = None

with urllib.request.urlopen(prods_url) as resp:
    res = json.loads(resp.read().decode('utf-8'))
    for p in res.get('data', []):
        name = p.get('nombre_completo', '').lower()
        if ('yeni' in name or 'jenny' in name or 'yeny' in name) and 'parra' in name:
            print(f"Found Candidate: ID={p['id']}, Name='{p['nombre_completo']}', Doc='{p.get('numero_documento')}', Vereda='{p.get('vereda')}'")
            yeni_id = p['id']

if not yeni_id:
    with urllib.request.urlopen(prods_url) as resp:
        res = json.loads(resp.read().decode('utf-8'))
        for p in res.get('data', []):
            name = p.get('nombre_completo', '').lower()
            if 'parra' in name:
                print(f"Found Parra Candidate: ID={p['id']}, Name='{p['nombre_completo']}'")
                yeni_id = p['id']

if not yeni_id:
    raise Exception("Yeni Lorena Parra Gallo not found in database!")

print(f"\nTargeting Producer ID: {yeni_id}")

payload = {
    'productor_id': yeni_id,
    'data': data
}

req = urllib.request.Request('http://localhost:8000/api/submit_pmapc.php', 
                             data=json.dumps(payload).encode('utf-8'), 
                             headers={'Content-Type': 'application/json'})

with urllib.request.urlopen(req) as resp:
    res_text = resp.read().decode('utf-8')
    print(f"Submit API Response for ID {yeni_id}:", res_text)

# Verify get_pmapc for ID
get_url = f'http://localhost:8000/api/get_pmapc.php?id={yeni_id}'
with urllib.request.urlopen(get_url) as g_resp:
    res = json.loads(g_resp.read().decode('utf-8'))
    print(f"get_pmapc response for ID {yeni_id}: success={res.get('success')}, exists={res.get('exists')}")

# Test PDF Download
pdf_url = f'http://localhost:8000/api/download_pmapc_pdf.php?id={yeni_id}'
with urllib.request.urlopen(pdf_url) as pdf_resp:
    html = pdf_resp.read().decode('utf-8')

print(f"PDF HTML Length for ID {yeni_id}:", len(html), "bytes")
