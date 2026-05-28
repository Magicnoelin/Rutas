<?php
/**
 * API: Obtener Ruta Temática Completa por Slug
 * Endpoint: /rutas-tematicas/api/ruta-slug.php?slug=puente-1-mayo-soria
 *
 * Devuelve la ruta con todos sus items enriquecidos desde las 4 tablas reales:
 *   - accommodations
 *   - places_of_interest
 *   - cultural_events
 *   - tourist_activities
 *
 * Reutilizable para cualquier ruta: castillos, vinos, puentes, etc.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300'); // 5 min cache

require_once __DIR__ . '/../../api/config.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug) || !preg_match('/^[a-z0-9\-]+$/', $slug)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Slug inválido']);
    exit;
}

try {
    $pdo = getDBConnection();

    // ── 1. Obtener la ruta base ──────────────────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT
            r.id, r.name, r.slug, r.description, r.duration_days,
            r.difficulty_level, r.youtube_url,
            r.status, r.views_count, r.is_public, r.is_featured,
            r.route_type, r.hero_image, r.seo_keywords,
            r.seo_title, r.seo_description, r.province,
            r.season, r.cover_color, r.itinerary_json,
            r.created_at
        FROM routes r
        WHERE r.slug = :slug
          AND r.status = 'published'
          AND r.is_public = 1
        LIMIT 1
    ");
    $stmt->execute([':slug' => $slug]);
    $ruta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ruta) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Ruta no encontrada']);
        exit;
    }

    // Decodificar JSON almacenados
    $ruta['map_points']     = json_decode($ruta['map_points'] ?? '[]', true);
    $ruta['discounts']      = json_decode($ruta['discounts'] ?? '[]', true);
    $ruta['itinerary_json'] = json_decode($ruta['itinerary_json'] ?? '[]', true);

    // ── 2. Obtener items de la ruta ──────────────────────────────────────────
    $stmtItems = $pdo->prepare("
        SELECT id, item_type, item_id, item_name, display_order,
               day_number, time_slot, editorial_note, is_highlight
        FROM route_items
        WHERE route_id = :route_id
        ORDER BY day_number ASC, display_order ASC
    ");
    $stmtItems->execute([':route_id' => $ruta['id']]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    // ── 3. Enriquecer cada item con datos reales de las 4 tablas ────────────
    $alojamientos = [];
    $lugares      = [];
    $actividades  = [];
    $eventos_ruta = [];

    // Agrupar IDs por tipo para hacer consultas eficientes (IN en lugar de N queries)
    $ids_alojamiento = [];
    $ids_lugar       = [];
    $ids_actividad   = [];
    $ids_evento      = [];

    foreach ($items as $item) {
        switch ($item['item_type']) {
            case 'alojamiento': $ids_alojamiento[] = (int)$item['item_id']; break;
            case 'lugar':       $ids_lugar[]       = (int)$item['item_id']; break;
            case 'actividad':   $ids_actividad[]   = (int)$item['item_id']; break;
            case 'evento':      $ids_evento[]       = (int)$item['item_id']; break;
        }
    }

    // ── 3a. Alojamientos ────────────────────────────────────────────────────
    if (!empty($ids_alojamiento)) {
        $placeholders = implode(',', array_fill(0, count($ids_alojamiento), '?'));
        $stmtA = $pdo->prepare("
            SELECT a.id, a.name, a.slug, a.description, a.short_description,
                   a.municipality, a.province, a.address, a.phone, a.email,
                   a.website, a.price_per_night, a.capacity,
                   a.photo1, a.photo2, a.photo3,
                   a.latitude, a.longitude,
                   c.name as category_name
            FROM accommodations a
            LEFT JOIN categories_accommodations c ON a.category_id = c.id
            WHERE a.id IN ($placeholders)
              AND a.is_active = 1
        ");
        $stmtA->execute($ids_alojamiento);
        $rawAloj = $stmtA->fetchAll(PDO::FETCH_ASSOC);

        // Indexar por ID para merge rápido
        $alojMap = [];
        foreach ($rawAloj as $a) {
            $a['fotos'] = array_values(array_filter([
                $a['photo1'] ?? null,
                $a['photo2'] ?? null,
                $a['photo3'] ?? null,
            ]));
            // Construir URL de foto si es solo nombre de archivo
            $a['fotos'] = array_map(function($f) use ($a) {
                if (preg_match('/^https?:\/\//', $f)) return $f;
                return 'https://rutasrurales.io/img/alojamientos/' . ($a['slug'] ?? '') . '/' . basename($f);
            }, $a['fotos']);
            $a['url'] = 'https://rutasrurales.io/alojamiento/' . ($a['slug'] ?? '');
            $alojMap[$a['id']] = $a;
        }

        foreach ($items as $item) {
            if ($item['item_type'] === 'alojamiento' && isset($alojMap[$item['item_id']])) {
                $merged = array_merge($alojMap[$item['item_id']], [
                    'day_number'    => $item['day_number'],
                    'time_slot'     => $item['time_slot'],
                    'editorial_note'=> $item['editorial_note'],
                    'is_highlight'  => (bool)$item['is_highlight'],
                    'display_order' => $item['display_order'],
                ]);
                $alojamientos[] = $merged;
            }
        }
    }

    // ── 3b. Lugares de interés ───────────────────────────────────────────────
    if (!empty($ids_lugar)) {
        $placeholders = implode(',', array_fill(0, count($ids_lugar), '?'));
        $stmtL = $pdo->prepare("
            SELECT p.id, p.name, p.slug, p.description, p.short_description,
                   p.municipality, p.province, p.address, p.phone,
                   p.website, p.opening_hours, p.entry_fee,
                   p.photo1, p.photo2, p.photo3,
                   p.latitude, p.longitude
            FROM places_of_interest p
            WHERE p.id IN ($placeholders)
              AND p.is_active = 1
        ");
        $stmtL->execute($ids_lugar);
        $rawLug = $stmtL->fetchAll(PDO::FETCH_ASSOC);

        $lugMap = [];
        foreach ($rawLug as $l) {
            $l['fotos'] = array_values(array_filter([
                $l['photo1'] ?? null,
                $l['photo2'] ?? null,
                $l['photo3'] ?? null,
            ]));
            $l['fotos'] = array_map(function($f) {
                if (preg_match('/^https?:\/\//', $f)) return $f;
                return 'https://rutasrurales.io/interest_places_images/' . basename($f);
            }, $l['fotos']);
            $l['precio_entrada'] = (!empty($l['entry_fee']) && $l['entry_fee'] > 0)
                ? number_format($l['entry_fee'], 2) . '€'
                : 'Entrada gratuita';
            $l['url'] = 'https://rutasrurales.io/lugar/' . ($l['slug'] ?? '');
            $lugMap[$l['id']] = $l;
        }

        foreach ($items as $item) {
            if ($item['item_type'] === 'lugar' && isset($lugMap[$item['item_id']])) {
                $merged = array_merge($lugMap[$item['item_id']], [
                    'day_number'    => $item['day_number'],
                    'time_slot'     => $item['time_slot'],
                    'editorial_note'=> $item['editorial_note'],
                    'is_highlight'  => (bool)$item['is_highlight'],
                    'display_order' => $item['display_order'],
                ]);
                $lugares[] = $merged;
            }
        }
    }

    // ── 3c. Actividades turísticas ───────────────────────────────────────────
    if (!empty($ids_actividad)) {
        $placeholders = implode(',', array_fill(0, count($ids_actividad), '?'));
        $stmtAct = $pdo->prepare("
            SELECT t.id, t.name, t.slug, t.description, t.short_description,
                   t.municipality, t.province, t.duration, t.difficulty_level,
                   t.price_adult, t.price_child, t.min_participants, t.max_participants,
                   t.available_seasons, t.contact_phone, t.website, t.booking_url,
                   t.photo1, t.photo2, t.photo3,
                   t.latitude, t.longitude
            FROM tourist_activities t
            WHERE t.id IN ($placeholders)
              AND t.is_active = 1
        ");
        $stmtAct->execute($ids_actividad);
        $rawAct = $stmtAct->fetchAll(PDO::FETCH_ASSOC);

        $actMap = [];
        foreach ($rawAct as $a) {
            $a['fotos'] = array_values(array_filter([
                $a['photo1'] ?? null,
                $a['photo2'] ?? null,
                $a['photo3'] ?? null,
            ]));
            $a['fotos'] = array_map(function($f) {
                if (preg_match('/^https?:\/\//', $f)) return $f;
                return 'https://rutasrurales.io/img/actividades/' . basename($f);
            }, $a['fotos']);
            $a['precio_display'] = (!empty($a['price_adult']) && $a['price_adult'] > 0)
                ? 'Desde ' . number_format($a['price_adult'], 2) . '€/persona'
                : 'Consultar precio';
            $a['url'] = 'https://rutasrurales.io/actividad/' . ($a['slug'] ?? '');
            $actMap[$a['id']] = $a;
        }

        foreach ($items as $item) {
            if ($item['item_type'] === 'actividad' && isset($actMap[$item['item_id']])) {
                $merged = array_merge($actMap[$item['item_id']], [
                    'day_number'    => $item['day_number'],
                    'time_slot'     => $item['time_slot'],
                    'editorial_note'=> $item['editorial_note'],
                    'is_highlight'  => (bool)$item['is_highlight'],
                    'display_order' => $item['display_order'],
                ]);
                $actividades[] = $merged;
            }
        }
    }

    // ── 3d. Eventos del período (dinámico por fechas de la ruta) ────────────
    // Para rutas temporales: carga eventos del período de la ruta
    // Para rutas temáticas: carga eventos vinculados por item_id
    $fecha_inicio = null;
    $fecha_fin    = null;

    if (!empty($ruta['itinerary_json'])) {
        $dias = $ruta['itinerary_json'];
        if (!empty($dias[0]['fecha'])) $fecha_inicio = $dias[0]['fecha'];
        if (!empty($dias[count($dias)-1]['fecha'])) $fecha_fin = $dias[count($dias)-1]['fecha'];
    }

    if ($fecha_inicio && $fecha_fin) {
        // Cargar eventos del período de la ruta (solo actuales y futuros)
        $stmtEv = $pdo->prepare("
            SELECT e.id, e.name as title, e.slug, e.description, e.short_description,
                   e.venue_name, e.municipality, e.province,
                   e.start_date, e.end_date, e.start_time,
                   e.is_free, e.ticket_price, e.ticket_url,
                   e.organizer, e.poster_image, e.photo1,
                   e.latitude, e.longitude
            FROM cultural_events e
            WHERE e.is_active = 1
              AND e.start_date >= CURDATE()
              AND e.start_date <= :fecha_fin
              AND COALESCE(e.end_date, e.start_date) >= :fecha_inicio
            ORDER BY e.start_date ASC
            LIMIT 12
        ");
        $stmtEv->execute([
            ':fecha_inicio' => $fecha_inicio,
            ':fecha_fin'    => $fecha_fin,
        ]);
        $rawEv = $stmtEv->fetchAll(PDO::FETCH_ASSOC);
    } elseif (!empty($ids_evento)) {
        // Cargar eventos vinculados explícitamente (solo actuales y futuros)
        $placeholders = implode(',', array_fill(0, count($ids_evento), '?'));
        $stmtEv2 = $pdo->prepare("
            SELECT e.id, e.name as title, e.slug, e.description, e.short_description,
                   e.venue_name, e.municipality, e.province,
                   e.start_date, e.end_date, e.start_time,
                   e.is_free, e.ticket_price, e.ticket_url,
                   e.organizer, e.poster_image, e.photo1,
                   e.latitude, e.longitude
            FROM cultural_events e
            WHERE e.id IN ($placeholders)
              AND e.is_active = 1
              AND e.start_date >= CURDATE()
            ORDER BY e.start_date ASC
        ");
        $stmtEv2->execute($ids_evento);
        $rawEv = $stmtEv2->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $rawEv = [];
    }

    foreach ($rawEv as $e) {
        $imagen = $e['poster_image'] ?: $e['photo1'] ?: null;
        if ($imagen && !preg_match('/^https?:\/\//', $imagen)) {
            $imagen = 'https://rutasrurales.io/cultural_events_images/' . basename($imagen);
        }
        $e['imagen'] = $imagen;
        $e['precio_display'] = $e['is_free'] ? 'Entrada gratuita' : (
            !empty($e['ticket_price']) ? number_format($e['ticket_price'], 2) . '€' : 'Consultar precio'
        );
        $e['url'] = 'https://rutasrurales.io/evento/' . ($e['slug'] ?? '');
        $eventos_ruta[] = $e;
    }

    // ── 4. Incrementar contador de visitas ───────────────────────────────────
    $pdo->prepare("UPDATE routes SET views_count = COALESCE(views_count,0) + 1 WHERE id = :id")
        ->execute([':id' => $ruta['id']]);

    // ── 5. Respuesta final ───────────────────────────────────────────────────
    echo json_encode([
        'success'      => true,
        'ruta'         => $ruta,
        'alojamientos' => $alojamientos,
        'lugares'      => $lugares,
        'actividades'  => $actividades,
        'eventos'      => $eventos_ruta,
        'totales'      => [
            'alojamientos' => count($alojamientos),
            'lugares'      => count($lugares),
            'actividades'  => count($actividades),
            'eventos'      => count($eventos_ruta),
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    error_log('ruta-slug.php ERROR: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
