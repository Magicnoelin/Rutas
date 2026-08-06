<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  INDEX.PHP — Orquestador del Sistema de Landings Long-Tail
 *  rutasrurales.io/alojamientos/{filtros}-{provincia}
 *
 *  Ejemplos de URL:
 *    /alojamientos/casas-rurales-con-chimenea-soria
 *    /alojamientos/turismo-rural-mascotas-zamora
 *    /en/alojamientos/rural-houses-with-pool-burgos
 *    /de/alojamientos/landhaeuser-mit-kamin-leon
 *
 *  Flujo:
 *    1. Validar slug → si no es válido, 301 a /alojamiento/{slug} (compat)
 *    2. Detectar idioma
 *    3. Cargar config, i18n y capa de datos
 *    4. Ejecutar queries en BD
 *    5. Preparar contexto ($ctx) para módulos
 *    6. Renderizar HTML: head → navbar → hero → intro → listing → cruce → footer
 * ════════════════════════════════════════════════════════════════════════════
 */

// ── Seguridad y errores ───────────────────────────────────────────────────────
ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);
define('API_NO_HEADERS', true);

// ── Dependencias ─────────────────────────────────────────────────────────────
$_BASE = dirname(__DIR__);
require_once $_BASE . '/api/config.php';
require_once $_BASE . '/api/inbound_links_helper.php';
require_once __DIR__ . '/config/filters.php';
require_once __DIR__ . '/i18n/translations.php';
require_once __DIR__ . '/api/landing-data.php';
require_once __DIR__ . '/modules/schema.php';
require_once __DIR__ . '/modules/hero.php';
require_once __DIR__ . '/modules/intro.php';
require_once __DIR__ . '/modules/listing-alojamientos.php';
require_once __DIR__ . '/modules/cruce-semantico.php';

// ── 1. Parámetros de entrada ──────────────────────────────────────────────────
$slug_raw = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$lang     = isset($_GET['lang']) ? trim($_GET['lang']) : 'es';
$lang     = in_array($lang, ['es', 'en', 'fr', 'de', 'zh'], true) ? $lang : 'es';
$page     = max(1, (int)($_GET['p'] ?? $_GET['page'] ?? 1));

// Sanitizar slug: solo a-z 0-9 y guiones
$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug_raw));

// ── 2. Parsear slug → provincia + filtros ─────────────────────────────────────
$parsed = parseLandingSlug($slug);

// Si el slug no es válido como landing, redirigir al detalle de alojamiento
// (compatibilidad hacia atrás con slugs de alojamientos individuales)
if (!$parsed['valid'] && !empty($slug)) {
    header('Location: https://rutasrurales.io/alojamiento/' . $slug, true, 301);
    exit;
}

// Si es completamente inválido (slug vacío), redirigir al listado
if (empty($slug)) {
    header('Location: https://rutasrurales.io/alojamientos-turisticos', true, 301);
    exit;
}

// ── 3. Resolución de datos de provincia y filtros ─────────────────────────────
$province_key  = $parsed['province'];
$province_data = !empty($province_key) ? (LANDING_PROVINCIAS[$province_key] ?? []) : [];
$province_db   = $province_data['db'] ?? null;
$province_label= $province_data['label'] ?? '';

// Filtros activos: labels, iconos, condiciones SQL
$filter_keys     = $parsed['filters'];
$filter_labels   = [];
$filter_icons    = [];
$sql_conditions  = [];

foreach ($filter_keys as $fk) {
    if (isset(LANDING_FILTROS[$fk])) {
        $fd = LANDING_FILTROS[$fk];
        $filter_labels[] = $fd['labels'][$lang] ?? $fd['labels']['es'];
        $filter_icons[]  = $fd['icon'];
        $sql_conditions[]= $fd['sql'];
    }
}

// ── 4. Cargar traducciones ────────────────────────────────────────────────────
$t = getLandingTranslations($lang);

// ── 5. Construir textos SEO dinámicos ─────────────────────────────────────────
// Etiqueta del filtro principal (primer filtro o genérico)
$primary_filter_label = !empty($filter_labels)
    ? $filter_labels[0]
    : ($lang === 'es' ? 'Alojamientos rurales' : ($lang === 'en' ? 'Rural accommodation' : ($lang === 'fr' ? 'Hébergements ruraux' : ($lang === 'de' ? 'Ländliche Unterkünfte' : '乡村住宿'))));

