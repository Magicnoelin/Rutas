<?php
/**
 * API Endpoint: Lugares de Interés
 * GET /api/places.php
 */

require_once 'config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Método no permitido', 405);
}

try {
    $pdo = getDBConnection();

    $status = $_GET['status'] ?? 'active';
    $limit = (int)($_GET['limit'] ?? 20);
    $offset = (int)($_GET['offset'] ?? 0);
    $category = $_GET['category'] ?? null;

    $sql = "SELECT 
                id, name, slug, description, short_description,
                address, municipality, province, postal_code,
                latitude, longitude,
                opening_hours, best_season, visit_duration,
                entry_fee, entry_fee_details,
                accessibility, facilities,
                pet_friendly, suitable_for_children,
                photo1, photo2, photo3, photo4,
                video_url, virtual_tour_url,
                is_active, is_featured, rating_avg, reviews_count,
                created_at
            FROM places_of_interest
            WHERE is_active = 1";

    $params = [];

    if ($category) {
        $sql .= " AND category_id = :category";
        $params[':category'] = $category;
    }

    $sql .= " ORDER BY is_featured DESC, rating_avg DESC, created_at DESC
              LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $places = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonSuccess([
        'success' => true,
        'places' => $places,
        'total' => count($places)
    ]);

} catch (PDOException $e) {
    error_log('places.php - Error: ' . $e->getMessage());
    jsonError('Error al obtener lugares: ' . $e->getMessage(), 500);
}
