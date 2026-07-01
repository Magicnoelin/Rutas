<?php
/**
 * LUGAR-MODULAR — Página de Detalle de Lugar de Interés
 * Versión 1.1 — SSR + Skeleton Screens + SEO máximo
 * Usa header.php exactamente igual que evento-detalle.php (ob_start para inyectar CSS/meta)
 * URL: /lugar/{slug}  ·  Test: /lugar-preview/{slug}
 */

error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

define('API_NO_HEADERS', true);
require_once '../api/config.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

// ─── REDIRECCIONES 301 ────────────────────────────────────────────────────────
$redirects = [
    'restaurante-santo-domingo-ii' => 'restaurante-santo-domingo-2-soria',
    'ermita-de-san-saturio'        => 'ermita-de-san-saturio-soria',
    'la-perdiz'                    => 'la-perdiz-brugo-de-osma',
    'asador-el-burgo'              => 'asador-el-burgo-de-osma',
    'asador-el-burgo-de osma'      => 'asador-el-burgo-de-osma',
    'pico-urbion'                  => 'pico-urbion-duruelo-de-la-sierra',
];
if (isset($redirects[$slug])) {
    header('Location: /lugar/' . $redirects[$slug], true, 301);
    exit;
}

// ─── OBTENER DATOS SSR ────────────────────────────────────────────────────────
$lugar = null;
$fotos = [];

if (!empty($slug)) {
    try {
        $pdo  = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT p.*, c.name AS category_name
            FROM places_of_interest p
            LEFT JOIN categories_places c ON p.category_id = c.id
            WHERE p.slug = ? AND p.is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$slug]);
        $lugar = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($lugar) {
            // Fotos desde entity_photos
            try {
                $stmtF = $pdo->prepare("
                    SELECT file_url FROM entity_photos
                    WHERE entity_type = 'places_of_interest'
                      AND entity_id = ?
                      AND permission_status = 'approved'
                      AND status = 'active'
                    ORDER BY is_cover DESC, featured DESC, uploaded_at DESC
                ");
                $stmtF->execute([$lugar['id']]);
                foreach ($stmtF->fetchAll(PDO::FETCH_ASSOC) as $f) {
                    if (!empty($f['file_url'])) {
                        $fotos[] = '/' . ltrim(str_replace('\\', '/', $f['file_url']), '/');
                    }
                }
            } catch (Exception $e) { /* ignorar */ }

            // Fallback legacy
            if (empty($fotos)) {
                foreach (['photo1','photo2','photo3','photo4'] as $c) {
                    if (!empty($lugar[$c])) {
                        $u = $lugar[$c];
                        if (!preg_match('/^https?:\/\//', $u)) $u = '/' . ltrim($u, '/');
                        $fotos[] = $u;
                    }
                }
                if (!empty($lugar['gallery'])) {
                    $g = json_decode($lugar['gallery'], true);
                    if (is_array($g)) $fotos = array_merge($fotos, $g);
                }
            }
            if (empty($fotos)) $fotos[] = '/interest_places_images/Patrocinio.webp';
        }
    } catch (Exception $e) {
        error_log('lugar-modular/index.php error: ' . $e->getMessage());
    }
}

// ─── SEO ─────────────────────────────────────────────────────────────────────
$canonical = 'https://rutasrurales.io/lugar/' . $slug;
$foto_og   = !empty($fotos[0])
    ? (preg_match('/^https?:\/\//', $fotos[0]) ? $fotos[0] : 'https://rutasrurales.io' . $fotos[0])
    : 'https://rutasrurales.io/menu_images/turismo_rural.webp';

// Variables que espera header.php
$page_title       = $lugar
    ? ($lugar['meta_title'] ?: $lugar['name'] . ' — ' . ($lugar['municipality'] ?? '') . ' | Rutas Rurales')
    : 'Lugar de interés | Rutas Rurales';
$page_description = $lugar
    ? ($lugar['meta_description'] ?: substr(strip_tags($lugar['description'] ?? ''), 0, 160) ?: 'Lugar de interés en ' . ($lugar['municipality'] ?? ''))
    : 'Descubre este lugar de interés en Rutas Rurales';
$page_canonical   = $canonical;
$defer_fontawesome = true; // FA no se necesita: usamos emojis en esta página

