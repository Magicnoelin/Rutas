<?php
/**
 * Corregir el enum de user_type en la tabla users
 */

require_once 'config.php';

echo "<h1>Corregir user_type Enum</h1>";
echo "<pre>";

try {
    $pdo = getDBConnection();
    echo "✅ Conexión BD exitosa\n\n";

    // Verificar estructura actual
    echo "=== ESTRUCTURA ACTUAL user_type ===\n";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        if ($col['Field'] === 'user_type') {
            echo "user_type: {$col['Type']} " . ($col['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . " " . ($col['Default'] ? "DEFAULT '{$col['Default']}'" : '') . "\n";
            break;
        }
    }

    echo "\n=== EJECUTANDO CORRECCIÓN ===\n";

    // Corregir el enum
    $sql = "ALTER TABLE users MODIFY COLUMN user_type ENUM('turista', 'alojamiento', 'promotor_eventos', 'actividad_cultural') NULL DEFAULT 'turista'";
    echo "Ejecutando: $sql\n";

    $pdo->exec($sql);
    echo "✅ Enum corregido exitosamente\n";

    // Verificar resultado
    echo "\n=== ESTRUCTURA CORREGIDA ===\n";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        if ($col['Field'] === 'user_type') {
            echo "user_type: {$col['Type']} " . ($col['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . " " . ($col['Default'] ? "DEFAULT '{$col['Default']}'" : '') . "\n";
            break;
        }
    }

    echo "\n🎉 ¡user_type CORREGIDO!\n";
    echo "Ahora los registros deberían guardar el tipo de usuario correctamente.\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>
