<?php
/**
 * API Endpoint: Obtener Detalles de Alojamiento para Moderación
 * Ubicación: admin_tablas/acciones_moderacion/get_details.php
 */

// 1. Ajuste de ruta: Si config.php está en la raíz de admin_tablas, usamos '../config.php'
// Si config.php está fuera de admin_tablas, usa '../../config.php'
require_once __DIR__ . '/../../config.php';

// Funciones de ayuda por si no están definidas en tu config.php
if (!function_exists('jsonError')) {
    function jsonError($message, $code = 400) {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode(['success' => false, 'error' => $message]);
        exit;
    }
}

if (!function_exists('jsonSuccess')) {
    function jsonSuccess($data, $message = '') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $data, 'message' => $message]);
        exit;
    }
}

// Solo permitir método GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Método no permitido', 405);
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    jsonError('ID de alojamiento requerido', 400);
}

try {
    // Asumimos que getDBConnection() viene de tu config.php
    $pdo = getDBConnection();
    $accommodationId = intval($_GET['id']);
    
    // 1. Obtener datos del alojamiento
    $stmt = $pdo->prepare("
        SELECT a.*, 
               u.first_name, u.last_name, u.email as user_email, u.phone as user_phone
        FROM accommodations a
        LEFT JOIN users u ON a.created_by = u.id
        WHERE a.id = ?
    ");
    $stmt->execute([$accommodationId]);
    $accommodation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$accommodation) {
        jsonError('Alojamiento no encontrado', 404);
    }
    
    // 2. Obtener cambios pendientes
    $pendingChanges = null;
    // Verificamos si la columna existe antes de usarla para evitar errores de SQL
    $hasPendingColumn = isset($accommodation['has_pending_changes']) ? $accommodation['has_pending_changes'] : false;

    if ($hasPendingColumn) {
        $pendingStmt = $pdo->prepare("
            SELECT * FROM accommodation_pending_changes 
            WHERE accommodation_id = ? AND status = 'pending'
            ORDER BY submitted_at DESC LIMIT 1
        ");
        $pendingStmt->execute([$accommodationId]);
        $pendingChange = $pendingStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($pendingChange) {
            $newData = json_decode($pendingChange['pending_data'], true) ?: [];
            $oldData = json_decode($pendingChange['previous_data'], true) ?: [];
            
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
    
    // 3. Obtener historial (con manejo de error por si la tabla no existe aún)
    $history = [];
    try {
        $historyStmt = $pdo->prepare("
            SELECT h.*, u.first_name, u.last_name
            FROM accommodation_moderation_history h
            LEFT JOIN users u ON h.performed_by = u.id
            WHERE h.accommodation_id = ?
            ORDER BY h.created_at DESC LIMIT 5
        ");
        $historyStmt->execute([$accommodationId]);
        $history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $history = []; // Si falla la tabla de historial, simplemente enviamos vacío
    }
    
    jsonSuccess([
        'accommodation' => $accommodation,
        'pending_changes' => $pendingChanges,
        'history' => $history
    ], 'Detalles obtenidos correctamente');
    
} catch (PDOException $e) {
    error_log('Error en get_details.php: ' . $e->getMessage());
    jsonError('Error de base de datos: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    jsonError('Error general: ' . $e->getMessage(), 500);
}
