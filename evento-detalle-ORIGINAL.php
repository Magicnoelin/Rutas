<?php
/**
 * EVENTO MODULAR - Página de Detalle de Evento
 * Versión optimizada para velocidad y enganche de usuarios
 * 
 * URL: /evento-modular/{slug}
 * Prueba: https://rutasrurales.io/evento-modular/{slug}
 * Producción final: /evento/{slug} (reemplazará evento-detalle.php)
 */

define('API_NO_HEADERS', true);
require_once 'api/config.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$lang = isset($_GET['lang']) ? trim($_GET['lang']) : 'es';
$lang = in_array($lang, ['es', 'en', 'fr', 'de', 'zh']) ? $lang : 'es';

// ─── OBTENER DATOS CRÍTICOS DEL EVENTO (SSR para SEO) ────────────────────────
$evento = null;
$traduccion = null;

if (!empty($slug)) {
    try {
        $pdo = getDBConnection();

        if ($lang === 'es') {
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
        } else {
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
                    FROM cultural_events e WHERE e.id = ? AND e.is_active = 1
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
                    FROM cultural_events e WHERE e.slug = ? AND e.is_active = 1
                ");
                $stmt->execute([$slug]);
            }
        }

        $evento = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($traduccion && $evento) {
            $evento['titulo']            = $traduccion['titulo_trad']      ?? $evento['titulo'];
            $evento['description']       = $traduccion['descripcion_trad'] ?? $evento['description'];
            $evento['short_description'] = $traduccion['short_desc_trad']  ?? $evento['short_description'];
            $evento['meta_title']        = $traduccion['meta_title_trad']  ?? $evento['meta_title'];
            $evento['meta_description']  = $traduccion['meta_desc_trad']   ?? $evento['meta_description'];
            $evento['programa']          = $traduccion['programa_trad']    ?? '';
            $evento['audiencia']         = $traduccion['audiencia_trad']   ?? '';
            $evento['accesibilidad']     = $traduccion['accesibilidad_trad'] ?? '';
        }

    } catch (Exception $e) {
        error_log("evento-modular/index.php error: " . $e->getMessage());
    }
}

// ─── SEO ─────────────────────────────────────────────────────────────────────
$page_title = $evento ? ($evento['meta_title'] ?: $evento['titulo'] . ' | Rutas Rurales') : 'Evento Cultural | Rutas Rurales';
$page_desc  = $evento ? ($evento['meta_description'] ?: $evento['short_description'] ?: '') : 'Descubre este evento en Rutas Rurales';
$slug_canonical = $slug;
if ($evento) {
    $slug_canonical = ($lang !== 'es' && !empty($traduccion['slug_trad'])) ? $traduccion['slug_trad'] : $evento['slug'];
}
$canonical = 'https://rutasrurales.io/' . ($lang !== 'es' ? $lang . '/' : '') . 'evento/' . $slug_canonical;

// Fotos
$fotos = [];
if ($evento) {
    foreach (['photo1','photo2','photo3','photo4','poster_image'] as $campo) {
        if (!empty($evento[$campo])) {
            $url = $evento[$campo];
            if (!preg_match('/^https?:\/\//', $url)) $url = '/' . ltrim($url, '/');
            $fotos[] = $url;
        }
    }
}
$foto_og = !empty($fotos[0]) ? $fotos[0] : 'https://rutasrurales.io/menu_images/turismo_rural.webp';

// Categorías
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
$categoria_nombre = $categorias[$evento['category_id'] ?? 0] ?? 'Cultura';

// Fechas formateadas
$fecha_display = '';
if ($evento && !empty($evento['start_date'])) {
    $start = date('d/m/Y', strtotime($evento['start_date']));
    if (!empty($evento['end_date']) && $evento['end_date'] !== $evento['start_date']) {
        $end = date('d/m/Y', strtotime($evento['end_date']));
        $diff = (new DateTime($evento['start_date']))->diff(new DateTime($evento['end_date']));
        $days = $diff->days + 1;
        $fecha_display = $days <= 2 ? "$start - $end" : "$start al $end ($days días)";
    } else {
        $fecha_display = $start;
    }
}

// Precio
$precio_display = '';
if ($evento) {
    if ($evento['is_free'] == 1) $precio_display = 'Gratis';
    elseif (!empty($evento['ticket_price']) && $evento['ticket_price'] > 0) $precio_display = number_format($evento['ticket_price'], 2) . '€';
    else $precio_display = 'Consultar';
}

