<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Actualizar Servidor - TODOS los alojamientos</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .paso { background: white; padding: 20px; margin: 15px 0; border-radius: 10px; border-left: 5px solid #4CAF50; }
        .importante { background: #fff3cd; border-left-color: #ffc107; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .exito { background: #d4edda; border-left-color: #28a745; }
        .codigo { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
        h1 { color: #2c5f2d; }
        button { background: #4CAF50; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
        button:hover { background: #45a049; }
    </style>
</head>
<body>
    <h1>🚀 Solución DEFINITIVA - Servidor Público</h1>
    
    <div class="importante">
        <h2>⚠️ IMPORTANTE</h2>
        <p><strong>Este script actualizará el archivo accommodations.json en el SERVIDOR con TODOS los alojamientos activos.</strong></p>
        <p>Una vez actualizado, TODOS los usuarios (móvil, web, etc.) verán todos los alojamientos correctamente.</p>
    </div>

    <?php
    if (isset($_POST['actualizar'])) {
        require_once 'api/config.php';
        
        echo "<div class='paso'>";
        echo "<h2>📥 Paso 1: Obteniendo datos de la base de datos</h2>";
        
        try {
            $pdo = getDBConnection();
            
            // Obtener TODOS los alojamientos activos
            $stmt = $pdo->query("
                SELECT * FROM accommodations 
                WHERE is_active = 1 
                ORDER BY province ASC, name ASC
            ");
            $alojamientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $total = count($alojamientos);
            echo "<p class='exito'>✅ <strong>Obtenidos $total alojamientos activos</strong></p>";
            
            // Contar por provincia
            $porProvincia = [];
            foreach ($alojamientos as $aloj) {
                $prov = $aloj['province'] ?? 'Sin provincia';
                if (!isset($porProvincia[$prov])) {
                    $porProvincia[$prov] = 0;
                }
                $porProvincia[$prov]++;
            }
            
            echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
            echo "<tr style='background: #4CAF50; color: white;'><th>Provincia</th><th>Cantidad</th></tr>";
            foreach ($porProvincia as $prov => $count) {
                $bgColor = $prov === 'Soria' ? '#ffeb3b' : '#f8f9fa';
                echo "<tr style='background: $bgColor;'><td><strong>$prov</strong></td><td><strong>$count</strong></td></tr>";
            }
            echo "</table>";
            echo "</div>";
            
            // Procesar datos para el frontend
            echo "<div class='paso'>";
            echo "<h2>⚙️ Paso 2: Procesando datos</h2>";
            
            $alojamientosProcesados = array_map(function($aloj) {
                $processed = [
                    'id' => $aloj['id'],
                    'nombre' => $aloj['name'],
                    'Nombre' => $aloj['name'],
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
                        if (strpos($fotoValue, ',') !== false) {
                            $fotoUrls = array_map('trim', explode(',', $fotoValue));
                            foreach ($fotoUrls as $fotoUrl) {
                                if (!empty($fotoUrl)) {
                                    if (!preg_match('/^https?:\/\//', $fotoUrl)) {
                                        $fotoUrl = 'https://rutasrurales.io/Alojamientos_Images/' . $fotoUrl;
                                    }
                                    $fotos[] = $fotoUrl;
                                }
                            }
                        } else {
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
            
            echo "<p class='exito'>✅ <strong>Datos procesados correctamente</strong></p>";
            echo "</div>";
            
            // Guardar JSON
            echo "<div class='paso'>";
            echo "<h2>💾 Paso 3: Guardando accommodations.json</h2>";
            
            $jsonData = json_encode($alojamientosProcesados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $result = file_put_contents('accommodations.json', $jsonData);
            
            if ($result !== false) {
                echo "<p class='exito' style='font-size: 1.2em; padding: 20px;'>✅ <strong>¡ÉXITO TOTAL!</strong></p>";
                echo "<p><strong>El archivo accommodations.json ha sido actualizado con $total alojamientos</strong></p>";
                echo "<p>Tamaño del archivo: " . number_format($result / 1024, 2) . " KB</p>";
                echo "</div>";
                
                echo "<div class='paso exito'>";
                echo "<h2>🌍 Paso 4: SUBIR AL SERVIDOR</h2>";
                echo "<p><strong>Ahora debes subir este archivo al servidor:</strong></p>";
                echo "<div class='codigo'>";
                echo "Archivo: <strong>accommodations.json</strong><br>";
                echo "Ubicación: Raíz del sitio (mismo lugar donde está index.html)";
                echo "</div>";
                
                echo "<h3>Opciones para subir:</h3>";
                echo "<ol>";
                echo "<li><strong>FTP/SFTP</strong>: Usa FileZilla o tu cliente FTP favorito</li>";
                echo "<li><strong>cPanel</strong>: Administrador de archivos → Cargar</li>";
                echo "<li><strong>Git</strong>: <code>git add accommodations.json && git commit -m 'Actualizar todos los alojamientos' && git push</code></li>";
                echo "</ol>";
                
                echo "<h3>✅ Verificar después de subir:</h3>";
                echo "<ol>";
                echo "<li>Abre en tu móvil: <a href='https://rutasrurales.io/alojamientos-turisticos.html' target='_blank'>https://rutasrurales.io/alojamientos-turisticos.html</a></li>";
                echo "<li>Filtra por provincia \"Soria\"</li>";
                echo "<li>Deberías ver los " . ($porProvincia['Soria'] ?? 0) . " alojamientos</li>";
                echo "</ol>";
                
                echo "<p style='background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
                echo "⚠️ <strong>IMPORTANTE:</strong> Puede que necesites limpiar la caché del móvil o esperar unos minutos para que se actualice.";
                echo "</p>";
                
                echo "</div>";
                
            } else {
                echo "<p style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
                echo "❌ <strong>ERROR:</strong> No se pudo escribir accommodations.json. Verifica permisos.";
                echo "</p>";
                echo "</div>";
            }
            
        } catch (Exception $e) {
            echo "<p style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
            echo "❌ <strong>ERROR:</strong> " . $e->getMessage();
            echo "</p>";
            echo "</div>";
        }
        
    } else {
        ?>
        <div class="paso">
            <h2>📋 ¿Qué hará este script?</h2>
            <ol>
                <li>Leerá TODOS los alojamientos activos de la base de datos</li>
                <li>Creará un archivo accommodations.json actualizado</li>
                <li>Te dará instrucciones para subirlo al servidor</li>
            </ol>
            <p><strong>Una vez subido, TODOS los usuarios verán TODOS los alojamientos.</strong></p>
        </div>
        
        <form method="POST">
            <button type="submit" name="actualizar">🚀 ACTUALIZAR AHORA</button>
        </form>
        <?php
    }
    ?>
</body>
</html>
