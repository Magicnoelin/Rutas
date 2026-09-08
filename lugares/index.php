<?php
/**
 * /lugares/ — Hub Índice de Lugares de Interés
 * URL canónica: https://rutasrurales.io/lugares/
 */
ini_set('display_errors', 0); error_reporting(E_ERROR | E_PARSE);

$base_domain = 'https://rutasrurales.io';
$canonical   = $base_domain . '/lugares/';
$meta_title  = 'Lugares de Interés en España | Monumentos, Naturaleza y Gastronomía Rural';
$meta_desc   = 'Descubre los mejores lugares de interés rurales de España: monumentos históricos, espacios naturales, restaurantes con encanto, bodegas y mucho más.';
$og_image    = $base_domain . '/menu_images/og-default.jpg';

// ── Helper: texto → slug URL ─────────────────────────────────────────────────
function lug_to_slug(string $text): string {
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = strtr($text, [
        'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',
        'à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u','ç'=>'c',
    ]);
    $text = preg_replace('/[^a-z0-9\s\-]/', '', $text);
    $text = preg_replace('/[\s\-]+/', '-', $text);
    return trim($text, '-');
}

// ── Emoji por provincia ───────────────────────────────────────────────────────
function lug_province_emoji(string $prov): string {
    $slug = lug_to_slug($prov);
    $map = [
        'soria'=>'🌲','zamora'=>'🦌','leon'=>'🏔️','burgos'=>'⚔️','valladolid'=>'🍇',
        'salamanca'=>'🏛️','segovia'=>'🏰','avila'=>'🧱','palencia'=>'🌾',
        'guadalajara'=>'🌳','ourense'=>'♨️','cordoba'=>'🕌','granada'=>'🏰',
        'asturias'=>'🦅','cantabria'=>'🏖️','lugo'=>'🏛️','pontevedra'=>'🌊',
        'toledo'=>'🏰','valencia'=>'🍊','navarra'=>'🏔️','madrid'=>'🏙️',
        'barcelona'=>'🎨','sevilla'=>'💃','malaga'=>'☀️','cadiz'=>'🌊',
        'huelva'=>'🌿','jaen'=>'🫒','almeria'=>'🏜️','murcia'=>'🌞',
        'alicante'=>'⛵','castellon'=>'🌄','zaragoza'=>'🕌','huesca'=>'⛰️',
        'teruel'=>'🏛️','lleida'=>'🏔️','tarragona'=>'🏺','girona'=>'🌊',
        'caceres'=>'🦜','badajoz'=>'🌾','albacete'=>'🔪','ciudad-real'=>'♟️',
        'cuenca'=>'🏚️','la-rioja'=>'🍷','alava'=>'🍷','gipuzkoa'=>'🌊',
        'vizcaya'=>'🌉','a-coruna'=>'🐙','baleares'=>'⛵',
        'las-palmas'=>'🌋','santa-cruz-de-tenerife'=>'🌋',
    ];
    return $map[$slug] ?? '📍';
}

// ── Icono fallback para categorías sin icono en BD ───────────────────────────
function lug_cat_icon(string $slug, ?string $db_icon): string {
    if (!empty($db_icon)) return $db_icon;
    $map = [
        'patrimonio'=>'🏛️','naturaleza'=>'🌿','gastronomia'=>'🍷','gastronom'=>'🍷',
        'bodegas'=>'🍾','bodega'=>'🍾','rutas'=>'🥾','museos'=>'🎨','museo'=>'🎨',
        'miradores'=>'🔭','mirador'=>'🔭','mercados'=>'🛖','mercado'=>'🛖',
        'restaurante'=>'🍽️','monumento'=>'🏛️','parque'=>'🌿','iglesia'=>'⛪',
        'castillo'=>'🏰','playa'=>'🏖️','rio'=>'🌊','lago'=>'💧',
    ];
    foreach ($map as $k => $v) {
        if (str_contains($slug, $k)) return $v;
    }
    return '📍';
}

