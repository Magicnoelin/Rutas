<?php
/**
 * API Endpoint: Datos del Alojamiento y contenido cercano (Optimizado)
 * GET /alojamiento-modular/api/alojamiento-data.php?slug={slug}&lat={lat}&lng={lng}&radius={km}&mode={minimal|full|nearby}
 * 
 * mode=minimal  → Solo datos críticos para renderizado inicial
 * mode=full     → Datos completos del alojamiento
 * mode=nearby   → Alojamientos, lugares, eventos, actividades cercanos (carga diferida)
 */

define('API_NO_HEADERS', true);
require_once '../../api/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=900'); // Cache 15 minutos

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$lng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;
$radius = isset($_GET['radius']) ? intval($_GET['radius']) : 50; // Radio por defecto 50km
$mode = isset($_GET['mode']) ? trim($_GET['mode']) : 'full';
$lang = isset($_GET['lang']) ? trim($_GET['lang']) : 'es';

// Validar parámetros
$lang = in_array($lang, ['es', 'en', 'fr', 'de', 'zh']) ? $lang : 'es';
$mode = in_array($mode, ['minimal', 'full', 'nearby']) ? $mode : 'full';

try {
    $pdo = getDBConnection();

    // ─── MODO NEARBY: Contenido cercano (50km radio) ─────────────────────────────
    if ($mode === 'nearby') {
        $prov = isset($_GET['prov']) ? trim($_GET['prov']) : '';
        $limit_initial = 6; // Mostrar 6 por defecto

        $result = [
            'alojamientos' => [],
            'lugares' => [],
            'eventos_similares' => [],
            'actividades' => []
        ];

        // Alojamientos cercanos (excluyendo el actual)
        if ($lat && $lng) {
            $stmt = $pdo->prepare("
                SELECT id, name, slug, municipality, province,
                       price_per_night, photo1 AS main_image, latitude, longitude,
                       (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
                FROM accommodations
                WHERE is_active = 1 
                  AND latitude IS NOT NULL 
                  AND longitude IS NOT NULL
                  AND slug != ?
                HAVING distance < ?
                ORDER BY distance ASC
                LIMIT 8
            ");
            $stmt->execute([$lat, $lng, $lat, $slug, $radius]);
        } else {
            $stmt = $pdo->prepare("
                SELECT id, name, slug, municipality, province,
                       price_per_night, photo1 AS main_image, latitude, longitude, 0 AS distance
                FROM accommodations
                WHERE is_active = 1 
                  AND province = ?
                  AND slug != ?
                ORDER BY RAND()
                LIMIT 8
            ");
            $stmt->execute([$prov, $slug]);
        }
        $alojamientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($alojamientos as &$a) {
            $a['distance'] = round((float)$a['distance'], 1);
            $a['url'] = '/alojamiento/' . $a['slug'];
        }
        $result['alojamientos'] = $alojamientos;

        // Lugares de interés cercanos
        if ($lat && $lng) {
            $stmt = $pdo->prepare("
                SELECT id, name, slug, municipality, province, category_id, photo1 AS main_image,
                       latitude, longitude,
                       (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
                FROM places_of_interest
                WHERE is_active = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL
                HAVING distance < ?
                ORDER BY distance ASC
                LIMIT 8
            ");
            $stmt->execute([$lat, $lng, $lat, $radius]);
        } else {
            $stmt = $pdo->prepare("
                SELECT id, name, slug, municipality, province, category_id, photo1 AS main_image,
                       latitude, longitude, 0 AS distance
                FROM places_of_interest
                WHERE is_active = 1 AND province = ?
                ORDER BY RAND()
                LIMIT 8
            ");
            $stmt->execute([$prov]);
        }
        $lugares = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($lugares as &$l) {
            $l['distance'] = round((float)$l['distance'], 1);
            $l['url'] = '/lugar/' . $l['slug'];
        }
        $result['lugares'] = $lugares;

        // Actividades turísticas cercanas
        if ($lat && $lng) {
            $stmt = $pdo->prepare("
                SELECT id, name, slug, municipality, province, category_id, photo1 AS main_image,
                       latitude, longitude, price_adult AS price,
                       (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
                FROM tourist_activities
                WHERE is_active = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL
                HAVING distance < ?
                ORDER BY distance ASC
                LIMIT 6
            ");
            $stmt->execute([$lat, $lng, $lat, $radius]);
        } else {
            $stmt = $pdo->prepare("
                SELECT id, name, slug, municipality, province, category_id, photo1 AS main_image,
                       latitude, longitude, price_adult AS price, 0 AS distance
                FROM tourist_activities
                WHERE is_active = 1 AND province = ?
                ORDER BY RAND()
                LIMIT 6
            ");
            $stmt->execute([$prov]);
        }
        $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($actividades as &$act) {
            $act['distance'] = round((float)$act['distance'], 1);
            $act['url'] = '/actividad/' . $act['slug'];
        }
        $result['actividades'] = $actividades;

        // Eventos culturales cercanos (futuros)
        if ($lat && $lng) {
            $stmt = $pdo->prepare("
                SELECT id, name, slug, start_date, end_date, municipality, province,
                       is_free, ticket_price, photo1, poster_image, category_id,
                       latitude, longitude,
                       (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
                FROM cultural_events
                WHERE is_active = 1
                  AND latitude IS NOT NULL 
                  AND longitude IS NOT NULL
                  AND (
                    (end_date IS NULL AND start_date >= CURDATE()) OR
                    (end_date IS NOT NULL AND end_date >= CURDATE())
                  )
                HAVING distance < ?
                ORDER BY start_date ASC
                LIMIT 6
            ");
            $stmt->execute([$lat, $lng, $lat, $radius]);
        } else {
            $stmt = $pdo->prepare("
                SELECT id, name, slug, start_date, end_date, municipality, province,
                       is_free, ticket_price, photo1, poster_image, category_id,
                       latitude, longitude, 0 AS distance
                FROM cultural_events
                WHERE is_active = 1
                  AND province = ?
                  AND (
                    (end_date IS NULL AND start_date >= CURDATE()) OR
                    (end_date IS NOT NULL AND end_date >= CURDATE())
                  )
                ORDER BY start_date ASC
                LIMIT 6
            ");
            $stmt->execute([$prov]);
        }
        $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($eventos as &$e) {
            $e['distance'] = round((float)$e['distance'], 1);
            $e['url'] = '/evento/' . $e['slug'];
            $e['main_image'] = $e['poster_image'] ?: $e['photo1'] ?: null;
            unset($e['photo1'], $e['poster_image']);
        }
        $result['eventos_similares'] = $eventos;
        $result['limit_initial'] = $limit_initial;

        echo json_encode(['success' => true, 'data' => $result]);
        exit;
    }

    // ─── MODO MINIMAL o FULL: Datos del alojamiento ───────────────────────────────
    $alojamiento = null;

    // Query principal con JOIN para categoría
    $stmt = $pdo->prepare("
        SELECT a.*, c.name as category_name
        FROM accommodations a
        LEFT JOIN categories_accommodations c ON a.category_id = c.id
        WHERE a.slug = ? AND a.is_active = 1
    ");
    $stmt->execute([$slug]);
    $alojamiento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$alojamiento) {
        http_response_code(404);
        echo json_encode(['error' => 'Alojamiento no encontrado']);
        exit;
    }

    // Construir array de fotos
    $fotos = [];
    for ($i = 1; $i <= 20; $i++) {
        $campo = 'photo' . $i;
        if (!empty($alojamiento[$campo])) {
            $url = $alojamiento[$campo];
            if (!preg_match('/^https?:\/\//', $url)) {
                $url = '/' . ltrim($url, '/');
            }
            $fotos[] = $url;
        }
    }
    $alojamiento['fotos'] = $fotos;

    // En modo minimal, devolver solo datos críticos
    if ($mode === 'minimal') {
        echo json_encode(['success' => true, 'data' => [
            'id' => $alojamiento['id'],
            'name' => $alojamiento['name'],
            'slug' => $alojamiento['slug'],
            'description' => $alojamiento['description'],
            'description_linked' => $alojamiento['description_linked'] ?? '',
            'meta_title' => $alojamiento['meta_title'],
            'meta_description' => $alojamiento['meta_description'],
            'municipality' => $alojamiento['municipality'],
            'province' => $alojamiento['province'],
            'latitude' => $alojamiento['latitude'],
            'longitude' => $alojamiento['longitude'],
            'price_per_night' => $alojamiento['price_per_night'],
            'capacity' => $alojamiento['capacity'],
            'category_name' => $alojamiento['category_name'],
            'accommodation_type' => $alojamiento['accommodation_type'],
            'fotos' => array_slice($fotos, 0, 1), // Solo primera foto
        ]]);
        exit;
    }

    // Modo full: devolver todo
    echo json_encode(['success' => true, 'data' => $alojamiento]);

} catch (Exception $e) {
    error_log('alojamiento-data.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}