<?php
/**
 * Debug para register.php original
 */

// Mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debug Register Original</h1>";
echo "<pre>";

try {
    echo "1. Archivo register.php existe: ";
    if (file_exists('register.php')) {
        echo "✅ SÍ\n";

        echo "2. Verificando sintaxis básica...\n";
        echo "⚠️ No se puede verificar sintaxis en este servidor (shell_exec deshabilitado)\n";
        echo "   Asumiendo que la sintaxis es correcta\n";

        echo "3. Intentando cargar funciones de register.php...\n";
        // Intentar cargar las funciones sin ejecutar el código principal
        $registerContent = file_get_contents('register.php');

        // Buscar funciones importantes
        $functions = [
            'createUserPermissions',
            'createTablesIfNotExist',
            'generateVerificationToken'
        ];

        foreach ($functions as $func) {
            if (strpos($registerContent, "function $func") !== false) {
                echo "✅ Función $func encontrada\n";
            } else {
                echo "⚠️ Función $func NO encontrada\n";
            }
        }

        echo "4. Verificando includes/requires...\n";
        if (strpos($registerContent, "require_once 'config.php'") !== false ||
            strpos($registerContent, "include_once 'config.php'") !== false) {
            echo "✅ config.php incluido correctamente\n";
        } else {
            echo "❌ config.php NO incluido\n";
        }

    } else {
        echo "❌ NO\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== CONCLUSIÓN ===\n";
echo "Si todo está OK arriba, el error 500 debe estar en la ejecución del código principal de register.php\n";
echo "El problema más probable es que está intentando crear tablas automáticamente y fallando.\n";

echo "</pre>";
?>
