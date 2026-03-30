<?php
/**
 * Archivar Alojamiento - Lo oculta de la cola de moderación sin borrarlo
 * El alojamiento sigue existiendo en la BD (para no romper URLs de Google)
 * pero no aparece en la pantalla de moderación.
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

    $accommodationId = intval($data['accommodation_id']);
    $adminId         = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 1;

    // Verificar que existe
    $checkStmt = $pdo->prepare("SELECT id, name FROM accommodations WHERE id = ?");
    $checkStmt->execute([$accommodationId]);
    $item = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        echo json_encode(['success' => false, 'error' => 'Alojamiento no encontrado']);
        exit;
    }

    // Marcar como archivado: is_active=0, moderation_status='rejected', has_pending_changes=0
    // Usamos 'rejected' porque es un valor válido del ENUM y lo excluye de la cola
    // El alojamiento sigue en la BD con su URL intacta
    $updateStmt = $pdo->prepare("
        UPDATE accommodations 
        SET moderation_status = 'rejected',
            is_active = 0,
            has_pending_changes = 0,
            reviewed_by = ?,
            reviewed_at = NOW(),
            rejection_reason = 'Archivado por administrador - no se muestra en moderación'
        WHERE id = ?
    ");
    $updateStmt->execute([$adminId, $accommodationId]);

    echo json_encode([
        'success'          => true,
        'message'          => 'Alojamiento archivado. Ya no aparecerá en la cola de moderación.',
        'accommodation_id' => $accommodationId
    ]);

} catch (Exception $e) {
    error_log('archive.php Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error al archivar: ' . $e->getMessage()]);
}
