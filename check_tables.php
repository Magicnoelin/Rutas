<?php
/**
 * Script para verificar las tablas en la base de datos
 */

require_once 'api/config.php';

try {
    $pdo = getDBConnection();
    
    // Obtener todas las tablas en la base de datos
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Tablas en la base de datos: " . DB_NAME . "</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Verificar si la tabla activities existe
    if (in_array('activities', $tables)) {
        echo "<p style='color: green;'><strong>✓ La tabla 'activities' EXISTE</strong></p>";
        
        // Mostrar estructura de la tabla activities
        $stmt = $pdo->query("DESCRIBE activities");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Estructura de la tabla 'activities':</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>" . $col['Field'] . "</td>";
            echo "<td>" . $col['Type'] . "</td>";
            echo "<td>" . $col['Null'] . "</td>";
            echo "<td>" . $col['Key'] . "</td>";
            echo "<td>" . $col['Default'] . "</td>";
            echo "<td>" . $col['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'><strong>✗ La tabla 'activities' NO EXISTE</strong></p>";
    }
    
    // Verificar otras tablas importantes
    $importantTables = ['places_of_interest', 'content_moderation_history', 'moderation_notifications', 'cultural_events'];
    foreach ($importantTables as $table) {
        if (in_array($table, $tables)) {
            echo "<p style='color: green;'>✓ La tabla '$table' EXISTE</p>";
        } else {
            echo "<p style='color: orange;'>⚠ La tabla '$table' NO EXISTE</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error de conexión: " . $e->getMessage() . "</p>";
}
?>