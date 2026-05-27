<?php
/**
 * SCRIPT DE REPARACIÓN: Regenera description_linked para un evento específico
 * 
 * Uso: php fix_event_description_linked.php
 * 
 * Este script regenera el campo description_linked del evento
 * "viernes-de-toros-soria-2026-san-juan" usando la función
 * procesarInboundLinks() mejorada que evita reemplazar keywords
 * dentro de atributos de enlaces HTML.
 */

require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/api/inbound_links_helper.php';

$slug = 'viernes-de-toros-soria-2026-san-juan';

try {
    $pdo = getDBConnection();
    
    // Obtener el evento
    $stmt = $pdo->prepare("SELECT id, description FROM cultural_events WHERE slug = ?");
    $stmt->execute([$slug]);
    $evento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$evento) {
        echo "ERROR: Evento con slug '$slug' no encontrado.\n";
        exit(1);
    }
    
    echo "Evento encontrado: ID={$evento['id']}\n";
    
    // Regenerar description_linked
    $description_linked = procesarInboundLinks($evento['description'], $pdo);
    
    // Actualizar en BD
    $upd = $pdo->prepare("UPDATE cultural_events SET description_linked = ? WHERE id = ?");
    $upd->execute([$description_linked, $evento['id']]);
    
    echo "✅ description_linked regenerado correctamente.\n";
    echo "\n--- NUEVO CONTENIDO (primeros 500 chars) ---\n";
    echo substr($description_linked, 0, 500) . "\n";
    echo "--- FIN ---\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
