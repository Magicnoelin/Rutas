<?php
/**
 * Script de prueba para verificar si una actividad existe en la base de datos
 */

// Configuración de base de datos (usando las mismas credenciales que en redirect_manager.php)
$dbHost = 'localhost';
$dbName = 'u412199647_Rutas';
$dbUser = 'u412199647_olgamarin';
$dbPass = 'Rutas5Rurales7$';

try {
    $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Prueba de actividad: kayak-cuerda-del-pozo</h1>";
    
    // Probar si la actividad existe
    $slug = 'kayak-cuerda-del-pozo';
    $stmt = $pdo->prepare("SELECT * FROM tourist_activities WHERE slug = :slug LIMIT 1");
    $stmt->execute([':slug' => $slug]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($activity) {
        echo "<h2 style='color: green;'>✓ Actividad encontrada</h2>";
        echo "<pre>" . print_r($activity, true) . "</pre>";
        
        // Verificar campos importantes
        echo "<h3>Campos importantes:</h3>";
        echo "<ul>";
        echo "<li>Nombre: " . ($activity['name'] ?? 'NO DEFINIDO') . "</li>";
        echo "<li>Slug: " . ($activity['slug'] ?? 'NO DEFINIDO') . "</li>";
        echo "<li>Descripción: " . (strlen($activity['description'] ?? '') > 0 ? 'PRESENTE' : 'VACÍA') . "</li>";
        echo "<li>Estado activo: " . ($activity['is_active'] ?? 'NO DEFINIDO') . "</li>";
        echo "<li>Estado moderación: " . ($activity['moderation_status'] ?? 'NO DEFINIDO') . "</li>";
        echo "</ul>";
    } else {
        echo "<h2 style='color: red;'>✗ Actividad NO encontrada</h2>";
        
        // Listar algunas actividades existentes para referencia
        echo "<h3>Algunas actividades existentes:</h3>";
        $stmtAll = $pdo->query("SELECT slug, name FROM tourist_activities WHERE is_active = 1 LIMIT 10");
        $activities = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
        
        if ($activities) {
            echo "<ul>";
            foreach ($activities as $act) {
                echo "<li>" . htmlspecialchars($act['name']) . " (slug: " . htmlspecialchars($act['slug']) . ")</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>No hay actividades activas en la base de datos.</p>";
        }
    }
    
    // Probar también con micologia-picos-urbion
    echo "<hr><h1>Prueba de actividad: micologia-picos-urbion</h1>";
    $slug2 = 'micologia-picos-urbion';
    $stmt2 = $pdo->prepare("SELECT * FROM tourist_activities WHERE slug = :slug LIMIT 1");
    $stmt2->execute([':slug' => $slug2]);
    $activity2 = $stmt2->fetch(PDO::FETCH_ASSOC);
    
    if ($activity2) {
        echo "<h2 style='color: green;'>✓ Actividad encontrada</h2>";
        echo "<p>Nombre: " . htmlspecialchars($activity2['name'] ?? '') . "</p>";
    } else {
        echo "<h2 style='color: red;'>✗ Actividad NO encontrada</h2>";
    }
    
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>Error de conexión a la base de datos:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Verifica las credenciales en redirect_manager.php</p>";
}
?>