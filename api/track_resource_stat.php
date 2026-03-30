<?php
/**
 * API: Trackear estadísticas de recursos
 * POST /api/track_resource_stat.php
 * Body: { resource_type, resource_id, stat_type }
 * stat_type: 'view', 'interest', 'message', 'favorite'
 */

require_once 'config.php';

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

try {
    // Obtener datos del body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        jsonError('Datos JSON inválidos', 400);
    }
    
    // Validar campos requeridos
    if (!isset($data['resource_type']) || !isset($data['resource_id']) || !isset($data['stat_type'])) {
        jsonError('Faltan campos requeridos: resource_type, resource_id, stat_type', 400);
    }
    
    $resourceType = sanitizeInput($data['resource_type']);
    $resourceId = (int)$data['resource_id'];
    $statType = sanitizeInput($data['stat_type']);
    
    // Validar resource_type
    $validTypes = ['accommodation', 'place', 'activity', 'event'];
    if (!in_array($resourceType, $validTypes)) {
        jsonError('Tipo de recurso inválido', 400);
    }
    
    // Validar stat_type
    $validStats = ['view', 'interest', 'message', 'favorite'];
    if (!in_array($statType, $validStats)) {
        jsonError('Tipo de estadística inválido', 400);
    }
    
    $pdo = getDBConnection();
    
    // Crear o actualizar estadística
    $stmtUpsert = $pdo->prepare("
        INSERT INTO resource_stats (resource_type, resource_id)
        VALUES (:resource_type, :resource_id)
        ON DUPLICATE KEY UPDATE id = id
    ");
    $stmtUpsert->execute([
        'resource_type' => $resourceType,
        'resource_id' => $resourceId
    ]);
    
    // Incrementar contador según tipo
    $field = '';
    $dateField = '';
    
    switch ($statType) {
        case 'view':
            $field = 'views_count';
            $dateField = 'last_view_at';
            break;
        case 'interest':
            $field = 'interests_count';
            $dateField = 'last_interest_at';
            break;
        case 'message':
            $field = 'messages_count';
            $dateField = 'last_message_at';
            break;
        case 'favorite':
            $field = 'favorites_count';
            $dateField = null; // No tiene campo de fecha
            break;
    }
    
    if ($dateField) {
        $stmtUpdate = $pdo->prepare("
            UPDATE resource_stats
            SET $field = $field + 1,
                $dateField = CURRENT_TIMESTAMP,
                updated_at = CURRENT_TIMESTAMP
            WHERE resource_type = :resource_type
            AND resource_id = :resource_id
        ");
    } else {
        $stmtUpdate = $pdo->prepare("
            UPDATE resource_stats
            SET $field = $field + 1,
                updated_at = CURRENT_TIMESTAMP
            WHERE resource_type = :resource_type
            AND resource_id = :resource_id
        ");
    }
    
    $stmtUpdate->execute([
        'resource_type' => $resourceType,
        'resource_id' => $resourceId
    ]);
    
    // Obtener estadísticas actualizadas
    $stmtGet = $pdo->prepare("
        SELECT * FROM resource_stats
        WHERE resource_type = :resource_type
        AND resource_id = :resource_id
    ");
    $stmtGet->execute([
        'resource_type' => $resourceType,
        'resource_id' => $resourceId
    ]);
    $stats = $stmtGet->fetch();
    
    jsonSuccess(['stats' => $stats], 'Estadística registrada');
    
} catch (PDOException $e) {
    error_log('track_resource_stat.php Error: ' . $e->getMessage());
    jsonError('Error al registrar estadística', 500);
}
