<?php
/**
 * /alojamientos/ — Hub Índice de Alojamientos Rurales
 * Multiidioma: /alojamientos/ y /{lang}/alojamientos/
 */

ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);

// I18n Bootstrap
require_once dirname(__DIR__) . '/index/i18n/vertical-hubs.php';
$vh = vh_boot('alojamientos');
$lang = $vh['lang'];
$t = $vh['t'];
$path_prefix = $vh['path_prefix'];
$base_domain = $vh['base_domain'];
$canonical   = $vh['canonical'];
$meta_title  = $t['alo_meta_title'];
$meta_desc   = $t['alo_meta_desc'];
$og_image    = $base_domain . '/menu_images/og-default.jpg';
$home_url    = $vh['home_url'];

// Config del hub
$_HUBCFG = dirname(__DIR__) . '/index/config/hub-config.php';
if (file_exists($_HUBCFG)) {
    require_once $_HUBCFG;
    $has_hub_data = true;
} else {
    $has_hub_data = false;
}

// Intentar stats desde BD
$total_stays  = '+500';
$total_provs  = '+20';
try {
    if (file_exists(dirname(__DIR__) . '/api/config.php')) {
        require_once dirname(__DIR__) . '/api/config.php';
        $pdo = getDBConnection();
        $r = $pdo->query("SELECT COUNT(*) AS c FROM accommodations WHERE is_active=1")->fetch(PDO::FETCH_ASSOC);
        if (!empty($r['c'])) $total_stays = '+' . number_format((int)$r['c'], 0, ',', '.');
        $rp = $pdo->query("SELECT COUNT(DISTINCT province) AS c FROM accommodations WHERE is_active=1")->fetch(PDO::FETCH_ASSOC);
        if (!empty($rp['c'])) $total_provs = '+' . (int)$rp['c'];
    }
} catch (Throwable $e) { /* silencioso — usa valores por defecto */ }

// Filtros inline si no está hub-config
$filtros_inline = [
    'casas-rurales'        => ['icon'=>'🏡', 'label'=>'Casas rurales'],
    'con-chimenea'         => ['icon'=>'🔥', 'label'=>'Con chimenea'],
    'con-piscina'          => ['icon'=>'🏊', 'label'=>'Con piscina'],
    'con-mascotas'         => ['icon'=>'🐾', 'label'=>'Para mascotas'],
    'romantico'            => ['icon'=>'💑', 'label'=>'Románticos'],
    'con-jacuzzi'          => ['icon'=>'♨️', 'label'=>'Con jacuzzi'],
    'grandes-grupos'       => ['icon'=>'👥', 'label'=>'Grupos grandes'],
    'con-cocina'           => ['icon'=>'🍳', 'label'=>'Con cocina equipada'],
    'baratos'              => ['icon'=>'💰', 'label'=>'Económicos'],
    'para-ninos'           => ['icon'=>'👨‍👩‍👧', 'label'=>'Para niños'],
];

$provincias_inline = [
    'soria'      => ['emoji'=>'🌲','label'=>'Soria',      'region'=>'Castilla y León'],
    'zamora'     => ['emoji'=>'🦌','label'=>'Zamora',     'region'=>'Castilla y León'],
    'leon'       => ['emoji'=>'🏔️','label'=>'León',      'region'=>'Castilla y León'],
    'burgos'     => ['emoji'=>'⚔️','label'=>'Burgos',    'region'=>'Castilla y León'],
    'valladolid' => ['emoji'=>'🍇','label'=>'Valladolid','region'=>'Castilla y León'],
    'salamanca'  => ['emoji'=>'🏛️','label'=>'Salamanca','region'=>'Castilla y León'],
    'palencia'   => ['emoji'=>'🌾','label'=>'Palencia',  'region'=>'Castilla y León'],
    'segovia'    => ['emoji'=>'🏰','label'=>'Segovia',   'region'=>'Castilla y León'],
    'avila'      => ['emoji'=>'🧱','label'=>'Ávila',     'region'=>'Castilla y León'],
    'guadalajara'=> ['emoji'=>'🌳','label'=>'Guadalajara','region'=>'Castilla-La Mancha'],
    'cuenca'     => ['emoji'=>'🪨','label'=>'Cuenca',    'region'=>'Castilla-La Mancha'],
    'ourense'    => ['emoji'=>'♨️','label'=>'Ourense',  'region'=>'Galicia'],
    'asturias'   => ['emoji'=>'🦅','label'=>'Asturias',  'region'=>'Asturias'],
    'cantabria'  => ['emoji'=>'🏖️','label'=>'Cantabria','region'=>'Cantabria'],
    'cordoba'    => ['emoji'=>'🕌','label'=>'Córdoba',   'region'=>'Andalucía'],
    'granada'    => ['emoji'=>'🏰','label'=>'Granada',   'region'=>'Andalucía'],
    'lugo'       => ['emoji'=>'🏛️','label'=>'Lugo',     'region'=>'Galicia'],
    'pontevedra' => ['emoji'=>'🌊','label'=>'Pontevedra','region'=>'Galicia'],
    'toledo'     => ['emoji'=>'🏰','label'=>'Toledo',    'region'=>'Castilla-La Mancha'],
    'valencia'   => ['emoji'=>'🍊','label'=>'Valencia',  'region'=>'C. Valenciana'],
    'navarra'    => ['emoji'=>'🏔️','label'=>'Navarra',  'region'=>'Navarra'],
];

