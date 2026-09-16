<?php
/**
 * /fr/actividades/ — Hub Índice de Actividades Turísticas (Francés)
 */
ini_set('display_errors', 0); error_reporting(E_ERROR | E_PARSE);
require_once dirname(__DIR__, 2) . '/index/i18n/vertical-hubs.php';
$vh = vh_boot('actividades');
$lang = $vh['lang'];
$t = $vh['t'];
$path_prefix = $vh['path_prefix'];
$base_domain = $vh['base_domain'];
$canonical   = $vh['canonical'];
$meta_title  = $t['act_meta_title'];
$meta_desc   = $t['act_meta_desc'];
$og_image    = $base_domain . '/menu_images/og-default.jpg';
$home_url    = $vh['home_url'];

$actividades_base = [
  'senderismo'     => ['icon'=>'🥾'],
  'rutas-en-bici'  => ['icon'=>'🚴'],
  'kayak-canoa'    => ['icon'=>'🛶'],
  'birdwatching'   => ['icon'=>'🦅'],
  'ecoturismo'     => ['icon'=>'🌿'],
  'escalada'       => ['icon'=>'🧗'],
  'rutas-caballo'  => ['icon'=>'🐴'],
  'fotografía'     => ['icon'=>'📷'],
  'astronomia'     => ['icon'=>'🔭'],
  'micologia'      => ['icon'=>'🍄'],
  'setas-guiada'   => ['icon'=>'🌲'],
  'nieve-invierno' => ['icon'=>'❄️'],
];
$actividades = [];
foreach ($actividades_base as $ak => $av) {
  $lab = $t['act_labels'][$ak] ?? ['label'=>$ak,'desc'=>''];
  $actividades[$ak] = ['icon'=>$av['icon'], 'label'=>$lab['label'], 'desc'=>$lab['desc']];
}

$provincias = [
  ['slug'=>'soria',      'emoji'=>'🌲','label'=>'Soria'],
  ['slug'=>'zamora',     'emoji'=>'🦌','label'=>'Zamora'],
  ['slug'=>'leon',       'emoji'=>'🏔️','label'=>'León'],
  ['slug'=>'burgos',     'emoji'=>'⚔️','label'=>'Burgos'],
  ['slug'=>'salamanca',  'emoji'=>'🏛️','label'=>'Salamanca'],
  ['slug'=>'segovia',    'emoji'=>'🏰','label'=>'Segovia'],
  ['slug'=>'avila',      'emoji'=>'🧱','label'=>'Ávila'],
  ['slug'=>'guadalajara','emoji'=>'🌳','label'=>'Guadalajara'],
  ['slug'=>'cuenca',     'emoji'=>'🪨','label'=>'Cuenca'],
  ['slug'=>'ourense',    'emoji'=>'♨️','label'=>'Ourense'],
  ['slug'=>'asturias',   'emoji'=>'🦅','label'=>'Asturias'],
  ['slug'=>'cantabria',  'emoji'=>'🏖️','label'=>'Cantabria'],
  ['slug'=>'granada',    'emoji'=>'🏰','label'=>'Granada'],
  ['slug'=>'lugo',       'emoji'=>'🏛️','label'=>'Lugo'],
];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>" dir="<?= htmlspecialchars($vh['dir']) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($meta_title) ?></title>
<meta name="description" content="<?= htmlspecialchars($meta_desc) ?>">
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
<link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
<?= vh_render_hreflang($vh['hreflang']) ?>
<meta property="og:type"        content="website">
<meta property="og:title"       content="<?= htmlspecialchars($meta_title) ?>">
<meta property="og:description" content="<?= htmlspecialchars($meta_desc) ?>">
<meta property="og:image"       content="<?= htmlspecialchars($og_image) ?>">
<meta property="og:url"         content="<?= htmlspecialchars($canonical) ?>">
<meta property="og:site_name"   content="Rutas Rurales">
<meta name="twitter:card"       content="summary_large_image">
<link rel="icon"             href="/menu_images/Favicon.png" type="image/png">
<link rel="apple-touch-icon" href="/menu_images/Favicon.png">
<link rel="manifest"         href="/manifest.json">
<meta name="theme-color"     content="#2F5233">

