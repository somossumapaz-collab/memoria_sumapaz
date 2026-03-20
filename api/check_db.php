<?php
require_once 'db_config.php';
$stmt = $pdo->query('SELECT tipo_organizacion FROM caracterizacion_productor WHERE productor_id=227');
$res = $stmt->fetch(PDO::FETCH_ASSOC);
file_put_contents('dump3.txt', print_r($res, true));
?>