// Preparar filtros con labels traducidos
$filtros = [];
if ($has_hub_data) {
    foreach (HUB_FILTROS_ALO as $k => $v) {
        $filtros[$k] = ['icon' => $v['icon'], 'label' => vh_filter_label($v, $lang)];
    }
} else {
    foreach ($filtros_inline as $k => $v) {
        $filtros[$k] = ['icon'=>$v['icon'],'label'=>$v['label']];
    }
}
$provincias = $has_hub_data ? HUB_PROVINCIAS : $provincias_inline;
$combis     = $has_hub_data ? HUB_COMBIS_ALO : [];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>" dir="<?= htmlspecialchars($vh['dir']) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- SEO primario -->
<title><?= htmlspecialchars($meta_title) ?></title>
<meta name="description" content="<?= htmlspecialchars($meta_desc) ?>">
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
<link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">

<?= vh_render_hreflang($vh['hreflang']) ?>

<!-- Open Graph -->
<meta property="og:type"        content="website">
<meta property="og:title"       content="<?= htmlspecialchars($meta_title) ?>">
<meta property="og:description" content="<?= htmlspecialchars($meta_desc) ?>">
<meta property="og:image"       content="<?= htmlspecialchars($og_image) ?>">
<meta property="og:image:width"  content="1200">
<meta property="og:image:height" content="630">
<meta property="og:url"         content="<?= htmlspecialchars($canonical) ?>">
<meta property="og:site_name"   content="Rutas Rurales">
<meta property="og:locale"      content="<?= htmlspecialchars($vh['locale']) ?>">

<!-- Twitter Card -->
<meta name="twitter:card"        content="summary_large_image">
<meta name="twitter:title"       content="<?= htmlspecialchars($meta_title) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($meta_desc) ?>">
<meta name="twitter:image"       content="<?= htmlspecialchars($og_image) ?>">

<!-- Favicon / PWA -->
<link rel="icon"             href="/menu_images/Favicon.png" type="image/png">
<link rel="apple-touch-icon" href="/menu_images/Favicon.png">
<link rel="manifest"         href="/manifest.json">
<meta name="theme-color"     content="#2F5233">

<!-- Preconnect -->
<link rel="preconnect" href="https://unpkg.com" crossorigin>

<!-- Fuentes locales -->
<style>
@font-face{font-family:'Montserrat';font-style:normal;font-weight:400;font-display:swap;
  src:local('Montserrat Regular'),url('/fonts/montserrat-v31-latin-regular.woff2') format('woff2')}
@font-face{font-family:'Montserrat';font-style:normal;font-weight:600;font-display:swap;
  src:local('Montserrat SemiBold'),url('/fonts/montserrat-v31-latin-600.woff2') format('woff2')}
@font-face{font-family:'Montserrat';font-style:normal;font-weight:800;font-display:swap;
  src:local('Montserrat ExtraBold'),url('/fonts/montserrat-v31-latin-800.woff2') format('woff2')}
</style>

<!-- CSS Crítico inline — sin modificar ningún archivo .css existente -->
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --primary:#2F5233;--primary-dark:#1a3d1e;--primary-light:#3d6b42;
  --accent:#81C784;--accent-warm:#F9A825;
  --white:#fff;--bg:#f8f9fa;--bg-alt:#f0f4f1;
  --text:#2d3436;--text-light:#636e72;--border:#e8eaed;
  --radius:14px;--radius-sm:8px;
  --shadow:0 2px 12px rgba(0,0,0,.07);
  --max-w:1200px;--tr:.18s ease}
html{scroll-behavior:smooth}
body{font-family:'Montserrat','Segoe UI',system-ui,sans-serif;background:var(--bg);color:var(--text);line-height:1.65;overflow-x:hidden;-webkit-font-smoothing:antialiased}
img{display:block;max-width:100%;height:auto}
a{color:var(--primary);text-decoration:none}
ul{list-style:none;padding:0;margin:0}

