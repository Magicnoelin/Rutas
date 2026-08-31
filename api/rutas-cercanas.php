<?php
header('Content-Type: application/json; charset=UTF-8');

// Suprimir warnings en producción
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

define('API_NO_HEADERS', true);
require_once dirname(__DIR__) . '/api/config.php';

$response = ['success' => false, 'message' => ''];

try {
    $pdo = getDBConnection();

    $lat = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
    $lng = isset($_GET['lng']) ? (float)$_GET['lng'] : null;
    $radius = isset($_GET['radius']) ? (int)$_GET['radius'] : 50; // Default 50km
    $categories = isset($_GET['categories']) ? explode(',', $_GET['categories']) : [];
    $provincia = isset($_GET['provincia']) ? trim($_GET['provincia']) : '';

    if (empty($categories)) {
        $response['message'] = 'No categories specified.';
        echo json_encode($response);
        exit();
    }

    if (!$lat || !$lng) {
        // If no coordinates, but province is provided, try to use province as fallback
        if (!empty($provincia)) {
            // This is a simplified approach. A more robust solution would involve geocoding the province.
            // For now, we'll proceed with province-based search if coordinates are missing.
        } else {
            $response['message'] = 'Latitude and longitude are required.';
            echo json_encode($response);
            exit();
        }
    }

    $results = [];

    foreach ($categories as $category) {
        $category = trim($category);
        $sql = '';
        $params = [];

        switch ($category) {
            case 'alojamientos':
                $sql = "
                    SELECT 'alojamiento' as tipo, name, slug, municipality, price_per_night as precio, photo1 as foto, latitude, longitude,
                        (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distancia
                    FROM accommodations
                    WHERE is_active = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL
                ";
                $params = [$lat, $lng, $lat];
                if (!empty($provincia)) {
                    $sql .= " AND province = ?";
                    $params[] = $provincia;
                }
                $sql .= " HAVING distancia < ? ORDER BY distancia ASC LIMIT 10";
                $params[] = $radius;
                break;
            case 'lugares':
                $sql = "
                    SELECT 'lugar' as tipo, name, slug, municipality, NULL as precio, photo1 as foto, latitude, longitude,
                        (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distancia
                    FROM places_of_interest
                    WHERE is_active = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL
                ";
                $params = [$lat, $lng, $lat];
                if (!empty($provincia)) {
                    $sql .= " AND province = ?";
                    $params[] = $provincia;
                }
                $sql .= " HAVING distancia < ? ORDER BY distancia ASC LIMIT 10";
                $params[] = $radius;
                break;
            case 'actividades':
                $sql = "
                    SELECT 'actividad' as tipo, name, slug, municipality, price as precio, photo1 as foto, latitude, longitude,
                        (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distancia
                    FROM tourist_activities
                    WHERE is_active = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL
                ";
                $params = [$lat, $lng, $lat];
                if (!empty($provincia)) {
                    $sql .= " AND province = ?";
                    $params[] = $provincia;
                }
                $sql .= " HAVING distancia < ? ORDER BY distancia ASC LIMIT 10";
                $params[] = $radius;
                break;
            case 'eventos':
                $sql = "
                    SELECT 'evento' as tipo, name, slug, municipality, ticket_price as precio, photo1 as foto, latitude, longitude, start_date as fecha,
                        (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distancia
                    FROM cultural_events
                    WHERE is_active = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL AND COALESCE(end_date, DATE_ADD(start_date, INTERVAL 1 DAY)) >= CURDATE()
                ";
                $params = [$lat, $lng, $lat];
                if (!empty($provincia)) {
                    $sql .= " AND province = ?";
                    $params[] = $provincia;
                }
                $sql .= " HAVING distancia < ? ORDER BY distancia ASC LIMIT 10";
                $params[] = $radius;
                break;
            default:
                continue;
        }

        if (!empty($sql)) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $categoryResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($categoryResults as $item) {
                $item['precio'] = $item['precio'] ? $item['precio'] . '€' : 'Gratis';
                $item['distancia'] = round($item['distancia'], 1);
                $results[] = $item;
            }
        }
    }

    // Sort results by distance if coordinates are available
    if ($lat && $lng) {
        usort($results, function($a, $b) {
            return $a['distancia'] <=> $b['distancia'];
        });
    }

    $response['success'] = true;
    $response['count'] = count($results);
    $response['data'] = $results;

} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    $response['message'] = 'Internal server error.';
}

echo json_encode($response);