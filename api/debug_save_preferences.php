<?php
/**
 * Debug para save-preferences.php
 */

// Mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debug Save Preferences</h1>";
echo "<pre>";

// Test 1: Verificar archivos
echo "=== ARCHIVOS ===\n";
echo "save-preferences.php existe: " . (file_exists('save-preferences.php') ? "✅" : "❌") . "\n";
echo "config.php existe: " . (file_exists('config.php') ? "✅" : "❌") . "\n";

// Test 2: Cargar config
echo "\n=== CARGANDO CONFIG ===\n";
try {
    require_once 'config.php';
    echo "✅ config.php cargado\n";
} catch (Exception $e) {
    echo "❌ Error cargando config: " . $e->getMessage() . "\n";
}

// Test 3: Verificar funciones
echo "\n=== FUNCIONES ===\n";
echo "getDBConnection: " . (function_exists('getDBConnection') ? "✅" : "❌") . "\n";
echo "jsonError: " . (function_exists('jsonError') ? "✅" : "❌") . "\n";
echo "jsonSuccess: " . (function_exists('jsonSuccess') ? "✅" : "❌") . "\n";
echo "sanitizeInput: " . (function_exists('sanitizeInput') ? "✅" : "❌") . "\n";

// Test 4: Conexión BD
echo "\n=== CONEXIÓN BD ===\n";
try {
    $pdo = getDBConnection();
    echo "✅ Conexión exitosa\n";

    // Test 5: Verificar tabla user_preferences
    echo "\n=== TABLA USER_PREFERENCES ===\n";
    $tables = $pdo->query("SHOW TABLES LIKE 'user_preferences'")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tabla user_preferences existe: " . (!empty($tables) ? "✅" : "❌") . "\n";

    if (!empty($tables)) {
        // Verificar columnas
        echo "\n=== COLUMNAS USER_PREFERENCES ===\n";
        $columns = $pdo->query("DESCRIBE user_preferences")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "{$col['Field']}: {$col['Type']} " . ($col['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . "\n";
        }
    }

    // Test 6: Verificar tabla users (para user_id)
    echo "\n=== TABLA USERS ===\n";
    $userTables = $pdo->query("SHOW TABLES LIKE 'users'")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tabla users existe: " . (!empty($userTables) ? "✅" : "❌") . "\n";

    echo "\n=== CONFIGURACIÓN OK ===\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";

// Simular una petición POST como la que hace preferences.html
echo "<h2>Simulando petición POST...</h2>";
echo "<pre>";

// Datos de prueba
$testData = [
    'user_id' => 1,
    'travel_purpose' => 'relaxation',
    'accommodation_type' => 'rural_house',
    'budget_range' => '200-500',
    'group_size' => '2-4',
    'preferred_activities' => ['hiking', 'nature'],
    'preferred_locations' => ['soria', 'burgos'],
    'special_requirements' => 'Pet friendly',
    'notification_preferences' => ['email', 'sms']
];

echo "Datos de prueba:\n";
echo json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

// Simular procesamiento
try {
    echo "=== SIMULANDO PROCESAMIENTO ===\n";

    // Simular validaciones básicas
    if (!isset($testData['user_id']) || !is_numeric($testData['user_id'])) {
        throw new Exception('user_id inválido');
    }
    echo "✅ user_id válido\n";

    // Verificar que el usuario existe
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$testData['user_id']]);
    if ($stmt->rowCount() === 0) {
        throw new Exception('Usuario no encontrado');
    }
    echo "✅ Usuario existe\n";

    echo "✅ VALIDACIONES PASAN\n";

} catch (Exception $e) {
    echo "❌ ERROR en validaciones: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<h2>Conclusión:</h2>";
echo "<p>Si ves errores arriba, esos son los problemas. Si todo está OK, el error 500 debe estar en la lógica de inserción/actualización de preferencias.</p>";
?>
