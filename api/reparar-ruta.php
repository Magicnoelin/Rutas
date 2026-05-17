<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

$slug = 'ruta-cascadas-covaleda-paso-penoncito';

try {
    $pdo = getDBConnection();
    
    // 1. Localizar la ruta y forzar estados activos
    $stmt = $pdo->prepare("SELECT id, name, itinerary_json FROM routes WHERE slug = ?");
    $stmt->execute([$slug]);
    $ruta = $stmt->fetch();

    if ($ruta) {
        $res['ruta_id'] = $ruta['id'];
        
        // Forzar que la ruta sea pública y esté publicada
        $pdo->prepare("UPDATE routes SET status = 'published', is_public = 1 WHERE id = ?")
            ->execute([$ruta['id']]);

        $data = json_decode($ruta['itinerary_json'], true);
        
        // Si el JSON es inválido o tiene el formato antiguo de "steps", lo reconstruimos
        if (isset($data['steps']) || !isset($data[0]['titulo'])) {
            $newItinerary = [];
            $steps = $data['steps'] ?? ($data ?: []);
            
            foreach ($steps as $step) {
                if (is_array($step)) {
                    $newItinerary[] = [
                        'titulo' => $step['place_name'] ?? $step['titulo'] ?? 'Punto de interés',
                        'descripcion' => $step['description'] ?? $step['descripcion'] ?? ''
                    ];
                }
            }
            
            if (!empty($newItinerary)) {
                $jsonFinal = json_encode($newItinerary, JSON_UNESCAPED_UNICODE);
                $update = $pdo->prepare("UPDATE routes SET itinerary_json = ? WHERE id = ?");
                $update->execute([$jsonFinal, $ruta['id']]);
                $res['itinerario'] = "Formato corregido a Array estándar.";
            }
        } else {
            $res['itinerario'] = "El formato ya era correcto.";
        }

        // 2. Corregir vinculación de Alojamiento y Lugares
        $checkCols = $pdo->query("DESCRIBE route_items")->fetchAll(PDO::FETCH_COLUMN);
        $typeCol = in_array('item_type', $checkCols) ? 'item_type' : 'resource_type';

        // Forzar tipos correctos para evitar que el filtro falle
        // Corregimos 'accommodation' -> 'alojamiento'
        $updAcc = $pdo->prepare("UPDATE route_items SET $typeCol = 'alojamiento' WHERE route_id = ? AND $typeCol = 'accommodation'");
        $updAcc->execute([$ruta['id']]);
        
        // Corregimos 'place' -> 'lugar'
        $updPla = $pdo->prepare("UPDATE route_items SET $typeCol = 'lugar' WHERE route_id = ? AND $typeCol = 'place'");
        $updPla->execute([$ruta['id']]);

        $res['alojamientos_actualizados'] = $updAcc->rowCount();
        $res['lugares_actualizados'] = $updPla->rowCount();

        // 3. Verificar si los items existen realmente
        $stmtItems = $pdo->prepare("SELECT COUNT(*) FROM route_items WHERE route_id = ?");
        $stmtItems->execute([$ruta['id']]);
        $res['total_items_vinculados'] = $stmtItems->fetchColumn();

    } else {
        echo json_encode(['error' => 'No se encontró la ruta con el slug proporcionado.']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'results' => $res
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}