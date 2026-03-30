<?php
header('Content-Type: text/html; charset=utf-8');
require_once 'api/config.php';

echo "<h2>Diagnóstico de Alojamientos en Soria</h2>";

try {
    $pdo = getDBConnection();
    
    // 1. Contar TODOS los alojamientos en Soria (sin filtro de is_active)
    echo "<h3>1. Total de alojamientos en Soria (sin filtros):</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM accommodations WHERE province = 'Soria'");
    $total = $stmt->fetch()['total'];
    echo "<p><strong>Total en Soria (todos): $total</strong></p>";
    
    // 2. Contar solo los ACTIVOS
    echo "<h3>2. Alojamientos ACTIVOS en Soria:</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM accommodations WHERE province = 'Soria' AND is_active = 1");
    $totalActivos = $stmt->fetch()['total'];
    echo "<p><strong>Total activos en Soria: $totalActivos</strong></p>";
    
    // 3. Mostrar todos los alojamientos de Soria con su estado
    echo "<h3>3. Detalle de todos los alojamientos en Soria:</h3>";
    $stmt = $pdo->query("SELECT id, name, municipality, is_active FROM accommodations WHERE province = 'Soria' ORDER BY name");
    $soriaAccommodations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr style='background: #4CAF50; color: white;'><th>ID</th><th>Nombre</th><th>Municipio</th><th>Estado (is_active)</th></tr>";
    foreach($soriaAccommodations as $acc) {
        $bgColor = $acc['is_active'] == 1 ? '#c8e6c9' : '#ffcdd2';
        $estado = $acc['is_active'] == 1 ? 'ACTIVO ✓' : 'INACTIVO ✗';
        echo "<tr style='background: $bgColor;'>";
        echo "<td>{$acc['id']}</td>";
        echo "<td>{$acc['name']}</td>";
        echo "<td>{$acc['municipality']}</td>";
        echo "<td><strong>$estado</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 4. Probar la API directamente
    echo "<h3>4. Prueba de la API con filtro de Soria:</h3>";
    echo "<p>Probando: <code>api/alojamientos.php?table=accommodations&page=1&limit=20&provincia=Soria</code></p>";
    
    // Simular la llamada a la API
    $page = 1;
    $limit = 20;
    $offset = 0;
    
    $sql = "SELECT * FROM accommodations WHERE is_active = 1 AND province = :provincia ORDER BY name ASC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':provincia', 'Soria');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Resultados de la API: " . count($results) . " alojamientos</strong></p>";
    
    if (count($results) > 0) {
        echo "<ul>";
        foreach($results as $r) {
            echo "<li>{$r['name']} - {$r['municipality']}</li>";
        }
        echo "</ul>";
    }
    
    // 5. Verificar si hay algún problema con campos vacíos
    echo "<h3>5. Verificar campos vacíos o nulos que podrían causar problemas:</h3>";
    $stmt = $pdo->query("SELECT id, name, province, municipality, is_active FROM accommodations WHERE province = 'Soria' AND (name IS NULL OR name = '' OR province IS NULL OR province = '')");
    $problemRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($problemRecords) > 0) {
        echo "<p style='color: red;'><strong>⚠️ Encontrados " . count($problemRecords) . " registros con campos vacíos:</strong></p>";
        echo "<ul>";
        foreach($problemRecords as $pr) {
            echo "<li>ID {$pr['id']}: name='{$pr['name']}', province='{$pr['province']}', municipality='{$pr['municipality']}', is_active={$pr['is_active']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: green;'>✓ No se encontraron registros con campos vacíos</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error: " . $e->getMessage() . "</strong></p>";
}
?>
