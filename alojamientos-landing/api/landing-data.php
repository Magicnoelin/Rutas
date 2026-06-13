<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  CAPA DE DATOS — Landing Pages de Alojamientos
 *  rutasrurales.io · NO incluir directamente desde el exterior (no headers)
 * ════════════════════════════════════════════════════════════════════════════
 *
 *  Funciones públicas:
 *    getLandingAccommodations()  → listado paginado con filtros
 *    getLandingStats()           → estadísticas hero (count, avg price)
 *    getSemanticCrossing()       → lugares_interes + rutas para cruce semántico
 *    getUpcomingEvents()         → próximos eventos de la provincia
 *
 *  SEGURIDAD: Los valores de los filtros SQL vienen de LANDING_FILTROS (constantes
 *  hardcoded). Nunca se interpolan datos del usuario en la query.
 */

if (!defined('LANDING_DATA_LOADED')) {
    define('LANDING_DATA_LOADED', true);
}

// ── Constantes de paginación ─────────────────────────────────────────────────
const LANDING_PER_PAGE = 12;

/**
 * Construye y ejecuta la query principal de alojamientos.
 *
 * @param PDO        $pdo
 * @param string|null $province_db   Valor exacto de la columna `province` (o null)
 * @param string[]   $sql_conditions Array de strings SQL raw (de LANDING_FILTROS['sql'])
 * @param int        $page           Página actual (1-indexed)
 * @param int        $per_page       Resultados por página
 * @return array{items: array, total: int, pages: int}
 */
function getLandingAccommodations(
    PDO    $pdo,
    ?string $province_db,
    array  $sql_conditions,
    int    $page     = 1,
    int    $per_page = LANDING_PER_PAGE,
    string $lang     = 'es'
): array {
    $where  = ['a.is_active = 1'];
    $params = [];

    if (!empty($province_db)) {
        $where[]        = 'a.province = :province';
        $params[':province'] = $province_db;
    }

    // Condiciones SQL hardcoded de los filtros (no user-input)
    foreach ($sql_conditions as $cond) {
        if (!empty(trim($cond)) && $cond !== '1=1') {
            $where[] = $cond;
        }
    }

    $whereClause = 'WHERE ' . implode(' AND ', $where);

    // Total para paginación
    // (el count no necesita el parámetro de idioma si se añadiera alguno en el futuro)
    $countSql = "
        SELECT COUNT(DISTINCT a.id)
        FROM accommodations a
        LEFT JOIN categories_accommodations c ON a.category_id = c.id
        $whereClause
    ";
    $stmtCount = $pdo->prepare($countSql);
    $stmtCount->execute($params);
    $total = (int)$stmtCount->fetchColumn();
    $pages = $total > 0 ? (int)ceil($total / $per_page) : 0;

    // Sanitizar página
    $page   = max(1, min($page, max(1, $pages)));
    $offset = ($page - 1) * $per_page;

    // Query principal
    // Columnas verificadas contra el esquema real de la BD:
    // wifi, check_in_time, check_out_time, bedrooms NO existen → eliminadas
    $sql = "
        SELECT
            a.id, a.name, a.slug, a.municipality, a.province,
            a.short_description, a.description,
            a.price_per_night, a.capacity,
            a.photo1, a.photo2, a.photo3,
            a.latitude, a.longitude,
            a.pet_friendly, a.suitable_for_children,
            a.kitchen_available, a.amenities,
            a.accommodation_type,
            c.name AS category_name
        FROM accommodations a
        LEFT JOIN categories_accommodations c ON a.category_id = c.id
        $whereClause
        ORDER BY
            CASE WHEN a.price_per_night > 0 THEN 0 ELSE 1 END ASC,
            a.price_per_night ASC,
            a.name ASC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit',  $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,   PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Normalizar fotos y URLs
    foreach ($rows as &$row) {
        $row['photo_url'] = _normalizePhoto($row['photo1'] ?? '', $row['slug'] ?? '');
        $row['url']       = 'https://rutasrurales.io/alojamiento/' . ($row['slug'] ?? '');

        // Decodificar amenities JSON si existe
        if (!empty($row['amenities'])) {
            $decoded = json_decode($row['amenities'], true);
            $row['amenities_arr'] = is_array($decoded) ? $decoded : [];
        } else {
            $row['amenities_arr'] = [];
        }

        // Precio display
        $row['precio_display'] = ($row['price_per_night'] > 0)
            ? number_format((float)$row['price_per_night'], 0, ',', '.') . ' €'
            : null;
    }
    unset($row);

    return [
        'items' => $rows,
        'total' => $total,
        'pages' => $pages,
        'page'  => $page,
    ];
}

/**
 * Estadísticas para el hero: total, precio medio, municipios únicos.
 */
function getLandingStats(
    PDO    $pdo,
    ?string $province_db,
    array  $sql_conditions
): array {
    $where  = ['a.is_active = 1'];
    $params = [];

    if (!empty($province_db)) {
        $where[]           = 'a.province = :province';
        $params[':province'] = $province_db;
    }
    foreach ($sql_conditions as $cond) {
        if (!empty(trim($cond)) && $cond !== '1=1') {
            $where[] = $cond;
        }
    }

    $whereClause = 'WHERE ' . implode(' AND ', $where);

    $sql = "
        SELECT
            COUNT(DISTINCT a.id)                               AS total,
            ROUND(AVG(NULLIF(a.price_per_night, 0)), 0)        AS avg_price,
            COUNT(DISTINCT a.municipality)                     AS towns
        FROM accommodations a
        LEFT JOIN categories_accommodations c ON a.category_id = c.id
        $whereClause
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return [
        'total'     => (int)($row['total']     ?? 0),
        'avg_price' => (int)($row['avg_price'] ?? 0),
        'towns'     => (int)($row['towns']     ?? 0),
    ];
}

