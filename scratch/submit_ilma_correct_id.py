import json
import urllib.request

path = r'C:\Users\sotoc\Downloads\gemini-code-1785183350513.json'
with open(path, 'r', encoding='utf-8') as f:
    ilma_data = json.load(f)

# Exact producer ID for Ilma Nieves Baquero Hortua is 225
ilma_id = 225

# Submit to API for Ilma Nieves Baquero Hortua (ID 225)
payload = {
    'productor_id': ilma_id,
    'data': ilma_data
}

req = urllib.request.Request('http://localhost:8000/api/submit_pmapc.php', 
                             data=json.dumps(payload).encode('utf-8'), 
                             headers={'Content-Type': 'application/json'})

with urllib.request.urlopen(req) as resp:
    res_text = resp.read().decode('utf-8')
    print(f"Submit API Response for Ilma Nieves Baquero Hortua (ID {ilma_id}):", res_text)

# Verify get_pmapc for ID 225
get_url = f'http://localhost:8000/api/get_pmapc.php?id={ilma_id}'
with urllib.request.urlopen(get_url) as g_resp:
    res = json.loads(g_resp.read().decode('utf-8'))
    print(f"get_pmapc response for ID {ilma_id}: success={res.get('success')}, exists={res.get('exists')}")

# Test PDF Download for ID 225
pdf_url = f'http://localhost:8000/api/download_pmapc_pdf.php?id={ilma_id}'
with urllib.request.urlopen(pdf_url) as pdf_resp:
    html = pdf_resp.read().decode('utf-8')

print(f"PDF HTML Length for Ilma Nieves Baquero Hortua ({ilma_id}):", len(html), "bytes")
print("PDF Contains Ilma Nieves Baquero Hortua?", "Ilma" in html and "Baquero" in html)
