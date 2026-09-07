<?php
/**
 * /lugares/{slug} — Landing de Lugares de Interés
 * Maneja dos modos:
 *   - Categoría:  /lugares/patrimonio  → lista lugares de esa category_places
 *   - Provincia:  /lugares/soria       → lista lugares de esa provincia
 *
 * URL canónica: https://rutasrurales.io/lugares/{slug}
 */
ini_set('display_errors', 0); error_reporting(E_ERROR | E_PARSE);

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
    require_once dirname(__DIR__) . '/api/config.php';
    $pdo = getDBConnection();

    // 1. Intentar como categoría (categories_places.slug)
    $sc = $pdo->prepare(
        "SELECT id, name, slug, icon, description FROM categories_places
         WHERE slug = ? AND is_active = 1 LIMIT 1"
    );
    $sc->execute([$slug]);
    $cat = $sc->fetch(PDO::FETCH_ASSOC);

    if ($cat) {
        $mode         = 'categoria';
        $category     = $cat;
        $cat_icon     = !empty($cat['icon']) ? $cat['icon'] : '📍';
        $bc_label     = $cat['name'];
        $page_h1      = $cat['name'] . ' en España';
        $meta_title   = $cat['name'] . ' en España | Rutas Rurales';
        $meta_desc    = 'Descubre los mejores lugares de ' . $cat['name'] . ' en España rural: monumentos, naturaleza, gastronomía y más.';

        $sp = $pdo->prepare(
            "SELECT p.id, p.slug, p.name, p.municipality, p.province,
                    p.short_description, p.photo1, p.entry_fee
             FROM places_of_interest p
             WHERE p.category_id = ? AND p.is_active = 1
             ORDER BY p.name ASC
             LIMIT 60"
        );
        $sp->execute([$cat['id']]);
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
            $page_h1    = 'Lugares de Interés en ' . $province_label;
            $meta_title = 'Lugares de Interés en ' . $province_label . ' | Rutas Rurales';
            $meta_desc  = 'Descubre los mejores lugares de interés en ' . $province_label
                        . ': monumentos históricos, naturaleza, gastronomía y rincones únicos del turismo rural.';

            $sp2 = $pdo->prepare(
                "SELECT p.id, p.slug, p.name, p.municipality, p.province,
                        p.short_description, p.photo1, p.entry_fee,
                        c.name AS category_name, c.icon AS category_icon
                 FROM places_of_interest p
                 LEFT JOIN categories_places c ON p.category_id = c.id
                 WHERE p.province = ? AND p.is_active = 1
                 ORDER BY p.name ASC
                 LIMIT 80"
            );
            $sp2->execute([$province_label]);
            $places = $sp2->fetchAll(PDO::FETCH_ASSOC);
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

$og_image = $base_domain . '/menu_images/og-default.jpg';
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

<style>
@font-face{font-family:'Montserrat';font-style:normal;font-weight:400;font-display:swap;src:local('Montserrat Regular'),url('/fonts/montserrat-v31-latin-regular.woff2') format('woff2')}
@font-face{font-family:'Montserrat';font-style:normal;font-weight:600;font-display:swap;src:local('Montserrat SemiBold'),url('/fonts/montserrat-v31-latin-600.woff2') format('woff2')}
@font-face{font-family:'Montserrat';font-style:normal;font-weight:800;font-display:swap;src:local('Montserrat ExtraBold'),url('/fonts/montserrat-v31-latin-800.woff2') format('woff2')}
</style>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --primary:#2F5233;--primary-dark:#1a3d1e;--accent:#81C784;
  --white:#fff;--bg:#f8f9fa;--bg-alt:#f0f4f1;
  --text:#2d3436;--text-light:#636e72;--border:#e8eaed;
  --radius:14px;--radius-sm:8px;--shadow:0 2px 12px rgba(0,0,0,.07);
  --max-w:1200px;--tr:.18s ease;--lug:#1a3a5c
}
html{scroll-behavior:smooth}
body{font-family:'Montserrat','Segoe UI',system-ui,sans-serif;background:var(--bg);color:var(--text);line-height:1.65;overflow-x:hidden;-webkit-font-smoothing:antialiased}
img{display:block;max-width:100%;height:auto}a{color:var(--primary);text-decoration:none}ul{list-style:none;padding:0;margin:0}

