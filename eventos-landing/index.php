<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  INDEX.PHP — Orquestador del Sistema de Landings Long-Tail de Eventos
 *  rutasrurales.io/eventos/{filtros}-{provincia}
 *
 *  Ejemplos de URL:
 *    /eventos/musica-soria
 *    /eventos/gratuitos-zamora
 *    /eventos/teatro-danza-salamanca
 *    /eventos/tradiciones-verano-burgos
 *    /en/eventos/music-soria
 *    /fr/eventos/musique-salamanca
 *
 *  Flujo:
 *    1. Parsear slug → provincia + filtros
 *    2. Si slug inválido → redirigir al listado de eventos
 *    3. Detectar idioma
 *    4. Cargar config, i18n, datos
 *    5. Ejecutar queries en BD
 *    6. Preparar $ctx para módulos
 *    7. Renderizar HTML: head → navbar → hero → intro → listing → cruce → footer
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
require_once __DIR__ . '/modules/listing.php';
require_once __DIR__ . '/modules/cruce-semantico.php';

// ── 1. Parámetros de entrada ──────────────────────────────────────────────────
$slug_raw = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$lang     = isset($_GET['lang']) ? trim($_GET['lang']) : 'es';
$lang     = in_array($lang, ['es', 'en', 'fr', 'de', 'zh'], true) ? $lang : 'es';
$page     = max(1, (int)($_GET['p'] ?? $_GET['page'] ?? 1));

// Sanitizar slug: solo a-z 0-9 y guiones
$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug_raw));

// ── 2. Parsear slug → provincia + filtros ─────────────────────────────────────
$parsed = parseEventosLandingSlug($slug);

// Si el slug no es válido como landing de eventos, redirigir al listado
if (!$parsed['valid'] && !empty($slug)) {
    header('Location: https://rutasrurales.io/eventos-culturales-paginacion.html', true, 301);
    exit;
}

// Si slug vacío → redirigir al listado
if (empty($slug)) {
    header('Location: https://rutasrurales.io/eventos-culturales-paginacion.html', true, 301);
    exit;
}

// ── 3. Resolución de datos de provincia y filtros ─────────────────────────────
$province_key  = $parsed['province'];
$province_data = !empty($province_key) ? (EVENTOS_PROVINCIAS[$province_key] ?? []) : [];
$province_db   = $province_data['db']    ?? null;
$province_label= $province_data['label'] ?? '';

// Filtros activos: labels, iconos, condiciones SQL
$filter_keys     = $parsed['filters'];
$filter_labels   = [];
$filter_icons    = [];
$sql_conditions  = [];

foreach ($filter_keys as $fk) {
    if (isset(EVENTOS_FILTROS[$fk])) {
        $fd = EVENTOS_FILTROS[$fk];
        $filter_labels[] = $fd['labels'][$lang] ?? $fd['labels']['es'];
        $filter_icons[]  = $fd['icon'];
        $sql_conditions[]= $fd['sql'];
    }
}

// ── 4. Cargar traducciones ────────────────────────────────────────────────────
$t = getEventosLandingTranslations($lang);

// ── 5. Construir textos SEO dinámicos ─────────────────────────────────────────
$primary_filter_label = !empty($filter_labels)
    ? $filter_labels[0]
    : ($lang === 'es' ? 'Eventos culturales' : ($lang === 'en' ? 'Cultural events' : ($lang === 'fr' ? 'Événements culturels' : ($lang === 'de' ? 'Kulturveranstaltungen' : '文化活动'))));

$feature_label = !empty($filter_labels[1]) ? mb_strtolower($filter_labels[1])
    : ($lang === 'es' ? 'entrada gratuita y agenda actualizada' : 'free entry and updated calendar');

