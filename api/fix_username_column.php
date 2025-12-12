<?php
/**
 * Script para corregir el problema de la columna username en la tabla users
 */

require_once 'config.php';

echo "<h1>Corregir Columna Username</h1>";
echo "<pre>";

try {
    $pdo = getDBConnection();

    // 1. Verificar estructura actual de la tabla users
    echo "=== 1. ESTRUCTURA ACTUAL DE USERS ===\n";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        echo "Columna: {$col['Field']} - Tipo: {$col['Type']} - Null: {$col['Null']} - Key: {$col['Key']} - Default: {$col['Default']}\n";
    }

    // 2. Verificar si hay registros con username vacío o NULL
    echo "\n=== 2. REGISTROS CON USERNAME PROBLEMÁTICO ===\n";
    $stmt = $pdo->query("SELECT id, username, email FROM users WHERE username IS NULL OR username = ''");
    $problematicUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Usuarios con username NULL o vacío: " . count($problematicUsers) . "\n";
    foreach ($problematicUsers as $user) {
        echo "- ID: {$user['id']}, Email: {$user['email']}, Username: '{$user['username']}'\n";
    }

    // 3. Opción A: Hacer username nullable y quitar UNIQUE
    echo "\n=== 3. CORRIENDO REPARACIÓN ===\n";

    // Primero, actualizar registros existentes con username NULL a un valor único
    if (count($problematicUsers) > 0) {
        echo "Actualizando registros existentes...\n";
        foreach ($problematicUsers as $user) {
            $newUsername = 'user_' . $user['id'] . '_' . time();
            $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt->execute([$newUsername, $user['id']]);
            echo "✅ Actualizado usuario ID {$user['id']} con username: $newUsername\n";
        }
    }

    // Hacer la columna username nullable y quitar la restricción UNIQUE
    echo "Modificando estructura de la columna username...\n";

    // Quitar índice único si existe
    try {
        $pdo->exec("ALTER TABLE users DROP INDEX username");
        echo "✅ Índice único de username eliminado\n";
    } catch (Exception $e) {
        echo "⚠️ No había índice único que eliminar: " . $e->getMessage() . "\n";
    }

    // Hacer la columna nullable
    $pdo->exec("ALTER TABLE users MODIFY COLUMN username VARCHAR(50) NULL");
    echo "✅ Columna username ahora es nullable\n";

    // 4. Verificar que la reparación funcionó
    echo "\n=== 4. VERIFICACIÓN ===\n";

    // Probar inserción de prueba
    $pdo->beginTransaction();

    $testData = [
        'user_type' => 'turista',
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test_' . time() . '@example.com',
        'password_hash' => password_hash('test123456', PASSWORD_DEFAULT),
        'terms_accepted' => 1,
        'status' => 'active',
        'username' => null // NULL para probar
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

    echo "✅ Inserción de prueba exitosa con username NULL - ID: $userId\n";

    $pdo->rollBack();
    echo "✅ Transacción revertida\n";

    echo "\n🎉 REPARACIÓN COMPLETADA EXITOSAMENTE\n";
    echo "Ahora el registro debería funcionar correctamente.\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}

echo "</pre>";
?>
