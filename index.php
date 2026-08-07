<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  INDEX.PHP — Hub de Autoridad de rutasrurales.io
 *  Versión 2.0 — Rediseño completo orientado a SEO y Crawl Budget
 *
 *  Arquitectura:
 *    1. Detección de idioma (por URL prefix o parámetro ?lang=)
 *    2. Carga de configuración, traducciones y módulos
 *    3. Query a BD para estadísticas (con fallback graceful)
 *    4. Renderizado: head → navbar → hero → hub_alo → hub_evt → autoridad → footer
 *
 *  URLs multilingüe (gestionadas por el .htaccess):
 *    /             → Español (este archivo)
 *    /en/          → English  (en/index.html redirige aquí con ?lang=en)
 *    /fr/          → Français
 *    /de/          → Deutsch
 *    /zh/          → 中文
 *
 *  Estrategia hreflang:
 *    - Alojamientos y Eventos: 5 idiomas disponibles (/lang/alojamientos/slug)
 *    - Lugares y Actividades:  solo ES por ahora; fallback a URL ES con nota
 *    - x-default apunta siempre a la versión ES
 * ════════════════════════════════════════════════════════════════════════════
 */

ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);
if (!defined('API_NO_HEADERS')) {
    define('API_NO_HEADERS', true);
}

// ── Rutas base ────────────────────────────────────────────────────────────────
$_BASE  = __DIR__;
$_INDEX = __DIR__ . '/index';

// ── Dependencias de las landings (provincias, filtros, helpers) ───────────────
// Solo las constantes de provincias/filtros que necesita el hub
require_once $_INDEX . '/config/hub-config.php';
require_once $_INDEX . '/i18n/translations.php';

// ── Módulos del hub ───────────────────────────────────────────────────────────
require_once $_INDEX . '/modules/schema.php';
require_once $_INDEX . '/modules/hero.php';
require_once $_INDEX . '/modules/hub-alojamientos.php';
require_once $_INDEX . '/modules/hub-eventos.php';
require_once $_INDEX . '/modules/autoridad-seo.php';

// ── 1. DETECCIÓN DE IDIOMA ────────────────────────────────────────────────────
// El .htaccess pasa ?lang=XX cuando accede desde /en/, /fr/, etc.
// También acepta el parámetro directo en la URL (para compatibilidad)
$lang = isset($_GET['lang']) ? trim($_GET['lang']) : 'es';
$lang = in_array($lang, ['es', 'en', 'fr', 'de', 'zh'], true) ? $lang : 'es';

// ── 2. TRADUCCIONES ───────────────────────────────────────────────────────────
$t = getHubTranslations($lang);

// ── 3. TEMPORADA ACTUAL (para el hub de eventos) ──────────────────────────────
$temporada = getTemporadaActual();

// ── 4. ESTADÍSTICAS DESDE BD (con fallback graceful) ─────────────────────────
$stats = ['total_stays' => null, 'total_events' => null, 'total_prov' => 12];

try {
    if (file_exists($_BASE . '/api/config.php')) {
        require_once $_BASE . '/api/config.php';
        $pdo = getDBConnection();

        $rowStays = $pdo->query(
            "SELECT COUNT(*) AS c FROM accommodations WHERE is_active = 1"
        )->fetch(PDO::FETCH_ASSOC);

        $rowEvents = $pdo->query(
            "SELECT COUNT(*) AS c FROM cultural_events WHERE is_active = 1"
        )->fetch(PDO::FETCH_ASSOC);

        $rowProv = $pdo->query(
            "SELECT COUNT(DISTINCT province) AS c FROM accommodations WHERE is_active = 1"
        )->fetch(PDO::FETCH_ASSOC);

        $stats['total_stays']  = '+' . number_format((int)($rowStays['c'] ?? 0), 0, ',', '.');
        $stats['total_events'] = '+' . number_format((int)($rowEvents['c'] ?? 0), 0, ',', '.');
        $stats['total_prov']   = (int)($rowProv['c'] ?? 12);
    }
} catch (Throwable $e) {
    error_log('[index.php] BD stats error: ' . $e->getMessage());
    // Fallback: valores ilustrativos
    $stats = ['total_stays' => '+500', 'total_events' => '+1.200', 'total_prov' => 12];
}

