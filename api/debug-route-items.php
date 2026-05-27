<?php
/**
 * DEBUG: Ver todos los items de una ruta
 * Uso: /api/debug-route-items.php?route_id=10
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';

$route_id = (int)($_GET['route_id'] ?? 0);
if (!$route_id) { echo json_encode(['error' => 'Falta route_id']); exit; }

try {
    $pdo = getDBConnection();
    
    // Ruta
    $stmt = $pdo->prepare("SELECT id, name, slug, province FROM routes WHERE id = ?");
    $stmt->execute([$route_id]);
    $ruta = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$ruta) { echo json_encode(['error' => 'Ruta no encontrada']); exit; }
    
    // Items
    $stmt = $pdo->prepare("SELECT * FROM route_items WHERE route_id = ? ORDER BY day_number, display_order");
    $stmt->execute([$route_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [
        'ruta' => $ruta,
        'total_items' => count($items),
        'items' => []
    ];
    
    foreach ($items as $item) {
        $entry = $item;
        $entry['item_data'] = null;
        
        // Obtener datos del item según tipo
        $t = $item['item_type'];
        $iid = $item['item_id'];
        
        if (in_array($t, ['event', 'evento'])) {
            $s = $pdo->prepare("SELECT id, name, slug, poster_image, photo1 FROM cultural_events WHERE id = ?");
            $s->execute([$iid]);
            $data = $s->fetch(PDO::FETCH_ASSOC);
            if ($data) {
                $img = $data['poster_image'] ?: $data['photo1'] ?: null;
                $data['imagen_construida'] = null;
                if ($img) {
                    if (preg_match('/^https?:\/\//', $img)) {
                        $data['imagen_construida'] = $img;
                    } else {
                        $data['imagen_construida'] = 'https://rutasrurales.io/cultural_events_images/' . basename($img);
                    }
                }
            }
            $entry['item_data'] = $data;
        } elseif (in_array($t, ['accommodation', 'alojamiento'])) {
            $s = $pdo->prepare("SELECT id, name, slug, photo1 FROM accommodations WHERE id = ?");
            $s->execute([$iid]);
            $entry['item_data'] = $s->fetch(PDO::FETCH_ASSOC);
        } elseif (in_array($t, ['place', 'lugar'])) {
            $s = $pdo->prepare("SELECT id, name, slug, photo1 FROM places_of_interest WHERE id = ?");
            $s->execute([$iid]);
            $entry['item_data'] = $s->fetch(PDO::FETCH_ASSOC);
        } elseif (in_array($t, ['activity', 'actividad'])) {
            $s = $pdo->prepare("SELECT id, name, slug, photo1 FROM tourist_activities WHERE id = ?");
            $s->execute([$iid]);
            $entry['item_data'] = $s->fetch(PDO::FETCH_ASSOC);
        }
        
        $result['items'][] = $entry;
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
