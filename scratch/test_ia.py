import urllib.request
import json

url = "http://localhost:8000/api/analyze_transcript.php"
mock_transcript = """
Entrevistador: Buenos días, ¿cuál es el nombre de su finca y organización?
Productor: Hola, mi finca se llama El Naranjal y mi asociación es Asociación de Productores Orgánicos de Sumapaz. Nos dedicamos a la actividad agrícola, principalmente cultivamos papa criolla y cebolla larga. Estamos en la vereda San Juan.
Entrevistador: ¿Cuál es su visión para el negocio?
Productor: Queremos ser el principal proveedor de hortalizas orgánicas en Sumapaz en 5 años. Nuestra misión es producir alimentos limpios cuidando la tierra. Valoramos la honestidad, la solidaridad y el respeto al medio ambiente.
Entrevistador: ¿Cuáles son sus fortalezas y debilidades?
Productor: Nuestra fortaleza es que tenemos agua pura de nacimiento y tierra fértil. La debilidad es la falta de transporte propio y el costo de los empaques. La helada es una gran amenaza climática.
Entrevistador: ¿A quién le vende y con qué frecuencia?
Productor: Le vendemos papa criolla directo al consumidor final de forma semanal, nos pagan en efectivo. También le vendemos a una cooperativa de la vereda de manera quincenal.
"""

payload = {
    "transcript": mock_transcript
}

req = urllib.request.Request(
    url,
    data=json.dumps(payload).encode('utf-8'),
    headers={'Content-Type': 'application/json'},
    method='POST'
)

try:
    with urllib.request.urlopen(req) as response:
        status_code = response.getcode()
        body = response.read().decode('utf-8')
        print(f"Status Code: {status_code}")
        print("Response Body:")
        data = json.loads(body)
        print(json.dumps(data, indent=2, ensure_ascii=False))
except Exception as e:
    print(f"Error occurred: {e}")
