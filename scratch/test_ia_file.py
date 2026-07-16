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
        status_code = response.getcode()
        body = response.read().decode('utf-8')
        print(f"Status Code: {status_code}")
        print("Raw Response Body:")
        print(body[:2000]) # Print first 2000 chars of raw body
        if len(body) > 2000:
            print("...[truncated]")
        
        # Try loading as JSON
        data = json.loads(body)
        print("\nParsed JSON successfully!")
except urllib.error.HTTPError as e:
    print(f"HTTP Error: {e.code}")
    print(e.read().decode('utf-8'))
except Exception as e:
    print(f"Error occurred: {e}")
