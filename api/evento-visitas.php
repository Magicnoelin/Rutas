<?php
/**
 * API: Obtener estadísticas de visitas de un evento
 * GET /api/evento-visitas.php?event_id=123
 * 
 * Devuelve el número de visitas (views_count) del evento
 */

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Método no permitido', 405);
}

$eventId = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

if ($eventId <= 0) {
    jsonError('event_id es requerido y debe ser un número positivo', 400);
}

try {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT views_count, favorites_count, interests_count, last_view_at
        FROM resource_stats
        WHERE resource_type = 'event' AND resource_id = :event_id
        LIMIT 1
    ");
    $stmt->execute(['event_id' => $eventId]);
    $stats = $stmt->fetch();
    
    if ($stats) {
        jsonSuccess([
            'event_id'        => $eventId,
            'views_count'     => (int)$stats['views_count'],
            'favorites_count' => (int)$stats['favorites_count'],
            'interests_count' => (int)$stats['interests_count'],
            'last_view_at'    => $stats['last_view_at']
        ], 'Estadísticas obtenidas');
    } else {
        // No hay registros aún, devolver 0
        jsonSuccess([
            'event_id'        => $eventId,
            'views_count'     => 0,
            'favorites_count' => 0,
            'interests_count' => 0,
            'last_view_at'    => null
        ], 'Sin estadísticas aún');
    }
    
} catch (PDOException $e) {
    error_log('evento-visitas.php Error: ' . $e->getMessage());
    jsonError('Error al obtener estadísticas', 500);
}
