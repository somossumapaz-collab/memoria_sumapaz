import os

root_dir = r"C:\Users\sotoc\AndroidStudioProjects"
matches = []

for dirpath, dirnames, filenames in os.walk(root_dir):
    for filename in filenames:
        if filename.endswith(('.kt', '.java', '.xml')):
            filepath = os.path.join(dirpath, filename)
            try:
                with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
                    content = f.read()
                    if 'WebView' in content or 'android.webkit' in content:
                        matches.append(filepath)
            except Exception as e:
                pass

print(f"Found {len(matches)} files referencing WebView:")
for m in matches[:20]:
    print(m)
