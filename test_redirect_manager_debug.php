<?php
/**
 * Test de redirect_manager.php con depuración de errores
 */

// Habilitar todos los errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simular una llamada a redirect_manager.php
$_GET['type'] = 'actividad';
$_GET['slug'] = 'kayak-cuerda-del-pozo';

echo "<h1>Test de redirect_manager.php con DEPURACIÓN</h1>";
echo "<p><strong>Nota:</strong> Errores PHP estarán visibles</p>";

// Intentar incluir redirect_manager.php
try {
    // Leer el contenido de redirect_manager.php y modificar temporalmente
    $redirectManagerContent = file_get_contents('redirect_manager.php');
    
    // Habilitar errores temporalmente en el contenido
    $redirectManagerContent = str_replace(
        'error_reporting(0);',
        'error_reporting(E_ALL); // MODIFICADO PARA DEPURACIÓN',
        $redirectManagerContent
    );
    $redirectManagerContent = str_replace(
        'ini_set(\'display_errors\', 0);',
        'ini_set(\'display_errors\', 1); // MODIFICADO PARA DEPURACIÓN',
        $redirectManagerContent
    );
    
    // Escribir versión temporal
    $tempFile = tempnam(sys_get_temp_dir(), 'redirect_manager_');
    file_put_contents($tempFile, $redirectManagerContent);
    
    // Incluir la versión modificada
    ob_start();
    include $tempFile;
    $output = ob_get_clean();
    
    // Eliminar archivo temporal
    unlink($tempFile);
    
} catch (Exception $e) {
    $output = "EXCEPCIÓN: " . $e->getMessage();
}

// Analizar la salida
echo "<h2>Parámetros:</h2>";
echo "<pre>type: " . htmlspecialchars($_GET['type']) . "</pre>";
echo "<pre>slug: " . htmlspecialchars($_GET['slug']) . "</pre>";

echo "<h2>Análisis de salida:</h2>";

// Buscar window.currentSlug en la salida
if (strpos($output, 'window.currentSlug') !== false) {
    echo "<p style='color: green;'>✓ window.currentSlug encontrado en la salida</p>";
    
    // Extraer el valor exacto
    preg_match('/window\.currentSlug\s*=\s*[\'"]([^\'"]+)[\'"]/', $output, $matches);
    if (isset($matches[1])) {
        echo "<p>Valor inyectado: <strong>" . htmlspecialchars($matches[1]) . "</strong></p>";
        
        if ($matches[1] === $_GET['slug']) {
            echo "<p style='color: green;'>✓ Valor correcto: coincide con el slug</p>";
        } else {
            echo "<p style='color: red;'>✗ Valor incorrecto: no coincide con el slug</p>";
        }
    }
} else {
    echo "<p style='color: red;'>✗ window.currentSlug NO encontrado en la salida</p>";
}

// Buscar si se cargó actividad.html
if (strpos($output, 'actividad.html') !== false) {
    echo "<p style='color: green;'>✓ actividad.html referenciado en la salida</p>";
} else {
    echo "<p style='color: orange;'>⚠ actividad.html NO referenciado en la salida</p>";
}

// Verificar si hay errores de "file not found"
if (strpos($output, 'failed to open stream') !== false || 
    strpos($output, 'No such file or directory') !== false) {
    echo "<p style='color: red;'>✗ ERROR: Archivo no encontrado</p>";
}

// Verificar conexión a base de datos
if (strpos($output, 'SQLSTATE') !== false || 
    strpos($output, 'PDOException') !== false ||
    strpos($output, 'MySQL') !== false) {
    echo "<p style='color: red;'>✗ ERROR de base de datos detectado</p>";
}

// Mostrar primeros 3000 caracteres de la salida para depuración
echo "<h2>Salida completa (primeros 3000 caracteres):</h2>";
echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 400px; overflow: auto; border: 1px solid #ccc;'>";
echo htmlspecialchars(substr($output, 0, 3000));
echo "</pre>";

// Si la salida es muy corta, podría indicar un error fatal
if (strlen($output) < 500) {
    echo "<p style='color: red;'>✗ ADVERTENCIA: La salida es muy corta (" . strlen($output) . " caracteres). Podría indicar un error fatal.</p>";
}

// También verificar el archivo actividad.html directamente
echo "<hr><h2>Verificación de actividad.html:</h2>";
if (file_exists('actividad.html')) {
    echo "<p style='color: green;'>✓ actividad.html existe en el sistema</p>";
    
    $actividadContent = file_get_contents('actividad.html');
    $checks = [
        'window.currentSlug' => 'Referencia a window.currentSlug',
        'loadActivityData' => 'Función loadActivityData',
        'actividadContent' => 'Elemento con id="actividadContent"',
        '/api/actividad.php' => 'Llamada a API actividad.php'
    ];
    
    foreach ($checks as $pattern => $description) {
        if (strpos($actividadContent, $pattern) !== false) {
            echo "<p style='color: green;'>✓ $description encontrado en actividad.html</p>";
        } else {
            echo "<p style='color: red;'>✗ $description NO encontrado en actividad.html</p>";
        }
    }
    
} else {
    echo "<p style='color: red;'>✗ ERROR CRÍTICO: actividad.html NO existe en el sistema</p>";
}
?>