// Feature label para meta description
$feature_label = !empty($filter_labels[1]) ? mb_strtolower($filter_labels[1])
    : ($lang === 'es' ? 'entorno natural único' : 'unique natural environment');

// H1
$h1 = '';
if (!empty($filter_labels) && !empty($province_label)) {
    // Construir H1 desde template (ej: "Casas rurales con chimenea en Soria")
    $mainFilter = $filter_labels[0];
    // Si hay segunda característica, unirla
    if (count($filter_labels) > 1) {
        $extras = implode(' ' . ($lang === 'es' ? 'y' : ($lang === 'fr' ? 'et' : ($lang === 'de' ? 'und' : 'and'))) . ' ', array_slice($filter_labels, 1));
        $mainFilter .= ' ' . $extras;
    }
    $h1 = t($t['h1_template'], ['FILTER_LABEL' => $mainFilter, 'PROVINCE' => $province_label]);
} elseif (!empty($filter_labels)) {
    $h1 = $filter_labels[0] . (count($filter_labels) > 1 ? ' ' . implode(' ', array_slice($filter_labels, 1)) : '');
} elseif (!empty($province_label)) {
    $h1 = t($t['h1_only_prov'], ['PROVINCE' => $province_label]);
} else {
    $h1 = $primary_filter_label;
}

// Meta title y description
$meta_title = t($t['meta_title'], [
    'FILTER_LABEL' => $primary_filter_label,
    'PROVINCE'     => $province_label ?: 'España',
]);
$meta_desc = t($t['meta_desc'], [
    'FILTER_LABEL_LOWER' => mb_strtolower($primary_filter_label),
    'PROVINCE'           => $province_label ?: 'España',
    'FILTER_FEATURE'     => $feature_label,
]);

// H2 del listing
$h2_listing = t($t['h2_listing'], ['PROVINCE' => $province_label ?: 'España']);

// ── 6. URL canónica y hreflang ────────────────────────────────────────────────
$base_domain = 'https://rutasrurales.io';
$canonical   = $lang === 'es'
    ? "$base_domain/alojamientos/$slug"
    : "$base_domain/$lang/alojamientos/$slug";

// hreflang: mismo slug para todos los idiomas (las landings en ES son el master)
$hreflang_urls = [
    'es'        => "$base_domain/alojamientos/$slug",
    'en'        => "$base_domain/en/alojamientos/$slug",
    'fr'        => "$base_domain/fr/alojamientos/$slug",
    'de'        => "$base_domain/de/alojamientos/$slug",
    'zh'        => "$base_domain/zh/alojamientos/$slug",
    'x-default' => "$base_domain/alojamientos/$slug",
];

// ── 7. Consultas a BD ─────────────────────────────────────────────────────────
$result   = ['items' => [], 'total' => 0, 'pages' => 0, 'page' => 1];
$stats    = ['total' => 0, 'avg_price' => 0, 'towns' => 0];
$semantic = ['places' => [], 'routes' => []];
$events   = [];

try {
    $pdo = getDBConnection();

    // Resultados paginados — Premium primero, rotación diaria, más cercanos al centro
    $result = getLandingAccommodations(
        $pdo,
        $province_db,
        $sql_conditions,
        $page,
        LANDING_PER_PAGE,
        $lang,
        (float)($province_data['lat'] ?? 0.0),
        (float)($province_data['lng'] ?? 0.0)
    );
    $stats  = getLandingStats($pdo, $province_db, $sql_conditions);

    // Cruce semántico (solo si hay provincia)
    if (!empty($province_db)) {
        $semantic = getSemanticCrossing($pdo, $province_db, 6);
        $events   = getUpcomingEvents($pdo, $province_db, 4);
    }
} catch (Throwable $e) {
    error_log('[alojamientos-landing] BD error: ' . $e->getMessage());
    // La página se renderiza aunque vacía — no muere
}

