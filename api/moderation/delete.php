<?php
/**
 * API Endpoint: Eliminar Alojamiento
 * POST /api/moderation/delete.php
 * Body: { accommodation_id: int }
 */

require_once '../config.php';

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

try {
    // Obtener datos
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);

    if (empty($data) || !isset($data['accommodation_id'])) {
        jsonError('Falta el ID del alojamiento', 400);
    }

    $accommodationId = intval($data['accommodation_id']);

    if ($accommodationId <= 0) {
        jsonError('ID de alojamiento inválido', 400);
    }

    $pdo = getDBConnection();

    // Obtener info del alojamiento y usuario
    $stmt = $pdo->prepare("
        SELECT a.*, u.id as user_id 
        FROM accommodations a 
        LEFT JOIN users u ON a.created_by = u.id 
        WHERE a.id = ?
    ");
    $stmt->execute([$accommodationId]);
    $accommodation = $stmt->fetch();

    if (!$accommodation) {
        jsonError('Alojamiento no encontrado', 404);
    }

    $userId = $accommodation['created_by'];

    // Iniciar transacción
    $pdo->beginTransaction();

    try {
        // 1. Eliminar de user_resources
        $stmt = $pdo->prepare("DELETE FROM user_resources WHERE resource_type = 'accommodation' AND resource_id = ?");
        $stmt->execute([$accommodationId]);

        // 2. Eliminar de accommodation_moderation_history
        $stmt = $pdo->prepare("DELETE FROM accommodation_moderation_history WHERE accommodation_id = ?");
        $stmt->execute([$accommodationId]);

        // 3. Eliminar de accommodation_pending_changes
        $stmt = $pdo->prepare("DELETE FROM accommodation_pending_changes WHERE accommodation_id = ?");
        $stmt->execute([$accommodationId]);

        // 4. Eliminar de resource_stats
        $stmt = $pdo->prepare("DELETE FROM resource_stats WHERE resource_type = 'accommodation' AND resource_id = ?");
        $stmt->execute([$accommodationId]);

        // 5. Eliminar el alojamiento
        $stmt = $pdo->prepare("DELETE FROM accommodations WHERE id = ?");
        $stmt->execute([$accommodationId]);

        // 6. Verificar si el usuario tiene más alojamientos
        if ($userId) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM accommodations WHERE created_by = ?");
            $stmt->execute([$userId]);
            $remainingCount = $stmt->fetch()['cnt'];

            // Si el usuario no tiene más alojamientos, NO eliminar el usuario
            // (podría tener otras membresías o datos importantes)
            // Pero podemos marcarlo como inactivo si se desea
        }

        // Confirmar transacción
        $pdo->commit();

        jsonSuccess([
            'deleted_id' => $accommodationId,
            'name' => $accommodation['name'],
            'user_id' => $userId,
            'message' => 'Alojamiento eliminado correctamente'
        ], 'Alojamiento eliminado');

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    error_log('Delete.php - Error: ' . $e->getMessage());
    jsonError('Error al eliminar alojamiento: ' . $e->getMessage(), 500);
}