// ─── SCHEMA.ORG JSON-LD ───────────────────────────────────────────────────────
$jsonld = '';
if ($lugar) {
    $image_objects = [];
    foreach ($fotos as $idx => $furl) {
        $full = preg_match('/^https?:\/\//', $furl) ? $furl : 'https://rutasrurales.io' . $furl;
        $image_objects[] = ['@type' => 'ImageObject', '@id' => $canonical . '#photo' . ($idx + 1), 'url' => $full, 'name' => $lugar['name'] . ' — foto ' . ($idx + 1)];
    }

    $tourist = [
        '@type'       => 'TouristAttraction',
        '@id'         => $canonical . '#lugar',
        'name'        => $lugar['name'],
        'description' => substr(strip_tags($lugar['description'] ?? ''), 0, 500),
        'url'         => $canonical,
        'image'       => !empty($image_objects) ? $image_objects : $fotos,
        'address'     => array_filter(['@type' => 'PostalAddress', 'streetAddress' => $lugar['address'] ?? '', 'addressLocality' => $lugar['municipality'] ?? '', 'addressRegion' => $lugar['province'] ?? '', 'postalCode' => $lugar['postal_code'] ?? '', 'addressCountry' => 'ES']),
    ];
    if (!empty($lugar['latitude']) && !empty($lugar['longitude'])) {
        $tourist['geo']    = ['@type' => 'GeoCoordinates', 'latitude' => (float)$lugar['latitude'], 'longitude' => (float)$lugar['longitude']];
        $tourist['hasMap'] = 'https://www.google.com/maps?q=' . $lugar['latitude'] . ',' . $lugar['longitude'];
    }
    if (!empty($lugar['phone']))         $tourist['telephone'] = $lugar['phone'];
    if (!empty($lugar['email']))         $tourist['email']     = $lugar['email'];
    if (!empty($lugar['website']))       $tourist['sameAs']    = [$lugar['website']];
    if (!empty($lugar['opening_hours'])) $tourist['openingHours'] = $lugar['opening_hours'];
    if (isset($lugar['entry_fee']))      $tourist['isAccessibleForFree'] = ($lugar['entry_fee'] == 0 || empty($lugar['entry_fee']));

    $breadcrumb_name = !empty($lugar['name']) ? $lugar['name'] : $slug;
    $breadcrumb = ['@type' => 'BreadcrumbList', '@id' => $canonical . '#breadcrumb', 'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio',             'item' => 'https://rutasrurales.io/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Lugares de interés', 'item' => 'https://rutasrurales.io/lugares-de-interes'],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $breadcrumb_name,     'item' => $canonical],
    ]];

    $webpage = ['@type' => 'WebPage', '@id' => $canonical . '#webpage', 'url' => $canonical, 'name' => $page_title, 'description' => $page_description, 'inLanguage' => 'es-ES', 'isPartOf' => ['@id' => 'https://rutasrurales.io/#website'], 'about' => ['@id' => $canonical . '#lugar'], 'breadcrumb' => ['@id' => $canonical . '#breadcrumb']];
    if (!empty($image_objects)) $webpage['primaryImageOfPage'] = ['@id' => $canonical . '#photo1'];

    $jsonld = json_encode(['@context' => 'https://schema.org', '@graph' => [$webpage, $breadcrumb, $tourist]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}

// ─── DATOS PARA JS ────────────────────────────────────────────────────────────
$lugar_js = $lugar ? json_encode([
    'id' => $lugar['id'], 'name' => $lugar['name'], 'slug' => $lugar['slug'],
    'latitude' => $lugar['latitude'], 'longitude' => $lugar['longitude'],
    'province' => $lugar['province'], 'municipality' => $lugar['municipality'],
    'address' => $lugar['address'] ?? '', 'fotos' => $fotos,
    'entry_fee' => $lugar['entry_fee'] ?? 0, 'phone' => $lugar['phone'] ?? '',
    'email' => $lugar['email'] ?? '', 'website' => $lugar['website'] ?? '',
    'category_name' => $lugar['category_name'] ?? '',
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) : 'null';

// ─── CSS CRÍTICO + EXTRAS que inyectaremos antes de </head> ─────────────────
$foto_hero = !empty($fotos[0]) ? (preg_match('/^https?:\/\//', $fotos[0]) ? $fotos[0] : '/' . ltrim($fotos[0], '/')) : '';

$extra_head = '';

// Preload imagen hero (LCP)
if ($foto_hero) {
    $extra_head .= '<link rel="preload" as="image" href="' . htmlspecialchars($foto_hero, ENT_QUOTES) . '" fetchpriority="high">' . "\n";
}

// Open Graph + Twitter Card (header.php no los genera)
$extra_head .= '
    <meta property="og:type"         content="place">
    <meta property="og:title"        content="' . htmlspecialchars($page_title, ENT_QUOTES) . '">
    <meta property="og:description"  content="' . htmlspecialchars($page_description, ENT_QUOTES) . '">
    <meta property="og:image"        content="' . htmlspecialchars($foto_og, ENT_QUOTES) . '">
    <meta property="og:image:width"  content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url"          content="' . $canonical . '">
    <meta property="og:site_name"    content="Rutas Rurales">
    <meta property="og:locale"       content="es_ES">
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:site"        content="@rutasrurales">
    <meta name="twitter:title"       content="' . htmlspecialchars($page_title, ENT_QUOTES) . '">
    <meta name="twitter:description" content="' . htmlspecialchars($page_description, ENT_QUOTES) . '">
    <meta name="twitter:image"       content="' . htmlspecialchars($foto_og, ENT_QUOTES) . '">
    <meta name="robots"              content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
';

if ($lugar && !empty($lugar['latitude'])) {
    $extra_head .= '    <meta property="place:location:latitude"  content="' . htmlspecialchars($lugar['latitude'], ENT_QUOTES) . '">' . "\n";
    $extra_head .= '    <meta property="place:location:longitude" content="' . htmlspecialchars($lugar['longitude'], ENT_QUOTES) . '">' . "\n";
}

// JSON-LD
if ($jsonld) {
    $extra_head .= '<script type="application/ld+json">' . $jsonld . '</script>' . "\n";
}

// CSS crítico inline (evita que el layout dependa de styles.css para above-the-fold)
$extra_head .= '<style>
/* ── Variables ── */
:root {
    --lug-primary:   #2F5233;
    --lug-primary-l: #3d6b42;
    --lug-primary-d: #1a3d1e;
    --lug-accent:    #81C784;
    --lug-warm:      #F9A825;
    --lug-text:      #333;
    --lug-text-l:    #666;
    --lug-bg:        #f5f7f5;
    --lug-white:     #fff;
    --lug-r:         12px;
    --lug-shadow:    0 4px 20px rgba(0,0,0,0.08);
    --lug-shadow-h:  0 8px 30px rgba(0,0,0,0.15);
}
/* ── Reset parcial (sin conflicto con styles.css) ── */
.lug-page { overflow-x: hidden; }

/* ── Links globales: evitar azul por defecto del navegador ── */
.lug-page a { color: #2F5233; text-decoration: none; }
.lug-page a:hover { color: #1a3d1e; }

/* ── HERO ── */
.lug-hero {
    position: relative; min-height: 440px;
    display: flex; flex-direction: column; justify-content: flex-end;
    overflow: hidden; background: var(--lug-primary-d);
    margin-top: 0; /* El header de events no necesita margin extra */
}
.lug-hero-bg-img {
    position: absolute; inset: 0;
    width: 100%; height: 100%;
    object-fit: cover; object-position: center;
    display: block; will-change: transform;
}
.lug-hero-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to bottom, rgba(0,0,0,0.08) 0%, rgba(0,0,0,0.72) 100%);
    pointer-events: none;
}
.lug-hero-content {
    position: relative; z-index: 2;
    padding: 28px 24px 40px;
    max-width: 1100px; margin: 0 auto; width: 100%;
}
.lug-breadcrumb {
    font-size: 0.78rem; color: rgba(255,255,255,0.75);
    margin-bottom: 14px;
    display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
}
.lug-breadcrumb a { color: rgba(255,255,255,0.75); }
.lug-breadcrumb a:hover { color: #fff; }
.lug-hero-badge {
    display: inline-block;
    background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3);
    color: #fff; padding: 4px 14px; border-radius: 20px;
    font-size: 0.8rem; font-weight: 600; letter-spacing: 0.5px;
    margin-bottom: 12px; text-transform: uppercase;
}
.lug-hero h1 {
    font-size: clamp(1.6rem, 4.5vw, 2.9rem);
    font-weight: 800; color: #fff;
    line-height: 1.2; margin-bottom: 16px;
    text-shadow: 0 2px 8px rgba(0,0,0,0.35);
}
.lug-hero-meta {
    display: flex; flex-wrap: wrap; gap: 16px;
    font-size: 0.92rem; color: rgba(255,255,255,0.92);
}
.lug-hero-meta span { display: flex; align-items: center; gap: 6px; }
.lug-hero-free {
    background: var(--lug-accent); color: var(--lug-primary-d);
    font-weight: 800; padding: 5px 14px; border-radius: 20px; font-size: 0.9rem;
}
.lug-hero-actions {
    position: absolute; top: 20px; right: 20px;
    z-index: 3; display: flex; gap: 10px;
}
.lug-hero-btn {
    background: rgba(255,255,255,0.18); backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.3); color: #fff;
    border-radius: 50%; width: 42px; height: 42px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 1rem; transition: 0.2s ease;
    text-decoration: none;
}
.lug-hero-btn:hover { background: rgba(255,255,255,0.35); color: #fff; }

/* ── LAYOUT ── */
.lug-layout {
    max-width: 1100px; margin: -40px auto 60px;
    padding: 0 16px;
    display: grid; grid-template-columns: 1fr 340px;
    gap: 24px; align-items: start;
}
@media (max-width: 900px) {
    .lug-layout { grid-template-columns: 1fr; margin-top: -30px; }
}

/* ── CARD ── */
.lug-card {
    background: var(--lug-white) !important;
    border-radius: var(--lug-r) !important;
    box-shadow: var(--lug-shadow) !important;
    overflow: hidden; margin-bottom: 24px;
    transform: none !important;
}
.lug-card:hover { transform: none !important; }
.lug-card-body { padding: 28px; }
.lug-card-title {
    font-size: 1.1rem !important; font-weight: 700 !important;
    color: var(--lug-primary) !important;
    margin-bottom: 18px !important; padding-bottom: 12px !important;
    border-bottom: 2px solid var(--lug-accent) !important;
    display: flex !important; align-items: center !important; gap: 8px !important;
    visibility: visible !important; opacity: 1 !important;
}

/* ── GALERÍA ── */
.gallery-main {
    position: relative; border-radius: 8px;
    overflow: hidden; margin-bottom: 10px;
    cursor: pointer; background: #111;
}
.gallery-main-img {
    width: 100%; height: 380px;
    object-fit: cover; display: block;
    transition: transform 0.4s ease;
}
.gallery-main:hover .gallery-main-img { transform: scale(1.02); }
.gallery-counter {
    position: absolute; bottom: 12px; right: 14px;
    background: rgba(0,0,0,0.55); color: #fff;
    font-size: 0.78rem; font-weight: 600;
    padding: 4px 10px; border-radius: 12px;
}
.gallery-expand-btn {
    position: absolute; top: 12px; right: 14px;
    background: rgba(0,0,0,0.45); color: #fff;
    border: none; border-radius: 8px;
    padding: 7px 12px; font-size: 0.8rem;
    cursor: pointer; display: flex; align-items: center; gap: 6px;
}
.gallery-expand-btn:hover { background: rgba(0,0,0,0.7); }
.gallery-thumbs {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 8px;
}
.gallery-thumb {
    height: 68px; border-radius: 6px; overflow: hidden;
    cursor: pointer; border: 2px solid transparent;
    transition: border-color 0.2s;
}
.gallery-thumb img { width: 100%; height: 100%; object-fit: cover; }
.gallery-thumb.active { border-color: var(--lug-primary); }
.gallery-thumb:hover  { border-color: var(--lug-accent); }

/* ── DESCRIPCIÓN ── */
.desc-text { line-height: 1.85; color: var(--lug-text); font-size: 0.97rem; }
/* Inbound links dentro de la descripción — visibles y diferenciados */
.desc-text a,
.desc-text a:visited {
    color: #2F5233;
    text-decoration: underline;
    text-decoration-color: rgba(47, 82, 51, 0.4);
    text-underline-offset: 2px;
    font-weight: 600;
    transition: color 0.15s, text-decoration-color 0.15s;
}
.desc-text a:hover {
    color: #1a3a1e;
    text-decoration-color: #2F5233;
}
.desc-text h2 a, .desc-text h3 a, .desc-text h4 a {
    font-size: inherit;
    font-weight: inherit;
}
.desc-text.collapsed { max-height: 130px; overflow: hidden; position: relative; }
.desc-text.collapsed::after {
    content: ""; position: absolute;
    bottom: 0; left: 0; right: 0; height: 50px;
    background: linear-gradient(transparent, var(--lug-white));
}
.desc-expand-btn {
    background: none; border: 1px solid var(--lug-accent);
    color: var(--lug-primary); padding: 7px 18px;
    border-radius: 20px; font-size: 0.85rem;
    font-weight: 600; cursor: pointer; margin-top: 12px;
    transition: all 0.2s;
}
.desc-expand-btn:hover { background: var(--lug-primary); color: #fff; border-color: var(--lug-primary); }

/* ── INFO GRID ── */
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 14px; margin-top: 20px;
}
.info-item {
    background: var(--lug-bg); border-radius: 8px;
    padding: 14px; border-left: 3px solid var(--lug-accent);
}
.info-item .info-icon { font-size: 1.3rem; margin-bottom: 6px; }
.info-item .info-label {
    font-size: 0.72rem; font-weight: 700; color: var(--lug-text-l);
    text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;
}
.info-item .info-value { font-size: 0.92rem; font-weight: 600; color: var(--lug-text); }

/* ── CONTACTO ── */
.contact-btns { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 14px; }
.btn-contact {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 11px 20px; border-radius: 8px;
    font-weight: 700; font-size: 0.9rem;
    text-decoration: none; transition: all 0.2s;
    border: none; cursor: pointer;
}
.btn-contact:hover { transform: translateY(-2px); box-shadow: var(--lug-shadow-h); }
.btn-phone    { background: var(--lug-primary); color: #fff; }
.btn-whatsapp { background: #25D366; color: #fff; }
.btn-email    { background: var(--lug-warm); color: #1a1a1a; }
.btn-website  { background: var(--lug-accent); color: var(--lug-primary-d); }
.contact-addr {
    display: flex; align-items: flex-start; gap: 10px;
    color: var(--lug-text-l); font-size: 0.88rem;
    padding: 12px; background: var(--lug-bg); border-radius: 8px;
}

/* ── MAPA ── */
#lug-map-container { border-radius: var(--lug-r); overflow: hidden; box-shadow: var(--lug-shadow); margin-bottom: 24px; }
#lug-map { height: 380px; width: 100%; background: #e8f0e8; display: none; }
.map-placeholder {
    height: 380px; display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    background: linear-gradient(135deg, #e8f0e8, #d4e8d4);
    color: var(--lug-primary); gap: 12px; cursor: pointer;
}
.map-ph-hint {
    background: var(--lug-primary); color: #fff;
    padding: 8px 20px; border-radius: 20px;
    font-size: 0.82rem; font-weight: 600;
}

/* ── CONTENIDO CERCANO ── */
.nearby-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 12px;
}
.nearby-card {
    border-radius: 8px; overflow: hidden;
    border: 1px solid #eee; transition: all 0.2s;
    background: var(--lug-white); text-decoration: none;
    color: inherit; display: block;
}
.nearby-card:hover { box-shadow: var(--lug-shadow-h); transform: translateY(-3px); color: inherit; }
.nearby-card-img { height: 120px; background: #e8f0e8; overflow: hidden; position: relative; }
.nearby-card-img img { width: 100%; height: 100%; object-fit: cover; }
.nearby-card-img-ph {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    font-size: 2.5rem;
    background: linear-gradient(135deg, #e8f0e8, #d0e4d0);
}
.nearby-card-dist {
    position: absolute; bottom: 6px; right: 8px;
    background: rgba(0,0,0,0.55); color: #fff;
    font-size: 0.7rem; font-weight: 700;
    padding: 2px 7px; border-radius: 10px;
}
.nearby-card-body  { padding: 10px 12px; }
.nearby-card-name  { font-size: 0.85rem !important; font-weight: 700 !important; color: var(--lug-text) !important; margin-bottom: 4px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.nearby-card-meta  { font-size: 0.75rem; color: var(--lug-text-l); margin-bottom: 4px; }
.nearby-card-price { font-size: 0.8rem; font-weight: 700; color: var(--lug-primary); margin-top: 4px; }
.nearby-card-free  { font-size: 0.75rem; font-weight: 700; color: #2e7d32; background: #e8f5e9; padding: 2px 8px; border-radius: 10px; display: inline-block; margin-top: 4px; }
.nearby-show-more  { text-align: center; margin-top: 12px; display: none; }
.nearby-show-more button { background: none; border: 1px solid var(--lug-primary); color: var(--lug-primary); padding: 8px 20px; border-radius: 20px; font-size: 0.85rem; cursor: pointer; font-weight: 600; transition: all 0.2s; }
.nearby-show-more button:hover { background: var(--lug-primary); color: #fff; }

/* ── SKELETON ── */
.skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: sk-load 1.5s infinite;
    border-radius: 4px;
}
@keyframes sk-load { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
.skeleton-card { height: 160px; border-radius: 8px; }

/* ── SIDEBAR ── */
.lug-sidebar { position: sticky; top: 90px; }
.info-card {
    background: var(--lug-white); border-radius: var(--lug-r);
    box-shadow: 0 4px 24px rgba(0,0,0,0.11);
    padding: 24px; margin-bottom: 16px;
    border-top: 4px solid var(--lug-primary);
}
.info-card-title {
    font-size: 0.85rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.5px;
    color: var(--lug-text-l); margin-bottom: 14px;
}
.info-list { list-style: none; padding: 0; margin: 0; }
.info-list li {
    display: flex; align-items: flex-start; gap: 10px;
    font-size: 0.85rem; color: var(--lug-text);
    padding: 8px 0; border-bottom: 1px solid #f0f0f0; line-height: 1.4;
}
.info-list li:last-child { border-bottom: none; }
.li-icon { font-size: 1rem; flex-shrink: 0; margin-top: 1px; }

/* ── CTA card ── */
.cta-card {
    background: linear-gradient(135deg, #2F5233, #1a3d1e) !important;
    color: #fff !important; border-radius: var(--lug-r);
    padding: 22px; margin-bottom: 16px; text-align: center;
}
.cta-card h3 { font-size: 1rem; font-weight: 700; color: #fff !important; opacity: 1 !important; visibility: visible !important; margin-bottom: 8px; }
.cta-card p  { font-size: 0.82rem; color: rgba(255,255,255,0.88); margin-bottom: 14px; line-height: 1.5; }
.btn-cta-primary {
    display: flex; align-items: center; justify-content: center;
    background: #fff; color: #2F5233; padding: 10px 16px;
    border-radius: 8px; font-weight: 700; font-size: 0.85rem;
    text-decoration: none; margin-bottom: 8px; width: 100%;
}
.btn-cta-secondary {
    display: flex; align-items: center; justify-content: center;
    background: transparent; color: #fff !important;
    border: 2px solid rgba(255,255,255,0.6);
    padding: 9px 16px; border-radius: 8px;
    font-weight: 600; font-size: 0.82rem;
    text-decoration: none; width: 100%;
}
.btn-cta-primary:hover   { background: #f0f0f0; color: #2F5233; }
.btn-cta-secondary:hover { background: rgba(255,255,255,0.1); border-color: #fff; color: #fff !important; }

/* ── COMPARTIR ── */
.share-card {
    background: var(--lug-white); border-radius: var(--lug-r);
    box-shadow: var(--lug-shadow); padding: 18px;
    text-align: center; margin-bottom: 16px;
}
.share-label { font-size: 0.82rem; color: #666; margin-bottom: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.share-btns  { display: flex; justify-content: center; gap: 14px; }
.share-btns button { background: none; border: none; cursor: pointer; font-size: 1.6rem; line-height: 1; }

/* ── LIGHTBOX ── */
.lbox-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.93); z-index: 9999; align-items: center; justify-content: center; }
.lbox-overlay.active { display: flex; }
.lbox-img { max-width: 92vw; max-height: 88vh; border-radius: 6px; object-fit: contain; }
.lbox-close { position: absolute; top: 18px; right: 22px; color: #fff; font-size: 2rem; cursor: pointer; background: none; border: none; opacity: 0.8; }
.lbox-close:hover { opacity: 1; }
.lbox-nav { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.15); border: none; color: #fff; font-size: 1.5rem; padding: 12px 16px; cursor: pointer; border-radius: 4px; }
.lbox-prev { left: 16px; } .lbox-next { right: 16px; }
.lbox-caption { position: absolute; bottom: 20px; color: rgba(255,255,255,0.7); font-size: 0.85rem; }

/* ── TOAST ── */
.toast { position: fixed; bottom: 24px; right: 24px; background: var(--lug-primary); color: #fff; padding: 12px 20px; border-radius: 8px; font-size: 0.9rem; font-weight: 600; z-index: 9998; transform: translateY(100px); opacity: 0; transition: all 0.3s ease; box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
.toast.show { transform: translateY(0); opacity: 1; }

/* ── ERROR ── */
.error-container { text-align: center; padding: 80px 20px 60px; max-width: 500px; margin: 0 auto; }
.error-icon { font-size: 4rem; margin-bottom: 20px; }
.error-container h1 { font-size: 1.6rem; margin-bottom: 12px; color: var(--lug-primary); }
.error-container p  { color: var(--lug-text-l); margin-bottom: 24px; }
.btn-back { display: inline-flex; align-items: center; gap: 8px; background: var(--lug-primary); color: #fff; padding: 12px 24px; border-radius: 8px; font-weight: 700; text-decoration: none; }

/* ── RESPONSIVE ── */
@media (max-width: 600px) {
    .lug-hero { min-height: 320px; }
    .lug-hero h1 { font-size: 1.5rem; }
    .lug-card-body { padding: 18px; }
    .gallery-main-img { height: 240px; }
    .info-grid { grid-template-columns: 1fr 1fr; }
    .nearby-grid { grid-template-columns: 1fr 1fr; }
}
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
}
</style>
';

// Datos para JS
$extra_head .= '<script>window.LUG_DATA=' . $lugar_js . ';window.LUG_SLUG=' . json_encode($slug) . ';</script>' . "\n";

// ─── INCLUIR HEADER.PHP CON INYECCIÓN ANTES DE </head> ───────────────────────
// Mismo patrón que evento-detalle.php PERO inyectamos nuestros extras en </head>
ob_start();
$header_path = dirname(__DIR__) . '/header.php';
if (file_exists($header_path)) {
    include $header_path;
} else {
    // Fallback mínimo si header.php no existe
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . htmlspecialchars($page_title, ENT_QUOTES) . '</title></head><body>';
}
$header_html = ob_get_clean();

// ── Hacer styles.css no-bloqueante (loadCSS pattern) ────────────────────────
// styles.css es render-blocking por defecto. Cambiamos el <link> a preload+onload
// para que no bloquee el First Contentful Paint.
// El CSS se aplica igualmente pero DESPUÉS de que el HTML se haya pintado.
$header_html = str_replace(
    '<link rel="stylesheet" href="/styles.css">',
    '<link rel="preload" href="/styles.css" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">'
    . '<noscript><link rel="stylesheet" href="/styles.css"></noscript>',
    $header_html
);

// Inyectar nuestros extras justo antes de </head>
echo str_replace('</head>', $extra_head . '</head>', $header_html);

// ─── Helpers PHP ─────────────────────────────────────────────────────────────
function esc($s) { return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }
function fixUrl($url) {
    if (!$url) return '';
    return preg_match('/^https?:\/\//', $url) ? $url : '/' . ltrim($url, '/');
}

// Detecta si un lugar es de tipo gastronómico/restaurante (no tiene "entrada")
function esLugarGastronomico($categoryName) {
    if (empty($categoryName)) return false;
    $lower = mb_strtolower($categoryName, 'UTF-8');
    foreach (['restauran', 'gastronom', 'enotur', 'bodega', 'cafeter', 'restauraci', 'taberna', 'hosteleria', 'hostelería'] as $kw) {
        if (strpos($lower, $kw) !== false) return true;
    }
    return false;
}
?>

<div class="lug-page">
<?php if ($lugar): ?>

<!-- ══════════════════════════════════════════════════════
     HERO — SSR, visible inmediatamente
     ══════════════════════════════════════════════════════ -->
<section class="lug-hero" id="lug-hero">
    <img id="heroBg"
         class="lug-hero-bg-img"
         src="<?php echo esc(fixUrl($fotos[0])); ?>"
         alt="<?php echo esc($lugar['name']); ?> — imagen principal"
         fetchpriority="high"
         loading="eager"
         decoding="async"
         width="1200" height="440">
    <div class="lug-hero-overlay"></div>

    <div class="lug-hero-actions">
        <button class="lug-hero-btn" id="btn-share" title="Compartir" aria-label="Compartir">🔗</button>
        <button class="lug-hero-btn" id="btn-fav"   title="Guardar"   aria-label="Guardar en favoritos">🤍</button>
    </div>

    <div class="lug-hero-content">
        <nav class="lug-breadcrumb" aria-label="breadcrumb">
            <a href="/">🏠 Inicio</a>
            <span>/</span>
            <a href="/lugares-de-interes">Lugares de interés</a>
            <?php if (!empty($lugar['province'])): ?>
            <span>/</span>
            <a href="/lugares-de-interes?provincia=<?php echo urlencode($lugar['province']); ?>"><?php echo esc($lugar['province']); ?></a>
            <?php endif; ?>
            <span>/</span>
            <span><?php echo esc($lugar['name']); ?></span>
        </nav>

        <?php if (!empty($lugar['category_name'])): ?>
        <div class="lug-hero-badge"><?php echo esc($lugar['category_name']); ?></div>
        <?php endif; ?>

        <h1><?php echo esc($lugar['name']); ?></h1>

        <div class="lug-hero-meta">
            <?php if (!empty($lugar['municipality']) || !empty($lugar['province'])): ?>
            <span>📍 <?php echo esc(implode(', ', array_filter([$lugar['municipality'] ?? '', $lugar['province'] ?? '']))); ?></span>
            <?php endif; ?>

            <?php if (!empty($lugar['visit_duration'])): ?>
            <span>⏱️ <?php echo esc($lugar['visit_duration']); ?></span>
            <?php endif; ?>

            <?php if (!empty($lugar['best_season'])): ?>
            <span>🌸 <?php echo esc($lugar['best_season']); ?></span>
            <?php endif; ?>

            <?php $esGratis = empty($lugar['entry_fee']) || $lugar['entry_fee'] == 0; ?>
            <?php if ($esGratis && empty($lugar['entry_fee_details']) && !esLugarGastronomico($lugar['category_name'] ?? '')): ?>
            <span class="lug-hero-free">✅ Entrada gratuita</span>
            <?php elseif (!empty($lugar['entry_fee'])): ?>
            <span class="lug-hero-free" style="background:var(--lug-warm);color:#1a1a1a;">💶 <?php echo esc($lugar['entry_fee']); ?>€<?php if (!empty($lugar['entry_fee_details'])): ?> · <?php echo esc($lugar['entry_fee_details']); ?><?php endif; ?></span>
            <?php elseif (!empty($lugar['entry_fee_details'])): ?>
            <span class="lug-hero-free" style="background:var(--lug-warm);color:#1a1a1a;">💶 <?php echo esc($lugar['entry_fee_details']); ?></span>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════
     LAYOUT PRINCIPAL
     ══════════════════════════════════════════════════════ -->
<div class="lug-layout">

    <main>

        <!-- ▸ GALERÍA -->
        <?php if (!empty($fotos)): ?>
        <div class="lug-card">
            <div class="lug-card-body">
                <h2 class="lug-card-title">📸 Galería de fotos</h2>
                <div class="gallery-main" id="gallery-main" onclick="openLightbox(currentGalleryIdx)">
                    <img id="gallery-main-img"
                         src="<?php echo esc(fixUrl($fotos[0])); ?>"
                         alt="<?php echo esc($lugar['name']); ?>"
                         class="gallery-main-img"
                         loading="eager" width="800" height="380">
                    <?php if (count($fotos) > 1): ?>
                    <span class="gallery-counter" id="gallery-counter">1 / <?php echo count($fotos); ?></span>
                    <button class="gallery-expand-btn" onclick="event.stopPropagation();openLightbox(currentGalleryIdx)" type="button">🔍 Ver todas</button>
                    <?php endif; ?>
                </div>
                <?php if (count($fotos) > 1): ?>
                <div class="gallery-thumbs" id="gallery-thumbs">
                    <?php foreach ($fotos as $i => $foto): ?>
                    <div class="gallery-thumb <?php echo $i === 0 ? 'active' : ''; ?>"
                         data-index="<?php echo $i; ?>"
                         onclick="setGalleryPhoto(<?php echo $i; ?>)"
                         role="button" tabindex="0">
                        <img src="<?php echo esc(fixUrl($foto)); ?>"
                             alt="<?php echo esc($lugar['name']); ?> — foto <?php echo $i+1; ?>"
                             loading="<?php echo $i < 3 ? 'eager' : 'lazy'; ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ▸ DESCRIPCIÓN -->
        <div class="lug-card">
            <div class="lug-card-body">
                <h2 class="lug-card-title">📋 Descripción</h2>
                <?php if (!empty($lugar['description'])): ?>
                <?php
                // Usar description_linked (pre-generado con inbound links) si está disponible
                $desc_raw = !empty($lugar['description_linked']) ? $lugar['description_linked'] : $lugar['description'];
                $desc = strip_tags($desc_raw, '<strong><b><em><i><u><p><br><ul><ol><li><a>');
                $long = strlen(strip_tags($desc)) > 350;
                ?>
                <div class="desc-text <?php echo $long ? 'collapsed' : ''; ?>" id="desc-text">
                    <?php echo nl2br($desc); ?>
                </div>
                <?php if ($long): ?>
                <button class="desc-expand-btn" id="desc-expand-btn" onclick="expandDesc()">↓ Leer más</button>
                <?php endif; ?>
                <?php else: ?>
                <p style="color:var(--lug-text-l);font-style:italic;">Descripción no disponible.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- ▸ INFORMACIÓN PRÁCTICA -->
        <?php
        $hayInfo = !empty($lugar['opening_hours']) || !empty($lugar['entry_fee']) || !empty($lugar['entry_fee_details']) || !empty($lugar['visit_duration'])
                || !empty($lugar['best_season']) || !empty($lugar['accessibility'])
                || !empty($lugar['pet_friendly']) || !empty($lugar['suitable_for_children']);
        if ($hayInfo): ?>
        <div class="lug-card">
            <div class="lug-card-body">
                <h2 class="lug-card-title">ℹ️ Información práctica</h2>
                <div class="info-grid">
                    <?php if (!empty($lugar['opening_hours'])): ?>
                    <div class="info-item"><div class="info-icon">🕐</div><div class="info-label">Horario</div><div class="info-value"><?php echo esc($lugar['opening_hours']); ?></div></div>
                    <?php endif; ?>
                    <?php if (!empty($lugar['entry_fee']) || !empty($lugar['entry_fee_details']) || (!esLugarGastronomico($lugar['category_name'] ?? '') && isset($lugar['entry_fee']))): ?>
                    <div class="info-item"><div class="info-icon">🎫</div><div class="info-label">Entrada</div><div class="info-value"><?php
                        if (!empty($lugar['entry_fee'])) {
                            echo esc($lugar['entry_fee']) . '€';
                        } elseif (!empty($lugar['entry_fee_details'])) {
                            echo esc($lugar['entry_fee_details']);
                        } else {
                            echo 'Gratuita';
                        }
                        if (!empty($lugar['entry_fee']) && !empty($lugar['entry_fee_details'])): ?><br><small style="color:var(--lug-text-l);font-weight:400;"><?php echo esc($lugar['entry_fee_details']); ?></small><?php endif; ?></div></div>
                    <?php endif; ?>
                    <?php if (!empty($lugar['visit_duration'])): ?>
                    <div class="info-item"><div class="info-icon">⏱️</div><div class="info-label">Duración visita</div><div class="info-value"><?php echo esc($lugar['visit_duration']); ?></div></div>
                    <?php endif; ?>
                    <?php if (!empty($lugar['best_season'])): ?>
                    <div class="info-item"><div class="info-icon">🌸</div><div class="info-label">Mejor época</div><div class="info-value"><?php echo esc($lugar['best_season']); ?></div></div>
                    <?php endif; ?>
                    <?php if (!empty($lugar['accessibility'])): ?>
                    <div class="info-item"><div class="info-icon">♿</div><div class="info-label">Accesibilidad</div><div class="info-value"><?php echo esc($lugar['accessibility']); ?></div></div>
                    <?php endif; ?>
                    <?php if (!empty($lugar['pet_friendly'])): ?>
                    <div class="info-item"><div class="info-icon">🐾</div><div class="info-label">Mascotas</div><div class="info-value">Admitidas</div></div>
                    <?php endif; ?>
                    <?php if (!empty($lugar['suitable_for_children'])): ?>
                    <div class="info-item"><div class="info-icon">👶</div><div class="info-label">Familias</div><div class="info-value">Apto para niños</div></div>
                    <?php endif; ?>
                </div>
                <?php
                $facilities = [];
                if (!empty($lugar['facilities'])) { $dec = json_decode($lugar['facilities'], true); if (is_array($dec)) $facilities = $dec; }
                if (!empty($facilities)): ?>
                <div style="margin-top:20px;">
                    <div style="font-size:0.82rem;font-weight:700;color:var(--lug-text-l);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:10px;">Instalaciones</div>
                    <div style="display:flex;flex-wrap:wrap;gap:8px;">
                        <?php foreach ($facilities as $f): ?>
                        <span style="background:#e8f5e9;color:#2F5233;font-size:0.75rem;font-weight:600;padding:4px 10px;border-radius:20px;border:1px solid #c8e6c9;">✓ <?php echo esc($f); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ▸ CONTACTO -->
        <?php if (!empty($lugar['phone']) || !empty($lugar['email']) || !empty($lugar['website'])): ?>
        <div class="lug-card">
            <div class="lug-card-body">
                <h2 class="lug-card-title">📞 Contacto y acceso</h2>
                <div class="contact-btns">
                    <?php if (!empty($lugar['phone'])): ?>
                    <a href="tel:<?php echo esc($lugar['phone']); ?>" class="btn-contact btn-phone">📞 Llamar</a>
                    <a href="https://wa.me/34<?php echo preg_replace('/[^0-9]/', '', $lugar['phone']); ?>" target="_blank" rel="noopener" class="btn-contact btn-whatsapp">💬 WhatsApp</a>
                    <?php endif; ?>
                    <?php if (!empty($lugar['email'])): ?>
                    <a href="mailto:<?php echo esc($lugar['email']); ?>" class="btn-contact btn-email">✉️ Email</a>
                    <?php endif; ?>
                    <?php if (!empty($lugar['website'])): ?>
                    <a href="<?php echo esc($lugar['website']); ?>" target="_blank" rel="noopener" class="btn-contact btn-website">🌐 Web oficial</a>
                    <?php endif; ?>
                </div>
                <?php if (!empty($lugar['address'])): ?>
                <div class="contact-addr">
                    <span>📍</span>
                    <span><?php
                        echo esc($lugar['address']);
                        if (!empty($lugar['municipality'])) echo ', ' . esc($lugar['municipality']);
                        if (!empty($lugar['province']))     echo ' (' . esc($lugar['province']) . ')';
                        if (!empty($lugar['postal_code']))  echo ' · CP: ' . esc($lugar['postal_code']);
                    ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ▸ MAPA diferido -->
        <?php if (!empty($lugar['latitude']) && !empty($lugar['longitude'])): ?>
        <div id="lug-map-container" class="lug-card">
            <div id="map-placeholder" class="map-placeholder" onclick="initMap()">
                <div style="font-size:3rem">🗺️</div>
                <strong style="font-size:1rem;">Ver en el mapa</strong>
                <p style="font-size:0.9rem;color:var(--lug-text-l);"><?php echo esc(implode(', ', array_filter([$lugar['municipality'] ?? '', $lugar['province'] ?? '']))); ?></p>
                <span class="map-ph-hint">Haz clic para cargar el mapa interactivo</span>
            </div>
            <div id="lug-map"></div>
        </div>
        <?php endif; ?>

        <!-- ▸ ALOJAMIENTOS CERCANOS — Skeleton -->
        <div id="nearby-alojamientos-section" class="lug-card" style="display:none;">
            <div class="lug-card-body">
                <h2 class="lug-card-title">🏠 ¿Dónde dormir cerca?</h2>
                <p style="color:var(--lug-text-l);font-size:0.88rem;margin-bottom:16px;">Alojamientos rurales a pocos kilómetros de <?php echo esc($lugar['name']); ?>. ¡No dejes tu reserva para última hora!</p>
                <div id="nearby-alojamientos" class="nearby-grid">
                    <div class="skeleton skeleton-card"></div><div class="skeleton skeleton-card"></div>
                    <div class="skeleton skeleton-card"></div><div class="skeleton skeleton-card"></div>
                </div>
                <div class="nearby-show-more" id="more-alojamientos"><button onclick="showMoreNearby('alojamientos')">Ver más alojamientos</button></div>
            </div>
        </div>

        <!-- ▸ ACTIVIDADES CERCANAS — Skeleton -->
        <div id="nearby-actividades-section" class="lug-card" style="display:none;">
            <div class="lug-card-body">
                <h2 class="lug-card-title">🎯 Actividades turísticas cercanas</h2>
                <div id="nearby-actividades" class="nearby-grid">
                    <div class="skeleton skeleton-card"></div><div class="skeleton skeleton-card"></div><div class="skeleton skeleton-card"></div>
                </div>
                <div class="nearby-show-more" id="more-actividades"><button onclick="showMoreNearby('actividades')">Ver más actividades</button></div>
            </div>
        </div>

        <!-- ▸ EVENTOS CERCANOS — Skeleton -->
        <div id="nearby-eventos-section" class="lug-card" style="display:none;">
            <div class="lug-card-body">
                <h2 class="lug-card-title">🎭 Eventos culturales próximos</h2>
                <div id="nearby-eventos" class="nearby-grid">
                    <div class="skeleton skeleton-card"></div><div class="skeleton skeleton-card"></div><div class="skeleton skeleton-card"></div>
                </div>
                <div class="nearby-show-more" id="more-eventos"><button onclick="showMoreNearby('eventos')">Ver más eventos</button></div>
            </div>
        </div>

        <!-- ▸ LUGARES CERCANOS — Skeleton -->
        <div id="nearby-lugares-section" class="lug-card" style="display:none;">
            <div class="lug-card-body">
                <h2 class="lug-card-title">🏛️ Otros lugares de interés cerca</h2>
                <div id="nearby-lugares" class="nearby-grid">
                    <div class="skeleton skeleton-card"></div><div class="skeleton skeleton-card"></div><div class="skeleton skeleton-card"></div>
                </div>
                <div class="nearby-show-more" id="more-lugares"><button onclick="showMoreNearby('lugares')">Ver más lugares</button></div>
            </div>
        </div>

    </main>

    <!-- ── SIDEBAR ── -->
    <aside class="lug-sidebar">
        <div class="info-card">
            <div class="info-card-title">📌 En un vistazo</div>
            <ul class="info-list">
                <?php if (!empty($lugar['category_name'])): ?><li><span class="li-icon">🏷️</span><span><?php echo esc($lugar['category_name']); ?></span></li><?php endif; ?>
                <?php if (!empty($lugar['municipality'])): ?><li><span class="li-icon">📍</span><span><?php echo esc($lugar['municipality']); ?><?php if (!empty($lugar['province'])): ?>, <?php echo esc($lugar['province']); ?><?php endif; ?></span></li><?php endif; ?>
                <?php if (!empty($lugar['entry_fee']) || !empty($lugar['entry_fee_details']) || (!esLugarGastronomico($lugar['category_name'] ?? '') && isset($lugar['entry_fee']))): ?><li><span class="li-icon">🎫</span><span><?php
                    if (!empty($lugar['entry_fee'])) {
                        echo '💶 ' . esc($lugar['entry_fee']) . '€';
                    } elseif (!empty($lugar['entry_fee_details'])) {
                        echo '💶 ' . esc($lugar['entry_fee_details']);
                    } else {
                        echo '✅ Entrada gratuita';
                    }
                    if (!empty($lugar['entry_fee']) && !empty($lugar['entry_fee_details'])): ?> · <small><?php echo esc($lugar['entry_fee_details']); ?></small><?php endif; ?></span></li><?php endif; ?>
                <?php if (!empty($lugar['opening_hours'])): ?><li><span class="li-icon">🕐</span><span><?php echo esc($lugar['opening_hours']); ?></span></li><?php endif; ?>
                <?php if (!empty($lugar['visit_duration'])): ?><li><span class="li-icon">⏱️</span><span>Visita: <?php echo esc($lugar['visit_duration']); ?></span></li><?php endif; ?>
                <?php if (!empty($lugar['best_season'])): ?><li><span class="li-icon">🌸</span><span>Mejor época: <?php echo esc($lugar['best_season']); ?></span></li><?php endif; ?>
                <?php if (!empty($lugar['pet_friendly'])): ?><li><span class="li-icon">🐾</span><span>Admite mascotas</span></li><?php endif; ?>
                <?php if (!empty($lugar['suitable_for_children'])): ?><li><span class="li-icon">👶</span><span>Apto para niños</span></li><?php endif; ?>
                <?php if (!empty($lugar['phone'])): ?><li><span class="li-icon">📞</span><a href="tel:<?php echo esc($lugar['phone']); ?>"><?php echo esc($lugar['phone']); ?></a></li><?php endif; ?>
                <?php if (!empty($lugar['website'])): ?><li><span class="li-icon">🌐</span><a href="<?php echo esc($lugar['website']); ?>" target="_blank" rel="noopener">Web oficial</a></li><?php endif; ?>
            </ul>
            <?php if (!empty($lugar['latitude']) && !empty($lugar['longitude'])): ?>
            <a href="https://www.google.com/maps?q=<?php echo esc($lugar['latitude']); ?>,<?php echo esc($lugar['longitude']); ?>"
               target="_blank" rel="noopener"
               style="display:flex;align-items:center;justify-content:center;gap:8px;background:#2F5233;color:#fff;padding:10px 16px;border-radius:8px;font-weight:700;font-size:0.88rem;text-decoration:none;margin-top:16px;width:100%;">
                🗺️ Cómo llegar (Google Maps)
            </a>
            <?php endif; ?>
        </div>

        <div class="cta-card">
            <div style="font-size:1.8rem;margin-bottom:8px;line-height:1;">🌿</div>
            <h3>¿Te gusta este lugar?</h3>
            <p>Guárdalo en favoritos y recibe alertas de eventos y actividades cercanas</p>
            <a href="/login.html?action=register&ref=lugar&slug=<?php echo urlencode($slug); ?>" class="btn-cta-primary">✨ Registrarme gratis</a>
            <a href="/login.html?ref=lugar&slug=<?php echo urlencode($slug); ?>"                 class="btn-cta-secondary">Ya tengo cuenta</a>
        </div>

        <div class="share-card">
            <p class="share-label">Compartir este lugar</p>
            <div class="share-btns">
                <button onclick="shareLug('whatsapp')" title="WhatsApp">💬</button>
                <button onclick="shareLug('facebook')" title="Facebook">📘</button>
                <button onclick="shareLug('twitter')"  title="Twitter">🐦</button>
                <button onclick="shareLug('copy')"     title="Copiar enlace">🔗</button>
            </div>
        </div>
    </aside>

</div><!-- /.lug-layout -->

<?php else: ?>
<div class="error-container">
    <div class="error-icon">😕</div>
    <h1>Lugar no encontrado</h1>
    <p>El lugar de interés que buscas no existe o ya no está disponible.</p>
    <a href="/lugares-de-interes" class="btn-back">← Volver a los lugares de interés</a>
</div>
<?php endif; ?>

</div><!-- /.lug-page -->

<!-- ── LIGHTBOX ── -->
<div class="lbox-overlay" id="lightbox" onclick="closeLightboxOnOverlay(event)">
    <button class="lbox-close" onclick="closeLightbox()">✕</button>
    <button class="lbox-nav lbox-prev" onclick="lightboxNav(-1)">‹</button>
    <img class="lbox-img" id="lightbox-img" src="" alt="">
    <button class="lbox-nav lbox-next" onclick="lightboxNav(1)">›</button>
    <div class="lbox-caption" id="lightbox-caption"></div>
</div>

<!-- ── TOAST ── -->
<div class="toast" id="toast"></div>

<!-- ── LEAFLET CSS diferido ── -->
<?php if (!empty($lugar['latitude']) && !empty($lugar['longitude'])): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" media="print" onload="this.media='all'">
<?php endif; ?>

<!-- ── JS PRINCIPAL ── -->
<script src="/lugar-modular/js/lugar.js"></script>

</body>
</html>
