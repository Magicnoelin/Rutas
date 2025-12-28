<?php
/**
 * API Endpoint: Eventos Culturales
 * GET /api/cultural-events.php
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
    $from_date = $_GET['from_date'] ?? null;

    $sql = "SELECT 
                id, name as title, slug, description, short_description,
                venue_name, venue_address, municipality, province,
                latitude, longitude,
                start_date, end_date, start_time, end_time,
                is_free, ticket_price, ticket_price_range, ticket_url,
                capacity, organizer, organizer_contact,
                poster_image, photo1, photo2, photo3, photo4,
                is_active, is_featured, status,
                created_at
            FROM cultural_events
            WHERE is_active = 1";

    $params = [];

    if ($category) {
        $sql .= " AND category_id = :category";
        $params[':category'] = $category;
    }

    if ($from_date) {
        $sql .= " AND start_date >= :from_date";
        $params[':from_date'] = $from_date;
    }

    $sql .= " ORDER BY is_featured DESC, start_date ASC, created_at DESC
              LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonSuccess([
        'success' => true,
        'events' => $events,
        'total' => count($events)
    ]);

} catch (PDOException $e) {
    error_log('cultural-events.php - Error: ' . $e->getMessage());
    jsonError('Error al obtener eventos: ' . $e->getMessage(), 500);
}
