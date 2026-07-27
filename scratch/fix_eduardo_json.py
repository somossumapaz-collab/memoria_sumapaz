path = r'C:\Users\sotoc\Downloads\JSON Eduin Eduardo Parada.json'
with open(path, 'r', encoding='utf-8', errors='ignore') as f:
    text = f.read()

lines = text.splitlines()
print(f"Total lines in JSON Eduin Eduardo Parada.json: {len(lines)}")

print("\n--- LINES 1155 TO 1175 ---")
for i in range(max(0, 1155), min(len(lines), 1175)):
    print(f"{i+1}: {lines[i]}")
