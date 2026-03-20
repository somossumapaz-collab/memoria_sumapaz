<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$input = json_encode(['username' => 'admin', 'password' => 'admin2026*']);

// We need to override php://input for login.php. 
// A simpler way is to test the DB fetch and verify directly.
require 'api/db_config.php';

$login_id = 'admin';
$pass = 'admin2026*';

$stmt = $pdo->prepare("
    SELECT u.id, u.nombre, u.email, u.password, r.nombre as rol_nombre 
    FROM usuarios u 
    LEFT JOIN roles r ON u.rol_id = r.id 
    WHERE u.email = :login OR u.nombre = :login
");
$stmt->execute(['login' => $login_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

var_dump($user);

if ($user) {
    echo "DB Password Hash:\n";
    var_dump($user['password']);
    
    $db_password = $user['password'];
    if (strpos($db_password, '$2b$') === 0) {
        $db_password = '$2y$' . substr($db_password, 4);
    }
    
    echo "Verifying...\n";
    var_dump(password_verify($pass, $db_password));
} else {
    echo "User not found\n";
}
?>
