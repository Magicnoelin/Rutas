<?php
/**
 * Script de diagnóstico para la API get_nearby_content.php
 */

require_once 'api/config.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = getDBConnection();
    
    echo "<h1>🔍 Diagnóstico de API get_nearby_content.php</h1>";
    echo "<hr>";
    
    // 1. Verificar si existe el accommodation_id=50
    echo "<h2>1. Verificar Alojamiento ID=50</h2>";
    $stmt = $pdo->prepare("SELECT id, name, municipality, province FROM accommodations WHERE id = 50");
    $stmt->execute();
    $accommodation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($accommodation) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>✅ Alojamiento encontrado:</strong><br>";
        echo "ID: " . $accommodation['id'] . "<br>";
        echo "Nombre: " . htmlspecialchars($accommodation['name']) . "<br>";
        echo "Municipio: " . htmlspecialchars($accommodation['municipality'] ?? 'NULL') . "<br>";
        echo "Provincia: " . htmlspecialchars($accommodation['province'] ?? 'NULL') . "<br>";
        echo "</div>";
        
        if (empty($accommodation['municipality']) && empty($accommodation['province'])) {
            echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "⚠️ <strong>PROBLEMA:</strong> Este alojamiento no tiene municipality ni province definidos.";
            echo "</div>";
        }
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "❌ <strong>No se encontró el alojamiento con ID=50</strong>";
        echo "</div>";
    }
    
    // 2. Listar algunos alojamientos disponibles
    echo "<h2>2. Alojamientos Disponibles (primeros 10)</h2>";
    $stmt = $pdo->query("SELECT id, name, municipality, province FROM accommodations ORDER BY id LIMIT 10");
    $accommodations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($accommodations) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #007bff; color: white;'>";
        echo "<th>ID</th><th>Nombre</th><th>Municipio</th><th>Provincia</th><th>Acción</th>";
        echo "</tr>";
        
        foreach ($accommodations as $acc) {
            $hasLocation = !empty($acc['municipality']) || !empty($acc['province']);
            $bgColor = $hasLocation ? '#ffffff' : '#fff3cd';
            
            echo "<tr style='background: $bgColor;'>";
            echo "<td>" . $acc['id'] . "</td>";
            echo "<td>" . htmlspecialchars($acc['name']) . "</td>";
            echo "<td>" . htmlspecialchars($acc['municipality'] ?? '<em>vacío</em>') . "</td>";
            echo "<td>" . htmlspecialchars($acc['province'] ?? '<em>vacío</em>') . "</td>";
            echo "<td>";
            if ($hasLocation) {
                echo "<a href='/api/get_nearby_content.php?accommodation_id=" . $acc['id'] . "' target='_blank'>Probar API</a>";
            } else {
                echo "<span style='color: #999;'>Sin ubicación</span>";
            }
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    // 3. Contar alojamientos con y sin ubicación
    echo "<h2>3. Estadísticas de Alojamientos</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM accommodations");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as con_ubicacion FROM accommodations WHERE municipality IS NOT NULL AND municipality != '' OR province IS NOT NULL AND province != ''");
    $conUbicacion = $stmt->fetch(PDO::FETCH_ASSOC)['con_ubicacion'];
    
    $sinUbicacion = $total - $conUbicacion;
    
    echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>Total de alojamientos:</strong> $total<br>";
    echo "<strong>Con ubicación (municipality o province):</strong> $conUbicacion<br>";
    echo "<strong>Sin ubicación:</strong> $sinUbicacion<br>";
    echo "</div>";
    
    // 4. Buscar un alojamiento válido para pruebas
    echo "<h2>4. Alojamiento Recomendado para Pruebas</h2>";
    $stmt = $pdo->query("SELECT id, name, municipality, province FROM accommodations WHERE (municipality IS NOT NULL AND municipality != '') OR (province IS NOT NULL AND province != '') ORDER BY id LIMIT 1");
    $validAccommodation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($validAccommodation) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>✅ Alojamiento válido encontrado:</strong><br>";
        echo "ID: " . $validAccommodation['id'] . "<br>";
        echo "Nombre: " . htmlspecialchars($validAccommodation['name']) . "<br>";
        echo "Municipio: " . htmlspecialchars($validAccommodation['municipality'] ?? 'N/A') . "<br>";
        echo "Provincia: " . htmlspecialchars($validAccommodation['province'] ?? 'N/A') . "<br><br>";
        
        $testUrl = "/api/get_nearby_content.php?accommodation_id=" . $validAccommodation['id'];
        echo "<strong>URL de prueba:</strong><br>";
        echo "<a href='$testUrl' target='_blank' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px;'>";
        echo "🧪 Probar API con ID=" . $validAccommodation['id'];
        echo "</a>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "❌ No se encontraron alojamientos con ubicación definida.";
        echo "</div>";
    }
    
    // 5. Verificar contenido relacionado disponible
    echo "<h2>5. Contenido Relacionado Disponible</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM places_of_interest WHERE is_active = 1");
    $places = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM tourist_activities WHERE is_active = 1");
    $activities = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cultural_events WHERE is_active = 1 AND event_date >= CURDATE()");
    $events = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>Lugares de interés activos:</strong> $places<br>";
    echo "<strong>Actividades turísticas activas:</strong> $activities<br>";
    echo "<strong>Eventos culturales futuros:</strong> $events<br>";
    echo "</div>";
    
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
