import urllib.request

url = 'http://localhost:8000/api/download_pmapc_pdf.php?id=33'
with urllib.request.urlopen(url) as resp:
    html = resp.read().decode('utf-8')

print("PDF Output Length for José Aquino Muñoz Mican (ID 33):", len(html), "bytes")
print("PDF Contains Papas Nativas?", "Papas Nativas" in html or "Papas" in html)
print("PDF Contains José Aquino Muñoz?", "Aquino" in html or "Muñoz" in html or "Munoz" in html)
