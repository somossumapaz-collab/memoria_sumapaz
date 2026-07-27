path = r'C:\Users\sotoc\Downloads\JSON Eduin Eduardo Parada.json'
with open(path, 'r', encoding='utf-8', errors='ignore') as f:
    lines = f.readlines()

print("--- LINES 940 TO 980 IN JSON Eduin Eduardo Parada.json ---")
for i in range(940, min(len(lines), 980)):
    print(f"{i+1}: {lines[i]}", end='')
