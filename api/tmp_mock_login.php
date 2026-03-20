<?php
/**
 * Database Authentication script
 * Uses bcrypt password verification against `usuarios` table
 */
/*session_start();*/
require_once 'db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$inputJSON = '{"username":"admin","password":"admin2026*"}';
$input = json_decode($inputJSON, TRUE);

if (empty($input['username']) || empty($input['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Usuario y contraseña son requeridos.']);
    exit;
}

$login_id = $input['username'];
$pass = $input['password'];

try {
    // Query by email or username (nombre)
    $stmt = $pdo->prepare("
        SELECT id, nombre, email, password, rol_id 
        FROM usuarios 
        WHERE email = :login_email OR nombre = :login_nombre
    ");
    $stmt->execute(['login_email' => $login_id, 'login_nombre' => $login_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $auth_success = false;
    if ($user) {
        $db_password = $user['password'];
        // Many PHP versions don't support Python's $2b$ bcrypt prefix, but support the identical $2y$
        if (strpos($db_password, '$2b$') === 0) {
            $db_password = '$2y$' . substr($db_password, 4);
        }
        $auth_success = password_verify($pass, $db_password);
    }

    if ($auth_success) {
        // Assume rol_id 1 is ADMIN, any other is just standard user
        $rol_nombre = (isset($user['rol_id']) && $user['rol_id'] == 1) ? 'ADMIN' : 'USUARIO';
        
        // Authentication successful
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['nombre'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['rol'] = $rol_nombre;
        
        // For backwards compatibility with older scripts checking is_admin
        if ($rol_nombre === 'ADMIN') {
            $_SESSION['is_admin'] = true;
        }

        echo json_encode([
            'success' => true, 
            'message' => 'Login exitoso', 
            'redirect' => 'productores_registrados.html',
            'rol' => $rol_nombre
        ]);
    } else {
        // Authentication failed
        http_response_code(401);
        echo json_encode(['error' => 'Usuario o contraseña incorrectos.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos de autenticación: ' . $e->getMessage()]);
}
?>