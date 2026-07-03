<?php
/**
 * Enrutador de la API REST para la App de Transporte
 * 
 * Uso desde la App Android:
 * Puedes hacer peticiones a:
 * https://tu-dominio.com/api/transporte_router.php?route=/login
 * o
 * https://tu-dominio.com/api/transporte_router.php/login (Dependiendo de la configuración del servidor web)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Responder OK a las peticiones preflight (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Requerir conexión a la base de datos (se asume que existe db_config.php y define $pdo)
require_once __DIR__ . '/db_config.php';

// Capturar el cuerpo de la petición (JSON)
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// Determinar el endpoint solicitado
$route = isset($_GET['route']) ? $_GET['route'] : '';

// Si no se pasó 'route' en la query, intentamos con PATH_INFO
if (empty($route) && isset($_SERVER['PATH_INFO'])) {
    $route = $_SERVER['PATH_INFO'];
}

$method = $_SERVER['REQUEST_METHOD'];

// Función de respuesta estandarizada
function response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

try {
    switch ($route) {
        
        // ==========================================
        // 1. META LÍDERES
        // ==========================================
        
        case '/login':
            if ($method !== 'POST') response(['error' => 'Method not allowed'], 405);
            $usuario = $input['usuario'] ?? '';
            $contrasena = $input['contrasena'] ?? '';
            
            $stmt = $pdo->prepare("SELECT usuario, nombre, hash_contrasena, debe_cambiar_contrasena, nivel FROM transporte_sumapaz_lideres WHERE usuario = ?");
            $stmt->execute([$usuario]);
            $lider = $stmt->fetch();
            
            // Verificar hash
            if ($lider && password_verify($contrasena, $lider['hash_contrasena'])) {
                unset($lider['hash_contrasena']); // No devolvemos el hash
                response(['success' => true, 'lider' => $lider]);
            } else {
                response(['error' => 'Credenciales inválidas'], 401);
            }
            break;

        case '/lideres/cambiar-contrasena':
            if ($method !== 'POST') response(['error' => 'Method not allowed'], 405);
            $usuario = $input['usuario'] ?? '';
            $nueva_contrasena = $input['nueva_contrasena'] ?? '';
            
            $hash = password_hash($nueva_contrasena, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE transporte_sumapaz_lideres SET hash_contrasena = ?, debe_cambiar_contrasena = 0 WHERE usuario = ?");
            $stmt->execute([$hash, $usuario]);
            
            response(['success' => true, 'message' => 'Contraseña actualizada correctamente']);
            break;

        case '/lideres/crear':
            if ($method !== 'POST') response(['error' => 'Method not allowed'], 405);
            $usuario = $input['usuario'] ?? '';
            $nombre = $input['nombre'] ?? '';
            $contrasena = $input['contrasena'] ?? '';
            $nivel = $input['nivel'] ?? 1;
            
            $hash = password_hash($contrasena, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO transporte_sumapaz_lideres (usuario, nombre, hash_contrasena, debe_cambiar_contrasena, nivel) VALUES (?, ?, ?, 1, ?)");
            $stmt->execute([$usuario, $nombre, $hash, $nivel]);
            
            response(['success' => true, 'message' => 'Líder creado exitosamente']);
            break;

        // ==========================================
        // 2. PARTICIPANTES
        // ==========================================

        case '/participantes':
            if ($method !== 'GET') response(['error' => 'Method not allowed'], 405);
            $stmt = $pdo->query("SELECT * FROM transporte_sumapaz_participantes");
            $participantes = $stmt->fetchAll();
            response(['success' => true, 'participantes' => $participantes]);
            break;

        case '/participantes/crear':
            if ($method !== 'POST') response(['error' => 'Method not allowed'], 405);
            $numero_documento = $input['numero_documento'] ?? '';
            $tipo_documento = $input['tipo_documento'] ?? '';
            $nombre = $input['nombre'] ?? '';
            $telefono = $input['telefono'] ?? null;
            $correo = $input['correo'] ?? null;
            $numero_proyecto = $input['numero_proyecto'] ?? null;

            $stmt = $pdo->prepare("INSERT INTO transporte_sumapaz_participantes (numero_documento, tipo_documento, nombre, telefono, correo, numero_proyecto) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$numero_documento, $tipo_documento, $nombre, $telefono, $correo, $numero_proyecto]);
            
            response(['success' => true, 'message' => 'Participante registrado exitosamente']);
            break;

        // ==========================================
        // 3. VIAJES PROGRAMADOS Y PASAJEROS
        // ==========================================

        case '/viajes':
            if ($method !== 'GET') response(['error' => 'Method not allowed'], 405);
            $stmt = $pdo->query("SELECT * FROM transporte_sumapaz_viajes");
            $viajes = $stmt->fetchAll();
            
            foreach ($viajes as &$viaje) {
                // Incluir array de pasajeros asociados
                $stmt_pasajeros = $pdo->prepare("SELECT numero_documento_participante FROM transporte_sumapaz_viajes_pasajeros WHERE id_viaje = ?");
                $stmt_pasajeros->execute([$viaje['id']]);
                $viaje['pasajeros'] = $stmt_pasajeros->fetchAll(PDO::FETCH_COLUMN); // Array de cédulas
                
                // Incluir array de registros de asistencia asociados
                $stmt_asistencias = $pdo->prepare("SELECT * FROM transporte_sumapaz_registros_asistencia WHERE id_viaje = ?");
                $stmt_asistencias->execute([$viaje['id']]);
                $viaje['asistencias'] = $stmt_asistencias->fetchAll();
            }
            response(['success' => true, 'viajes' => $viajes]);
            break;

        case '/viajes/crear':
            if ($method !== 'POST') response(['error' => 'Method not allowed'], 405);
            $id = $input['id'] ?? '';
            $fecha_viaje = $input['fecha_viaje'] ?? '';
            $ruta = $input['ruta'] ?? '';
            $estado = $input['estado'] ?? 'PROGRAMADO';
            $programado_por = $input['programado_por'] ?? null;
            $cedulas = $input['cedulas'] ?? []; // Array de cédulas que van en el viaje

            // Usamos transacción para asegurar inserción de viaje y pasajeros atómicamente
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO transporte_sumapaz_viajes (id, fecha_viaje, ruta, estado, programado_por) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id, $fecha_viaje, $ruta, $estado, $programado_por]);
            
            if (!empty($cedulas) && is_array($cedulas)) {
                $stmt_p = $pdo->prepare("INSERT IGNORE INTO transporte_sumapaz_viajes_pasajeros (id_viaje, numero_documento_participante) VALUES (?, ?)");
                foreach ($cedulas as $cedula) {
                    $stmt_p->execute([$id, $cedula]);
                }
            }
            $pdo->commit();
            response(['success' => true, 'message' => 'Viaje programado correctamente']);
            break;

        case '/viajes/agregar-pasajero':
            if ($method !== 'POST') response(['error' => 'Method not allowed'], 405);
            $id_viaje = $input['id_viaje'] ?? '';
            $cedula = $input['cedula'] ?? '';
            
            $stmt = $pdo->prepare("INSERT IGNORE INTO transporte_sumapaz_viajes_pasajeros (id_viaje, numero_documento_participante) VALUES (?, ?)");
            $stmt->execute([$id_viaje, $cedula]);
            
            response(['success' => true, 'message' => 'Pasajero agregado al viaje exitosamente']);
            break;

        // ==========================================
        // 4. ASISTENCIA Y TELEMETRÍA
        // ==========================================

        case '/asistencia/iniciar':
            if ($method !== 'POST') response(['error' => 'Method not allowed'], 405);
            $id_viaje = $input['id_viaje'] ?? '';
            $cedula_pasajero = $input['cedula_pasajero'] ?? '';
            $nombre_conductor = $input['nombre_conductor'] ?? null;
            $placa_vehiculo = $input['placa_vehiculo'] ?? null;
            $tipo_vehiculo = $input['tipo_vehiculo'] ?? null;
            $hora_inicio = $input['hora_inicio'] ?? null;
            $estado = $input['estado'] ?? 'INICIADO';
            $hora_inicio_dispositivo = $input['hora_inicio_dispositivo'] ?? null;
            $coordenadas_inicio = $input['coordenadas_inicio'] ?? null;

            $stmt = $pdo->prepare("INSERT INTO transporte_sumapaz_registros_asistencia 
                (id_viaje, cedula_pasajero, nombre_conductor, placa_vehiculo, tipo_vehiculo, hora_inicio, estado, hora_inicio_dispositivo, coordenadas_inicio) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $id_viaje, $cedula_pasajero, $nombre_conductor, $placa_vehiculo, 
                $tipo_vehiculo, $hora_inicio, $estado, $hora_inicio_dispositivo, $coordenadas_inicio
            ]);
            
            response(['success' => true, 'message' => 'Asistencia iniciada', 'id' => $pdo->lastInsertId()]);
            break;

        case '/asistencia/cerrar':
            if ($method !== 'POST') response(['error' => 'Method not allowed'], 405);
            $id_viaje = $input['id_viaje'] ?? '';
            $cedula_pasajero = $input['cedula_pasajero'] ?? '';
            $estado = $input['estado'] ?? 'CUMPLIDO'; // O NO_CUMPLIDO
            $hora_fin_dispositivo = $input['hora_fin_dispositivo'] ?? null;
            $coordenadas_fin = $input['coordenadas_fin'] ?? null;
            
            $stmt = $pdo->prepare("UPDATE transporte_sumapaz_registros_asistencia 
                SET estado = ?, hora_fin_dispositivo = ?, coordenadas_fin = ? 
                WHERE id_viaje = ? AND cedula_pasajero = ? AND (estado = 'INICIADO' OR estado IS NULL)");
            $stmt->execute([$estado, $hora_fin_dispositivo, $coordenadas_fin, $id_viaje, $cedula_pasajero]);
            
            if ($stmt->rowCount() > 0) {
                response(['success' => true, 'message' => 'Asistencia cerrada correctamente']);
            } else {
                response(['success' => false, 'message' => 'No se encontró un registro abierto para cerrar', 'updated' => 0]);
            }
            break;

        case '/asistencia/eliminar':
            if ($method !== 'POST') response(['error' => 'Method not allowed'], 405);
            $id_viaje = $input['id_viaje'] ?? '';
            $cedula_pasajero = $input['cedula_pasajero'] ?? '';
            
            $stmt = $pdo->prepare("DELETE FROM transporte_sumapaz_registros_asistencia WHERE id_viaje = ? AND cedula_pasajero = ?");
            $stmt->execute([$id_viaje, $cedula_pasajero]);
            
            response(['success' => true, 'message' => 'Asistencia eliminada (estado reseteado a pendiente)']);
            break;

        // ==========================================
        // 5. VIAJES OCASIONALES
        // ==========================================

        case '/viajes-ocasionales/crear':
            if ($method !== 'POST') response(['error' => 'Method not allowed'], 405);
            $id = $input['id'] ?? '';
            $nombre_pasajero = $input['nombre_pasajero'] ?? '';
            $fecha_viaje = $input['fecha_viaje'] ?? '';
            $origen = $input['origen'] ?? '';
            $destino = $input['destino'] ?? '';
            
            $stmt = $pdo->prepare("INSERT INTO transporte_sumapaz_viajes_ocasionales (id, nombre_pasajero, fecha_viaje, origen, destino) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id, $nombre_pasajero, $fecha_viaje, $origen, $destino]);
            
            response(['success' => true, 'message' => 'Viaje ocasional registrado exitosamente']);
            break;

        // ==========================================
        // RUTA NO ENCONTRADA
        // ==========================================
        default:
            response(['error' => 'Endpoint no encontrado', 'route' => $route], 404);
            break;
    }

} catch (PDOException $e) {
    // Revertir transacción si hubo error y estamos en medio de una
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Para producción, tal vez no quieras exponer el mensaje exacto de la DB, 
    // pero para depurar y conectar la App de Android será muy útil.
    response(['error' => 'Error de base de datos: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    response(['error' => 'Error inesperado: ' . $e->getMessage()], 500);
}
