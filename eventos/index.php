<?php
/**
 * /eventos/ — Hub Índice de Eventos Culturales
 * URL canónica: https://rutasrurales.io/eventos/
 */

ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);

$_HUBCFG = dirname(__DIR__) . '/index/config/hub-config.php';
$has_hub_data = false;
if (file_exists($_HUBCFG)) {
    require_once $_HUBCFG;
    $has_hub_data = true;
}

$base_domain = 'https://rutasrurales.io';
$canonical   = $base_domain . '/eventos/';
$meta_title  = 'Eventos Culturales en España | Agenda Cultural y Festivales';
$meta_desc   = 'Descubre más de 1.200 eventos culturales verificados en España. Música, gastronomía, tradiciones, teatro, mercados medievales y festivales por provincia.';
$og_image    = $base_domain . '/menu_images/og-default.jpg';


// Conexión PDO y carga de eventos para el carrusel
$pdo = null;
$total_events = '+1.200';
$upcoming_events = [];

try {
    if (file_exists(dirname(__DIR__) . '/api/config.php')) {
        require_once dirname(__DIR__) . '/api/config.php';
        $pdo = getDBConnection();

        // Conteo total activo
        $r = $pdo->query("SELECT COUNT(*) AS c FROM cultural_events WHERE is_active = 1")->fetch(PDO::FETCH_ASSOC);
        if (!empty($r['c'])) {
            $total_events = '+' . number_format((int)$r['c'], 0, ',', '.');
        }

        // 1. Intentar obtener eventos futuros
        $stmt = $pdo->prepare("
            SELECT name, slug, description, municipality, province, start_date, poster_image 
            FROM cultural_events 
            WHERE is_active = 1 AND start_date >= CURDATE()
            ORDER BY start_date ASC 
            LIMIT 10
        ");
        $stmt->execute();
        $upcoming_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. FALLBACK: Si no hay eventos futuros registrados, traer los últimos 10 activos
        if (empty($upcoming_events)) {
            $stmt = $pdo->prepare("
                SELECT name, slug, description, municipality, province, start_date, poster_image 
                FROM cultural_events 
                WHERE is_active = 1
                ORDER BY id DESC 
                LIMIT 10
            ");
            $stmt->execute();
            $upcoming_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (Throwable $e) {
    // Manejo silencioso de fallback
}    // Manejo silencioso de fallback

// Temporada actual
$mes = (int)date('n');
if (in_array($mes, [12,1,2])) { $temporada = 'invierno'; $temp_label = 'Eventos de invierno ❄️'; }
elseif (in_array($mes, [3,4,5])) { $temporada = 'primavera'; $temp_label = 'Eventos de primavera 🌸'; }
elseif (in_array($mes, [6,7,8])) { $temporada = 'verano'; $temp_label = 'Eventos de verano ☀️'; }
else { $temporada = 'otono'; $temp_label = 'Eventos de otoño 🍂'; }

// Datos inline de respaldo
$categorias_inline = [
    'musica'       => ['icon'=>'🎵', 'label'=>'Música y conciertos'],
    'gastronomia'  => ['icon'=>'🍷', 'label'=>'Gastronomía y vinos'],
    'tradiciones'  => ['icon'=>'🎪', 'label'=>'Tradiciones y folklore'],
    'teatro'       => ['icon'=>'🎭', 'label'=>'Teatro y danza'],
    'exposiciones' => ['icon'=>'🎨', 'label'=>'Arte y exposiciones'],
    'gratuitos'    => ['icon'=>'🎁', 'label'=>'Eventos gratuitos'],
    'mercados'     => ['icon'=>'🛖', 'label'=>'Mercados medievales'],
    'infantil'     => ['icon'=>'👨‍👩‍👧', 'label'=>'Familiar'],
    'verano'       => ['icon'=>'☀️', 'label'=>'Verano'],
    'otono'        => ['icon'=>'🍂', 'label'=>'Otoño'],
];

$provincias_inline = [
    'soria'      => ['emoji'=>'🌲','label'=>'Soria'],
    'zamora'     => ['emoji'=>'🦌','label'=>'Zamora'],
    'leon'       => ['emoji'=>'🏔️','label'=>'León'],
    'burgos'     => ['emoji'=>'⚔️','label'=>'Burgos'],
    'valladolid' => ['emoji'=>'🍇','label'=>'Valladolid'],
    'salamanca'  => ['emoji'=>'🏛️','label'=>'Salamanca'],
    'palencia'   => ['emoji'=>'🌾','label'=>'Palencia'],
    'segovia'    => ['emoji'=>'🏰','label'=>'Segovia'],
    'avila'      => ['emoji'=>'🧱','label'=>'Ávila'],
    'guadalajara'=> ['emoji'=>'🌳','label'=>'Guadalajara'],
    'ourense'    => ['emoji'=>'♨️','label'=>'Ourense'],
    'asturias'   => ['emoji'=>'🦅','label'=>'Asturias'],
    'cantabria'  => ['emoji'=>'🏖️','label'=>'Cantabria'],
    'cordoba'    => ['emoji'=>'🕌','label'=>'Córdoba'],
    'granada'    => ['emoji'=>'🏰','label'=>'Granada'],
];

$categorias = [];
if ($has_hub_data) {
    foreach (HUB_FILTROS_EVT as $k => $v) {
        $categorias[$k] = ['icon' => $v['icon'], 'label' => $v['es']];
    }
} else {
    $categorias = array_map(fn($v) => ['icon'=>$v['icon'],'label'=>$v['label']], $categorias_inline);
}
$provincias = $has_hub_data ? HUB_PROVINCIAS : $provincias_inline;
$combis     = $has_hub_data ? HUB_COMBIS_EVT : [];
$provs_temp = ['soria','zamora','burgos','salamanca','valladolid','leon','palencia','segovia','avila'];
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($meta_title) ?></title>
<meta name="description" content="<?= htmlspecialchars($meta_desc) ?>">
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
<link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
<link rel="alternate" hreflang="es"        href="https://rutasrurales.io/eventos/">
<link rel="alternate" hreflang="en"        href="https://rutasrurales.io/en/eventos/">
<link rel="alternate" hreflang="fr"        href="https://rutasrurales.io/fr/eventos/">
<link rel="alternate" hreflang="de"        href="https://rutasrurales.io/de/eventos/">
<link rel="alternate" hreflang="x-default" href="https://rutasrurales.io/eventos/">
<meta property="og:type"        content="website">
<meta property="og:title"       content="<?= htmlspecialchars($meta_title) ?>">
<meta property="og:description" content="<?= htmlspecialchars($meta_desc) ?>">
<meta property="og:image"       content="<?= htmlspecialchars($og_image) ?>">
<meta property="og:url"         content="<?= htmlspecialchars($canonical) ?>">
<meta property="og:site_name"   content="Rutas Rurales">
<meta name="twitter:card"        content="summary_large_image">
<meta name="twitter:title"       content="<?= htmlspecialchars($meta_title) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($meta_desc) ?>">
<link rel="icon"             href="/menu_images/Favicon.png" type="image/png">
<link rel="apple-touch-icon" href="/menu_images/Favicon.png">
<link rel="manifest"         href="/manifest.json">
<meta name="theme-color"     content="#2F5233">

<style>
@font-face{font-family:'Montserrat';font-style:normal;font-weight:400;font-display:swap;src:local('Montserrat Regular'),url('/fonts/montserrat-v31-latin-regular.woff2') format('woff2')}
@font-face{font-family:'Montserrat';font-style:normal;font-weight:600;font-display:swap;src:local('Montserrat SemiBold'),url('/fonts/montserrat-v31-latin-600.woff2') format('woff2')}
@font-face{font-family:'Montserrat';font-style:normal;font-weight:800;font-display:swap;src:local('Montserrat ExtraBold'),url('/fonts/montserrat-v31-latin-800.woff2') format('woff2')}

*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--primary:#2F5233;--primary-dark:#1a3d1e;--accent:#81C784;--accent-warm:#F9A825;--white:#fff;--bg:#f8f9fa;--bg-alt:#f0f4f1;--text:#2d3436;--text-light:#636e72;--border:#e8eaed;--radius:14px;--radius-sm:8px;--shadow:0 2px 12px rgba(0,0,0,.07);--max-w:1200px;--tr:.18s ease}
html{scroll-behavior:smooth}
body{font-family:'Montserrat','Segoe UI',system-ui,sans-serif;background:var(--bg);color:var(--text);line-height:1.65;overflow-x:hidden;-webkit-font-smoothing:antialiased}
img{display:block;max-width:100%;height:auto}a{color:var(--primary);text-decoration:none}ul{list-style:none;padding:0;margin:0}

.evt-nav{position:sticky;top:0;z-index:900;background:var(--white);border-bottom:1px solid var(--border);height:60px;display:flex;align-items:center;padding:0 20px;gap:14px;box-shadow:0 1px 6px rgba(0,0,0,.06)}
.evt-nav__logo{display:flex;align-items:center;gap:10px;font-weight:800;color:var(--primary);font-size:1rem;flex-shrink:0}
.evt-nav__logo img{width:38px;height:38px;border-radius:50%;object-fit:cover}
.evt-nav__links{display:flex;align-items:center;gap:4px;margin-left:auto;font-size:.8rem}
.evt-nav__links a{color:var(--text);font-weight:600;padding:6px 10px;border-radius:var(--radius-sm);white-space:nowrap;transition:background var(--tr)}
.evt-nav__links a:hover,.evt-nav__links a[aria-current]{background:var(--bg-alt);color:var(--primary)}
.evt-nav__cta{background:var(--primary)!important;color:var(--white)!important;padding:7px 14px!important;border-radius:var(--radius-sm)!important;font-weight:700!important}

/* Hero */
.evt-hero{position:relative;min-height:360px;display:flex;align-items:flex-end;overflow:hidden;background:var(--primary-dark)}
.evt-hero__bg{position:absolute;inset:0;z-index:0}
.evt-hero__bg img{width:100%;height:100%;object-fit:cover;object-position:center 35%}
.evt-hero__overlay{position:absolute;inset:0;background:linear-gradient(160deg,rgba(26,38,60,.9) 0%,rgba(60,40,80,.72) 55%,rgba(26,38,60,.5) 100%)}
.evt-hero__inner{position:relative;z-index:1;padding:52px 20px 48px;width:100%;max-width:var(--max-w);margin:0 auto}
.evt-hero h1{font-size:clamp(1.7rem,4vw,2.7rem);font-weight:800;color:var(--white);line-height:1.1;margin-bottom:12px;text-shadow:0 2px 8px rgba(0,0,0,.3);max-width:680px}
.evt-hero__sub{font-size:clamp(.88rem,1.8vw,1.05rem);color:rgba(255,255,255,.88);margin-bottom:22px;max-width:560px;font-weight:500;line-height:1.55}
.evt-hero__stats{display:flex;flex-wrap:wrap;gap:24px}
.evt-stat__val{display:block;font-size:1.55rem;font-weight:800;color:var(--accent-warm);line-height:1}
.evt-stat__lbl{font-size:.72rem;color:rgba(255,255,255,.78);font-weight:500}
.evt-bc ol{display:flex;gap:4px;flex-wrap:wrap;font-size:.75rem;color:rgba(255,255,255,.7);margin-bottom:14px}
.evt-bc a{color:rgba(255,255,255,.7)}

/* Container */
.evt-wrap{max-width:var(--max-w);margin:0 auto;padding:0 20px}
.evt-section{padding:48px 0}
.evt-section--alt{background:var(--bg-alt)}
.evt-section__hdr{margin-bottom:20px}
.evt-h2{font-size:clamp(1.25rem,2.5vw,1.65rem);font-weight:800;color:var(--primary);display:flex;align-items:center;gap:10px;margin-bottom:6px}
.evt-intro{font-size:.92rem;color:var(--text-light);max-width:620px;line-height:1.6}

/* Carrusel Touch Native Optimizado */
.evt-carousel-wrap{position:relative;width:100%;margin-top:10px}
.evt-carousel{display:flex;gap:16px;overflow-x:auto;scroll-snap-type:x mandatory;scroll-behavior:smooth;padding:8px 4px 16px;scrollbar-width:thin;scrollbar-color:var(--primary) transparent;-webkit-overflow-scrolling:touch}
.evt-carousel::-webkit-scrollbar{height:6px}
.evt-carousel::-webkit-scrollbar-thumb{background:var(--primary);border-radius:4px}
.evt-card{flex:0 0 280px;scroll-snap-align:start;background:var(--white);border:1.5px solid var(--border);border-radius:var(--radius);overflow:hidden;box-shadow:var(--shadow);display:flex;flex-direction:column;transition:transform .2s ease,box-shadow .2s ease}
.evt-card:hover{transform:translateY(-3px);box-shadow:0 6px 16px rgba(0,0,0,.1)}
.evt-card__img{position:relative;width:100%;height:150px;background:var(--bg-alt);overflow:hidden}
.evt-card__img img{width:100%;height:100%;object-fit:cover}
.evt-card__badge{position:absolute;top:10px;right:10px;background:var(--primary);color:var(--white);font-size:0.75rem;font-weight:800;padding:4px 8px;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,.2)}
.evt-card__body{padding:14px;display:flex;flex-direction:column;flex-grow:1}
.evt-card__loc{font-size:0.75rem;font-weight:700;color:var(--accent-warm);text-transform:uppercase;margin-bottom:4px}
.evt-card__title{font-size:0.95rem;font-weight:800;line-height:1.3;margin-bottom:6px}
.evt-card__title a{color:var(--text)}
.evt-card__desc{font-size:0.8rem;color:var(--text-light);line-height:1.45;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:12px}
.evt-card__btn{margin-top:auto;font-size:0.8rem;font-weight:700;color:var(--primary);display:inline-flex;align-items:center;gap:4px}

/* Temporada destacada */
.evt-season-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;margin-top:4px}
.evt-season-card{display:flex;flex-direction:column;align-items:center;gap:4px;padding:14px 10px;background:var(--white);border:1.5px solid var(--border);border-radius:var(--radius);text-align:center;transition:var(--tr);box-shadow:var(--shadow)}
.evt-season-card:hover{background:#3d2b6e;color:var(--white);border-color:#3d2b6e;transform:translateY(-2px)}
.evt-season-card__em{font-size:1.5rem}
.evt-season-card__prov{font-size:.82rem;font-weight:700}
.evt-season-card__cat{font-size:.7rem;opacity:.7}

/* Categorías */
.evt-chips{display:flex;flex-wrap:wrap;gap:10px}
.evt-chip{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:var(--white);border:1.5px solid var(--border);border-radius:24px;font-size:.83rem;font-weight:600;color:var(--text);transition:var(--tr);box-shadow:var(--shadow)}
.evt-chip:hover{background:#3d2b6e;color:var(--white);border-color:#3d2b6e;transform:translateY(-1px)}

/* Provincias */
.evt-provs{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px}
.evt-prov{display:flex;flex-direction:column;align-items:center;gap:4px;padding:14px 10px;background:var(--white);border:1.5px solid var(--border);border-radius:var(--radius);text-align:center;transition:var(--tr);box-shadow:var(--shadow)}
.evt-prov:hover{background:#3d2b6e;color:var(--white);border-color:#3d2b6e;transform:translateY(-2px)}
.evt-prov__em{font-size:1.5rem}.evt-prov__nm{font-size:.82rem;font-weight:700}

/* Accordion */
.evt-accord{background:var(--white);border:1.5px solid var(--border);border-radius:var(--radius);overflow:hidden;box-shadow:var(--shadow);margin-top:24px}
.evt-accord summary{padding:14px 18px;cursor:pointer;font-weight:700;font-size:.9rem;color:var(--primary);display:flex;align-items:center;gap:8px;list-style:none;user-select:none}
.evt-accord summary::-webkit-details-marker{display:none}
.evt-accord summary .ac-ch{margin-left:auto;transition:transform .2s}
.evt-accord[open] summary .ac-ch{transform:rotate(90deg)}
.evt-accord__body{padding:16px 18px;border-top:1px solid var(--border)}
.evt-links{display:flex;flex-wrap:wrap;gap:8px}
.evt-lnk{font-size:.8rem;color:#3d2b6e;padding:4px 10px;background:var(--bg-alt);border-radius:20px;font-weight:600;transition:var(--tr)}
.evt-lnk:hover{background:#3d2b6e;color:var(--white)}

/* CTA */
.evt-cta{background:linear-gradient(135deg,#1a1a2e 0%,#3d2b6e 100%);color:var(--white);padding:52px 20px;text-align:center}
.evt-cta h2{font-size:clamp(1.25rem,2.5vw,1.7rem);font-weight:800;margin-bottom:10px}
.evt-cta p{font-size:.92rem;opacity:.82;max-width:480px;margin:0 auto 22px;line-height:1.6}
.evt-btn{display:inline-flex;align-items:center;gap:8px;padding:12px 26px;border-radius:25px;font-weight:700;font-size:.88rem;transition:var(--tr)}
.evt-btn--w{background:var(--white);color:#3d2b6e}.evt-btn--w:hover{background:var(--accent-warm)}
.evt-btn--o{background:transparent;border:2px solid rgba(255,255,255,.5);color:var(--white);margin-left:10px}.evt-btn--o:hover{background:rgba(255,255,255,.1)}

/* Footer */
.evt-footer{background:var(--primary-dark);color:rgba(255,255,255,.7);padding:24px 20px;font-size:.8rem}
.evt-footer__inner{max-width:var(--max-w);margin:0 auto;display:flex;flex-wrap:wrap;gap:14px;justify-content:space-between;align-items:center}
.evt-footer a{color:rgba(255,255,255,.7)}.evt-footer a:hover{color:#fff}
.evt-footer__nav{display:flex;flex-wrap:wrap;gap:10px}

@media(max-width:800px){
    .evt-nav__links{display:none}
    .evt-provs{grid-template-columns:repeat(auto-fill,minmax(120px,1fr))}
    .evt-card{flex:0 0 240px} /* Tamaño ajustado para pantallas móviles */
}
@media(max-width:480px){
    .evt-hero{min-height:300px}
    .evt-hero__inner{padding:40px 16px 36px}
    .evt-section{padding:36px 0}
}
</style>

<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@type":"CollectionPage",
  "name":"Eventos Culturales en España",
  "description":"<?= htmlspecialchars($meta_desc) ?>",
  "url":"<?= htmlspecialchars($canonical) ?>",
  "inLanguage":"es",
  "breadcrumb":{"@type":"BreadcrumbList","itemListElement":[
    {"@type":"ListItem","position":1,"name":"Inicio","item":"https://rutasrurales.io/"},
    {"@type":"ListItem","position":2,"name":"Eventos Culturales","item":"<?= htmlspecialchars($canonical) ?>"}
  ]},
  "publisher":{"@type":"Organization","name":"Rutas Rurales","url":"https://rutasrurales.io"}
}
</script>
<script>(function(){var l=function(){if(window._gtm)return;window._gtm=1;(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-MBP57VQM');};['click','scroll','keydown','touchstart'].forEach(function(e){window.addEventListener(e,function(){setTimeout(l,1e3)},{once:true,passive:true});});setTimeout(l,8000);})();</script>
</head>
<body>
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MBP57VQM" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

<!-- NAVBAR -->
<header class="evt-nav" role="banner">
  <a href="https://rutasrurales.io/" class="evt-nav__logo" aria-label="Rutas Rurales - Inicio">
    <img src="/menu_images/Logo%20transparente.webp" alt="Rutas Rurales" width="38" height="38" loading="eager">
    <span>Rutas Rurales</span>
  </a>
  <nav class="evt-nav__links" aria-label="Menú principal">
    <a href="/alojamientos/">🏡 Alojamientos</a>
    <a href="/eventos/" aria-current="page">🎭 Eventos</a>
    <a href="/lugares/">📍 Lugares</a>
    <a href="/actividades/">🥾 Actividades</a>
    <a href="/rutas.php">🗺️ Mapa</a>
    <a href="/login.html" class="evt-nav__cta" rel="nofollow">Acceder</a>
  </nav>
</header>

<main id="main-content">

<!-- HERO -->
<section class="evt-hero" id="inicio" aria-labelledby="evt-h1">
  <div class="evt-hero__bg" aria-hidden="true">
    <img src="/menu_images/hero_main.webp"
         alt="Eventos culturales y festivales en España"
         width="1200" height="500" loading="eager" fetchpriority="high">
    <div class="evt-hero__overlay"></div>
  </div>
  <div class="evt-hero__inner">
    <nav class="evt-bc" aria-label="Ruta de navegación">
      <ol>
        <li><a href="https://rutasrurales.io/">Inicio</a></li>
        <li aria-hidden="true" style="padding:0 4px">›</li>
        <li><span aria-current="page" style="color:#fff">Eventos culturales</span></li>
      </ol>
    </nav>
    <h1 id="evt-h1">Eventos Culturales en España</h1>
    <p class="evt-hero__sub">Más de 1.200 eventos verificados: música, gastronomía, tradiciones, teatro, mercados medievales y mucho más por toda España.</p>
    <div class="evt-hero__stats" aria-label="Estadísticas">
      <div><span class="evt-stat__val"><?= htmlspecialchars($total_events) ?></span><span class="evt-stat__lbl">Eventos</span></div>
      <div><span class="evt-stat__val">+20</span><span class="evt-stat__lbl">Provincias</span></div>
      <div><span class="evt-stat__val">Gratis</span><span class="evt-stat__lbl">Muchos sin coste</span></div>
    </div>
  </div>
</section>

<!-- CARRUSEL DE EVENTOS PRÓXIMOS -->
<?php if (!empty($upcoming_events)): ?>
<section class="evt-section" aria-labelledby="carousel-h2">
  <div class="evt-wrap">
    <div class="evt-section__hdr">
      <h2 class="evt-h2" id="carousel-h2">📅 Próximos eventos en la agenda</h2>
      <p class="evt-intro">Eventos destacados ordenados por fecha de celebración en los próximos meses.</p>
    </div>
    <div class="evt-carousel-wrap">
      <ul class="evt-carousel" role="list">
        <?php foreach ($upcoming_events as $ev): 
          $img_src = !empty($ev['poster_image']) ? $ev['poster_image'] : '/menu_images/og-default.jpg';
          $date_badge = date('d M', strtotime($ev['start_date']));
          $lugar = array_filter([$ev['municipality'], $ev['province']]);
          $loc_str = implode(', ', $lugar);
          // Usar description (truncada) para short_description
          $short_desc = !empty($ev['description']) ? mb_substr(strip_tags($ev['description']), 0, 120) . '...' : '';
        ?>
        <li class="evt-card">
          <div class="evt-card__img">
            <img src="<?= htmlspecialchars($img_src) ?>" 
                 alt="<?= htmlspecialchars($ev['name']) ?>" 
                 width="280" height="150" 
                 loading="lazy" 
                 decoding="async">
            <span class="evt-card__badge"><?= strtoupper($date_badge) ?></span>
          </div>
          <div class="evt-card__body">
            <?php if ($loc_str): ?>
              <span class="evt-card__loc">📍 <?= htmlspecialchars($loc_str) ?></span>
            <?php endif; ?>
            <h3 class="evt-card__title">
              <a href="/eventos/<?= htmlspecialchars($ev['slug']) ?>"><?= htmlspecialchars($ev['name']) ?></a>
            </h3>
            <?php if (!empty($short_desc)): ?>
              <p class="evt-card__desc"><?= htmlspecialchars($short_desc) ?></p>
            <?php endif; ?>
            <a href="/eventos/<?= htmlspecialchars($ev['slug']) ?>" class="evt-card__btn">Ver detalles ›</a>
          </div>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- TEMPORADA ACTUAL -->
<section class="evt-section evt-section--alt" aria-labelledby="temp-h2">
  <div class="evt-wrap">
    <div class="evt-section__hdr">
      <h2 class="evt-h2" id="temp-h2"><?= htmlspecialchars($temp_label) ?></h2>
      <p class="evt-intro">Los mejores eventos de la temporada en las provincias con mayor oferta cultural.</p>
    </div>
    <ul class="evt-season-grid" role="list" aria-label="Eventos de temporada por provincia">
      <?php
      $filtro_temp = $temporada;
      $cat_label = $has_hub_data ? (HUB_FILTROS_EVT[$filtro_temp]['es'] ?? ucfirst($temporada)) : ucfirst($temporada);
      foreach ($provs_temp as $pk):
        $pd = $has_hub_data ? (HUB_PROVINCIAS[$pk] ?? null) : ($provincias_inline[$pk] ?? null);
        if (!$pd) continue;
        $slug_t = $filtro_temp . '-' . $pk;
        $url_t  = '/eventos/' . $slug_t;
      ?>
      <li>
        <a href="<?= htmlspecialchars($url_t) ?>" class="evt-season-card"
           title="<?= htmlspecialchars($cat_label . ' en ' . $pd['label']) ?>">
          <span class="evt-season-card__em" aria-hidden="true"><?= $pd['emoji'] ?></span>
          <span class="evt-season-card__prov"><?= htmlspecialchars($pd['label']) ?></span>
          <span class="evt-season-card__cat"><?= htmlspecialchars($cat_label) ?></span>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<!-- CATEGORÍAS -->
<section class="evt-section" aria-labelledby="cats-h2">
  <div class="evt-wrap">
    <div class="evt-section__hdr">
      <h2 class="evt-h2" id="cats-h2">🗂️ Buscar por categoría</h2>
      <p class="evt-intro">Explora la agenda cultural de España según el tipo de evento que más te interese.</p>
    </div>
    <ul class="evt-chips" role="list" aria-label="Categorías de eventos culturales">
      <?php foreach ($categorias as $ck => $cd): ?>
      <li>
        <a href="/eventos/<?= htmlspecialchars($ck) ?>" class="evt-chip"
           title="<?= htmlspecialchars($cd['label']) ?> en España">
          <span aria-hidden="true"><?= $cd['icon'] ?></span>
          <?= htmlspecialchars($cd['label']) ?>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<!-- PROVINCIAS -->
<section class="evt-section evt-section--alt" aria-labelledby="provs-h2">
  <div class="evt-wrap">
    <div class="evt-section__hdr">
      <h2 class="evt-h2" id="provs-h2">📍 Agenda cultural por provincia</h2>
      <p class="evt-intro">Consulta todos los eventos culturales disponibles en cada provincia española.</p>
    </div>
    <ul class="evt-provs" role="list" aria-label="Provincias con agenda cultural">
      <?php foreach ($provincias as $pk => $pd): ?>
      <li>
        <a href="/eventos/<?= htmlspecialchars($pk) ?>" class="evt-prov"
           title="Agenda cultural en <?= htmlspecialchars($pd['label']) ?>">
          <span class="evt-prov__em" aria-hidden="true"><?= $pd['emoji'] ?></span>
          <span class="evt-prov__nm"><?= htmlspecialchars($pd['label']) ?></span>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>

    <?php if (!empty($combis)): ?>
    <details class="evt-accord">
      <summary>
        <span aria-hidden="true">⭐</span>
        Combinaciones destacadas
        <span class="ac-ch" aria-hidden="true">›</span>
      </summary>
      <div class="evt-accord__body">
        <ul class="evt-links" role="list" aria-label="Combinaciones populares de eventos">
          <?php foreach ($combis as $combi):
            [$fk, $pk2] = $combi;
            if (!isset(HUB_FILTROS_EVT[$fk], HUB_PROVINCIAS[$pk2])) continue;
            $slug_c = $fk . '-' . $pk2;
            $label_c = (HUB_FILTROS_EVT[$fk]['es'] ?? $fk) . ' · ' . (HUB_PROVINCIAS[$pk2]['label'] ?? $pk2);
          ?>
          <li>
            <a href="/eventos/<?= htmlspecialchars($slug_c) ?>" class="evt-lnk">
              <?= htmlspecialchars($label_c) ?>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </details>
    <?php endif; ?>
  </div>
</section>

<!-- CTA -->
<section class="evt-cta" aria-label="Añadir un evento">
  <h2>¿Organizas un evento cultural?</h2>
  <p>Publica tu evento en nuestra plataforma y llega a miles de aficionados a la cultura rural en toda España.</p>
  <a href="/agregar-evento.html" class="evt-btn evt-btn--w">Publicar mi evento</a>
  <a href="/rutas.php?alojamientos=0&lugares=0&actividades=0&eventos=1" class="evt-btn evt-btn--o">🗺️ Ver en el mapa</a>
</section>

</main>

<!-- FOOTER -->
<footer class="evt-footer" role="contentinfo">
  <div class="evt-footer__inner">
    <nav class="evt-footer__nav" aria-label="Navegación del pie">
      <a href="https://rutasrurales.io/">Inicio</a>
      <a href="/alojamientos/">Alojamientos</a>
      <a href="/eventos/" aria-current="page">Eventos</a>
      <a href="/lugares/">Lugares</a>
      <a href="/actividades/">Actividades</a>
      <a href="/aviso-legal.html">Aviso Legal</a>
    </nav>
    <p>© <?= date('Y') ?> <strong style="color:#fff">rutasrurales.io</strong></p>
  </div>
</footer>

<script>if('serviceWorker' in navigator){window.addEventListener('load',function(){navigator.serviceWorker.register('/sw.js').catch(function(){});});}</script>
</body>
</html>