// Ubicación
$ubicacion_display = '';
if ($evento) {
    $partes = array_filter([$evento['localidad'] ?? '', $evento['municipality'] ?? '', $evento['province'] ?? '']);
    $ubicacion_display = implode(', ', $partes);
}

// JSON-LD para SEO
$jsonld = '';
if ($evento) {
    $jsonld_data = [
        '@context' => 'https://schema.org',
        '@type' => 'Event',
        'name' => $evento['titulo'],
        'description' => strip_tags($evento['short_description'] ?: $evento['description']),
        'startDate' => $evento['start_date'],
        'endDate' => $evento['end_date'] ?: $evento['start_date'],
        'location' => [
            '@type' => 'Place',
            'name' => $evento['localidad'] ?: $evento['municipality'],
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => $evento['municipality'],
                'addressRegion' => $evento['province'],
                'addressCountry' => 'ES'
            ]
        ],
        'organizer' => ['@type' => 'Organization', 'name' => $evento['organizer'] ?: 'Rutas Rurales'],
        'isAccessibleForFree' => $evento['is_free'] == 1,
        'url' => $canonical,
    ];
    if (!empty($fotos[0])) $jsonld_data['image'] = $fotos[0];
    if ($evento['latitude'] && $evento['longitude']) {
        $jsonld_data['location']['geo'] = [
            '@type' => 'GeoCoordinates',
            'latitude' => $evento['latitude'],
            'longitude' => $evento['longitude']
        ];
    }
    $jsonld = json_encode($jsonld_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

// Datos para JavaScript (evitar segunda llamada API)
$evento_js = $evento ? json_encode([
    'id'          => $evento['id'],
    'titulo'      => $evento['titulo'],
    'slug'        => $evento['slug'],
    'start_date'  => $evento['start_date'],
    'end_date'    => $evento['end_date'],
    'latitude'    => $evento['latitude'],
    'longitude'   => $evento['longitude'],
    'province'    => $evento['province'],
    'municipality'=> $evento['municipality'],
    'fotos'       => $fotos,
    'categoria'   => $categoria_nombre,
    'is_free'     => $evento['is_free'],
    'ticket_price'=> $evento['ticket_price'],
    'organizer'   => $evento['organizer'],
    'localidad'   => $evento['localidad'],
    'venue_address'=> $evento['venue_address'],
    'programa'    => $evento['programa'] ?? '',
    'audiencia'   => $evento['audiencia'] ?? '',
    'accesibilidad'=> $evento['accesibilidad'] ?? '',
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) : 'null';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_desc); ?>">
    <link rel="canonical" href="<?php echo $canonical; ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($page_desc); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($foto_og); ?>">
    <meta property="og:url" content="<?php echo $canonical; ?>">
    <meta property="og:site_name" content="Rutas Rurales">

    <!-- Favicon -->
    <link rel="icon" href="/menu_images/Favicon.png" type="image/png">

    <!-- Preconnect solo para recursos críticos -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://unpkg.com" crossorigin>

    <!-- Fuentes locales (Montserrat) -->
    <style>
        @font-face {
            font-family: 'Montserrat';
            font-style: normal;
            font-weight: 400;
            font-display: swap;
            src: url('/fonts/Montserrat-Regular.woff2') format('woff2');
        }
        @font-face {
            font-family: 'Montserrat';
            font-style: normal;
            font-weight: 700;
            font-display: swap;
            src: url('/fonts/Montserrat-Bold.woff2') format('woff2');
        }
    </style>

    <!-- CSS CRÍTICO INLINE (solo lo esencial para el primer render) -->
    <style>
        /* ── Variables ── */
        :root {
            --primary: #2F5233;
            --primary-light: #3d6b42;
            --accent: #81C784;
            --accent-warm: #F9A825;
            --text: #333;
            --text-light: #666;
            --bg: #f8f9fa;
            --white: #fff;
            --radius: 12px;
            --shadow: 0 4px 20px rgba(0,0,0,0.08);
            --shadow-hover: 0 8px 30px rgba(0,0,0,0.15);
        }

        /* ── Reset mínimo ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Montserrat', 'Segoe UI', sans-serif;
            color: var(--text);
            background: var(--bg);
            line-height: 1.6;
            overflow-x: hidden;
        }
        img { max-width: 100%; height: auto; display: block; }
        a { color: var(--primary); text-decoration: none; }
        a:hover { color: var(--primary-light); }

        /* ── Header (compatible con header.php) ── */
        .site-header {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            background: var(--white);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: 70px;
        }

        /* ── Hero ── */
        .event-hero {
            margin-top: 70px;
            background: linear-gradient(135deg, var(--primary) 0%, #1a3d1e 100%);
            color: var(--white);
            padding: 50px 20px 70px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .event-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .event-hero-badge {
            display: inline-block;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            color: var(--white);
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
            text-transform: uppercase;
        }
        .event-hero h1 {
            font-size: clamp(1.6rem, 4vw, 2.8rem);
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 16px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .event-hero-meta {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 16px;
            font-size: 0.95rem;
            opacity: 0.92;
        }
        .event-hero-meta span {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .event-hero-meta .icon { font-size: 1rem; }

        /* ── Contenedor principal ── */
        .event-layout {
            max-width: 1100px;
            margin: -40px auto 60px;
            padding: 0 16px;
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 24px;
            align-items: start;
        }
        @media (max-width: 900px) {
            .event-layout { grid-template-columns: 1fr; margin-top: -30px; }
        }

        /* ── Card base ── */
        .card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .card-body { padding: 28px; }
        .card-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--accent);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ── Galería ── */
        .event-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }
        .gallery-item {
            border-radius: 8px;
            overflow: hidden;
            aspect-ratio: 4/3;
            cursor: pointer;
            position: relative;
        }
        .gallery-item img {
            width: 100%; height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .gallery-item:hover img { transform: scale(1.05); }

        /* ── Descripción ── */
        .event-description {
            line-height: 1.8;
            font-size: 1rem;
            color: var(--text);
        }
        .event-description p { margin-bottom: 1.2rem; }
        .event-description h2, .event-description h3 {
            color: var(--primary);
            margin: 1.5rem 0 0.8rem;
        }

        /* ── Meta grid ── */
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 16px;
            margin: 24px 0;
        }
        .meta-item {
            background: var(--bg);
            border-radius: 8px;
            padding: 14px;
            border-left: 3px solid var(--accent);
        }
        .meta-item .meta-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .meta-item .meta-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text);
        }
        .meta-item .meta-icon {
            font-size: 1.2rem;
            margin-bottom: 6px;
        }

        /* ── Sidebar ── */
        .event-sidebar { position: sticky; top: 90px; }

        /* ── CTA Principal ── */
        .cta-card {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: var(--white);
            border-radius: var(--radius);
            padding: 24px;
            margin-bottom: 16px;
            text-align: center;
        }
        .cta-card h3 { font-size: 1.1rem; margin-bottom: 8px; }
        .cta-card p { font-size: 0.85rem; opacity: 0.85; margin-bottom: 16px; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
            text-decoration: none;
            width: 100%;
        }
        .btn-white {
            background: var(--white);
            color: var(--primary);
        }
        .btn-white:hover {
            background: #f0f0f0;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .btn-outline-white {
            background: transparent;
            color: var(--white);
            border: 2px solid rgba(255,255,255,0.6);
            margin-top: 8px;
        }
        .btn-outline-white:hover {
            background: rgba(255,255,255,0.1);
            border-color: var(--white);
        }
        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }
        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(47,82,51,0.3);
        }
        .btn-accent {
            background: var(--accent-warm);
            color: #333;
        }
        .btn-accent:hover {
            background: #f0a000;
            transform: translateY(-1px);
        }

        /* ── Mapa ── */
        #event-map-container {
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            margin-bottom: 24px;
        }
        #event-map {
            height: 380px;
            width: 100%;
            background: #e8f0e8;
        }
        .map-placeholder {
            height: 380px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #e8f0e8, #d4e8d4);
            color: var(--primary);
            gap: 12px;
            cursor: pointer;
        }
        .map-placeholder .map-icon { font-size: 3rem; }
        .map-placeholder p { font-size: 0.9rem; color: var(--text-light); }
        .map-controls {
            background: var(--white);
            padding: 12px 16px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            border-top: 1px solid #eee;
        }
        .map-toggle-btn {
            padding: 6px 14px;
            border-radius: 20px;
            border: 1px solid #ddd;
            background: var(--white);
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 600;
        }
        .map-toggle-btn.active {
            background: var(--primary);
            color: var(--white);
            border-color: var(--primary);
        }

        /* ── Contenido cercano ── */
        .nearby-section { margin-bottom: 24px; }
        .nearby-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
        }
        .nearby-card {
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #eee;
            transition: all 0.2s;
            background: var(--white);
        }
        .nearby-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-2px);
        }
        .nearby-card-img {
            height: 120px;
            background: #e8f0e8;
            overflow: hidden;
        }
        .nearby-card-img img {
            width: 100%; height: 100%;
            object-fit: cover;
        }
        .nearby-card-body { padding: 10px 12px; }
        .nearby-card-name {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .nearby-card-meta {
            font-size: 0.75rem;
            color: var(--text-light);
        }
        .nearby-card-price {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--primary);
            margin-top: 4px;
        }
        .nearby-show-more {
            text-align: center;
            margin-top: 12px;
        }
        .nearby-show-more button {
            background: none;
            border: 1px solid var(--primary);
            color: var(--primary);
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.85rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }
        .nearby-show-more button:hover {
            background: var(--primary);
            color: var(--white);
        }

        /* ── Eventos similares ── */
        .similar-events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 16px;
        }
        .similar-event-card {
            border-radius: var(--radius);
            overflow: hidden;
            border: 1px solid #eee;
            background: var(--white);
            transition: all 0.2s;
        }
        .similar-event-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-3px);
        }
        .similar-event-img {
            height: 150px;
            background: linear-gradient(135deg, #e8f0e8, #c8dcc8);
            overflow: hidden;
            position: relative;
        }
        .similar-event-img img { width: 100%; height: 100%; object-fit: cover; }
        .similar-event-badge {
            position: absolute;
            top: 8px; right: 8px;
            background: var(--primary);
            color: var(--white);
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 700;
        }
        .similar-event-body { padding: 14px; }
        .similar-event-name {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 6px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .similar-event-meta {
            font-size: 0.78rem;
            color: var(--text-light);
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        /* ── Suscripción ── */
        .subscribe-card {
            background: linear-gradient(135deg, #fff8e1, #fff3cd);
            border: 1px solid #ffe082;
            border-radius: var(--radius);
            padding: 24px;
            text-align: center;
        }
        .subscribe-card h3 {
            color: #e65100;
            font-size: 1rem;
            margin-bottom: 8px;
        }
        .subscribe-card p {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-bottom: 16px;
        }
        .subscribe-form {
            display: flex;
            gap: 8px;
            flex-direction: column;
        }
        .subscribe-form input {
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.9rem;
            width: 100%;
        }
        .subscribe-form input:focus {
            outline: none;
            border-color: var(--accent-warm);
            box-shadow: 0 0 0 3px rgba(249,168,37,0.15);
        }

        /* ── Skeleton loading ── */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s infinite;
            border-radius: 4px;
        }
        @keyframes skeleton-loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        .skeleton-card { height: 120px; border-radius: 8px; }
        .skeleton-text { height: 14px; margin-bottom: 8px; }
        .skeleton-text.short { width: 60%; }

        /* ── Lightbox ── */
        .lightbox-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.92);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        .lightbox-overlay.active { display: flex; }
        .lightbox-img {
            max-width: 90vw;
            max-height: 90vh;
            border-radius: 8px;
            object-fit: contain;
        }
        .lightbox-close {
            position: absolute;
            top: 20px; right: 24px;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            background: none;
            border: none;
            line-height: 1;
        }
        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255,255,255,0.15);
            border: none;
            color: white;
            font-size: 1.5rem;
            padding: 12px 16px;
            cursor: pointer;
            border-radius: 4px;
        }
        .lightbox-prev { left: 16px; }
        .lightbox-next { right: 16px; }

        /* ── Toast ── */
        .toast {
            position: fixed;
            bottom: 24px; right: 24px;
            background: var(--primary);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            z-index: 9998;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        .toast.show { transform: translateY(0); opacity: 1; }

        /* ── Footer ── */
        .site-footer {
            background: #1a2e1a;
            color: rgba(255,255,255,0.8);
            padding: 32px 20px;
            text-align: center;
            font-size: 0.85rem;
        }
        .footer-links { display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; margin-bottom: 12px; }
        .footer-links a { color: rgba(255,255,255,0.7); }
        .footer-links a:hover { color: var(--accent); }
        .footer-social { display: flex; justify-content: center; gap: 16px; margin-bottom: 16px; }
        .footer-social a { color: rgba(255,255,255,0.7); font-size: 1.2rem; }
        .footer-social a:hover { color: var(--accent); }

        /* ── Responsive ── */
        @media (max-width: 600px) {
            .event-hero { padding: 40px 16px 60px; }
            .event-hero h1 { font-size: 1.5rem; }
            .event-hero-meta { gap: 10px; font-size: 0.85rem; }
            .card-body { padding: 20px; }
            .meta-grid { grid-template-columns: 1fr 1fr; }
            .similar-events-grid { grid-template-columns: 1fr; }
        }
    </style>

    <!-- JSON-LD Schema.org -->
    <?php if ($jsonld): ?>
    <script type="application/ld+json"><?php echo $jsonld; ?></script>
    <?php endif; ?>

    <!-- Datos del evento para JS (evita segunda llamada API) -->
    <script>
        window.EVENTO_DATA = <?php echo $evento_js; ?>;
        window.EVENTO_SLUG = <?php echo json_encode($slug); ?>;
        window.EVENTO_LANG = <?php echo json_encode($lang); ?>;
    </script>
</head>
<body>

<!-- ── HEADER (compatible con el existente) ── -->
<?php
// Intentar incluir el header existente
$header_path = __DIR__ . '/header.php';
if (file_exists($header_path)) {
    include $header_path;
} else {
    // Header ligero de fallback
    echo '<header class="site-header" style="display:flex;align-items:center;padding:0 20px;gap:16px;">
        <a href="/" style="font-weight:700;color:var(--primary);font-size:1.1rem;text-decoration:none;">🌿 Rutas Rurales</a>
        <nav style="margin-left:auto;display:flex;gap:16px;font-size:0.9rem;">
            <a href="/eventos-culturales-paginacion.html" style="color:var(--text);">Eventos</a>
            <a href="/alojamientos-turisticos.html" style="color:var(--text);">Alojamientos</a>
            <a href="/login.html" style="color:var(--primary);font-weight:700;">Acceder</a>
        </nav>
    </header>';
}
?>

<!-- ── HERO ── -->
<?php if ($evento): ?>
<section class="event-hero">
    <div class="event-hero-badge"><?php echo htmlspecialchars($categoria_nombre); ?></div>
    <h1><?php echo htmlspecialchars($evento['titulo']); ?></h1>
    <div class="event-hero-meta">
        <?php if ($fecha_display): ?>
        <span><span class="icon">📅</span> <?php echo htmlspecialchars($fecha_display); ?></span>
        <?php endif; ?>
        <?php if ($ubicacion_display): ?>
        <span><span class="icon">📍</span> <?php echo htmlspecialchars($ubicacion_display); ?></span>
        <?php endif; ?>
        <?php if ($precio_display): ?>
        <span><span class="icon">🎟️</span> <?php echo htmlspecialchars($precio_display); ?></span>
        <?php endif; ?>
    </div>
</section>

<!-- ── LAYOUT PRINCIPAL ── -->
<div class="event-layout">

    <!-- ── COLUMNA PRINCIPAL ── -->
    <main>

        <!-- Galería de fotos -->
        <?php if (!empty($fotos)): ?>
        <div class="card" style="margin-bottom:24px;">
            <div class="card-body">
                <div class="event-gallery" id="event-gallery">
                    <?php foreach ($fotos as $i => $foto): ?>
                    <div class="gallery-item" onclick="openLightbox(<?php echo $i; ?>)">
                        <img src="<?php echo htmlspecialchars($foto); ?>"
                             alt="Foto <?php echo $i+1; ?> de <?php echo htmlspecialchars($evento['titulo']); ?>"
                             loading="<?php echo $i === 0 ? 'eager' : 'lazy'; ?>"
                             width="400" height="300">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Descripción del evento -->
        <div class="card" style="margin-bottom:24px;">
            <div class="card-body">
                <h2 class="card-title">📋 Sobre el evento</h2>
                <div class="event-description">
                    <?php echo $evento['description']; ?>
                </div>

                <!-- Info adicional de traducciones -->
                <?php
                $programa = $evento['programa'] ?? '';
                $audiencia = $evento['audiencia'] ?? '';
                $accesibilidad = $evento['accesibilidad'] ?? '';
                if ($programa || $audiencia || $accesibilidad):
                ?>
                <div style="margin-top:24px;display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:16px;">
                    <?php if ($programa): ?>
                    <div style="background:var(--bg);padding:16px;border-radius:8px;">
                        <h4 style="color:var(--primary);margin-bottom:8px;font-size:0.95rem;">📅 Programa</h4>
                        <div style="font-size:0.9rem;"><?php echo nl2br(htmlspecialchars($programa)); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($audiencia): ?>
                    <div style="background:var(--bg);padding:16px;border-radius:8px;">
                        <h4 style="color:var(--primary);margin-bottom:8px;font-size:0.95rem;">👥 Público</h4>
                        <div style="font-size:0.9rem;"><?php echo nl2br(htmlspecialchars($audiencia)); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($accesibilidad): ?>
                    <div style="background:var(--bg);padding:16px;border-radius:8px;">
                        <h4 style="color:var(--primary);margin-bottom:8px;font-size:0.95rem;">♿ Accesibilidad</h4>
                        <div style="font-size:0.9rem;"><?php echo nl2br(htmlspecialchars($accesibilidad)); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Meta información -->
        <div class="card" style="margin-bottom:24px;">
            <div class="card-body">
                <h2 class="card-title">ℹ️ Información del evento</h2>
                <div class="meta-grid">
                    <div class="meta-item">
                        <div class="meta-icon">📅</div>
                        <div class="meta-label">Fecha inicio</div>
                        <div class="meta-value"><?php echo date('d/m/Y', strtotime($evento['start_date'])); ?></div>
                    </div>
                    <?php if (!empty($evento['end_date']) && $evento['end_date'] !== $evento['start_date']): ?>
                    <div class="meta-item">
                        <div class="meta-icon">🏁</div>
                        <div class="meta-label">Fecha fin</div>
                        <div class="meta-value"><?php echo date('d/m/Y', strtotime($evento['end_date'])); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($ubicacion_display): ?>
                    <div class="meta-item">
                        <div class="meta-icon">📍</div>
                        <div class="meta-label">Ubicación</div>
                        <div class="meta-value"><?php echo htmlspecialchars($ubicacion_display); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($evento['venue_address'])): ?>
                    <div class="meta-item">
                        <div class="meta-icon">🗺️</div>
                        <div class="meta-label">Dirección</div>
                        <div class="meta-value"><?php echo htmlspecialchars($evento['venue_address']); ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="meta-item">
                        <div class="meta-icon">🏷️</div>
                        <div class="meta-label">Categoría</div>
                        <div class="meta-value"><?php echo htmlspecialchars($categoria_nombre); ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-icon">🎟️</div>
                        <div class="meta-label">Precio</div>
                        <div class="meta-value"><?php echo htmlspecialchars($precio_display); ?></div>
                    </div>
                    <?php if (!empty($evento['organizer'])): ?>
                    <div class="meta-item">
                        <div class="meta-icon">🏛️</div>
                        <div class="meta-label">Organiza</div>
                        <div class="meta-value"><?php echo htmlspecialchars($evento['organizer']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- MAPA (carga diferida con Leaflet) -->
        <?php if (!empty($evento['latitude']) && !empty($evento['longitude'])): ?>
        <div id="event-map-container" class="card" style="margin-bottom:24px;">
            <div id="map-placeholder" class="map-placeholder" onclick="initMap()">
                <div class="map-icon">🗺️</div>
                <strong style="font-size:1rem;">Ver en el mapa</strong>
                <p>Haz clic para cargar el mapa interactivo</p>
            </div>
            <div id="event-map" style="display:none;"></div>
            <div class="map-controls" id="map-controls" style="display:none;">
                <button class="map-toggle-btn active" id="btn-evento" onclick="toggleMapLayer('evento')">📍 Evento</button>
                <button class="map-toggle-btn" id="btn-alojamientos" onclick="toggleMapLayer('alojamientos')">🏠 Alojamientos</button>
                <button class="map-toggle-btn" id="btn-lugares" onclick="toggleMapLayer('lugares')">🏛️ Lugares</button>
                <button class="map-toggle-btn" id="btn-actividades" onclick="toggleMapLayer('actividades')">🎯 Actividades</button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Contenido cercano (carga diferida) -->
        <div id="nearby-section" class="card" style="margin-bottom:24px;display:none;">
            <div class="card-body">
                <h2 class="card-title">🏠 Alojamientos cercanos</h2>
                <div id="nearby-alojamientos" class="nearby-grid">
                    <!-- Skeleton loading -->
                    <div class="skeleton skeleton-card"></div>
                    <div class="skeleton skeleton-card"></div>
                    <div class="skeleton skeleton-card"></div>
                </div>
                <div class="nearby-show-more" id="more-alojamientos" style="display:none;">
                    <button onclick="showMoreNearby('alojamientos')">Ver más alojamientos</button>
                </div>
            </div>
        </div>

        <div id="nearby-lugares-section" class="card" style="margin-bottom:24px;display:none;">
            <div class="card-body">
                <h2 class="card-title">🏛️ Lugares de interés cercanos</h2>
                <div id="nearby-lugares" class="nearby-grid">
                    <div class="skeleton skeleton-card"></div>
                    <div class="skeleton skeleton-card"></div>
                    <div class="skeleton skeleton-card"></div>
                </div>
                <div class="nearby-show-more" id="more-lugares" style="display:none;">
                    <button onclick="showMoreNearby('lugares')">Ver más lugares</button>
                </div>
            </div>
        </div>

        <!-- Actividades turísticas cercanas (carga diferida) -->
        <div id="nearby-actividades-section" class="card" style="margin-bottom:24px;display:none;">
            <div class="card-body">
                <h2 class="card-title">🎯 Actividades turísticas cercanas</h2>
                <div id="nearby-actividades" class="nearby-grid">
                    <div class="skeleton skeleton-card"></div>
                    <div class="skeleton skeleton-card"></div>
                    <div class="skeleton skeleton-card"></div>
                </div>
                <div class="nearby-show-more" id="more-actividades" style="display:none;">
                    <button onclick="showMoreNearby('actividades')">Ver más actividades</button>
                </div>
            </div>
        </div>

        <!-- Eventos similares (carga diferida) -->
        <div id="similar-section" class="card" style="margin-bottom:24px;display:none;">
            <div class="card-body">
                <h2 class="card-title">🎭 Eventos similares</h2>
                <div id="similar-events" class="similar-events-grid">
                    <div class="skeleton skeleton-card"></div>
                    <div class="skeleton skeleton-card"></div>
                    <div class="skeleton skeleton-card"></div>
                </div>
            </div>
        </div>

    </main>

    <!-- ── SIDEBAR ── -->
    <aside class="event-sidebar">

        <!-- CTA Principal: Registro -->
        <div class="cta-card" id="cta-register">
            <div style="font-size:2rem;margin-bottom:8px;">🌿</div>
            <h3>¡No te pierdas ningún evento!</h3>
            <p>Regístrate gratis y recibe alertas de eventos similares en <?php echo htmlspecialchars($evento['province'] ?? 'tu zona'); ?></p>
            <a href="/login.html?action=register&ref=evento&slug=<?php echo urlencode($slug); ?>" class="btn btn-white">
                ✨ Registrarme gratis
            </a>
            <a href="/login.html?ref=evento&slug=<?php echo urlencode($slug); ?>" class="btn btn-outline-white">
                Ya tengo cuenta
            </a>
        </div>

        <!-- Visitas y Likes -->
        <div class="card" style="margin-bottom:16px;">
            <div class="card-body" style="text-align:center;">
                <div style="display:flex;justify-content:center;gap:24px;margin-bottom:16px;">
                    <div style="text-align:center;">
                        <div style="font-size:1.5rem;font-weight:700;color:var(--primary);" id="view-count">—</div>
                        <div style="font-size:0.75rem;color:var(--text-light);text-transform:uppercase;letter-spacing:0.5px;">Visitas</div>
                    </div>
                    <div style="width:1px;background:#eee;"></div>
                    <div style="text-align:center;">
                        <button id="btn-like" onclick="toggleLike()" style="background:none;border:none;cursor:pointer;font-size:1.8rem;line-height:1;transition:transform 0.2s;">🤍</button>
                        <div style="font-size:0.75rem;color:var(--text-light);text-transform:uppercase;letter-spacing:0.5px;"><span id="like-count">—</span> likes</div>
                    </div>
                </div>
                <button class="btn btn-primary" onclick="saveEvent()" id="btn-save-event" style="margin-bottom:8px;">
                    🔖 Guardar evento
                </button>
                <button class="btn btn-accent" onclick="addToRoute()">
                    🗺️ Añadir a mi ruta
                </button>
                <div style="margin-top:12px;display:flex;justify-content:center;gap:12px;">
                    <button onclick="shareEvent('whatsapp')" style="background:none;border:none;cursor:pointer;font-size:1.4rem;" title="Compartir en WhatsApp">💬</button>
                    <button onclick="shareEvent('twitter')" style="background:none;border:none;cursor:pointer;font-size:1.4rem;" title="Compartir en Twitter">🐦</button>
                    <button onclick="shareEvent('facebook')" style="background:none;border:none;cursor:pointer;font-size:1.4rem;" title="Compartir en Facebook">📘</button>
                    <button onclick="shareEvent('copy')" style="background:none;border:none;cursor:pointer;font-size:1.4rem;" title="Copiar enlace">🔗</button>
                </div>
            </div>
        </div>

        <!-- Suscripción a eventos similares -->
        <div class="subscribe-card" id="subscribe-card">
            <div style="font-size:1.8rem;margin-bottom:8px;">🔔</div>
            <h3>Eventos similares</h3>
            <p>Avísame cuando haya eventos de <strong><?php echo htmlspecialchars($categoria_nombre); ?></strong> en <?php echo htmlspecialchars($evento['province'] ?? 'esta zona'); ?></p>
            <form class="subscribe-form" onsubmit="subscribeEvents(event)">
                <input type="email" placeholder="tu@email.com" required id="subscribe-email">
                <button type="submit" class="btn btn-accent">🔔 Suscribirme</button>
            </form>
        </div>

    </aside>

</div><!-- /.event-layout -->

<?php else: ?>
<!-- Evento no encontrado -->
<div style="max-width:600px;margin:120px auto 60px;text-align:center;padding:40px;">
    <div style="font-size:4rem;margin-bottom:16px;">😕</div>
    <h1 style="color:var(--primary);margin-bottom:12px;">Evento no encontrado</h1>
    <p style="color:var(--text-light);margin-bottom:24px;">El evento que buscas no existe o ya no está disponible.</p>
    <a href="/eventos-culturales-paginacion.html" class="btn btn-primary" style="display:inline-flex;width:auto;">Ver todos los eventos</a>
</div>
<?php endif; ?>

<!-- ── LIGHTBOX ── -->
<div class="lightbox-overlay" id="lightbox" onclick="closeLightboxOnOverlay(event)">
    <button class="lightbox-close" onclick="closeLightbox()">✕</button>
    <button class="lightbox-nav lightbox-prev" onclick="lightboxNav(-1)">‹</button>
    <img class="lightbox-img" id="lightbox-img" src="" alt="">
    <button class="lightbox-nav lightbox-next" onclick="lightboxNav(1)">›</button>
</div>

<!-- ── TOAST ── -->
<div class="toast" id="toast"></div>

<!-- ── FOOTER ── -->
<footer class="site-footer">
    <div class="footer-social">
        <a href="https://www.instagram.com/rutas_rurales/" target="_blank" rel="noopener" aria-label="Instagram">📸</a>
        <a href="#" aria-label="Facebook">📘</a>
        <a href="#" aria-label="Twitter">🐦</a>
    </div>
    <div class="footer-links">
        <a href="/aviso-legal.html">Aviso Legal</a>
        <a href="/politica-cookies.html">Cookies</a>
        <a href="/agradecimientos.html">Agradecimientos</a>
    </div>
    <p style="color:rgba(255,255,255,0.5);font-size:0.8rem;">© 2026 rutasrurales.io · Todos los derechos reservados</p>
</footer>

<!-- ── SCRIPTS DIFERIDOS ── -->
<!-- Font Awesome (diferido) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>

<!-- Leaflet CSS (diferido, solo si hay mapa) -->
<?php if (!empty($evento['latitude']) && !empty($evento['longitude'])): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" media="print" onload="this.media='all'">
<?php endif; ?>

<!-- Script principal (diferido) -->
<script defer src="/js/evento-modular.js?v=1.0"></script>

<!-- GTM diferido (después de interacción) -->
<script>
(function() {
    var gtmLoaded = false;
    function loadGTM() {
        if (gtmLoaded) return;
        gtmLoaded = true;
        (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});
        var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';
        j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;
        f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-MBP57VQM');
    }
    ['click','scroll','keydown','touchstart'].forEach(function(e) {
        window.addEventListener(e, loadGTM, {once: true, passive: true});
    });
    setTimeout(loadGTM, 8000);
})();
</script>

</body>
</html>
