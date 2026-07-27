import urllib.request
import json

json_path = r'C:\Users\sotoc\Downloads\gemini-code-1785185540986.json'
with open(json_path, 'r', encoding='utf-8') as f:
    jose_data = json.load(f)

# Submit for ID 33 (José Aquino Muñoz Mican)
payload33 = {'productor_id': 33, 'data': jose_data}
req33 = urllib.request.Request('http://localhost:8000/api/submit_pmapc.php', 
                              data=json.dumps(payload33).encode('utf-8'), 
                              headers={'Content-Type': 'application/json'})
with urllib.request.urlopen(req33) as resp:
    print("Submit Response ID 33:", resp.read().decode('utf-8'))

# Submit for ID 71 (Jose Aquino Muñoz)
payload71 = {'productor_id': 71, 'data': jose_data}
req71 = urllib.request.Request('http://localhost:8000/api/submit_pmapc.php', 
                              data=json.dumps(payload71).encode('utf-8'), 
                              headers={'Content-Type': 'application/json'})
with urllib.request.urlopen(req71) as resp:
    print("Submit Response ID 71:", resp.read().decode('utf-8'))

# Test PDF Download for ID 33
pdf_url = 'http://localhost:8000/api/download_pmapc_pdf.php?id=33'
with urllib.request.urlopen(pdf_url) as pdf_resp:
    html = pdf_resp.read().decode('utf-8')

print("\nPDF HTML Length for José Aquino Muñoz Mican (33):", len(html), "bytes")
print("PDF Contains Papas Nativas?", "Papas Nativas" in html or "Papas" in html)
print("PDF Contains José Aquino Muñoz?", "Aquino" in html or "Muñoz" in html or "Munoz" in html)