/* ── Navbar ────────────────────────────────────────── */
.alo-nav{position:sticky;top:0;z-index:900;background:var(--white);border-bottom:1px solid var(--border);height:60px;display:flex;align-items:center;padding:0 20px;gap:14px;box-shadow:0 1px 6px rgba(0,0,0,.06)}
.alo-nav__logo{display:flex;align-items:center;gap:10px;font-weight:800;color:var(--primary);font-size:1rem;flex-shrink:0}
.alo-nav__logo img{width:38px;height:38px;border-radius:50%;object-fit:cover}
.alo-nav__links{display:flex;align-items:center;gap:4px;margin-left:auto;font-size:.8rem;flex-wrap:nowrap}
.alo-nav__links a{color:var(--text);font-weight:600;padding:6px 10px;border-radius:var(--radius-sm);white-space:nowrap;transition:background var(--tr)}
.alo-nav__links a:hover,.alo-nav__links a[aria-current]{background:var(--bg-alt);color:var(--primary)}
.alo-nav__cta{background:var(--primary)!important;color:var(--white)!important;padding:7px 14px!important;border-radius:var(--radius-sm)!important;font-weight:700!important}

/* ── Hero ──────────────────────────────────────────── */
.alo-hero{position:relative;min-height:380px;display:flex;align-items:flex-end;overflow:hidden}
.alo-hero__bg{position:absolute;inset:0;z-index:0}
.alo-hero__bg img{width:100%;height:100%;object-fit:cover;object-position:center 40%}
.alo-hero__overlay{position:absolute;inset:0;background:linear-gradient(160deg,rgba(26,61,30,.9) 0%,rgba(47,82,51,.72) 55%,rgba(26,61,30,.5) 100%)}
.alo-hero__inner{position:relative;z-index:1;padding:52px 20px 48px;width:100%;max-width:var(--max-w);margin:0 auto}
.alo-hero h1{font-size:clamp(1.7rem,4vw,2.7rem);font-weight:800;color:var(--white);line-height:1.1;margin-bottom:12px;text-shadow:0 2px 8px rgba(0,0,0,.25);max-width:680px}
.alo-hero__sub{font-size:clamp(.88rem,1.8vw,1.05rem);color:rgba(255,255,255,.88);margin-bottom:24px;max-width:560px;font-weight:500;line-height:1.55}
.alo-hero__stats{display:flex;flex-wrap:wrap;gap:24px}
.alo-stat__val{display:block;font-size:1.55rem;font-weight:800;color:var(--accent);line-height:1}
.alo-stat__lbl{font-size:.72rem;color:rgba(255,255,255,.78);font-weight:500}
.alo-breadcrumb{margin-bottom:14px}
.alo-breadcrumb ol{display:flex;gap:4px;flex-wrap:wrap;font-size:.75rem;color:rgba(255,255,255,.7);list-style:none;padding:0}
.alo-breadcrumb a{color:rgba(255,255,255,.7)}
.alo-breadcrumb li+li::before{content:'›';margin-right:4px;color:rgba(255,255,255,.5)}

/* ── Container / Sections ──────────────────────────── */
.alo-wrap{max-width:var(--max-w);margin:0 auto;padding:0 20px}
.alo-section{padding:56px 0}
.alo-section--alt{background:var(--bg-alt)}
.alo-section__hdr{margin-bottom:24px}
.alo-section__h2{font-size:clamp(1.25rem,2.5vw,1.65rem);font-weight:800;color:var(--primary);display:flex;align-items:center;gap:10px;margin-bottom:6px}
.alo-section__intro{font-size:.92rem;color:var(--text-light);max-width:620px;line-height:1.6}

