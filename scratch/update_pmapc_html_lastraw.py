import re

file_path = r'c:\Users\sotoc\OneDrive\somos_sumapaz\memoria_sumapaz\pmapc.html'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Add global variable declaration for lastLoadedRawJson if not present
if 'let lastLoadedRawJson = null;' not in content:
    content = content.replace('let selectedProducerId = null;', 'let selectedProducerId = null;\n        let lastLoadedRawJson = null;')

# 2. Update isJsonFile parse block to store lastLoadedRawJson
old_json_parse = """                            const parsed = parseJsonSafely(loadedTranscriptText);
                            const pmapcData = normalizePmapcJson(parsed.data || parsed);"""

new_json_parse = """                            const parsed = parseJsonSafely(loadedTranscriptText);
                            lastLoadedRawJson = parsed.data || parsed;
                            const pmapcData = normalizePmapcJson(lastLoadedRawJson);"""

content = content.replace(old_json_parse, new_json_parse)

# 3. Update submitForm payload to merge lastLoadedRawJson
old_payload_start = """            const formData = new FormData(document.getElementById('pmapc-form'));
            const payload = {
                productor_id: selectedProducerId,
                data: {"""

new_payload_start = """            const formData = new FormData(document.getElementById('pmapc-form'));
            const generatedData = {"""

content = content.replace(old_payload_start, new_payload_start)

# Find end of generatedData and construct final payload
old_payload_end = """                    pdf_comentarios: formData.get('pdf_comentarios')
                }
            };"""

new_payload_end = """                    pdf_comentarios: formData.get('pdf_comentarios')
                }
            };

            const finalData = Object.assign({}, lastLoadedRawJson || {}, generatedData);
            const payload = {
                productor_id: selectedProducerId,
                data: finalData
            };"""

content = content.replace(old_payload_end, new_payload_end)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated pmapc.html to preserve lastLoadedRawJson during submit!")
