<?php
/**
 * Verificar qué tablas de moderación existen
 */
require_once 'config.php';

header('Content-Type: text/plain');

try {
    $pdo = getDBConnection();
    
    echo "=== VERIFICANDO TABLAS DE MODERACIÓN ===\n\n";
    
    // Lista de tablas a verificar
    $tables = [
        'accommodations',
        'accommodation_moderation_history',
        'accommodation_pending_changes',
        'moderation_notifications',
        'users'
    ];
    
    foreach ($tables as $table) {
        $exists = $pdo->query("SHOW TABLES LIKE '$table'")->rowCount() > 0;
        echo ($exists ? "✅" : "❌") . " $table\n";
    }
    
    echo "\n=== VERIFICANDO VISTAS ===\n";
    
    $views = ['v_moderation_stats'];
    
    foreach ($views as $view) {
        try {
            $result = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_db = '$view'");
            $exists = $result->rowCount() > 0;
            echo ($exists ? "✅" : "❌") . " $view\n";
        } catch (Exception $e) {
            echo "❌ $view (error: " . $e->getMessage() . ")\n";
        }
    }
    
    echo "\n=== VERIFICANDO COLUMNAS DE accommodations ===\n";
    
    $columns = $pdo->query("SHOW COLUMNS FROM accommodations LIKE 'moderation_status'");
    if ($columns->rowCount() > 0) {
        echo "✅ Columna moderation_status existe\n";
    } else {
        echo "❌ Columna moderation_status NO existe\n";
    }
    
    $columns2 = $pdo->query("SHOW COLUMNS FROM accommodations LIKE 'has_pending_changes'");
    if ($columns2->rowCount() > 0) {
        echo "✅ Columna has_pending_changes existe\n";
    } else {
        echo "❌ Columna has_pending_changes NO existe\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