// ── 8. Contexto compartido para módulos ──────────────────────────────────────
$ctx = [
    // Identidad
    'slug'          => $slug,
    'lang'          => $lang,
    'canonical'     => $canonical,
    'parsed'        => $parsed,

    // Provincia
    'province_key'  => $province_key,
    'province_label'=> $province_label,
    'province_data' => $province_data,

    // Filtros
    'filter_keys'   => $filter_keys,
    'filter_labels' => $filter_labels,
    'filter_icons'  => $filter_icons,
    'filter_label'  => $primary_filter_label, // alias singular

    // SEO
    'page_title'    => $meta_title,
    'page_desc'     => $meta_desc,
    'h1'            => $h1,
    'h2_listing'    => $h2_listing,
    'lang_locale'   => $t['lang_locale'],

    // Datos
    'items'         => $result['items'],
    'total'         => $result['total'],
    'pages'         => $result['pages'],
    'page'          => $result['page'],
    'stats'         => $stats,
    'places'        => $semantic['places'],
    'routes'        => $semantic['routes'],
    'events'        => $events,

    // Traducciones
    't'             => $t,

    // BD (para procesarInboundLinks en módulos — una sola query gracias al cache)
    'pdo'           => $pdo ?? null,
];

// ── 9. Imagen OG ──────────────────────────────────────────────────────────────
$og_image = !empty($result['items'][0]['photo_url'])
    ? $result['items'][0]['photo_url']
    : 'https://rutasrurales.io/menu_images/turismo_rural.webp';

// ── 9b. Imagen Hero ──────────────────────────────────────────────────────────
$hero_image_url = 'https://rutasrurales.io/img/eventos-landing-hero/turismo_rural2.webp'; // Default
if ($slug === 'turismo-rural') {
    $hero_image_url = '/img/eventos-landing-hero/turismo_rural3.webp';
}
$ctx['hero_image_url'] = $hero_image_url;
$ctx['hero_image_alt'] = $h1;


// ── Paginación: rel prev/next ─────────────────────────────────────────────────
$rel_prev = ($page > 1)                  ? $canonical . '?p=' . ($page - 1) : null;
$rel_next = ($page < $result['pages'])   ? $canonical . '?p=' . ($page + 1) : null;

// Define the general listings URL as requested
$general_listings_url = $lang === 'es'
    ? "$base_domain/alojamientos/turismo-rural"
    : "$base_domain/$lang/alojamientos/turismo-rural";
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>" dir="<?= $t['dir'] ?? 'ltr' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- ── SEO primario ──────────────────────────────────────────────── -->
<title><?= htmlspecialchars($meta_title) ?></title>
<meta name="description" content="<?= htmlspecialchars($meta_desc) ?>">
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
<link rel="canonical" href="<?= htmlspecialchars($canonical . ($page > 1 ? '?p=' . $page : '')) ?>">
<?php if ($rel_prev): ?><link rel="prev" href="<?= htmlspecialchars($rel_prev) ?>"><?php endif; ?>
<?php if ($rel_next): ?><link rel="next" href="<?= htmlspecialchars($rel_next) ?>"><?php endif; ?>

<!-- ── hreflang — 5 idiomas + x-default ──────────────────────────── -->
<?php foreach ($hreflang_urls as $hl_lang => $hl_url): ?>
<link rel="alternate" hreflang="<?= htmlspecialchars($hl_lang) ?>" href="<?= htmlspecialchars($hl_url) ?>">
<?php endforeach; ?>

<!-- ── Open Graph ────────────────────────────────────────────────── -->
<meta property="og:type"        content="website">
<meta property="og:title"       content="<?= htmlspecialchars($meta_title) ?>">
<meta property="og:description" content="<?= htmlspecialchars($meta_desc) ?>">
<meta property="og:image"       content="<?= htmlspecialchars($og_image) ?>">
<meta property="og:image:width"  content="1200">
<meta property="og:image:height" content="630">
<meta property="og:url"         content="<?= htmlspecialchars($canonical) ?>">
<meta property="og:site_name"   content="rutasrurales.io">
<meta property="og:locale"      content="<?= htmlspecialchars($t['lang_locale']) ?>">

<!-- ── Twitter Card ──────────────────────────────────────────────── -->
<meta name="twitter:card"        content="summary_large_image">
<meta name="twitter:title"       content="<?= htmlspecialchars($meta_title) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($meta_desc) ?>">
<meta name="twitter:image"       content="<?= htmlspecialchars($og_image) ?>">

<!-- ── Favicon ───────────────────────────────────────────────────── -->
<link rel="icon" href="/menu_images/Favicon.png" type="image/png">

<!-- ── Preconnect ─────────────────────────────────────────────────── -->
<link rel="preconnect" href="https://images.unsplash.com" crossorigin>

