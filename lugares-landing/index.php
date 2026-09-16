<?php
/**
 * /lugares/{slug} — Landing de Lugares de Interés
 * Maneja dos modos:
 *   - Categoría:  /lugares/patrimonio  → lista lugares de esa category_places
 *   - Provincia:  /lugares/soria       → lista lugares de esa provincia
 *
 * URL canónica: https://rutasrurales.io/lugares/{slug}
 * Multiidioma: /lugares/{slug} y /{lang}/lugares/{slug}
 */
ini_set('display_errors', 0); error_reporting(E_ERROR | E_PARSE);

// I18n Bootstrap
require_once dirname(__DIR__) . '/index/i18n/vertical-hubs.php';
$vh = vh_boot('lugares');
$lang = $vh['lang'];
$t = $vh['t'];
$path_prefix = $vh['path_prefix'];

// ── Sanitizar slug ────────────────────────────────────────────────────────────
$slug_raw = isset($_GET['slug']) ? $_GET['slug'] : '';
$slug     = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($slug_raw)));

if (empty($slug)) {
    header('Location: /lugares/', true, 302);
    exit;
}

// ── Helper: texto → slug URL ─────────────────────────────────────────────────
function ll_to_slug(string $text): string {
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = strtr($text, [
        'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',
        'à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u','ç'=>'c',
    ]);
    $text = preg_replace('/[^a-z0-9\s\-]/', '', $text);
    $text = preg_replace('/[\s\-]+/', '-', $text);
    return trim($text, '-');
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
        'fas fa-glass-wine'    => '🍾',
        'fas fa-concierge-bell' => '🍽️',
        'fas fa-ticket-alt'    => '🎢',
        'bodega', 'bodegas'    => '🍾',
        'parque', 'parques'    => '🎢',
        'restauracion'         => '🍽️',
        '🎢'                  => '🎢',
        '🍾'                  => '🍾',
        '🍽️'                  => '🍽️',
        'Bodeags'              => '🍾',  // Fix para el slug con error tipográfico
        default                => '📍',
    };
}

// ── Variables de página ───────────────────────────────────────────────────────
$base_domain = 'https://rutasrurales.io';
$canonical   = $base_domain . '/lugares/' . $slug . '/';
$mode        = null;   // 'categoria' | 'provincia'
$category    = null;
$province_label = null;
$places      = [];
$meta_title  = '';
$meta_desc   = '';
$page_h1     = '';
$cat_icon    = '📍';
$bc_label    = '';