// H1
$h1 = '';
if (!empty($filter_labels) && !empty($province_label)) {
    $mainFilter = $filter_labels[0];
    if (count($filter_labels) > 1) {
        $and_word = ['es'=>'y','en'=>'and','fr'=>'et','de'=>'und','zh'=>'和'][$lang] ?? 'y';
        $extras = implode(' ' . $and_word . ' ', array_slice($filter_labels, 1));
        $mainFilter .= ' ' . $extras;
    }
    $h1 = t($t['h1_template'], ['FILTER_LABEL' => $mainFilter, 'PROVINCE' => $province_label]);
} elseif (!empty($filter_labels)) {
    $h1 = implode(' ', $filter_labels);
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
    ? "$base_domain/eventos/$slug"
    : "$base_domain/$lang/eventos/$slug";

$hreflang_urls = [
    'es'        => "$base_domain/eventos/$slug",
    'en'        => "$base_domain/en/eventos/$slug",
    'fr'        => "$base_domain/fr/eventos/$slug",
    'de'        => "$base_domain/de/eventos/$slug",
    'zh'        => "$base_domain/zh/eventos/$slug",
    'x-default' => "$base_domain/eventos/$slug",
];

// ── 7. Consultas a BD ─────────────────────────────────────────────────────────
$result   = ['items' => [], 'total' => 0, 'pages' => 0, 'page' => 1];
$stats    = ['total' => 0, 'free_count' => 0, 'towns' => 0];
$semantic = ['accommodations' => [], 'places' => [], 'routes' => []];

try {
    $pdo = getDBConnection();

    $result  = getLandingEventos($pdo, $province_db, $sql_conditions, $page, EVENTOS_PER_PAGE, $lang);
    $stats   = getLandingEventosStats($pdo, $province_db, $sql_conditions);

    if (!empty($province_db)) {
        $semantic = getEventosSemanticCrossing($pdo, $province_db, 6);
    }
} catch (Throwable $e) {
    error_log('[eventos-landing] BD error: ' . $e->getMessage());
}

// ── 8. Contexto compartido para módulos ──────────────────────────────────────
$ctx = [
    // Identidad
    'slug'           => $slug,
    'lang'           => $lang,
    'canonical'      => $canonical,
    'parsed'         => $parsed,

    // Provincia
    'province_key'   => $province_key,
    'province_label' => $province_label,
    'province_data'  => $province_data,

    // Filtros
    'filter_keys'    => $filter_keys,
    'filter_labels'  => $filter_labels,
    'filter_icons'   => $filter_icons,
    'filter_label'   => $primary_filter_label,

    // SEO
    'page_title'     => $meta_title,
    'page_desc'      => $meta_desc,
    'h1'             => $h1,
    'h2_listing'     => $h2_listing,
    'lang_locale'    => $t['lang_locale'],

    // Datos
    'items'          => $result['items'],
    'total'          => $result['total'],
    'pages'          => $result['pages'],
    'page'           => $result['page'],
    'stats'          => $stats,
    'accommodations' => $semantic['accommodations'],
    'places'         => $semantic['places'],
    'routes'         => $semantic['routes'],

    // Traducciones
    't'              => $t,

    // BD (para procesarInboundLinks en módulos — una sola query gracias al cache)
    'pdo'            => $pdo ?? null,
];

// ── 9. Imagen OG ──────────────────────────────────────────────────────────────
$og_image = !empty($result['items'][0]['photo_url'])
    ? $result['items'][0]['photo_url']
    : 'https://rutasrurales.io/menu_images/og-default.jpg';

// ── Paginación: rel prev/next ─────────────────────────────────────────────────
$rel_prev = ($page > 1)                  ? $canonical . '?p=' . ($page - 1) : null;
$rel_next = ($page < $result['pages'])   ? $canonical . '?p=' . ($page + 1) : null;

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
<link rel="sitemap" type="application/xml" title="Sitemap" href="/sitemap.xml">
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

<!-- ── Fuentes ────────────────────────────────────────────────────── -->
<style>
@font-face{font-family:'Montserrat';font-style:normal;font-weight:400;font-display:swap;
  src:local('Montserrat Regular'),url('/fonts/montserrat-v31-latin-regular.woff2') format('woff2')}
@font-face{font-family:'Montserrat';font-style:normal;font-weight:600;font-display:swap;
  src:local('Montserrat SemiBold'),url('/fonts/montserrat-v31-latin-600.woff2') format('woff2')}
@font-face{font-family:'Montserrat';font-style:normal;font-weight:800;font-display:swap;
  src:local('Montserrat ExtraBold'),url('/fonts/montserrat-v31-latin-800.woff2') format('woff2')}
</style>

<!-- ── CSS CRÍTICO INLINE — above the fold ────────────────────────── -->
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--primary:#2F5233;--primary-dark:#1a3d1e;--primary-light:#3d6b42;
      --accent:#81C784;--accent-warm:#F9A825;--white:#fff;--bg:#f8f9fa;
      --text:#333;--border:#e8eaed;--radius:12px;--radius-sm:8px;
      --shadow:0 2px 12px rgba(0,0,0,.07);--max-w:1200px;--transition:.18s ease}
