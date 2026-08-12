<?php
/**
 * lugar-modular/index.php — Controlador principal
 * ================================================
 * Arquitectura modular de fichas de lugares de interés.
 * Lee $slug de la URL, carga datos de la BD y delega
 * el renderizado a los componentes de /components/.
 *
 * URL: /lugar/{slug}  →  servida por .htaccess o router PHP
 */

// Suprimir warnings en producción
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

header('Content-Type: text/html; charset=UTF-8');

// ─── HELPERS ─────────────────────────────────────────────────────────────────

/**
 * Escapa un valor para salida HTML segura.
 */
function esc(?string $str): string {
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Cargar el helper de FAQs
require_once __DIR__ . '/../includes/faq-helper.php';

// ─── SEGURIDAD: SLUG ─────────────────────────────────────────────────────────

$slug = trim($_GET['slug'] ?? '');
$slug = preg_replace('/[^a-z0-9\-\.\_]/i', '', $slug);

if (empty($slug)) {
    http_response_code(400);
    exit('<p>Slug inválido.</p>');
}

// ─── IDIOMA ───────────────────────────────────────────────────────────────────
$lang = 'es';
$langAllowed = ['es', 'en', 'fr', 'de', 'zh'];

if (!empty($_GET['lang']) && in_array($_GET['lang'], $langAllowed, true)) {
    $lang = $_GET['lang'];
} elseif (!empty($_SERVER['REQUEST_URI'])) {
    if (preg_match('#^/(' . implode('|', $langAllowed) . ')/#', $_SERVER['REQUEST_URI'], $m)) {
        $lang = $m[1];
    }
}

// ─── CARGA DE DATOS (BD) ─────────────────────────────────────────────────────
$lugar = [];
$fotos = [];
$faqs  = [];

if (!defined('API_NO_HEADERS')) {
    define('API_NO_HEADERS', true);
}
require_once dirname(__DIR__) . '/api/config.php';

try {
    $pdo = getDBConnection();

    // 1) Consulta principal del lugar
    $stmt = $pdo->prepare("
        SELECT p.*, c.name AS category_name
        FROM places_of_interest p
        LEFT JOIN categories_places c ON p.category_id = c.id
        WHERE p.slug = ? AND p.is_active = 1
        LIMIT 1
    ");
    $stmt->execute([$slug]);
    $lugar = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    if (!empty($lugar)) {
        // 2) Cargar las FAQs de la BD ahora que tenemos la ID real del lugar ($lugar['id'])
        if (function_exists('getFaqs')) {
            $faqs = getFaqs($pdo, 'place', (int)$lugar['id'], $lang);
        }

        // 3) Fotos desde entity_photos
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
        } catch (Exception $e) { /* ignorar */ }

        // Fallback a campos legacy photo1..photo4
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
}

// ─── VARIABLES SEO ────────────────────────────────────────────────────────────

$baseUrl   = 'https://rutasrurales.io';
$canonical = $lang === 'es'
    ? $baseUrl . '/lugar/' . $slug
    : $baseUrl . '/' . $lang . '/lugar/' . $slug;

$municipio    = $lugar['municipality'] ?? '';
$provincia    = $lugar['province']     ?? '';
$categoryName = $lugar['category_name'] ?? '';
$nombreLugar  = $lugar['name']          ?? ucwords(str_replace('-', ' ', $slug));

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

$descSeo = '';
if (!empty($lugar['short_description'])) {
    $descSeo = mb_substr(strip_tags($lugar['short_description']), 0, 160);
} elseif (!empty($lugar['description'])) {
    $descSeo = mb_substr(strip_tags($lugar['description']), 0, 160); 
}
if (empty($descSeo)) {
    $descSeo = 'Descubre ' . $nombreLugar
        . (!empty($municipio) ? ' en ' . $municipio : '')
        . (!empty($provincia) ? ', ' . $provincia : '')
        . '. Información práctica, horarios, fotos y cómo llegar. Planifica tu visita con Rutas Rurales.';
}
$page_description = $descSeo;

$foto_og = !empty($fotos[0])
    ? (preg_match('/^https?:\/\//', $fotos[0]) ? $fotos[0] : $baseUrl . '/' . ltrim($fotos[0], '/'))
    : $baseUrl . '/menu_images/turismo_rural.webp';

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

// ─── HEAD ────────────────────────────────────────────────────────────────────
require_once __DIR__ . '/components/head.php';

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

?>
<body>

<noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-XXXXXXX"
            height="1" width="1" style="display:none;visibility:hidden"
            title="Google Tag Manager"></iframe>
</noscript>

<?php
$globalHeader = dirname(__DIR__) . '/header.php';
if (file_exists($globalHeader)) {
    if (!defined('HEADER_NO_HTML_HEAD')) {
        define('HEADER_NO_HTML_HEAD', true);
    }
    include $globalHeader;
}
?>

<div class="lug-page">

<?php if (!empty($lugar)): ?>

    <!-- ── HERO ── -->
    <?php require __DIR__ . '/components/hero.php'; ?>

    <div class="lug-layout">

        <!-- ── COLUMNA PRINCIPAL ── -->
        <main id="main-content">

            <?php require __DIR__ . '/components/galeria.php'; ?>
            <?php require __DIR__ . '/components/descripcion.php'; ?>
            
            <?php 
            // Renderizar el acordeón visual de preguntas frecuentes en la columna principal
            if (file_exists(__DIR__ . '/../components/faq-accordion.php')) {
                include __DIR__ . '/../components/faq-accordion.php';
            }
            ?>

            <?php require __DIR__ . '/components/cercanos.php'; ?>

        </main>

        <!-- ── SIDEBAR ── -->
        <?php require __DIR__ . '/components/sidebar.php'; ?>

    </div>

    <style>
        #event-map-container { border-radius: var(--radius); }
        #event-map { height: 380px; }
        .map-placeholder {
            height: 380px;
            background: linear-gradient(135deg, #f0f4f1, #e8f0e8);
            border: 2px dashed var(--border);
            border-radius: var(--radius);
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: background .2s, border-color .2s;
            margin-bottom: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .map-placeholder:hover { background: #e8f0e8; border-color: var(--accent); }
        .map-placeholder .map-icon { font-size: 3rem; margin-bottom: 12px; }
        .map-placeholder strong { color: var(--primary); font-size: 1.1rem; }
        .map-placeholder p { color: var(--text-light); margin: 4px 0 8px; font-size: 0.9rem; }
        .map-placeholder small { color: #999; font-size: 0.8rem; }
        
        .map-controls {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            padding: 12px;
            background: var(--white);
            border-top: 1px solid #eee;
        }
        .map-toggle-btn {
            padding: 6px 14px;
            border: 1px solid #ddd;
            background: #f8f9fa;
            color: #666;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .map-toggle-btn:hover { background: #e9ecef; border-color: #adb5bd; }
        .map-toggle-btn.active {
            background: var(--primary);
            color: var(--white);
            border-color: var(--primary);
        }
    </style>

<?php else: ?>

    <!-- ── ERROR 404 ── -->
    <?php
    http_response_code(404);
    $err_h1  = $t['no_encontrado_h1'] ?? 'Lugar no encontrado';
    $err_p   = $t['no_encontrado_p']  ?? 'El lugar de interés que buscas no existe o ya no está disponible.';
    $err_btn = $t['volver_lista']     ?? '← Volver a los lugares de interés';
    ?>
    <div class="error-container">
        <div class="error-icon" aria-hidden="true">😕</div>
        <h1><?php echo esc($err_h1); ?></h1>
        <p><?php echo esc($err_p); ?></p>
        <a href="/lugares-de-interes" class="btn-back"><?php echo esc($err_btn); ?></a>
    </div>

<?php endif; ?>

</div>

<?php 
// Impresión del JSON-LD Schema
if (!empty($lugar) && function_exists('renderLugarSchema')) {
    renderLugarSchema($lugar, $fotos, $canonical, $page_title, $page_description, $lang, $faqs);
}
?>

<!-- ── COMPONENTES FINALES ── -->
<?php require __DIR__ . '/components/footer.php'; ?>
