<?php
/**
 * Script for creating user ambiental_general
 */
require_once __DIR__ . '/../api/db_config.php';

$nombre = "ambiental_general";
$email = "ambiental@gmail.com";
$password_plano = "ambiental2026*";
$rol_id = 6;

$password_hash_str = password_hash($password_plano, PASSWORD_BCRYPT, ['cost' => 12]);

try {
    $stmt = $pdo->prepare("
        INSERT INTO usuarios (nombre, email, password, rol_id, activo)
        VALUES (:nombre, :email, :password, :rol_id, 1)
        ON DUPLICATE KEY UPDATE 
            nombre = VALUES(nombre),
            password = VALUES(password),
            rol_id = VALUES(rol_id),
            activo = 1
    ");

    $stmt->execute([
        ':nombre' => $nombre,
        ':email' => $email,
        ':password' => $password_hash_str,
        ':rol_id' => $rol_id
    ]);

    echo "✅ Usuario '$nombre' ($email) creado/actualizado correctamente en la base de datos local/proyecto.\n";

    $stmtCheck = $pdo->prepare("SELECT id, nombre, email, rol_id, activo FROM usuarios WHERE email = :email OR nombre = :nombre");
    $stmtCheck->execute([':email' => $email, ':nombre' => $nombre]);
    $users = $stmtCheck->fetchAll(PDO::FETCH_ASSOC);

    echo "📋 Listado de usuarios encontrados:\n";
    print_r($users);

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
