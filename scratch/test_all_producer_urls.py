import urllib.request
import json

test_ids = [338, 165, 283, 77, 4, 26, 36, 57, 65, 92, 143, 161, 193, 208]

print("=== TESTING PRODUCER API & PMAPC API FOR ALL IDs ===")

for pid in test_ids:
    prod_url = f"http://localhost:8000/api/get_productor.php?id={pid}"
    pmapc_url = f"http://localhost:8000/api/get_pmapc.php?id={pid}"
    
    prod_name = "Unknown"
    has_pmapc = False
    data_len = 0
    
    try:
        with urllib.request.urlopen(prod_url) as resp:
            r = json.loads(resp.read().decode('utf-8'))
            if r.get('success'):
                prod_name = r['data']['nombre_completo']
    except Exception as e:
        prod_name = f"Error: {e}"

    try:
        with urllib.request.urlopen(pmapc_url) as resp:
            r = json.loads(resp.read().decode('utf-8'))
            if r.get('success') and r.get('exists'):
                has_pmapc = True
                data_len = len(json.dumps(r['data']))
    except Exception as e:
        pass

    print(f"ID {pid:3d} | Producer: {prod_name:35s} | PMAPC Saved: {'YES (' + str(data_len) + ' bytes)' if has_pmapc else 'NO (Blank template)'}")
