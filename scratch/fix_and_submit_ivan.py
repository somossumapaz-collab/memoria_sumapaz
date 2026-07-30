import json
import re
import urllib.request

path = r'C:\Users\sotoc\Downloads\JSON Ivan Dario Chingate.json'
with open(path, 'r', encoding='utf-8', errors='ignore') as f:
    text = f.read()

# Fix invalid backslashes before $ or other non-escape characters
fixed_text = re.sub(r'\\([^\/\\"' + "'" + r'bfnrtu])', r'\1', text)

try:
    data = json.loads(fixed_text)
    print("SUCCESSFULLY PARSED AND REPAIRED Ivan Dario Chingate JSON!")
    print("Top keys in JSON:", len(data))
    print("Persona entrevistada:", data.get('PMAPC_F01', {}).get('persona_entrevistada') or data.get('f01', {}).get('persona_entrevistada'))
except Exception as e:
    print("Parse error after fix:", e)
    err_pos = getattr(e, 'pos', None)
    if err_pos:
        print("Snippet:", fixed_text[max(0, err_pos-100):min(len(fixed_text), err_pos+100)])
    raise e

# Save cleaned JSON back to disk
with open(path, 'w', encoding='utf-8') as f:
    json.dump(data, f, indent=2, ensure_ascii=False)

print("Saved clean JSON back to C:\\Users\\sotoc\\Downloads\\JSON Ivan Dario Chingate.json")

# Submit for Producer ID 296 (Ivan Dario Chingate Mican)
ivan_id = 296

payload = {
    'productor_id': ivan_id,
    'data': data
}

req = urllib.request.Request('http://localhost:8000/api/submit_pmapc.php', 
                             data=json.dumps(payload).encode('utf-8'), 
                             headers={'Content-Type': 'application/json'})

with urllib.request.urlopen(req) as resp:
    res_text = resp.read().decode('utf-8')
    print(f"\nSubmit API Response for Ivan Dario Chingate Mican (ID {ivan_id}):", res_text)

# Verify get_pmapc for ID 296
get_url = f'http://localhost:8000/api/get_pmapc.php?id={ivan_id}'
with urllib.request.urlopen(get_url) as g_resp:
    res = json.loads(g_resp.read().decode('utf-8'))
    print(f"get_pmapc response for ID {ivan_id}: success={res.get('success')}, exists={res.get('exists')}")

# Test PDF Download for ID 296
pdf_url = f'http://localhost:8000/api/download_pmapc_pdf.php?id={ivan_id}'
with urllib.request.urlopen(pdf_url) as pdf_resp:
    html = pdf_resp.read().decode('utf-8')

print(f"PDF HTML Length for Ivan Dario Chingate Mican ({ivan_id}):", len(html), "bytes")
print("PDF Contains Ivan / Chingate?", "Ivan" in html or "Chingate" in html or "Iván" in html)