// ── Conectar BD y detectar modo ───────────────────────────────────────────────
try {
    if (!defined('API_NO_HEADERS')) define('API_NO_HEADERS', true);
    require_once dirname(__DIR__) . '/api/config.php';
    $pdo = getDBConnection();

    // 1. Intentar como categoría (categories_places.slug)
    // Selecciona todos los campos traducibles
    $sc = $pdo->prepare(
        "SELECT id, name AS name_es, slug, icon, description AS description_es,
                name_en, description_en, name_fr, description_fr, name_de, description_de, name_zh, description_zh
         FROM categories_places
         WHERE slug = ? AND is_active = 1 LIMIT 1"
    );
    $sc->execute([$slug]);
    $cat_raw = $sc->fetch(PDO::FETCH_ASSOC);

    if ($cat_raw) {
        // Aplicar traducciones según el idioma actual
        $lang_fields = [
            'en' => ['name' => 'name_en', 'desc' => 'description_en'],
            'fr' => ['name' => 'name_fr', 'desc' => 'description_fr'],
            'de' => ['name' => 'name_de', 'desc' => 'description_de'],
            'zh' => ['name' => 'name_zh', 'desc' => 'description_zh'],
        ];
        
        $cat_name = $cat_raw['name_es'];
        $cat_desc = $cat_raw['description_es'];
        
        if ($lang !== 'es' && isset($lang_fields[$lang])) {
            $fields = $lang_fields[$lang];
            if (!empty($cat_raw[$fields['name']])) {
                $cat_name = $cat_raw[$fields['name']];
            }
            if (!empty($cat_raw[$fields['desc']])) {
                $cat_desc = $cat_raw[$fields['desc']];
            }
        }
        
        $category = [
            'id' => $cat_raw['id'],
            'slug' => $cat_raw['slug'],
            'icon' => $cat_raw['icon'],
            'name' => $cat_name,
            'description' => $cat_desc,
        ];
        
        $mode         = 'categoria';
        $cat_icon     = !empty($category['icon']) ? obtenerEmojiLugar($category['icon']) : '📍';
        $bc_label     = $category['name'];
        $page_h1      = $category['name'] . ' ' . ($t['in_spain'] ?? 'en España');
        $meta_title   = $category['name'] . ' ' . ($t['in_spain'] ?? 'en España') . ' | Rutas Rurales';
        $meta_desc    = 'Descubre los mejores lugares de ' . $category['name'] . ' ' . ($t['lug_meta_desc'] ?? 'en España rural: monumentos, naturaleza, gastronomía y más.');

        $sp = $pdo->prepare(
            "SELECT p.id, p.slug, p.name, p.municipality, p.province,
                    p.short_description, p.photo1, p.entry_fee
             FROM places_of_interest p
             WHERE p.category_id = ? AND p.is_active = 1
             ORDER BY p.name ASC
             LIMIT 60"
        );
        $sp->execute([$category['id']]);
        $places = $sp->fetchAll(PDO::FETCH_ASSOC);

    } else {
        // 2. Intentar como provincia (slug normalizado de places_of_interest.province)
        $sprovs = $pdo->query(
            "SELECT DISTINCT province FROM places_of_interest
             WHERE is_active = 1 AND province IS NOT NULL AND province != ''"
        );
        $all_provinces = $sprovs->fetchAll(PDO::FETCH_COLUMN);

        foreach ($all_provinces as $prov) {
            if (ll_to_slug($prov) === $slug) {
                $province_label = $prov;
                break;
            }
        }

        if ($province_label) {
            $mode       = 'provincia';
            $bc_label   = $province_label;
            $page_h1    = $t['lug_by_prov'] . ' ' . $province_label;
            $meta_title = $t['lug_by_prov'] . ' ' . $province_label . ' | Rutas Rurales';
            $meta_desc  = 'Descubre los mejores lugares de interés en ' . $province_label
                        . ': monumentos históricos, naturaleza, gastronomía y rincones únicos del turismo rural.';

            // Seleccionar categorías traducibles
            $sp2 = $pdo->prepare(
                "SELECT p.id, p.slug, p.name, p.municipality, p.province,
                        p.short_description, p.photo1, p.entry_fee,
                        c.name AS category_name, c.icon AS category_icon,
                        COALESCE(c.name_en, c.name) AS category_name_en,
                        COALESCE(c.name_fr, c.name) AS category_name_fr,
                        COALESCE(c.name_de, c.name) AS category_name_de,
                        COALESCE(c.name_zh, c.name) AS category_name_zh
                 FROM places_of_interest p
                 LEFT JOIN categories_places c ON p.category_id = c.id
                 WHERE p.province = ? AND p.is_active = 1
                 ORDER BY p.name ASC
                 LIMIT 80"
            );
            $sp2->execute([$province_label]);
            $places_raw = $sp2->fetchAll(PDO::FETCH_ASSOC);
            
            // Aplicar traducciones a las categorías de cada lugar
            $cat_field = 'category_name_' . $lang;
            foreach ($places_raw as &$place) {
                if ($lang !== 'es' && !empty($place[$cat_field])) {
                    $place['category_name'] = $place[$cat_field];
                }
            }
            unset($place);
            $places = $places_raw;
        }
    }

    // Si no coincide ni categoría ni provincia → hub
    if (!$mode) {
        header('Location: /lugares/', true, 302);
        exit;
    }

} catch (Throwable $e) {
    header('Location: /lugares/', true, 302);
    exit;
}

$og_image = !empty($places[0]['photo1'])
    ? $places[0]['photo1']
    : $base_domain . '/menu_images/og-default.jpg';
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- ── SEO primario ──────────────────────────────────────────────── -->
<title><?= htmlspecialchars($meta_title) ?></title>
<meta name="description" content="<?= htmlspecialchars($meta_desc) ?>">
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
<link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">

