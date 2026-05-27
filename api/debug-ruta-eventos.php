<?php
/**
 * DEBUG: Ver eventos de una ruta y sus imágenes
 * Uso: /api/debug-ruta-eventos.php?slug=ruta-fiestas-san-juan-soria-2026
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) { echo json_encode(['error' => 'Falta slug']); exit; }

try {
    $pdo = getDBConnection();
    
    // Buscar ruta por slug
    $stmt = $pdo->prepare("SELECT id, name, slug, province FROM routes WHERE slug = ?");
    $stmt->execute([$slug]);
    $ruta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ruta) { echo json_encode(['error' => 'Ruta no encontrada']); exit; }
    
    // Obtener items de tipo event/evento
    $stmt = $pdo->prepare("SELECT * FROM route_items WHERE route_id = ? AND item_type IN ('event','evento')");
    $stmt->execute([$ruta['id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $eventos = [];
    foreach ($items as $item) {
        $stmt2 = $pdo->prepare("SELECT id, name, slug, poster_image, photo1 FROM cultural_events WHERE id = ?");
        $stmt2->execute([$item['item_id']]);
        $ev = $stmt2->fetch(PDO::FETCH_ASSOC);
        if ($ev) {
            $ev['route_item_id'] = $item['id'];
            $ev['item_type'] = $item['item_type'];
            $ev['poster_image_raw'] = $ev['poster_image'];
            $ev['photo1_raw'] = $ev['photo1'];
            
            // Simular la lógica de construcción de URL
            $img = $ev['poster_image'] ?: $ev['photo1'] ?: null;
            $img_url = null;
            if ($img) {
                if (preg_match('/^https?:\/\//', $img)) {
                    $img_url = $img;
                } else {
                    $basename = basename($img);
                    $img_url = 'https://rutasrurales.io/cultural_events_images/' . $basename;
                }
            }
            $ev['imagen_construida'] = $img_url;
            
            // Verificar si el archivo existe
            $ev['archivo_existe_en_servidor'] = false;
            if ($img_url && !preg_match('/^https?:\/\//', $img)) {
                $local_path = __DIR__ . '/../cultural_events_images/' . basename($img);
                $ev['archivo_existe_en_servidor'] = file_exists($local_path);
            }
            
            $eventos[] = $ev;
        }
    }
    
    echo json_encode([
        'ruta' => $ruta,
        'total_items_evento' => count($items),
        'eventos' => $eventos
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
