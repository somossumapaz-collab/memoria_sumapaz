import os

root_dir = r"C:\Users\sotoc\AndroidStudioProjects"
kt_files = []

for dirpath, dirnames, filenames in os.walk(root_dir):
    # Skip build folders
    if 'build' in dirpath.split(os.sep) or '.gradle' in dirpath.split(os.sep):
        continue
    for filename in filenames:
        if filename.endswith('.kt') and not filename.endswith('Test.kt'):
            filepath = os.path.join(dirpath, filename)
            relpath = os.path.relpath(filepath, root_dir)
            kt_files.append((relpath, os.path.getsize(filepath)))

print(f"Total Kotlin files: {len(kt_files)}")
# Sort by size or project
for path, size in sorted(kt_files):
    print(f"{path} ({size} bytes)")