<!-- ── Open Graph ────────────────────────────────────────────────── -->
<meta property="og:type"         content="website">
<meta property="og:title"        content="<?= htmlspecialchars($meta_title) ?>">
<meta property="og:description"  content="<?= htmlspecialchars($meta_desc) ?>">
<meta property="og:image"        content="<?= htmlspecialchars($og_image) ?>">
<meta property="og:image:width"  content="1200">
<meta property="og:image:height" content="630">
<meta property="og:url"          content="<?= htmlspecialchars($canonical) ?>">
<meta property="og:site_name"    content="Rutas Rurales">

<!-- ── Twitter Card ──────────────────────────────────────────────── -->
<meta name="twitter:card"        content="summary_large_image">
<meta name="twitter:title"       content="<?= htmlspecialchars($meta_title) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($meta_desc) ?>">
<meta name="twitter:image"       content="<?= htmlspecialchars($og_image) ?>">

<!-- ── Favicon ───────────────────────────────────────────────────── -->
<link rel="icon"             href="/menu_images/Favicon.png" type="image/png">
<link rel="apple-touch-icon" href="/menu_images/Favicon.png">
<link rel="manifest"         href="/manifest.json">
<meta name="theme-color"     content="#2F5233">

<!-- ── Preload primera imagen del listado (LCP candidato) ────────── -->
<?php if (!empty($places[0]['photo1'])): ?>
<link rel="preload" as="image" href="<?= htmlspecialchars($places[0]['photo1']) ?>">
<?php endif; ?>

<!-- ── JSON-LD ───────────────────────────────────────────────────── -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "CollectionPage",
  "name": "<?= htmlspecialchars($page_h1) ?>",
  "description": "<?= htmlspecialchars($meta_desc) ?>",
  "url": "<?= htmlspecialchars($canonical) ?>",
  "inLanguage": "es",
  "breadcrumb": {
    "@type": "BreadcrumbList",
    "itemListElement": [
      {"@type":"ListItem","position":1,"name":"Inicio","item":"https://rutasrurales.io/"},
      {"@type":"ListItem","position":2,"name":"Lugares de interés","item":"https://rutasrurales.io/lugares/"},
      {"@type":"ListItem","position":3,"name":"<?= htmlspecialchars($bc_label) ?>","item":"<?= htmlspecialchars($canonical) ?>"}
    ]
  },
  "publisher": {"@type":"Organization","name":"Rutas Rurales","url":"https://rutasrurales.io"}
}
</script>

<!-- ── Fuentes ────────────────────────────────────────────────────── -->
<style>
@font-face{font-family:'Montserrat';font-style:normal;font-weight:400;font-display:swap;
  src:local('Montserrat Regular'),url('/fonts/montserrat-v31-latin-regular.woff2') format('woff2')}
@font-face{font-family:'Montserrat';font-style:normal;font-weight:600;font-display:swap;
  src:local('Montserrat SemiBold'),url('/fonts/montserrat-v31-latin-600.woff2') format('woff2')}
@font-face{font-family:'Montserrat';font-style:normal;font-weight:800;font-display:swap;
  src:local('Montserrat ExtraBold'),url('/fonts/montserrat-v31-latin-800.woff2') format('woff2')}
</style>

<!-- ── CSS crítico inline — above the fold ───────────────────────── -->
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --primary:#2F5233;--primary-dark:#1a3d1e;--primary-light:#3d6b42;
  --accent:#81C784;--accent-warm:#F9A825;
  --white:#fff;--bg:#f8f9fa;--text:#333;--text-light:#666;--text-muted:#999;
  --border:#e8eaed;--radius:12px;--radius-sm:8px;
  --shadow:0 2px 12px rgba(0,0,0,.07);--shadow-hover:0 6px 24px rgba(0,0,0,.12);
  --transition:.18s ease;--max-w:1200px
}
body{font-family:'Montserrat','Segoe UI',sans-serif;background:var(--bg);color:var(--text);line-height:1.6;overflow-x:hidden;-webkit-font-smoothing:antialiased}
img{display:block;max-width:100%;height:auto}
a{color:var(--primary);text-decoration:none}
ul,ol{list-style:none;margin:0;padding:0}

/* Navbar */
.lnd-navbar{position:sticky;top:0;z-index:900;background:var(--white);
  border-bottom:1px solid var(--border);height:64px;display:flex;
  align-items:center;padding:0 20px;gap:16px;
  box-shadow:0 1px 4px rgba(0,0,0,.06);contain:layout style}
