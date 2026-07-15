<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  CAPA DE DATOS — Landing Pages de Eventos Culturales
 *  rutasrurales.io · NO incluir directamente desde el exterior (no headers)
 * ════════════════════════════════════════════════════════════════════════════
 *
 *  Funciones públicas:
 *    getLandingEventos()          → listado paginado con filtros
 *    getLandingEventosStats()     → estadísticas hero (count, free, towns)
 *    getEventosSemanticCrossing() → alojamientos + lugares para cruce semántico
 *
 *  SEGURIDAD: Los valores de los filtros SQL vienen de EVENTOS_FILTROS (constantes
 *  hardcoded). Nunca se interpolan datos del usuario en la query.
 */

if (!defined('EVENTOS_LANDING_DATA_LOADED')) {
    define('EVENTOS_LANDING_DATA_LOADED', true);
}

// ── Constantes de paginación ─────────────────────────────────────────────────
const EVENTOS_PER_PAGE = 12;

/**
 * Construye y ejecuta la query principal de eventos culturales.
 *
 * @param PDO         $pdo
 * @param string|null $province_db    Valor exacto de la columna `province` (o null)
 * @param string[]    $sql_conditions Array de strings SQL raw (de EVENTOS_FILTROS['sql'])
 * @param int         $page           Página actual (1-indexed)
 * @param int         $per_page       Resultados por página
 * @return array{items: array, total: int, pages: int, page: int}
 */
