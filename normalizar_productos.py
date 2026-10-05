import mysql.connector
import unicodedata
import re
import time

# ============================================================
# CONEXIÓN
# ============================================================

conn = mysql.connector.connect(
    host="srv1220.hstgr.io",
    user="u949171480_sumapaz_admin",
    password="Somossumapaz2026*",
    database="u949171480_somos_sumapaz",
    port=3306
)

cursor = conn.cursor(dictionary=True)
print("Conectado a la base de datos")

# ============================================================
# FUNCIÓN PARA LIMPIAR TEXTO
# ============================================================

def limpiar(texto):
    if not texto:
        return ""
    texto = texto.strip().lower()
    # Quitar tildes
    texto = unicodedata.normalize("NFD", texto)
    texto = "".join(
        c for c in texto
        if unicodedata.category(c) != "Mn"
    )
    # Espacios múltiples
    texto = re.sub(r"\s+", " ", texto)
    return texto.strip()

# ============================================================
# MAPEO COMPLETO
# ============================================================

MAPEO = {
    # LÁCTEOS
    "leche": "LECHE",
    "leche fresca": "LECHE",
    "queso": "QUESO",
    "queso campesino": "QUESO CAMPESINO",
    "queso doble crema": "QUESO DOBLE CREMA",
    "cuajada": "CUAJADA",
    "mantequilla": "MANTEQUILLA",
    "arequipe": "AREQUIPE",
    "yogurt": "YOGURT",
    "yogur": "YOGURT",
    "yogurth": "YOGURT",

    # HUEVOS
    "huevo": "HUEVO",
    "huevos": "HUEVO",
    "huevos campesinos": "HUEVO",
    "huevos criollos": "HUEVO",
    "huevos organicos bio nutritivos": "HUEVO",
    "huevos de codorniz": "HUEVO DE CODORNIZ",

    # CERDOS
    "cerdo": "CERDO",
    "cerdos": "CERDO",
    "cerdos de engorde": "CERDO",
    "cerdos para consumo": "CERDO",
    "cerdos en pie": "CERDO",
    "cerdo criollo para cria": "CERDO PARA CRÍA",
    "cerdos de cria": "CERDO PARA CRÍA",
    "cerdos para cria": "CERDO PARA CRÍA",
    "produccion de cerdos para crianza o consumo": "CERDO",
    "carne de cerdo": "CARNE DE CERDO",
    "picada de cerdo": "PICADA DE CERDO",

    # POLLO
    "pollo": "POLLO",
    "pollos": "POLLO",
    "pollo de engorde": "POLLO",
    "pollos de engorde": "POLLO",
    "pollo engorde": "POLLO",
    "pollos engorde": "POLLO",
    "pollo campesino": "POLLO CAMPESINO",
    "pollo semicriollo": "POLLO SEMICRIOLLO",
    "pollos semicriollos": "POLLO SEMICRIOLLO",
    "pollo semi criollo para el consumo": "POLLO SEMICRIOLLO",
    "pollos semicriollos para consumo": "POLLO SEMICRIOLLO",
    "pollo en pie": "POLLO EN PIE",
    "pollo completo crudo": "CARNE DE POLLO",
    "carne de pollo": "CARNE DE POLLO",
    "pierna pernil": "CARNE DE POLLO",

    # GALLINAS
    "gallina": "GALLINA",
    "gallinas tunalunas": "GALLINA",
    "gallos y gallinas finas": "GALLINA",
    "gallina cocida": "GALLINA COCIDA",

    # GANADO / RES
    "ganado": "GANADO BOVINO",
    "ganado bovino": "GANADO BOVINO",
    "ganado en pie": "GANADO BOVINO",
    "animal en pie": "ANIMAL EN PIE",
    "novillos": "NOVILLO",
    "novillas": "NOVILLA",
    "terneras": "TERNERA",
    "carne de res": "CARNE DE RES",
    "carne res": "CARNE DE RES",
    "carnes ganado": "CARNE DE RES",

    # CONEJOS
    "conejo": "CONEJO",
    "conejos": "CONEJO",
    "carne de conejo": "CARNE DE CONEJO",

    # CUY
    "cuy": "CUY",

    # CABRO
    "cabro": "CABRO",

    # OVINOS
    "carne ovino": "CARNE DE OVINO",
    "cria de oveja": "OVEJA",

    # TRUCHA
    "trucha": "TRUCHA",
    "truchas susa": "TRUCHA",

    # PAPA
    "papa": "PAPA",
    "papa nativa": "PAPA NATIVA",
    "papa pastusa nativa": "PAPA NATIVA",
    "papa criolla abejona": "PAPA CRIOLLA",
    "papa criolla manzana": "PAPA CRIOLLA",
    "papa criolla nativa (corneta)": "PAPA CRIOLLA",
    "papa rellena": "PAPA RELLENA",

    # FRUTAS
    "fresa": "FRESA",
    "arandano": "ARÁNDANO",
    "arandanos": "ARÁNDANO",
    "mora": "MORA",
    "uchua": "UCHUVA",
    "papayuela": "PAPAYUELA",

    # HORTALIZAS / TUBÉRCULOS
    "arveja": "ARVEJA",
    "zanahoria": "ZANAHORIA",
    "calabaza": "CALABAZA",
    "cubios": "CUBIOS",
    "chuguas": "CHUGUAS",
    "perejil": "PEREJIL",
    "legumbres": "LEGUMBRES",

    # AROMÁTICAS / MEDICINALES
    "aromaticas": "AROMÁTICAS",
    "calendula": "CALÉNDULA",
    "ruda": "RUDA",
    "savila": "SÁBILA",

    # APICULTURA
    "miel": "MIEL",
    "polen": "POLEN",

    # PANADERÍA
    "pan": "PAN",
    "mantecada": "MANTECADA",
    "mantecadas": "MANTECADA",
    "bizcocho tradicional": "BIZCOCHO",
    "cupcakes": "CUPCAKE",
    "pasteles": "PASTEL",
    "envueltos": "ENVUELTO",
    "panes -almojabanas": "PAN / ALMOJÁBANA",

    # COMIDAS
    "empanadas": "EMPANADA",
    "hamburguesa": "HAMBURGUESA",
    "hamburgesa": "HAMBURGUESA",
    "hamburguesas": "HAMBURGUESA",
    "perro caliente": "PERRO CALIENTE",
    "perros calientes": "PERRO CALIENTE",
    "salchipapa": "SALCHIPAPA",
    "mazorcada": "MAZORCADA",
    "pataconada": "PATACONADA",
    "rellena": "RELLENA",
    "chorizos": "CHORIZO",
    "arepa": "AREPA",
    "arepa rellena": "AREPA RELLENA",
    "arepa con chorizo": "AREPA CON CHORIZO",
    "chorizo con arepa": "AREPA CON CHORIZO",
    "chorizos con arepa": "AREPA CON CHORIZO",
    "choriperro": "CHORIPERRO",
    "tamales": "TAMAL",

    # RESTAURANTE
    "desayuno": "DESAYUNO",
    "desayunos": "DESAYUNO",
    "almuerzo": "ALMUERZO",
    "almuerzos": "ALMUERZO",
    "almuezos": "ALMUERZO",
    "cenas": "CENA",
    "refrigerios": "REFRIGERIO",
    "comida rapida": "COMIDA RÁPIDA",

    # POSTRES
    "postres": "POSTRE",
    "merengon": "MERENGÓN",
    "obleas": "OBLEA",
    "helados": "HELADO",
    "fresas con crema": "FRESAS CON CREMA",
    "fresas con chocolate": "FRESAS CON CHOCOLATE",

    # BEBIDAS
    "gaseosa": "GASEOSA",
    "sabajon": "SABAJÓN",

    # ARTESANÍAS
    "artesanias": "ARTESANÍAS",
    "manillas": "MANILLAS",
    "ruanas": "RUANA",
    "porta sombreros": "PORTA SOMBREROS",

    # ROPA
    "jeans": "JEANS",
    "chaquetas": "CHAQUETA",
    "ropa de cama": "ROPA DE CAMA",
    "ropa interior": "ROPA INTERIOR",
    "calzado": "CALZADO",
    "botas": "BOTAS",

    # OTROS
    "jabon": "JABÓN",
    "jabon de loza": "JABÓN DE LOZA",
    "pomadas": "POMADA",
    "mesas de madera": "MUEBLES DE MADERA",
    "productos de ferreteria": "FERRETERÍA",
    "forraje verde hidroponico": "FORRAJE VERDE HIDROPÓNICO",
    "montallantas": "MONTALLANTAS",
    "montallantas, despichada": "MONTALLANTAS",
    "hospedaje": "HOSPEDAJE",
    "habitaciones": "HOSPEDAJE",
    "hotel paramo de sumapaz": "HOSPEDAJE",
    "servicio de mantenimiento": "SERVICIO DE MANTENIMIENTO",
    "ornamentacion en general": "ORNAMENTACIÓN",
    "costura": "COSTURA",
    "material reutilizable": "MATERIAL REUTILIZABLE",
}

