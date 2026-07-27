import zipfile
import xml.etree.ElementTree as ET

docx_path = r"C:\Users\sotoc\Downloads\PMAPC Eduardo Parada.docx"

with zipfile.ZipFile(docx_path) as z:
    xml_content = z.read("word/document.xml")

root = ET.fromstring(xml_content)

namespaces = {
    'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'
}

print("=== READING DOCX CONTENT ===")

body = root.find('w:body', namespaces)

for elem in body:
    tag = elem.tag.split('}')[-1]
    if tag == 'p':
        text = ''.join(elem.itertext()).strip()
        if text:
            print(f"[P] {text}")
    elif tag == 'tbl':
        print("\n--- TABLE START ---")
        for r_idx, row in enumerate(elem.findall('w:tr', namespaces)):
            row_cells = []
            for cell in row.findall('w:tc', namespaces):
                cell_text = ''.join(cell.itertext()).replace('\n', ' ').strip()
                row_cells.append(cell_text)
            print(f"  Row {r_idx}: " + " | ".join(row_cells))
        print("--- TABLE END ---\n")
