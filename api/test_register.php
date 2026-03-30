<?php
/**
 * Test de registro - Para debug
 */

// Mostrar todos los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test de Registro - Debug</h2>";

try {
    echo "<p>1. Cargando config.php...</p>";
    require_once 'config.php';
    echo "<p>✓ Config cargado</p>";
    
    echo "<p>2. Conectando a base de datos...</p>";
    $pdo = getDBConnection();
    echo "<p>✓ Conexión exitosa</p>";
    
    echo "<p>3. Verificando tabla users...</p>";
    $sql = "DESCRIBE users";
    $stmt = $pdo->query($sql);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>✓ Columnas de la tabla users:</p>";
    echo "<pre>";
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
    echo "</pre>";
    
    echo "<p>4. Verificando tabla user_permissions...</p>";
    $sql = "SHOW TABLES LIKE 'user_permissions'";
    $result = $pdo->query($sql);
    if ($result->rowCount() > 0) {
        echo "<p>✓ Tabla user_permissions existe</p>";
        
        $sql = "DESCRIBE user_permissions";
        $stmt = $pdo->query($sql);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Columnas de user_permissions:</p>";
        echo "<pre>";
        foreach ($columns as $col) {
            echo $col['Field'] . " - " . $col['Type'] . "\n";
        }
        echo "</pre>";
    } else {
        echo "<p>❌ Tabla user_permissions NO existe</p>";
    }
    
    echo "<p>5. Probando función createUserPermissions...</p>";
    
    // Cargar la función desde register.php
    $registerContent = file_get_contents('register.php');
    if (strpos($registerContent, 'function createUserPermissions') !== false) {
        echo "<p>✓ Función createUserPermissions encontrada en register.php</p>";
    } else {
        echo "<p>❌ Función createUserPermissions NO encontrada</p>";
    }
    
    echo "<h3>✅ Todas las verificaciones completadas</h3>";
    echo "<p>Si ves este mensaje, la configuración básica está correcta.</p>";
    echo "<p>El error 500 puede ser por:</p>";
    echo "<ul>";
    echo "<li>Falta la tabla user_permissions</li>";
    echo "<li>Faltan columnas en la tabla users</li>";
    echo "<li>Error en la función createUserPermissions</li>";
    echo "<li>Problema con sesiones PHP</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