def ejecutar_normalizacion():
    inicio = time.time()
    print("\nLeyendo productos diferentes...")

    cursor.execute("""
        SELECT DISTINCT nombre
        FROM productor_productos
        WHERE nombre IS NOT NULL
          AND TRIM(nombre) <> ''
    """)

    nombres_bd = cursor.fetchall()
    print(f"Nombres diferentes encontrados: {len(nombres_bd)}")

    mapeo_bd = {}
    sin_regla = []

    for fila in nombres_bd:
        original = fila["nombre"]
        limpio = limpiar(original)
        normalizado = MAPEO.get(limpio)
        if normalizado:
            mapeo_bd[original] = normalizado
        else:
            sin_regla.append(original)

    print(f"Con regla: {len(mapeo_bd)}")
    print(f"Sin regla: {len(sin_regla)}")

    if mapeo_bd:
        print("\nEjecutando UPDATE único en lote...")

        partes_case = []
        parametros = []

        for original, normalizado in mapeo_bd.items():
            partes_case.append("WHEN %s THEN %s")
            parametros.append(original)
            parametros.append(normalizado)

        case_sql = "\n".join(partes_case)

        nombres_actualizables = list(mapeo_bd.keys())
        placeholders = ",".join(["%s"] * len(nombres_actualizables))

        query = f"""
            UPDATE productor_productos
            SET producto_normal =
                CASE nombre
                    {case_sql}
                    ELSE producto_normal
                END
            WHERE nombre IN ({placeholders})
        """

        parametros.extend(nombres_actualizables)

        try:
            tiempo_update = time.time()
            cursor.execute(query, tuple(parametros))
            afectados = cursor.rowcount
            conn.commit()
            segundos_update = time.time() - tiempo_update

            print("\n" + "=" * 80)
            print("NORMALIZACIÓN TERMINADA")
            print("=" * 80)
            print(f"Registros afectados: {afectados}")
            print(f"Tiempo UPDATE: {segundos_update:.2f} segundos")
        except Exception as e:
            conn.rollback()
            print("\nERROR DURANTE LA ACTUALIZACIÓN")
            print("=" * 80)
            print(e)
            print("\nSe realizó ROLLBACK.")
    else:
        print("\nNo hay productos para normalizar.")

    tiempo_total = time.time() - inicio
    print(f"\nTiempo total de ejecución: {tiempo_total:.2f} segundos")

    cursor.close()
    conn.close()
    print("Conexión cerrada.")

if __name__ == "__main__":
    ejecutar_normalizacion()
