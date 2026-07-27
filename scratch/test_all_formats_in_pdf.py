import urllib.request

pdf_url = 'http://localhost:8000/api/download_pmapc_pdf.php?id=165'
with urllib.request.urlopen(pdf_url) as pdf_resp:
    html = pdf_resp.read().decode('utf-8')

print("=== PDF FORMAT VERIFICATION FOR CARMEN ROSA MORENO MORENO ===")
print("Total HTML length:", len(html), "bytes\n")

formats = [
    "F01", "F02", "F03", "F04", "F05", "F06", "F07", "F08", "F09", "F10", 
    "F11", "F12", "F12A", "F12B", "F12C", "F13", "F14", "F15", "F15A", "F15B", 
    "F15C", "F16", "F17", "F18", "F19", "F20", "F21", "F22", "F22A", "F23", 
    "F24", "F25", "F26"
]

for f in formats:
    present = f"FORMATO PMAPC-{f}" in html
    print(f"Format [{f}]: {'PRESENT' if present else 'MISSING'}")

# Check 3-column F08
if "FORMATO PMAPC-F08" in html:
    f08_idx = html.find("FORMATO PMAPC-F08")
    f08_block = html[f08_idx:f08_idx+1500]
    print("\n--- F08 BLOCK PREVIEW ---")
    print(f08_block[:600])

# Check F12A, F12B, F12C, F13, F14, F17, F24
for check_str in ["Venta previa", "Acueducto", "Purina", "45 bultos", "Gallinas de descarte", "Cartilla de postura"]:
    print(f"Contains '{check_str}'? ", check_str in html)