body{font-family:'Montserrat','Segoe UI',sans-serif;background:var(--bg);color:var(--text);line-height:1.6;overflow-x:hidden}
img{display:block;max-width:100%;height:auto}
a{color:var(--primary);text-decoration:none}

/* Navbar crítico */
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
.lnd-hero{background:linear-gradient(135deg,#1a3a5c 0%,#2d5a8e 60%,#3d7abf 100%);
  padding:48px 20px 52px;color:var(--white);contain:layout style}
.lnd-hero__h1{font-size:clamp(1.6rem,4vw,2.6rem);font-weight:800;line-height:1.15;
  margin:0 0 24px;text-shadow:0 2px 6px rgba(0,0,0,.2)}
.lnd-breadcrumb ol{display:flex;flex-wrap:wrap;gap:4px;align-items:center;
  font-size:.78rem;margin:0 0 18px;color:rgba(255,255,255,.75);list-style:none;padding:0}
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

/* Tarjetas evento (evita CLS) */
.lnd-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:24px}
.lnd-card{background:var(--white);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;position:relative}
.lnd-card__img-wrap{aspect-ratio:3/2;background:#e8eaed;overflow:hidden;display:block;position:relative}
.lnd-card__img{width:100%;height:100%;object-fit:cover}
.lnd-card__body{padding:16px}

/* Badge fecha calendario en la tarjeta */
.lnd-card__date-badge{position:absolute;top:12px;left:12px;background:var(--white);
  border-radius:8px;padding:6px 10px;text-align:center;z-index:2;
  box-shadow:0 2px 8px rgba(0,0,0,.15);min-width:48px}
.lnd-card__date-dia{display:block;font-size:1.4rem;font-weight:800;color:var(--primary);line-height:1}
.lnd-card__date-mes{display:block;font-size:.65rem;font-weight:700;color:var(--text);text-transform:uppercase;letter-spacing:.05em}
</style>

<!-- ── CSS no-crítico (carga asíncrona) ───────────────────────────── -->
<link rel="stylesheet"
      href="/alojamientos-landing/css/landing.css"
      media="print"
      onload="this.media='all'">
<noscript><link rel="stylesheet" href="/alojamientos-landing/css/landing.css"></noscript>

<!-- ── Estilos específicos de eventos (tarjeta fecha, precio badge) ── -->
<style>
/* Reutilizamos el CSS de alojamientos-landing y añadimos solo los delta */
.lnd-card--evento{padding-top:0}
.lnd-card__price-badge{position:absolute;bottom:12px;right:12px;z-index:2;
  padding:4px 10px;border-radius:20px;font-size:.75rem;font-weight:700;
  background:rgba(0,0,0,.55);color:#fff;backdrop-filter:blur(4px)}
.lnd-card__price-badge--free{background:var(--primary);color:#fff}
.lnd-card__dates{display:flex;flex-wrap:wrap;gap:8px;margin:6px 0;font-size:.78rem;color:var(--text)}
.lnd-card__date-text{display:flex;align-items:center;gap:4px}
.lnd-card__date-text--end{color:#888}
.lnd-sem-card__type{display:inline-block;font-size:.7rem;font-weight:600;
  text-transform:uppercase;letter-spacing:.06em;color:var(--primary);margin-bottom:4px}
.lnd-hero__sublink{margin-top:12px;font-size:.85rem}
.lnd-hero__sublink a{color:rgba(255,255,255,.8);text-decoration:underline}
.lnd-hero__sublink a:hover{color:#fff}
</style>

<!-- ── JSON-LD Schema.org ──────────────────────────────────────────── -->
<?php renderEventosLandingSchema($ctx); ?>

<!-- ── GTM (diferido) ─────────────────────────────────────────────── -->
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
        <a href="https://rutasrurales.io/eventos-culturales-paginacion.html">
            <?= htmlspecialchars($t['footer_events']) ?>
        </a>
        <a href="https://rutasrurales.io/alojamientos-turisticos">
            <?= htmlspecialchars($t['footer_stays']) ?>
        </a>
        <a href="https://rutasrurales.io/rutas.php" class="lnd-navbar__cta">
            <?= $lang === 'zh' ? '探索路线' : ($lang === 'de' ? 'Routen' : ($lang === 'fr' ? 'Itinéraires' : ($lang === 'en' ? 'Routes' : 'Rutas'))) ?>
        </a>
    </nav>
</header>

<main id="main-content">

    <!-- ── HERO ──────────────────────────────────────────────────────── -->
    <?php renderEventosLandingHero($ctx); ?>

    <!-- ── INTRO SEO ──────────────────────────────────────────────────── -->
    <?php renderEventosLandingIntro($ctx); ?>

    <!-- ── LISTING DE EVENTOS ─────────────────────────────────────────── -->
    <?php renderEventosLandingListing($ctx); ?>

    <!-- ── CRUCE SEMÁNTICO (Alojamientos + Lugares + Rutas) ──────────── -->
    <?php
    renderEventosCruceSemantico([
        't'              => $t,
        'lang'           => $lang,
        'province_label' => $province_label,
        'accommodations' => $ctx['accommodations'],
        'places'         => $ctx['places'],
        'routes'         => $ctx['routes'],
        'h2_semantico'   => $t['h2_semantico'],
        'h2_rutas'       => $t['h2_rutas'],
        'semantic_intro' => $t['semantic_intro'],
        'semantic_stays' => $t['semantic_stays'],
        'semantic_places'=> $t['semantic_places'],
        'semantic_routes'=> $t['semantic_routes'],
        'semantic_cta_alo'=> $t['semantic_cta_alo'],
        'semantic_cta_poi'=> $t['semantic_cta_poi'],
        'semantic_cta_rt'=> $t['semantic_cta_rt'],
        'entry_fee_free' => $t['entry_fee_free'],
        'card_precio'    => $t['card_precio'],
        'price_per_night'=> $t['price_per_night'],
    ]);
    ?>

    <!-- ── CTA FINAL ──────────────────────────────────────────────────── -->
    <?php if ($stats['total'] > 0):
        // Enlace a la agenda completa de la provincia (sin filtros)
        $cta_url = !empty($province_key)
            ? ($lang !== 'es'
                ? "https://rutasrurales.io/$lang/eventos/$province_key"
                : "https://rutasrurales.io/eventos/$province_key")
            : "https://rutasrurales.io/eventos-culturales-paginacion.html";
    ?>
    <section class="lnd-intro" style="border-top:3px solid var(--accent);" aria-label="Llamada a la acción">
        <div class="lnd-intro__inner" style="text-align:center;padding:40px 20px;">
            <h2 class="lnd-intro__h2" style="display:block;text-align:left;">
                <?= htmlspecialchars(str_replace('{PROVINCE}', $province_label, $t['cta_title'] ?? '¿Buscas más eventos?')) ?>
            </h2>
            <p class="lnd-intro__p">
                <?= htmlspecialchars(str_replace('{PROVINCE}', $province_label ?: 'España', $t['cta_desc'] ?? '')) ?>
            </p>
            <a href="<?= htmlspecialchars($cta_url) ?>"
               class="lnd-btn lnd-btn--primary"
               style="display:inline-flex;margin-top:12px;">
                <?= htmlspecialchars($t['cta_button'] ?? 'Ver agenda completa') ?>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 19"/>
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
            <a href="https://rutasrurales.io/eventos-culturales-paginacion.html"><?= htmlspecialchars($t['footer_events']) ?></a>
            <a href="https://rutasrurales.io/alojamientos-turisticos"><?= htmlspecialchars($t['footer_stays']) ?></a>
            <a href="https://rutasrurales.io/lugares-de-interes"><?= htmlspecialchars($t['footer_places']) ?></a>
            <a href="https://rutasrurales.io/aviso-legal.html"><?= htmlspecialchars($t['footer_legal']) ?></a>
        </nav>

        <!-- Selector de idioma -->
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

<style>
.lnd-footer__langs{display:flex;flex-wrap:wrap;gap:12px;font-size:.8rem}
.lnd-lang-link{color:rgba(255,255,255,.6);padding:4px 0}
.lnd-lang-link:hover{color:#fff}
.lnd-lang-link--active{color:#fff;font-weight:700;pointer-events:none}
</style>

</body>
</html>
