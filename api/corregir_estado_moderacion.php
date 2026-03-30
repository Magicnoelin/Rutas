<?php
/**
 * Corregir estado de moderación - Cambiar todos los draft a pending
 */
require_once 'config.php';

header('Content-Type: text/plain');

try {
    $pdo = getDBConnection();
    
    echo "=== ANTES DEL CAMBIO ===\n";
    
    $statsBefore = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN moderation_status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN moderation_status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN moderation_status = 'rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN moderation_status = 'draft' THEN 1 ELSE 0 END) as draft
        FROM accommodations
    ")->fetch();
    
    echo "Total: {$statsBefore['total']}\n";
    echo "Pending: {$statsBefore['pending']}\n";
    echo "Approved: {$statsBefore['approved']}\n";
    echo "Rejected: {$statsBefore['rejected']}\n";
    echo "Draft: {$statsBefore['draft']}\n\n";
    
    // Contar alojamientos en draft (sin contar los ya aprobados)
    $countDraft = $pdo->query("
        SELECT COUNT(*) as cnt 
        FROM accommodations 
        WHERE moderation_status = 'draft'
    ")->fetch()['cnt'];
    
    if ($countDraft > 0) {
        echo "=== ACTUALIZANDO {$countDraft} ALOJAMIENTOS DE 'draft' A 'pending' ===\n\n";
        
        // Actualizar todos los draft a pending
        $stmt = $pdo->prepare("
            UPDATE accommodations 
            SET 
                moderation_status = 'pending',
                last_submitted_at = COALESCE(last_submitted_at, NOW()),
                has_pending_changes = 1
            WHERE moderation_status = 'draft'
        ");
        $stmt->execute();
        
        echo "Registros actualizados: " . $stmt->rowCount() . "\n\n";
    } else {
        echo "=== NO HAY ALOJAMIENTOS EN draft PARA ACTUALIZAR ===\n\n";
    }
    
    echo "=== DESPUÉS DEL CAMBIO ===\n";
    
    $statsAfter = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN moderation_status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN moderation_status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN moderation_status = 'rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN moderation_status = 'draft' THEN 1 ELSE 0 END) as draft
        FROM accommodations
    ")->fetch();
    
    echo "Total: {$statsAfter['total']}\n";
    echo "Pending: {$statsAfter['pending']}\n";
    echo "Approved: {$statsAfter['approved']}\n";
    echo "Rejected: {$statsAfter['rejected']}\n";
    echo "Draft: {$statsAfter['draft']}\n\n";
    
    // Mostrar los que ahora deberían aparecer en moderación
    echo "=== ALOJAMIENTOS QUE AHORA APARECERÁN EN MODERACIÓN ===\n";
    
    $pending = $pdo->query("
        SELECT id, name, moderation_status, has_pending_changes, created_at
        FROM accommodations 
        WHERE moderation_status = 'pending' OR has_pending_changes = 1
        ORDER BY last_submitted_at DESC
        LIMIT 20
    ");
    
    while ($row = $pending->fetch()) {
        echo "ID: {$row['id']} | {$row['name']} | Estado: {$row['moderation_status']} | Cambios: {$row['has_pending_changes']} | Fecha: {$row['created_at']}\n";
    }
    
    echo "\n✅ ¡Listo! Ahora deberían aparecer en el panel de moderación.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
