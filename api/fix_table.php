<?php
/**
 * Script para corregir la estructura de la tabla user_preferences
 */

require_once 'config.php';

echo "<h1>Corregir Tabla user_preferences</h1>";
echo "<pre>";

try {
    $pdo = getDBConnection();
    echo "✅ Conexión a BD exitosa\n\n";

    // Verificar estructura actual
    echo "=== ESTRUCTURA ACTUAL ===\n";
    $stmt = $pdo->query("DESCRIBE user_preferences");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        echo "{$col['Field']}: {$col['Type']} " . ($col['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . "\n";
    }

    echo "\n=== VERIFICANDO COLUMNAS NECESARIAS ===\n";

    // Columnas que necesitamos
    $requiredColumns = ['interests', 'accommodation_types', 'budget', 'group_size', 'trip_duration'];
    $existingColumns = array_column($columns, 'Field');

    $missingColumns = array_diff($requiredColumns, $existingColumns);

    if (empty($missingColumns)) {
        echo "✅ Todas las columnas necesarias existen\n";
    } else {
        echo "❌ Columnas faltantes: " . implode(', ', $missingColumns) . "\n";
        echo "Agregando columnas faltantes...\n";

        // Agregar columnas faltantes
        $alterQueries = [];

        if (!in_array('interests', $existingColumns)) {
            $alterQueries[] = "ADD COLUMN interests JSON NULL AFTER user_id";
        }
        if (!in_array('accommodation_types', $existingColumns)) {
            $alterQueries[] = "ADD COLUMN accommodation_types JSON NULL AFTER interests";
        }
        if (!in_array('budget', $existingColumns)) {
            $alterQueries[] = "ADD COLUMN budget VARCHAR(20) NULL AFTER accommodation_types";
        }
        if (!in_array('group_size', $existingColumns)) {
            $alterQueries[] = "ADD COLUMN group_size VARCHAR(20) NULL AFTER budget";
        }
        if (!in_array('trip_duration', $existingColumns)) {
            $alterQueries[] = "ADD COLUMN trip_duration VARCHAR(20) NULL AFTER group_size";
        }

        if (!empty($alterQueries)) {
            $sql = "ALTER TABLE user_preferences " . implode(', ', $alterQueries);
            echo "Ejecutando: $sql\n";
            $pdo->exec($sql);
            echo "✅ Columnas agregadas exitosamente\n";
        }
    }

    echo "\n=== ESTRUCTURA FINAL ===\n";
    $stmt = $pdo->query("DESCRIBE user_preferences");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        echo "{$col['Field']}: {$col['Type']} " . ($col['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . "\n";
    }

    echo "\n🎉 ¡TABLA CORREGIDA EXITOSAMENTE!\n";
    echo "Ahora puedes guardar preferencias sin errores.\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>
