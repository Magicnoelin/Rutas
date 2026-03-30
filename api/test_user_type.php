<?php
/**
 * Test rápido para verificar user_type en la base de datos
 */

require_once 'config.php';

echo "<h1>Test user_type en Base de Datos</h1>";
echo "<pre>";

try {
    $pdo = getDBConnection();
    echo "✅ Conexión BD exitosa\n\n";

    // Verificar usuarios recientes
    echo "=== ÚLTIMOS USUARIOS REGISTRADOS ===\n";
    $stmt = $pdo->query("SELECT id, user_type, first_name, last_name, email, created_at FROM users ORDER BY id DESC LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($users)) {
        echo "❌ No hay usuarios en la base de datos\n";
    } else {
        foreach ($users as $user) {
            echo "ID {$user['id']}: {$user['first_name']} {$user['last_name']} ({$user['email']})\n";
            echo "  user_type: '" . ($user['user_type'] ?? 'NULL') . "'\n";
            echo "  created_at: {$user['created_at']}\n";
            echo "\n";
        }
    }

    // Verificar estructura de la tabla users
    echo "=== ESTRUCTURA TABLA users ===\n";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        echo "{$col['Field']}: {$col['Type']} " . ($col['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . " " . ($col['Default'] ? "DEFAULT '{$col['Default']}'" : '') . "\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
