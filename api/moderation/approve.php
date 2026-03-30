<?php
/**
 * API Endpoint: Aprobar Contenido (Alojamientos, Eventos, Actividades, Lugares)
 * POST /api/moderation/approve.php
 * Body: { content_type, content_id, admin_notes }
 * Requiere: Admin autenticado
 */

require_once '../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$adminId = $_SESSION['user_id'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

try {
    $pdo = getDBConnection();
    
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        jsonError('Error al decodificar JSON: ' . json_last_error_msg(), 400);
    }
    
    // Aceptar tanto accommodation_id (legacy) como content_type + content_id (nuevo)
    if (isset($data['accommodation_id'])) {
        $contentType = 'accommodation';
        $contentId = intval($data['accommodation_id']);
    } elseif (isset($data['content_type']) && isset($data['content_id'])) {
        $contentType = $data['content_type'];
        $contentId = intval($data['content_id']);
    } else {
        jsonError('Parámetros requeridos: content_type + content_id', 400);
    }
    
    $adminNotes = isset($data['admin_notes']) ? sanitizeInput($data['admin_notes']) : '';
    
    $tables = [
        'accommodation' => 'accommodations',
        'event'         => 'cultural_events',
        'activity'      => 'activities',
        'place'        => 'places_of_interest'
    ];
    
    if (!isset($tables[$contentType])) {
        jsonError('Tipo de contenido no válido: ' . $contentType, 400);
    }
    
    $table = $tables[$contentType];
    $idField = $contentType === 'accommodation' ? 'id' : 'id';
    
    // Verificar que existe
    $checkStmt = $pdo->prepare("SELECT id, moderation_status, created_by FROM `{$table}` WHERE id = ?");
    $checkStmt->execute([$contentId]);
    $item = $checkStmt->fetch();
    
    if (!$item) {
        jsonError(ucfirst($contentType) . ' no encontrado', 404);
    }
    
    $previousStatus = $item['moderation_status'] ?? 'draft';
    $userId = $item['created_by'];
    
    // Actualizar estado
    $updateStmt = $pdo->prepare("
        UPDATE `{$table}` 
        SET moderation_status = 'approved',
            is_active = 1,
            reviewed_by = ?,
            reviewed_at = NOW(),
            published_at = COALESCE(published_at, NOW()),
            rejection_reason = NULL
        WHERE id = ?
    ");
    $updateStmt->execute([$adminId, $contentId]);
    
    // Registrar en historial - usar tabla específica del tipo de contenido
    try {
        $historyTable = $contentType === 'accommodation' ? 'accommodation_moderation_history' : 'content_moderation_history';
        $tableExists = $pdo->query("SHOW TABLES LIKE '{$historyTable}'")->rowCount() > 0;
        
        if ($tableExists && $contentType === 'accommodation') {
            $historyStmt = $pdo->prepare("
                INSERT INTO accommodation_moderation_history 
                    (accommodation_id, action, performed_by, previous_status, new_status, notes)
                VALUES (?, 'approved', ?, ?, 'approved', ?)
            ");
            $historyStmt->execute([$contentId, $adminId, $previousStatus, $adminNotes]);
        }
    } catch (Exception $e) {
        error_log('Historial: ' . $e->getMessage());
    }
    
    // Crear notificación solo para alojamientos (tabla legacy)
    $notifTable = 'moderation_notifications';
    try {
        $notifTableExists = $pdo->query("SHOW TABLES LIKE '{$notifTable}'")->rowCount() > 0;
        
        if ($notifTableExists && $userId) {
            $typeLabels = [
                'accommodation' => 'alojamiento',
                'event'         => 'evento',
                'activity'      => 'actividad',
                'place'         => 'lugar'
            ];
            $typeLabel = $typeLabels[$contentType] ?? $contentType;
            
            // La tabla moderation_notifications tiene accommodation_id
            $notifStmt = $pdo->prepare("
                INSERT INTO moderation_notifications 
                    (user_id, accommodation_id, notification_type, title, message)
                VALUES (?, ?, 'approved', ?, ?)
            ");
            $notifStmt->execute([
                $userId,
                $contentId,
                '¡Alojamiento Aprobado!',
                'Tu alojamiento ha sido aprobado y ahora es visible para todos.'
            ]);
        }
    } catch (Exception $e) {
        error_log('Notificación: ' . $e->getMessage());
    }
    
    jsonSuccess([
        'content_type' => $contentType,
        'content_id' => $contentId,
        'new_status' => 'approved',
        'previous_status' => $previousStatus
    ], ucfirst($typeLabel ?? $contentType) . ' aprobado correctamente');
    
} catch (PDOException $e) {
    error_log('Error en approve.php: ' . $e->getMessage());
    jsonError('Error al aprobar: ' . $e->getMessage(), 500);
}
