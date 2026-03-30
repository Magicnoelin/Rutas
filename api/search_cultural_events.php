<?php
header('Content-Type: application/json; charset=utf-8');

// --- CONFIGURACIÓN DE CONEXIÓN ---
$host = "localhost";
$db   = "u412199647_Rutas";
$user = "u412199647_olgamarin";
$pass = "Rutas5Rurales7$";
$charset = 'utf8mb4';      

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    
    // --- PRUEBA 1: ¿Existen eventos en la tabla? ---
    $check = $pdo->query("SELECT COUNT(*) FROM cultural_events")->fetchColumn();
    
    // --- PRUEBA 2: Ver los 3 primeros nombres reales para comparar ---
    $samples = $pdo->query("SELECT name, is_active FROM cultural_events LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

    // --- PRUEBA 3: La búsqueda que está fallando ---
    $query = isset($_GET['query']) ? $_GET['query'] : '';
    $sql = "SELECT id, name, slug FROM cultural_events WHERE name LIKE :t OR slug LIKE :t";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['t' => "%$query%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'debug_info' => [
            'total_eventos_en_tabla' => $check,
            'ejemplos_encontrados' => $samples,
            'lo_que_buscaste' => $query
        ],
        'success' => true,
        'results' => $results
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}