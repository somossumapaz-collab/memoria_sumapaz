<?php
$ch = curl_init('http://localhost/memoria_sumapaz/api/login.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['username' => 'admin', 'password' => 'admin2026*']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);
echo "Response:\n";
echo $response;
?>
