import json
import urllib.request

json_path = r'C:\Users\sotoc\Downloads\gemini-code-1785179712231.json'
with open(json_path, 'r', encoding='utf-8') as f:
    extracted = json.load(f)

# Submit to API for Cristian Alonso Morales Palacios (ID 283)
payload = {
    'productor_id': 283,
    'data': extracted
}

req = urllib.request.Request('http://localhost:8000/api/submit_pmapc.php', 
                             data=json.dumps(payload).encode('utf-8'), 
                             headers={'Content-Type': 'application/json'})

with urllib.request.urlopen(req) as resp:
    res_text = resp.read().decode('utf-8')
    print("Submit API Response:", res_text)

# Test PDF Download for Cristian (ID 283)
pdf_url = 'http://localhost:8000/api/download_pmapc_pdf.php?id=283'
with urllib.request.urlopen(pdf_url) as pdf_resp:
    pdf_html = pdf_resp.read().decode('utf-8')

print("\nPDF HTML Length for Cristian:", len(pdf_html), "bytes")
print("Contains Cristian Alonso Morales Palacios?", "Cristian" in pdf_html or "Morales" in pdf_html)
print("Contains F19 in PDF?", "FORMATO PMAPC-F19" in pdf_html)

if "FORMATO PMAPC-F19" in pdf_html:
    idx = pdf_html.find("FORMATO PMAPC-F19")
    print("\n--- F19 PREVIEW IN PDF ---")
    print(pdf_html[idx:idx+2000])

print("\nContains 'Fuente de agua'?", "Fuente de agua" in pdf_html)
print("Contains 'Consumo de agua'?", "Consumo de agua" in pdf_html)
print("Contains 'Almacén Camp'?", "Almacén Camp" in pdf_html or "Almac" in pdf_html)
