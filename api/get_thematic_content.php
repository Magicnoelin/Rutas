<?php
/**
 * API: Obtener Contenido Temático Mixto
 * GET /api/get_thematic_content.php?theme=gastronomia
 */

header('Content-Type: application/json');
require_once 'config.php';
require_once 'themes_config.php';

$themeKey = $_GET['theme'] ?? '';

if (!isset($THEMES_CONFIG[$themeKey])) {
    echo json_encode(['success' => false, 'message' => 'Tema no válido']);
    exit;
}

$config = $THEMES_CONFIG[$themeKey];
$results = [
    'places' => [],
    'events' => [],
    'activities' => [],
    'accommodations' => []
];

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. LUGARES DE INTERÉS
    if (!empty($config['filters']['places']['subcategory_ids'])) {
        $ids = implode(',', $config['filters']['places']['subcategory_ids']);
        $stmt = $pdo->query("SELECT id, name, slug, photo1, municipality, province, subcategory_id FROM places_of_interest WHERE subcategory_id IN ($ids) AND is_active = 1 LIMIT 8");
        $results['places'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. EVENTOS CULTURALES
    if (!empty($config['filters']['events']['category_ids'])) {
        $ids = implode(',', $config['filters']['events']['category_ids']);
        $today = date('Y-m-d');
        $stmt = $pdo->query("SELECT id, name, slug, poster_image as photo1, municipality, province, start_date FROM cultural_events WHERE category_id IN ($ids) AND is_active = 1 AND start_date >= '$today' ORDER BY start_date ASC LIMIT 8");
        $results['events'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. ACTIVIDADES
    if (!empty($config['filters']['activities']['category_ids'])) {
        $ids = implode(',', $config['filters']['activities']['category_ids']);
        $stmt = $pdo->query("SELECT id, name, slug, photo1, municipality, province, price_adult FROM tourist_activities WHERE category_id IN ($ids) AND is_active = 1 LIMIT 8");
        $results['activities'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 4. ALOJAMIENTOS (Si no hay filtros específicos, traer destacados o aleatorios para rellenar la landing)
    $stmt = $pdo->query("SELECT id, name, slug, photo1, municipality, province, price_per_night FROM accommodations WHERE is_active = 1 ORDER BY RAND() LIMIT 4");
    $results['accommodations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'theme' => $config,
        'data' => $results
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>