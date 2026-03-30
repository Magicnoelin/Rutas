<?php
/**
 * Script para actualizar accommodations.json con TODOS los alojamientos activos de la BD
 * Esto asegura que incluso si el JavaScript usa el fallback, tenga todos los datos
 */
header('Content-Type: text/html; charset=utf-8');

require_once 'api/config.php';

echo "<h1>🔄 Actualizando accommodations.json</h1>";

try {
    $pdo = getDBConnection();
    
    // Obtener TODOS los alojamientos activos
    $stmt = $pdo->query("
        SELECT * FROM accommodations 
        WHERE is_active = 1 
        ORDER BY province ASC, name ASC
    ");
    $alojamientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>✅ Obtenidos " . count($alojamientos) . " alojamientos activos de la BD</strong></p>";
    
    // Contar por provincia
    $porProvincia = [];
    foreach ($alojamientos as $aloj) {
        $prov = $aloj['province'] ?? 'Sin provincia';
        if (!isset($porProvincia[$prov])) {
            $porProvincia[$prov] = 0;
        }
        $porProvincia[$prov]++;
    }
    
    echo "<h3>Distribución por provincia:</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr style='background: #4CAF50; color: white;'><th>Provincia</th><th>Cantidad</th></tr>";
    foreach ($porProvincia as $prov => $count) {
        $bgColor = $prov === 'Soria' ? '#ffeb3b' : '#fff';
        echo "<tr style='background: $bgColor;'><td><strong>$prov</strong></td><td><strong>$count</strong></td></tr>";
    }
    echo "</table>";
    
    // Procesar datos para que sean compatibles con el frontend
    $alojamientosProcesados = array_map(function($aloj) {
        // Convertir formato BD a formato esperado por el frontend
        $processed = [
            'id' => $aloj['id'],
            'nombre' => $aloj['name'],
            'Nombre' => $aloj['name'], // Compatibilidad
            'tipo' => $aloj['accommodation_type'],
            'Tipo' => $aloj['accommodation_type'],
            'provincia' => $aloj['province'],
            'Provincia' => $aloj['province'],
            'localidad' => $aloj['municipality'],
            'Localidad' => $aloj['municipality'],
            'plazas' => intval($aloj['capacity']),
            'Plazas' => intval($aloj['capacity']),
            'descripcion' => $aloj['description'] ?? '',
            'Notaspublicas' => $aloj['description'] ?? '',
            'Direccion' => $aloj['address'] ?? '',
            'Telefono1' => $aloj['phone'] ?? '',
            'Email' => $aloj['email'] ?? '',
            'Web' => $aloj['website'] ?? '',
            'precio' => $aloj['price_per_night'] ? floatval($aloj['price_per_night']) : null,
            'Precio' => $aloj['price_per_night'] ? floatval($aloj['price_per_night']) : null,
            'slug' => $aloj['slug'] ?? ''
        ];
        
        // Procesar fotos
        $fotos = [];
        for ($i = 1; $i <= 4; $i++) {
            $fotoKey = 'photo' . $i;
            if (!empty($aloj[$fotoKey])) {
                $fotoValue = $aloj[$fotoKey];
                
                // Si contiene múltiples URLs separadas por comas
                if (strpos($fotoValue, ',') !== false) {
                    $fotoUrls = array_map('trim', explode(',', $fotoValue));
                    foreach ($fotoUrls as $fotoUrl) {
                        if (!empty($fotoUrl)) {
                            // Si no es URL completa, construirla
                            if (!preg_match('/^https?:\/\//', $fotoUrl)) {
                                $fotoUrl = 'https://rutasrurales.io/Alojamientos_Images/' . $fotoUrl;
                            }
                            $fotos[] = $fotoUrl;
                        }
                    }
                } else {
                    // URL única
                    if (!preg_match('/^https?:\/\//', $fotoValue)) {
                        $fotoValue = 'https://rutasrurales.io/Alojamientos_Images/' . $fotoValue;
                    }
                    $fotos[] = $fotoValue;
                }
            }
        }
        
        $processed['fotos'] = $fotos;
        $processed['Fotos'] = $fotos;
        
        return $processed;
    }, $alojamientos);
    
    // Guardar en accommodations.json
    $jsonData = json_encode($alojamientosProcesados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    $result = file_put_contents('accommodations.json', $jsonData);
    
    if ($result !== false) {
        echo "<p style='background: #c8e6c9; padding: 15px; border-radius: 5px; font-size: 1.2em;'>";
        echo "✅ <strong>¡ÉXITO!</strong> El archivo accommodations.json ha sido actualizado con " . count($alojamientosProcesados) . " alojamientos";
        echo "</p>";
        
        echo "<h3>Próximos pasos:</h3>";
        echo "<ol>";
        echo "<li><strong>Limpia la caché del navegador</strong> (Ctrl + Shift + Del)</li>";
        echo "<li><strong>Recarga la página</strong> de alojamientos-turisticos.html (Ctrl + F5)</li>";
        echo "<li><strong>Filtra por Soria</strong> y verifica que aparecen todos</li>";
        echo "</ol>";
        
        echo "<p><a href='alojamientos-turisticos.html' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>Ir a Alojamientos Turísticos</a></p>";
        
    } else {
        echo "<p style='background: #ffcdd2; padding: 15px; border-radius: 5px;'>";
        echo "❌ <strong>ERROR:</strong> No se pudo escribir el archivo accommodations.json. Verifica los permisos.";
        echo "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='background: #ffcdd2; padding: 15px; border-radius: 5px;'>";
    echo "❌ <strong>ERROR:</strong> " . $e->getMessage();
    echo "</p>";
}
?>
