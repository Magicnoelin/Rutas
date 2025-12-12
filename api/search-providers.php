<?php
/**
 * API Endpoint: Buscar Proveedores
 * GET /api/search-providers.php
 * Parámetros: serviceType, location, dateFrom, dateTo, guests, budget
 */

require_once 'config.php';

// Solo permitir método GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Método no permitido', 405);
}

try {
    $pdo = getDBConnection();

    // Obtener parámetros de búsqueda
    $serviceType = $_GET['serviceType'] ?? '';
    $location = $_GET['location'] ?? '';
    $dateFrom = $_GET['dateFrom'] ?? '';
    $dateTo = $_GET['dateTo'] ?? '';
    $guests = $_GET['guests'] ?? '';
    $budget = $_GET['budget'] ?? '';

    $providers = [];

    // Buscar alojamientos si no se especifica tipo o se incluye alojamiento
    if (empty($serviceType) || $serviceType === 'alojamiento') {
        $accommodations = searchAccommodations($pdo, $location, $guests, $budget);
        $providers = array_merge($providers, $accommodations);
    }

    // Buscar eventos si no se especifica tipo o se incluye evento
    if (empty($serviceType) || $serviceType === 'evento') {
        $events = searchEvents($pdo, $location, $dateFrom, $dateTo, $budget);
        $providers = array_merge($providers, $events);
    }

    // Buscar actividades si no se especifica tipo o se incluye actividad
    if (empty($serviceType) || $serviceType === 'actividad') {
        $activities = searchActivities($pdo, $location, $guests, $budget);
        $providers = array_merge($providers, $activities);
    }

    // Buscar lugares si no se especifica tipo o se incluye lugar
    if (empty($serviceType) || $serviceType === 'lugar') {
        $places = searchPlaces($pdo, $location);
        $providers = array_merge($providers, $places);
    }

    // Ordenar por rating (simulado) y limitar resultados
    usort($providers, function($a, $b) {
        return ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0);
    });

    // Limitar a 20 resultados para mejor rendimiento
    $providers = array_slice($providers, 0, 20);

    jsonSuccess([
        'providers' => $providers,
        'total' => count($providers),
        'filters' => [
            'serviceType' => $serviceType,
            'location' => $location,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'guests' => $guests,
            'budget' => $budget
        ]
    ]);

} catch (PDOException $e) {
    error_log('Search-providers.php - Database Error: ' . $e->getMessage());
    jsonError('Error en la búsqueda: ' . $e->getMessage(), 500);
}

/**
 * Buscar alojamientos
 */
function searchAccommodations($pdo, $location, $guests, $budget) {
    try {
        $sql = "SELECT id, name, description, location, price_per_night, max_guests,
                       rating, total_reviews, verified, created_at
                FROM accommodations
                WHERE status = 'active'";

        $params = [];
        $conditions = [];

        // Filtro por ubicación
        if (!empty($location)) {
            $conditions[] = "(location LIKE :location OR municipality LIKE :location)";
            $params[':location'] = '%' . $location . '%';
        }

        // Filtro por número de huéspedes
        if (!empty($guests)) {
            if ($guests === '8+') {
                $conditions[] = "max_guests >= 8";
            } elseif (strpos($guests, '-') !== false) {
                list($min, $max) = explode('-', $guests);
                $conditions[] = "max_guests BETWEEN :min_guests AND :max_guests";
                $params[':min_guests'] = (int)$min;
                $params[':max_guests'] = (int)$max;
            } else {
                $conditions[] = "max_guests >= :guests";
                $params[':guests'] = (int)$guests;
            }
        }

        // Filtro por presupuesto
        if (!empty($budget)) {
            $conditions[] = "price_per_night <= :budget";
            $params[':budget'] = (int)$budget;
        }

        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY rating DESC, total_reviews DESC LIMIT 10";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $accommodations = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $accommodations[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'type' => 'alojamiento',
                'description' => $row['description'],
                'location' => $row['location'],
                'rating' => (float)($row['rating'] ?? 0),
                'reviews' => (int)($row['total_reviews'] ?? 0),
                'price' => (float)($row['price_per_night'] ?? 0),
                'verified' => (bool)($row['verified'] ?? false),
                'max_guests' => (int)($row['max_guests'] ?? 0)
            ];
        }

        return $accommodations;

    } catch (PDOException $e) {
        error_log('Error searching accommodations: ' . $e->getMessage());
        return [];
    }
}

/**
 * Buscar eventos culturales
 */
