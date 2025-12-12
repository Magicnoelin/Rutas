<?php
/**
 * Debug simple para register_simple.php
 */

// Mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debug Register Simple</h1>";
echo "<pre>";

try {
    echo "1. Archivo register_simple.php existe: ";
    if (file_exists('register_simple.php')) {
        echo "✅ SÍ\n";

        echo "2. Intentando cargar register_simple.php...\n";
        require_once 'register_simple.php';
        echo "✅ register_simple.php cargado correctamente\n";

    } else {
        echo "❌ NO\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR al cargar register_simple.php:\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>
