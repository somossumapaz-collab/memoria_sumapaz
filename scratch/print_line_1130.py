path = r'C:\Users\sotoc\Downloads\JSON Eduin Eduardo Parada.json'
with open(path, 'r', encoding='utf-8', errors='ignore') as f:
    lines = f.readlines()

print("--- LINES 1130 TO 1150 ---")
for i in range(1130, 1150):
    print(f"{i+1}: {repr(lines[i])}")