.lnd-navbar__logo{display:flex;align-items:center;gap:10px;font-weight:800;
  color:var(--primary);font-size:1rem;text-decoration:none;flex-shrink:0}
.lnd-navbar__logo img{width:40px;height:40px;border-radius:50%;object-fit:cover}
.lnd-navbar__nav{margin-left:auto;display:flex;gap:6px;align-items:center;font-size:.85rem}
.lnd-navbar__nav a{color:var(--text);font-weight:600;padding:6px 10px;border-radius:var(--radius-sm);
  white-space:nowrap;transition:background var(--transition),color var(--transition)}
.lnd-navbar__nav a:hover,.lnd-navbar__nav a[aria-current]{background:var(--bg);color:var(--primary)}
.lnd-navbar__cta{background:var(--primary)!important;color:var(--white)!important;
  padding:8px 16px!important;border-radius:var(--radius-sm)!important;font-weight:700!important}
.lnd-navbar__cta:hover{background:var(--primary-light)!important}

/* Hero con imagen de fondo */
.lnd-hero{
  position:relative;overflow:hidden;
  background:linear-gradient(135deg,var(--primary-dark) 0%,var(--primary) 60%,var(--primary-light) 100%);
  color:var(--white);min-height:320px;display:flex;flex-direction:column;justify-content:flex-end;
  contain:layout style
}
.lnd-hero__bg-wrap{position:absolute;inset:0;z-index:0;overflow:hidden}
.lnd-hero__bg-img{width:100%;height:100%;object-fit:cover;object-position:center 40%;display:block}
.lnd-hero::after{content:'';position:absolute;inset:0;z-index:1;
  background:linear-gradient(to bottom,rgba(10,25,47,.5) 0%,rgba(10,25,47,.28) 40%,rgba(10,25,47,.72) 100%);
  pointer-events:none}
.lnd-hero__content{position:relative;z-index:2;padding:52px 20px 48px;width:100%;max-width:var(--max-w);margin:0 auto}

/* Breadcrumb */
.lnd-breadcrumb ol{display:flex;flex-wrap:wrap;gap:4px;align-items:center;
  font-size:.78rem;margin-bottom:16px;color:rgba(255,255,255,.75)}
.lnd-breadcrumb a{color:rgba(255,255,255,.75)}
.lnd-breadcrumb a:hover{color:#fff}
.lnd-bc-sep{color:rgba(255,255,255,.45);margin:0 2px}

/* H1 hero */
.lnd-hero__h1{font-size:clamp(1.6rem,4vw,2.6rem);font-weight:800;line-height:1.15;
  margin:0 0 12px;text-shadow:0 2px 6px rgba(0,0,0,.25);max-width:720px}
.lnd-hero__sub{font-size:clamp(.85rem,1.8vw,1rem);color:rgba(255,255,255,.88);
  max-width:600px;font-weight:500;line-height:1.55;margin-bottom:16px}

/* Badge contador */
.lnd-hero__badge{display:inline-flex;align-items:center;gap:6px;
  background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.28);
  border-radius:20px;padding:5px 14px;font-size:.78rem;color:rgba(255,255,255,.92);font-weight:600}

/* Grid tarjetas */
.lnd-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:24px}

/* Tarjeta */
.lnd-card{background:var(--white);border-radius:var(--radius);box-shadow:var(--shadow);
  overflow:hidden;display:flex;flex-direction:column;
  transition:box-shadow var(--transition),transform var(--transition);contain:layout style}
.lnd-card:hover{box-shadow:var(--shadow-hover);transform:translateY(-3px)}

