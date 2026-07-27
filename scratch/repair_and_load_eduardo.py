import re
import json
import urllib.request

path = r'C:\Users\sotoc\Downloads\JSON Eduin Eduardo Parada.json'
with open(path, 'r', encoding='utf-8', errors='ignore') as f:
    text = f.read()

# Fix array with dict key objects like "0": { ... } -> { ... }
fixed_text = re.sub(r'\[\s*"(?:\d+)":\s*\{', '[{', text)
fixed_text = re.sub(r'\},\s*"(?:\d+)":\s*\{', '},{', fixed_text)

try:
    data = json.loads(fixed_text)
    print("SUCCESSFULLY PARSED AND REPAIRED Eduin Eduardo Parada JSON!")
except Exception as e:
    print("Repair error:", e)
    # Let's inspect where it failed
    err_pos = getattr(e, 'pos', None)
    if err_pos:
        print("Snippet around error:", fixed_text[max(0, err_pos-100):min(len(fixed_text), err_pos+100)])
    raise e

# Save repaired JSON back to C:\Users\sotoc\Downloads\JSON Eduin Eduardo Parada.json
with open(path, 'w', encoding='utf-8') as f:
    json.dump(data, f, indent=2, ensure_ascii=False)

print("Saved repaired JSON back to disk.")

# Submit to DB for Eduin Eduardo Parada Macana (ID 338)
payload = {
    'productor_id': 338,
    'data': data
}

req = urllib.request.Request('http://localhost:8000/api/submit_pmapc.php', 
                             data=json.dumps(payload).encode('utf-8'), 
                             headers={'Content-Type': 'application/json'})

with urllib.request.urlopen(req) as resp:
    res_text = resp.read().decode('utf-8')
    print("Submit API Response for ID 338:", res_text)

# Test PDF Download for ID 338
pdf_url = 'http://localhost:8000/api/download_pmapc_pdf.php?id=338'
with urllib.request.urlopen(pdf_url) as pdf_resp:
    html = pdf_resp.read().decode('utf-8')

print("\nPDF HTML Length for Eduin Eduardo Parada Macana (338):", len(html), "bytes")
print("Contains Eduin Eduardo Parada?", "Eduin" in html or "Parada" in html)
