<?php
// Test script to verify header/footer loading
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Header/Footer Loading</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .test { margin: 20px 0; padding: 15px; border: 1px solid #ccc; border-radius: 5px; }
        .success { color: green; border-color: green; background: #f0fff0; }
        .error { color: red; border-color: red; background: #fff0f0; }
        .info { color: blue; border-color: blue; background: #f0f8ff; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Test de Carga de Header/Footer</h1>
    
    <div class="test info">
        <h2>Objetivo</h2>
        <p>Verificar que los módulos de header y footer puedan cargarse correctamente desde las páginas modulares.</p>
    </div>
    
    <div class="test">
        <h2>1. Verificar archivos existentes</h2>
        <?php
        $files = [
            'header.php' => file_exists('header.php'),
            'footer.php' => file_exists('footer.php'),
            'nuevo-alojamiento/modules/header.js' => file_exists('nuevo-alojamiento/modules/header.js'),
            'nuevo-alojamiento/modules/footer.js' => file_exists('nuevo-alojamiento/modules/footer.js'),
            'nuevo-alojamiento/alojamiento.js' => file_exists('nuevo-alojamiento/alojamiento.js'),
            'nuevo-alojamiento/index.html' => file_exists('nuevo-alojamiento/index.html'),
        ];
        
        foreach ($files as $file => $exists) {
            echo '<p>' . ($exists ? '✅' : '❌') . ' ' . $file . '</p>';
        }
        ?>
    </div>
    
    <div class="test">
        <h2>2. Verificar contenido de header.php</h2>
        <?php
        if (file_exists('header.php')) {
            $headerContent = file_get_contents('header.php');
            $hasHeaderTag = strpos($headerContent, '<header class="header">') !== false;
            $hasNavTag = strpos($headerContent, '<nav class="navbar">') !== false;
            
            echo '<p>' . ($hasHeaderTag ? '✅' : '❌') . ' Contiene <header class="header"></p>';
            echo '<p>' . ($hasNavTag ? '✅' : '❌') . ' Contiene <nav class="navbar"></p>';
            
            // Extract a sample of the header
            $sample = substr($headerContent, 0, 500);
            echo '<pre>' . htmlspecialchars($sample) . '...</pre>';
        } else {
            echo '<p class="error">❌ header.php no existe</p>';
        }
        ?>
    </div>
    
    <div class="test">
        <h2>3. Verificar contenido de footer.php</h2>
        <?php
        if (file_exists('footer.php')) {
            $footerContent = file_get_contents('footer.php');
            $hasFooterTag = strpos($footerContent, '<footer class="footer">') !== false;
            $hasCopyright = strpos($footerContent, 'rutasrurales.io') !== false;
            
            echo '<p>' . ($hasFooterTag ? '✅' : '❌') . ' Contiene <footer class="footer"></p>';
            echo '<p>' . ($hasCopyright ? '✅' : '❌') . ' Contiene "rutasrurales.io"</p>';
            
            // Extract a sample of the footer
            $sample = substr($footerContent, 0, 500);
            echo '<pre>' . htmlspecialchars($sample) . '...</pre>';
        } else {
            echo '<p class="error">❌ footer.php no existe</p>';
        }
        ?>
    </div>
    
    <div class="test">
        <h2>4. Verificar módulos JavaScript</h2>
        <?php
        if (file_exists('nuevo-alojamiento/modules/header.js')) {
            $headerJS = file_get_contents('nuevo-alojamiento/modules/header.js');
            $hasLoadHeader = strpos($headerJS, 'loadHeader') !== false;
            $hasExport = strpos($headerJS, 'export') !== false;
            
            echo '<p>' . ($hasLoadHeader ? '✅' : '❌') . ' Contiene función loadHeader()</p>';
            echo '<p>' . ($hasExport ? '✅' : '❌') . ' Usa export ES6</p>';
        }
        
        if (file_exists('nuevo-alojamiento/modules/footer.js')) {
            $footerJS = file_get_contents('nuevo-alojamiento/modules/footer.js');
            $hasLoadFooter = strpos($footerJS, 'loadFooter') !== false;
            
            echo '<p>' . ($hasLoadFooter ? '✅' : '❌') . ' Contiene función loadFooter()</p>';
        }
        ?>
    </div>
    
    <div class="test success">
        <h2>5. Prueba de Integración</h2>
        <p>Para probar la integración completa:</p>
        <ol>
            <li>Abrir <a href="/nuevo-alojamiento/index.html?slug=test" target="_blank">/nuevo-alojamiento/index.html?slug=test</a></li>
            <li>Inspeccionar la consola del navegador (F12 > Console)</li>
            <li>Verificar que no haya errores JavaScript</li>
            <li>Comprobar que el header y footer se carguen</li>
            <li>Verificar que el CSS se cargue correctamente</li>
        </ol>
    </div>
    
    <div class="test info">
        <h2>6. Optimizaciones Implementadas</h2>
        <ul>
            <li>✅ CSS crítico inline para FCP más rápido</li>
            <li>✅ Precarga de módulos JavaScript</li>
            <li>✅ Carga asíncrona de CSS no crítico</li>
            <li>✅ Cabeceras de caché en .htaccess</li>
            <li>✅ Compresión GZIP/Brotli</li>
            <li>✅ Skeleton loading para mejor UX</li>
            <li>✅ Modularidad manteniendo consistencia corporativa</li>
        </ul>
    </div>
    
    <div class="test">
        <h2>7. Comandos de Verificación</h2>
        <pre>
# Verificar cabeceras HTTP
curl -I https://www.rutasrurales.io/nuevo-alojamiento/index.html

# Verificar compresión
curl -H "Accept-Encoding: gzip" -I https://www.rutasrurales.io/nuevo-alojamiento/styles/optimized.css

# Verificar caché
curl -I https://www.rutasrurales.io/nuevo-alojamiento/alojamiento.js
        </pre>
    </div>
</body>
</html>