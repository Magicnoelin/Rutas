<?php
/**
 * Script para activar todos los alojamientos de Soria
 * Esto establecerá is_active = 1 para todos los registros de la provincia de Soria
 */

header('Content-Type: text/html; charset=utf-8');
require_once 'api/config.php';

echo "<h2>Activar Alojamientos en Soria</h2>";

try {
    $pdo = getDBConnection();
    
    // 1. Mostrar estado actual ANTES de la actualización
    echo "<h3>Estado ANTES de activar:</h3>";
    $stmt = $pdo->query("SELECT id, name, municipality, is_active FROM accommodations WHERE province = 'Soria' ORDER BY name");
    $beforeUpdate = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin-bottom: 20px;'>";
    echo "<tr style='background: #4CAF50; color: white;'><th>ID</th><th>Nombre</th><th>Municipio</th><th>Estado Actual</th></tr>";
    
    $countInactivos = 0;
    foreach($beforeUpdate as $acc) {
        $bgColor = $acc['is_active'] == 1 ? '#c8e6c9' : '#ffcdd2';
        $estado = $acc['is_active'] == 1 ? 'ACTIVO ✓' : 'INACTIVO ✗';
        if ($acc['is_active'] == 0) $countInactivos++;
        
        echo "<tr style='background: $bgColor;'>";
        echo "<td>{$acc['id']}</td>";
        echo "<td>{$acc['name']}</td>";
        echo "<td>{$acc['municipality']}</td>";
        echo "<td><strong>$estado</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><strong>Total en Soria: " . count($beforeUpdate) . "</strong></p>";
    echo "<p><strong>Inactivos: $countInactivos</strong></p>";
    echo "<p><strong>Activos: " . (count($beforeUpdate) - $countInactivos) . "</strong></p>";
    
    if ($countInactivos > 0) {
        // 2. Activar todos los alojamientos de Soria
        echo "<hr>";
        echo "<h3>Activando todos los alojamientos de Soria...</h3>";
        
        $updateStmt = $pdo->prepare("UPDATE accommodations SET is_active = 1 WHERE province = 'Soria'");
        $updateStmt->execute();
        $rowsAffected = $updateStmt->rowCount();
        
        echo "<p style='color: green; font-size: 18px;'><strong>✓ Actualización completada: $rowsAffected registros actualizados</strong></p>";
        
        // 3. Mostrar estado DESPUÉS de la actualización
        echo "<h3>Estado DESPUÉS de activar:</h3>";
        $stmt = $pdo->query("SELECT id, name, municipality, is_active FROM accommodations WHERE province = 'Soria' ORDER BY name");
        $afterUpdate = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr style='background: #4CAF50; color: white;'><th>ID</th><th>Nombre</th><th>Municipio</th><th>Estado Nuevo</th></tr>";
        
        $countActivosNow = 0;
        foreach($afterUpdate as $acc) {
            $bgColor = $acc['is_active'] == 1 ? '#c8e6c9' : '#ffcdd2';
            $estado = $acc['is_active'] == 1 ? 'ACTIVO ✓' : 'INACTIVO ✗';
            if ($acc['is_active'] == 1) $countActivosNow++;
            
            echo "<tr style='background: $bgColor;'>";
            echo "<td>{$acc['id']}</td>";
            echo "<td>{$acc['name']}</td>";
            echo "<td>{$acc['municipality']}</td>";
            echo "<td><strong>$estado</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p style='font-size: 18px;'><strong>✓ Todos los alojamientos activos ahora: $countActivosNow</strong></p>";
        
        echo "<hr>";
        echo "<p style='font-size: 16px; color: #4CAF50;'><strong>🎉 ¡Proceso completado! Ahora deberías ver todos los alojamientos de Soria en la página.</strong></p>";
        echo "<p><a href='alojamientos-turisticos-paginacion.html?provincia=Soria' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Ver Alojamientos de Soria</a></p>";
        
    } else {
        echo "<p style='color: #4CAF50; font-size: 18px;'><strong>✓ Todos los alojamientos de Soria ya están activos.</strong></p>";
        echo "<p>No se necesita realizar ninguna actualización.</p>";
        echo "<p><a href='alojamientos-turisticos-paginacion.html?provincia=Soria' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Ver Alojamientos de Soria</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ Error: " . $e->getMessage() . "</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