// ── 5. CONTEXTO COMPARTIDO ────────────────────────────────────────────────────
$ctx = [
    'lang'      => $lang,
    't'         => $t,
    'stats'     => $stats,
    'temporada' => $temporada,
];

// ── 6. URLs CANÓNICAS Y HREFLANG ──────────────────────────────────────────────
$base_domain = 'https://rutasrurales.io';
$canonical   = $lang === 'es'
    ? $base_domain . '/'
    : $base_domain . '/' . $lang . '/';

// Hreflang para el Index
// Todos los idiomas apuntan a sus versiones de index.html en las carpetas de idioma
// excepto ES que es la raíz
$hreflang_urls = [
    'es'        => $base_domain . '/',
    'en'        => $base_domain . '/en/',
    'fr'        => $base_domain . '/fr/',
    'de'        => $base_domain . '/de/',
    'zh'        => $base_domain . '/zh/',
    'x-default' => $base_domain . '/',
];

// ── 7. OG IMAGE ───────────────────────────────────────────────────────────────
$og_image = $base_domain . '/menu_images/og-default.jpg';
$og_title = $t['og_title'] ?? $t['meta_title'];

// ── HELPERS locales ───────────────────────────────────────────────────────────
$langPfx = ($lang !== 'es') ? '/' . $lang : '';

?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>" dir="<?= $t['dir'] ?? 'ltr' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- ── SEO Primario ──────────────────────────────────────────────────────── -->
<title><?= htmlspecialchars($t['meta_title']) ?></title>
<meta name="description" content="<?= htmlspecialchars($t['meta_desc']) ?>">
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
<link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">

<!-- ── hreflang — 5 idiomas + x-default ─────────────────────────────────── -->
<?php foreach ($hreflang_urls as $hl_lang => $hl_url): ?>
<link rel="alternate" hreflang="<?= htmlspecialchars($hl_lang) ?>" href="<?= htmlspecialchars($hl_url) ?>">
<?php endforeach; ?>

<!-- ── Open Graph ───────────────────────────────────────────────────────── -->
<meta property="og:type"         content="website">
<meta property="og:title"        content="<?= htmlspecialchars($og_title) ?>">
<meta property="og:description"  content="<?= htmlspecialchars($t['meta_desc']) ?>">
<meta property="og:image"        content="<?= htmlspecialchars($og_image) ?>">
<meta property="og:image:width"  content="1200">
<meta property="og:image:height" content="630">
<meta property="og:url"          content="<?= htmlspecialchars($canonical) ?>">
<meta property="og:site_name"    content="Rutas Rurales">
<meta property="og:locale"       content="<?= htmlspecialchars($t['lang_locale'] ?? 'es-ES') ?>">

<!-- ── Twitter Card ─────────────────────────────────────────────────────── -->
<meta name="twitter:card"        content="summary_large_image">
<meta name="twitter:site"        content="@rutas_rurales">
<meta name="twitter:title"       content="<?= htmlspecialchars($og_title) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($t['meta_desc']) ?>">
<meta name="twitter:image"       content="<?= htmlspecialchars($og_image) ?>">

<!-- ── Favicon ──────────────────────────────────────────────────────────── -->
<link rel="icon"             href="/menu_images/Favicon.png" type="image/png">
<link rel="apple-touch-icon" href="/menu_images/Favicon.png">

<!-- ── Preconnect ───────────────────────────────────────────────────────── -->
<link rel="preconnect" href="https://hatscripts.github.io" crossorigin>

<!-- ── Preload: Logo y hero (LCP crítico) ───────────────────────────────── -->
<link rel="preload" as="image" href="/menu_images/Logo%20transparente.webp" type="image/webp">
<link rel="preload" as="image" href="/menu_images/hero_main.webp" type="image/webp" fetchpriority="high">

