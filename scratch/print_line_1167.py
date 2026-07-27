path = r'C:\Users\sotoc\Downloads\JSON Eduin Eduardo Parada.json'
with open(path, 'r', encoding='utf-8', errors='ignore') as f:
    lines = f.readlines()

print("--- LINES 1150 TO 1175 ---")
for i in range(1150, min(len(lines), 1175)):
    print(f"{i+1}: {repr(lines[i])}")
