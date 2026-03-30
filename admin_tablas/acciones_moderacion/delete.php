<?php
/**
 * Eliminar Alojamiento
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

    // Verificar que existe
    $checkStmt = $pdo->prepare("SELECT id, name FROM accommodations WHERE id = ?");
    $checkStmt->execute([$accommodationId]);
    $item = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        echo json_encode(['success' => false, 'error' => 'Alojamiento no encontrado']);
        exit;
    }

    // Eliminar historial si existe
    try {
        $pdo->prepare("DELETE FROM accommodation_moderation_history WHERE accommodation_id = ?")
            ->execute([$accommodationId]);
    } catch (Exception $e) { /* tabla puede no existir */ }

    // Eliminar notificaciones si existen
    try {
        $pdo->prepare("DELETE FROM moderation_notifications WHERE accommodation_id = ?")
            ->execute([$accommodationId]);
    } catch (Exception $e) { /* tabla puede no existir */ }

    // Eliminar cambios pendientes si existen
    try {
        $pdo->prepare("DELETE FROM accommodation_pending_changes WHERE accommodation_id = ?")
            ->execute([$accommodationId]);
    } catch (Exception $e) { /* tabla puede no existir */ }

    // Eliminar de user_resources si existe
    try {
        $pdo->prepare("DELETE FROM user_resources WHERE resource_type = 'accommodation' AND resource_id = ?")
            ->execute([$accommodationId]);
    } catch (Exception $e) { /* tabla puede no existir */ }

    // Eliminar el alojamiento
    $pdo->prepare("DELETE FROM accommodations WHERE id = ?")->execute([$accommodationId]);

    echo json_encode([
        'success'          => true,
        'message'          => 'Alojamiento eliminado correctamente',
        'accommodation_id' => $accommodationId
    ]);

} catch (Exception $e) {
    error_log('delete.php Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error al eliminar: ' . $e->getMessage()]);
}
