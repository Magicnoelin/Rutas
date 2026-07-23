<?php
/**
 * API Endpoint: Datos del Evento (Optimizado)
 * GET /evento-modular/api/evento-data.php?slug={slug}&lang={lang}&mode={minimal|full|nearby}
 * 
 * mode=minimal  → Solo datos críticos para renderizado inicial (título, fechas, ubicación)
 * mode=full     → Datos completos del evento
 * mode=nearby   → Alojamientos y lugares cercanos (carga diferida)
 */

define('API_NO_HEADERS', true);
require_once '../../api/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=300'); // Cache 5 minutos

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$lang = isset($_GET['lang']) ? trim($_GET['lang']) : 'es';
$mode = isset($_GET['mode']) ? trim($_GET['mode']) : 'full';

if (empty($slug)) {
    http_response_code(400);
    echo json_encode(['error' => 'Slug requerido']);
    exit;
}

// Validar parámetros
$lang = in_array($lang, ['es', 'en', 'fr', 'de', 'zh']) ? $lang : 'es';
$mode = in_array($mode, ['minimal', 'full', 'nearby']) ? $mode : 'full';

try {
    $pdo = getDBConnection();

    // ─── MODO NEARBY: Alojamientos y lugares cercanos ───────────────────────
    if ($mode === 'nearby') {
        $lat  = isset($_GET['lat'])    ? floatval($_GET['lat'])    : null;
        $lng  = isset($_GET['lng'])    ? floatval($_GET['lng'])    : null;
        $prov = isset($_GET['prov'])   ? trim($_GET['prov'])       : '';
        $limit_initial = 3; // Mostrar 3 por defecto, el resto con toggle

        $result = ['alojamientos' => [], 'lugares' => [], 'eventos_similares' => []];

        // Alojamientos cercanos: prioridad por coordenadas, fallback por provincia
        // featured_until: campo que permite destacar manualmente un alojamiento por tiempo limitado
        $alojamientos = [];
        if ($lat && $lng) {
            $stmt = $pdo->prepare("
                SELECT id, name, slug, municipality, province,
                       price_per_night, capacity, photo1 AS main_image, latitude, longitude,
                       featured_until,
                       (featured_until IS NOT NULL AND featured_until > NOW()) AS is_featured_now,
                       (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
                FROM accommodations
                WHERE is_active = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL
                HAVING distance < 100
                ORDER BY is_featured_now DESC, distance ASC
                LIMIT 8
            ");
            $stmt->execute([$lat, $lng, $lat]);
            $alojamientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Si hay pocos resultados con coordenadas (o no hay lat/lng), completar con alojamientos de la misma provincia
        if (count($alojamientos) < 6 && !empty($prov)) {
            $existing_ids = array_column($alojamientos, 'id');
            $needed = 8 - count($alojamientos);
            if ($existing_ids) {
                $placeholders = implode(',', array_map('intval', $existing_ids));
                $stmt2 = $pdo->prepare("
                    SELECT id, name, slug, municipality, province,
                           price_per_night, capacity, photo1 AS main_image, latitude, longitude,
                           featured_until,
                           (featured_until IS NOT NULL AND featured_until > NOW()) AS is_featured_now,
                           0 AS distance
                    FROM accommodations
                    WHERE is_active = 1 AND province = ?
                      AND id NOT IN ($placeholders)
                    ORDER BY is_featured_now DESC, RAND()
                    LIMIT $needed
                ");
                $stmt2->execute([$prov]);
            } else {
                $stmt2 = $pdo->prepare("
                    SELECT id, name, slug, municipality, province,
                           price_per_night, capacity, photo1 AS main_image, latitude, longitude,
                           featured_until,
                           (featured_until IS NOT NULL AND featured_until > NOW()) AS is_featured_now,
                           0 AS distance
                    FROM accommodations
                    WHERE is_active = 1 AND province = ?
                    ORDER BY is_featured_now DESC, RAND()
                    LIMIT $needed
                ");
                $stmt2->execute([$prov]);
            }
            $extra = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            $alojamientos = array_merge($alojamientos, $extra);
        }

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
                HAVING distance < 50
                ORDER BY distance ASC
                LIMIT 8
            ");
            $stmt->execute([$lat, $lng, $lat]);
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

        // Actividades turísticas cercanas (tabla tourist_activities)
        if ($lat && $lng) {
            $stmt = $pdo->prepare("
                SELECT id, name, slug, municipality, province, category_id, photo1 AS main_image,
                       latitude, longitude, price_adult AS price,
                       (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
                FROM tourist_activities
                WHERE is_active = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL
                HAVING distance < 50
                ORDER BY distance ASC
                LIMIT 6
            ");
            $stmt->execute([$lat, $lng, $lat]);
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

        // Eventos similares (misma categoría o provincia, excluyendo el actual)
        if ($lang !== 'es') {
            // Para idiomas distintos al español, hacer JOIN con traducciones
            $stmt = $pdo->prepare("
                SELECT e.id, e.name, e.slug AS slug_es, e.start_date, e.end_date,
                       e.municipality, e.province, e.is_free, e.ticket_price,
                       e.photo1, e.poster_image, e.category_id, e.latitude, e.longitude,
                       t.slug AS slug_trad, t.name AS name_trad
                FROM cultural_events e
                LEFT JOIN cultural_events_trads t ON t.event_id = e.id AND t.language_code = ?
                WHERE e.is_active = 1
                  AND e.slug != ?
                  AND (e.province = ? OR e.category_id = (SELECT category_id FROM cultural_events WHERE slug = ? LIMIT 1))
                  AND (
                    (e.end_date IS NULL AND e.start_date >= CURDATE()) OR
                    (e.end_date IS NOT NULL AND e.end_date >= CURDATE())
                  )
                ORDER BY e.start_date ASC
                LIMIT 6
            ");
            $stmt->execute([$lang, $slug, $prov, $slug]);
            $similares = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $lang_prefix = '/' . $lang;
            foreach ($similares as &$s) {
                // Usar slug traducido si existe, si no usar el español
                $slug_final = !empty($s['slug_trad']) ? $s['slug_trad'] : $s['slug_es'];
                $s['slug'] = $slug_final;
                // Usar nombre traducido si existe
                if (!empty($s['name_trad'])) {
                    $s['name'] = $s['name_trad'];
                }
                // URL con prefijo de idioma
                $s['url'] = $lang_prefix . '/evento/' . $slug_final;
                $s['imagen'] = $s['poster_image'] ?: $s['photo1'] ?: null;
                unset($s['photo1'], $s['poster_image'], $s['slug_trad'], $s['name_trad'], $s['slug_es']);
            }
        } else {
            $stmt = $pdo->prepare("
                SELECT id, name, slug, start_date, end_date, municipality, province,
                       is_free, ticket_price, photo1, poster_image, category_id,
                       latitude, longitude
                FROM cultural_events
                WHERE is_active = 1
                  AND slug != ?
                  AND (province = ? OR category_id = (SELECT category_id FROM cultural_events WHERE slug = ? LIMIT 1))
                  AND (
                    (end_date IS NULL AND start_date >= CURDATE()) OR
                    (end_date IS NOT NULL AND end_date >= CURDATE())
                  )
                ORDER BY start_date ASC
                LIMIT 6
            ");
            $stmt->execute([$slug, $prov, $slug]);
            $similares = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($similares as &$s) {
                $s['url'] = '/evento/' . $s['slug'];
                $s['imagen'] = $s['poster_image'] ?: $s['photo1'] ?: null;
                unset($s['photo1'], $s['poster_image']);
            }
        }
        $result['eventos_similares'] = $similares;
        $result['limit_initial'] = $limit_initial;

        // Contador de visitas del evento actual (incrementar)
        try {
            $pdo->prepare("UPDATE cultural_events SET views = COALESCE(views, 0) + 1 WHERE slug = ?")->execute([$slug]);
        } catch (Exception $e) { /* columna views puede no existir */ }

        echo json_encode(['success' => true, 'data' => $result]);
        exit;
    }

    // ─── MODO MINIMAL o FULL: Datos del evento ───────────────────────────────
    $evento = null;
    $traduccion = null;

    if ($lang === 'es') {
        $stmt = $pdo->prepare("
            SELECT e.id, e.name AS titulo, e.slug, e.description, e.short_description,
                   e.meta_title, e.meta_description, e.start_date, e.end_date,
                   e.venue_name AS localidad, e.venue_address, e.municipality, e.province,
                   e.latitude, e.longitude, e.is_free, e.ticket_price, e.organizer,
                   e.photo1, e.photo2, e.photo3, e.photo4, e.poster_image,
                   e.category_id, e.is_active, e.status,
                   e.program, e.target_audience, e.accessibility
            FROM cultural_events e
            WHERE e.slug = ? AND e.is_active = 1
        ");
        $stmt->execute([$slug]);
    } else {
        // Buscar traducción
        $stmt_trad = $pdo->prepare("
            SELECT event_id, name AS titulo_trad, slug AS slug_trad,
                   short_description AS short_desc_trad, description AS descripcion_trad,
                   meta_title AS meta_title_trad, meta_description AS meta_desc_trad,
                   program AS programa_trad, target_audience AS audiencia_trad,
                   accessibility AS accesibilidad_trad
            FROM cultural_events_trads
            WHERE slug = ? AND language_code = ?
        ");
        $stmt_trad->execute([$slug, $lang]);
        $traduccion = $stmt_trad->fetch(PDO::FETCH_ASSOC);

        if ($traduccion) {
            $stmt = $pdo->prepare("
                SELECT e.id, e.name AS titulo, e.slug, e.description, e.short_description,
                       e.meta_title, e.meta_description, e.start_date, e.end_date,
                       e.venue_name AS localidad, e.venue_address, e.municipality, e.province,
                       e.latitude, e.longitude, e.is_free, e.ticket_price, e.organizer,
                       e.photo1, e.photo2, e.photo3, e.photo4, e.poster_image,
                       e.category_id, e.is_active, e.status
                FROM cultural_events e
                WHERE e.id = ? AND e.is_active = 1
            ");
            $stmt->execute([$traduccion['event_id']]);
        } else {
            $stmt = $pdo->prepare("
                SELECT e.id, e.name AS titulo, e.slug, e.description, e.short_description,
                       e.meta_title, e.meta_description, e.start_date, e.end_date,
                       e.venue_name AS localidad, e.venue_address, e.municipality, e.province,
                       e.latitude, e.longitude, e.is_free, e.ticket_price, e.organizer,
                       e.photo1, e.photo2, e.photo3, e.photo4, e.poster_image,
                       e.category_id, e.is_active, e.status
                FROM cultural_events e
                WHERE e.slug = ? AND e.is_active = 1
            ");
            $stmt->execute([$slug]);
        }
    }

    $evento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$evento) {
        http_response_code(404);
        echo json_encode(['error' => 'Evento no encontrado']);
        exit;
    }

    // Combinar con traducción si existe
    if ($traduccion) {
        $evento['titulo']            = $traduccion['titulo_trad']    ?? $evento['titulo'];
        $evento['description']       = $traduccion['descripcion_trad'] ?? $evento['description'];
        $evento['short_description'] = $traduccion['short_desc_trad'] ?? $evento['short_description'];
        $evento['meta_title']        = $traduccion['meta_title_trad'] ?? $evento['meta_title'];
        $evento['meta_description']  = $traduccion['meta_desc_trad']  ?? $evento['meta_description'];
        $evento['programa']          = $traduccion['programa_trad']   ?? '';
        $evento['audiencia']         = $traduccion['audiencia_trad']  ?? '';
        $evento['accesibilidad']     = $traduccion['accesibilidad_trad'] ?? '';
        $evento['slug_trad']         = $traduccion['slug_trad']       ?? $evento['slug'];
    } else {
        // Para español: mapear columnas nativas al alias esperado
        $evento['programa']      = $evento['program']         ?? '';
        $evento['audiencia']     = $evento['target_audience'] ?? '';
        $evento['accesibilidad'] = $evento['accessibility']   ?? '';
    }

    // Construir array de fotos
    $fotos = [];
    foreach (['photo1','photo2','photo3','photo4','poster_image'] as $campo) {
        if (!empty($evento[$campo])) {
            $url = $evento[$campo];
            if (!preg_match('/^https?:\/\//', $url)) {
                $url = '/' . ltrim($url, '/');
            }
            $fotos[] = $url;
        }
    }
    $evento['fotos'] = $fotos;

    // Mapeo de categorías
    $categorias = [
        1=>'Fiestas Populares',2=>'Fiestas Patronales',3=>'Fiestas Tradicionales',
        4=>'Romerías',5=>'Carnavales',6=>'Cultura y Espectáculos',7=>'Conciertos',
        8=>'Teatro',9=>'Exposiciones',10=>'Festivales de Música',11=>'Cine',
        12=>'Gastronomía y Ferias',13=>'Ferias Gastronómicas',14=>'Jornadas Gastronómicas',
        15=>'Mercados Tradicionales',16=>'Ferias de Productos Locales',17=>'Deportes',
        18=>'Carreras Populares',19=>'Maratones y Medias',20=>'Competiciones Ciclistas',
        21=>'Eventos Deportivos',22=>'Religión y Tradición',23=>'Semana Santa',
        24=>'Procesiones',25=>'Celebraciones Religiosas'
    ];
    $evento['categoria_nombre'] = $categorias[$evento['category_id']] ?? 'Cultura';

    // En modo minimal, devolver solo datos críticos
    if ($mode === 'minimal') {
        echo json_encode(['success' => true, 'data' => [
            'id'               => $evento['id'],
            'titulo'           => $evento['titulo'],
            'slug'             => $evento['slug'],
            'short_description'=> $evento['short_description'],
            'meta_title'       => $evento['meta_title'],
            'meta_description' => $evento['meta_description'],
            'start_date'       => $evento['start_date'],
            'end_date'         => $evento['end_date'],
            'localidad'        => $evento['localidad'],
            'municipality'     => $evento['municipality'],
            'province'         => $evento['province'],
            'latitude'         => $evento['latitude'],
            'longitude'        => $evento['longitude'],
            'is_free'          => $evento['is_free'],
            'ticket_price'     => $evento['ticket_price'],
            'categoria_nombre' => $evento['categoria_nombre'],
            'fotos'            => array_slice($fotos, 0, 1), // Solo primera foto
        ]]);
        exit;
    }

    // Modo full: devolver todo
    echo json_encode(['success' => true, 'data' => $evento]);

} catch (Exception $e) {
    error_log('evento-data.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