/* Imagen con aspect-ratio fijo — CLS = 0 */
.lnd-card__img-wrap{position:relative;display:block;overflow:hidden;
  aspect-ratio:3/2;background:#e8f4ea;flex-shrink:0}
.lnd-card__img{width:100%;height:100%;object-fit:cover;transition:transform .3s ease}
.lnd-card:hover .lnd-card__img{transform:scale(1.04)}
.lnd-card__img-placeholder{width:100%;height:100%;display:flex;align-items:center;
  justify-content:center;font-size:2.6rem;background:linear-gradient(135deg,#e8f4ea 0%,#c8e6c9 100%)}

/* Badge tipo sobre la imagen */
.lnd-card__type-badge{position:absolute;top:10px;left:10px;
  background:rgba(47,82,51,.88);backdrop-filter:blur(4px);
  color:var(--white);font-size:.7rem;font-weight:700;padding:3px 9px;
  border-radius:12px;text-transform:uppercase;letter-spacing:.4px}

/* Badge entrada libre */
.lnd-card__free-badge{position:absolute;top:10px;right:10px;
  background:rgba(46,125,50,.88);backdrop-filter:blur(4px);
  color:var(--white);font-size:.68rem;font-weight:700;padding:3px 9px;border-radius:12px}

/* Cuerpo tarjeta */
.lnd-card__body{padding:16px;display:flex;flex-direction:column;gap:6px;flex:1}
.lnd-card__location{display:flex;align-items:center;gap:4px;font-size:.75rem;color:var(--text-muted);margin:0}
.lnd-card__location svg{color:var(--primary);flex-shrink:0}
.lnd-card__name{font-size:1rem;font-weight:700;color:var(--text);line-height:1.3;margin:0}
.lnd-card__desc{font-size:.82rem;color:var(--text-light);line-height:1.55;margin:0;flex:1}

/* Footer tarjeta */
.lnd-card__footer{display:flex;align-items:center;justify-content:space-between;gap:8px;
  margin-top:auto;padding:10px 16px;border-top:1px solid var(--border)}
.lnd-card__cta{color:var(--primary);font-weight:700;font-size:.78rem;
  display:inline-flex;align-items:center;gap:4px}
.lnd-card__price{font-size:.8rem;color:var(--text-muted)}
.lnd-card__price--free{color:#2e7d32;font-weight:700}

/* Sin resultados */
.lnd-no-results{text-align:center;padding:60px 20px;background:var(--white);
  border-radius:var(--radius);box-shadow:var(--shadow)}
.lnd-no-results__icon{font-size:3rem;margin:0 0 12px}
.lnd-no-results__h3{font-size:1.2rem;color:var(--primary);margin:0 0 8px}
.lnd-no-results__p{color:var(--text-light);margin:0 0 20px;max-width:480px;margin-inline:auto}
</style>

<!-- ── CSS no-crítico (carga asíncrona) ───────────────────────────── -->
<link rel="stylesheet"
      href="/alojamientos-landing/css/landing.css"
      media="print"
      onload="this.media='all'">
<noscript><link rel="stylesheet" href="/alojamientos-landing/css/landing.css"></noscript>

<!-- ── GTM (diferido) ─────────────────────────────────────────────── -->
<script>(function(){
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
})();</script>
</head>
<body>

<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MBP57VQM"
  height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

<!-- ══════════════════════════════════════════════════════ NAVBAR ══ -->
<header class="lnd-navbar" role="banner">
  <a href="https://rutasrurales.io/" class="lnd-navbar__logo" aria-label="Rutas Rurales - Inicio">
    <img src="/menu_images/Logo%20transparente.webp" alt="Rutas Rurales" width="40" height="40" loading="eager">
    <span>Rutas Rurales</span>
  </a>
  <nav class="lnd-navbar__nav" aria-label="Menú principal">
    <a href="/alojamientos/">🏡 Alojamientos</a>
    <a href="/eventos/">🎭 Eventos</a>
    <a href="/lugares/" aria-current="true">📍 Lugares</a>
    <a href="/actividades/">🥾 Actividades</a>
    <a href="/rutas.php">🗺️ Mapa</a>
    <a href="/login.html" class="lnd-navbar__cta" rel="nofollow">Acceder</a>
  </nav>
</header>

<main id="main-content">

<!-- ══════════════════════════════════════════════════════ HERO ══ -->
<section class="lnd-hero" aria-labelledby="ll-h1">
  <div class="lnd-hero__bg-wrap" aria-hidden="true">
    <img class="lnd-hero__bg-img"
         src="/menu_images/hero_main.webp"
         alt=""
         width="1440" height="500"
         loading="eager"
         fetchpriority="high">
  </div>
  <div class="lnd-hero__content">
    <nav class="lnd-breadcrumb" aria-label="Ruta de navegación">
      <ol>
        <li><a href="https://rutasrurales.io/">Inicio</a></li>
        <li aria-hidden="true" class="lnd-bc-sep">›</li>
        <li><a href="/lugares/">Lugares de interés</a></li>
        <li aria-hidden="true" class="lnd-bc-sep">›</li>
        <li><span aria-current="page" style="color:#fff"><?= htmlspecialchars($bc_label) ?></span></li>
      </ol>
    </nav>
    <h1 id="ll-h1" class="lnd-hero__h1">
      <?= $mode === 'categoria' ? htmlspecialchars($cat_icon) . ' ' : '' ?><?= htmlspecialchars($page_h1) ?>
    </h1>
    <p class="lnd-hero__sub"><?= htmlspecialchars($meta_desc) ?></p>
    <?php if (!empty($places)): ?>
    <span class="lnd-hero__badge">
      📍 <?= count($places) ?> lugar<?= count($places) !== 1 ? 'es' : '' ?> encontrado<?= count($places) !== 1 ? 's' : '' ?>
    </span>
    <?php endif; ?>
  </div>
</section>

<!-- ══════════════════════════════════════════════════════ LISTING ══ -->
<section aria-labelledby="ll-list-h2">
  <div class="lnd-listing">

    <a href="/lugares/"
       style="display:inline-flex;align-items:center;gap:6px;color:var(--primary);font-weight:700;font-size:.85rem;margin-bottom:24px">
      ← Volver a Lugares de interés
    </a>

    <?php if (!empty($places)): ?>

    <div class="lnd-listing__header">
      <h2 id="ll-list-h2" class="lnd-listing__title">
        <?php if ($mode === 'categoria'): ?>
          <?= htmlspecialchars($cat_icon) ?> <?= htmlspecialchars($category['name']) ?> en España
        <?php else: ?>
          📍 Lugares de interés en <?= htmlspecialchars($province_label) ?>
        <?php endif; ?>
      </h2>
      <p class="lnd-listing__count"><?= count($places) ?> resultado<?= count($places) !== 1 ? 's' : '' ?></p>
    </div>

    <ul class="lnd-grid" role="list" aria-label="<?= htmlspecialchars($page_h1) ?>">
      <?php foreach ($places as $place): ?>
      <li>
        <a href="/lugar/<?= htmlspecialchars($place['slug']) ?>"
           class="lnd-card"
           title="<?= htmlspecialchars($place['name']) ?>">

          <!-- Imagen con aspect-ratio fijo -->
          <div class="lnd-card__img-wrap">
            <?php if (!empty($place['photo1'])): ?>
            <img class="lnd-card__img"
                 src="<?= htmlspecialchars($place['photo1']) ?>"
                 alt="<?= htmlspecialchars($place['name']) ?>"
                 width="400" height="267" loading="lazy">
            <?php else: ?>
            <div class="lnd-card__img-placeholder" aria-hidden="true">
              <?= $mode === 'categoria' ? htmlspecialchars($cat_icon) : '📍' ?>
            </div>
            <?php endif; ?>

            <?php if ($mode === 'provincia' && !empty($place['category_name'])): ?>
            <span class="lnd-card__type-badge">
              <?= !empty($place['category_icon']) ? obtenerEmojiLugar($place['category_icon']) . ' ' : '' ?><?= htmlspecialchars($place['category_name']) ?>
            </span>
            <?php endif; ?>

            <?php if (empty($place['entry_fee']) || (float)$place['entry_fee'] === 0.0): ?>
            <span class="lnd-card__free-badge">Entrada libre</span>
            <?php endif; ?>
          </div>

          <div class="lnd-card__body">
            <?php if (!empty($place['municipality']) || !empty($place['province'])): ?>
            <p class="lnd-card__location">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
              </svg>
              <?= htmlspecialchars(implode(', ', array_filter([$place['municipality'] ?? '', $place['province'] ?? '']))) ?>
            </p>
            <?php endif; ?>
            <p class="lnd-card__name"><?= htmlspecialchars($place['name']) ?></p>
            <?php if (!empty($place['short_description'])): ?>
            <p class="lnd-card__desc"><?= htmlspecialchars(mb_substr($place['short_description'], 0, 110) . (mb_strlen($place['short_description']) > 110 ? '…' : '')) ?></p>
            <?php endif; ?>
          </div>

          <div class="lnd-card__footer">
            <span class="lnd-card__cta">
              Ver más
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
              </svg>
            </span>
            <?php if (!empty($place['entry_fee']) && (float)$place['entry_fee'] > 0): ?>
            <span class="lnd-card__price"><?= number_format((float)$place['entry_fee'], 2, ',', '.') ?>€</span>
            <?php else: ?>
            <span class="lnd-card__price lnd-card__price--free">✓ Gratis</span>
            <?php endif; ?>
          </div>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>

    <?php else: ?>
    <div class="lnd-no-results" role="status">
      <div class="lnd-no-results__icon">🗺️</div>
      <h3 class="lnd-no-results__h3">Aún no hay lugares publicados aquí</h3>
      <p class="lnd-no-results__p">¡Sé el primero en añadir un lugar en esta sección!</p>
      <a href="/agregar-lugar-interes.html" class="lnd-btn lnd-btn--primary">Añadir un lugar</a>
    </div>
    <?php endif; ?>

    <!-- Ver en el mapa -->
    <?php if (!empty($places)): ?>
    <div style="margin-top:36px;text-align:center">
      <?php if ($mode === 'provincia'): ?>
      <a href="/rutas.php?provincia=<?= urlencode($province_label) ?>&alojamientos=0&lugares=1&actividades=0&eventos=0"
         class="lnd-btn lnd-btn--primary">
        🗺️ Ver en el mapa — <?= htmlspecialchars($province_label) ?>
      </a>
      <?php else: ?>
      <a href="/rutas.php?alojamientos=0&lugares=1&actividades=0&eventos=0"
         class="lnd-btn lnd-btn--primary">
        🗺️ Ver todos en el mapa interactivo
      </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </div>
</section>

<!-- ══════════════════════════════════════════════ CTA FINAL ══ -->
<section class="lnd-intro" style="border-top:3px solid var(--accent)" aria-label="Añadir un lugar">
  <div class="lnd-intro__inner" style="text-align:center;padding:48px 20px">
    <h2 class="lnd-intro__h2" style="display:block;text-align:left">¿Conoces un lugar con encanto?</h2>
    <p class="lnd-intro__p">Comparte tu restaurante, bodega, monumento o espacio natural con viajeros de toda España.</p>
    <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:16px">
      <a href="/agregar-lugar-interes.html" class="lnd-btn lnd-btn--primary">Añadir un lugar</a>
      <a href="/lugares/" class="lnd-btn lnd-btn--secondary">← Ver todos los tipos</a>
    </div>
  </div>
</section>

</main>

<!-- ══════════════════════════════════════════════════════ FOOTER ══ -->
<footer class="lnd-footer" role="contentinfo">
  <div class="lnd-footer__inner">
    <nav class="lnd-footer__links" aria-label="Navegación del pie">
      <a href="https://rutasrurales.io/">Inicio</a>
      <a href="/alojamientos/">Alojamientos</a>
      <a href="/eventos/">Eventos</a>
      <a href="/lugares/">Lugares</a>
      <a href="/actividades/">Actividades</a>
      <a href="/aviso-legal.html">Aviso Legal</a>
    </nav>
    <p class="lnd-footer__copy">© <?= date('Y') ?> <strong style="color:#fff">rutasrurales.io</strong></p>
  </div>
</footer>

<!-- Responsive extras -->
<style>
@media(max-width:800px){
  .lnd-navbar__nav a:not(.lnd-navbar__cta){font-size:.78rem;padding:5px 7px}
  .lnd-grid{grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px}
}
@media(max-width:600px){
  .lnd-navbar__nav a:not([href='/lugares/']):not([href='/login.html']):not(.lnd-navbar__cta){display:none}
  .lnd-hero__content{padding:36px 16px 32px}
  .lnd-hero{min-height:260px}
  .lnd-grid{grid-template-columns:1fr}
}
</style>

<script>if('serviceWorker' in navigator){window.addEventListener('load',function(){navigator.serviceWorker.register('/sw.js').catch(function(){});});}</script>
</body>
</html>
