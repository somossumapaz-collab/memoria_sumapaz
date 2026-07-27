import zipfile
import xml.etree.ElementTree as ET

docx_path = r"C:\Users\sotoc\Downloads\PMAPC Eduardo Parada.docx"

with zipfile.ZipFile(docx_path) as z:
    xml_content = z.read("word/document.xml")

root = ET.fromstring(xml_content)
namespaces = {'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'}

body = root.find('w:body', namespaces)

out_file = r"c:\Users\sotoc\OneDrive\somos_sumapaz\memoria_sumapaz\scratch\docx_text.txt"

with open(out_file, "w", encoding="utf-8") as f:
    f.write("=== PMAPC EDUARDO PARADA DOCX STRUCTURE ===\n\n")
    for elem in body:
        tag = elem.tag.split('}')[-1]
        if tag == 'p':
            text = ''.join(elem.itertext()).strip()
            if text:
                f.write(f"[P] {text}\n")
        elif tag == 'tbl':
            f.write("\n--- TABLE START ---\n")
            for r_idx, row in enumerate(elem.findall('w:tr', namespaces)):
                row_cells = []
                for cell in row.findall('w:tc', namespaces):
                    cell_text = ''.join(cell.itertext()).replace('\n', ' ').strip()
                    row_cells.append(cell_text)
                f.write(f"  Row {r_idx}: " + " | ".join(row_cells) + "\n")
            f.write("--- TABLE END ---\n\n")

print(f"Dumped structure to {out_file}")
