import urllib.request
import json

url = "http://localhost:8000/api/get_productores.php"
with urllib.request.urlopen(url) as resp:
    res = json.loads(resp.read().decode('utf-8'))
    print("API Success?", res.get('success'))
    data = res.get('data', [])
    print(f"Total producers in API: {len(data)}")
    
    with_events = [p for p in data if p.get('circuitos_comercializacion')]
    print(f"Producers with commercial circuits/events: {len(with_events)}")
    for p in with_events[:5]:
        print(f" - {p['nombre_completo']} ({p['vereda']}): {p['circuitos_comercializacion']}")
