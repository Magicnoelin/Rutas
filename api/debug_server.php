<?php
/**
 * Debug para verificar el estado del servidor y BD
 */

require_once 'config.php';

echo "<h1>Debug del Servidor - Rutas Rurales</h1>";
echo "<pre>";

// 1. Verificar conexión a BD
echo "=== 1. CONEXIÓN A BASE DE DATOS ===\n";
try {
    $pdo = getDBConnection();
    echo "✅ Conexión exitosa a BD: " . DB_NAME . "\n";
} catch (Exception $e) {
    echo "❌ ERROR de conexión: " . $e->getMessage() . "\n";
    exit();
}

// 2. Verificar tablas necesarias
echo "\n=== 2. VERIFICACIÓN DE TABLAS ===\n";
$tablasNecesarias = ['users', 'user_permissions', 'user_preferences'];

foreach ($tablasNecesarias as $tabla) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tabla'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Tabla '$tabla' existe\n";

            // Verificar estructura básica
            $stmt = $pdo->query("DESCRIBE $tabla");
            $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "   - Columnas: " . count($columnas) . "\n";

            // Verificar si hay registros
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM $tabla");
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "   - Registros: " . $count['total'] . "\n";

        } else {
            echo "❌ Tabla '$tabla' NO existe\n";
        }
    } catch (Exception $e) {
        echo "❌ ERROR al verificar tabla '$tabla': " . $e->getMessage() . "\n";
    }
}

// 3. Verificar columnas específicas de users
echo "\n=== 3. VERIFICACIÓN DE COLUMNAS USERS ===\n";
$columnasUsers = ['id', 'user_type', 'first_name', 'last_name', 'email', 'password_hash', 'terms_accepted'];

foreach ($columnasUsers as $columna) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE '$columna'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Columna '$columna' existe en users\n";
        } else {
            echo "❌ Columna '$columna' NO existe en users\n";
        }
    } catch (Exception $e) {
        echo "❌ ERROR al verificar columna '$columna': " . $e->getMessage() . "\n";
    }
}

// 4. Probar inserción de prueba (sin commit)
echo "\n=== 4. PRUEBA DE INSERCIÓN ===\n";
try {
    $pdo->beginTransaction();

    // Crear usuario de prueba
    $testData = [
        'user_type' => 'turista',
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test_' . time() . '@example.com',
        'password_hash' => password_hash('test123456', PASSWORD_DEFAULT),
        'terms_accepted' => 1,
        'status' => 'active'
    ];

    $columnas = array_keys($testData);
    $placeholders = array_map(function($col) { return ":$col"; }, $columnas);
    $sql = "INSERT INTO users (" . implode(', ', $columnas) . ") VALUES (" . implode(', ', $placeholders) . ")";

    $stmt = $pdo->prepare($sql);
    foreach ($testData as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }

    $result = $stmt->execute();
    $userId = $pdo->lastInsertId();

    echo "✅ Inserción de prueba exitosa - ID: $userId\n";

    // Revertir la transacción (no queremos dejar datos de prueba)
    $pdo->rollBack();
    echo "✅ Transacción revertida correctamente\n";

} catch (Exception $e) {
    echo "❌ ERROR en inserción de prueba: " . $e->getMessage() . "\n";
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

// 5. Verificar funciones PHP necesarias
echo "\n=== 5. FUNCIONES PHP ===\n";
$funcionesNecesarias = ['password_hash', 'random_bytes', 'json_encode', 'json_decode'];

foreach ($funcionesNecesarias as $funcion) {
    if (function_exists($funcion)) {
        echo "✅ Función '$funcion' disponible\n";
    } else {
        echo "❌ Función '$funcion' NO disponible\n";
    }
}

// 6. Información del servidor
echo "\n=== 6. INFORMACIÓN DEL SERVIDOR ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "Request Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'Unknown') . "\n";
echo "Remote IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n";

echo "\n=== RESUMEN ===\n";
echo "Si todo arriba está en ✅, el problema debe estar en el código JavaScript o en la petición desde el navegador móvil.\n";
echo "Si hay ❌, esos son los problemas que hay que solucionar.\n";

echo "</pre>";
?>
