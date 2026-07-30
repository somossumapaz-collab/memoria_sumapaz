import json
import re

path = r'C:\Users\sotoc\Downloads\JSON Ivan Dario Chingate.json'
with open(path, 'r', encoding='utf-8', errors='ignore') as f:
    text = f.read()

print("File length:", len(text), "chars")

# Let's try parsing and catch exact line/char of syntax error
try:
    data = json.loads(text)
    print("Direct json.loads succeeded!")
except Exception as e:
    print("Direct parse error:", e)
    err_pos = getattr(e, 'pos', None)
    if err_pos:
        print("\nSnippet around error position:")
        print(text[max(0, err_pos-100):min(len(text), err_pos+100)])

# Apply structural fixes for mismatched dict/array brackets and missing commas
fixed = text
# 1. Fix line 1167 closing bracket ] instead of }
fixed = re.sub(r'\},(\s*)"(\d+)":\s*\{', r'},\1{\n      "id": "\2",', fixed)
fixed = fixed.replace('  ],\n  "f15b":', '  },\n  "f15b":')
fixed = fixed.replace('  ],\n  "f15c":', '  },\n  "f15c":')
fixed = fixed.replace('  ],\n  "f16":', '  },\n  "f16":')

# Try line-by-line check if needed
lines = text.splitlines()
print(f"Total lines in file: {len(lines)}")
for i, l in enumerate(lines):
    if '  ],' in l:
        print(f"Line {i+1}: {l} (preceding line: {lines[max(0, i-1)]})")
