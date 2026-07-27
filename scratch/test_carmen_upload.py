import json
import urllib.request

json_path = r'C:\Users\sotoc\Downloads\extracted_data.json'
with open(json_path, 'r', encoding='utf-8') as f:
    extracted = json.load(f)

# Submit to API for Carmen Rosa Moreno Moreno (ID 165)
payload = {
    'productor_id': 165,
    'data': extracted
}

req = urllib.request.Request('http://localhost:8000/api/submit_pmapc.php', 
                             data=json.dumps(payload).encode('utf-8'), 
                             headers={'Content-Type': 'application/json'})

with urllib.request.urlopen(req) as resp:
    res_text = resp.read().decode('utf-8')
    print("Submit API Response:", res_text)

# Test PDF Download for Carmen Rosa Moreno Moreno (ID 165)
pdf_url = 'http://localhost:8000/api/download_pmapc_pdf.php?id=165'
with urllib.request.urlopen(pdf_url) as pdf_resp:
    pdf_html = pdf_resp.read().decode('utf-8')

print("\nPDF HTML Output Length:", len(pdf_html), "bytes")
print("Contains Carmen Rosa Moreno? ", "Carmen Rosa Moreno" in pdf_html)
print("Contains Gallinas Ponedoras? ", "Gallinas Ponedoras" in pdf_html or "gallinas" in pdf_html.lower())
print("Contains Huevos frescos? ", "Huevos frescos" in pdf_html or "huevo" in pdf_html.lower())
print("Contains Procansu? ", "Procansu" in pdf_html or "procansu" in pdf_html.lower())
print("Contains 'Pendiente de verificar'? ", pdf_html.count("Pendiente de verificar"), "times")
