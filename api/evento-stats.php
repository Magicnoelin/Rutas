<?php
/**
 * API: Estadísticas de eventos (visitas y likes reales)
 * GET /api/evento-stats.php?slug={slug}
 * POST /api/evento-stats.php (registrar vista o like)
 * 
 * Body POST: { slug, action: 'view'|'like'|'unlike' }
 */

define('API_NO_HEADERS', true);
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $pdo = getDBConnection();
    
    /**
     * Buscar un evento por slug, incluyendo slugs traducidos (cultural_events_trads)
     * @param PDO $pdo
     * @param string $slug
     * @return array|null Array con 'id' del evento o null si no se encuentra
     */
    function findEventBySlug($pdo, $slug) {
        // Buscar primero en cultural_events (slug original en español)
        $stmt = $pdo->prepare("SELECT id FROM cultural_events WHERE slug = ? AND is_active = 1");
        $stmt->execute([$slug]);
        $evento = $stmt->fetch();
        if ($evento) return $evento;
        
        // Si no se encuentra, buscar en cultural_events_trads (slug traducido)
        $stmt = $pdo->prepare("
            SELECT e.id FROM cultural_events e
            INNER JOIN cultural_events_trads t ON t.event_id = e.id
            WHERE t.slug = ? AND e.is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }
    
    // ─── GET: Obtener estadísticas ────────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
        
        if (empty($slug)) {
            http_response_code(400);
            echo json_encode(['error' => 'Slug requerido']);
            exit;
        }
        
        // Buscar evento por slug (incluye slugs traducidos)
        $evento = findEventBySlug($pdo, $slug);
        
        if (!$evento) {
            http_response_code(404);
            echo json_encode(['error' => 'Evento no encontrado']);
            exit;
        }
        
        $eventId = $evento['id'];
        
        // Obtener o crear registro en resource_stats
        $stmtStats = $pdo->prepare("
            SELECT views_count, favorites_count 
            FROM resource_stats 
            WHERE resource_type = 'event' AND resource_id = ?
        ");
        $stmtStats->execute([$eventId]);
        $stats = $stmtStats->fetch();
        
        $views = $stats ? (int)$stats['views_count'] : 0;
        $likes = $stats ? (int)$stats['favorites_count'] : 0;
        
        echo json_encode([
            'success' => true,
            'data' => [
                'views' => $views,
                'likes' => $likes,
                'event_id' => $eventId
            ]
        ]);
        exit;
    }
    
    // ─── POST: Registrar acción (vista o like) ─────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (!$data || !isset($data['slug']) || !isset($data['action'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Datos inválidos: se requiere slug y action']);
            exit;
        }
        
        $slug = trim($data['slug']);
        $action = $data['action'];
        
        // Validar acción
        if (!in_array($action, ['view', 'like', 'unlike'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Acción inválida']);
            exit;
        }
        
        // Obtener ID del evento (incluye slugs traducidos)
        $evento = findEventBySlug($pdo, $slug);
        
        if (!$evento) {
            http_response_code(404);
            echo json_encode(['error' => 'Evento no encontrado']);
            exit;
        }
        
        $eventId = $evento['id'];
        
        // Crear o actualizar registro en resource_stats
        $stmtUpsert = $pdo->prepare("
            INSERT INTO resource_stats (resource_type, resource_id, views_count, favorites_count, last_view_at)
            VALUES ('event', :event_id, 0, 0, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE 
                id = id,
                views_count = COALESCE(views_count, 0),
                favorites_count = COALESCE(favorites_count, 0)
        ");
        $stmtUpsert->execute(['event_id' => $eventId]);
        
        // Procesar acción
        if ($action === 'view') {
            // Registrar visita (solo una vez por sesión, verificar con cookie)
            $sessionKey = 'event_viewed_' . $slug;
            if (!isset($_COOKIE[$sessionKey])) {
                $stmtUpdate = $pdo->prepare("
                    UPDATE resource_stats 
                    SET views_count = COALESCE(views_count, 0) + 1,
                        last_view_at = CURRENT_TIMESTAMP
                    WHERE resource_type = 'event' AND resource_id = ?
                ");
                $stmtUpdate->execute([$eventId]);
                
                // También actualizar en cultural_events si existe la columna
                try {
                    $pdo->prepare("UPDATE cultural_events SET views = COALESCE(views, 0) + 1 WHERE id = ?")->execute([$eventId]);
                } catch (Exception $e) { /* columna views puede no existir */ }
                
                // Registrar vista en log para estadísticas por fecha
                $pdo->prepare("INSERT IGNORE INTO page_views_log (resource_type, resource_id, viewed_at) VALUES ('event', ?, NOW())")->execute([$eventId]);
                
                // Crear cookie para evitar contar múltiples visitas del mismo usuario
                setcookie($sessionKey, '1', time() + 86400, '/');
            }
        }
        elseif ($action === 'like') {
            $stmtUpdate = $pdo->prepare("
                UPDATE resource_stats 
                SET favorites_count = COALESCE(favorites_count, 0) + 1
                WHERE resource_type = 'event' AND resource_id = ?
            ");
            $stmtUpdate->execute([$eventId]);
        }
        elseif ($action === 'unlike') {
            $stmtUpdate = $pdo->prepare("
                UPDATE resource_stats 
                SET favorites_count = GREATEST(COALESCE(favorites_count, 0) - 1, 0)
                WHERE resource_type = 'event' AND resource_id = ?
            ");
            $stmtUpdate->execute([$eventId]);
        }
        
        // Obtener estadísticas actualizadas
        $stmtGet = $pdo->prepare("
            SELECT views_count, favorites_count 
            FROM resource_stats 
            WHERE resource_type = 'event' AND resource_id = ?
        ");
        $stmtGet->execute([$eventId]);
        $stats = $stmtGet->fetch();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'views' => (int)($stats['views_count'] ?? 0),
                'likes' => (int)($stats['favorites_count'] ?? 0)
            ]
        ]);
        exit;
    }
    
    // Método no permitido
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    
} catch (Exception $e) {
    error_log('evento-stats.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
