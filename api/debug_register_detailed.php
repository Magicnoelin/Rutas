<?php
/**
 * Debug detallado de register_simple.php
 */

// Mostrar todos los errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debug Detallado - Register Simple</h1>";
echo "<pre>";

// Test 1: Verificar archivos
echo "=== ARCHIVOS ===\n";
echo "register_simple.php existe: " . (file_exists('register_simple.php') ? "✅" : "❌") . "\n";
echo "config.php existe: " . (file_exists('config.php') ? "✅" : "❌") . "\n";

// Test 2: Cargar config.php
echo "\n=== CARGANDO CONFIG ===\n";
try {
    require_once 'config.php';
    echo "✅ config.php cargado correctamente\n";

    // Test 3: Verificar funciones
    echo "\n=== FUNCIONES ===\n";
    echo "getDBConnection: " . (function_exists('getDBConnection') ? "✅" : "❌") . "\n";
    echo "jsonError: " . (function_exists('jsonError') ? "✅" : "❌") . "\n";
    echo "jsonSuccess: " . (function_exists('jsonSuccess') ? "✅" : "❌") . "\n";
    echo "sanitizeInput: " . (function_exists('sanitizeInput') ? "✅" : "❌") . "\n";
    echo "isValidEmail: " . (function_exists('isValidEmail') ? "✅" : "❌") . "\n";

    // Test 4: Conexión a BD
    echo "\n=== CONEXIÓN BD ===\n";
    $pdo = getDBConnection();
    echo "✅ Conexión exitosa\n";

    // Test 5: Verificar tablas
    echo "\n=== TABLAS ===\n";
    $tables = $pdo->query("SHOW TABLES LIKE 'users'")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tabla users existe: " . (!empty($tables) ? "✅" : "❌") . "\n";

    if (!empty($tables)) {
        // Verificar columnas
        echo "\n=== COLUMNAS USERS ===\n";
        $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "{$col['Field']}: {$col['Type']} " . ($col['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . "\n";
        }
    }

    // Test 6: Verificar tabla user_permissions
    echo "\n=== TABLA USER_PERMISSIONS ===\n";
    $permTables = $pdo->query("SHOW TABLES LIKE 'user_permissions'")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tabla user_permissions existe: " . (!empty($permTables) ? "✅" : "❌") . "\n";

    echo "\n=== TODO OK - EL ERROR DEBE ESTAR EN LA LÓGICA ===\n";

} catch (Exception $e) {
    echo "❌ ERROR en config/conexión: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";

// Simular una petición POST como la que hace el formulario
echo "<h2>Simulando petición POST...</h2>";
echo "<pre>";

// Datos de prueba
$testData = [
    'userType' => 'turista',
    'firstName' => 'Test',
    'lastName' => 'User',
    'email' => 'test@example.com',
    'password' => 'password123',
    'confirmPassword' => 'password123',
    'terms' => true
];

echo "Datos de prueba:\n";
echo json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

// Simular el procesamiento
try {
    echo "=== SIMULANDO PROCESAMIENTO ===\n";

    // Simular validaciones
    $camposRequeridos = ['userType', 'firstName', 'lastName', 'email', 'password'];
    foreach ($camposRequeridos as $campo) {
        if (!isset($testData[$campo]) || empty(trim($testData[$campo]))) {
            throw new Exception("Campo requerido faltante: $campo");
        }
    }
    echo "✅ Validación de campos OK\n";

    // Simular validación de tipo
    $tiposValidos = ['turista', 'alojamiento', 'promotor_eventos', 'actividad_cultural'];
    if (!in_array($testData['userType'], $tiposValidos)) {
        throw new Exception('Tipo de usuario inválido');
    }
    echo "✅ Validación de tipo de usuario OK\n";

    // Simular validación de email
    if (!filter_var($testData['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email inválido');
    }
    echo "✅ Validación de email OK\n";

    // Simular validación de contraseña
    if (strlen($testData['password']) < 8) {
        throw new Exception('Contraseña demasiado corta');
    }
    echo "✅ Validación de contraseña OK\n";

    // Simular verificación de contraseñas
    if ($testData['password'] !== $testData['confirmPassword']) {
        throw new Exception('Contraseñas no coinciden');
    }
    echo "✅ Verificación de contraseñas OK\n";

    echo "✅ TODAS LAS VALIDACIONES PASAN\n";

} catch (Exception $e) {
    echo "❌ ERROR en validaciones: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<h2>Conclusión:</h2>";
echo "<p>Si ves errores arriba, esos son los problemas. Si todo está OK, el error 500 debe estar en la inserción a BD o en las funciones de permisos.</p>";
?>
