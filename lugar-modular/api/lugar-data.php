<?php
/**
 * API Endpoint: Datos del Lugar de Interés + contenido cercano
 * GET /lugar-modular/api/lugar-data.php?slug={slug}&lat={lat}&lng={lng}&radius={km}&mode={minimal|full|nearby}
 *
 * mode=minimal  → Solo datos críticos para renderizado inicial
 * mode=full     → Datos completos del lugar
 * mode=nearby   → Alojamientos, lugares, eventos, actividades cercanos (carga diferida)
 */

define('API_NO_HEADERS', true);
require_once '../../api/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=900'); // Cache 15 minutos

$slug   = isset($_GET['slug'])   ? trim($_GET['slug'])   : '';
$lat    = isset($_GET['lat'])    ? floatval($_GET['lat']) : null;
$lng    = isset($_GET['lng'])    ? floatval($_GET['lng']) : null;
$radius = isset($_GET['radius']) ? intval($_GET['radius']) : 50;
$mode   = isset($_GET['mode'])   ? trim($_GET['mode'])   : 'full';
$prov   = isset($_GET['prov'])   ? trim($_GET['prov'])   : '';
$muni   = isset($_GET['muni'])   ? trim($_GET['muni'])   : '';

$mode   = in_array($mode, ['minimal', 'full', 'nearby']) ? $mode : 'full';

