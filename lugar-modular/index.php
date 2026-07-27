<?php
/**
 * lugar-modular/index.php — Controlador principal
 * ================================================
 * Arquitectura modular de fichas de lugares de interés.
 * Lee $slug de la URL, carga datos de la BD y delega
 * el renderizado a los componentes de /components/.
 *
 * URL: /lugar/{slug}  →  servida por .htaccess o router PHP
 *
 * Componentes:
 *   components/schema.php      — JSON-LD (TouristAttraction, FAQPage, BreadcrumbList, WebPage)
 *   components/head.php        — <head> SEO, OG, hreflang, CSS, JS globals, traducciones $ui
 *   components/hero.php        — Hero imagen + H1 + breadcrumb + badges
 *   components/galeria.php     — Galería de fotos + miniaturas
 *   components/descripcion.php — Descripción + info práctica + contacto + mapa
 *   components/sidebar.php     — Info rápida + CTA + compartir
 *   components/cercanos.php    — Skeleton screens nearby (AJAX via lugar.js)
 *   components/footer.php      — Lightbox + Toast + Leaflet CSS + lugar.js
 *
 * NO modificar la lógica de DB aquí — hacerlo en api/lugar-data.php
 */

declare(strict_types=1);
header('Content-Type: text/html; charset=UTF-8');

// ─── HELPERS ─────────────────────────────────────────────────────────────────

/**
 * Escapa un string para salida HTML segura.
 */
