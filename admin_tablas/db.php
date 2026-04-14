<?php
$host = 'localhost'; // Normalmente en Hostinger es localhost
$db   = 'u412199647_Rutas'; // Mira esto en tu panel MySQL de Hostinger
$user = 'u412199647_olgamarin';   // Mira esto en tu panel MySQL de Hostinger
$pass = 'Rutas5Rurales7$';     // La contraseña que creaste para la BD

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

function getDBConnection() {
    global $pdo;
    return $pdo;
}
?>
