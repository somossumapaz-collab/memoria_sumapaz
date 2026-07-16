import urllib.request
import json
import sys

url = "http://localhost:8000/api/analyze_transcript.php"
file_path = "C:/Users/sotoc/Downloads/0715 (2).txt"

try:
    with open(file_path, "r", encoding="utf-8") as f:
        transcript = f.read()
except Exception as e:
    print(f"Error reading file: {e}")
    sys.exit(1)

payload = {
    "transcript": transcript
}

req = urllib.request.Request(
    url,
    data=json.dumps(payload).encode('utf-8'),
    headers={'Content-Type': 'application/json'},
    method='POST'
)

try:
    with urllib.request.urlopen(req) as response:
        body = response.read().decode('utf-8')
        data = json.loads(body)
        if data.get("success"):
            keys = sorted(data["data"].keys())
            print("Successfully extracted keys:", keys)
            print("\nWarnings or Errors:", data.get("warnings"))
            # Print sample formatting from f04 or f11
            if "f04" in data["data"]:
                print("\nF04 Sample:\n", json.dumps(data["data"]["f04"], indent=2, ensure_ascii=False))
            if "f11" in data["data"]:
                print("\nF11 Sample:\n", json.dumps(data["data"]["f11"], indent=2, ensure_ascii=False))
        else:
            print("API Error:", data.get("error"))
except Exception as e:
    print(f"Error occurred: {e}")
