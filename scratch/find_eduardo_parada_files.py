import os

downloads_dir = r'C:\Users\sotoc\Downloads'
matching_files = []

for f in os.listdir(downloads_dir):
    if 'parada' in f.lower() or 'eduardo' in f.lower() or 'eduin' in f.lower() or '338' in f:
        matching_files.append(os.path.join(downloads_dir, f))

print("Matching files for Eduardo Parada in Downloads:")
for mf in matching_files:
    print(" -", mf)
