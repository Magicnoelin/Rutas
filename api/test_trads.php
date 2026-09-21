<?php
header('Content-Type: application/json');

$host = 'localhost';
$db   = 'u412199647_Rutas';
$user = 'u412199647_olgamarin';
$pass = 'Rutas5Rurales7$';

$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);

// Buscar lugares con slug que contengan 'armejun'
$stmt = $pdo->query("SELECT id, name, slug FROM places_of_interest WHERE slug LIKE '%armejun%'");
$lugares = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'lugares_encontrados' => count($lugares),
    'lugares' => $lugares
], JSON_PRETTY_PRINT);

if (count($lugares) > 0) {
    $id = $lugares[0]['id'];
    
    // Buscar traducciones
    $stmt2 = $pdo->prepare("SELECT language_code, slug, name FROM places_of_interest_trads WHERE place_id = ?");
    $stmt2->execute([$id]);
    $trads = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n\nTraducciones:\n";
    echo json_encode($trads, JSON_PRETTY_PRINT);
}