/**
 * Cruce semántico: lugares de interés + rutas de la provincia.
 * Esto es lo que diferencia la landing de Booking/Airbnb.
 *
 * @return array{places: array, routes: array}
 */
function getSemanticCrossing(PDO $pdo, ?string $province_db, int $limit = 6): array
{
    $places = [];
    $routes = [];

    if (empty($province_db)) {
        return compact('places', 'routes');
    }

    // 1. Lugares de interés de la provincia
    try {
        $stmtP = $pdo->prepare("
            SELECT p.id, p.name, p.slug, p.short_description, p.description,
                   p.municipality, p.entry_fee, p.photo1, p.photo2
            FROM places_of_interest p
            WHERE p.province = :province AND p.is_active = 1
            ORDER BY
                CASE WHEN p.is_featured = 1 THEN 0 ELSE 1 END ASC,
                p.name ASC
            LIMIT :limit
        ");
        $stmtP->bindValue(':province', $province_db);
        $stmtP->bindValue(':limit',    $limit, PDO::PARAM_INT);
        $stmtP->execute();
        $rawPlaces = $stmtP->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rawPlaces as $p) {
            $p['photo_url']      = _normalizePhoto($p['photo1'] ?? '', $p['slug'] ?? '');
            $p['url']            = 'https://rutasrurales.io/lugar/' . ($p['slug'] ?? '');
            $p['entry_display']  = (!empty($p['entry_fee']) && $p['entry_fee'] > 0)
                ? number_format((float)$p['entry_fee'], 2) . ' €'
                : null; // null = gratis
            $places[] = $p;
        }
    } catch (PDOException $e) {
        error_log('[landing-data] places_of_interest query error: ' . $e->getMessage());
    }

    // 2. Rutas temáticas de la provincia
    try {
        $stmtR = $pdo->prepare("
            SELECT r.id, r.name, r.slug, r.description,
                   r.duration_days, r.difficulty_level, r.hero_image, r.province
            FROM routes r
            WHERE r.province = :province
              AND r.status = 'published'
              AND r.is_public = 1
            ORDER BY r.is_featured DESC, r.views_count DESC
            LIMIT :limit
        ");
        $stmtR->bindValue(':province', $province_db);
        $stmtR->bindValue(':limit',    min($limit, 4), PDO::PARAM_INT);
        $stmtR->execute();
        $rawRoutes = $stmtR->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rawRoutes as $r) {
            $r['url'] = 'https://rutasrurales.io/rutas/' . ($r['slug'] ?? '');
            $routes[] = $r;
        }
    } catch (PDOException $e) {
        // La tabla routes puede no tener datos para esa provincia → silencioso
        error_log('[landing-data] routes query error: ' . $e->getMessage());
    }

    return compact('places', 'routes');
}

/**
 * Próximos eventos culturales de la provincia (máx 4).
 */
function getUpcomingEvents(PDO $pdo, ?string $province_db, int $limit = 4): array
{
    if (empty($province_db)) return [];

    try {
        // Solo eventos futuros o en curso (end_date >= hoy, o start_date >= hoy si no hay end_date)
        $stmt = $pdo->prepare("
            SELECT e.id, e.name AS title, e.slug,
                   e.short_description, e.municipality,
                   e.start_date, e.end_date, e.is_free, e.ticket_price,
                   e.poster_image, e.photo1
            FROM cultural_events e
            WHERE e.province = :province
              AND e.is_active = 1
              AND COALESCE(e.end_date, e.start_date) >= CURDATE()
            ORDER BY e.start_date ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':province', $province_db);
        $stmt->bindValue(':limit',    $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$e) {
            // Construir URL de foto de forma robusta:
            // - Si ya es URL completa → usar tal cual
            // - Si contiene '/' → es una ruta relativa → añadir dominio
            // - Si es solo un nombre de archivo → añadir directorio de eventos
            $img = $e['poster_image'] ?: $e['photo1'] ?: null;
            if (!$img) {
                $e['photo_url'] = null;
            } elseif (preg_match('/^https?:\/\//', $img)) {
                $e['photo_url'] = $img;
            } elseif (str_contains($img, '/')) {
                // Ruta relativa con subdirectorio (ej: "cultural_events_images/foto.jpg")
                $e['photo_url'] = 'https://rutasrurales.io/' . ltrim($img, '/');
            } else {
                // Solo nombre de archivo → asumir directorio estándar
                $e['photo_url'] = 'https://rutasrurales.io/cultural_events_images/' . $img;
            }
            $e['url']            = 'https://rutasrurales.io/evento/' . ($e['slug'] ?? '');
            $e['precio_display'] = $e['is_free'] ? null : (!empty($e['ticket_price']) ? number_format((float)$e['ticket_price'], 2) . ' €' : null);
        }
        unset($e);
        return $rows;
    } catch (PDOException $ex) {
        error_log('[landing-data] events query error: ' . $ex->getMessage());
        return [];
    }
}

// ─── Helpers privados ─────────────────────────────────────────────────────────

function _normalizePhoto(string $photo, string $slug): string
{
    $fallback = 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=600&h=400&fit=crop&auto=format&q=75';
    if (empty($photo)) return $fallback;
    if (preg_match('/^https?:\/\//', $photo)) return $photo;
    // Ruta relativa
    return 'https://rutasrurales.io/' . ltrim($photo, '/');
}
