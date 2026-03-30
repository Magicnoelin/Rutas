<?php
header('Content-Type: text/html; charset=utf-8');
require_once 'api/config.php';

echo "<h2>Prueba Directa de API - Alojamientos en Soria</h2>";

try {
    $pdo = getDBConnection();
    
    // 1. Verificar el nombre exacto de la provincia en la BD
    echo "<h3>1. Verificar nombres de provincia únicos en la BD:</h3>";
    $stmt = $pdo->query("SELECT DISTINCT province, COUNT(*) as count FROM accommodations WHERE is_active = 1 GROUP BY province ORDER BY province");
    $provincias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr style='background: #4CAF50; color: white;'><th>Provincia (exacto)</th><th>Cantidad</th><th>Hex</th></tr>";
    
    foreach($provincias as $p) {
        $provinceName = $p['province'];
        $hex = bin2hex($provinceName);
        $bgColor = (strtolower($provinceName) === 'soria' || $provinceName === 'Soria') ? '#ffeb3b' : '#fff';
        
        echo "<tr style='background: $bgColor;'>";
        echo "<td><strong>'{$provinceName}'</strong></td>";
        echo "<td>{$p['count']}</td>";
        echo "<td style='font-family: monospace; font-size: 11px;'>$hex</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Buscar TODOS los alojamientos que contengan "Soria" (case insensitive)
    echo "<h3>2. Buscar alojamientos con 'Soria' en province (case insensitive):</h3>";
    $stmt = $pdo->query("SELECT id, name, province, municipality, is_active FROM accommodations WHERE LOWER(province) LIKE '%soria%' ORDER BY province, name");
    $soriaResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Encontrados: " . count($soriaResults) . " alojamientos</strong></p>";
    
    if (count($soriaResults) > 0) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr style='background: #4CAF50; color: white;'><th>ID</th><th>Nombre</th><th>Provincia (exacto)</th><th>Municipio</th><th>Activo</th></tr>";
        
        foreach($soriaResults as $acc) {
            $bgColor = $acc['is_active'] == 1 ? '#c8e6c9' : '#ffcdd2';
            $estado = $acc['is_active'] == 1 ? '✓' : '✗';
            
            echo "<tr style='background: $bgColor;'>";
            echo "<td>{$acc['id']}</td>";
            echo "<td>{$acc['name']}</td>";
            echo "<td><code>'{$acc['province']}'</code></td>";
            echo "<td>{$acc['municipality']}</td>";
            echo "<td>$estado</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 3. Probar la consulta exacta que usa la API
    echo "<h3>3. Simular consulta de la API (con filtro 'Soria'):</h3>";
    
    $page = 1;
    $limit = 20;
    $offset = 0;
    
    // Probar con diferentes variaciones
    $variaciones = ['Soria', 'soria', 'SORIA'];
    
    foreach ($variaciones as $provincia) {
        echo "<h4>Probando con provincia = '$provincia':</h4>";
        
        $sql = "SELECT * FROM accommodations WHERE is_active = 1 AND province = :provincia ORDER BY name ASC LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':provincia', $provincia);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Resultados: " . count($results) . " alojamientos</strong></p>";
        
        if (count($results) > 0) {
            echo "<ul>";
            foreach($results as $r) {
                echo "<li>ID {$r['id']}: {$r['name']} - {$r['municipality']}</li>";
            }
            echo "</ul>";
        }
    }
    
    // 4. Probar la API real con file_get_contents
    echo "<hr><h3>4. Probar API real (GET request):</h3>";
    
    $apiUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/rutas_public/api/alojamientos.php?table=accommodations&page=1&limit=20&provincia=Soria';
    echo "<p>URL: <code>$apiUrl</code></p>";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Accept: application/json'
        ]
    ]);
    
    $apiResponse = @file_get_contents($apiUrl, false, $context);
    
    if ($apiResponse) {
        $apiData = json_decode($apiResponse, true);
        
        echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px; max-height: 400px; overflow-y: auto;'>";
        echo htmlspecialchars(json_encode($apiData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "</pre>";
        
        if (isset($apiData['data']['alojamientos'])) {
            echo "<p><strong>La API devolvió: " . count($apiData['data']['alojamientos']) . " alojamientos</strong></p>";
            echo "<p><strong>Total en BD según API: " . ($apiData['data']['pagination']['total_records'] ?? 'N/A') . "</strong></p>";
        }
    } else {
        echo "<p style='color: red;'>No se pudo acceder a la API</p>";
    }
    
    // 5. Ver si hay algún problema con caracteres especiales
    echo "<hr><h3>5. Análisis de caracteres en 'province':</h3>";
    $stmt = $pdo->query("SELECT province, LENGTH(province) as longitud, CHAR_LENGTH(province) as caracteres FROM accommodations WHERE LOWER(province) LIKE '%soria%' GROUP BY province");
    $analysis = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr style='background: #4CAF50; color: white;'><th>Provincia</th><th>Bytes</th><th>Caracteres</th><th>Problema?</th></tr>";
    
    foreach($analysis as $a) {
        $problema = ($a['longitud'] != $a['caracteres'] || trim($a['province']) !== $a['province']) ? '⚠️ SÍ' : '✓ NO';
        $bgColor = $problema === '✓ NO' ? '#c8e6c9' : '#ffcdd2';
        
        echo "<tr style='background: $bgColor;'>";
        echo "<td><code>'{$a['province']}'</code></td>";
        echo "<td>{$a['longitud']}</td>";
        echo "<td>{$a['caracteres']}</td>";
        echo "<td><strong>$problema</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error: " . $e->getMessage() . "</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
