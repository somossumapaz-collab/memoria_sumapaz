filepath = r"C:\Users\sotoc\AndroidStudioProjects\transportes_sumapaz\app\src\main\java\com\example\transportes_sumapaz\ui\Screens.kt"
with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
    for line in f:
        if 'import' in line and 'icon' in line.lower():
            print(line.strip())
