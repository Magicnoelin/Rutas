<?php
/**
 * Script de diagnóstico: Verificar tablas de traducciones
 */

$host = 'localhost';
$db   = 'u412199647_Rutas';
$user = 'u412199647_olgamarin';
$pass = 'Rutas5Rurales7$';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== VERIFICACIÓN DE TABLAS DE TRADUCCIONES ===\n\n";
    
    // Ver tablas existentes
    $stmt = $pdo->query("SHOW TABLES LIKE '%trad%'");
    $tablas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tablas con 'trad' en el nombre:\n";
    foreach ($tablas as $tabla) {
        echo "  - $tabla\n";
    }
    
    echo "\n---\n";
    
    // Ver estructura de cultural_events_trads
    echo "\nEstructura de cultural_events_trads:\n";
    $stmt = $pdo->query("DESCRIBE cultural_events_trads");
    $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columnas as $col) {
        echo "  - {$col['Field']}: {$col['Type']}\n";
    }
    
    // Contar registros
    $stmt = $pdo->query("SELECT COUNT(*) FROM cultural_events_trads");
    $count = $stmt->fetchColumn();
    echo "\nRegistros en cultural_events_trads: $count\n";
    
    echo "\n---\n";
    
    // Ver estructura de places_of_interest_trads
    echo "\nEstructura de places_of_interest_trads:\n";
    try {
        $stmt = $pdo->query("DESCRIBE places_of_interest_trads");
        $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columnas as $col) {
            echo "  - {$col['Field']}: {$col['Type']}\n";
        }
        
        // Contar registros
        $stmt = $pdo->query("SELECT COUNT(*) FROM places_of_interest_trads");
        $count = $stmt->fetchColumn();
        echo "\nRegistros en places_of_interest_trads: $count\n";
        
    } catch (Exception $e) {
        echo "  ERROR: La tabla NO existe!\n";
        echo "  Mensaje: " . $e->getMessage() . "\n";
    }
    
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage() . "\n";
}