/* ── Mapa ──────────────────────────────────────────── */
.alo-map-box{background:var(--white);border:1.5px solid var(--border);border-radius:var(--radius);overflow:hidden;box-shadow:var(--shadow)}
.alo-map-hdr{padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px}
.alo-map-hdr h3{font-size:.95rem;font-weight:700;color:var(--primary)}
.alo-map-hdr small{font-size:.78rem;color:var(--text-light);margin-left:auto}
.alo-map-placeholder{height:420px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;background:linear-gradient(135deg,#f0f4f1 0%,#deeadf 100%);cursor:default}
.alo-map-placeholder .mp-icon{font-size:2.8rem}
.alo-map-placeholder p{font-size:.95rem;font-weight:700;color:var(--primary)}
.alo-map-placeholder small{font-size:.78rem;color:var(--text-light)}
.alo-map-spinner{height:420px;display:none;flex-direction:column;align-items:center;justify-content:center;gap:10px;background:#f8f9fa}
.alo-map-spinner .spinner{width:34px;height:34px;border:4px solid var(--border);border-top-color:var(--primary);border-radius:50%;animation:mapspin .75s linear infinite}
@keyframes mapspin{to{transform:rotate(360deg)}}
#mapAlo{height:420px;display:none}
.alo-map-cta{padding:12px 20px;border-top:1px solid var(--border);background:var(--bg-alt);display:flex;align-items:center;justify-content:flex-end;gap:12px;font-size:.82rem}
.alo-map-cta a{color:var(--primary);font-weight:700}

/* ── Filtros (chip grid) ───────────────────────────── */
.alo-chips{display:flex;flex-wrap:wrap;gap:10px}
.alo-chip{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:var(--white);border:1.5px solid var(--border);border-radius:24px;font-size:.83rem;font-weight:600;color:var(--text);transition:var(--tr);box-shadow:var(--shadow)}
.alo-chip:hover{background:var(--primary);color:var(--white);border-color:var(--primary);transform:translateY(-1px)}

/* ── Provincias ────────────────────────────────────── */
.alo-provs{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px}
.alo-prov{display:flex;flex-direction:column;align-items:center;gap:4px;padding:14px 10px;background:var(--white);border:1.5px solid var(--border);border-radius:var(--radius);text-align:center;transition:var(--tr);box-shadow:var(--shadow)}
.alo-prov:hover{background:var(--primary);color:var(--white);border-color:var(--primary);transform:translateY(-2px)}
.alo-prov__em{font-size:1.5rem}
.alo-prov__nm{font-size:.82rem;font-weight:700}
.alo-prov__rg{font-size:.68rem;opacity:.65}

/* ── Accordion ─────────────────────────────────────── */
.alo-accord{background:var(--white);border:1.5px solid var(--border);border-radius:var(--radius);overflow:hidden;box-shadow:var(--shadow);margin-top:24px}
.alo-accord summary{padding:14px 18px;cursor:pointer;font-weight:700;font-size:.9rem;color:var(--primary);display:flex;align-items:center;gap:8px;list-style:none;user-select:none}
.alo-accord summary::-webkit-details-marker{display:none}
.alo-accord summary .ac-chevron{margin-left:auto;transition:transform .2s;font-style:normal}
.alo-accord[open] summary .ac-chevron{transform:rotate(90deg)}
.alo-accord__body{padding:16px 18px;border-top:1px solid var(--border)}
.alo-links{display:flex;flex-wrap:wrap;gap:8px}
.alo-lnk{font-size:.8rem;color:var(--primary);padding:4px 10px;background:var(--bg-alt);border-radius:20px;font-weight:600;transition:var(--tr)}
.alo-lnk:hover{background:var(--primary);color:var(--white)}

/* ── CTA final ─────────────────────────────────────── */
.alo-cta{background:var(--primary-dark);color:var(--white);padding:52px 20px;text-align:center}
.alo-cta h2{font-size:clamp(1.25rem,2.5vw,1.7rem);font-weight:800;margin-bottom:10px}
.alo-cta p{font-size:.92rem;opacity:.82;max-width:480px;margin:0 auto 22px;line-height:1.6}
.alo-btn{display:inline-flex;align-items:center;gap:8px;padding:12px 26px;border-radius:25px;font-weight:700;font-size:.88rem;transition:var(--tr)}
.alo-btn--w{background:var(--white);color:var(--primary)}
.alo-btn--w:hover{background:var(--accent)}
.alo-btn--o{background:transparent;border:2px solid rgba(255,255,255,.5);color:var(--white);margin-left:10px}
.alo-btn--o:hover{background:rgba(255,255,255,.1)}

/* ── Footer ────────────────────────────────────────── */
.alo-footer{background:var(--primary);color:rgba(255,255,255,.7);padding:24px 20px;font-size:.8rem}
.alo-footer__inner{max-width:var(--max-w);margin:0 auto;display:flex;flex-wrap:wrap;gap:14px;justify-content:space-between;align-items:center}
.alo-footer a{color:rgba(255,255,255,.7)}
.alo-footer a:hover{color:#fff}
.alo-footer__nav{display:flex;flex-wrap:wrap;gap:10px}

/* ── Responsive ────────────────────────────────────── */
@media(max-width:800px){
  .alo-nav__links{display:none}
  .alo-provs{grid-template-columns:repeat(auto-fill,minmax(120px,1fr))}
  .alo-hero__stats{gap:16px}
}
@media(max-width:480px){
  .alo-hero{min-height:320px}
  .alo-hero__inner{padding:40px 16px 36px}
  .alo-section{padding:40px 0}
}
</style>

<!-- Schema.org CollectionPage -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "CollectionPage",
  "name": "Alojamientos Rurales en España",
  "description": "<?= htmlspecialchars($meta_desc) ?>",
  "url": "<?= htmlspecialchars($canonical) ?>",
  "inLanguage": "es",
  "breadcrumb": {
    "@type": "BreadcrumbList",
    "itemListElement": [
      {"@type":"ListItem","position":1,"name":"Inicio","item":"https://rutasrurales.io/"},
      {"@type":"ListItem","position":2,"name":"Alojamientos Rurales","item":"<?= htmlspecialchars($canonical) ?>"}
    ]
  },
  "publisher": {
    "@type": "Organization",
    "name": "Rutas Rurales",
    "url": "https://rutasrurales.io"
  }
}
</script>

<!-- GTM diferido -->
<script>
(function(){var l=function(){if(window._gtm)return;window._gtm=1;(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-MBP57VQM');};['click','scroll','keydown','touchstart'].forEach(function(e){window.addEventListener(e,function(){setTimeout(l,1e3)},{once:true,passive:true});});setTimeout(l,8000);})();
</script>
</head>
<body>

<!-- GTM noscript -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MBP57VQM" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

<!-- Skip link -->
<a href="#main-content" style="position:absolute;left:-9999px;top:4px;z-index:9999;background:var(--primary);color:#fff;padding:6px 14px;border-radius:4px;font-size:.85rem;font-weight:700" onfocus="this.style.left='4px'" onblur="this.style.left='-9999px'"><?= htmlspecialchars($t['skip_link']) ?></a>

<!-- ══════════════════════ NAVBAR ══════════════════════ -->
<header class="alo-nav" role="banner">
  <a href="<?= htmlspecialchars($home_url) ?>" class="alo-nav__logo" aria-label="Rutas Rurales - <?= htmlspecialchars($t['nav_home']) ?>">
    <img src="/menu_images/Logo%20transparente.webp" alt="Rutas Rurales" width="38" height="38" loading="eager">
    <span>Rutas Rurales</span>
  </a>
  <nav class="alo-nav__links" aria-label="<?= htmlspecialchars($t['nav_main']) ?>">
    <a href="<?= htmlspecialchars($path_prefix) ?>/alojamientos/" aria-current="page">🏡 <?= htmlspecialchars($t['nav_stays']) ?></a>
    <a href="<?= htmlspecialchars($path_prefix) ?>/eventos/">🎭 <?= htmlspecialchars($t['nav_events']) ?></a>
    <a href="<?= htmlspecialchars($path_prefix) ?>/lugares/">📍 <?= htmlspecialchars($t['nav_places']) ?></a>
    <a href="<?= htmlspecialchars($path_prefix) ?>/actividades/">🥾 <?= htmlspecialchars($t['nav_activities']) ?></a>
    <a href="/rutas.php">🗺️ <?= htmlspecialchars($t['nav_map']) ?></a>
    <a href="/login.html" class="alo-nav__cta" rel="nofollow"><?= htmlspecialchars($t['nav_login']) ?></a>
  </nav>
</header>

<!-- ══════════════════════ MAIN ════════════════════════ -->
<main id="main-content">

<!-- ── HERO ──────────────────────────────────────────── -->
<section class="alo-hero" id="inicio" aria-labelledby="alo-h1">
  <div class="alo-hero__bg" aria-hidden="true">
    <img src="/img/eventos-landing-hero/turismo_rural3.webp"
         alt="Casas rurales y alojamientos en España"
         width="1200" height="500"
         loading="eager" fetchpriority="high">
    <div class="alo-hero__overlay"></div>
  </div>
  <div class="alo-hero__inner">
    <nav class="alo-breadcrumb" aria-label="<?= htmlspecialchars($t['bc_nav']) ?>">
      <ol>
        <li><a href="<?= htmlspecialchars($home_url) ?>"><?= htmlspecialchars($t['nav_home']) ?></a></li>
        <li><span aria-current="page" style="color:#fff"><?= htmlspecialchars($t['alo_bc']) ?></span></li>
      </ol>
    </nav>
    <h1 id="alo-h1"><?= htmlspecialchars($t['alo_h1']) ?></h1>
    <p class="alo-hero__sub"><?= htmlspecialchars($t['alo_sub']) ?></p>
    <div class="alo-hero__stats" aria-label="<?= htmlspecialchars($t['stats']) ?>">
      <div><span class="alo-stat__val"><?= htmlspecialchars($total_stays) ?></span><span class="alo-stat__lbl"><?= htmlspecialchars($t['alo_stat_stays']) ?></span></div>
      <div><span class="alo-stat__val"><?= htmlspecialchars($total_provs) ?></span><span class="alo-stat__lbl"><?= htmlspecialchars($t['alo_stat_provs']) ?></span></div>
      <div><span class="alo-stat__val">100%</span><span class="alo-stat__lbl"><?= htmlspecialchars($t['alo_stat_verified']) ?></span></div>
    </div>
  </div>
</section>

<!-- ── MAPA CON LAZY LOADING ─────────────────────────── -->
<section class="alo-section" aria-labelledby="map-h2">
  <div class="alo-wrap">
    <div class="alo-section__hdr">
      <h2 class="alo-section__h2" id="map-h2">🗺️ <?= htmlspecialchars($t['map_cta']) ?></h2>
      <p class="alo-section__intro"><?= htmlspecialchars($t['alo_map_intro']) ?></p>
    </div>
    <div class="alo-map-box">
      <div class="alo-map-hdr">
        <h3><?= htmlspecialchars($t['alo_map_dist_h3']) ?></h3>
        <small><?= htmlspecialchars($t['alo_map_lazy_hint']) ?></small>
      </div>

      <!-- Placeholder visible antes de cargar Leaflet -->
      <div class="alo-map-placeholder" id="mapPlaceholder" role="img" aria-label="<?= htmlspecialchars($t['alo_map_preview_aria']) ?>">
        <span class="mp-icon" aria-hidden="true">🗺️</span>
        <p><?= htmlspecialchars($t['alo_map_placeholder_p']) ?></p>
        <small><?= htmlspecialchars($t['alo_map_placeholder_small']) ?></small>
      </div>

      <!-- Spinner mientras carga Leaflet JS/CSS -->
      <div class="alo-map-spinner" id="mapSpinner" role="status" aria-live="polite" aria-label="<?= htmlspecialchars($t['alo_map_loading_aria']) ?>">
        <div class="spinner" aria-hidden="true"></div>
        <span><?= htmlspecialchars($t['alo_map_loading']) ?></span>
      </div>

      <!-- Contenedor real del mapa Leaflet (oculto hasta que cargue) -->
      <div id="mapAlo" aria-label="<?= htmlspecialchars($t['alo_map_aria']) ?>"></div>

      <div class="alo-map-cta">
        <a href="/rutas.php?alojamientos=1&lugares=0&actividades=0&eventos=0" aria-label="<?= htmlspecialchars($t['alo_map_full_aria']) ?>"><?= htmlspecialchars($t['map_full']) ?> →</a>
      </div>
    </div>
  </div>
</section>

<!-- ── FILTROS / CARACTERÍSTICAS ──────────────────────── -->
<section class="alo-section alo-section--alt" aria-labelledby="filtros-h2">
  <div class="alo-wrap">
    <div class="alo-section__hdr">
      <h2 class="alo-section__h2" id="filtros-h2">✨ <?= htmlspecialchars($t['alo_by_feat']) ?></h2>
      <p class="alo-section__intro"><?= htmlspecialchars($t['alo_by_feat_intro']) ?></p>
    </div>
    <ul class="alo-chips" role="list" aria-label="<?= htmlspecialchars($t['alo_feat_aria']) ?>">
      <?php foreach ($filtros as $fk => $fd): ?>
      <li>
        <a href="<?= htmlspecialchars(vh_item_url('alojamientos', $fk, $lang)) ?>"
           class="alo-chip"
           title="<?= htmlspecialchars($fd['label']) ?> en España">
          <span aria-hidden="true"><?= $fd['icon'] ?></span>
          <?= htmlspecialchars($fd['label']) ?>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<!-- ── PROVINCIAS ─────────────────────────────────────── -->
<section class="alo-section" aria-labelledby="provs-h2">
  <div class="alo-wrap">
    <div class="alo-section__hdr">
      <h2 class="alo-section__h2" id="provs-h2">📍 <?= htmlspecialchars($t['alo_by_prov']) ?></h2>
      <p class="alo-section__intro"><?= htmlspecialchars($t['alo_by_prov_intro']) ?></p>
    </div>
    <ul class="alo-provs" role="list" aria-label="<?= htmlspecialchars($t['alo_prov_aria']) ?>">
      <?php foreach ($provincias as $pk => $pd): ?>
      <li>
        <a href="/alojamientos/turismo-rural-<?= htmlspecialchars($pk) ?>"
           class="alo-prov"
           title="<?= htmlspecialchars($t['alo_stays_in_prov']) ?> <?= htmlspecialchars($pd['label']) ?>">
          <span class="alo-prov__em" aria-hidden="true"><?= $pd['emoji'] ?></span>
          <span class="alo-prov__nm"><?= htmlspecialchars($pd['label']) ?></span>
          <span class="alo-prov__rg"><?= htmlspecialchars($pd['region']) ?></span>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>

    <!-- Combinaciones más buscadas -->
    <?php if (!empty($combis)): ?>
    <details class="alo-accord">
      <summary>
        <span aria-hidden="true">🔥</span>
        <?= htmlspecialchars($t['alo_combis']) ?>
        <span class="ac-chevron" aria-hidden="true">›</span>
      </summary>
      <div class="alo-accord__body">
        <ul class="alo-links" role="list" aria-label="<?= htmlspecialchars($t['alo_combi_aria']) ?>">
          <?php foreach ($combis as $combi):
            $slug_c = implode('-', $combi);
            $parts_lbl = [];
            foreach ($combi as $part) {
              if (isset(HUB_FILTROS_ALO[$part])) $parts_lbl[] = HUB_FILTROS_ALO[$part]['es'];
              elseif (isset(HUB_PROVINCIAS[$part])) $parts_lbl[] = HUB_PROVINCIAS[$part]['label'];
              else $parts_lbl[] = ucfirst(str_replace('-',' ',$part));
            }
          ?>
          <li>
            <a href="/alojamientos/<?= htmlspecialchars($slug_c) ?>" class="alo-lnk">
              <?= htmlspecialchars(implode(' · ', $parts_lbl)) ?>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </details>
    <?php endif; ?>
  </div>
</section>

<!-- ── CTA FINAL ──────────────────────────────────────── -->
<section class="alo-cta" aria-label="<?= htmlspecialchars($t['alo_cta_aria']) ?>">
  <h2><?= htmlspecialchars($t['alo_cta_h2']) ?></h2>
  <p><?= htmlspecialchars($t['alo_cta_p']) ?></p>
  <a href="/agregar-alojamiento.html" class="alo-btn alo-btn--w"><?= htmlspecialchars($t['alo_cta_btn']) ?></a>
  <a href="/rutas.php" class="alo-btn alo-btn--o">🗺️ <?= htmlspecialchars($t['map_full']) ?></a>
</section>

</main>

<!-- ══════════════════════ FOOTER ══════════════════════ -->
<footer class="alo-footer" role="contentinfo">
  <div class="alo-footer__inner">
    <nav class="alo-footer__nav" aria-label="<?= htmlspecialchars($t['nav_footer']) ?>">
      <a href="<?= htmlspecialchars($home_url) ?>"><?= htmlspecialchars($t['nav_home']) ?></a>
      <a href="<?= htmlspecialchars($path_prefix) ?>/alojamientos/" aria-current="page"><?= htmlspecialchars($t['nav_stays']) ?></a>
      <a href="<?= htmlspecialchars($path_prefix) ?>/eventos/"><?= htmlspecialchars($t['nav_events']) ?></a>
      <a href="<?= htmlspecialchars($path_prefix) ?>/lugares/"><?= htmlspecialchars($t['nav_places']) ?></a>
      <a href="<?= htmlspecialchars($path_prefix) ?>/actividades/"><?= htmlspecialchars($t['nav_activities']) ?></a>
      <a href="/aviso-legal.html"><?= htmlspecialchars($t['legal']) ?></a>
    </nav>
    <p>© <?= date('Y') ?> <strong style="color:#fff">rutasrurales.io</strong></p>
  </div>
</footer>

<!-- ══════════════ LAZY LOADING DEL MAPA ══════════════ -->
<!-- Strings i18n para el mapa (JS) -->
<script>
var ALO_MAP = {
  errorMsg:  <?= json_encode($t['alo_map_error']) ?>,
  openFull:  <?= json_encode($t['alo_map_open_full']) ?>,
  seeStays:  <?= json_encode($t['see_stays']) ?>,
  staysIn:   <?= json_encode($t['stays_in']) ?>
};
</script>
<!--
  IntersectionObserver:
  - rootMargin:300px → empieza a cargar cuando el mapa está a 300px del viewport
  - Solo inyecta Leaflet CSS + JS bajo demanda (no bloquea la carga inicial)
  - Fallback graceful si el navegador no soporta IntersectionObserver
-->
<script>
(function () {
  'use strict';

  var placeholder = document.getElementById('mapPlaceholder');
  var spinner     = document.getElementById('mapSpinner');
  var mapEl       = document.getElementById('mapAlo');

  if (!mapEl) return;

  var mapLoaded = false;

  // ── Función principal de carga ──────────────────────
  function loadLeafletMap() {
    if (mapLoaded) return;
    mapLoaded = true;

    // Ocultar placeholder → mostrar spinner
    if (placeholder) placeholder.style.display = 'none';
    if (spinner)     spinner.style.display = 'flex';

    // 1. Inyectar Leaflet CSS
    var css = document.createElement('link');
    css.rel = 'stylesheet';
    css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    document.head.appendChild(css);

    // 2. Inyectar Leaflet JS → al cargar, inicializar mapa
    var script = document.createElement('script');
    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    script.onload = initMap;
    script.onerror = function () {
      if (spinner)     spinner.style.display = 'none';
      if (placeholder) {
        placeholder.innerHTML =
          '<span class="mp-icon">⚠️</span>' +
          '<p>' + ALO_MAP.errorMsg + '</p>' +
          '<small><a href="/rutas.php?alojamientos=1&lugares=0&actividades=0&eventos=0">' + ALO_MAP.openFull + '</a></small>';
        placeholder.style.display = 'flex';
      }
    };
    document.body.appendChild(script);
  }

  // ── IntersectionObserver (navegadores modernos) ─────
  if ('IntersectionObserver' in window) {
    var observer = new IntersectionObserver(function (entries) {
      if (entries[0].isIntersecting) {
        observer.disconnect();
        loadLeafletMap();
      }
    }, { rootMargin: '300px' });
    observer.observe(mapEl);
  } else {
    // Fallback: cargar el mapa al evento load (IE11 y similares)
    window.addEventListener('load', loadLeafletMap, { once: true });
  }

  // ── Inicializar el mapa Leaflet ─────────────────────
  function initMap() {
    if (spinner) spinner.style.display = 'none';
    mapEl.style.display = 'block';

    var map = L.map('mapAlo', {
      scrollWheelZoom: false,
      attributionControl: true
    }).setView([40.4, -3.7], 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      maxZoom: 14,
      tileSize: 256
    }).addTo(map);

    // Icono personalizado (marca de alojamiento)
    var icon = L.divIcon({
      html: '<div style="background:#2F5233;width:13px;height:13px;border-radius:50%;border:2.5px solid #fff;box-shadow:0 1px 5px rgba(0,0,0,.45)"></div>',
      className: '',
      iconSize: [13, 13],
      iconAnchor: [6, 6],
      popupAnchor: [0, -10]
    });

    // Datos de provincias (hardcoded — sin llamadas a BD en el hub)
    var provincias = [
      { name: 'Soria',        lat: 41.763,  lng: -2.464,   slug: 'turismo-rural-soria'       },
      { name: 'Zamora',       lat: 41.504,  lng: -5.745,   slug: 'turismo-rural-zamora'      },
      { name: 'León',         lat: 42.598,  lng: -5.567,   slug: 'turismo-rural-leon'        },
      { name: 'Burgos',       lat: 42.343,  lng: -3.697,   slug: 'turismo-rural-burgos'      },
      { name: 'Valladolid',   lat: 41.652,  lng: -4.724,   slug: 'turismo-rural-valladolid'  },
      { name: 'Salamanca',    lat: 40.970,  lng: -5.663,   slug: 'turismo-rural-salamanca'   },
      { name: 'Palencia',     lat: 42.010,  lng: -4.527,   slug: 'turismo-rural-palencia'    },
      { name: 'Segovia',      lat: 40.948,  lng: -4.118,   slug: 'turismo-rural-segovia'     },
      { name: '\u00c1vila',   lat: 40.656,  lng: -4.701,   slug: 'turismo-rural-avila'       },
      { name: 'Guadalajara',  lat: 40.633,  lng: -3.163,   slug: 'turismo-rural-guadalajara' },
      { name: 'Cuenca',       lat: 40.070,  lng: -2.134,   slug: 'turismo-rural-cuenca'      },
      { name: 'Ourense',      lat: 42.336,  lng: -7.864,   slug: 'turismo-rural-ourense'     },
      { name: 'A Coru\u00f1a',lat: 43.371,  lng: -8.396,   slug: 'turismo-rural-a-coruna'   },
      { name: 'Lugo',         lat: 43.012,  lng: -7.556,   slug: 'turismo-rural-lugo'        },
      { name: 'Pontevedra',   lat: 42.433,  lng: -8.648,   slug: 'turismo-rural-pontevedra'  },
      { name: 'Asturias',     lat: 43.362,  lng: -5.849,   slug: 'turismo-rural-asturias'    },
      { name: 'Cantabria',    lat: 43.183,  lng: -3.988,   slug: 'turismo-rural-cantabria'   },
      { name: 'C\u00f3rdoba', lat: 37.888,  lng: -4.779,   slug: 'turismo-rural-cordoba'     },
      { name: 'Granada',      lat: 37.177,  lng: -3.599,   slug: 'turismo-rural-granada'     },
      { name: 'Toledo',       lat: 39.857,  lng: -4.024,   slug: 'turismo-rural-toledo'      },
      { name: 'Valencia',     lat: 39.470,  lng: -0.376,   slug: 'turismo-rural-valencia'    },
      { name: 'Barcelona',    lat: 41.389,  lng:  2.159,   slug: 'turismo-rural-barcelona'   },
      { name: 'Navarra',      lat: 42.695,  lng: -1.676,   slug: 'turismo-rural-navarra'     }
    ];

    provincias.forEach(function (p) {
      var popup =
        '<div style="font-family:Montserrat,sans-serif;min-width:130px">' +
        '<strong style="color:#2F5233;font-size:.9rem">' + p.name + '</strong><br>' +
        '<a href="/alojamientos/' + p.slug + '" ' +
           'style="color:#2F5233;font-weight:700;font-size:.8rem;display:inline-block;margin-top:6px">' +
           ALO_MAP.seeStays + '</a>' +
        '</div>';
      L.marker([p.lat, p.lng], { icon: icon, title: ALO_MAP.staysIn + ' ' + p.name })
        .bindPopup(popup)
        .addTo(map);
    });
  }

})();
</script>

<!-- PWA Service Worker -->
<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function () {
    navigator.serviceWorker.register('/sw.js').catch(function(){});
  });
}
</script>

</body>
</html>
