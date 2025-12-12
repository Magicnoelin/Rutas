<?php
/**
 * Debug simple para register.php
 */

// Mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debug Register API</h1>";
echo "<pre>";

// Test 1: Verificar que el archivo existe
echo "1. Archivo register.php existe: ";
if (file_exists('register.php')) {
    echo "✅ SÍ\n";
} else {
    echo "❌ NO\n";
}

// Test 2: Verificar sintaxis básica
echo "2. Sintaxis PHP básica: ";
echo "⚠️ No se puede verificar sintaxis en este servidor (shell_exec deshabilitado)\n";
echo "   Asumiendo que la sintaxis es correcta\n";

// Test 3: Verificar config.php
echo "3. Config.php: ";
if (file_exists('config.php')) {
    echo "✅ Existe\n";
    require_once 'config.php';
    echo "   Función getDBConnection existe: " . (function_exists('getDBConnection') ? "✅" : "❌") . "\n";
    echo "   Función jsonError existe: " . (function_exists('jsonError') ? "✅" : "❌") . "\n";
    echo "   Función jsonSuccess existe: " . (function_exists('jsonSuccess') ? "✅" : "❌") . "\n";
} else {
    echo "❌ No existe\n";
}

// Test 4: Conexión a BD
echo "4. Conexión a base de datos: ";
try {
    $pdo = getDBConnection();
    echo "✅ OK\n";

    // Test 5: Verificar tablas
    echo "5. Tablas existentes:\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "   - $table\n";
    }

    // Test 6: Verificar tabla users específicamente
    echo "6. Estructura tabla users:\n";
    if (in_array('users', $tables)) {
        $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "   - {$col['Field']}: {$col['Type']}\n";
        }
    } else {
        echo "   ❌ Tabla users NO existe\n";
    }

    // Test 7: Verificar tabla user_permissions
    echo "7. Tabla user_permissions: ";
    if (in_array('user_permissions', $tables)) {
        echo "✅ Existe\n";
    } else {
        echo "❌ NO existe\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<h2>Posibles soluciones:</h2>";
echo "<ol>";
echo "<li>Si faltan tablas, ejecuta: <code>api/crear_tablas_usuarios.sql</code></li>";
echo "<li>Si faltan columnas en users, ejecuta: <code>api/actualizar_tabla_users.sql</code></li>";
echo "<li>Verifica que config.php tenga las funciones necesarias</li>";
echo "<li>Revisa los permisos de archivos PHP</li>";
echo "</ol>";
?>
