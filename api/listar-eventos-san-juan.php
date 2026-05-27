<?php
/**
 * Listar eventos de San Juan en Soria para añadirlos a la ruta
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';

try {
    $pdo = getDBConnection();
    
    // Buscar eventos relacionados con San Juan en Soria
    $stmt = $pdo->prepare("
        SELECT id, name, slug, poster_image, photo1, start_date, end_date, municipality, is_active
        FROM cultural_events 
        WHERE (name LIKE '%san juan%' OR name LIKE '%San Juan%' OR name LIKE '%sanjuán%' OR name LIKE '%Sanjuán%')
          AND province = 'Soria'
          AND is_active = 1
        ORDER BY start_date ASC
    ");
    $stmt->execute();
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'total' => count($eventos),
        'eventos' => $eventos
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
