import urllib.request
import json

for pid in [165, 283, 77, 338, 1]:
    url = f"http://localhost:8000/api/get_pmapc.php?id={pid}"
    try:
        with urllib.request.urlopen(url) as resp:
            res = json.loads(resp.read().decode('utf-8'))
            print(f"\nget_pmapc API Response for ID {pid}:")
            print("  success:", res.get('success'))
            print("  exists:", res.get('exists'))
            if res.get('data'):
                print("  data keys:", list(res['data'].keys())[:10])
    except Exception as e:
        print(f"Error fetching for ID {pid}:", e)
