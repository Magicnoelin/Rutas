<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

$slug = 'ruta-cascadas-covaleda-paso-penoncito';

try {
    $pdo = getDBConnection();
    
    // 1. Verificar la ruta
    $stmt = $pdo->prepare("SELECT id, name, itinerary_json, status, is_public FROM routes WHERE slug = ?");
    $stmt->execute([$slug]);
    $ruta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ruta) {
        echo json_encode(['error' => 'La ruta no existe en la base de datos con ese slug']);
        exit;
    }

    // 2. Verificar elementos vinculados (Tabla route_items)
    // Intentamos detectar si usa item_type o resource_type
    $checkCols = $pdo->query("DESCRIBE route_items")->fetchAll(PDO::FETCH_COLUMN);
    $typeCol = in_array('item_type', $checkCols) ? 'item_type' : 'resource_type';
    $idCol   = in_array('item_id', $checkCols) ? 'item_id' : 'resource_id';

    $stmtItems = $pdo->prepare("SELECT $typeCol as type, $idCol as id FROM route_items WHERE route_id = ?");
    $stmtItems->execute([$ruta['id']]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    // 3. Verificar si esos items están activos
    $statusReport = [];
    foreach ($items as $item) {
        $table = ($item['type'] === 'alojamiento' || $item['type'] === 'accommodation') ? 'accommodations' : 'places_of_interest';
        $stmtActive = $pdo->prepare("SELECT name, is_active, moderation_status FROM $table WHERE id = ?");
        $stmtActive->execute([$item['id']]);
        $statusReport[] = [
            'type' => $item['type'],
            'data' => $stmtActive->fetch(PDO::FETCH_ASSOC)
        ];
    }

    echo json_encode([
        'ruta_encontrada' => $ruta['name'],
        'itinerario_raw' => $ruta['itinerary_json'],
        'itinerario_decodificado' => json_decode($ruta['itinerary_json'], true),
        'estado_items_vinculados' => $statusReport
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}