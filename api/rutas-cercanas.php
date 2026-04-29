<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    // Obtener parámetros de la solicitud
    $latitud = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
    $longitud = isset($_GET['lng']) ? floatval($_GET['lng']) : null;
    $radio = isset($_GET['radius']) ? floatval($_GET['radius']) : 100; // Radio en km, default 100km
    $categorias = isset($_GET['categories']) ? explode(',', $_GET['categories']) : ['alojamientos', 'lugares', 'actividades', 'eventos'];

    if (!$latitud || !$longitud) {
        echo json_encode(['success' => false, 'message' => 'Coordenadas no proporcionadas']);
        exit;
    }

    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $resultados = [];

    // Función para calcular distancia en km usando fórmula Haversine
    function calcularDistancia($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371; // Radio de la Tierra en km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distancia = $earthRadius * $c;

        return $distancia;
    }

    // Obtener alojamientos cercanos
    if (in_array('alojamientos', $categorias)) {
        $stmt = $pdo->query("SELECT id, slug, name, address, municipality, province, latitude, longitude, photo1, price_per_night, category_id, created_by as propietario_id, description FROM accommodations WHERE is_active = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL AND latitude != 0");
        $alojamientos = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $distancia = calcularDistancia($latitud, $longitud, $row['latitude'], $row['longitude']);
            if ($distancia <= $radio) {
                $alojamientos[] = [
                    'id' => $row['id'],
                    'slug' => $row['slug'],
                    'nombre' => $row['name'],
                    'direccion' => $row['address'],
                    'localidad' => $row['municipality'],
                    'provincia' => $row['province'],
                    'latitud' => $row['latitude'],
                    'longitud' => $row['longitude'],
                    'foto' => $row['photo1'],
                    'precio' => !empty($row['price_per_night']) && $row['price_per_night'] > 0 ? $row['price_per_night'].'€' : 'Consultar',
                    'distancia' => round($distancia, 1),
                    'tipo' => 'alojamiento',
                    'categoria' => $row['category_id'],
                    'propietario_id' => $row['propietario_id'],
                    'description' => $row['description']
                ];
            }
        }
        $resultados = array_merge($resultados, $alojamientos);
    }

    // Obtener lugares de interés cercanos
    if (in_array('lugares', $categorias)) {
        $stmt = $pdo->query("SELECT id, slug, name, address, municipality, province, latitude, longitude, photo1, entry_fee, category_id, created_by as propietario_id, description FROM places_of_interest WHERE is_active = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL AND latitude != 0");
        $lugares = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $distancia = calcularDistancia($latitud, $longitud, $row['latitude'], $row['longitude']);
            if ($distancia <= $radio) {
                $lugares[] = [
                    'id' => $row['id'],
                    'slug' => $row['slug'],
                    'nombre' => $row['name'],
                    'direccion' => $row['address'],
                    'localidad' => $row['municipality'],
                    'provincia' => $row['province'],
                    'latitud' => $row['latitude'],
                    'longitud' => $row['longitude'],
                    'foto' => $row['photo1'],
                    'precio' => !empty($row['entry_fee']) && $row['entry_fee'] > 0 ? $row['entry_fee'].'€' : 'Gratis',
                    'distancia' => round($distancia, 1),
                    'tipo' => 'lugar',
                    'categoria' => $row['category_id'],
                    'propietario_id' => $row['propietario_id'],
                    'description' => $row['description']
                ];
            }
        }
        $resultados = array_merge($resultados, $lugares);
    }

    // Obtener actividades turísticas cercanas
    if (in_array('actividades', $categorias)) {
        $stmt = $pdo->query("SELECT id, slug, name, meeting_point, municipality, province, latitude, longitude, photo1, price_adult, category_id, created_by as propietario_id, description FROM tourist_activities WHERE is_active = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL AND latitude != 0");
        $actividades = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $distancia = calcularDistancia($latitud, $longitud, $row['latitude'], $row['longitude']);
            if ($distancia <= $radio) {
                $actividades[] = [
                    'id' => $row['id'],
                    'slug' => $row['slug'],
                    'nombre' => $row['name'],
                    'direccion' => $row['meeting_point'],
                    'localidad' => $row['municipality'],
                    'provincia' => $row['province'],
                    'latitud' => $row['latitude'],
                    'longitud' => $row['longitude'],
                    'foto' => $row['photo1'],
                    'precio' => !empty($row['price_adult']) && $row['price_adult'] > 0 ? $row['price_adult'].'€' : 'Gratis',
                    'distancia' => round($distancia, 1),
                    'tipo' => 'actividad',
                    'categoria' => $row['category_id'],
                    'propietario_id' => $row['propietario_id'],
                    'description' => $row['description']
                ];
            }
        }
        $resultados = array_merge($resultados, $actividades);
    }

    // Obtener eventos culturales cercanos
    if (in_array('eventos', $categorias)) {
        $stmt = $pdo->query("SELECT id, slug, name, venue_name as location, municipality, province, latitude, longitude, photo1, poster_image, ticket_price as price, category_id as category, created_by as propietario_id, description, start_date FROM cultural_events WHERE is_active = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL AND latitude != 0 AND COALESCE(end_date, DATE_ADD(start_date, INTERVAL 1 DAY)) >= CURDATE()");
        $eventos = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $distancia = calcularDistancia($latitud, $longitud, $row['latitude'], $row['longitude']);
            if ($distancia <= $radio) {
                // Usar poster_image o photo1 como imagen principal, con fallback al slug
                $foto = $row['poster_image'] ?: $row['photo1'];
                if (empty($foto)) {
                    $foto = "/cultural_events_images/" . $row['slug'] . ".webp";
                }

                $eventos[] = [
                    'id' => $row['id'],
                    'slug' => $row['slug'],
                    'nombre' => $row['name'],
                    'direccion' => $row['location'],
                    'localidad' => $row['municipality'],
                    'provincia' => $row['province'],
                    'latitud' => $row['latitude'],
                    'longitud' => $row['longitude'],
                    'foto' => $foto,
                    'precio' => !empty($row['price']) && $row['price'] > 0 ? $row['price'].'€' : 'Gratis',
                    'distancia' => round($distancia, 1),
                    'tipo' => 'evento',
                    'categoria' => $row['category'],
                    'propietario_id' => $row['propietario_id'],
                    'description' => $row['description'],
                    'fecha' => $row['start_date'] ?? null
                ];
            }
        }
        $resultados = array_merge($resultados, $eventos);
    }

    // Separar eventos del resto para ordenar de forma diferente
    $eventos = [];
    $no_eventos = [];
    
    foreach ($resultados as $item) {
        if ($item['tipo'] === 'evento') {
            $eventos[] = $item;
        } else {
            $no_eventos[] = $item;
        }
    }
    
    // Ordenar no-eventos por distancia
    usort($no_eventos, function($a, $b) {
        return $a['distancia'] <=> $b['distancia'];
    });
    
    // Ordenar eventos por fecha ASC (próximos primero)
    usort($eventos, function($a, $b) {
        $fechaA = $a['fecha'] ?? '9999-12-31';
        $fechaB = $b['fecha'] ?? '9999-12-31';
        return $fechaA <=> $fechaB;
    });
    
    // Combinar: primero no-eventos (por distancia), luego eventos (por fecha)
    $resultados = array_merge($no_eventos, $eventos);

    echo json_encode(['success' => true, 'data' => $resultados, 'count' => count($resultados)]);

} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>