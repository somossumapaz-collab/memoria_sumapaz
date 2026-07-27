import urllib.request
import json

url = 'http://localhost:8000/api/get_productores.php'
with urllib.request.urlopen(url) as resp:
    res = json.loads(resp.read().decode('utf-8'))
    print("API Success?", res.get('success'))
    sample = [p for p in res.get('data', []) if p.get('circuitos_comercializacion')][:5]
    print(f"\nFound {len(sample)} sample producers with event participations:")
    for s in sample:
        print(f" - {s['nombre_completo']}: '{s['circuitos_comercializacion']}'")