try {
    $pdo = getDBConnection();

    // ─── MODO NEARBY: Contenido cercano ─────────────────────────────────────────
    if ($mode === 'nearby') {

        $result = [
            'alojamientos'     => [],
            'lugares'          => [],
            'eventos_similares'=> [],
            'actividades'      => []
        ];

        // Alojamientos cercanos
        if ($lat && $lng) {
            $stmt = $pdo->prepare("
                SELECT id, name, slug, municipality, province,
                       price_per_night, photo1 AS main_image, latitude, longitude,
                       (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
                FROM accommodations
                WHERE is_active = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL
                HAVING distance < ?
                ORDER BY distance ASC
                LIMIT 8
            ");
            $stmt->execute([$lat, $lng, $lat, $radius]);
        } else {
            $stmt = $pdo->prepare("
                SELECT id, name, slug, municipality, province,
                       price_per_night, photo1 AS main_image, latitude, longitude, 0 AS distance
                FROM accommodations
                WHERE is_active = 1 AND province = ?
                ORDER BY RAND()
                LIMIT 8
            ");
            $stmt->execute([$prov]);
        }
        $alojamientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($alojamientos as &$a) {
            $a['distance'] = round((float)$a['distance'], 1);
            $a['url'] = '/alojamiento/' . $a['slug'];
        }
        $result['alojamientos'] = $alojamientos;

        // Lugares de interés cercanos (excluyendo el actual)
        if ($lat && $lng) {
            $stmt = $pdo->prepare("
                SELECT id, name, slug, municipality, province, category_id, photo1 AS main_image,
                       latitude, longitude,
                       (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
                FROM places_of_interest
                WHERE is_active = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL AND slug != ?
                HAVING distance < ?
                ORDER BY distance ASC
                LIMIT 8
            ");
            $stmt->execute([$lat, $lng, $lat, $slug, $radius]);
        } else {
            $stmt = $pdo->prepare("
                SELECT id, name, slug, municipality, province, category_id, photo1 AS main_image,
                       latitude, longitude, 0 AS distance
                FROM places_of_interest
                WHERE is_active = 1 AND province = ? AND slug != ?
                ORDER BY RAND()
                LIMIT 8
            ");
            $stmt->execute([$prov, $slug]);
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
            // Limpiar espacios en main_image (pueden quedar por errores de carga)
            if (!empty($act['main_image'])) {
                $act['main_image'] = trim($act['main_image']);
            }
            // Fallback: si no hay main_image, buscar en entity_photos
            if (empty($act['main_image'])) {
                try {
                    $stmtEp = $pdo->prepare("
                        SELECT file_url FROM entity_photos
                        WHERE entity_type = 'tourist_activities'
                          AND entity_id = ?
                          AND permission_status = 'approved'
                          AND status = 'active'
                        ORDER BY is_cover DESC, featured DESC, uploaded_at DESC
                        LIMIT 1
                    ");
                    $stmtEp->execute([$act['id']]);
                    $ep = $stmtEp->fetch(PDO::FETCH_ASSOC);
                    if (!empty($ep['file_url'])) {
                        $act['main_image'] = '/' . ltrim(str_replace('\\', '/', $ep['file_url']), '/');
                    }
                } catch (Exception $e) { /* ignorar */ }
            }
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
                  AND latitude IS NOT NULL AND longitude IS NOT NULL
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
            $e['distance']   = round((float)$e['distance'], 1);
            $e['url']        = '/evento/' . $e['slug'];
            $e['main_image'] = $e['poster_image'] ?: $e['photo1'] ?: null;
            unset($e['photo1'], $e['poster_image']);
        }
        $result['eventos_similares'] = $eventos;

        echo json_encode(['success' => true, 'data' => $result]);
        exit;
    }

    // ─── MODO MINIMAL o FULL: Datos del lugar ──────────────────────────────────
    if (empty($slug)) {
        http_response_code(400);
        echo json_encode(['error' => 'Slug requerido']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT p.*, c.name AS category_name
        FROM places_of_interest p
        LEFT JOIN categories_places c ON p.category_id = c.id
        WHERE p.slug = ? AND p.is_active = 1
        LIMIT 1
    ");
    $stmt->execute([$slug]);
    $lugar = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lugar) {
        http_response_code(404);
        echo json_encode(['error' => 'Lugar no encontrado']);
        exit;
    }

    // ── Construir array de fotos ──────────────────────────────────────────────
    // 1) Primero: fotos legacy (photo1, photo2, photo3, photo4) - siempre en orden
    $fotosLegacy = [];
    for ($i = 1; $i <= 4; $i++) {
        $campo = 'photo' . $i;
        if (!empty($lugar[$campo])) {
            $url = $lugar[$campo];
            if (!preg_match('/^https?:\/\//', $url)) $url = '/' . ltrim($url, '/');
            $fotosLegacy[] = $url;
        }
    }

    // 2) Segundo: fotos de entity_photos (aportadas por usuarios)
    $fotosEntity = [];
    try {
        $stmtF = $pdo->prepare("
            SELECT file_url, author_name, author_instagram
            FROM entity_photos
            WHERE entity_type = 'places_of_interest'
              AND entity_id = ?
              AND permission_status = 'approved'
              AND status = 'active'
            ORDER BY is_cover DESC, featured DESC, uploaded_at DESC
        ");
        $stmtF->execute([$lugar['id']]);
        $fotosEntityRows = $stmtF->fetchAll(PDO::FETCH_ASSOC);
        foreach ($fotosEntityRows as $f) {
            if (!empty($f['file_url'])) {
                // Excluir fotos que ya estén en legacy para evitar duplicados
                $url = '/' . ltrim(str_replace('\\', '/', $f['file_url']), '/');
                if (!in_array($url, $fotosLegacy)) {
                    $fotosEntity[] = $url;
                }
            }
        }
    } catch (Exception $e) { /* ignorar */ }

    // Combinar: primero legacy, luego entity_photos
    $fotos = array_merge($fotosLegacy, $fotosEntity);

    if (empty($fotos)) {
        $fotos[] = '/interest_places_images/Patrocinio.webp';
    }

    $lugar['fotos'] = $fotos;

    // ── Procesar campos JSON ───────────────────────────────────────────────────
    $facilities = [];
    if (!empty($lugar['facilities'])) {
        $decoded = json_decode($lugar['facilities'], true);
        if (is_array($decoded)) $facilities = $decoded;
    }
    $lugar['facilities_array'] = $facilities;

    $languages = [];
    if (!empty($lugar['languages_available'])) {
        $decoded = json_decode($lugar['languages_available'], true);
        if (is_array($decoded)) $languages = $decoded;
    }
    $lugar['languages_array'] = $languages;

    if ($mode === 'minimal') {
        echo json_encode(['success' => true, 'data' => [
            'id'          => $lugar['id'],
            'name'        => $lugar['name'],
            'slug'        => $lugar['slug'],
            'description' => $lugar['description'],
            'municipality'=> $lugar['municipality'],
            'province'    => $lugar['province'],
            'latitude'    => $lugar['latitude'],
            'longitude'   => $lugar['longitude'],
            'category_name'=> $lugar['category_name'],
            'fotos'       => array_slice($fotos, 0, 1),
        ]]);
        exit;
    }

    echo json_encode(['success' => true, 'data' => $lugar]);

} catch (Exception $e) {
    error_log('lugar-data.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