// ── Helper: convierte identificador de icono a emoji ─────────────────────────
function obtenerEmojiLugar(string $icono): string {
    return match (strtolower(trim($icono))) {
        'fort', 'castle-turret' => '🏰',
        'city'                 => '🏙️',
        'binoculars'           => '🔭',
        'castle', 'landmark'   => '🏛️',
        'museum'               => '🏛️',
        'church', 'chapel'     => '⛪',
        'tree', 'forest'       => '🌲',
        'park'                 => '🏞️',
        'water'                => '🌊',
        'home-city'            => '🏡',
        'monastery'            => '🛕',
        'fas fa-hammer'        => '🔨',
        'bodega', 'bodegas'    => '🍾',
        'parque', 'parques'    => '🎢',
        'restauracion'         => '🍽️',
        '🎢'                  => '🎢',
        '🍾'                  => '🍾',
        '🍽️'                  => '🍽️',
        default                => '📍',
    };
}

// ── Conectar a BD y cargar datos ─────────────────────────────────────────────
$tipos     = [];
$provincias = [];
$total_places = '';

try {
    // API_NO_HEADERS evita que api/config.php envíe Content-Type: application/json
    // (esta es una página HTML, no una respuesta de API)
    if (!defined('API_NO_HEADERS')) define('API_NO_HEADERS', true);
    require_once dirname(__DIR__) . '/api/config.php';
    $pdo = getDBConnection();

    // Total de lugares activos
    $r = $pdo->query("SELECT COUNT(*) AS c FROM places_of_interest WHERE is_active=1")->fetch(PDO::FETCH_ASSOC);
    if (!empty($r['c'])) $total_places = '+' . number_format((int)$r['c'], 0, ',', '.');

    // Todas las categorías activas (incluyendo las con 0 lugares)
    $stypes = $pdo->query("
        SELECT c.id, c.name, c.slug, c.icon, c.description, COUNT(p.id) AS total
        FROM categories_places c
        LEFT JOIN places_of_interest p ON p.category_id = c.id AND p.is_active = 1
        WHERE c.is_active = 1
        GROUP BY c.id, c.name, c.slug, c.icon, c.description
        ORDER BY c.display_order ASC, c.name ASC
    ");
    $tipos = $stypes->fetchAll(PDO::FETCH_ASSOC);

    // Provincias con al menos 1 lugar activo, ordenadas por número de lugares
    $sprovs = $pdo->query("
        SELECT province, COUNT(*) AS total
        FROM places_of_interest
        WHERE is_active = 1 AND province IS NOT NULL AND province != ''
        GROUP BY province
        HAVING total > 0
        ORDER BY total DESC, province ASC
    ");
    $provincias = $sprovs->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) { /* silencioso — fallback abajo */ }

// ── Fallback estático si la BD no responde ───────────────────────────────────
if (empty($tipos)) {
    $tipos = [
        ['name'=>'Patrimonio histórico','slug'=>'patrimonio',    'icon'=>'🏛️','description'=>'Castillos, iglesias y monumentos','total'=>0],
        ['name'=>'Naturaleza',          'slug'=>'naturaleza',    'icon'=>'🌿','description'=>'Parques, reservas y paisajes',    'total'=>0],
        ['name'=>'Gastronomía',         'slug'=>'gastronomia',   'icon'=>'🍷','description'=>'Restaurantes y productos locales','total'=>0],
        ['name'=>'Bodegas y vinos',     'slug'=>'bodegas',       'icon'=>'🍾','description'=>'Enoturismo y catas',              'total'=>0],
        ['name'=>'Rutas y senderos',    'slug'=>'rutas',         'icon'=>'🥾','description'=>'Caminos y travesías',             'total'=>0],
        ['name'=>'Museos y arte',       'slug'=>'museos',        'icon'=>'🎨','description'=>'Arte, cultura e historia',        'total'=>0],
        ['name'=>'Miradores',           'slug'=>'miradores',     'icon'=>'🔭','description'=>'Vistas panorámicas',             'total'=>0],
        ['name'=>'Mercados locales',    'slug'=>'mercados',      'icon'=>'🛖','description'=>'Artesanía y productos km0',      'total'=>0],
        ['name'=>'Bodegas',             'slug'=>'bodegas-cat',   'icon'=>'🍾','description'=>'Categoría dedicada a las bodegas','total'=>14],
        ['name'=>'Parques Temáticos',   'slug'=>'parques-tematicos','icon'=>'🎢','description'=>'Categoría dedicada a parques de atracciones y temáticos','total'=>1],
        ['name'=>'Restauración',        'slug'=>'restauracion',  'icon'=>'🍽️','description'=>'Categoría de restauración',       'total'=>0],
    ];
}
if (empty($provincias)) {
    foreach (['Soria','Zamora','León','Burgos','Valladolid','Salamanca','Segovia','Ávila',
              'Palencia','Guadalajara','Ourense','Córdoba','Granada','Asturias','Cantabria'] as $p) {
        $provincias[] = ['province' => $p, 'total' => 0];
    }
}
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
<link rel="alternate" hreflang="es"        href="https://rutasrurales.io/lugares/">
<link rel="alternate" hreflang="x-default" href="https://rutasrurales.io/lugares/">
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
:root{--primary:#2F5233;--primary-dark:#1a3d1e;--accent:#81C784;--white:#fff;--bg:#f8f9fa;--bg-alt:#f0f4f1;--text:#2d3436;--text-light:#636e72;--border:#e8eaed;--radius:14px;--radius-sm:8px;--shadow:0 2px 12px rgba(0,0,0,.07);--max-w:1200px;--tr:.18s ease;--lug:#1a3a5c}
html{scroll-behavior:smooth}
body{font-family:'Montserrat','Segoe UI',system-ui,sans-serif;background:var(--bg);color:var(--text);line-height:1.65;overflow-x:hidden;-webkit-font-smoothing:antialiased}
img{display:block;max-width:100%;height:auto}a{color:var(--primary);text-decoration:none}ul{list-style:none;padding:0;margin:0}

.lug-nav{position:sticky;top:0;z-index:900;background:var(--white);border-bottom:1px solid var(--border);height:60px;display:flex;align-items:center;padding:0 20px;gap:14px;box-shadow:0 1px 6px rgba(0,0,0,.06)}
.lug-nav__logo{display:flex;align-items:center;gap:10px;font-weight:800;color:var(--primary);font-size:1rem;flex-shrink:0}
.lug-nav__logo img{width:38px;height:38px;border-radius:50%;object-fit:cover}
.lug-nav__links{display:flex;align-items:center;gap:4px;margin-left:auto;font-size:.8rem}
.lug-nav__links a{color:var(--text);font-weight:600;padding:6px 10px;border-radius:var(--radius-sm);white-space:nowrap;transition:background var(--tr)}
.lug-nav__links a:hover,.lug-nav__links a[aria-current]{background:var(--bg-alt);color:var(--primary)}
.lug-nav__cta{background:var(--primary)!important;color:var(--white)!important;padding:7px 14px!important;border-radius:var(--radius-sm)!important;font-weight:700!important}

.lug-hero{position:relative;min-height:340px;display:flex;align-items:flex-end;overflow:hidden}
.lug-hero__bg{position:absolute;inset:0;z-index:0}
.lug-hero__bg img{width:100%;height:100%;object-fit:cover;object-position:center 45%}
.lug-hero__overlay{position:absolute;inset:0;background:linear-gradient(160deg,rgba(26,58,92,.9) 0%,rgba(36,80,120,.72) 55%,rgba(26,58,92,.5) 100%)}
.lug-hero__inner{position:relative;z-index:1;padding:52px 20px 48px;width:100%;max-width:var(--max-w);margin:0 auto}
.lug-hero h1{font-size:clamp(1.7rem,4vw,2.7rem);font-weight:800;color:var(--white);line-height:1.1;margin-bottom:12px;text-shadow:0 2px 8px rgba(0,0,0,.3);max-width:680px}
.lug-hero__sub{font-size:clamp(.88rem,1.8vw,1.05rem);color:rgba(255,255,255,.88);max-width:560px;font-weight:500;line-height:1.55}
.lug-hero__stats{display:flex;flex-wrap:wrap;gap:24px;margin-top:18px}
.lug-stat__val{display:block;font-size:1.55rem;font-weight:800;color:var(--accent);line-height:1}
.lug-stat__lbl{font-size:.72rem;color:rgba(255,255,255,.78);font-weight:500}
.lug-bc ol{display:flex;gap:4px;flex-wrap:wrap;font-size:.75rem;color:rgba(255,255,255,.7);margin-bottom:14px}
.lug-bc a{color:rgba(255,255,255,.7)}

.lug-wrap{max-width:var(--max-w);margin:0 auto;padding:0 20px}
.lug-section{padding:56px 0}
.lug-section--alt{background:var(--bg-alt)}
.lug-h2{font-size:clamp(1.25rem,2.5vw,1.65rem);font-weight:800;color:var(--primary);display:flex;align-items:center;gap:10px;margin-bottom:6px}
.lug-intro{font-size:.92rem;color:var(--text-light);max-width:620px;line-height:1.6;margin-bottom:24px}

/* Grid tipos */
.lug-tipos{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px}
.lug-tipo{display:flex;align-items:center;gap:12px;padding:14px 16px;background:var(--white);border:1.5px solid var(--border);border-radius:var(--radius);transition:var(--tr);box-shadow:var(--shadow);text-decoration:none;color:var(--text)}
.lug-tipo:hover{background:var(--lug);color:var(--white);border-color:var(--lug);transform:translateY(-2px)}
.lug-tipo__icon{font-size:1.6rem;flex-shrink:0}
.lug-tipo__info{min-width:0}
.lug-tipo__nm{font-size:.85rem;font-weight:700;display:block}
.lug-tipo__desc{font-size:.72rem;opacity:.7;display:block;margin-top:2px}
.lug-tipo__count{font-size:.68rem;opacity:.55;display:block;margin-top:2px}

/* Grid provincias */
.lug-provs{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px}
.lug-prov{display:flex;flex-direction:column;align-items:center;gap:4px;padding:14px 10px;background:var(--white);border:1.5px solid var(--border);border-radius:var(--radius);text-align:center;transition:var(--tr);box-shadow:var(--shadow)}
.lug-prov:hover{background:var(--lug);color:var(--white);border-color:var(--lug);transform:translateY(-2px)}
.lug-prov__em{font-size:1.5rem}.lug-prov__nm{font-size:.82rem;font-weight:700}
.lug-prov__count{font-size:.68rem;opacity:.6}

.lug-cta{background:linear-gradient(135deg,#1a3a5c 0%,#2563a8 100%);color:var(--white);padding:52px 20px;text-align:center}
.lug-cta h2{font-size:clamp(1.25rem,2.5vw,1.7rem);font-weight:800;margin-bottom:10px}
.lug-cta p{font-size:.92rem;opacity:.82;max-width:480px;margin:0 auto 22px;line-height:1.6}
.lug-btn{display:inline-flex;align-items:center;gap:8px;padding:12px 26px;border-radius:25px;font-weight:700;font-size:.88rem;transition:var(--tr)}
.lug-btn--w{background:var(--white);color:#1a3a5c}.lug-btn--w:hover{background:var(--accent)}
.lug-btn--o{background:transparent;border:2px solid rgba(255,255,255,.5);color:var(--white);margin-left:10px}.lug-btn--o:hover{background:rgba(255,255,255,.1)}

.lug-footer{background:var(--primary-dark);color:rgba(255,255,255,.7);padding:24px 20px;font-size:.8rem}
.lug-footer__inner{max-width:var(--max-w);margin:0 auto;display:flex;flex-wrap:wrap;gap:14px;justify-content:space-between;align-items:center}
.lug-footer a{color:rgba(255,255,255,.7)}.lug-footer a:hover{color:#fff}
.lug-footer__nav{display:flex;flex-wrap:wrap;gap:10px}

@media(max-width:800px){.lug-nav__links{display:none}.lug-provs{grid-template-columns:repeat(auto-fill,minmax(120px,1fr))}.lug-tipos{grid-template-columns:repeat(auto-fill,minmax(160px,1fr))}}
@media(max-width:480px){.lug-hero{min-height:290px}.lug-hero__inner{padding:38px 16px 34px}.lug-section{padding:40px 0}}
</style>

<script type="application/ld+json">
{"@context":"https://schema.org","@type":"CollectionPage","name":"Lugares de Interés en España","description":"<?= htmlspecialchars($meta_desc) ?>","url":"<?= htmlspecialchars($canonical) ?>","inLanguage":"es","breadcrumb":{"@type":"BreadcrumbList","itemListElement":[{"@type":"ListItem","position":1,"name":"Inicio","item":"https://rutasrurales.io/"},{"@type":"ListItem","position":2,"name":"Lugares de interés","item":"<?= htmlspecialchars($canonical) ?>"}]},"publisher":{"@type":"Organization","name":"Rutas Rurales","url":"https://rutasrurales.io"}}
</script>
<script>(function(){var l=function(){if(window._gtm)return;window._gtm=1;(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-MBP57VQM');};['click','scroll','keydown','touchstart'].forEach(function(e){window.addEventListener(e,function(){setTimeout(l,1e3)},{once:true,passive:true});});setTimeout(l,8000);})();</script>
</head>
<body>
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MBP57VQM" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

<header class="lug-nav" role="banner">
  <a href="https://rutasrurales.io/" class="lug-nav__logo" aria-label="Rutas Rurales - Inicio">
    <img src="/menu_images/Logo%20transparente.webp" alt="Rutas Rurales" width="38" height="38" loading="eager">
    <span>Rutas Rurales</span>
  </a>
  <nav class="lug-nav__links" aria-label="Menú principal">
    <a href="/alojamientos/">🏡 Alojamientos</a>
    <a href="/eventos/">🎭 Eventos</a>
    <a href="/lugares/" aria-current="page">📍 Lugares</a>
    <a href="/actividades/">🥾 Actividades</a>
    <a href="/rutas.php">🗺️ Mapa</a>
    <a href="/login.html" class="lug-nav__cta" rel="nofollow">Acceder</a>
  </nav>
</header>

<main id="main-content">

<section class="lug-hero" id="inicio" aria-labelledby="lug-h1">
  <div class="lug-hero__bg" aria-hidden="true">
    <img src="/menu_images/hero_main.webp"
         alt="Lugares de interés y monumentos en España rural"
         width="1200" height="500" loading="eager" fetchpriority="high">
    <div class="lug-hero__overlay"></div>
  </div>
  <div class="lug-hero__inner">
    <nav class="lug-bc" aria-label="Ruta de navegación">
      <ol>
        <li><a href="https://rutasrurales.io/">Inicio</a></li>
        <li aria-hidden="true" style="padding:0 4px">›</li>
        <li><span aria-current="page" style="color:#fff">Lugares de interés</span></li>
      </ol>
    </nav>
    <h1 id="lug-h1">Lugares de Interés en España</h1>
    <p class="lug-hero__sub">Monumentos históricos, espacios naturales, restaurantes con encanto, bodegas y rincones únicos del turismo rural español.</p>
    <?php if ($total_places): ?>
    <div class="lug-hero__stats" aria-label="Estadísticas">
      <div><span class="lug-stat__val"><?= htmlspecialchars($total_places) ?></span><span class="lug-stat__lbl">Lugares</span></div>
      <div><span class="lug-stat__val"><?= count($provincias) ?></span><span class="lug-stat__lbl">Provincias</span></div>
      <div><span class="lug-stat__val"><?= count($tipos) ?></span><span class="lug-stat__lbl">Categorías</span></div>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- TIPOS DE LUGAR — desde categories_places -->
<section class="lug-section lug-section--alt" aria-labelledby="tipos-h2">
  <div class="lug-wrap">
    <h2 class="lug-h2" id="tipos-h2">🗂️ Explorar por tipo</h2>
    <p class="lug-intro">Encuentra lugares según tu interés: patrimonio, gastronomía, naturaleza y más.</p>
    <ul class="lug-tipos" role="list" aria-label="Tipos de lugares de interés">
      <?php foreach ($tipos as $td): ?>
      <li>
        <a href="/lugares/<?= htmlspecialchars($td['slug']) ?>"
           class="lug-tipo"
           title="<?= htmlspecialchars($td['name']) ?> en España">
          <span class="lug-tipo__icon" aria-hidden="true"><?= obtenerEmojiLugar($td['icon'] ?? '') ?></span>
          <span class="lug-tipo__info">
            <span class="lug-tipo__nm"><?= htmlspecialchars($td['name']) ?></span>
            <?php if (!empty($td['description'])): ?>
            <span class="lug-tipo__desc"><?= htmlspecialchars($td['description']) ?></span>
            <?php endif; ?>
            <?php if (!empty($td['total'])): ?>
            <span class="lug-tipo__count"><?= (int)$td['total'] ?> lugares</span>
            <?php endif; ?>
          </span>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<!-- PROVINCIAS — desde places_of_interest -->
<section class="lug-section" aria-labelledby="prov-h2">
  <div class="lug-wrap">
    <h2 class="lug-h2" id="prov-h2">📍 Explorar por provincia</h2>
    <p class="lug-intro">Descubre los lugares de interés más destacados de cada provincia española.</p>
    <ul class="lug-provs" role="list" aria-label="Provincias con lugares de interés">
      <?php foreach ($provincias as $pv): ?>
      <?php $pslug = lug_to_slug($pv['province']); ?>
      <li>
        <a href="/lugares/<?= htmlspecialchars($pslug) ?>"
           class="lug-prov"
           title="Lugares de interés en <?= htmlspecialchars($pv['province']) ?>">
          <span class="lug-prov__em" aria-hidden="true"><?= lug_province_emoji($pv['province']) ?></span>
          <span class="lug-prov__nm"><?= htmlspecialchars($pv['province']) ?></span>
          <?php if (!empty($pv['total'])): ?>
          <span class="lug-prov__count"><?= (int)$pv['total'] ?> lugares</span>
          <?php endif; ?>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<!-- CTA -->
<section class="lug-cta" aria-label="Añadir un lugar">
  <h2>¿Conoces un lugar con encanto?</h2>
  <p>Añade tu restaurante, bodega, monumento o espacio natural y compártelo con viajeros de toda España.</p>
  <a href="/agregar-lugar-interes.html" class="lug-btn lug-btn--w">Añadir un lugar</a>
  <a href="/rutas.php?alojamientos=0&lugares=1&actividades=0&eventos=0" class="lug-btn lug-btn--o">🗺️ Ver en el mapa</a>
</section>

</main>

<footer class="lug-footer" role="contentinfo">
  <div class="lug-footer__inner">
    <nav class="lug-footer__nav" aria-label="Navegación del pie">
      <a href="https://rutasrurales.io/">Inicio</a>
      <a href="/alojamientos/">Alojamientos</a>
      <a href="/eventos/">Eventos</a>
      <a href="/lugares/" aria-current="page">Lugares</a>
      <a href="/actividades/">Actividades</a>
      <a href="/aviso-legal.html">Aviso Legal</a>
    </nav>
    <p>© <?= date('Y') ?> <strong style="color:#fff">rutasrurales.io</strong></p>
  </div>
</footer>

<script>if('serviceWorker' in navigator){window.addEventListener('load',function(){navigator.serviceWorker.register('/sw.js').catch(function(){});});}</script>
</body>
</html>
