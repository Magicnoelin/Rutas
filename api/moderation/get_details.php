<?php
/**
 * API Endpoint: Obtener Detalles de Alojamiento para Moderación
 * GET /api/moderation/get_details.php?id=XXX
 * Requiere: Admin autenticado
 */

require_once '../config.php';

// La autenticación se maneja por .htaccess en la carpeta admin_tablas
// No necesitamos verificar sesión PHP aquí

// Solo permitir método GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Método no permitido', 405);
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    jsonError('ID de alojamiento requerido', 400);
}

try {
    $pdo = getDBConnection();
    $accommodationId = intval($_GET['id']);
    
    // Obtener datos del alojamiento
    $stmt = $pdo->prepare("
        SELECT a.*, 
               u.first_name, u.last_name, u.email as user_email, u.phone as user_phone,
               reviewer.first_name as reviewer_first_name, 
               reviewer.last_name as reviewer_last_name
        FROM accommodations a
        LEFT JOIN users u ON a.created_by = u.id
        LEFT JOIN users reviewer ON a.reviewed_by = reviewer.id
        WHERE a.id = ?
    ");
    $stmt->execute([$accommodationId]);
    $accommodation = $stmt->fetch();
    
    if (!$accommodation) {
        jsonError('Alojamiento no encontrado', 404);
    }
    
    // Obtener cambios pendientes si existen
    $pendingChanges = null;
    if ($accommodation['has_pending_changes']) {
        $pendingStmt = $pdo->prepare("
            SELECT * FROM accommodation_pending_changes 
            WHERE accommodation_id = ? AND status = 'pending'
            ORDER BY submitted_at DESC LIMIT 1
        ");
        $pendingStmt->execute([$accommodationId]);
        $pendingChange = $pendingStmt->fetch();
        
        if ($pendingChange) {
            $newData = json_decode($pendingChange['pending_data'], true);
            $oldData = json_decode($pendingChange['previous_data'], true);
            
            // Calcular diferencias
            $changes = [];
            foreach ($newData as $key => $newValue) {
                $oldValue = $oldData[$key] ?? null;
                if ($newValue != $oldValue) {
                    $changes[$key] = [
                        'old' => $oldValue,
                        'new' => $newValue
                    ];
                }
            }
            
            $pendingChanges = [
                'id' => $pendingChange['id'],
                'change_type' => $pendingChange['change_type'],
                'submitted_at' => $pendingChange['submitted_at'],
                'data' => $newData,
                'previous_data' => $oldData,
                'changes' => $changes
            ];
        }
    }
    
    // Obtener historial de moderación
    $historyStmt = $pdo->prepare("
        SELECT h.*, u.first_name, u.last_name
        FROM accommodation_moderation_history h
        LEFT JOIN users u ON h.performed_by = u.id
        WHERE h.accommodation_id = ?
        ORDER BY h.created_at DESC
        LIMIT 10
    ");
    $historyStmt->execute([$accommodationId]);
    $history = $historyStmt->fetchAll();
    
    // Obtener estadísticas del recurso
    $statsStmt = $pdo->prepare("
        SELECT * FROM resource_stats 
        WHERE resource_type = 'accommodation' AND resource_id = ?
    ");
    $statsStmt->execute([$accommodationId]);
    $stats = $statsStmt->fetch();
    
    jsonSuccess([
        'accommodation' => $accommodation,
        'pending_changes' => $pendingChanges,
        'history' => $history,
        'stats' => $stats
    ], 'Detalles obtenidos correctamente');
    
} catch (PDOException $e) {
    error_log('Error en get_details.php: ' . $e->getMessage());
    jsonError('Error al obtener detalles: ' . $e->getMessage(), 500);
}