function getLandingEventos(
    PDO     $pdo,
    ?string $province_db,
    array   $sql_conditions,
    int     $page     = 1,
    int     $per_page = EVENTOS_PER_PAGE,
    string  $lang     = 'es'
): array {
    $where  = ['e.is_active = 1', "e.moderation_status = 'approved'"];
    $params = [];

    // Solo eventos futuros o en curso
    $where[] = 'COALESCE(e.end_date, e.start_date) >= CURDATE()';

    if (!empty($province_db)) {
        $where[]             = 'e.province = :province';
        $params[':province'] = $province_db;
    }

    // Condiciones SQL hardcoded de los filtros (no user-input)
    foreach ($sql_conditions as $cond) {
        if (!empty(trim($cond))) {
            $where[] = $cond;
        }
    }

    $whereClause = 'WHERE ' . implode(' AND ', $where);

    // ── JOIN con traducciones cuando el idioma no es español ──────────────────
    // Si lang = 'es' no se hace JOIN (sin overhead).
    // Si lang ≠ 'es': LEFT JOIN cultural_events_trads para obtener nombre,
    // descripción corta y slug traducido.  COALESCE garantiza fallback al español.
    $joinTrad  = '';
    $selectTrad = '';
    if ($lang !== 'es') {
        $params[':lang'] = $lang;
        $joinTrad = "LEFT JOIN cultural_events_trads t
                       ON t.event_id = e.id AND t.language_code = :lang";
        $selectTrad = "
            COALESCE(t.name,              e.name)              AS name,
            COALESCE(t.short_description, e.short_description) AS short_description,
            COALESCE(t.slug,              e.slug)              AS trad_slug,";
    } else {
        $selectTrad = "
            e.name,
            e.short_description,
            e.slug AS trad_slug,";
    }

    // Total para paginación
    $countSql = "
        SELECT COUNT(DISTINCT e.id)
        FROM cultural_events e
        $whereClause
    ";
    // El count no necesita el JOIN de traducciones (no afecta al total)
    $countParams = array_filter($params, fn($k) => $k !== ':lang', ARRAY_FILTER_USE_KEY);
    $stmtCount = $pdo->prepare($countSql);
    $stmtCount->execute($countParams);
    $total = (int)$stmtCount->fetchColumn();
    $pages = $total > 0 ? (int)ceil($total / $per_page) : 0;

    // Sanitizar página
    $page   = max(1, min($page, max(1, $pages)));
    $offset = ($page - 1) * $per_page;

    // Query principal con traducciones opcionales
    $sql = "
        SELECT
            e.id, e.slug,
            $selectTrad
            e.description,
            e.start_date, e.end_date,
            e.municipality, e.province,
            e.venue_name, e.venue_address,
            e.latitude, e.longitude,
            e.is_free, e.ticket_price,
            e.organizer, e.target_audience,
            e.poster_image, e.photo1, e.photo2,
            e.category_id
        FROM cultural_events e
        $joinTrad
        $whereClause
        ORDER BY
            e.start_date ASC,
            e.name ASC
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

    // Normalizar fotos, URLs y datos de precio
    foreach ($rows as &$row) {
        $row['photo_url'] = _normalizeEventPhoto(
            $row['poster_image'] ?? '',
            $row['photo1'] ?? ''
        );

        // URL con prefijo de idioma — usa el slug traducido si existe
        $slugParaUrl = $row['trad_slug'] ?? $row['slug'] ?? '';
        if ($lang !== 'es') {
            $row['url'] = 'https://rutasrurales.io/' . $lang . '/evento/' . $slugParaUrl;
        } else {
            $row['url'] = 'https://rutasrurales.io/evento/' . $slugParaUrl;
        }

        // Precio display
        $row['precio_display'] = null;
        if (empty($row['is_free']) || !$row['is_free']) {
            if (!empty($row['ticket_price']) && $row['ticket_price'] > 0) {
                $row['precio_display'] = number_format((float)$row['ticket_price'], 2, ',', '.') . ' €';
            }
        }

        // Fecha formateada para mostrar
        $row['fecha_inicio'] = $row['start_date'] ?? null;
        $row['fecha_fin']    = (!empty($row['end_date']) && $row['end_date'] !== $row['start_date'])
            ? $row['end_date']
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
 * Estadísticas para el hero: total, gratuitos, municipios únicos.
 */
function getLandingEventosStats(
    PDO     $pdo,
    ?string $province_db,
    array   $sql_conditions
): array {
    $where  = ['e.is_active = 1', "e.moderation_status = 'approved'"];
    $params = [];

    $where[] = 'COALESCE(e.end_date, e.start_date) >= CURDATE()';

    if (!empty($province_db)) {
        $where[]           = 'e.province = :province';
        $params[':province'] = $province_db;
    }
    foreach ($sql_conditions as $cond) {
        if (!empty(trim($cond))) {
            $where[] = $cond;
        }
    }

    $whereClause = 'WHERE ' . implode(' AND ', $where);

    $sql = "
        SELECT
            COUNT(DISTINCT e.id)                               AS total,
            SUM(CASE WHEN e.is_free = 1 THEN 1 ELSE 0 END)    AS free_count,
            COUNT(DISTINCT e.municipality)                     AS towns
        FROM cultural_events e
        $whereClause
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return [
        'total'      => (int)($row['total']      ?? 0),
        'free_count' => (int)($row['free_count'] ?? 0),
        'towns'      => (int)($row['towns']      ?? 0),
    ];
}

/**
 * Cruce semántico INVERSO al de alojamientos:
 * Muestra alojamientos + lugares de interés de la provincia.
 * Esto diferencia eventos-landing de alojamientos-landing para Google.
 *
 * Orden de prioridad (resuelto 100% en BD, cero lógica PHP):
 *   1º suscripcion_nivel DESC  — Premium siempre arriba
 *   2º RAND(seed diario)       — rotación equitativa cada 24 h
 *   3º distancia ASC           — más cercano al centroide de la provincia
 *
 * @param float $prov_lat  Latitud del centroide de la provincia (de EVENTOS_PROVINCIAS)
 * @param float $prov_lng  Longitud del centroide de la provincia
 * @return array{accommodations: array, places: array, routes: array}
 */
function getEventosSemanticCrossing(
    PDO     $pdo,
    ?string $province_db,
    int     $limit    = 3,      // fijo: 3 tarjetas en la landing de eventos
    float   $prov_lat = 0.0,
    float   $prov_lng = 0.0
): array {
    $accommodations = [];
    $places         = [];
    $routes         = [];

    if (empty($province_db)) {
        return compact('accommodations', 'places', 'routes');
    }

    // ── Semilla diaria para RAND: cambia una vez al día, igual para todos ─────
    // YEAR(NOW())*1000 + DAYOFYEAR(NOW()) genera un entero único por día
    // que MySQL usa como semilla determinista → el orden rota cada 24 h.
    $daily_seed = (int)date('Y') * 1000 + (int)date('z'); // PHP precomputa la semilla

    // 1. Alojamientos de la provincia (cruce inverso — el diferenciador SEO)
    try {
        // ── Haversine en SELECT para calcular distancia al centroide ──────────
        // Si la provincia no tiene coordenadas (lat=0, lng=0), distancia = 0
        // y el tercer criterio queda neutro (no rompe nada).
        $haversine = ($prov_lat != 0 && $prov_lng != 0)
            ? "( 6371 * acos( cos( radians(:prov_lat) )
                   * cos( radians( COALESCE(a.latitude,  :prov_lat2) ) )
                   * cos( radians( COALESCE(a.longitude, :prov_lng2) ) - radians(:prov_lng) )
                   + sin( radians(:prov_lat3) )
                   * sin( radians( COALESCE(a.latitude,  :prov_lat4) ) )
               ) )"
            : "0";

        $sql = "
            SELECT a.id, a.name, a.slug, a.short_description,
                   a.municipality, a.province,
                   a.price_per_night, a.capacity,
                   a.photo1, a.accommodation_type,
                   a.suscripcion_nivel,
                   c.name AS category_name,
                   $haversine AS distancia
            FROM accommodations a
            LEFT JOIN categories_accommodations c ON a.category_id = c.id
            WHERE a.province = :province AND a.is_active = 1
            ORDER BY
                a.suscripcion_nivel DESC,     -- 1º: Premium (3) antes que Gratuito (1)
                RAND(:seed),                  -- 2º: rotación diaria equitativa
                distancia ASC                 -- 3º: más cercano al centro de la provincia
            LIMIT :limit
        ";

        $stmtA = $pdo->prepare($sql);
        $stmtA->bindValue(':province', $province_db);
        $stmtA->bindValue(':seed',     $daily_seed, PDO::PARAM_INT);
        $stmtA->bindValue(':limit',    3,            PDO::PARAM_INT);

        if ($prov_lat != 0 && $prov_lng != 0) {
            $stmtA->bindValue(':prov_lat',  $prov_lat);
            $stmtA->bindValue(':prov_lat2', $prov_lat);
            $stmtA->bindValue(':prov_lat3', $prov_lat);
            $stmtA->bindValue(':prov_lat4', $prov_lat);
            $stmtA->bindValue(':prov_lng',  $prov_lng);
            $stmtA->bindValue(':prov_lng2', $prov_lng);
        }

        $stmtA->execute();
        $rawAlo = $stmtA->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rawAlo as $a) {
            $photo = $a['photo1'] ?? '';
            if (empty($photo)) {
                $a['photo_url'] = 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=400&h=260&fit=crop&auto=format&q=70';
            } elseif (preg_match('/^https?:\/\//', $photo)) {
                $a['photo_url'] = $photo;
            } else {
                $a['photo_url'] = 'https://rutasrurales.io/' . ltrim($photo, '/');
            }
            unset($a['distancia']); // no exponer el cálculo interno al template
            $a['url']            = 'https://rutasrurales.io/alojamiento/' . ($a['slug'] ?? '');
            $a['precio_display'] = ($a['price_per_night'] > 0)
                ? number_format((float)$a['price_per_night'], 0, ',', '.') . ' €'
                : null;
            $accommodations[] = $a;
        }
    } catch (PDOException $e) {
        error_log('[eventos-landing-data] accommodations query error: ' . $e->getMessage());
    }

    // 2. Lugares de interés de la provincia
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
        $stmtP->bindValue(':limit',    min($limit, 6), PDO::PARAM_INT);
        $stmtP->execute();
        $rawPlaces = $stmtP->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rawPlaces as $p) {
            $photo = $p['photo1'] ?? '';
            if (empty($photo)) {
                $p['photo_url'] = null;
            } elseif (preg_match('/^https?:\/\//', $photo)) {
                $p['photo_url'] = $photo;
            } else {
                $p['photo_url'] = 'https://rutasrurales.io/' . ltrim($photo, '/');
            }
            $p['url']           = 'https://rutasrurales.io/lugar/' . ($p['slug'] ?? '');
            $p['entry_display'] = (!empty($p['entry_fee']) && $p['entry_fee'] > 0)
                ? number_format((float)$p['entry_fee'], 2) . ' €'
                : null;
            $places[] = $p;
        }
    } catch (PDOException $e) {
        error_log('[eventos-landing-data] places query error: ' . $e->getMessage());
    }

    // 3. Rutas temáticas de la provincia
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
        $stmtR->bindValue(':limit',    min($limit, 3), PDO::PARAM_INT);
        $stmtR->execute();
        $rawRoutes = $stmtR->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rawRoutes as $r) {
            $r['url'] = 'https://rutasrurales.io/rutas/' . ($r['slug'] ?? '');
            $routes[] = $r;
        }
    } catch (PDOException $e) {
        error_log('[eventos-landing-data] routes query error: ' . $e->getMessage());
    }

    return compact('accommodations', 'places', 'routes');
}

