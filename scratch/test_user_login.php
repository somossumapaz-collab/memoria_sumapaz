<?php
require_once __DIR__ . '/../api/db_config.php';

$login_id = 'ambiental_general';
$pass = 'ambiental2026*';

$stmt = $pdo->prepare("SELECT id, nombre, email, password, rol_id FROM usuarios WHERE email = :email OR nombre = :nombre");
$stmt->execute(['email' => $login_id, 'nombre' => $login_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $db_password = $user['password'];
    if (strpos($db_password, '$2b$') === 0) {
        $db_password = '$2y$' . substr($db_password, 4);
    }
    $verified = password_verify($pass, $db_password);
    echo "ID: " . $user['id'] . "\n";
    echo "Nombre: " . $user['nombre'] . "\n";
    echo "Email: " . $user['email'] . "\n";
    echo "Rol ID: " . $user['rol_id'] . "\n";
    echo "Password Verify: " . ($verified ? "OK (Exito)" : "Fallo") . "\n";
} else {
    echo "Usuario no encontrado.\n";
}
?>
