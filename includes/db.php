<?php
$host = 'localhost';
$dbname = 'poultry_farm';
$user = 'farmuser';
$pass = 'farm2026';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die('DB Error: ' . $e->getMessage());
}
?>