// ─── Helpers privados ─────────────────────────────────────────────────────────

/**
 * Normaliza la URL de imagen de un evento.
 * Orden de preferencia: poster_image → photo1 → fallback Unsplash.
 */
function _normalizeEventPhoto(string $posterImage, string $photo1): string
{
    $fallback = 'https://images.unsplash.com/photo-1514320291840-2e0a9bf2a9ae?w=600&h=400&fit=crop&auto=format&q=75';

    $img = !empty($posterImage) ? $posterImage : $photo1;
    if (empty($img)) return $fallback;

    if (preg_match('/^https?:\/\//', $img)) return $img;

    // Ruta relativa con subdirectorio
    if (str_contains($img, '/')) {
        return 'https://rutasrurales.io/' . ltrim($img, '/');
    }

    // Solo nombre de archivo → asumir directorio estándar
    return 'https://rutasrurales.io/cultural_events_images/' . $img;
}

// ═════════════════════════════════════════════════════════════════════════════
//  HERO IMAGE — Sistema de imagen de portada dinámica para las landing pages
// ═════════════════════════════════════════════════════════════════════════════

/**
 * Normaliza una URL de imagen hero (absoluta o relativa).
 *
 * @param  string $img  Ruta relativa (/hero-images/...) o URL absoluta (https://...)
 * @return string       URL absoluta lista para usar en <img src> o <link rel="preload">
 */