<style>
@font-face{font-family:'Montserrat';font-style:normal;font-weight:400;font-display:swap;src:local('Montserrat Regular'),url('/fonts/montserrat-v31-latin-regular.woff2') format('woff2')}
@font-face{font-family:'Montserrat';font-style:normal;font-weight:600;font-display:swap;src:local('Montserrat SemiBold'),url('/fonts/montserrat-v31-latin-600.woff2') format('woff2')}
@font-face{font-family:'Montserrat';font-style:normal;font-weight:800;font-display:swap;src:local('Montserrat ExtraBold'),url('/fonts/montserrat-v31-latin-800.woff2') format('woff2')}
</style>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--primary:#2F5233;--primary-dark:#1a3d1e;--accent:#81C784;--accent-warm:#F9A825;--white:#fff;--bg:#f8f9fa;--bg-alt:#f0f4f1;--text:#2d3436;--text-light:#636e72;--border:#e8eaed;--radius:14px;--radius-sm:8px;--shadow:0 2px 12px rgba(0,0,0,.07);--max-w:1200px;--tr:.18s ease;--act:#5c3a0a}
html{scroll-behavior:smooth}
body{font-family:'Montserrat','Segoe UI',system-ui,sans-serif;background:var(--bg);color:var(--text);line-height:1.65;overflow-x:hidden;-webkit-font-smoothing:antialiased}
img{display:block;max-width:100%;height:auto}a{color:var(--primary);text-decoration:none}ul{list-style:none;padding:0;margin:0}

.act-nav{position:sticky;top:0;z-index:900;background:var(--white);border-bottom:1px solid var(--border);height:60px;display:flex;align-items:center;padding:0 20px;gap:14px;box-shadow:0 1px 6px rgba(0,0,0,.06)}
.act-nav__logo{display:flex;align-items:center;gap:10px;font-weight:800;color:var(--primary);font-size:1rem;flex-shrink:0}
.act-nav__logo img{width:38px;height:38px;border-radius:50%;object-fit:cover}
.act-nav__links{display:flex;align-items:center;gap:4px;margin-left:auto;font-size:.8rem}
.act-nav__links a{color:var(--text);font-weight:600;padding:6px 10px;border-radius:var(--radius-sm);white-space:nowrap;transition:background var(--tr)}
.act-nav__links a:hover,.act-nav__links a[aria-current]{background:var(--bg-alt);color:var(--primary)}
.act-nav__cta{background:var(--primary)!important;color:var(--white)!important;padding:7px 14px!important;border-radius:var(--radius-sm)!important;font-weight:700!important}

.act-hero{position:relative;min-height:340px;display:flex;align-items:flex-end;overflow:hidden}
.act-hero__bg{position:absolute;inset:0;z-index:0}
.act-hero__bg img{width:100%;height:100%;object-fit:cover;object-position:center 40%}
.act-hero__overlay{position:absolute;inset:0;background:linear-gradient(160deg,rgba(50,30,5,.9) 0%,rgba(90,60,10,.72) 55%,rgba(50,30,5,.5) 100%)}
.act-hero__inner{position:relative;z-index:1;padding:52px 20px 48px;width:100%;max-width:var(--max-w);margin:0 auto}
.act-hero h1{font-size:clamp(1.7rem,4vw,2.7rem);font-weight:800;color:var(--white);line-height:1.1;margin-bottom:12px;text-shadow:0 2px 8px rgba(0,0,0,.3);max-width:680px}
.act-hero__sub{font-size:clamp(.88rem,1.8vw,1.05rem);color:rgba(255,255,255,.88);max-width:560px;font-weight:500;line-height:1.55;margin-bottom:22px}
.act-hero__stats{display:flex;flex-wrap:wrap;gap:24px}
.act-stat__val{display:block;font-size:1.55rem;font-weight:800;color:var(--accent-warm);line-height:1}
.act-stat__lbl{font-size:.72rem;color:rgba(255,255,255,.78);font-weight:500}
.act-bc ol{display:flex;gap:4px;flex-wrap:wrap;font-size:.75rem;color:rgba(255,255,255,.7);margin-bottom:14px}
.act-bc a{color:rgba(255,255,255,.7)}

