import re

file_path = r'c:\Users\sotoc\OneDrive\somos_sumapaz\memoria_sumapaz\pmapc.html'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

old_match = """                            if (!validProd && typeof allProducers !== 'undefined' && allProducers && allProducers.length > 0) {
                                const pName = (parsed.productora || pmapcData.productora || '').toLowerCase().trim();
                                const orgName = (parsed.nombre_organizacion || pmapcData.nombre_organizacion || pmapcData.f01?.nombre_organizacion || '').toLowerCase().trim();
                                
                                if (pName || orgName) {
                                    validProd = allProducers.find(p => {
                                        const pNombre = (p.nombre_completo || '').toLowerCase().trim();
                                        const pOrg = (p.nombre_organizacion || '').toLowerCase().trim();
                                        return (pName && (pNombre.includes(pName) || pName.includes(pNombre))) || 
                                               (orgName && pOrg && (pOrg.includes(orgName) || orgName.includes(pOrg)));
                                    });
                                }
                            }"""

new_match = """                            if (!validProd && typeof allProducers !== 'undefined' && allProducers && allProducers.length > 0) {
                                const pName = (parsed.productora || pmapcData.productora || pmapcData.PMAPC_F01?.persona_entrevistada || '').toLowerCase().trim();
                                const orgName = (parsed.nombre_organizacion || pmapcData.nombre_organizacion || pmapcData.f01?.nombre_organizacion || pmapcData.PMAPC_F01?.nombre_unidad_productiva || '').toLowerCase().trim();
                                const fullRawText = JSON.stringify(parsed).toLowerCase();

                                validProd = allProducers.find(p => {
                                    const pNombre = (p.nombre_completo || '').toLowerCase().trim();
                                    const pOrg = (p.nombre_organizacion || '').toLowerCase().trim();
                                    const firstName = pNombre.split(' ')[0];
                                    
                                    if (pName && (pNombre.includes(pName) || pName.includes(pNombre))) return true;
                                    if (orgName && pOrg && (pOrg.includes(orgName) || orgName.includes(pOrg))) return true;
                                    if (firstName && firstName.length >= 4 && fullRawText.includes(firstName)) return true;
                                    return false;
                                });
                            }"""

content = content.replace(old_match, new_match)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated producer matching in pmapc.html!")
