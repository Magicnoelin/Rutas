<?php
/**
 * Script para vincular alojamientos existentes con sus usuarios
 * Este script debe ejecutarse UNA VEZ después de crear la tabla user_resources
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDBConnection();
    
    // Verificar si la tabla user_resources existe
    $checkTable = $pdo->query("SHOW TABLES LIKE 'user_resources'");
    if ($checkTable->rowCount() === 0) {
        jsonError('La tabla user_resources no existe. Ejecuta primero el script crear_tabla_user_resources.sql', 500);
    }
    
    // Obtener todos los alojamientos que tienen un created_by
    $stmt = $pdo->query("
        SELECT id, created_by, name, email, manager_name 
        FROM accommodations 
        WHERE created_by IS NOT NULL 
        AND created_by > 0
    ");
    $alojamientos = $stmt->fetchAll();
    
    if (count($alojamientos) === 0) {
        jsonSuccess([
            'message' => 'No hay alojamientos con user_id para vincular',
            'vinculados' => 0
        ]);
    }
    
    $vinculados = 0;
    $errores = [];
    
    foreach ($alojamientos as $aloj) {
        try {
            // Verificar si ya existe la vinculación
            $checkStmt = $pdo->prepare("
                SELECT id FROM user_resources 
                WHERE user_id = ? 
                AND resource_type = 'accommodation' 
                AND resource_id = ?
            ");
            $checkStmt->execute([$aloj['created_by'], $aloj['id']]);
            
            if ($checkStmt->rowCount() === 0) {
                // No existe, crear vinculación
                $insertStmt = $pdo->prepare("
                    INSERT INTO user_resources (user_id, resource_type, resource_id, role, status)
                    VALUES (?, 'accommodation', ?, 'owner', 'active')
                ");
                $insertStmt->execute([$aloj['created_by'], $aloj['id']]);
                $vinculados++;
            }
        } catch (PDOException $e) {
            $errores[] = [
                'alojamiento_id' => $aloj['id'],
                'nombre' => $aloj['name'],
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Crear estadísticas iniciales para los recursos vinculados
    $pdo->exec("
        INSERT IGNORE INTO resource_stats (resource_type, resource_id, views_count, interests_count, messages_count, favorites_count)
        SELECT 'accommodation', id, 0, 0, 0, 0
        FROM accommodations
        WHERE created_by IS NOT NULL AND created_by > 0
    ");
    
    jsonSuccess([
        'message' => 'Vinculación completada',
        'total_alojamientos' => count($alojamientos),
        'vinculados' => $vinculados,
        'ya_existian' => count($alojamientos) - $vinculados,
        'errores' => $errores
    ]);
    
} catch (PDOException $e) {
    error_log('Error vinculando alojamientos: ' . $e->getMessage());
    jsonError('Error: ' . $e->getMessage(), 500);
}
