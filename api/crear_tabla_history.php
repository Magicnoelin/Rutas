<?php
/**
 * Crear solo la tabla que falta: accommodation_moderation_history
 */
require_once 'config.php';

header('Content-Type: text/plain');

try {
    $pdo = getDBConnection();
    
    echo "=== CREANDO TABLA FALTANTE ===\n\n";
    
    // Verificar si ya existe
    $exists = $pdo->query("SHOW TABLES LIKE 'accommodation_moderation_history'")->rowCount() > 0;
    
    if ($exists) {
        echo "⚠️  La tabla accommodation_moderation_history YA existe\n";
    } else {
        echo "Creando tabla accommodation_moderation_history...\n";
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS accommodation_moderation_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                accommodation_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                performed_by INT NULL,
                previous_status VARCHAR(20) NULL,
                new_status VARCHAR(20) NULL,
                notes TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_accommodation (accommodation_id),
                INDEX idx_performed_by (performed_by),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "✅ Tabla creada correctamente\n";
    }
    
    echo "\n=== VERIFICANDO VISTA v_moderation_stats ===\n";
    
    // Crear la vista
    $pdo->exec("
        CREATE OR REPLACE VIEW v_moderation_stats AS
        SELECT 
            COUNT(*) as total_count,
            COUNT(CASE WHEN moderation_status = 'pending' THEN 1 END) as pending_count,
            COUNT(CASE WHEN moderation_status = 'approved' THEN 1 END) as approved_count,
            COUNT(CASE WHEN moderation_status = 'rejected' THEN 1 END) as rejected_count,
            COUNT(CASE WHEN moderation_status = 'draft' THEN 1 END) as draft_count,
            COUNT(CASE WHEN has_pending_changes = 1 THEN 1 END) as pending_changes_count
        FROM accommodations
    ");
    echo "✅ Vista creada/actualizada\n";
    
    echo "\n=== VERIFICANDO RESULTADO ===\n";
    
    $tables = ['accommodation_moderation_history', 'accommodation_pending_changes', 'moderation_notifications'];
    foreach ($tables as $table) {
        $exists = $pdo->query("SHOW TABLES LIKE '$table'")->rowCount() > 0;
        echo ($exists ? "✅" : "❌") . " $table\n";
    }
    
    echo "\n✅ ¡Listo! Ahora el panel de moderación debería funcionar correctamente.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
