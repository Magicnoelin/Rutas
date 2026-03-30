<?php
/**
 * API Endpoint: Dashboard Turista
 * Proporciona datos combinados para el dashboard del turista:
 * - Alojamientos según preferencias del usuario
 * - Lugares de interés cercanos
 * - Estadísticas y recomendaciones
 */

require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    jsonError('Debes iniciar sesión', 401);
}

$userId = $_SESSION['user_id'];
$pdo = getDBConnection();

$action = $_GET['action'] ?? 'get_dashboard_data';

try {
    switch ($action) {
        // Obtener datos completos del dashboard
        case 'get_dashboard_data':
            getDashboardData($pdo, $userId);
            break;

        // Obtener alojamientos filtrados por preferencias
        case 'get_filtered_accommodations':
            getFilteredAccommodations($pdo, $userId);
            break;

        // Obtener lugares de interés cercanos
        case 'get_nearby_places':
            getNearbyPlaces($pdo);
            break;

        // Obtener recomendaciones personalizadas
        case 'get_recommendations':
            getRecommendations($pdo, $userId);
            break;

        default:
            jsonError('Acción no válida', 400);
    }
} catch (Exception $e) {
    jsonError('Error en el dashboard: ' . $e->getMessage(), 500);
}

// --------------------------------------------------------------------------
// FUNCIONES
// --------------------------------------------------------------------------

function getDashboardData($pdo, $userId) {
    // 1. Obtener preferencias del usuario
    $prefs = getUserPreferences($pdo, $userId);
    
    // 2. Obtener alojamientos filtrados
    $alojamientos = getAccommodationsByPreferences($pdo, $prefs);
    
    // 3. Obtener lugares de interés
    $lugares = getPlacesOfInterest($pdo);
    
    // 4. Obtener estadísticas
    $stats = [
        'total_alojamientos' => count($alojamientos),
        'total_lugares' => count($lugares),
        'preferencias' => $prefs
    ];

    jsonSuccess([
        'stats' => $stats,
        'alojamientos' => $alojamientos,
        'lugares' => $lugares
    ]);
}

function getFilteredAccommodations($pdo, $userId) {
    $prefs = getUserPreferences($pdo, $userId);
    $alojamientos = getAccommodationsByPreferences($pdo, $prefs);
    jsonSuccess($alojamientos);
}

function getNearbyPlaces($pdo) {
    $lugares = getPlacesOfInterest($pdo);
    jsonSuccess($lugares);
}

function getRecommendations($pdo, $userId) {
    $prefs = getUserPreferences($pdo, $userId);
    $recommendations = getAccommodationsByPreferences($pdo, $prefs, true); // true = modo recomendación
    jsonSuccess($recommendations);
}

// --------------------------------------------------------------------------
// FUNCIONES DE APOYO
// --------------------------------------------------------------------------

function getUserPreferences($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT preferences_json FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result || !$result['preferences_json']) {
        return [];
    }
    
    return json_decode($result['preferences_json'], true) ?? [];
}

function getAccommodationsByPreferences($pdo, $prefs, $recommendationMode = false) {
    // Obtener todos los alojamientos activos
    $sql = "SELECT * FROM accommodations WHERE is_active = 1 ORDER BY name";
    $stmt = $pdo->query($sql);
    $alojamientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Procesar y filtrar según preferencias
    $result = [];
    
    foreach ($alojamientos as $alojamiento) {
        // Verificar si tiene coordenadas
        if (empty($alojamiento['latitude']) || empty($alojamiento['longitude'])) {
            continue;
        }

        // Calcular puntuación de coincidencia
        $score = calculateMatchScore($alojamiento, $prefs);
        
        // En modo recomendación, solo mostrar alta coincidencia
        if ($recommendationMode && $score < 0.6) {
            continue;
        }

        // Formatear datos
        $formatted = [
            'id' => $alojamiento['id'],
            'slug' => $alojamiento['slug'],
            'nombre' => $alojamiento['name'],
            'tipo' => $alojamiento['accommodation_type'],
            'localidad' => $alojamiento['municipality'],
            'provincia' => $alojamiento['province'],
            'plazas' => intval($alojamiento['capacity']),
            'precio' => floatval($alojamiento['price_per_night']),
            'lat' => floatval($alojamiento['latitude']),
            'lng' => floatval($alojamiento['longitude']),
            'score' => $score,
            'fotos' => array_filter([
                $alojamiento['photo1'],
                $alojamiento['photo2'],
                $alojamiento['photo3'],
                $alojamiento['photo4']
            ])
        ];

        $result[] = $formatted;
    }

    // Ordenar por puntuación (mejores primero)
    usort($result, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });

    return $result;
}