<!-- ── Fuentes locales (no bloquean render) ─────────────────────────────── -->
<style>
@font-face{font-family:'Montserrat';font-style:normal;font-weight:400;font-display:swap;
  src:local('Montserrat Regular'),url('/fonts/montserrat-v31-latin-regular.woff2') format('woff2')}
@font-face{font-family:'Montserrat';font-style:normal;font-weight:600;font-display:swap;
  src:local('Montserrat SemiBold'),url('/fonts/montserrat-v31-latin-600.woff2') format('woff2')}
@font-face{font-family:'Montserrat';font-style:normal;font-weight:800;font-display:swap;
  src:local('Montserrat ExtraBold'),url('/fonts/montserrat-v31-latin-800.woff2') format('woff2')}
</style>

<!-- ── CSS CRÍTICO INLINE (above the fold — evita render-blocking) ───────── -->
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --primary:#2F5233;--primary-dark:#1a3d1e;--primary-light:#3d6b42;
  --accent:#81C784;--accent-warm:#F9A825;--accent-blue:#1a3a5c;
  --white:#fff;--bg:#f8f9fa;--bg-alt:#f0f4f1;
  --text:#2d3436;--text-light:#636e72;--border:#e8eaed;
  --radius:14px;--radius-sm:8px;
  --shadow:0 2px 12px rgba(0,0,0,.07);
  --max-w:1200px;--transition:.18s ease}
html{scroll-behavior:smooth}
body{font-family:'Montserrat','Segoe UI',system-ui,sans-serif;
  background:var(--bg);color:var(--text);line-height:1.65;overflow-x:hidden;
  -webkit-font-smoothing:antialiased}
img{display:block;max-width:100%;height:auto}
a{color:var(--primary);text-decoration:none}
ul{list-style:none}

/* Navbar crítico */
.hub-navbar{position:sticky;top:0;z-index:900;background:var(--white);
  border-bottom:1px solid var(--border);height:64px;display:flex;
  align-items:center;padding:0 20px;gap:16px;
  box-shadow:0 1px 6px rgba(0,0,0,.06);contain:layout style}
.hub-navbar__logo{display:flex;align-items:center;gap:10px;font-weight:800;
  color:var(--primary);font-size:1rem;text-decoration:none;flex-shrink:0}
.hub-navbar__logo img{width:40px;height:40px;border-radius:50%;object-fit:cover}
.hub-navbar__nav{display:flex;align-items:center;gap:6px;margin-left:auto;font-size:.82rem}
.hub-navbar__nav a{color:var(--text);font-weight:600;padding:6px 12px;
  border-radius:var(--radius-sm);white-space:nowrap}
.hub-navbar__cta{background:var(--primary)!important;color:var(--white)!important;
  padding:8px 16px!important;border-radius:var(--radius-sm)!important;font-weight:700!important}
.hub-hamburger{display:none;flex-direction:column;gap:5px;background:none;
  border:none;cursor:pointer;padding:8px;margin-left:auto}
.hub-hamburger span{display:block;width:24px;height:2px;background:var(--text);border-radius:2px}

/* Hero crítico (LCP zone) */
.hub-hero{position:relative;min-height:540px;display:flex;align-items:center;
  overflow:hidden;contain:layout}
.hub-hero__bg{position:absolute;inset:0;z-index:0}
.hub-hero__bg-img{width:100%;height:100%;object-fit:cover;object-position:center 40%}
.hub-hero__overlay{position:absolute;inset:0;
  background:linear-gradient(135deg,rgba(26,61,30,.88) 0%,rgba(47,82,51,.75) 50%,rgba(26,61,30,.55) 100%)}
.hub-hero__inner{position:relative;z-index:1;padding:60px 20px 56px;width:100%}
.hub-hero__h1{font-size:clamp(1.8rem,4.5vw,3rem);font-weight:800;color:var(--white);
  line-height:1.1;margin-bottom:16px;text-shadow:0 2px 8px rgba(0,0,0,.25);max-width:700px}
.hub-hero__sub{font-size:clamp(.95rem,2vw,1.15rem);color:rgba(255,255,255,.88);
  margin-bottom:36px;max-width:600px;font-weight:500;line-height:1.5}
