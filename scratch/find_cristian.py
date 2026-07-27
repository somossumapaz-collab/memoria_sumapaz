import urllib.request
import json

url = "http://localhost:8000/api/get_productores.php"
with urllib.request.urlopen(url) as resp:
    producers = json.loads(resp.read().decode('utf-8'))

for p in producers:
    name = p.get('nombre_completo', '')
    if 'cristian' in name.lower() or 'morales' in name.lower():
        print(f"Found Producer: ID={p['id']}, Name='{p['nombre_completo']}', Document='{p.get('numero_documento')}', Vereda='{p.get('vereda')}'")