<!-- ── Preload LCP image (primera tarjeta) ───────────────────────── -->
<?php if (!empty($result['items'][0]['photo_url'])): ?>
<link rel="preload" as="image" href="<?= htmlspecialchars($result['items'][0]['photo_url']) ?>">
<?php endif; ?>

<!-- ── Fuentes (mismo sistema que el resto del proyecto) ─────────── -->
<style>
@font-face{font-family:'Montserrat';font-style:normal;font-weight:400;font-display:swap;
  src:local('Montserrat Regular'),url('/fonts/montserrat-v31-latin-regular.woff2') format('woff2')}
@font-face{font-family:'Montserrat';font-style:normal;font-weight:500;font-display:swap;
  src:local('Montserrat Medium'),url('/fonts/montserrat-v31-latin-500.woff2') format('woff2')}
@font-face{font-family:'Montserrat';font-style:normal;font-weight:600 800;font-display:swap;
  src:local('Montserrat SemiBold'),url('/fonts/montserrat-v31-latin-600.woff2') format('woff2')}
</style>

<!-- ── CSS CRÍTICO INLINE — above the fold, evita render-blocking ── -->
<style>
/* Reset + variables base */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--primary:#2F5233;--primary-dark:#1a3d1e;--primary-light:#3d6b42;
      --accent:#81C784;--accent-warm:#F9A825;--white:#fff;--bg:#f8f9fa;
      --text:#333;--border:#e8eaed;--radius:12px;--radius-sm:8px;
      --shadow:0 2px 12px rgba(0,0,0,.07);--max-w:1200px;--transition:.18s ease}
body{font-family:'Montserrat','Segoe UI',sans-serif;background:var(--bg);color:var(--text);line-height:1.6;overflow-x:hidden}
img{display:block;max-width:100%;height:auto}
a{color:var(--primary);text-decoration:none}

/* Navbar crítico (sticky, visible inmediatamente) */
.lnd-navbar{position:sticky;top:0;z-index:900;background:var(--white);
  border-bottom:1px solid var(--border);height:64px;display:flex;
  align-items:center;padding:0 20px;gap:16px;
  box-shadow:0 1px 4px rgba(0,0,0,.06);contain:layout style}
.lnd-navbar__logo{display:flex;align-items:center;gap:10px;font-weight:800;
  color:var(--primary);font-size:1rem;text-decoration:none}
.lnd-navbar__logo img{width:40px;height:40px;border-radius:50%;object-fit:cover}
.lnd-navbar__nav{margin-left:auto;display:flex;gap:20px;align-items:center;font-size:.875rem}
.lnd-navbar__nav a{color:var(--text);font-weight:600}
.lnd-navbar__cta{background:var(--primary);color:var(--white)!important;
  padding:8px 18px;border-radius:var(--radius-sm);font-weight:700!important;font-size:.82rem!important}

/* Hero crítico (LCP zone) */
.lnd-hero{
  position: relative;
  overflow: hidden;
  background:linear-gradient(135deg,var(--primary-dark) 0%,var(--primary) 60%,var(--primary-light) 100%);
  padding:0;
  color:var(--white);
  contain:layout style;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  min-height: 320px;
}
.lnd-hero__bg-wrap { position: absolute; inset: 0; z-index: 0; overflow: hidden; }
.lnd-hero__bg-img { width: 100%; height: 100%; object-fit: cover; object-position: center 35%; display: block; }
.lnd-hero::after {
    content: '';
    position: absolute;
    inset: 0;
    z-index: 1;
    background: linear-gradient(to bottom, rgba(10, 25, 47, 0.55) 0%, rgba(10, 25, 47, 0.30) 40%, rgba(10, 25, 47, 0.75) 100%);
    pointer-events: none;
}
.lnd-hero__content {
    position: relative;
    z-index: 2;
    padding: 48px 20px 52px;
    width: 100%;
    max-width: var(--max-w, 1200px);
    margin: 0 auto;
}
.lnd-hero__h1{font-size:clamp(1.6rem,4vw,2.6rem);font-weight:800;line-height:1.15;
  margin:0 0 24px;text-shadow:0 2px 6px rgba(0,0,0,.2)}
