path = r'C:\Users\sotoc\Downloads\JSON Eduin Eduardo Parada.json'
with open(path, 'r', encoding='utf-8', errors='ignore') as f:
    lines = f.readlines()

print("--- LINES 1240 TO 1260 ---")
for i in range(1240, min(len(lines), 1260)):
    print(f"{i+1}: {repr(lines[i])}")
