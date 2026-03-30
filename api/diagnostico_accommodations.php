<?php
/**
 * Script de diagnóstico para ver la estructura de la tabla accommodations
 */

require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = getDBConnection();
    
    echo "<h1>Diagnóstico de Tabla Accommodations</h1>";
    
    // Ver columnas de accommodations
    echo "<h2>Columnas en 'accommodations':</h2>";
    $columns = $pdo->query("SHOW COLUMNS FROM accommodations")->fetchAll();
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Ver algunos registros de ejemplo
    echo "<h2>Registros de Ejemplo (primeros 5):</h2>";
    $stmt = $pdo->query("SELECT * FROM accommodations LIMIT 5");
    $rows = $stmt->fetchAll();
    
    if (count($rows) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; font-size: 0.9em;'>";
        // Encabezados
        echo "<tr>";
        foreach (array_keys($rows[0]) as $header) {
            if (!is_numeric($header)) {
                echo "<th>$header</th>";
            }
        }
        echo "</tr>";
        
        // Datos
        foreach ($rows as $row) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                if (!is_numeric($key)) {
                    echo "<td>" . htmlspecialchars(substr($value, 0, 50)) . "</td>";
                }
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No hay registros en la tabla.</p>";
    }
    
    // Contar total
    $total = $pdo->query("SELECT COUNT(*) FROM accommodations")->fetchColumn();
    echo "<h3>Total de alojamientos: $total</h3>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
