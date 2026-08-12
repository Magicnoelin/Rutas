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

// Suprimir warnings en producción (mismo patrón que alojamiento-modular)
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

header('Content-Type: text/html; charset=UTF-8');

// ─── HELPERS ─────────────────────────────────────────────────────────────────

/**
 * Escapa un valor para salida HTML segura.
 * Acepta string|null para evitar TypeError en PHP 8 cuando la clave no existe.
 */
function esc(?string $str): string {
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
$lugar = [];
$fotos = [];

// Mismo config que todo el proyecto: api/config.php con getDBConnection()
// Evitar redefinir la constante si ya fue incluido por otro componente
if (!defined('API_NO_HEADERS')) {
    define('API_NO_HEADERS', true);
}
require_once dirname(__DIR__) . '/api/config.php';

try {
    $pdo = getDBConnection();

    // ── Consulta principal del lugar ──────────────────────────────────────────
    // Tabla: places_of_interest + categories_places (según lugar-data.php)
    $stmt = $pdo->prepare("
        SELECT p.*, c.name AS category_name
        FROM places_of_interest p
        LEFT JOIN categories_places c ON p.category_id = c.id
        WHERE p.slug = ? AND p.is_active = 1
        LIMIT 1
    ");
    $stmt->execute([$slug]);
    $lugar = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    // ── Fotos desde entity_photos (igual que lugar-data.php) ─────────────────
    if (!empty($lugar)) {
        // 1) Tabla entity_photos (fotos aprobadas, ordenadas por portada)
        try {
            $stmtF = $pdo->prepare("
                SELECT file_url
                FROM entity_photos
                WHERE entity_type = 'places_of_interest'
                  AND entity_id = ?
                  AND permission_status = 'approved'
                  AND status = 'active'
                ORDER BY is_cover DESC, featured DESC, uploaded_at DESC
            ");
            $stmtF->execute([$lugar['id']]);
            foreach ($stmtF->fetchAll(PDO::FETCH_ASSOC) as $f) {
                if (!empty($f['file_url'])) {
                    $fotos[] = '/' . ltrim(str_replace('\\', '/', $f['file_url']), '/');
                }
            }
        } catch (Exception $e) { /* ignorar — tabla puede no existir en local */ }

        // 2) Fallback a campos legacy photo1..photo4
        if (empty($fotos)) {
            foreach (['photo1', 'photo2', 'photo3', 'photo4'] as $campo) {
                if (!empty($lugar[$campo])) {
                    $url = $lugar[$campo];
                    $fotos[] = preg_match('/^https?:\/\//', $url) ? $url : '/' . ltrim($url, '/');
                }
            }
        }

        $fotos = array_values(array_filter($fotos));
    }

} catch (Exception $e) {
    error_log('[lugar-modular] Error BD: ' . $e->getMessage());
    // $lugar permanece [] → se mostrará la página de error 404
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
if (!empty($lugar['short_description'])) {
    $descSeo = mb_substr(strip_tags($lugar['short_description']), 0, 160);
} elseif (!empty($lugar['description'])) {
    // Fallback a la descripción larga si no hay corta
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

// Datos JS para window.LUG_DATA (expone lat/lng/slug/provincia/municipio al JS de la página)
$lugar_js = json_encode([
    'slug'         => $slug,
    'name'         => $lugar['name']         ?? '',
    'lat'          => !empty($lugar['latitude'])   ? (float)$lugar['latitude']  : null,
    'lng'          => !empty($lugar['longitude'])  ? (float)$lugar['longitude'] : null,
    'latitude'     => !empty($lugar['latitude'])   ? (float)$lugar['latitude']  : null,
    'longitude'    => !empty($lugar['longitude'])  ? (float)$lugar['longitude'] : null,
    'province'     => $lugar['province']     ?? '',
    'municipality' => $lugar['municipality'] ?? '',
    'photos'       => $fotos,
    'lang'         => $lang,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// ─── CARGAR SCHEMA (debe estar disponible antes de head.php) ─────────────────
require_once __DIR__ . '/components/schema.php';

// ─── HEAD (incluye traducciones $ui → $t, CSS, meta, JSON-LD) ────────────────
// head.php define siempre $t (traducciones) aunque $lugar esté vacío
require_once __DIR__ . '/components/head.php';
// Garantía de seguridad: si head.php no se ejecutó correctamente, $t puede no existir
if (!isset($t) || !is_array($t)) {
    $t = [
        'no_encontrado_h1' => 'Lugar no encontrado',
        'no_encontrado_p'  => 'El lugar de interés que buscas no existe o ya no está disponible.',
        'volver_lista'     => '← Volver a los lugares de interés',
        'ubicacion'        => 'Ubicación',
        'click_mapa'       => 'Haz clic para cargar el mapa interactivo',
        'mapa_hint'        => 'Se mostrarán alojamientos, lugares, actividades y eventos cercanos.',
        'actividades'      => 'Actividades',
    ];
}

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
// Mismo patrón que evento-detalle.php (que funciona):
// HEADER_NO_HTML_HEAD le indica a header.php que omita el bloque
// <!DOCTYPE html>...<head>...</head><body> (ya generado por head.php)
// y solo añada el <header> de navegación.
$globalHeader = dirname(__DIR__) . '/header.php';
if (file_exists($globalHeader)) {
    if (!defined('HEADER_NO_HTML_HEAD')) {
        define('HEADER_NO_HTML_HEAD', true);
    }
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

    <style>
        .map-placeholder {
            background: linear-gradient(135deg, #f0f4f1, #e8f0e8);
            border: 2px dashed var(--border);
            border-radius: var(--radius);
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: background .2s, border-color .2s;
            margin-bottom: 24px;
        }
        .map-placeholder:hover {
            background: #e8f0e8;
            border-color: var(--accent);
        }
        .map-placeholder .map-icon { font-size: 3rem; margin-bottom: 12px; }
        .map-placeholder strong { color: var(--primary); font-size: 1.1rem; }
        .map-placeholder p { color: var(--text-light); margin: 4px 0 8px; }
        .map-placeholder small { color: #999; font-size: 0.8rem; }
    </style>


<?php else: ?>

    <!-- ── ERROR 404 ── -->
    <?php
    http_response_code(404);
    // $t está garantizado por head.php (array_merge con 'es' como base)
    // El fallback extra protege si head.php no pudo ejecutarse
    $err_h1  = isset($t['no_encontrado_h1']) ? $t['no_encontrado_h1'] : 'Lugar no encontrado';
    $err_p   = isset($t['no_encontrado_p'])  ? $t['no_encontrado_p']  : 'El lugar de interés que buscas no existe o ya no está disponible.';
    $err_btn = isset($t['volver_lista'])      ? $t['volver_lista']     : '← Volver a los lugares de interés';
    ?>
    <div class="error-container">
        <div class="error-icon" aria-hidden="true">😕</div>
        <h1><?php echo esc($err_h1); ?></h1>
        <p><?php echo esc($err_p); ?></p>
        <a href="/lugares-de-interes" class="btn-back"><?php echo esc($err_btn); ?></a>
    </div>

<?php endif; ?>

</div><!-- /.lug-page -->

<!-- ── COMPONENTES FINALES: lightbox, toast, scripts ── -->
<?php require __DIR__ . '/components/footer.php'; ?>