function esc(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ─── SEGURIDAD: SLUG ─────────────────────────────────────────────────────────

$slug = trim($_GET['slug'] ?? '');

// Limpiar slug: solo letras, números, guiones y puntos
$slug = preg_replace('/[^a-z0-9\-\.\_]/i', '', $slug);

if (empty($slug)) {
    http_response_code(400);
    exit('<p>Slug inválido.</p>');
}

// ─── IDIOMA ───────────────────────────────────────────────────────────────────
// Detecta lang desde: 1) ?lang=xx  2) prefijo de URL  3) Accept-Language  4) es
$lang = 'es';
$langAllowed = ['es', 'en', 'fr', 'de', 'zh'];

if (!empty($_GET['lang']) && in_array($_GET['lang'], $langAllowed, true)) {
    $lang = $_GET['lang'];
} elseif (!empty($_SERVER['REQUEST_URI'])) {
    // Soporta URLs: /en/lugar/xxx, /fr/lugar/xxx ...
    if (preg_match('#^/(' . implode('|', $langAllowed) . ')/#', $_SERVER['REQUEST_URI'], $m)) {
        $lang = $m[1];
    }
}

// ─── CARGA DE DATOS (BD) ─────────────────────────────────────────────────────
// Incluir el helper de conexión y hacer la consulta
$lugar  = [];
$fotos  = [];

// Require la conexión a BD (ajustar ruta si difiere en el servidor)
$dbPath = dirname(__DIR__) . '/api/db.php';
if (!file_exists($dbPath)) {
    // Intentar ruta alternativa
    $dbPath = dirname(__DIR__) . '/includes/db.php';
}

if (file_exists($dbPath)) {
    require_once $dbPath;

    // Consulta con todos los campos necesarios
    $stmt = $pdo->prepare("
        SELECT
            p.*,
            c.name AS category_name
        FROM places_of_interest p
        LEFT JOIN poi_categories c ON p.category_id = c.id
        WHERE p.slug = :slug
          AND (p.status IS NULL OR p.status != 'deleted')
        LIMIT 1
    ");
    $stmt->execute([':slug' => $slug]);
    $lugar = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    // Fotos: puede ser JSON array o columna photo_url/main_photo
    if (!empty($lugar)) {
        if (!empty($lugar['photos'])) {
            $decoded = json_decode($lugar['photos'], true);
            $fotos   = is_array($decoded) ? $decoded : [$lugar['photos']];
        } elseif (!empty($lugar['photo_url'])) {
            $fotos = [$lugar['photo_url']];
        } elseif (!empty($lugar['main_photo'])) {
            $fotos = [$lugar['main_photo']];
        }
        $fotos = array_values(array_filter($fotos));
    }
} else {
    // Fallback: sin BD (modo desarrollo / preview estático)
    error_log('[lugar-modular] No se encontró db.php en: ' . $dbPath);
}

// ─── VARIABLES SEO ────────────────────────────────────────────────────────────

$baseUrl   = 'https://rutasrurales.io';
$canonical = $lang === 'es'
    ? $baseUrl . '/lugar/' . $slug
    : $baseUrl . '/' . $lang . '/lugar/' . $slug;

// Título SEO dinámico
$municipio   = $lugar['municipality'] ?? '';
$provincia   = $lugar['province']     ?? '';
$categoryName = $lugar['category_name'] ?? '';
$nombreLugar  = $lugar['name'] ?? ucwords(str_replace('-', ' ', $slug));

if ($lang === 'es') {
    $page_title = $nombreLugar
        . (!empty($municipio) ? ' en ' . $municipio : '')
        . (!empty($provincia) ? ' (' . $provincia . ')' : '')
        . ' — Rutas Rurales';
} elseif ($lang === 'en') {
    $page_title = $nombreLugar
        . (!empty($municipio) ? ' in ' . $municipio : '')
        . ' | Rural Routes Spain';
} elseif ($lang === 'fr') {
    $page_title = $nombreLugar
        . (!empty($municipio) ? ' à ' . $municipio : '')
        . ' | Rutas Rurales Espagne';
} elseif ($lang === 'de') {
    $page_title = $nombreLugar
        . (!empty($municipio) ? ' in ' . $municipio : '')
        . ' | Rutas Rurales Spanien';
} else {
    $page_title = $nombreLugar . ' — Rutas Rurales';
}

// Descripción SEO
$descSeo = '';
if (!empty($lugar['description'])) {
    $descSeo = mb_substr(strip_tags($lugar['description']), 0, 160);
}
if (empty($descSeo)) {
    $descSeo = 'Descubre ' . $nombreLugar
        . (!empty($municipio) ? ' en ' . $municipio : '')
        . (!empty($provincia) ? ', ' . $provincia : '')
        . '. Información práctica, horarios, fotos y cómo llegar. Planifica tu visita con Rutas Rurales.';
}
$page_description = $descSeo;

// Foto OG (primera foto o genérica)
$foto_og = !empty($fotos[0])
    ? (preg_match('/^https?:\/\//', $fotos[0]) ? $fotos[0] : $baseUrl . '/' . ltrim($fotos[0], '/'))
    : $baseUrl . '/menu_images/turismo_rural.webp';

// Datos JS para window.LUG_DATA (expone lat/lng/slug al JS de la página)
$lugar_js = json_encode([
    'slug'      => $slug,
    'name'      => $lugar['name']      ?? '',
    'lat'       => !empty($lugar['latitude'])  ? (float)$lugar['latitude']  : null,
    'lng'       => !empty($lugar['longitude']) ? (float)$lugar['longitude'] : null,
    'photos'    => $fotos,
    'lang'      => $lang,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// ─── CARGAR SCHEMA (debe estar disponible antes de head.php) ─────────────────
require_once __DIR__ . '/components/schema.php';

// ─── HEAD (incluye traducciones $ui → $t, CSS, meta, JSON-LD) ────────────────
require_once __DIR__ . '/components/head.php';
// Nota: head.php define $t (traducciones del idioma activo) y cierra </head>

// ─── BODY ────────────────────────────────────────────────────────────────────
?>
<body>

<?php // GTM noscript — justo al abrir <body> ?>
<noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-XXXXXXX"
            height="1" width="1" style="display:none;visibility:hidden"
            title="Google Tag Manager"></iframe>
</noscript>

<?php
// ─── MENÚ DE NAVEGACIÓN ───────────────────────────────────────────────────────
// Reutiliza el header/nav global del proyecto (menú responsivo ya existente)
$globalHeader = dirname(__DIR__) . '/header.php';
if (file_exists($globalHeader)) {
    include $globalHeader;
}
?>

<!-- ══════════════════════════════════════════════════════
     CONTENIDO PRINCIPAL
     ══════════════════════════════════════════════════════ -->
<div class="lug-page">

<?php if (!empty($lugar)): ?>

    <!-- ── HERO ── -->
    <?php require __DIR__ . '/components/hero.php'; ?>

    <div class="lug-layout">

        <!-- ── COLUMNA PRINCIPAL ── -->
        <main id="main-content">

            <?php require __DIR__ . '/components/galeria.php'; ?>
            <?php require __DIR__ . '/components/descripcion.php'; ?>
            <?php require __DIR__ . '/components/cercanos.php'; ?>

        </main><!-- /#main-content -->

        <!-- ── SIDEBAR ── -->
        <?php require __DIR__ . '/components/sidebar.php'; ?>

    </div><!-- /.lug-layout -->

<?php else: ?>

    <!-- ── ERROR 404 ── -->
    <?php http_response_code(404); ?>
    <div class="error-container">
        <div class="error-icon" aria-hidden="true">😕</div>
        <h1><?php echo isset($t) ? esc($t['no_encontrado_h1']) : 'Lugar no encontrado'; ?></h1>
        <p><?php echo isset($t) ? esc($t['no_encontrado_p']) : 'El lugar de interés que buscas no existe o ya no está disponible.'; ?></p>
        <a href="/lugares-de-interes" class="btn-back">
            <?php echo isset($t) ? esc($t['volver_lista']) : '← Volver a los lugares de interés'; ?>
        </a>
    </div>

<?php endif; ?>

</div><!-- /.lug-page -->

<!-- ── FOOTER COMPARTIDO ── -->
<?php
$globalFooter = dirname(__DIR__) . '/footer.php';
if (file_exists($globalFooter)) {
    include $globalFooter;
}
?>

<!-- ── COMPONENTES FINALES: lightbox, toast, scripts ── -->
<?php require __DIR__ . '/components/footer.php'; ?>
