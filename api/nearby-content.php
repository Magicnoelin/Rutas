<?php
header('Content-Type: application/json; charset=UTF-8');

require_once dirname(__DIR__) . '/api/config.php';

$response = [
    'success' => false,
    'message' => 'Invalid request',
    'data'    => []
];

if (!isset($_GET['type']) || !isset($_GET['lat']) || !isset($_GET['lng'])) {
    echo json_encode($response);
    exit;
}

$type = $_GET['type'];
$lat  = (float)$_GET['lat'];
$lng  = (float)$_GET['lng'];
$radius = (int)($_GET['radius'] ?? 25); // Default radius 25km

if ($lat == 0 || $lng == 0) {
    $response['message'] = 'Invalid latitude or longitude';
    echo json_encode($response);
    exit;
}

try {
    $pdo = getDBConnection();
    $data = [];

    // Haversine formula para calcular distancia en KM
    $distance_sql = "(
        6371 * acos(
            cos(radians(?)) * cos(radians(latitude)) *
            cos(radians(longitude) - radians(?)) +
            sin(radians(?)) * sin(radians(latitude))
        )
    )";

    switch ($type) {
        case 'alojamientos':
            $stmt = $pdo->prepare("
                SELECT id, name, slug, latitude, longitude, {$distance_sql} AS distance
                FROM accommodations
                WHERE is_active = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL
                HAVING distance < ?
                ORDER BY distance
                LIMIT 5
            ");
            $stmt->execute([$lat, $lng, $lat, $radius]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'lugares':
            $stmt = $pdo->prepare("
                SELECT id, name, slug, latitude, longitude, {$distance_sql} AS distance
                FROM places_of_interest
                WHERE is_active = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL
                HAVING distance < ?
                ORDER BY distance
                LIMIT 5
            ");
            $stmt->execute([$lat, $lng, $lat, $radius]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'actividades':
            $stmt = $pdo->prepare("
                SELECT id, name, slug, latitude, longitude, {$distance_sql} AS distance
                FROM activities
                WHERE is_active = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL
                HAVING distance < ?
                ORDER BY distance
                LIMIT 5
            ");
            $stmt->execute([$lat, $lng, $lat, $radius]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        default:
            $response['message'] = 'Unknown type';
            echo json_encode($response);
            exit;
    }

    $response['success'] = true;
    $response['message'] = 'Data fetched successfully';
    $response['data']    = $data;

} catch (Exception $e) {
    error_log("API Error (nearby-content.php): " . $e->getMessage());
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);