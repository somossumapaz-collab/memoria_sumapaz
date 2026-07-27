file_path = r'c:\Users\sotoc\OneDrive\somos_sumapaz\memoria_sumapaz\productores_registrados.html'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Update the table header
old_header = """                        <th>Certificados</th>
                        <th>Beneficiario</th>"""

new_header = """                        <th>Certificados</th>
                        <th>Circuitos de Comercialización</th>
                        <th>Beneficiario</th>"""

content = content.replace(old_header, new_header)

# 2. Update colspans
content = content.replace('colspan="16"', 'colspan="17"')

# 3. Update renderOriginalTable logic
old_certs_logic = """                let certsHtml = '-';
                if (productor.certificaciones_nombres) {
                    certsHtml = productor.certificaciones_nombres.split(', ').map(c => 
                        `<span style="display: inline-block; font-size: 0.75rem; background: #eef8f6; color: #2A9D8F; padding: 2px 6px; border-radius: 4px; margin-right: 4px; margin-bottom: 4px; font-weight: 500; border: 1px solid #ccece6;">${c}</span>`
                    ).join('');
                }"""

new_certs_logic = """                let certsHtml = '-';
                if (productor.certificaciones_nombres) {
                    certsHtml = productor.certificaciones_nombres.split(', ').map(c => 
                        `<span style="display: inline-block; font-size: 0.75rem; background: #eef8f6; color: #2A9D8F; padding: 2px 6px; border-radius: 4px; margin-right: 4px; margin-bottom: 4px; font-weight: 500; border: 1px solid #ccece6;">${c}</span>`
                    ).join('');
                }

                let circuitosHtml = '-';
                if (productor.circuitos_comercializacion) {
                    circuitosHtml = productor.circuitos_comercializacion.split(', ').map(circ => 
                        `<span style="display: inline-block; font-size: 0.75rem; background: #fff8e7; color: #d35400; padding: 2px 6px; border-radius: 4px; margin-right: 4px; margin-bottom: 4px; font-weight: 500; border: 1px solid #f9e79f;">${circ}</span>`
                    ).join('');
                }"""

content = content.replace(old_certs_logic, new_certs_logic)

# 4. Update row innerHTML
old_row_td = """                    <td><div style="max-width: 250px; display: flex; flex-wrap: wrap;">${certsHtml}</div></td>
                    <td style="text-align: center; vertical-align: middle;">${beneficiarioHtml}</td>"""

new_row_td = """                    <td><div style="max-width: 250px; display: flex; flex-wrap: wrap;">${certsHtml}</div></td>
                    <td><div style="max-width: 250px; display: flex; flex-wrap: wrap;">${circuitosHtml}</div></td>
                    <td style="text-align: center; vertical-align: middle;">${beneficiarioHtml}</td>"""

content = content.replace(old_row_td, new_row_td)

# 5. Update exportToExcel headers
old_excel_headers = """            const headers = [
                'Ranking Ajustado', 'Ranking Estándar', 'Puntaje Total', 'Puntaje Ajustado', 'Estado Caracterización', 'Fecha Inscripción', 
                'Nombre Completo', 'Tipo Documento', 'Número Documento', 'Vereda', 'Cuenca', 'Teléfono', 
                'Correo Electrónico', 'Nombre Predio', 'Fecha Nacimiento', 'Mypime', 'Efectividad 2025', 'Panaca'
            ];"""

new_excel_headers = """            const headers = [
                'Ranking Ajustado', 'Ranking Estándar', 'Puntaje Total', 'Puntaje Ajustado', 'Estado Caracterización', 'Fecha Inscripción', 
                'Nombre Completo', 'Tipo Documento', 'Número Documento', 'Vereda', 'Cuenca', 'Teléfono', 
                'Correo Electrónico', 'Nombre Predio', 'Fecha Nacimiento', 'Circuitos de Comercialización', 'Mypime', 'Efectividad 2025', 'Panaca'
            ];"""

content = content.replace(old_excel_headers, new_excel_headers)

# 6. Update exportToExcel row data
old_excel_row = """                    `"${p.nombre_predio || '-'}"`,
                    p.fecha_nacimiento || '-',
                    p.mypime == 1 ? 'Sí' : 'No',"""

new_excel_row = """                    `"${p.nombre_predio || '-'}"`,
                    p.fecha_nacimiento || '-',
                    `"${(p.circuitos_comercializacion || '-').replace(/"/g, '""')}"`,
                    p.mypime == 1 ? 'Sí' : 'No',"""

content = content.replace(old_excel_row, new_excel_row)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Successfully updated productores_registrados.html with Circuitos de Comercialización column!")
