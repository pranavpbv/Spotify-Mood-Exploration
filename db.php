<?php
$host     = 'sql312.infinityfree.com'; 
$dbname   = 'if0_41815486_spotify';   // your actual DB name
$username = 'if0_41815486';           // your actual username
$password = 'qyCQ6ZIKOq';    // password you set

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die(json_encode(['error' => 'DB Error: ' . $e->getMessage()]));
}
?>
