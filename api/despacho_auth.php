<?php
/**
 * Guardián estricto de autenticación y autorización para el módulo Despacho.
 * - Verifica sesión real en servidor contra la tabla `usuarios` en base de datos.
 * - NO permite bypass por localhost (127.0.0.1 / ::1).
 * - NO confía en check_auth.php ni en variables enviadas por el cliente.
 * - Autoriza EXCLUSIVAMENTE:
 *     1) El usuario 'despacho' con ID de usuario exactamente 16 (usuarios.id === 16 y nombre === 'despacho').
 *     2) El administrador real del sistema (usuarios.rol_id === 1 activo, excluyendo cuentas restringidas id 7 y 8).
 * - Rechaza explícitamente usuarios.id = 15 (concursos_general) y cualquier cuenta que solo tenga rol_id = 15.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_config.php';

function verifyDespachoSession(PDO $pdo, bool $terminateOnError = true): ?array {
    if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
        if ($terminateOnError) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'authenticated' => false,
                'authorized' => false,
                'message' => 'No autenticado. Debe iniciar sesión para acceder al módulo Despacho.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        return null;
    }

    $sessionUserId = (int)$_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT id, nombre, email, rol_id, activo FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->execute([$sessionUserId]);
    $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dbUser || (int)($dbUser['activo'] ?? 1) !== 1) {
        if ($terminateOnError) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'authenticated' => false,
                'authorized' => false,
                'message' => 'Sesión inválida o usuario inactivo.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        return null;
    }

    $uid = (int)$dbUser['id'];
    $uname = strtolower(trim((string)$dbUser['nombre']));
    $urol = (int)$dbUser['rol_id'];

    // Regla estricta:
    // 1. Usuario despacho autorizado: id === 16 y nombre === 'despacho'
    // 2. Administrador real: rol_id === 1 y no es cuenta restringida (7=revision, 8=starcop)
    // Prohibido explícitamente: id === 15 (concursos_general) o solo tener rol_id === 15
    $isDespachoAccount = ($uid === 16 && $uname === 'despacho');
    $isRealAdmin = ($urol === 1 && !in_array($uid, [7, 8], true));

    if ($uid === 15 || (!$isDespachoAccount && !$isRealAdmin)) {
        if ($terminateOnError) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'authenticated' => true,
                'authorized' => false,
                'message' => 'Acceso denegado. Solo el usuario autorizado de Despacho (ID 16) y el Administrador real pueden consultar o gestionar esta información.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        return null;
    }

    return [
        'id' => $uid,
        'nombre' => $dbUser['nombre'],
        'email' => $dbUser['email'],
        'rol_id' => $urol,
        'is_despacho' => $isDespachoAccount,
        'is_real_admin' => $isRealAdmin,
        'perfil_despacho' => $isRealAdmin ? 'ADMINISTRADOR' : 'DESPACHO'
    ];
}

function despachoLogBitacora(
    PDO $pdo,
    ?int $contratoId,
    string $entidadTipo,
    ?int $entidadId,
    string $accion,
    string $actorTipo,
    ?int $actorId,
    ?string $actorNombre,
    $valorAnterior = null,
    $valorNuevo = null,
    ?string $descripcion = null
): void {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $antJson = $valorAnterior !== null ? (is_string($valorAnterior) ? $valorAnterior : json_encode($valorAnterior, JSON_UNESCAPED_UNICODE)) : null;
        $nueJson = $valorNuevo !== null ? (is_string($valorNuevo) ? $valorNuevo : json_encode($valorNuevo, JSON_UNESCAPED_UNICODE)) : null;

        $descFinal = $descripcion;
        $actNombreFinal = $actorNombre;
        // Compatibilidad: si actorNombre tiene más de 40 caracteres o formato de frase, se mueve a descripción
        if ($descFinal === null && $actorNombre !== null && (strlen($actorNombre) > 40 || strpos($actorNombre, ' ') !== false && strpos($actorNombre, ':') !== false)) {
            $descFinal = $actorNombre;
            $actNombreFinal = 'Sistema / Despacho';
        }
        if ($descFinal === null) {
            $descFinal = $accion;
        }

        $stmt = $pdo->prepare("INSERT INTO despacho_bitacora (
            contrato_id, entidad_tipo, entidad_id, accion, actor_tipo, actor_id, actor_nombre, descripcion, valor_anterior_json, valor_nuevo_json, ip_origen
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $contratoId, $entidadTipo, $entidadId, $accion, $actorTipo, $actorId, $actNombreFinal, $descFinal, $antJson, $nueJson, $ip
        ]);
    } catch (\Throwable $e) {
        // No interrumpir flujo principal si falla registro secundario, pero conservar log de error
        error_log("Error en despachoLogBitacora: " . $e->getMessage());
    }
}
