import json
import urllib.request

json_path = r'C:\Users\sotoc\Downloads\gemini-code-1785181025347.json'
with open(json_path, 'r', encoding='utf-8') as f:
    diyer_json = json.load(f)

# Submit to API for Diyer Gerardo Prieto Hurtado (ID 77)
payload = {
    'productor_id': 77,
    'data': diyer_json
}

req = urllib.request.Request('http://localhost:8000/api/submit_pmapc.php', 
                             data=json.dumps(payload).encode('utf-8'), 
                             headers={'Content-Type': 'application/json'})

with urllib.request.urlopen(req) as resp:
    res_text = resp.read().decode('utf-8')
    print("Submit API Response:", res_text)

# Download PDF HTML for Diyer (ID 77)
pdf_url = 'http://localhost:8000/api/download_pmapc_pdf.php?id=77'
with urllib.request.urlopen(pdf_url) as pdf_resp:
    html = pdf_resp.read().decode('utf-8')

print("\n=== COMPARING DIYER JSON VS GENERATED PDF HTML ===")
print("PDF HTML Length:", len(html), "bytes")

missing_count = 0
found_count = 0

for fmt_key, content in diyer_json.items():
    code = fmt_key.replace("PMAPC_", "")
    header = f"FORMATO PMAPC-{code}"
    is_in_pdf = header in html
    if not is_in_pdf:
        print(f"MISSING HEADER: {header}")
        missing_count += 1
    else:
        found_count += 1

    if isinstance(content, dict):
        for subk, subv in content.items():
            if isinstance(subv, list) and len(subv) > 0:
                first_item = subv[0]
                if isinstance(first_item, dict):
                    for k, v in first_item.items():
                        if v and isinstance(v, str) and len(v) > 4:
                            # check snippet in html
                            snip = v[:12]
                            if snip not in html:
                                print(f"[{fmt_key} -> {subk} -> {k}] NOT IN HTML: '{v}'")
            elif isinstance(subv, dict):
                for k, v in subv.items():
                    if v and isinstance(v, str) and len(v) > 4:
                        snip = v[:12]
                        if snip not in html:
                            print(f"[{fmt_key} -> {subk} -> {k}] NOT IN HTML: '{v}'")
            elif isinstance(subv, str) and len(subv) > 5 and not subk.startswith('observaciones'):
                snip = subv[:12]
                if snip not in html:
                    print(f"[{fmt_key} -> {subk}] NOT IN HTML: '{subv}'")

print(f"\nSummary: {found_count} headers found, {missing_count} missing headers.")
