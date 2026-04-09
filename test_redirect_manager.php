<?php
/**
 * Test directo de redirect_manager.php
 */

// Simular una llamada a redirect_manager.php
$_GET['type'] = 'actividad';
$_GET['slug'] = 'kayak-cuerda-del-pozo';

// Incluir redirect_manager.php pero capturar la salida
ob_start();
include 'redirect_manager.php';
$output = ob_get_clean();

// Analizar la salida
echo "<h1>Test de redirect_manager.php</h1>";
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

// Buscar elementos clave de actividad.html
$checks = [
    'actividadContent' => 'Elemento principal de actividad',
    'loading' => 'Elemento de carga',
    'error' => 'Elemento de error',
    'actividadDescripcion' => 'Contenedor de descripción'
];

foreach ($checks as $element => $description) {
    if (strpos($output, "id=\"$element\"") !== false || strpos($output, "id='$element'") !== false) {
        echo "<p style='color: green;'>✓ $description encontrado</p>";
    } else {
        echo "<p style='color: orange;'>⚠ $description NO encontrado</p>";
    }
}

// Verificar si hay errores PHP
if (strpos($output, 'Fatal error') !== false || strpos($output, 'Parse error') !== false) {
    echo "<p style='color: red;'>✗ ERROR PHP detectado en la salida</p>";
}

// Mostrar primeros 2000 caracteres de la salida para depuración
echo "<h2>Primeros 2000 caracteres de la salida:</h2>";
echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 300px; overflow: auto;'>";
echo htmlspecialchars(substr($output, 0, 2000));
echo "</pre>";

// También probar con una actividad que no existe
echo "<hr><h2>Test con actividad inexistente:</h2>";

$_GET['slug'] = 'actividad-inexistente-12345';
ob_start();
include 'redirect_manager.php';
$output2 = ob_get_clean();

if (strpos($output2, '404') !== false || strpos($output2, 'no encontrada') !== false) {
    echo "<p style='color: green;'>✓ Manejo correcto de actividad no encontrada</p>";
} else {
    echo "<p style='color: orange;'>⚠ No se detectó manejo específico para actividad no encontrada</p>";
}
?>