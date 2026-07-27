import re

file_path = r'c:\Users\sotoc\OneDrive\somos_sumapaz\memoria_sumapaz\pmapc.html'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Replace type="number" with type="text"
updated = re.sub(r'type=["\']number["\']', 'type="text"', content)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(updated)

print("Replaced all type='number' with type='text' in pmapc.html!")
