<?php
/**
 * Mobile App Authentication script
 * Uses bcrypt password verification against `usuarios` table
 * and verifies that the user's rol_id in the database is 6
 */
require_once 'db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$inputJSON = file_get_contents('php://input');
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
        WHERE (email = :login_email OR nombre = :login_nombre)
    ");
    $stmt->execute(['login_email' => $login_id, 'login_nombre' => $login_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $auth_success = false;
    
    if ($user) {
        // Checking the password first
        $db_password = $user['password'];
        // Many PHP versions don't support Python's $2b$ bcrypt prefix, but support the identical $2y$
        if (strpos($db_password, '$2b$') === 0) {
            $db_password = '$2y$' . substr($db_password, 4);
        }
        $auth_success = password_verify($pass, $db_password);

        if ($auth_success) {
            // Check if the role is 6
            if ($user['rol_id'] != 6) {
                http_response_code(403);
                echo json_encode(['error' => 'No tienes permitido acceso al aplicativo movil']);
                exit;
            }
            
            // Authentication and Role check successful
            echo json_encode([
                'success' => true, 
                'message' => 'Login exitoso', 
                'user' => [
                    'id' => $user['id'],
                    'nombre' => $user['nombre'],
                    'email' => $user['email'],
                    'rol_id' => $user['rol_id']
                ]
            ]);
        } else {
            // Invalid password
            http_response_code(401);
            echo json_encode(['error' => 'Usuario o contraseña incorrectos.']);
        }
    } else {
        // User not found
        http_response_code(401);
        echo json_encode(['error' => 'Usuario o contraseña incorrectos.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos de autenticación.']);
}
?>
