import json
import urllib.request

path = r'C:\Users\sotoc\Downloads\JSON Eduin Eduardo Parada.json'
with open(path, 'r', encoding='utf-8', errors='ignore') as f:
    text = f.read()

# Fix any block starting with "key": { and ending with ],
# Replace "fXX": {\n "0": ... ], with "fXX": [\n { ... },
def fix_mismatched_brackets(content):
    lines = content.splitlines()
    stack = []
    fixed_lines = []
    
    for i, line in enumerate(lines):
        line_str = line
        # Check if line opens object for key with {
        if ('"f' in line or '"PMAPC_F' in line) and '{' in line:
            # We are opening an object
            pass
        # Check lines like 1167: '  ],' where open was {
        if line.strip() == '],' or line.strip() == ']' or line.strip() == '],':
            # Check preceding lines
            # If preceding lines had "0": { or "1": {, change ] to }
            prev_block = "\n".join(lines[max(0, i-20):i])
            if '"0": {' in prev_block or '"1": {' in prev_block:
                line_str = line.replace(']', '}')
        fixed_lines.append(line_str)
    return "\n".join(fixed_lines)

repaired_text = fix_mismatched_brackets(text)

try:
    data = json.loads(repaired_text)
    print("SUCCESSFULLY PARSED REPAIRED EDUARDO PARADA JSON! Keys:", len(data))
except Exception as e:
    print("Error parsing repaired text:", e)
    err_pos = getattr(e, 'pos', None)
    if err_pos:
        print("Error snippet:", repaired_text[max(0, err_pos-100):min(len(repaired_text), err_pos+100)])
    raise e

with open(path, 'w', encoding='utf-8') as f:
    json.dump(data, f, indent=2, ensure_ascii=False)

print("Saved clean JSON back to file.")

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
