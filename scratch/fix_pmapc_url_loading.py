import re

file_path = r'c:\Users\sotoc\OneDrive\somos_sumapaz\memoria_sumapaz\pmapc.html'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

old_url_code = """            if (idParam) {
                selectedProducerId = idParam;
                document.getElementById('select-producer').value = idParam;
                document.getElementById('productor_id').value = idParam;
                document.getElementById('producer-selector-box').style.display = 'none';
                
                // Show prefilled card
                const prod = allProducers.find(p => p.id == idParam);
                if (prod) {
                    document.getElementById('select-producer-input').value = prod.nombre_completo;
                    showProducerDetails(prod);
                }
                
                // Fetch saved PMAPC
                await loadSavedPmapc(idParam);
            }"""

new_url_code = """            if (idParam) {
                selectedProducerId = idParam;
                const pInput = document.getElementById('productor_id');
                if (pInput) pInput.value = idParam;
                const selInput = document.getElementById('select-producer');
                if (selInput) selInput.value = idParam;
                
                let prod = (typeof allProducers !== 'undefined' && allProducers) ? allProducers.find(p => p.id == idParam) : null;
                
                if (!prod) {
                    try {
                        const res = await fetch(API_BASE + `api/get_productor.php?id=${idParam}`);
                        const result = await res.json();
                        if (result.success && result.data) {
                            prod = result.data;
                        }
                    } catch (e) {
                        console.error("Error fetching producer details by ID:", e);
                    }
                }
                
                if (prod) {
                    const searchInput = document.getElementById('select-producer-input');
                    if (searchInput) searchInput.value = prod.nombre_completo;
                    showProducerDetails(prod);
                    const selectorBox = document.getElementById('producer-selector-box');
                    if (selectorBox) selectorBox.style.display = 'none';
                }
                
                // Fetch saved PMAPC
                await loadSavedPmapc(idParam);
            }"""

content = content.replace(old_url_code, new_url_code)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Successfully updated URL parameter loader in pmapc.html!")
