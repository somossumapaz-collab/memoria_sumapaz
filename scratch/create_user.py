import mysql.connector
import bcrypt
import sys

# ==========================================
# DATOS DEL USUARIO
# ==========================================
nombre = "ambiental_general"
email = "ambiental@gmail.com"
password_plano = "ambiental2026*"
rol_id = 6

# ==========================================
# HASH DE CONTRASEÑA (bcrypt)
# ==========================================
password_bytes = password_plano.encode('utf-8')
salt = bcrypt.gensalt(rounds=12)
password_hash = bcrypt.hashpw(password_bytes, salt)
password_hash_str = password_hash.decode('utf-8')

print(f"Hash generado para {password_plano}: {password_hash_str}")

valores = (nombre, email, password_hash_str, rol_id, 1)

# ==========================================
# 1. CONEXIÓN A BASE DE DATOS REMOTA (15.235.82.117)
# ==========================================
try:
    print("Conectando a DB remota 15.235.82.117...")
    conn = mysql.connector.connect(
        host="15.235.82.117",
        user="somossum_admin",
        password="Talento_suma",
        database="somossum_talento",
        port=3306,
        connect_timeout=10
    )
    cursor = conn.cursor()
    print("Conectado a la base de datos remota 15.235.82.117")

    query = """
    INSERT INTO usuarios (nombre, email, password, rol_id, activo)
    VALUES (%s, %s, %s, %s, %s)
    ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), password=VALUES(password), rol_id=VALUES(rol_id), activo=VALUES(activo)
    """
    cursor.execute(query, valores)
    conn.commit()
    print("Usuario creado/actualizado correctamente en DB remota (15.235.82.117)")

    cursor.execute("SELECT id, nombre, email, rol_id FROM usuarios WHERE email = %s OR nombre = %s", (email, nombre))
    for row in cursor.fetchall():
        print("  -> Registrado:", row)

    cursor.close()
    conn.close()
except Exception as e:
    print(f"Nota DB remota (15.235.82.117): {e}")

# ==========================================
# 2. CONEXIÓN A BASE DE DATOS DEL PROYECTO (srv1220.hstgr.io)
# ==========================================
try:
    print("\nConectando a DB del Proyecto srv1220.hstgr.io...")
    conn_proj = mysql.connector.connect(
        host="srv1220.hstgr.io",
        user="u949171480_sumapaz_admin",
        password="Somossumapaz2026*",
        database="u949171480_somos_sumapaz",
        port=3306,
        connect_timeout=10
    )
    cursor_proj = conn_proj.cursor()
    print("Conectado a la base de datos del proyecto")

    query_proj = """
    INSERT INTO usuarios (nombre, email, password, rol_id, activo)
    VALUES (%s, %s, %s, %s, %s)
    ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), password=VALUES(password), rol_id=VALUES(rol_id), activo=VALUES(activo)
    """
    cursor_proj.execute(query_proj, valores)
    conn_proj.commit()
    print("Usuario creado/actualizado correctamente en DB del Proyecto (srv1220.hstgr.io)")

    cursor_proj.execute("SELECT id, nombre, email, rol_id FROM usuarios WHERE email = %s OR nombre = %s", (email, nombre))
    for row in cursor_proj.fetchall():
        print("  -> Registrado:", row)

    cursor_proj.close()
    conn_proj.close()
except Exception as e:
    print(f"Nota DB proyecto: {e}")
