file_path = r'c:\Users\sotoc\OneDrive\somos_sumapaz\memoria_sumapaz\api\get_productores.php'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

target_old = """            GROUP_CONCAT(DISTINCT cpcat.categoria_id) AS categorias_ids,
            GROUP_CONCAT(DISTINCT c.id) AS certificaciones_ids,
            GROUP_CONCAT(DISTINCT c.nombre ORDER BY c.nombre SEPARATOR ', ') AS certificaciones_nombres
        FROM productores_sumapaz p
        LEFT JOIN caracterizacion_productor cp ON p.id = cp.productor_id
        LEFT JOIN pmapc_registros pm ON p.id = pm.productor_id
        LEFT JOIN productor_categoria cpcat ON p.id = cpcat.productor_id
        LEFT JOIN productor_certificacion pc ON p.id = pc.productor_id
        LEFT JOIN certificaciones c ON pc.certificacion_id = c.id
        GROUP BY p.id"""

target_new = """            GROUP_CONCAT(DISTINCT cpcat.categoria_id) AS categorias_ids,
            GROUP_CONCAT(DISTINCT c.id) AS certificaciones_ids,
            GROUP_CONCAT(DISTINCT c.nombre ORDER BY c.nombre SEPARATOR ', ') AS certificaciones_nombres,
            GROUP_CONCAT(DISTINCT pe.nombre_evento ORDER BY pe.fecha_evento DESC SEPARATOR ', ') AS circuitos_comercializacion
        FROM productores_sumapaz p
        LEFT JOIN caracterizacion_productor cp ON p.id = cp.productor_id
        LEFT JOIN pmapc_registros pm ON p.id = pm.productor_id
        LEFT JOIN productor_categoria cpcat ON p.id = cpcat.productor_id
        LEFT JOIN productor_certificacion pc ON p.id = pc.productor_id
        LEFT JOIN certificaciones c ON pc.certificacion_id = c.id
        LEFT JOIN participacion_eventos pe ON p.id = pe.id_productor
        GROUP BY p.id"""

if target_old in content:
    content = content.replace(target_old, target_new)
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Successfully updated api/get_productores.php!")
else:
    print("Target block not found in api/get_productores.php")
