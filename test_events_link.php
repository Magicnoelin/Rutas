<?php
require_once 'api/config.php';

try {
    $pdo = getDBConnection();
    $slug = 'la-plaza-vinuesa';
    
    $stmt = $pdo->prepare("SELECT id, name FROM accommodations WHERE slug = :slug");
    $stmt->execute([':slug' => $slug]);
    $acc = $stmt->fetch();
    
    if (!$acc) {
        die("Alojamiento no encontrado: $slug");
    }
    
    echo "Alojamiento: " . $acc['name'] . " (ID: " . $acc['id'] . ")\n";
    
    $sqlEvents = "SELECT e.id, e.name, e.start_date FROM cultural_events e
                  JOIN accommodation_event_links link ON e.id = link.event_id
                  WHERE link.accommodation_id = :acc_id
                  ORDER BY e.start_date ASC";
    $stmtEvents = $pdo->prepare($sqlEvents);
    $stmtEvents->execute([':acc_id' => $acc['id']]);
    $eventos = $stmtEvents->fetchAll();
    
    echo "Eventos vinculados: " . count($eventos) . "\n";
    foreach ($eventos as $e) {
        echo "- " . $e['name'] . " (" . $e['start_date'] . ")\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
