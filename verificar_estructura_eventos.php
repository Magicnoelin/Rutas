<?php
/**
 * Verificar estructura de la tabla cultural_events
 */

require_once 'api/config.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = getDBConnection();
    
    echo "<h1>🔍 Verificar Estructura de cultural_events</h1>";
    echo "<hr>";
    
    // Obtener estructura de la tabla
    $stmt = $pdo->query("DESCRIBE cultural_events");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Columnas de la tabla cultural_events:</h2>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #007bff; color: white;'>";
    echo "<th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th>";
    echo "</tr>";
    
    $hasEventDate = false;
    $hasStartDate = false;
    $hasEndDate = false;
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
        echo "</tr>";
        
        if ($col['Field'] === 'event_date') $hasEventDate = true;
        if ($col['Field'] === 'start_date') $hasStartDate = true;
        if ($col['Field'] === 'end_date') $hasEndDate = true;
    }
    
    echo "</table>";
    
    echo "<h2>Análisis:</h2>";
    echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>event_date:</strong> " . ($hasEventDate ? "✅ Existe" : "❌ No existe") . "<br>";
    echo "<strong>start_date:</strong> " . ($hasStartDate ? "✅ Existe" : "❌ No existe") . "<br>";
    echo "<strong>end_date:</strong> " . ($hasEndDate ? "✅ Existe" : "❌ No existe") . "<br>";
    echo "</div>";
    
    if (!$hasEventDate && $hasStartDate) {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "⚠️ <strong>PROBLEMA DETECTADO:</strong> La tabla usa <code>start_date</code> en lugar de <code>event_date</code>.<br>";
        echo "Necesitamos actualizar el código de la API para usar <code>start_date</code>.";
        echo "</div>";
    }
    
    // Mostrar algunos eventos de ejemplo
    echo "<h2>Eventos de ejemplo (primeros 5):</h2>";
    $stmt = $pdo->query("SELECT * FROM cultural_events LIMIT 5");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($events) {
        echo "<pre style='background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
        print_r($events);
        echo "</pre>";
    } else {
        echo "<p>No hay eventos en la tabla.</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 1200px;
        margin: 20px auto;
        padding: 20px;
        background: #f5f5f5;
    }
    h1, h2 {
        color: #333;
    }
    hr {
        margin: 20px 0;
        border: none;
        border-top: 2px solid #ddd;
    }
</style>
