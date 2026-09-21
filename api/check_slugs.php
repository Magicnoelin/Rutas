<?php
header('Content-Type: text/plain; charset=utf-8');

$host = 'localhost';
$db   = 'u412199647_Rutas';
$user = 'u412199647_olgamarin';
$pass = 'Rutas5Rurales7$';

$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);

// Buscar el lugar por nombre
$stmt = $pdo->query("SELECT id, name, slug FROM places_of_interest WHERE name LIKE '%Armejun%' OR name LIKE '%Armejún%'");
$lugar = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Lugar encontrado:\n";
print_r($lugar);

if ($lugar) {
    echo "\n\nTraducciones:\n";
    $stmt2 = $pdo->prepare("SELECT * FROM places_of_interest_trads WHERE place_id = ?");
    $stmt2->execute([$lugar['id']]);
    while ($t = $stmt2->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$t['language_code']}: {$t['slug']}\n";
    }
}
