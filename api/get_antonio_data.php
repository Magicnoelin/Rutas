<?php
/**
 * get_antonio_data.php - API para Antonio, el experto local
 * Devuelve datos REALES de la base de datos con slugs, fotos y URLs completas
 * Incluye: accommodations, places_of_interest, tourist_activities, cultural_events, routes
 */
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$response = [
    'accommodations'     => [],
    'places_of_interest' => [],
    'tourist_activities' => [],
    'cultural_events'    => [],
    'routes'             => []
];

try {
    $pdo = getDBConnection();

    // ── 1. ALOJAMIENTOS ──────────────────────────────────────────────
    $stmt = $pdo->query("
        SELECT a.id, a.name AS nombre, a.slug,
               COALESCE(a.short_description, a.description) AS descripcion,
               a.municipality AS ubicacion, a.province,
               a.price_per_night AS precio,
               a.photo1,
               a.website
        FROM accommodations a
        WHERE a.is_active = 1
        ORDER BY a.is_featured DESC, a.created_at DESC
        LIMIT 50
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $foto = $row['photo1'] ?? '';
        if ($foto && !preg_match('/^https?:\/\//', $foto)) {
            $foto = 'https://rutasrurales.io/img/alojamientos/' . ($row['slug'] ?? '') . '/' . basename($foto);
        }
        $response['accommodations'][] = [
            'id'          => (int)$row['id'],
            'nombre'      => $row['nombre'],
            'slug'        => $row['slug'],
            'descripcion' => mb_substr(strip_tags($row['descripcion'] ?? ''), 0, 150),
            'ubicacion'   => $row['ubicacion'] ?: $row['province'],
            'province'    => $row['province'],
            'precio'      => $row['precio'] ? 'Desde ' . number_format((float)$row['precio'], 0) . '€/noche' : 'Consultar',
            'foto'        => $foto,
            'icono'       => '🏨',
            'url'         => 'https://rutasrurales.io/alojamiento/' . ($row['slug'] ?? ''),
        ];
    }

    // ── 2. LUGARES DE INTERÉS ─────────────────────────────────────────
    $stmt = $pdo->query("
        SELECT p.id, p.name AS nombre, p.slug,
               COALESCE(p.short_description, p.description) AS descripcion,
               p.municipality AS ubicacion, p.province,
               p.entry_fee AS precio,
               p.photo1
        FROM places_of_interest p
        WHERE p.is_active = 1
        ORDER BY p.is_featured DESC, p.created_at DESC
        LIMIT 50
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $foto = $row['photo1'] ?? '';
        if ($foto && !preg_match('/^https?:\/\//', $foto)) {
            $foto = 'https://rutasrurales.io/interest_places_images/' . basename($foto);
        }
        $response['places_of_interest'][] = [
            'id'          => (int)$row['id'],
            'nombre'      => $row['nombre'],
            'slug'        => $row['slug'],
            'descripcion' => mb_substr(strip_tags($row['descripcion'] ?? ''), 0, 150),
            'ubicacion'   => $row['ubicacion'] ?: $row['province'],
            'province'    => $row['province'],
            'precio'      => (!empty($row['precio']) && $row['precio'] > 0)
                ? number_format((float)$row['precio'], 2) . '€' : 'Entrada gratuita',
            'foto'        => $foto,
            'icono'       => '🏛️',
            'url'         => 'https://rutasrurales.io/lugar/' . ($row['slug'] ?? ''),
        ];
    }

    // ── 3. ACTIVIDADES TURÍSTICAS ─────────────────────────────────────
    $stmt = $pdo->query("
        SELECT t.id, t.name AS nombre, t.slug,
               COALESCE(t.short_description, t.description) AS descripcion,
               t.municipality AS ubicacion, t.province,
               t.price_adult AS precio, t.duration,
               t.photo1
        FROM tourist_activities t
        WHERE t.is_active = 1
        ORDER BY t.is_featured DESC, t.created_at DESC
        LIMIT 50
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $foto = $row['photo1'] ?? '';
        if ($foto && !preg_match('/^https?:\/\//', $foto)) {
            $foto = 'https://rutasrurales.io/img/actividades/' . basename($foto);
        }
        $response['tourist_activities'][] = [
            'id'          => (int)$row['id'],
            'nombre'      => $row['nombre'],
            'slug'        => $row['slug'],
            'descripcion' => mb_substr(strip_tags($row['descripcion'] ?? ''), 0, 150),
            'ubicacion'   => $row['ubicacion'] ?: $row['province'],
            'province'    => $row['province'],
            'duracion'    => $row['duration'] ?? '',
            'precio'      => (!empty($row['precio']) && $row['precio'] > 0)
                ? 'Desde ' . number_format((float)$row['precio'], 0) . '€' : 'Consultar',
            'foto'        => $foto,
            'icono'       => '🥾',
            'url'         => 'https://rutasrurales.io/actividad/' . ($row['slug'] ?? ''),
        ];
    }

    // ── 4. EVENTOS CULTURALES (solo desde hoy en adelante) ────────────
    $hoy = date('Y-m-d');
    $stmt = $pdo->prepare("
        SELECT e.id, e.name AS nombre, e.slug,
               COALESCE(e.short_description, e.description) AS descripcion,
               e.municipality AS ubicacion, e.province,
               e.start_date, e.end_date,
               e.is_free, e.ticket_price AS precio,
               e.poster_image, e.photo1
        FROM cultural_events e
        WHERE e.is_active = 1
          AND (e.end_date IS NULL OR e.end_date >= ?)
        ORDER BY e.start_date ASC
        LIMIT 50
    ");
    $stmt->execute([$hoy]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $foto = $row['poster_image'] ?: $row['photo1'] ?? '';
        if ($foto && !preg_match('/^https?:\/\//', $foto)) {
            $foto = 'https://rutasrurales.io/cultural_events_images/' . basename($foto);
        }
        $fecha = $row['start_date'] ?? '';
        if ($row['end_date'] && $row['end_date'] !== $row['start_date']) {
            $fecha .= ' → ' . $row['end_date'];
        }
        $response['cultural_events'][] = [
            'id'          => (int)$row['id'],
            'nombre'      => $row['nombre'],
            'slug'        => $row['slug'],
            'descripcion' => mb_substr(strip_tags($row['descripcion'] ?? ''), 0, 150),
            'ubicacion'   => $row['ubicacion'] ?: $row['province'],
            'province'    => $row['province'],
            'fecha'       => $fecha,
            'precio'      => $row['is_free'] ? 'Entrada gratuita'
                : (!empty($row['precio']) ? number_format((float)$row['precio'], 2) . '€' : 'Consultar'),
            'foto'        => $foto,
            'icono'       => '🎭',
            'url'         => 'https://rutasrurales.io/evento/' . ($row['slug'] ?? ''),
        ];
    }

    // ── 5. RUTAS TEMÁTICAS ────────────────────────────────────────────
    $stmt = $pdo->query("
        SELECT r.id, r.name AS nombre, r.slug,
               r.description AS descripcion,
               r.province, r.duration_days,
               r.difficulty_level, r.season,
               r.hero_image, r.cover_color,
               r.seo_title
        FROM routes r
        WHERE r.status = 'published' AND r.is_public = 1
        ORDER BY r.is_featured DESC, r.created_at DESC
        LIMIT 20
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $foto = $row['hero_image'] ?? '';
        if ($foto && !preg_match('/^https?:\/\//', $foto)) {
            $foto = 'https://rutasrurales.io/' . ltrim($foto, '/');
        }
        $dificultad = [
            'facil'    => 'Fácil',
            'moderado' => 'Moderada',
            'dificil'  => 'Difícil',
        ][$row['difficulty_level']] ?? $row['difficulty_level'];

        $response['routes'][] = [
            'id'          => (int)$row['id'],
            'nombre'      => $row['nombre'],
            'slug'        => $row['slug'],
            'descripcion' => mb_substr(strip_tags($row['descripcion'] ?? ''), 0, 200),
            'ubicacion'   => $row['province'] ?? '',
            'province'    => $row['province'] ?? '',
            'duracion'    => (int)$row['duration_days'] . ' días',
            'dificultad'  => $dificultad,
            'temporada'   => $row['season'] ?? '',
            'foto'        => $foto ?: '',
            'color'       => $row['cover_color'] ?? '#2F5233',
            'icono'       => '🗺️',
            'url'         => 'https://rutasrurales.io/rutas/' . ($row['slug'] ?? ''),
        ];
    }

} catch (Exception $e) {
    error_log('get_antonio_data.php ERROR: ' . $e->getMessage());
    // Devolver arrays vacíos si hay error
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