.lnd-breadcrumb ol{display:flex;flex-wrap:wrap;gap:4px;align-items:center;
  font-size:.78rem;margin-bottom:18px;color:rgba(255,255,255,.75);list-style:none;padding:0;margin:0 0 18px}
.lnd-breadcrumb a{color:rgba(255,255,255,.75)}
.lnd-bc-sep{color:rgba(255,255,255,.45);margin:0 2px}
.lnd-hero__badges{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px}
.lnd-badge{display:inline-flex;align-items:center;gap:4px;padding:4px 12px;
  border-radius:20px;font-size:.78rem;font-weight:600}
.lnd-badge--filter{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:var(--white)}
.lnd-badge--province{background:var(--accent-warm);color:#1a1a1a}
.lnd-hero__stats{display:flex;flex-wrap:wrap;gap:24px;margin:0 0 16px}
.lnd-stat{display:flex;flex-direction:column;gap:2px}
.lnd-stat__value{font-size:1.6rem;font-weight:800;color:var(--accent);line-height:1}
.lnd-stat__label{font-size:.78rem;color:rgba(255,255,255,.8);font-weight:500}

/* Placeholder para las tarjetas (evita CLS mientras carga CSS no-crítico) */
.lnd-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:24px}
.lnd-card{background:var(--white);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden}
.lnd-card__img-wrap{aspect-ratio:3/2;background:#e8eaed;overflow:hidden}
.lnd-card__img{width:100%;height:100%;object-fit:cover}
.lnd-card__body{padding:16px}
</style>

<!-- ── CSS no-crítico — carga asíncrona (no bloquea renderizado) ─── -->
<link rel="stylesheet"
      href="/alojamientos-landing/css/landing.css"
      media="print"
      onload="this.media='all'">
<noscript><link rel="stylesheet" href="/alojamientos-landing/css/landing.css"></noscript>

<!-- ── JSON-LD Schema.org ──────────────────────────────────────────── -->
<?php renderLandingSchema($ctx); ?>

<!-- ── GTM (diferido, no bloquea) ─────────────────────────────────── -->
<script>
(function(){
  var l=function(){if(window._gtm)return;window._gtm=1;
    (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});
    var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';
    j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-MBP57VQM');
  };
  ['click','scroll','keydown','touchstart'].forEach(function(e){
    window.addEventListener(e,function(){setTimeout(l,1e3)},{once:true,passive:true});
  });
  setTimeout(l,8000);
})();
</script>

</head>
<body>

<!-- GTM noscript -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MBP57VQM"
  height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

<!-- ══════════════════════════════════════════════════════ NAVBAR ══ -->
<header class="lnd-navbar" role="banner">
    <a href="https://rutasrurales.io/" class="lnd-navbar__logo" aria-label="Rutas Rurales - Inicio">
        <img src="/menu_images/Logo%20transparente.webp"
             alt="Rutas Rurales"
             width="40" height="40"
             loading="eager">
        <span>Rutas Rurales</span>
    </a>
    <nav class="lnd-navbar__nav" aria-label="Menú principal">
            <a href="<?= htmlspecialchars($general_listings_url) ?>">
            <?= htmlspecialchars($t['footer_listings']) ?>
        </a>
        <a href="https://rutasrurales.io/lugares-de-interes">
            <?= htmlspecialchars($t['footer_places']) ?>
        </a>
        <a href="https://rutasrurales.io/rutas.php" class="lnd-navbar__cta">
            <?= $lang === 'zh' ? '探索路线' : ($lang === 'de' ? 'Routen' : ($lang === 'fr' ? 'Itinéraires' : ($lang === 'en' ? 'Routes' : 'Rutas'))) ?>
        </a>
    </nav>
</header>

