<?php
/**
 * DEBUG: Ver fotos del evento san-pedro-regalado-valladolid-2026
 * BORRAR DESPUÉS DE USAR
 */
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDBConnection();

    // Buscar el evento
    $stmt = $pdo->query("SELECT id, name, slug, photo1, photo2, photo3, photo4 FROM cultural_events WHERE slug LIKE '%san-pedro-regalado%' LIMIT 3");
    $events = $stmt->fetchAll();

    // También ver cómo son las fotos por defecto en general
    $stmt2 = $pdo->query("SELECT id, name, slug, photo1 FROM cultural_events WHERE photo1 IS NOT NULL AND photo1 != '' LIMIT 5");
    $samples = $stmt2->fetchAll();

    echo json_encode([
        'target_event' => $events,
        'photo_samples' => $samples,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