function getPlacesOfInterest($pdo) {
    $sql = "SELECT id, name, description, short_description, municipality, province, category_id, 
                   latitude, longitude, photo1, photo2, photo3, photo4
            FROM places_of_interest 
            WHERE is_active = 1 
            ORDER BY name";
    
    $stmt = $pdo->query($sql);
    $lugares = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [];
    foreach ($lugares as $lugar) {
        // Si no tiene coordenadas, generar aleatorias para demo
        $lat = $lugar['latitude'] ?? getRandomLat();
        $lng = $lugar['longitude'] ?? getRandomLon();
        
        $result[] = [
            'id' => $lugar['id'],
            'nombre' => $lugar['name'],
            'descripcion' => $lugar['description'],
            'descripcion_corta' => $lugar['short_description'],
            'localidad' => $lugar['municipality'],
            'provincia' => $lugar['province'],
            'categoria' => $lugar['category_id'],
            'lat' => floatval($lat),
            'lng' => floatval($lng),
            'fotos' => array_filter([
                $lugar['photo1'],
                $lugar['photo2'],
                $lugar['photo3'],
                $lugar['photo4']
            ])
        ];
    }
    
    return $result;
}

function calculateMatchScore($alojamiento, $prefs) {
    $score = 0.5; // Puntuación base
    
    if (empty($prefs)) {
        return $score; // Sin preferencias, puntuación base
    }

    // 1. Coincidencia de tipo según intereses
    if (!empty($prefs['interests'])) {
        $interests = is_array($prefs['interests']) ? $prefs['interests'] : json_decode($prefs['interests'], true);
        
        if (is_array($interests)) {
            // Lógica simple: si el alojamiento es "Casa Rural" y le gusta "Naturaleza", sumar puntos
            $tipo = strtolower($alojamiento['accommodation_type'] ?? '');
            $interesesStr = implode(' ', $interests);
            
            if (strpos($interesesStr, 'naturaleza') !== false && strpos($tipo, 'rural') !== false) {
                $score += 0.2;
            }
            if (strpos($interesesStr, 'aventura') !== false && strpos($tipo, 'camping') !== false) {
                $score += 0.2;
            }
            if (strpos($interesesStr, 'cultura') !== false && strpos($tipo, 'hotel') !== false) {
                $score += 0.1;
            }
        }
    }

    // 2. Coincidencia de presupuesto
    if (!empty($prefs['budget'])) {
        $precio = floatval($alojamiento['price_per_night'] ?? 0);
        $budget = $prefs['budget'];
        
        if ($budget === 'bajo' && $precio < 50) {
            $score += 0.15;
        } elseif ($budget === 'medio' && $precio >= 50 && $precio <= 150) {
            $score += 0.15;
        } elseif ($budget === 'alto' && $precio > 150) {
            $score += 0.15;
        }
    }

    // 3. Disponibilidad de fotos (mejor experiencia)
    $fotosCount = count(array_filter([
        $alojamiento['photo1'],
        $alojamiento['photo2'],
        $alojamiento['photo3'],
        $alojamiento['photo4']
    ]));
    
    if ($fotosCount >= 2) {
        $score += 0.1;
    }

    // 4. Capacidad según duración preferida
    if (!empty($prefs['duration'])) {
        $capacidad = intval($alojamiento['capacity'] ?? 0);
        $duration = $prefs['duration'];
        
        if ($duration === 'fin_semana' && $capacidad <= 4) {
            $score += 0.05;
        } elseif ($duration === 'puente' && $capacidad >= 4 && $capacidad <= 6) {
            $score += 0.05;
        } elseif ($duration === 'semana' && $capacidad >= 6) {
            $score += 0.05;
        }
    }

    // Asegurar que el score no exceda 1.0
    return min($score, 1.0);
}

// Función para generar coordenadas aleatorias (versión PHP)
function getRandomLat() {
    return 40.0 + (mt_rand() / mt_getrandmax()) * 2.8;
}

function getRandomLon() {
    return -1.8 + (mt_rand() / mt_getrandmax()) * 2.3;
}
?>