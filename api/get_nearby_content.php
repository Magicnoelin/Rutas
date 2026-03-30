<?php
/**
 * API Endpoint: Obtener contenido cercano a un alojamiento
 * Devuelve lugares de interés, actividades turísticas y eventos culturales
 * de la misma localidad/provincia que el alojamiento
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDBConnection();
    
    // Obtener parámetros
    $accommodationId = $_GET['accommodation_id'] ?? null;
    $municipality = $_GET['municipality'] ?? null;
    $province = $_GET['province'] ?? null;
    
    $accommodationLat = null;
    $accommodationLng = null;

    // Log para debugging
    error_log("get_nearby_content.php - Parámetros recibidos: accommodation_id=$accommodationId, municipality=$municipality, province=$province");
    
    // Si se proporciona ID de alojamiento, obtener su ubicación
    if ($accommodationId) {
        $stmt = $pdo->prepare("SELECT municipality, province, latitude, longitude FROM accommodations WHERE id = ?");
        $stmt->execute([$accommodationId]);
        $accommodation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($accommodation) {
            $municipality = $accommodation['municipality'];
            $province = $accommodation['province'];
            $accommodationLat = $accommodation['latitude'];
            $accommodationLng = $accommodation['longitude'];
            error_log("get_nearby_content.php - Ubicación desde BD: municipality=$municipality, province=$province");
        } else {
            error_log("get_nearby_content.php - No se encontró alojamiento con ID: $accommodationId");
        }
    }
    
    // Validar que tengamos al menos uno de los dos
    if ((!isset($municipality) || trim($municipality) === '') && (!isset($province) || trim($province) === '')) {
        error_log("get_nearby_content.php - ERROR: No hay municipality ni province. Municipality: '$municipality', Province: '$province'");
        jsonError('Se requiere municipality, province o accommodation_id válido', 400);
    }
    
    error_log("get_nearby_content.php - Buscando contenido para: municipality=$municipality, province=$province");
    
    // Obtener lugares de interés
    $places = getPlacesOfInterest($pdo, $municipality, $province, $accommodationLat, $accommodationLng);
    
    // Obtener actividades turísticas
    $activities = getTouristActivities($pdo, $municipality, $province, $accommodationLat, $accommodationLng);
    
    // Obtener eventos culturales (próximos)
    $events = getCulturalEvents($pdo, $municipality, $province, $accommodationLat, $accommodationLng);
    
    jsonSuccess([
        'places_of_interest' => $places,
        'tourist_activities' => $activities,
        'cultural_events' => $events,
        'location' => [
            'municipality' => $municipality,
            'province' => $province
        ]
    ]);
    
} catch (Exception $e) {
    jsonError('Error al obtener contenido cercano: ' . $e->getMessage(), 500);
}

// --------------------------------------------------------------------------
// FUNCIONES
// --------------------------------------------------------------------------

function getPlacesOfInterest($pdo, $municipality, $province, $refLat = null, $refLng = null) {
    $sql = "SELECT id, name, slug, short_description, description, municipality, province, 
                   category_id, latitude, longitude, photo1, photo2, photo3, photo4,
                   opening_hours, website
            FROM places_of_interest 
            WHERE is_active = 1 
            AND (municipality = ? OR province = ?)
            ORDER BY 
                CASE WHEN municipality = ? THEN 0 ELSE 1 END,
                name
            LIMIT 6";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$municipality, $province, $municipality]);
    
    $places = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [];
    foreach ($places as $place) {
        $distance = calculateDistance($refLat, $refLng, $place['latitude'], $place['longitude']);

        $result[] = [
            'id' => $place['id'],
            'name' => $place['name'],
            'slug' => $place['slug'],
            'short_description' => $place['short_description'],
            'description' => $place['description'],
            'municipality' => $place['municipality'],
            'province' => $place['province'],
            'category_id' => $place['category_id'],
            'latitude' => $place['latitude'],
            'longitude' => $place['longitude'],
            'distance' => $distance,
            'opening_hours' => $place['opening_hours'],
            'contact_phone' => '',
            'website' => $place['website'],
            'photos' => array_values(array_filter([
                $place['photo1'],
                $place['photo2'],
                $place['photo3'],
                $place['photo4']
            ])),
            'main_photo' => $place['photo1'] ?: '/menu_images/image_not_found.webp'
        ];
    }
    
    return $result;
}

function getTouristActivities($pdo, $municipality, $province, $refLat = null, $refLng = null) {
    $sql = "SELECT id, name, slug, short_description, description, municipality, province,
                   category_id, latitude, longitude, photo1, photo2, photo3, photo4,
                   duration, difficulty_level, website
            FROM tourist_activities 
            WHERE is_active = 1 
            AND (municipality = ? OR province = ?)
            ORDER BY 
                CASE WHEN municipality = ? THEN 0 ELSE 1 END,
                name
            LIMIT 6";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$municipality, $province, $municipality]);
    
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [];
    foreach ($activities as $activity) {
        $distance = calculateDistance($refLat, $refLng, $activity['latitude'], $activity['longitude']);

        $result[] = [
            'id' => $activity['id'],
            'name' => $activity['name'],
            'slug' => $activity['slug'],
            'short_description' => $activity['short_description'],
            'description' => $activity['description'],
            'municipality' => $activity['municipality'],
            'province' => $activity['province'],
            'category_id' => $activity['category_id'],
            'latitude' => $activity['latitude'],
            'longitude' => $activity['longitude'],
            'distance' => $distance,
            'duration' => $activity['duration'],
            'difficulty_level' => $activity['difficulty_level'],
            'price' => !empty($activity['price']) ? $activity['price'] . '€' : 'Consultar',
            'contact_phone' => '',
            'website' => $activity['website'],
            'photos' => array_values(array_filter([
                $activity['photo1'],
                $activity['photo2'],
                $activity['photo3'],
                $activity['photo4']
            ])),
            'main_photo' => $activity['photo1'] ?: '/menu_images/image_not_found.webp'
        ];
    }
    
    return $result;
}

function getCulturalEvents($pdo, $municipality, $province, $refLat = null, $refLng = null) {
    // Solo eventos futuros o del día actual
    $today = date('Y-m-d');
    
    $sql = "SELECT *
            FROM cultural_events 
            WHERE is_active = 1 
            AND start_date >= ?
            AND (municipality = ? OR province = ?)
            ORDER BY 
                start_date ASC,
                CASE WHEN municipality = ? THEN 0 ELSE 1 END
            LIMIT 6";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$today, $municipality, $province, $municipality]);
    
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [];
    foreach ($events as $event) {
        // Formatear fecha
        $startDate = new DateTime($event['start_date']);
        $formattedDate = $startDate->format('d/m/Y');
        $dayName = getDayName($startDate->format('N'));
        
        // Formatear end_date si existe
        $endDateFormatted = null;
        if (!empty($event['end_date'])) {
            $endDate = new DateTime($event['end_date']);
            $endDateFormatted = $endDate->format('d/m/Y');
        }
        
        // Obtener nombre de categoría
        $categoryName = getEventCategoryName($event['category_id']);

        // Formatear precio - usar campos correctos: ticket_price e is_free
        $price = '';
        $isFree = isset($event['is_free']) && $event['is_free'] == 1;
        $ticketPrice = isset($event['ticket_price']) ? floatval($event['ticket_price']) : 0;
        
        if ($isFree) {
            $price = 'Gratis';
        } elseif ($ticketPrice > 0) {
            $price = number_format($ticketPrice, 2) . ' EUR';
        } elseif (!empty($event['ticket_price_range'])) {
            $price = $event['ticket_price_range'];
        }

        $distance = calculateDistance($refLat, $refLng, $event['latitude'], $event['longitude']);

        $result[] = [
            'id' => $event['id'],
            'name' => $event['name'] ?? '',
            'title' => $event['name'] ?? $event['title'] ?? '',
            'slug' => $event['slug'],
            'short_description' => $event['short_description'] ?? '',
            'description' => $event['description'] ?? '',
            'municipality' => $event['municipality'],
            'province' => $event['province'],
            'category_id' => $event['category_id'] ?? null,
            'category_name' => $categoryName,
            'start_date' => $event['start_date'],
            'end_date' => $event['end_date'] ?? null,
            'start_date_formatted' => $formattedDate,
            'end_date_formatted' => $endDateFormatted,
            'day_name' => $dayName,
            'event_time' => $event['event_time'] ?? '',
            'venue' => $event['venue'] ?? '',
            'address' => $event['address'] ?? '',
            'latitude' => $event['latitude'] ?? null,
            'longitude' => $event['longitude'] ?? null,
            'distance' => $distance,
            'is_free' => $event['is_free'] ?? 0,
            'ticket_price' => $ticketPrice > 0 ? $ticketPrice : null,
            'ticket_price_range' => $event['ticket_price_range'] ?? '',
            'price' => $price,
            'organizer' => $event['organizer'] ?? '',
            'contact_phone' => '',
            'contact_email' => $event['email'] ?? '',
            'website' => $event['website'] ?? '',
            'photos' => array_values(array_filter([
                $event['photo1'] ?? null,
                $event['photo2'] ?? null,
                $event['photo3'] ?? null,
                $event['photo4'] ?? null
            ])),
            'main_photo' => ($event['poster_image'] ?? $event['photo1'] ?? null) ?: ("/cultural_events_images/" . $event['slug'] . ".webp")
        ];
    }
    
    return $result;
}

function getDayName($dayNumber) {
    $days = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo'
    ];
    return $days[$dayNumber] ?? '';
}

function getEventCategoryName($id) {
    $categorias = [
        1 => 'Fiestas Populares',
        2 => 'Fiestas Patronales',
        3 => 'Fiestas Tradicionales',
        4 => 'Romerías',
        5 => 'Carnavales',
        6 => 'Cultura y Espectáculos',
        7 => 'Conciertos',
        8 => 'Teatro',
        9 => 'Exposiciones',
        10 => 'Festivales de Música',
        11 => 'Cine',
        12 => 'Gastronomía y Ferias',
        13 => 'Ferias Gastronómicas',
        14 => 'Jornadas Gastronómicas',
        15 => 'Mercados Tradicionales',
        16 => 'Ferias de Productos Locales',
        17 => 'Deportes',
        18 => 'Carreras Populares',
        19 => 'Maratones y Medias',
        20 => 'Competiciones Ciclistas',
        21 => 'Eventos Deportivos',
        22 => 'Religión y Tradición',
        23 => 'Semana Santa',
        24 => 'Procesiones',
        25 => 'Celebraciones Religiosas'
    ];
    return $categorias[$id] ?? 'Evento Cultural';
}

function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    if (!is_numeric($lat1) || !is_numeric($lon1) || !is_numeric($lat2) || !is_numeric($lon2)) {
        return null;
    }
    
    $earthRadius = 6371; // Radio de la tierra en km

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);

    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $distance = $earthRadius * $c;

    return round($distance, 1); // 1 decimal
}
?>