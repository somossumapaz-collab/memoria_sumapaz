import urllib.request
import json

url = "http://localhost:8000/api/get_productores.php"
try:
    with urllib.request.urlopen(url) as resp:
        res = json.loads(resp.read().decode('utf-8'))
        print("get_productores API Response success?", res.get('success'))
        print("Number of producers loaded:", len(res.get('data', [])))
        if res.get('data'):
            print("First producer:", res['data'][0])
except Exception as e:
    print("Error calling get_productores:", e)