/* Navbar */
.ll-nav{position:sticky;top:0;z-index:900;background:var(--white);border-bottom:1px solid var(--border);height:60px;display:flex;align-items:center;padding:0 20px;gap:14px;box-shadow:0 1px 6px rgba(0,0,0,.06)}
.ll-nav__logo{display:flex;align-items:center;gap:10px;font-weight:800;color:var(--primary);font-size:1rem;flex-shrink:0}
.ll-nav__logo img{width:38px;height:38px;border-radius:50%;object-fit:cover}
.ll-nav__links{display:flex;align-items:center;gap:4px;margin-left:auto;font-size:.8rem}
.ll-nav__links a{color:var(--text);font-weight:600;padding:6px 10px;border-radius:var(--radius-sm);white-space:nowrap;transition:background var(--tr)}
.ll-nav__links a:hover,.ll-nav__links a[aria-current]{background:var(--bg-alt);color:var(--primary)}
.ll-nav__cta{background:var(--primary)!important;color:var(--white)!important;padding:7px 14px!important;border-radius:var(--radius-sm)!important;font-weight:700!important}

/* Hero */
.ll-hero{position:relative;min-height:280px;display:flex;align-items:flex-end;overflow:hidden;background:var(--lug)}
.ll-hero__overlay{position:absolute;inset:0;background:linear-gradient(160deg,rgba(26,58,92,.92) 0%,rgba(36,80,120,.8) 55%,rgba(26,58,92,.65) 100%)}
.ll-hero__inner{position:relative;z-index:1;padding:44px 20px 40px;width:100%;max-width:var(--max-w);margin:0 auto}
.ll-bc ol{display:flex;gap:4px;flex-wrap:wrap;font-size:.75rem;color:rgba(255,255,255,.7);margin-bottom:12px;list-style:none}
.ll-bc a{color:rgba(255,255,255,.7)}
.ll-hero h1{font-size:clamp(1.5rem,3.5vw,2.4rem);font-weight:800;color:var(--white);line-height:1.15;margin-bottom:10px;text-shadow:0 2px 8px rgba(0,0,0,.3);max-width:680px}
.ll-hero__sub{font-size:clamp(.85rem,1.8vw,1rem);color:rgba(255,255,255,.85);max-width:560px;font-weight:500;line-height:1.55}
.ll-hero__badge{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);border-radius:20px;padding:4px 12px;font-size:.78rem;color:rgba(255,255,255,.9);margin-top:12px;font-weight:600}

/* Contenido */
.ll-wrap{max-width:var(--max-w);margin:0 auto;padding:0 20px}
.ll-section{padding:48px 0}

