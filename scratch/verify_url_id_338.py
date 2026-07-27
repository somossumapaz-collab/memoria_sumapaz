import urllib.request
import json

print("=== VERIFYING ENDPOINTS FOR ID 338 (Eduin Eduardo Parada Macana) ===")

# 1. Check get_productor.php?id=338
url_prod = "http://localhost:8000/api/get_productor.php?id=338"
with urllib.request.urlopen(url_prod) as resp:
    r = json.loads(resp.read().decode('utf-8'))
    print("Producer Info (338):", r.get('success'), r['data']['nombre_completo'], r['data']['vereda'])

# 2. Check get_pmapc.php?id=338
url_pmapc = "http://localhost:8000/api/get_pmapc.php?id=338"
with urllib.request.urlopen(url_pmapc) as resp:
    r = json.loads(resp.read().decode('utf-8'))
    print("PMAPC Info (338):", r.get('success'), r.get('exists'), "Data keys:", list(r['data'].keys())[:5] if r.get('data') else "No data")

# 3. Check PDF download for 338
url_pdf = "http://localhost:8000/api/download_pmapc_pdf.php?id=338"
with urllib.request.urlopen(url_pdf) as resp:
    html = resp.read().decode('utf-8')
    print("PDF Output length (338):", len(html), "bytes")
    print("PDF contains Eduin Eduardo Parada Macana?", "Eduin" in html or "Parada" in html)
