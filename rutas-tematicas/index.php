<?php
/**
 * PLANTILLA UNIVERSAL DE RUTAS TEMÁTICAS
 * =======================================
 * Sirve para CUALQUIER ruta: puentes, castillos, vinos, provincias...
 * URL: /rutas/{slug}  →  rutas-tematicas/index.php?slug={slug}
 *
 * Datos desde BD: routes + route_items + JOIN con las 4 tablas reales:
 *   accommodations | places_of_interest | cultural_events | tourist_activities
 */

// ── Seguridad: sin output antes de headers ───────────────────
ini_set('display_errors', 0);
error_reporting(E_ALL);

// ── Obtener slug ─────────────────────────────────────────────
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if (empty($slug) || !preg_match('/^[a-z0-9\-]+$/', $slug)) {
    http_response_code(404);
    header('Location: https://rutasrurales.io/404.html');
    exit;
}

// ── Cargar datos desde la API interna ────────────────────────
// Usar ruta absoluta para evitar problemas con rewrites de Apache
$_BASE = dirname(__DIR__); // /home/.../Rutas
require_once $_BASE . '/api/config.php';

$ruta         = null;
$alojamientos = [];
$lugares      = [];
$actividades  = [];
$eventos      = [];
$error        = null;

try {
    $pdo = getDBConnection();

    // 1. Ruta base
    $stmt = $pdo->prepare("
        SELECT r.id, r.name, r.slug, r.description, r.duration_days,
               r.difficulty_level,
               r.status, r.views_count, r.is_public, r.is_featured,
               r.route_type, r.hero_image, r.seo_keywords,
               r.seo_title, r.seo_description, r.province,
               r.season, r.cover_color, r.itinerary_json, r.created_at
        FROM routes r
        WHERE r.slug = :slug AND r.status = 'published' AND r.is_public = 1
        LIMIT 1
    ");
    $stmt->execute([':slug' => $slug]);
    $ruta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ruta) {
        http_response_code(404);
        header('Location: https://rutasrurales.io/404.html');
        exit;
    }

    $ruta['itinerary_json'] = json_decode($ruta['itinerary_json'] ?? '[]', true);

    // 2. Items de la ruta (columnas reales confirmadas)
    $stmtItems = $pdo->prepare("
        SELECT id, item_type, item_id, title, display_order,
               day_number, time_slot, editorial_note, is_highlight
        FROM route_items
        WHERE route_id = :route_id
        ORDER BY day_number ASC, display_order ASC
    ");
    $stmtItems->execute([':route_id' => $ruta['id']]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    // Agrupar IDs por tipo (enum real: accommodation, place, activity, event)
    $ids = ['accommodation' => [], 'place' => [], 'activity' => [], 'event' => []];
    foreach ($items as $item) {
        $t = $item['item_type'];
        if (isset($ids[$t])) $ids[$t][] = (int)$item['item_id'];
    }

    // Helper: merge item con metadatos de route_items
    $mergeItem = function(array $data, array $item): array {
        return array_merge($data, [
            'item_type'      => $item['item_type'],
            'day_number'     => $item['day_number'],
            'time_slot'      => $item['time_slot'],
            'editorial_note' => $item['editorial_note'],
            'is_highlight'   => (bool)$item['is_highlight'],
            'display_order'  => $item['display_order'],
        ]);
    };

    // 3a. Alojamientos
    if (!empty($ids['accommodation'])) {
        $ph = implode(',', array_fill(0, count($ids['accommodation']), '?'));
        $rows = $pdo->prepare("
            SELECT a.id, a.name, a.slug, a.description, a.short_description,
                   a.municipality, a.province, a.address, a.phone, a.email,
                   a.website, a.price_per_night, a.capacity,
                   a.photo1, a.photo2, a.photo3, a.latitude, a.longitude,
                   c.name as category_name
            FROM accommodations a
            LEFT JOIN categories_accommodations c ON a.category_id = c.id
            WHERE a.id IN ($ph) AND a.is_active = 1
        ");
        $rows->execute($ids['accommodation']);
        $map = [];
        foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $a) {
            $a['fotos'] = array_values(array_filter([$a['photo1'], $a['photo2'], $a['photo3']]));
            $a['fotos'] = array_map(function($f) use ($a) {
                return preg_match('/^https?:\/\//', $f) ? $f
                    : 'https://rutasrurales.io/img/alojamientos/' . ($a['slug'] ?? '') . '/' . basename($f);
            }, $a['fotos']);
            $a['url'] = 'https://rutasrurales.io/alojamiento/' . ($a['slug'] ?? '');
            $map[$a['id']] = $a;
        }
        foreach ($items as $item) {
            if ($item['item_type'] === 'accommodation' && isset($map[$item['item_id']])) {
                $alojamientos[] = $mergeItem($map[$item['item_id']], $item);
            }
        }
    }

    // 3b. Lugares
    if (!empty($ids['place'])) {
        $ph = implode(',', array_fill(0, count($ids['place']), '?'));
        $rows = $pdo->prepare("
            SELECT p.id, p.name, p.slug, p.description, p.short_description,
                   p.municipality, p.province, p.address, p.phone,
                   p.website, p.opening_hours, p.entry_fee,
                   p.photo1, p.photo2, p.photo3, p.latitude, p.longitude
            FROM places_of_interest p
            WHERE p.id IN ($ph) AND p.is_active = 1
        ");
        $rows->execute($ids['place']);
        $map = [];
        foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $l) {
            $l['fotos'] = array_values(array_filter([$l['photo1'], $l['photo2'], $l['photo3']]));
            $l['fotos'] = array_map(function($f) {
                return preg_match('/^https?:\/\//', $f) ? $f
                    : 'https://rutasrurales.io/interest_places_images/' . basename($f);
            }, $l['fotos']);
            $l['precio_entrada'] = (!empty($l['entry_fee']) && $l['entry_fee'] > 0)
                ? number_format($l['entry_fee'], 2) . '€' : 'Entrada gratuita';
            $l['url'] = 'https://rutasrurales.io/lugar/' . ($l['slug'] ?? '');
            $map[$l['id']] = $l;
        }
        foreach ($items as $item) {
            if ($item['item_type'] === 'place' && isset($map[$item['item_id']])) {
                $lugares[] = $mergeItem($map[$item['item_id']], $item);
            }
        }
    }

    // 3c. Actividades
    if (!empty($ids['activity'])) {
        $ph = implode(',', array_fill(0, count($ids['activity']), '?'));
        $rows = $pdo->prepare("
            SELECT t.id, t.name, t.slug, t.description, t.short_description,
                   t.municipality, t.province, t.duration, t.difficulty_level,
                   t.price_adult, t.price_child, t.contact_phone,
                   t.website, t.booking_url,
                   t.photo1, t.photo2, t.photo3, t.latitude, t.longitude
            FROM tourist_activities t
            WHERE t.id IN ($ph) AND t.is_active = 1
        ");
        $rows->execute($ids['activity']);
        $map = [];
        foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $a) {
            $a['fotos'] = array_values(array_filter([$a['photo1'], $a['photo2'], $a['photo3']]));
            $a['fotos'] = array_map(function($f) {
                return preg_match('/^https?:\/\//', $f) ? $f
                    : 'https://rutasrurales.io/img/actividades/' . basename($f);
            }, $a['fotos']);
            $a['precio_display'] = (!empty($a['price_adult']) && $a['price_adult'] > 0)
                ? 'Desde ' . number_format($a['price_adult'], 2) . '€/persona' : 'Consultar precio';
            $a['url'] = 'https://rutasrurales.io/actividad/' . ($a['slug'] ?? '');
            $map[$a['id']] = $a;
        }
        foreach ($items as $item) {
            if ($item['item_type'] === 'activity' && isset($map[$item['item_id']])) {
                $actividades[] = $mergeItem($map[$item['item_id']], $item);
            }
        }
    }

    // 3d. Eventos
    // Prioridad 1: items de tipo 'event' añadidos manualmente en el gestor
    // Prioridad 2: búsqueda automática por fecha + provincia del itinerario
    if (!empty($ids['event'])) {
        // Eventos seleccionados manualmente → mostrar siempre estos
        $ph = implode(',', array_fill(0, count($ids['event']), '?'));
        $stmtEv2 = $pdo->prepare("
            SELECT e.id, e.name as title, e.slug, e.description, e.short_description,
                   e.venue_name, e.municipality, e.province,
                   e.start_date, e.end_date, e.start_time,
                   e.is_free, e.ticket_price, e.ticket_url,
                   e.organizer, e.poster_image, e.photo1
            FROM cultural_events e
            WHERE e.id IN ($ph) AND e.is_active = 1
            ORDER BY e.start_date ASC
        ");
        $stmtEv2->execute($ids['event']);
        foreach ($stmtEv2->fetchAll(PDO::FETCH_ASSOC) as $e) {
            $img = $e['poster_image'] ?: $e['photo1'] ?: null;
            if ($img && !preg_match('/^https?:\/\//', $img)) {
                $img = 'https://rutasrurales.io/cultural_events_images/' . basename($img);
            }
            $e['imagen'] = $img;
            $e['precio_display'] = $e['is_free'] ? 'Entrada gratuita'
                : (!empty($e['ticket_price']) ? number_format($e['ticket_price'], 2) . '€' : 'Consultar precio');
            $e['url'] = 'https://rutasrurales.io/evento/' . ($e['slug'] ?? '');
            $eventos[] = $e;
        }
    } else {
        // Búsqueda automática: SOLO si hay fechas en el itinerario Y filtra por provincia
        $fechaInicio = $fechaFin = null;
        if (!empty($ruta['itinerary_json'])) {
            $dias = $ruta['itinerary_json'];
            // Buscar primera y última fecha válida en el JSON
            foreach ($dias as $dia) {
                if (!empty($dia['fecha']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dia['fecha'])) {
                    if (!$fechaInicio) $fechaInicio = $dia['fecha'];
                    $fechaFin = $dia['fecha'];
                }
            }
        }

        if ($fechaInicio && $fechaFin && !empty($provincia)) {
            // Filtrar por fecha Y provincia para no mezclar eventos de otras zonas
            $stmtEv = $pdo->prepare("
                SELECT e.id, e.name as title, e.slug, e.description, e.short_description,
                       e.venue_name, e.municipality, e.province,
                       e.start_date, e.end_date, e.start_time,
                       e.is_free, e.ticket_price, e.ticket_url,
                       e.organizer, e.poster_image, e.photo1
                FROM cultural_events e
                WHERE e.is_active = 1
                  AND e.province = :prov
                  AND e.start_date <= :fin
                  AND COALESCE(e.end_date, e.start_date) >= :ini
                ORDER BY e.start_date ASC
                LIMIT 12
            ");
            $stmtEv->execute([':prov' => $provincia, ':ini' => $fechaInicio, ':fin' => $fechaFin]);
            foreach ($stmtEv->fetchAll(PDO::FETCH_ASSOC) as $e) {
                $img = $e['poster_image'] ?: $e['photo1'] ?: null;
                if ($img && !preg_match('/^https?:\/\//', $img)) {
                    $img = 'https://rutasrurales.io/cultural_events_images/' . basename($img);
                }
                $e['imagen'] = $img;
                $e['precio_display'] = $e['is_free'] ? 'Entrada gratuita'
                    : (!empty($e['ticket_price']) ? number_format($e['ticket_price'], 2) . '€' : 'Consultar precio');
                $e['url'] = 'https://rutasrurales.io/evento/' . ($e['slug'] ?? '');
                $eventos[] = $e;
            }
        }
        // Si no hay fechas en el itinerario o no hay provincia → no mostrar eventos aleatorios
    }

    // Incrementar visitas
    $pdo->prepare("UPDATE routes SET views_count = COALESCE(views_count,0)+1 WHERE id=:id")
        ->execute([':id' => $ruta['id']]);

} catch (PDOException $e) {
    error_log('rutas-tematicas/index.php ERROR: ' . $e->getMessage());
    $error = 'Error cargando la ruta. Por favor, inténtalo de nuevo.';
} catch (Throwable $e) {
    error_log('rutas-tematicas/index.php FATAL: ' . $e->getMessage());
    $error = 'Error cargando la ruta. Por favor, inténtalo de nuevo.';
}

// ── Variables SEO ─────────────────────────────────────────────
$metaTitle = $ruta['seo_title'] ?? ($ruta['name'] ?? 'Ruta Turística') . ' | rutasrurales.io';
$metaDesc  = $ruta['seo_description'] ?? substr(strip_tags($ruta['description'] ?? ''), 0, 300);
$metaKeys  = $ruta['seo_keywords'] ?? '';
$heroImg   = $ruta['hero_image'] ?? 'https://rutasrurales.io/menu_images/Logo%20transparente.webp';
$canonUrl  = 'https://rutasrurales.io/rutas/' . ($ruta['slug'] ?? $slug);
$provincia = $ruta['province'] ?? 'Soria';

// ── Cargar módulos ────────────────────────────────────────────
$_MODS = $_BASE . '/rutas-tematicas/modules';
require_once $_MODS . '/schema.php';
require_once $_MODS . '/hero.php';
require_once $_MODS . '/itinerario.php';
require_once $_MODS . '/alojamientos.php';
require_once $_MODS . '/lugares.php';
require_once $_MODS . '/actividades.php';
require_once $_MODS . '/eventos.php';
require_once $_MODS . '/faq.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- SEO primario -->
<title><?= htmlspecialchars($metaTitle) ?></title>
<meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
<?php if ($metaKeys): ?>
<meta name="keywords" content="<?= htmlspecialchars($metaKeys) ?>">
<?php endif; ?>
<link rel="canonical" href="<?= htmlspecialchars($canonUrl) ?>">

<!-- Open Graph -->
<meta property="og:type"        content="article">
<meta property="og:title"       content="<?= htmlspecialchars($metaTitle) ?>">
<meta property="og:description" content="<?= htmlspecialchars($metaDesc) ?>">
<meta property="og:image"       content="<?= htmlspecialchars($heroImg) ?>">
<meta property="og:url"         content="<?= htmlspecialchars($canonUrl) ?>">
<meta property="og:site_name"   content="rutasrurales.io">
<meta property="og:locale"      content="es_ES">

<!-- Twitter Card -->
<meta name="twitter:card"        content="summary_large_image">
<meta name="twitter:title"       content="<?= htmlspecialchars($metaTitle) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($metaDesc) ?>">
<meta name="twitter:image"       content="<?= htmlspecialchars($heroImg) ?>">

<!-- Favicon -->
<link rel="icon" href="/menu_images/Favicon.png" type="image/png">

<!-- Preconnect para velocidad -->
<link rel="preconnect" href="https://rutasrurales.io">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<!-- CSS crítico inline (above the fold) -->
<style>
/* ── CRÍTICO INLINE: evita render-blocking ── */
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Montserrat','Segoe UI',Tahoma,Geneva,Verdana,sans-serif;line-height:1.6;color:#1A2E1A;background:#fff}
.rt-container{max-width:1200px;margin:0 auto;padding:0 20px}
/* Navbar mínimo para LCP */
.rt-navbar{background:#2F5233;position:fixed;top:0;width:100%;z-index:1000;padding:12px 0}
.rt-navbar__inner{max-width:1200px;margin:0 auto;padding:0 20px;display:flex;align-items:center;justify-content:space-between}
.rt-navbar__logo{display:flex;align-items:center;gap:10px;text-decoration:none;color:#fff}
.rt-navbar__logo img{width:44px;height:44px;border-radius:50%}
.rt-navbar__logo-text{font-weight:800;font-size:1.1rem}
.rt-navbar__back{color:rgba(255,255,255,0.85);text-decoration:none;font-size:0.88rem;display:flex;align-items:center;gap:5px}
.rt-navbar__back:hover{color:#fff}
/* Hero placeholder para evitar CLS */
.rt-hero{min-height:520px;background:#2F5233;display:flex;align-items:center;padding:80px 0 60px}
</style>

<!-- CSS principal (no bloqueante) -->
<link rel="stylesheet" href="/rutas-tematicas/css/ruta.css" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="/rutas-tematicas/css/ruta.css"></noscript>

<!-- Schema.org JSON-LD -->
<?php if ($ruta): renderSchema($ruta, $alojamientos, $lugares, $actividades, $eventos); endif; ?>

<!-- GTM diferido -->
<script>
(function(){
  function loadGTM(){
    if(window._gtmLoaded)return; window._gtmLoaded=true;
    (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});
    var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';
    j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-MBP57VQM');
  }
  ['click','scroll','keydown','touchstart'].forEach(function(e){
    window.addEventListener(e,function(){setTimeout(loadGTM,2000)},{once:true});
  });
  setTimeout(loadGTM,8000);
})();
</script>
</head>
<body>

<!-- GTM noscript -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MBP57VQM" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

<!-- ── NAVBAR ──────────────────────────────────────────────── -->
<nav class="rt-navbar" aria-label="Navegación principal">
    <div class="rt-navbar__inner">
        <a href="https://rutasrurales.io/" class="rt-navbar__logo">
            <img src="/menu_images/Logo%20transparente.webp" alt="Rutas Rurales" width="44" height="44" loading="eager">
            <span class="rt-navbar__logo-text">Rutas</span>
        </a>
        <a href="https://rutasrurales.io/rutas/" class="rt-navbar__back">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
            Todas las rutas
        </a>
    </div>
</nav>

<?php if ($error): ?>
<!-- Error -->
<div style="padding:120px 20px;text-align:center;color:#c62828;">
    <h1>Error cargando la ruta</h1>
    <p><?= htmlspecialchars($error) ?></p>
    <a href="https://rutasrurales.io/" style="color:#2F5233;">← Volver al inicio</a>
</div>
<?php else: ?>

<!-- ── HERO ────────────────────────────────────────────────── -->
<?php renderHero($ruta); ?>

<!-- ── ITINERARIO ──────────────────────────────────────────── -->
<?php renderItinerario($ruta, $alojamientos, $lugares, $actividades, $eventos); ?>

<!-- ── ALOJAMIENTOS ────────────────────────────────────────── -->
<?php renderAlojamientos($alojamientos, $ruta); ?>

<!-- ── LUGARES DE INTERÉS ──────────────────────────────────── -->
<?php renderLugares($lugares, $ruta); ?>

<!-- ── ACTIVIDADES ─────────────────────────────────────────── -->
<?php renderActividades($actividades, $ruta); ?>

<!-- ── EVENTOS ─────────────────────────────────────────────── -->
<?php renderEventos($eventos, $ruta); ?>

<!-- ── FAQ + SEO TEXT ──────────────────────────────────────── -->
<?php renderFaq($ruta, $alojamientos, $lugares, $actividades); ?>

<!-- ── CTA FINAL ───────────────────────────────────────────── -->
<section class="rt-cta-final">
    <div class="rt-container">
        <h2 class="rt-cta-final__title">
            ¿Listo para escaparte a <?= htmlspecialchars($provincia) ?>?
        </h2>
        <p class="rt-cta-final__sub">
            Reserva tu alojamiento ahora. Las mejores casas rurales se agotan semanas antes del puente.
        </p>
        <div class="rt-cta-final__btns">
            <a href="#alojamientos" class="rt-btn rt-btn--primary" style="background:#B8956A;border-color:#B8956A;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Ver alojamientos disponibles
            </a>
            <a href="https://rutasrurales.io/alojamientos-turisticos.html" class="rt-btn rt-btn--secondary">
                Explorar todos los alojamientos
            </a>
        </div>
    </div>
</section>

<?php endif; ?>

<!-- ── FOOTER ──────────────────────────────────────────────── -->
<footer class="rt-footer">
    <div class="rt-container">
        <div class="rt-footer__links">
            <a href="https://rutasrurales.io/">Inicio</a>
            <a href="https://rutasrurales.io/alojamientos-turisticos.html">Alojamientos</a>
            <a href="https://rutasrurales.io/lugares-interes-paginacion.html">Lugares de interés</a>
            <a href="https://rutasrurales.io/eventos-culturales-paginacion.html">Eventos</a>
            <a href="https://rutasrurales.io/aviso-legal.html">Aviso Legal</a>
        </div>
        <p>&copy; <?= date('Y') ?> rutasrurales.io — Turismo rural auténtico en España</p>
    </div>
</footer>

</body>
</html>
