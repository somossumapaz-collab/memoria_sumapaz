<?php
require_once 'db_config.php';
$stmt = $pdo->prepare("SELECT email, password FROM usuarios WHERE email = 'sotocollazos99@gmail.com'");
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found\n";
    exit;
}

echo "DB Hash: " . $user['password'] . "\n";
echo "Length: " . strlen($user['password']) . "\n";

$testPass = 'admin2026*';
if (password_verify($testPass, $user['password'])) {
    echo "Match OK with password_verify()\n";
} else {
    echo "password_verify() failed\n";
    // Check if it's a prefix issue
    if (strpos($user['password'], '$2b$') === 0) {
        $replaced = '$2y$' . substr($user['password'], 4);
        echo "Replaced Hash: $replaced\n";
        if (password_verify($testPass, $replaced)) {
             echo "Match OK when replacing $2b$ with $2y$\n";
        }
    }
}
?>
