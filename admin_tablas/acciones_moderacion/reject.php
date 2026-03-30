<?php
/**
 * Rechazar Alojamiento
 * Usado por: admin_tablas/moderacion_alojamientos.php
 */

session_start();
require_once '../db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);

    if (!$data || !isset($data['accommodation_id'])) {
        echo json_encode(['success' => false, 'error' => 'accommodation_id requerido']);
        exit;
    }

    if (!isset($data['rejection_reason']) || empty(trim($data['rejection_reason']))) {
        echo json_encode(['success' => false, 'error' => 'Motivo de rechazo requerido']);
        exit;
    }

    $accommodationId = intval($data['accommodation_id']);
    $rejectionReason = trim($data['rejection_reason']);
    $adminNotes      = isset($data['admin_notes']) ? trim($data['admin_notes']) : '';
    $adminId         = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 1;

    // Verificar que existe
    $checkStmt = $pdo->prepare("SELECT id, moderation_status, created_by, name FROM accommodations WHERE id = ?");
    $checkStmt->execute([$accommodationId]);
    $item = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        echo json_encode(['success' => false, 'error' => 'Alojamiento no encontrado']);
        exit;
    }

    $previousStatus = $item['moderation_status'] ?? 'draft';
    $userId         = $item['created_by'];
    $itemName       = $item['name'];

    // Actualizar estado
    $updateStmt = $pdo->prepare("
        UPDATE accommodations 
        SET moderation_status = 'rejected',
            is_active = 0,
            has_pending_changes = 0,
            reviewed_by = ?,
            reviewed_at = NOW(),
            rejection_reason = ?
        WHERE id = ?
    ");
    $updateStmt->execute([$adminId, $rejectionReason, $accommodationId]);

    // Registrar en historial si la tabla existe
    try {
        $histExists = $pdo->query("SHOW TABLES LIKE 'accommodation_moderation_history'")->rowCount() > 0;
        if ($histExists) {
            $pdo->prepare("
                INSERT INTO accommodation_moderation_history 
                    (accommodation_id, action, performed_by, previous_status, new_status, notes, rejection_reason)
                VALUES (?, 'rejected', ?, ?, 'rejected', ?, ?)
            ")->execute([$accommodationId, $adminId, $previousStatus, $adminNotes, $rejectionReason]);
        }
    } catch (Exception $e) {
        error_log('Historial reject: ' . $e->getMessage());
    }

    // Notificación si la tabla existe
    try {
        $notifExists = $pdo->query("SHOW TABLES LIKE 'moderation_notifications'")->rowCount() > 0;
        if ($notifExists && $userId) {
            $pdo->prepare("
                INSERT INTO moderation_notifications 
                    (user_id, accommodation_id, notification_type, title, message)
                VALUES (?, ?, 'rejected', 'Alojamiento Requiere Correcciones', ?)
            ")->execute([
                $userId,
                $accommodationId,
                'Tu alojamiento "' . $itemName . '" necesita correcciones. Motivo: ' . $rejectionReason
            ]);
        }
    } catch (Exception $e) {
        error_log('Notificación reject: ' . $e->getMessage());
    }

    echo json_encode([
        'success'          => true,
        'message'          => 'Alojamiento rechazado correctamente',
        'accommodation_id' => $accommodationId,
        'new_status'       => 'rejected'
    ]);

} catch (Exception $e) {
    error_log('reject.php Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error al rechazar: ' . $e->getMessage()]);
}