function _normalizeHeroUrl(string $img): string
{
    $img = trim($img);
    if (empty($img)) return '';
    if (preg_match('/^https?:\/\//', $img)) return $img;
    return 'https://rutasrurales.io/' . ltrim($img, '/');
}

/**
 * Resuelve la imagen hero según lógica de prioridad en cascada:
 *
 *   1. Imagen específica del EVENTO  (campo hero_image en cultural_events — máxima prioridad)
 *   2. Imagen de la CATEGORÍA        (campo hero_image en categories_events — tras ejecutar el SQL)
 *   3. Imagen del FILTRO ACTIVO      (mapa PHP hardcoded — sin BD, funciona siempre)
 *   4. FALLBACK global               (/menu_images/turismo_rural.webp)
 *
 * Características de rendimiento:
 *   - La consulta a BD (prioridad 2) solo se lanza si hay $categoryId Y $pdo.
 *   - Si la tabla categories_events no tiene la columna hero_image todavía,
 *     el try/catch silencia el error y cae al nivel 3 automáticamente.
 *   - El mapa $filterHeroMap usa Unsplash con parámetros optimizados (webp, q=75).
 *     Sustitúyelos por rutas locales cuando tengas tus propias imágenes .webp.
 *
 * @param  string|null $eventoHeroImage  $evento['hero_image'] — imagen específica del evento
 * @param  int|null    $categoryId       $evento['category_id'] — ID de la categoría
 * @param  string[]    $filterKeys       Filtros activos del slug, ej. ['musica', 'verano']
 * @param  PDO|null    $pdo              Conexión activa (para consultar categories_events)
 * @return array{url:string, source:string, alt_suffix:string}
 *           'url'        → URL absoluta de la imagen elegida
 *           'source'     → 'evento' | 'categoria' | 'filtro' | 'fallback' (para debug)
 *           'alt_suffix' → texto descriptivo para el atributo alt del <img>
 */