/* Grid de lugares */
.ll-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin-top:8px}
.ll-card{background:var(--white);border:1.5px solid var(--border);border-radius:var(--radius);overflow:hidden;box-shadow:var(--shadow);transition:var(--tr);text-decoration:none;color:var(--text);display:flex;flex-direction:column}
.ll-card:hover{transform:translateY(-3px);box-shadow:0 6px 24px rgba(0,0,0,.13);border-color:var(--lug)}
.ll-card__img{width:100%;height:160px;object-fit:cover;background:var(--bg-alt);flex-shrink:0}
.ll-card__img-placeholder{width:100%;height:160px;background:linear-gradient(135deg,#e8f4ea 0%,#c8e6c9 100%);display:flex;align-items:center;justify-content:center;font-size:2.4rem;flex-shrink:0}
.ll-card__body{padding:14px 16px;flex:1;display:flex;flex-direction:column;gap:4px}
.ll-card__cat{font-size:.68rem;font-weight:700;color:var(--lug);text-transform:uppercase;letter-spacing:.04em}
.ll-card__name{font-size:.9rem;font-weight:700;color:var(--text);line-height:1.3}
.ll-card__loc{font-size:.75rem;color:var(--text-light);display:flex;align-items:center;gap:4px}
.ll-card__desc{font-size:.78rem;color:var(--text-light);line-height:1.5;margin-top:4px;flex:1}
.ll-card__footer{display:flex;align-items:center;justify-content:space-between;padding:10px 16px;border-top:1px solid var(--border);font-size:.75rem}
.ll-card__cta{color:var(--lug);font-weight:700}
.ll-card__fee{color:var(--text-light)}

/* Vacío */
.ll-empty{text-align:center;padding:56px 20px;color:var(--text-light)}
.ll-empty__icon{font-size:3rem;margin-bottom:12px}
.ll-empty p{font-size:.95rem;margin-bottom:16px}
.ll-empty a{color:var(--primary);font-weight:700}

/* Volver */
.ll-back{display:inline-flex;align-items:center;gap:6px;color:var(--primary);font-weight:700;font-size:.85rem;margin-bottom:24px}
.ll-back:hover{text-decoration:underline}

/* CTA */
.ll-cta{background:linear-gradient(135deg,#1a3a5c 0%,#2563a8 100%);color:var(--white);padding:44px 20px;text-align:center;margin-top:8px}
.ll-cta h2{font-size:clamp(1.1rem,2.5vw,1.55rem);font-weight:800;margin-bottom:8px}
.ll-cta p{font-size:.88rem;opacity:.82;max-width:440px;margin:0 auto 18px;line-height:1.6}
.ll-btn{display:inline-flex;align-items:center;gap:8px;padding:11px 24px;border-radius:25px;font-weight:700;font-size:.85rem;transition:var(--tr)}
.ll-btn--w{background:var(--white);color:#1a3a5c}.ll-btn--w:hover{background:var(--accent)}
.ll-btn--o{background:transparent;border:2px solid rgba(255,255,255,.5);color:var(--white);margin-left:8px}.ll-btn--o:hover{background:rgba(255,255,255,.1)}

/* Footer */
.ll-footer{background:var(--primary-dark);color:rgba(255,255,255,.7);padding:22px 20px;font-size:.8rem}
.ll-footer__inner{max-width:var(--max-w);margin:0 auto;display:flex;flex-wrap:wrap;gap:14px;justify-content:space-between;align-items:center}
.ll-footer a{color:rgba(255,255,255,.7)}.ll-footer a:hover{color:#fff}
.ll-footer__nav{display:flex;flex-wrap:wrap;gap:10px}

@media(max-width:800px){.ll-nav__links{display:none}.ll-grid{grid-template-columns:repeat(auto-fill,minmax(240px,1fr))}}
@media(max-width:480px){.ll-hero{min-height:240px}.ll-hero__inner{padding:32px 16px 28px}.ll-section{padding:32px 0}.ll-grid{grid-template-columns:1fr}}
</style>

<script>(function(){var l=function(){if(window._gtm)return;window._gtm=1;(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-MBP57VQM');};['click','scroll','keydown','touchstart'].forEach(function(e){window.addEventListener(e,function(){setTimeout(l,1e3)},{once:true,passive:true});});setTimeout(l,8000);})();</script>
</head>
<body>
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MBP57VQM" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

<!-- NAVBAR -->
<header class="ll-nav" role="banner">
  <a href="https://rutasrurales.io/" class="ll-nav__logo" aria-label="Rutas Rurales - Inicio">
    <img src="/menu_images/Logo%20transparente.webp" alt="Rutas Rurales" width="38" height="38" loading="eager">
    <span>Rutas Rurales</span>
  </a>
  <nav class="ll-nav__links" aria-label="Menú principal">
    <a href="/alojamientos/">🏡 Alojamientos</a>
    <a href="/eventos/">🎭 Eventos</a>
    <a href="/lugares/" aria-current="true">📍 Lugares</a>
    <a href="/actividades/">🥾 Actividades</a>
    <a href="/rutas.php">🗺️ Mapa</a>
    <a href="/login.html" class="ll-nav__cta" rel="nofollow">Acceder</a>
  </nav>
</header>

<main id="main-content">

<!-- HERO -->
<section class="ll-hero" aria-labelledby="ll-h1">
  <div class="ll-hero__overlay" aria-hidden="true"></div>
  <div class="ll-hero__inner">
    <nav class="ll-bc" aria-label="Ruta de navegación">
      <ol>
        <li><a href="https://rutasrurales.io/">Inicio</a></li>
        <li aria-hidden="true" style="padding:0 4px">›</li>
        <li><a href="/lugares/">Lugares de interés</a></li>
        <li aria-hidden="true" style="padding:0 4px">›</li>
        <li><span aria-current="page" style="color:#fff"><?= htmlspecialchars($bc_label) ?></span></li>
      </ol>
    </nav>
    <h1 id="ll-h1"><?= $mode === 'categoria' ? htmlspecialchars($cat_icon) . ' ' : '' ?><?= htmlspecialchars($page_h1) ?></h1>
    <p class="ll-hero__sub"><?= htmlspecialchars($meta_desc) ?></p>
    <?php if (!empty($places)): ?>
    <span class="ll-hero__badge">📍 <?= count($places) ?> lugar<?= count($places) !== 1 ? 'es' : '' ?> encontrado<?= count($places) !== 1 ? 's' : '' ?></span>
    <?php endif; ?>
  </div>
</section>

<!-- LISTADO -->
<section class="ll-section" aria-labelledby="ll-list-h2">
  <div class="ll-wrap">
    <a href="/lugares/" class="ll-back">← Volver a Lugares de interés</a>

    <?php if (!empty($places)): ?>

    <h2 id="ll-list-h2" style="font-size:1.15rem;font-weight:800;color:var(--primary);margin-bottom:18px">
      <?php if ($mode === 'categoria'): ?>
        <?= htmlspecialchars($cat_icon) ?> <?= htmlspecialchars($category['name']) ?> en España
      <?php else: ?>
        📍 Lugares de interés en <?= htmlspecialchars($province_label) ?>
      <?php endif; ?>
    </h2>

    <ul class="ll-grid" role="list" aria-label="<?= htmlspecialchars($page_h1) ?>">
      <?php foreach ($places as $place): ?>
      <li>
        <a href="/lugar/<?= htmlspecialchars($place['slug']) ?>"
           class="ll-card"
           title="<?= htmlspecialchars($place['name']) ?>">

          <?php if (!empty($place['photo1'])): ?>
          <img class="ll-card__img"
               src="<?= htmlspecialchars($place['photo1']) ?>"
               alt="<?= htmlspecialchars($place['name']) ?>"
               width="400" height="160" loading="lazy">
          <?php else: ?>
          <div class="ll-card__img-placeholder" aria-hidden="true">
            <?= $mode === 'categoria' ? htmlspecialchars($cat_icon) : '📍' ?>
          </div>
          <?php endif; ?>

          <div class="ll-card__body">
            <?php if ($mode === 'provincia' && !empty($place['category_name'])): ?>
            <span class="ll-card__cat">
              <?= !empty($place['category_icon']) ? htmlspecialchars($place['category_icon']) . ' ' : '' ?>
              <?= htmlspecialchars($place['category_name']) ?>
            </span>
            <?php endif; ?>
            <span class="ll-card__name"><?= htmlspecialchars($place['name']) ?></span>
            <?php if (!empty($place['municipality']) || !empty($place['province'])): ?>
            <span class="ll-card__loc">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
              <?= htmlspecialchars(implode(', ', array_filter([$place['municipality'] ?? '', $place['province'] ?? '']))) ?>
            </span>
            <?php endif; ?>
            <?php if (!empty($place['short_description'])): ?>
            <p class="ll-card__desc"><?= htmlspecialchars(mb_substr($place['short_description'], 0, 110) . (mb_strlen($place['short_description']) > 110 ? '…' : '')) ?></p>
            <?php endif; ?>
          </div>

          <div class="ll-card__footer">
            <span class="ll-card__cta">Ver más →</span>
            <?php if (!empty($place['entry_fee']) && (float)$place['entry_fee'] > 0): ?>
            <span class="ll-card__fee"><?= number_format((float)$place['entry_fee'], 2, ',', '.') ?>€</span>
            <?php else: ?>
            <span class="ll-card__fee" style="color:#4caf50">Entrada libre</span>
            <?php endif; ?>
          </div>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>

    <?php else: ?>
    <div class="ll-empty" role="status">
      <div class="ll-empty__icon">🗺️</div>
      <p>Aún no hay lugares publicados en esta sección.<br>¡Sé el primero en añadir uno!</p>
      <a href="/agregar-lugar-interes.html">Añadir un lugar →</a>
    </div>
    <?php endif; ?>

    <!-- Mapa -->
    <div style="margin-top:32px;text-align:center">
      <?php if ($mode === 'provincia'): ?>
      <a href="/rutas.php?provincia=<?= urlencode($province_label) ?>&alojamientos=0&lugares=1&actividades=0&eventos=0"
         style="display:inline-flex;align-items:center;gap:8px;background:var(--primary);color:#fff;padding:11px 22px;border-radius:25px;font-weight:700;font-size:.85rem">
        🗺️ Ver en el mapa — <?= htmlspecialchars($province_label) ?>
      </a>
      <?php else: ?>
      <a href="/rutas.php?alojamientos=0&lugares=1&actividades=0&eventos=0"
         style="display:inline-flex;align-items:center;gap:8px;background:var(--primary);color:#fff;padding:11px 22px;border-radius:25px;font-weight:700;font-size:.85rem">
        🗺️ Ver todos en el mapa interactivo
      </a>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="ll-cta" aria-label="Añadir un lugar">
  <h2>¿Conoces un lugar con encanto?</h2>
  <p>Comparte tu restaurante, bodega, monumento o espacio natural con viajeros de toda España.</p>
  <a href="/agregar-lugar-interes.html" class="ll-btn ll-btn--w">Añadir un lugar</a>
  <a href="/lugares/" class="ll-btn ll-btn--o">← Ver todos los tipos</a>
</section>

</main>

<!-- FOOTER -->
<footer class="ll-footer" role="contentinfo">
  <div class="ll-footer__inner">
    <nav class="ll-footer__nav" aria-label="Navegación del pie">
      <a href="https://rutasrurales.io/">Inicio</a>
      <a href="/alojamientos/">Alojamientos</a>
      <a href="/eventos/">Eventos</a>
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