.hub-container{max-width:var(--max-w);margin:0 auto;padding:0 20px}
.hub-hero__actions{display:flex;flex-wrap:wrap;gap:12px;align-items:center;margin-top:24px}
.home-share-btn{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:var(--white);padding:10px 18px;border-radius:25px;text-decoration:none;font-size:1rem;font-weight:600;transition:background .2s ease;cursor:pointer}
.home-share-btn:hover{background:rgba(255,255,255,.25)}
.share-modal{position:fixed;top:0;left:0;width:100%;height:100%;z-index:2000;display:flex;align-items:center;justify-content:center}
.share-modal__overlay{position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.6);backdrop-filter:blur(5px)}
.share-modal__content{position:relative;background:var(--white);border-radius:var(--radius);padding:24px;width:90%;max-width:400px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,.2)}
.share-modal__close{position:absolute;top:10px;right:10px;background:0 0;border:none;font-size:24px;cursor:pointer;color:#888}
.share-modal__title{font-size:1.5rem;margin:0 0 8px;color:var(--primary)}
.share-modal__subtitle{font-size:1rem;margin:0 0 20px;color:var(--text-light)}
.share-modal__buttons{display:flex;gap:12px;margin-bottom:20px}
.share-modal__btn{flex:1;display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:12px;border-radius:var(--radius-sm);text-decoration:none;font-weight:600;color:var(--white);border:none}.share-modal__btn svg{width:20px;height:20px}.share-modal__btn--whatsapp{background:#25D366}.share-modal__btn--twitter{background:#1DA1F2}
.share-modal__copy-link{display:flex}
.share-modal__copy-link input{flex:1;border:1px solid var(--border);border-radius:var(--radius-sm) 0 0 var(--radius-sm);padding:10px;background:var(--bg);color:var(--text)}
.share-modal__copy-link button{background:var(--primary);color:var(--white);border:none;padding:0 15px;border-radius:0 var(--radius-sm) var(--radius-sm) 0;cursor:pointer;font-weight:600}

/* Placeholder acordeones (evita CLS) */
.hub-accordion{background:var(--white);border:1px solid var(--border);
  border-radius:var(--radius);margin-bottom:12px;overflow:hidden;
  box-shadow:var(--shadow)}
.hub-section{padding:72px 0}
.hub-section--alt{background:var(--bg-alt)}

@media(max-width:900px){
  .hub-navbar__nav{display:none}
  .hub-hamburger{display:flex}
}
@media(max-width:600px){
  .hub-hero{min-height:480px}
  .hub-hero__inner{padding:48px 16px 44px}
  .hub-section{padding:52px 0}
}
</style>

<!-- ── CSS no-crítico — carga asíncrona (no bloquea render) ─────────────── -->
<link rel="stylesheet"
      href="/index/css/index.css?v=2.0"
      media="print"
      onload="this.media='all'">
<noscript><link rel="stylesheet" href="/index/css/index.css?v=2.0"></noscript>

<!-- ── JSON-LD Schema.org ───────────────────────────────────────────────── -->
<?php renderHubSchema($ctx); ?>

<!-- ── GTM diferido (no bloquea, carga tras interacción) ────────────────── -->
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
<!-- ── GA diferido ────────────────────────────────────────────────────────── -->
<script>
(function(){
  var l=function(){if(window._ga)return;window._ga=1;
    var s=document.createElement('script');s.async=true;
    s.src='https://www.googletagmanager.com/gtag/js?id=G-X990K5GE42';
    document.head.appendChild(s);
    window.dataLayer=window.dataLayer||[];
    function gtag(){dataLayer.push(arguments);}
    gtag('js',new Date());gtag('config','G-X990K5GE42');
  };
  ['click','scroll','keydown','touchstart'].forEach(function(e){
    window.addEventListener(e,function(){setTimeout(l,3e3)},{once:true,passive:true});
  });
  setTimeout(l,12000);
})();
</script>

</head>
<body>

<!-- GTM noscript -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MBP57VQM"
  height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

<!-- Skip link (accesibilidad) -->
<a href="#main-content" class="skip-link">
    <?php echo $lang === 'es' ? 'Saltar al contenido principal' : 'Skip to main content'; ?>
</a>

<!-- ══════════════════════════════════════════════════════════ NAVBAR ══════ -->
<header class="hub-navbar" role="banner">

    <!-- Logo -->
    <a href="<?= $base_domain ?>/" class="hub-navbar__logo" aria-label="Rutas Rurales — Inicio">
        <img src="/menu_images/Logo%20transparente.webp"
             alt="Rutas Rurales"
             width="40" height="40"
             loading="eager">
        <span>Rutas Rurales</span>
    </a>

    <!-- Navegación desktop -->
    <nav class="hub-navbar__nav" aria-label="Menú principal">
        <a href="<?= $base_domain . $langPfx ?>/alojamientos/turismo-rural">
            <?= htmlspecialchars($t['nav_stays']) ?>
        </a>
        <a href="<?= $base_domain ?>/eventos-culturales">
            <?= htmlspecialchars($t['nav_events']) ?>
        </a>
        <a href="<?= $base_domain ?>/lugares-de-interes">
            <?= htmlspecialchars($t['nav_places']) ?>
        </a>
        <a href="<?= $base_domain ?>/actividades-turisticas">
            <?= htmlspecialchars($t['nav_activities']) ?>
        </a>
        <a href="<?= $base_domain ?>/rutas.php">
            🗺️ <?= $lang === 'es' ? 'Mapa' : ($lang === 'en' ? 'Map' : ($lang === 'fr' ? 'Carte' : ($lang === 'de' ? 'Karte' : '地图'))) ?>
        </a>
        <a href="<?= $base_domain ?>/login.html" class="hub-navbar__cta">
            <?= htmlspecialchars($t['nav_login']) ?>
        </a>
    </nav>

    <!-- Selector de idioma desktop -->
    <div class="hub-lang-selector" id="langSelector" aria-label="<?= htmlspecialchars($t['nav_language']) ?>">
        <button class="hub-lang-btn" id="langBtn" aria-expanded="false" aria-haspopup="listbox">
            <img src="<?= HUB_LANGS[$lang]['flag_svg'] ?>"
                 alt="<?= htmlspecialchars(HUB_LANGS[$lang]['label']) ?>"
                 width="20" height="20" loading="lazy">
            <span><?= strtoupper($lang) ?></span>
            <svg class="hub-lang-chevron" width="14" height="14" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </button>
        <div class="hub-lang-dropdown" id="langDropdown" role="listbox">
            <?php foreach (HUB_LANGS as $lk => $lv):
                $lUrl = $lk === 'es'
                    ? $base_domain . '/'
                    : $base_domain . '/' . $lk . '/';
            ?>
            <a href="<?= htmlspecialchars($lUrl) ?>"
               role="option"
               hreflang="<?= $lk ?>"
               lang="<?= $lk ?>"
               class="<?= ($lk === $lang) ? 'active' : '' ?>"
               <?= ($lk === $lang) ? 'aria-selected="true"' : '' ?>>
                <img src="<?= $lv['flag_svg'] ?>"
                     alt="<?= htmlspecialchars($lv['label']) ?>"
                     width="22" height="22" loading="lazy">
                <?= htmlspecialchars($lv['label']) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Hamburguesa móvil -->
    <button class="hub-hamburger" id="hubHamburger"
            aria-label="<?= $lang === 'es' ? 'Abrir menú' : 'Open menu' ?>"
            aria-expanded="false"
            aria-controls="hubMobileMenu">
        <span></span><span></span><span></span>
    </button>

</header>

<!-- Menú móvil -->
<nav class="hub-navbar__mobile-menu" id="hubMobileMenu" aria-label="Menú móvil" aria-hidden="true">
    <a href="<?= $base_domain . $langPfx ?>/alojamientos/turismo-rural">
        <span class="hub-cta__icon">🏡</span>
        <?= htmlspecialchars($t['nav_stays']) ?>
    </a>
    <a href="<?= $base_domain ?>/eventos-culturales">
        <span class="hub-cta__icon">🎭</span>
        <?= htmlspecialchars($t['nav_events']) ?>
    </a>
    <a href="<?= $base_domain ?>/lugares-de-interes">
        <span class="hub-cta__icon">🏛️</span>
        <?= htmlspecialchars($t['nav_places']) ?>
    </a>
    <a href="<?= $base_domain ?>/actividades-turisticas">
        <span class="hub-cta__icon">🥾</span>
        <?= htmlspecialchars($t['nav_activities']) ?>
    </a>
    <a href="<?= $base_domain ?>/rutas.php">
        <span class="hub-cta__icon">🗺️</span>
        <?= $lang === 'es' ? 'Mapa interactivo' : ($lang === 'en' ? 'Interactive map' : ($lang === 'fr' ? 'Carte interactive' : ($lang === 'de' ? 'Interaktive Karte' : '互动地图'))) ?>
    </a>
    <a href="<?= $base_domain ?>/login.html">
        <span class="hub-cta__icon">👤</span>
        <?= htmlspecialchars($t['nav_login']) ?>
    </a>
    <!-- Selector de idioma móvil -->
    <div style="margin-top:20px;padding-top:20px;border-top:1px solid var(--border)">
        <p style="font-size:.78rem;font-weight:700;color:var(--text-light);text-transform:uppercase;
                  letter-spacing:.08em;margin-bottom:12px">
            <?= htmlspecialchars($t['nav_language']) ?>
        </p>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
            <?php foreach (HUB_LANGS as $lk => $lv):
                $lUrl = $lk === 'es'
                    ? $base_domain . '/'
                    : $base_domain . '/' . $lk . '/';
            ?>
            <a href="<?= htmlspecialchars($lUrl) ?>"
               hreflang="<?= $lk ?>"
               style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;
                      border:1px solid var(--border);border-radius:20px;font-size:.82rem;
                      font-weight:600;color:<?= ($lk===$lang) ? 'var(--primary)' : 'var(--text)' ?>;
                      background:<?= ($lk===$lang) ? 'var(--bg-alt)' : 'transparent' ?>;">
                <img src="<?= $lv['flag_svg'] ?>" alt="<?= htmlspecialchars($lv['label']) ?>"
                     width="18" height="18" loading="lazy" style="border-radius:50%">
                <?= strtoupper($lk) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</nav>

<!-- ══════════════════════════════════════════════════════════ MAIN ═════════ -->
<main id="main-content">

    <!-- ── HERO ───────────────────────────────────────────────────────────── -->
    <?php renderHubHero($ctx); ?>

    <!-- ── HUB ALOJAMIENTOS ───────────────────────────────────────────────── -->
    <?php renderHubAlojamientos($ctx); ?>

    <!-- ── HUB EVENTOS ────────────────────────────────────────────────────── -->
    <?php renderHubEventos($ctx); ?>

    <!-- ── SECCIÓN AUTORIDAD SEO ──────────────────────────────────────────── -->
    <?php renderAutoridadSeo($ctx); ?>

</main>

<!-- ══════════════════════════════════════════════════════════ FOOTER ══════ -->
<footer class="hub-footer" role="contentinfo">
    <div class="hub-footer__inner">

        <div class="hub-footer__grid">

            <!-- Columna 1: Marca -->
            <div>
                <div class="hub-footer__brand">
                    <img src="/menu_images/Logo%20transparente.webp"
                         alt="Rutas Rurales"
                         width="36" height="36"
                         loading="lazy">
                    <span>Rutas Rurales</span>
                </div>
                <p class="hub-footer__tagline">
                    <?php if ($lang === 'es'): ?>Plataforma de turismo rural auténtico en España. Alojamientos, eventos y experiencias verificadas.
                    <?php elseif ($lang === 'en'): ?>Authentic rural tourism platform in Spain. Verified accommodation, events and experiences.
                    <?php elseif ($lang === 'fr'): ?>Plateforme de tourisme rural authentique en Espagne. Hébergements, événements et expériences vérifiés.
                    <?php elseif ($lang === 'de'): ?>Plattform für authentischen Landurlaub in Spanien. Geprüfte Unterkünfte, Veranstaltungen und Erlebnisse.
                    <?php else: ?>西班牙正宗乡村旅游平台。经过核实的住宿、活动和体验。<?php endif; ?>
                </p>
                <!-- Selector de idioma en footer -->
                <div class="hub-footer__langs" aria-label="Selector de idioma">
                    <?php foreach (HUB_LANGS as $lk => $lv):
                        $lUrl = $lk === 'es'
                            ? $base_domain . '/'
                            : $base_domain . '/' . $lk . '/';
                    ?>
                    <a href="<?= htmlspecialchars($lUrl) ?>"
                       class="hub-footer-lang<?= ($lk === $lang) ? ' hub-footer-lang--active' : '' ?>"
                       hreflang="<?= $lk ?>"
                       lang="<?= $lk ?>"
                       <?= ($lk === $lang) ? 'aria-current="true"' : '' ?>>
                        <img src="<?= $lv['flag_svg'] ?>"
                             alt="<?= htmlspecialchars($lv['label']) ?>"
                             width="18" height="18" loading="lazy">
                        <?= htmlspecialchars($lv['label']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Columna 2: Verticales -->
            <div>
                <p class="hub-footer__col-title">
                    <?php if ($lang === 'es'): ?>Descubre
                    <?php elseif ($lang === 'en'): ?>Explore
                    <?php elseif ($lang === 'fr'): ?>Découvrez
                    <?php elseif ($lang === 'de'): ?>Entdecken
                    <?php else: ?>探索<?php endif; ?>
                </p>
                <nav class="hub-footer__links" aria-label="Secciones principales">
                    <a href="<?= $base_domain ?>/alojamientos/turismo-rural">
                        <?= htmlspecialchars($t['footer_nav_stays']) ?>
                    </a>
                    <a href="<?= $base_domain ?>/eventos-culturales">
                        <?= htmlspecialchars($t['footer_nav_events']) ?>
                    </a>
                    <a href="<?= $base_domain ?>/lugares-de-interes">
                        <?= htmlspecialchars($t['footer_nav_places']) ?>
                    </a>
                    <a href="<?= $base_domain ?>/actividades-turisticas">
                        <?= htmlspecialchars($t['footer_nav_activ']) ?>
                    </a>
                    <a href="<?= $base_domain ?>/rutas/">
                        <?php if ($lang === 'es'): ?>Rutas temáticas
                        <?php elseif ($lang === 'en'): ?>Themed routes
                        <?php elseif ($lang === 'fr'): ?>Itinéraires thématiques
                        <?php elseif ($lang === 'de'): ?>Themenrouten
                        <?php else: ?>主题路线<?php endif; ?>
                    </a>
                    <a href="<?= $base_domain ?>/rutas.php">
                        🗺️ <?php if ($lang === 'es'): ?>Mapa interactivo
                        <?php elseif ($lang === 'en'): ?>Interactive map
                        <?php elseif ($lang === 'fr'): ?>Carte interactive
                        <?php elseif ($lang === 'de'): ?>Interaktive Karte
                        <?php else: ?>互动地图<?php endif; ?>
                    </a>
                </nav>
            </div>

            <!-- Columna 3: Destinos destacados -->
            <div>
                <p class="hub-footer__col-title">
                    <?php if ($lang === 'es'): ?>Destinos
                    <?php elseif ($lang === 'en'): ?>Destinations
                    <?php elseif ($lang === 'fr'): ?>Destinations
                    <?php elseif ($lang === 'de'): ?>Reiseziele
                    <?php else: ?>目的地<?php endif; ?>
                </p>
                <nav class="hub-footer__links" aria-label="Destinos principales">
                    <?php
                    // Mostramos las 6 provincias más importantes
                    $footerProvs = ['soria', 'zamora', 'leon', 'burgos', 'valladolid', 'salamanca'];
                    foreach ($footerProvs as $pk):
                        if (!isset(HUB_PROVINCIAS[$pk])) continue;
                        $pLabel = HUB_PROVINCIAS[$pk]['label'];
                        $pEmoji = HUB_PROVINCIAS[$pk]['emoji'];
                        $pUrl   = hubUrl('turismo-rural-' . $pk, $lang, 'alojamientos');
                    ?>
                    <a href="<?= htmlspecialchars($pUrl) ?>"
                       title="<?= htmlspecialchars('Turismo rural en ' . $pLabel) ?>">
                        <?= $pEmoji ?> <?= htmlspecialchars($pLabel) ?>
                    </a>
                    <?php endforeach; ?>
                </nav>
            </div>

        </div><!-- /.hub-footer__grid -->

        <!-- Pie: copyright + legal -->
        <div class="hub-footer__bottom">
            <p class="hub-footer__copy">
                <?= htmlspecialchars(str_replace('{YEAR}', date('Y'), $t['footer_copy'])) ?>
            </p>
            <nav class="hub-footer__legal" aria-label="Links legales">
                <a href="<?= $base_domain ?>/aviso-legal.html">
                    <?= htmlspecialchars($t['footer_legal']) ?>
                </a>
                <a href="<?= $base_domain ?>/politica-cookies.html">
                    <?= htmlspecialchars($t['footer_cookies']) ?>
                </a>
                <a href="mailto:olgamarin@rutasrurales.io">
                    <?= htmlspecialchars($t['footer_contact']) ?>
                </a>
                <a href="<?= $base_domain ?>/compromiso-social.html">
                    <?= htmlspecialchars($t['footer_social']) ?>
                </a>
            </nav>
        </div>

    </div><!-- /.hub-footer__inner -->
</footer>

<!-- ══════════════════════════════════════════════════════════ SCRIPTS ══════ -->

<!-- Asistente Antonio (diferido) -->
<script src="/antonio_improved.js" defer></script>

<!-- JS del Hub: navbar hamburguesa + selector de idioma -->
<script>
(function () {
    'use strict';

    // ── Hamburguesa ──────────────────────────────────────────────────────────
    var hamburger   = document.getElementById('hubHamburger');
    var mobileMenu  = document.getElementById('hubMobileMenu');

    if (hamburger && mobileMenu) {
        hamburger.addEventListener('click', function () {
            var isOpen = mobileMenu.classList.toggle('open');
            hamburger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            mobileMenu.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
            // Animar hamburguesa
            var spans = hamburger.querySelectorAll('span');
            if (isOpen) {
                spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                spans[1].style.opacity   = '0';
                spans[2].style.transform = 'rotate(-45deg) translate(5px, -5px)';
            } else {
                spans[0].style.transform = '';
                spans[1].style.opacity   = '';
                spans[2].style.transform = '';
            }
        });
    }

    // ── Selector de idioma desktop ────────────────────────────────────────────
    var langSelector = document.getElementById('langSelector');
    var langBtn      = document.getElementById('langBtn');
    var langDropdown = document.getElementById('langDropdown');

    if (langBtn && langDropdown && langSelector) {
        langBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            var isOpen = langSelector.classList.toggle('open');
            langBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        document.addEventListener('click', function (e) {
            if (!langSelector.contains(e.target)) {
                langSelector.classList.remove('open');
                langBtn.setAttribute('aria-expanded', 'false');
            }
        });

        // Cerrar con Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                langSelector.classList.remove('open');
                langBtn.setAttribute('aria-expanded', 'false');
                langBtn.focus();
            }
        });
    }

    // ── Navbar scroll shadow ──────────────────────────────────────────────────
    var navbar = document.querySelector('.hub-navbar');
    if (navbar) {
        window.addEventListener('scroll', function () {
            navbar.style.boxShadow = window.scrollY > 10
                ? '0 2px 12px rgba(0,0,0,.12)'
                : '0 1px 6px rgba(0,0,0,.06)';
        }, { passive: true });
    }

    // Cargar script de compartir
    var shareScript = document.createElement('script');
    shareScript.src = '/js/home-share.js';
    shareScript.defer = true;
    document.body.appendChild(shareScript);
})();
</script>

</body>
</html>