function searchEvents($pdo, $location, $dateFrom, $dateTo, $budget) {
    try {
        $sql = "SELECT id, title, description, location, event_date, price,
                       rating, total_reviews, verified, created_at
                FROM cultural_events
                WHERE status = 'active'";

        $params = [];
        $conditions = [];

        // Filtro por ubicación
        if (!empty($location)) {
            $conditions[] = "location LIKE :location";
            $params[':location'] = '%' . $location . '%';
        }

        // Filtro por fecha
        if (!empty($dateFrom)) {
            $conditions[] = "event_date >= :date_from";
            $params[':date_from'] = $dateFrom;
        }

        if (!empty($dateTo)) {
            $conditions[] = "event_date <= :date_to";
            $params[':date_to'] = $dateTo;
        }

        // Filtro por presupuesto
        if (!empty($budget)) {
            $conditions[] = "price <= :budget";
            $params[':budget'] = (int)$budget;
        }

        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY event_date ASC, rating DESC LIMIT 10";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $events = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $events[] = [
                'id' => $row['id'],
                'name' => $row['title'],
                'type' => 'evento',
                'description' => $row['description'],
                'location' => $row['location'],
                'rating' => (float)($row['rating'] ?? 0),
                'reviews' => (int)($row['total_reviews'] ?? 0),
                'price' => (float)($row['price'] ?? 0),
                'verified' => (bool)($row['verified'] ?? false),
                'event_date' => $row['event_date']
            ];
        }

        return $events;

    } catch (PDOException $e) {
        error_log('Error searching events: ' . $e->getMessage());
        return [];
    }
}

/**
 * Buscar actividades turísticas
 */
function searchActivities($pdo, $location, $guests, $budget) {
    try {
        $sql = "SELECT id, name, description, location, duration, price_per_person,
                       max_participants, rating, total_reviews, verified, created_at
                FROM activities
                WHERE status = 'active'";

        $params = [];
        $conditions = [];

        // Filtro por ubicación
        if (!empty($location)) {
            $conditions[] = "location LIKE :location";
            $params[':location'] = '%' . $location . '%';
        }

        // Filtro por número de participantes
        if (!empty($guests)) {
            if ($guests === '8+') {
                $conditions[] = "max_participants >= 8";
            } elseif (strpos($guests, '-') !== false) {
                list($min, $max) = explode('-', $guests);
                $conditions[] = "max_participants BETWEEN :min_guests AND :max_guests";
                $params[':min_guests'] = (int)$min;
                $params[':max_guests'] = (int)$max;
            } else {
                $conditions[] = "max_participants >= :guests";
                $params[':guests'] = (int)$guests;
            }
        }

        // Filtro por presupuesto
        if (!empty($budget)) {
            $conditions[] = "price_per_person <= :budget";
            $params[':budget'] = (int)$budget;
        }

        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY rating DESC, total_reviews DESC LIMIT 10";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $activities = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $activities[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'type' => 'actividad',
                'description' => $row['description'],
                'location' => $row['location'],
                'rating' => (float)($row['rating'] ?? 0),
                'reviews' => (int)($row['total_reviews'] ?? 0),
                'price' => (float)($row['price_per_person'] ?? 0),
                'verified' => (bool)($row['verified'] ?? false),
                'duration' => $row['duration'],
                'max_participants' => (int)($row['max_participants'] ?? 0)
            ];
        }

        return $activities;

    } catch (PDOException $e) {
        error_log('Error searching activities: ' . $e->getMessage());
        return [];
    }
}

/**
 * Buscar lugares de interés
 */
function searchPlaces($pdo, $location) {
    try {
        $sql = "SELECT id, name, description, location, category,
                       rating, total_reviews, verified, created_at
                FROM places_of_interest
                WHERE status = 'active'";

        $params = [];
        $conditions = [];

        // Filtro por ubicación
        if (!empty($location)) {
            $conditions[] = "location LIKE :location";
            $params[':location'] = '%' . $location . '%';
        }

        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY rating DESC, total_reviews DESC LIMIT 10";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $places = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $places[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'type' => 'lugar',
                'description' => $row['description'],
                'location' => $row['location'],
                'rating' => (float)($row['rating'] ?? 0),
                'reviews' => (int)($row['total_reviews'] ?? 0),
                'price' => 0, // Los lugares generalmente no tienen precio de entrada
                'verified' => (bool)($row['verified'] ?? false),
                'category' => $row['category']
            ];
        }

        return $places;

    } catch (PDOException $e) {
        error_log('Error searching places: ' . $e->getMessage());
        return [];
    }
}