.act-wrap{max-width:var(--max-w);margin:0 auto;padding:0 20px}
.act-section{padding:56px 0}
.act-section--alt{background:var(--bg-alt)}
.act-h2{font-size:clamp(1.25rem,2.5vw,1.65rem);font-weight:800;color:var(--primary);display:flex;align-items:center;gap:10px;margin-bottom:6px}
.act-intro{font-size:.92rem;color:var(--text-light);max-width:620px;line-height:1.6;margin-bottom:24px}

/* Grid actividades */
.act-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px}
.act-card{display:flex;align-items:center;gap:12px;padding:14px 16px;background:var(--white);border:1.5px solid var(--border);border-radius:var(--radius);transition:var(--tr);box-shadow:var(--shadow);color:var(--text)}
.act-card:hover{background:var(--act);color:var(--white);border-color:var(--act);transform:translateY(-2px)}
.act-card__icon{font-size:1.6rem;flex-shrink:0}
.act-card__nm{font-size:.85rem;font-weight:700;display:block}
.act-card__desc{font-size:.72rem;opacity:.7;display:block;margin-top:2px}

/* Provincias */
.act-provs{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px}
.act-prov{display:flex;flex-direction:column;align-items:center;gap:4px;padding:14px 10px;background:var(--white);border:1.5px solid var(--border);border-radius:var(--radius);text-align:center;transition:var(--tr);box-shadow:var(--shadow)}
.act-prov:hover{background:var(--act);color:var(--white);border-color:var(--act);transform:translateY(-2px)}
.act-prov__em{font-size:1.5rem}.act-prov__nm{font-size:.82rem;font-weight:700}

