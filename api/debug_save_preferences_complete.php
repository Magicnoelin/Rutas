<?php
/**
 * Debug completo para save-preferences.php
 * Simula una petición POST completa con sesión
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debug Completo - Save Preferences</h1>";
echo "<pre>";

// Incluir config
require_once 'config.php';

echo "=== CONFIGURACIÓN ===\n";
echo "DB_HOST: " . DB_HOST . "\n";
echo "DB_NAME: " . DB_NAME . "\n";
echo "DB_USER: " . DB_USER . "\n";

// Probar conexión a BD
echo "\n=== CONEXIÓN BD ===\n";
try {
    $pdo = getDBConnection();
    echo "✅ Conexión exitosa\n";

    // Verificar tabla user_preferences
    echo "\n=== TABLA USER_PREFERENCES ===\n";
    $result = $pdo->query("SHOW TABLES LIKE 'user_preferences'");
    $tableExists = $result->rowCount() > 0;
    echo "Tabla existe: " . ($tableExists ? "✅" : "❌") . "\n";

    if ($tableExists) {
        // Mostrar estructura
        echo "\nEstructura de la tabla:\n";
        $columns = $pdo->query("DESCRIBE user_preferences")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "  {$col['Field']}: {$col['Type']} " . ($col['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . "\n";
        }

        // Verificar si hay datos
        echo "\nDatos existentes:\n";
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM user_preferences");
        $count = $stmt->fetch()['total'];
        echo "Total registros: $count\n";

        if ($count > 0) {
            $stmt = $pdo->query("SELECT id, user_id, interests, accommodation_types, budget, group_size, trip_duration, created_at FROM user_preferences LIMIT 5");
            $rows = $stmt->fetchAll();
            foreach ($rows as $row) {
                echo "  ID {$row['id']}: User {$row['user_id']}, Budget: {$row['budget']}, Created: {$row['created_at']}\n";
            }
        }
    }

    // Verificar tabla users
    echo "\n=== TABLA USERS ===\n";
    $result = $pdo->query("SHOW TABLES LIKE 'users'");
    $usersTableExists = $result->rowCount() > 0;
    echo "Tabla users existe: " . ($usersTableExists ? "✅" : "❌") . "\n";

    if ($usersTableExists) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $count = $stmt->fetch()['total'];
        echo "Total usuarios: $count\n";

        if ($count > 0) {
            $stmt = $pdo->query("SELECT id, first_name, last_name, email FROM users ORDER BY id DESC LIMIT 3");
            $users = $stmt->fetchAll();
            foreach ($users as $user) {
                echo "  ID {$user['id']}: {$user['first_name']} {$user['last_name']} ({$user['email']})\n";
            }
        }
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit();
}

// Simular petición POST
echo "\n=== SIMULACIÓN POST ===\n";

// Datos de prueba
$testData = [
    'interests' => ['naturaleza', 'cultura'],
    'accommodation_types' => ['casa_rural', 'hotel'],
    'budget' => '100-150',
    'group_size' => '2',
    'trip_duration' => '3-4'
];

echo "Datos a enviar:\n";
echo json_encode($testData, JSON_PRETTY_PRINT) . "\n";

// Simular sesión (usar un user_id existente)
session_start();
if ($usersTableExists) {
    $stmt = $pdo->query("SELECT id FROM users ORDER BY id DESC LIMIT 1");
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        echo "\nUsando user_id de sesión: {$user['id']}\n";
    } else {
        echo "\n❌ No hay usuarios en la BD para probar\n";
        exit();
    }
}

// Simular el procesamiento de save-preferences.php
echo "\n=== PROCESAMIENTO ===\n";

try {
    // Verificar método POST (simulado)
    echo "1. Verificando método POST: ✅ (simulado)\n";

    // Verificar sesión
    echo "2. Verificando sesión...\n";
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Usuario no autenticado');
    }
    $userId = $_SESSION['user_id'];
    echo "   ✅ Usuario autenticado: $userId\n";

    // Verificar que el usuario existe
    echo "3. Verificando usuario en BD...\n";
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = :user_id");
    $stmt->bindValue(':user_id', $userId);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        throw new Exception('Usuario no encontrado en BD');
    }
    echo "   ✅ Usuario existe\n";

    // Crear tabla si no existe
    echo "4. Verificando/creando tabla user_preferences...\n";
    $sqlCheckTable = "SHOW TABLES LIKE 'user_preferences'";
    $result = $pdo->query($sqlCheckTable);
    $tableExists = $result->rowCount() > 0;

    if (!$tableExists) {
        echo "   Creando tabla...\n";
        $sqlCreateTable = "
            CREATE TABLE user_preferences (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                interests JSON,
                accommodation_types JSON,
                budget VARCHAR(20),
                group_size VARCHAR(20),
                trip_duration VARCHAR(20),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $pdo->exec($sqlCreateTable);
        echo "   ✅ Tabla creada\n";
    } else {
        echo "   ✅ Tabla ya existe\n";
    }

    // Verificar preferencias existentes
    echo "5. Verificando preferencias existentes...\n";
    $sqlCheckPreferences = "SELECT id FROM user_preferences WHERE user_id = :user_id";
    $stmtCheck = $pdo->prepare($sqlCheckPreferences);
    $stmtCheck->bindValue(':user_id', $userId);
    $stmtCheck->execute();

    $preferencesExist = $stmtCheck->rowCount() > 0;
    echo "   " . ($preferencesExist ? "✅ Existen preferencias" : "ℹ️ No existen preferencias") . "\n";

    // Preparar datos
    echo "6. Preparando datos...\n";
    $preferencesData = [
        'user_id' => $userId,
        'interests' => json_encode($testData['interests'] ?? []),
        'accommodation_types' => json_encode($testData['accommodation_types'] ?? []),
        'budget' => $testData['budget'] ?? null,
        'group_size' => $testData['group_size'] ?? null,
        'trip_duration' => $testData['trip_duration'] ?? null
    ];
    echo "   ✅ Datos preparados\n";

    // Insertar o actualizar
    echo "7. " . ($preferencesExist ? "Actualizando" : "Insertando") . " preferencias...\n";

    if ($preferencesExist) {
        $columnas = array_keys($preferencesData);
        $setClause = implode(', ', array_map(function($col) { return "$col = :$col"; }, $columnas));
        $sql = "UPDATE user_preferences SET $setClause WHERE user_id = :user_id";
    } else {
        $columnas = array_keys($preferencesData);
        $placeholders = array_map(function($col) { return ":$col"; }, $columnas);
        $sql = "INSERT INTO user_preferences (" . implode(', ', $columnas) . ") VALUES (" . implode(', ', $placeholders) . ")";
    }

    $stmt = $pdo->prepare($sql);
    foreach ($preferencesData as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }

    $stmt->execute();

    if ($preferencesExist) {
        echo "   ✅ Preferencias actualizadas\n";
    } else {
        echo "   ✅ Preferencias insertadas con ID: " . $pdo->lastInsertId() . "\n";
    }

    echo "\n🎉 ¡PREFERENCIAS GUARDADAS EXITOSAMENTE!\n";

    // Verificar resultado
    echo "\n=== VERIFICACIÓN ===\n";
    $stmt = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id = :user_id");
    $stmt->bindValue(':user_id', $userId);
    $stmt->execute();

    $result = $stmt->fetch();
    if ($result) {
        echo "Datos guardados:\n";
        echo "  ID: {$result['id']}\n";
        echo "  User ID: {$result['user_id']}\n";
        echo "  Interests: {$result['interests']}\n";
        echo "  Accommodation Types: {$result['accommodation_types']}\n";
        echo "  Budget: {$result['budget']}\n";
        echo "  Group Size: {$result['group_size']}\n";
        echo "  Trip Duration: {$result['trip_duration']}\n";
        echo "  Created: {$result['created_at']}\n";
        echo "  Updated: {$result['updated_at']}\n";
    }

} catch (Exception $e) {
    echo "\n❌ ERROR en el procesamiento:\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>
