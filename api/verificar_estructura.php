<?php
/**
 * Script para verificar la estructura actual de la tabla accommodations
 */

require_once 'config.php';

try {
    $pdo = getDBConnection();
    
    echo "<h1>🔍 Verificación de Estructura - Tabla accommodations</h1>";
    
    // Verificar si la tabla existe
    $sqlCheck = "SHOW TABLES LIKE 'accommodations'";
    $stmt = $pdo->query($sqlCheck);
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "<p style='color: red;'>❌ La tabla accommodations NO existe</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✅ La tabla accommodations existe</p>";
    
    // Verificar estructura de columnas
    $sqlDescribe = "DESCRIBE accommodations";
    $columns = $pdo->query($sqlDescribe)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Columnas de la tabla:</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Columna</th><th>Tipo</th><th>Nula</th><th>Clave</th><th>Default</th><th>Extra</th></tr>";
    
    $hasSlug = false;
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
        
        if ($col['Field'] === 'slug') {
            $hasSlug = true;
        }
    }
    echo "</table>";
    
    if (!$hasSlug) {
        echo "<p style='color: orange;'>⚠️ La tabla NO tiene el campo 'slug'</p>";
    } else {
        echo "<p style='color: green;'>✅ La tabla tiene el campo 'slug'</p>";
    }
    
    // Verificar cantidad de alojamientos
    $sqlCount = "SELECT COUNT(*) as total FROM accommodations";
    $stmt = $pdo->query($sqlCount);
    $result = $stmt->fetch();
    echo "<p><strong>Total de alojamientos:</strong> {$result['total']}</p>";
    
    // Verificar alojamientos con slug
    $sqlSlugCount = "SELECT COUNT(*) as total FROM accommodations WHERE slug IS NOT NULL AND slug != ''";
    $stmt = $pdo->query($sqlSlugCount);
    $result = $stmt->fetch();
    echo "<p><strong>Alojamientos con slug:</strong> {$result['total']}</p>";
    
    // Mostrar primeros 10 alojamientos con slug
    $sqlList = "SELECT id, name, slug FROM accommodations WHERE slug IS NOT NULL AND slug != '' LIMIT 10";
    $stmt = $pdo->query($sqlList);
    $alojamientos = $stmt->fetchAll();
    
    if (count($alojamientos) > 0) {
        echo "<h3>Primeros 10 alojamientos con slug:</h3>";
        echo "<ul>";
        foreach ($alojamientos as $aloj) {
            echo "<li>ID: {$aloj['id']} - <strong>{$aloj['name']}</strong> (slug: <code>{$aloj['slug']}</code>)</li>";
        }
        echo "</ul>";
    }
    
    // Verificar si hay alojamientos sin slug
    $sqlNoSlug = "SELECT COUNT(*) as total FROM accommodations WHERE slug IS NULL OR slug = ''";
    $stmt = $pdo->query($sqlNoSlug);
    $result = $stmt->fetch();
    
    if ($result['total'] > 0) {
        echo "<p style='color: orange;'>⚠️ Hay {$result['total']} alojamientos sin slug</p>";
        
        // Mostrar algunos sin slug
        $sqlListNoSlug = "SELECT id, name FROM accommodations WHERE slug IS NULL OR slug = '' LIMIT 5";
        $stmt = $pdo->query($sqlListNoSlug);
        $alojamientosNoSlug = $stmt->fetchAll();
        
        echo "<p><strong>Ejemplos sin slug:</strong></p>";
        echo "<ul>";
        foreach ($alojamientosNoSlug as $aloj) {
            echo "<li>ID: {$aloj['id']} - {$aloj['name']}</li>";
        }
        echo "</ul>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>