function resolveHeroImage(
    ?string $eventoHeroImage,
    ?int    $categoryId,
    array   $filterKeys,
    ?PDO    $pdo = null
): array {

    // ── Mapa filtro → imágenes (placeholder Unsplash mientras no haya propias) ──
    // Formato: 'filtro' => ['url' => '...', 'alt' => '...']
    // Unsplash: w=1440&h=500 → tamaño desktop. El srcset adapta el resto.
    // q=80: calidad óptima sin peso excesivo. auto=format: webp en Chrome, jpg en Safari.
    $filterHeroMap = [
        'musica' => [
            'url' => 'https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?w=1440&h=500&fit=crop&auto=format&q=80',
            'alt' => 'Concierto y eventos de música en vivo',
        ],
        'teatro' => [
            'url' => 'https://images.unsplash.com/photo-1507676184212-d03ab07a01bf?w=1440&h=500&fit=crop&auto=format&q=80',
            'alt' => 'Teatro, danza y artes escénicas',
        ],
        'exposiciones' => [
            'url' => 'https://images.unsplash.com/photo-1531243269054-5ebf6f34081e?w=1440&h=500&fit=crop&auto=format&q=80',
            'alt' => 'Exposiciones de arte y galerías',
        ],
        'gastronomia' => [
            'url' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1440&h=500&fit=crop&auto=format&q=80',
            'alt' => 'Gastronomía, vinos y ferias culinarias',
        ],
        'tradiciones' => [
            'url' => 'https://images.unsplash.com/photo-1533174072545-7a4b6ad7a6c3?w=1440&h=500&fit=crop&auto=format&q=80',
            'alt' => 'Tradiciones, folklore y fiestas populares',
        ],
        'mercados' => [
            'url' => 'https://images.unsplash.com/photo-1506617420156-8e4536971650?w=1440&h=500&fit=crop&auto=format&q=80',
            'alt' => 'Mercados tradicionales y artesanía',
        ],
        'infantil' => [
            'url' => 'https://images.unsplash.com/photo-1472162072942-cd5147eb3902?w=1440&h=500&fit=crop&auto=format&q=80',
            'alt' => 'Actividades familiares e infantiles',
        ],
        'naturaleza' => [
            'url' => 'https://images.unsplash.com/photo-1448375240586-882707db888b?w=1440&h=500&fit=crop&auto=format&q=80',
            'alt' => 'Naturaleza, senderismo y rutas al aire libre',
        ],
        'gratuitos' => [
            'url' => 'https://images.unsplash.com/photo-1514320291840-2e0a9bf2a9ae?w=1440&h=500&fit=crop&auto=format&q=80',
            'alt' => 'Eventos culturales gratuitos y para todos',
        ],
        'de-pago' => [
            'url' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=1440&h=500&fit=crop&auto=format&q=80',
            'alt' => 'Espectáculos y eventos con entrada',
        ],
        'primavera' => [
            'url' => 'https://images.unsplash.com/photo-1490750967868-88df5691cc22?w=1440&h=500&fit=crop&auto=format&q=80',
            'alt' => 'Eventos y festivales de primavera',
        ],
        'verano' => [
            'url' => 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1440&h=500&fit=crop&auto=format&q=80',
            'alt' => 'Festivales y eventos de verano al aire libre',
        ],
        'otono' => [
            'url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=1440&h=500&fit=crop&auto=format&q=80',
            'alt' => 'Eventos culturales de otoño',
        ],
        'invierno' => [
            'url' => 'https://images.unsplash.com/photo-1418985991508-e47386d96a71?w=1440&h=500&fit=crop&auto=format&q=80',
            'alt' => 'Eventos y celebraciones de invierno',
        ],
        'este-mes' => [
            'url' => 'https://images.unsplash.com/photo-1501281668745-f7f57925c3b4?w=1440&h=500&fit=crop&auto=format&q=80',
            'alt' => 'Eventos culturales este mes',
        ],
    ];

    // ── PRIORIDAD 1: imagen específica del evento ─────────────────────────────
    if (!empty($eventoHeroImage)) {
        return [
            'url'        => _normalizeHeroUrl($eventoHeroImage),
            'source'     => 'evento',
            'alt_suffix' => '',
        ];
    }

    // ── PRIORIDAD 2: imagen de la categoría (requiere ALTER TABLE ejecutado) ──
    if ($categoryId && $pdo) {
        try {
            $stmt = $pdo->prepare(
                "SELECT hero_image FROM categories_events WHERE id = ? AND hero_image IS NOT NULL AND hero_image != '' LIMIT 1"
            );
            $stmt->execute([$categoryId]);
            $catImg = $stmt->fetchColumn();
            if (!empty($catImg)) {
                return [
                    'url'        => _normalizeHeroUrl($catImg),
                    'source'     => 'categoria',
                    'alt_suffix' => '',
                ];
            }
        } catch (Throwable $e) {
            // Silencioso: si la columna no existe aún, cae al nivel 3
            // error_log('[resolveHeroImage] categoria query: ' . $e->getMessage());
        }
    }

    // ── PRIORIDAD 3: primer filtro activo con imagen definida ─────────────────
    foreach ($filterKeys as $fk) {
        if (!empty($filterHeroMap[$fk])) {
            return [
                'url'        => $filterHeroMap[$fk]['url'],
                'source'     => 'filtro',
                'alt_suffix' => $filterHeroMap[$fk]['alt'],
            ];
        }
    }

    // ── PRIORIDAD 4: fallback global ──────────────────────────────────────────
    return [
        'url'        => 'https://rutasrurales.io/menu_images/turismo_rural.webp',
        'source'     => 'fallback',
        'alt_suffix' => 'Eventos culturales en la España rural',
    ];
}
