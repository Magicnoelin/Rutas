<?php
/**
 * API: Estadísticas de visitas para lugares de interés
 * 
 * GET /api/place_of_interest-visitas.php?place_id=123
 * 
 * Devuelve el número de visitas (views_count) del lugar
 * y otras estadísticas relacionadas
 */

require_once 'config.php';

// ── GET: Obtener estadísticas del lugar ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $placeId = isset($_GET['place_id']) ? intval($_GET['place_id']) : 0;
    
    if ($placeId <= 0) {
        jsonError('place_id es requerido', 400);
    }
    
    try {
        $pdo = getDBConnection();
        
        // Verificar si la tabla resource_stats existe
        $tableExists = false;
        try {
            $pdo->query("SELECT 1 FROM resource_stats LIMIT 1");
            $tableExists = true;
        } catch (PDOException $e) {
            // La tabla no existe, crear estadísticas vacías
            $tableExists = false;
        }
        
        if ($tableExists) {
            // Obtener estadísticas de resource_stats
            $stmt = $pdo->prepare("
                SELECT views_count, favorites_count, interests_count, messages_count, last_view_at
                FROM resource_stats
                WHERE resource_type = 'place' AND resource_id = :place_id
            ");
            $stmt->execute(['place_id' => $placeId]);
            $stats = $stmt->fetch();
            
            if ($stats) {
                jsonSuccess([
                    'place_id'        => $placeId,
                    'views_count'     => (int)$stats['views_count'],
                    'favorites_count' => (int)$stats['favorites_count'],
                    'interests_count' => (int)$stats['interests_count'],
                    'messages_count'  => (int)$stats['messages_count'],
                    'last_view_at'    => $stats['last_view_at']
                ], 'Estadísticas obtenidas');
            } else {
                // No hay estadísticas aún, devolver ceros
                jsonSuccess([
                    'place_id'        => $placeId,
                    'views_count'     => 0,
                    'favorites_count' => 0,
                    'interests_count' => 0,
                    'messages_count'  => 0,
                    'last_view_at'    => null
                ], 'Sin estadísticas aún');
            }
        } else {
            // Tabla no existe, devolver ceros
            jsonSuccess([
                'place_id'        => $placeId,
                'views_count'     => 0,
                'favorites_count' => 0,
                'interests_count' => 0,
                'messages_count'  => 0,
                'last_view_at'    => null
            ], 'Sin estadísticas aún');
        }
        
    } catch (PDOException $e) {
        error_log('place_of_interest-visitas.php Error: ' . $e->getMessage());
        jsonError('Error al obtener estadísticas', 500);
    }
} else {
    jsonError('Método no permitido', 405);
}