.act-cta{background:linear-gradient(135deg,#3a2000 0%,#7c4a00 100%);color:var(--white);padding:52px 20px;text-align:center}
.act-cta h2{font-size:clamp(1.25rem,2.5vw,1.7rem);font-weight:800;margin-bottom:10px}
.act-cta p{font-size:.92rem;opacity:.82;max-width:480px;margin:0 auto 22px;line-height:1.6}
.act-btn{display:inline-flex;align-items:center;gap:8px;padding:12px 26px;border-radius:25px;font-weight:700;font-size:.88rem;transition:var(--tr)}
.act-btn--w{background:var(--white);color:#5c3a0a}.act-btn--w:hover{background:var(--accent-warm)}
.act-btn--o{background:transparent;border:2px solid rgba(255,255,255,.5);color:var(--white);margin-left:10px}.act-btn--o:hover{background:rgba(255,255,255,.1)}

.act-footer{background:var(--primary-dark);color:rgba(255,255,255,.7);padding:24px 20px;font-size:.8rem}
.act-footer__inner{max-width:var(--max-w);margin:0 auto;display:flex;flex-wrap:wrap;gap:14px;justify-content:space-between;align-items:center}
.act-footer a{color:rgba(255,255,255,.7)}.act-footer a:hover{color:#fff}
.act-footer__nav{display:flex;flex-wrap:wrap;gap:10px}

@media(max-width:800px){.act-nav__links{display:none}.act-provs{grid-template-columns:repeat(auto-fill,minmax(120px,1fr))}.act-grid{grid-template-columns:repeat(auto-fill,minmax(160px,1fr))}}
@media(max-width:480px){.act-hero{min-height:290px}.act-hero__inner{padding:38px 16px 34px}.act-section{padding:40px 0}}
</style>

<script type="application/ld+json">
{"@context":"https://schema.org","@type":"CollectionPage","name":"Actividades Turísticas en España","description":"<?= htmlspecialchars($meta_desc) ?>","url":"<?= htmlspecialchars($canonical) ?>","inLanguage":"<?= htmlspecialchars($lang) ?>","breadcrumb":{"@type":"BreadcrumbList","itemListElement":[{"@type":"ListItem","position":1,"name":"<?= htmlspecialchars($t['nav_home']) ?>","item":"<?= htmlspecialchars($home_url) ?>"},{"@type":"ListItem","position":2,"name":"<?= htmlspecialchars($t['act_bc']) ?>","item":"<?= htmlspecialchars($canonical) ?>"}]},"publisher":{"@type":"Organization","name":"Rutas Rurales","url":"https://rutasrurales.io"}}
</script>
<script>(function(){var l=function(){if(window._gtm)return;window._gtm=1;(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-MBP57VQM');};['click','scroll','keydown','touchstart'].forEach(function(e){window.addEventListener(e,function(){setTimeout(l,1e3)},{once:true,passive:true});});setTimeout(l,8000);})();</script>
</head>
<body>
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MBP57VQM" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

<header class="act-nav" role="banner">
  <a href="<?= htmlspecialchars($home_url) ?>" class="act-nav__logo" aria-label="Rutas Rurales - <?= htmlspecialchars($t['nav_home']) ?>">
    <img src="/menu_images/Logo%20transparente.webp" alt="Rutas Rurales" width="38" height="38" loading="eager">
    <span>Rutas Rurales</span>
  </a>
  <nav class="act-nav__links" aria-label="<?= htmlspecialchars($t['nav_main']) ?>">
    <a href="<?= htmlspecialchars($path_prefix) ?>/alojamientos/">🏡 <?= htmlspecialchars($t['nav_stays']) ?></a>
    <a href="<?= htmlspecialchars($path_prefix) ?>/eventos/">🎭 <?= htmlspecialchars($t['nav_events']) ?></a>
    <a href="<?= htmlspecialchars($path_prefix) ?>/lugares/">📍 <?= htmlspecialchars($t['nav_places']) ?></a>
    <a href="<?= htmlspecialchars($path_prefix) ?>/actividades/" aria-current="page">🥾 <?= htmlspecialchars($t['nav_activities']) ?></a>
    <a href="/rutas.php">🗺️ <?= htmlspecialchars($t['nav_map']) ?></a>
    <a href="/login.html" class="act-nav__cta" rel="nofollow"><?= htmlspecialchars($t['nav_login']) ?></a>
  </nav>
</header>

<main id="main-content">

<section class="act-hero" id="inicio" aria-labelledby="act-h1">
  <div class="act-hero__bg" aria-hidden="true">
    <img src="/menu_images/hero_main.webp"
         alt="Actividades turísticas y senderismo en España rural"
         width="1200" height="500" loading="eager" fetchpriority="high">
    <div class="act-hero__overlay"></div>
  </div>
  <div class="act-hero__inner">
    <nav class="act-bc" aria-label="<?= htmlspecialchars($t['bc_nav']) ?>">
      <ol>
        <li><a href="<?= htmlspecialchars($home_url) ?>"><?= htmlspecialchars($t['nav_home']) ?></a></li>
        <li aria-hidden="true" style="padding:0 4px">›</li>
        <li><span aria-current="page" style="color:#fff"><?= htmlspecialchars($t['act_bc']) ?></span></li>
      </ol>
    </nav>
    <h1 id="act-h1"><?= htmlspecialchars($t['act_h1']) ?></h1>
    <p class="act-hero__sub"><?= htmlspecialchars($t['act_sub']) ?></p>
    <div class="act-hero__stats" aria-label="Estadísticas">
      <div><span class="act-stat__val">+300</span><span class="act-stat__lbl">Actividades</span></div>
      <div><span class="act-stat__val">+15</span><span class="act-stat__lbl">Provincias</span></div>
      <div><span class="act-stat__val">100%</span><span class="act-stat__lbl">Naturaleza</span></div>
    </div>
  </div>
</section>

<!-- TIPOS DE ACTIVIDAD -->
<section class="act-section act-section--alt" aria-labelledby="tipos-h2">
  <div class="act-wrap">
    <h2 class="act-h2" id="tipos-h2">🏃 <?= htmlspecialchars($t['act_by_type']) ?></h2>
    <p class="act-intro">Elige la actividad que más se adapte a tu nivel y preferencias. Todas en entornos naturales de España.</p>
    <ul class="act-grid" role="list" aria-label="<?= htmlspecialchars($t['act_type_aria']) ?>">
      <?php foreach ($actividades as $ak => $ad): ?>
      <li>
        <a href="/actividades-turisticas.html?tipo=<?= urlencode($ak) ?>" class="act-card"
           title="<?= htmlspecialchars($ad['label']) ?>">
          <span class="act-card__icon" aria-hidden="true"><?= $ad['icon'] ?></span>
          <span>
            <span class="act-card__nm"><?= htmlspecialchars($ad['label']) ?></span>
            <span class="act-card__desc"><?= htmlspecialchars($ad['desc']) ?></span>
          </span>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<!-- PROVINCIAS -->
<section class="act-section" aria-labelledby="prov-h2">
  <div class="act-wrap">
    <h2 class="act-h2" id="prov-h2">📍 <?= htmlspecialchars($t['act_by_prov']) ?></h2>
    <p class="act-intro"><?= htmlspecialchars($t['act_by_prov_intro']) ?></p>
    <ul class="act-provs" role="list" aria-label="<?= htmlspecialchars($t['act_prov_aria']) ?>">
      <?php foreach ($provincias as $pv): ?>
      <li>
        <a href="/actividades-turisticas.html?provincia=<?= urlencode($pv['slug']) ?>"
           class="act-prov"
           title="<?= htmlspecialchars($pv['label']) ?>">
          <span class="act-prov__em" aria-hidden="true"><?= $pv['emoji'] ?></span>
          <span class="act-prov__nm"><?= htmlspecialchars($pv['label']) ?></span>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<!-- CTA -->
<section class="act-cta" aria-label="<?= htmlspecialchars($t['act_cta_aria']) ?>">
  <h2><?= htmlspecialchars($t['act_cta_h2']) ?></h2>
  <p><?= htmlspecialchars($t['act_cta_p']) ?></p>
  <a href="/agregar-actividad.html" class="act-btn act-btn--w"><?= htmlspecialchars($t['act_cta_btn']) ?></a>
  <a href="/rutas.php?alojamientos=0&lugares=0&actividades=1&eventos=0" class="act-btn act-btn--o">🗺️ <?= htmlspecialchars($t['map_cta']) ?></a>
</section>

</main>

<footer class="act-footer" role="contentinfo">
  <div class="act-footer__inner">
    <nav class="act-footer__nav" aria-label="Navegación del pie">
      <a href="<?= htmlspecialchars($home_url) ?>"><?= htmlspecialchars($t['nav_home']) ?></a>
      <a href="<?= htmlspecialchars($path_prefix) ?>/alojamientos/"><?= htmlspecialchars($t['nav_stays']) ?></a>
      <a href="<?= htmlspecialchars($path_prefix) ?>/eventos/"><?= htmlspecialchars($t['nav_events']) ?></a>
      <a href="<?= htmlspecialchars($path_prefix) ?>/lugares/"><?= htmlspecialchars($t['nav_places']) ?></a>
      <a href="<?= htmlspecialchars($path_prefix) ?>/actividades/" aria-current="page"><?= htmlspecialchars($t['nav_activities']) ?></a>
      <a href="/aviso-legal.html">Aviso Legal</a>
    </nav>
    <p>&copy; <?= date('Y') ?> <strong style="color:#fff">rutasrurales.io</strong></p>
  </div>
</footer>

<script>if('serviceWorker' in navigator){window.addEventListener('load',function(){navigator.serviceWorker.register('/sw.js').catch(function(){});});}</script>
</body>
</html>