<main id="main-content">

    <!-- ── HERO ──────────────────────────────────────────────────────── -->
    <?php renderLandingHero($ctx); ?>

    <!-- ── INTRO SEO ──────────────────────────────────────────────────── -->
    <?php renderLandingIntro($ctx); ?>

    <!-- ── LISTING DE ALOJAMIENTOS ────────────────────────────────────── -->
    <?php renderAlojamientosLandingListing($ctx); ?>

    <!-- ── CRUCE SEMÁNTICO (Lugares + Rutas + Eventos) ───────────────── -->
    <?php
    renderCruceSemantico([
        't'              => $t,
        'lang'           => $lang,
        'province_label' => $province_label,
        'places'         => $ctx['places'],
        'routes'         => $ctx['routes'],
        'events'         => $ctx['events'],
        'h2_semantico'   => $t['h2_semantico'],
        'h2_rutas'       => $t['h2_rutas'],
        'semantic_intro' => $t['semantic_intro'],
        'semantic_places'=> $t['semantic_places'],
        'semantic_routes'=> $t['semantic_routes'],
        'semantic_events'=> $t['semantic_events'],
        'semantic_cta'   => $t['semantic_cta'],
        'semantic_cta_rt'=> $t['semantic_cta_rt'],
        'entry_fee_free' => $t['entry_fee_free'],
    ]);
    ?>

    <!-- ── CTA FINAL ──────────────────────────────────────────────────── -->
    <?php if ($stats['total'] > 0): ?>
    <section class="lnd-intro" style="border-top:3px solid var(--accent);" aria-label="Llamada a la acción">
        <div class="lnd-intro__inner" style="text-align:center;padding:40px 20px;">
            <h2 class="lnd-intro__h2" style="display:block;text-align:left;">
                <?= htmlspecialchars(str_replace('{PROVINCE}', $province_label, $t['cta_title'] ?? '¿No encuentras lo que buscas?')) ?>
            </h2>
            <p class="lnd-intro__p">
                <?= htmlspecialchars(str_replace('{PROVINCE}', $province_label ?: 'España', $t['cta_desc'] ?? '')) ?>
            </p>
            <a href="https://rutasrurales.io/alojamientos-turisticos"
               class="lnd-btn lnd-btn--primary"
               style="display:inline-flex;margin-top:12px;">
                <?= htmlspecialchars($t['cta_button'] ?? 'Explorar todos los alojamientos') ?>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                </svg>
            </a>
        </div>
    </section>
    <?php endif; ?>

</main>

<!-- ══════════════════════════════════════════════════════ FOOTER ══ -->
<footer class="lnd-footer" role="contentinfo">
    <div class="lnd-footer__inner">
        <nav class="lnd-footer__links" aria-label="Links de pie de página">
            <a href="https://rutasrurales.io/"><?= htmlspecialchars($t['footer_home']) ?></a>
            <a href="<?= htmlspecialchars($general_listings_url) ?>"><?= htmlspecialchars($t['footer_listings']) ?></a>
            <a href="https://rutasrurales.io/lugares-de-interes"><?= htmlspecialchars($t['footer_places']) ?></a>
            <a href="https://rutasrurales.io/eventos-culturales-paginacion.html"><?= htmlspecialchars($t['footer_events']) ?></a>
            <a href="https://rutasrurales.io/aviso-legal.html"><?= htmlspecialchars($t['footer_legal']) ?></a>
        </nav>

        <!-- Selector de idioma — hreflang en acción para el usuario -->
        <nav class="lnd-footer__langs" aria-label="Selector de idioma">
            <?php
            $langLabels = ['es'=>'🇪🇸 Español','en'=>'🇬🇧 English','fr'=>'🇫🇷 Français','de'=>'🇩🇪 Deutsch','zh'=>'🇨🇳 中文'];
            foreach ($hreflang_urls as $hl => $url):
                if ($hl === 'x-default') continue;
            ?>
            <a href="<?= htmlspecialchars($url) ?>"
               hreflang="<?= $hl ?>"
               lang="<?= $hl ?>"
               class="lnd-lang-link<?= ($hl === $lang) ? ' lnd-lang-link--active' : '' ?>"
               <?= ($hl === $lang) ? 'aria-current="true"' : '' ?>>
                <?= $langLabels[$hl] ?? strtoupper($hl) ?>
            </a>
            <?php endforeach; ?>
        </nav>

        <p class="lnd-footer__copy">
            <?= htmlspecialchars(str_replace('{YEAR}', date('Y'), $t['footer_copy'])) ?>
        </p>
    </div>
</footer>

<!-- Estilos inline mínimos para el footer langs (no merecen ir al CSS principal) -->
<style>
.lnd-footer__langs{display:flex;flex-wrap:wrap;gap:12px;font-size:.8rem}
.lnd-lang-link{color:rgba(255,255,255,.6);padding:4px 0}
.lnd-lang-link:hover{color:#fff}
.lnd-lang-link--active{color:#fff;font-weight:700;pointer-events:none}
</style>

</body>
</html>
