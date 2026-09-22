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
// Cargar el helper de viator-section
require_once __DIR__ . '/components/viator-section.php';

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

    // 1) Buscar el lugar - si el idioma no es español, primero buscar en traducciones
    $placeId = null;
    
    if ($lang !== 'es') {
        // Buscar el place_id usando el slug traducido
        try {
            $stmtTrad = $pdo->prepare("SELECT place_id FROM places_of_interest_trads WHERE slug = ? AND language_code = ? LIMIT 1");
            $stmtTrad->execute([$slug, $lang]);
            $result = $stmtTrad->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $placeId = $result['place_id'];
            }
        } catch (Exception $e) { /* ignorar */ }
    }
    
    // Consulta principal del lugar
    if ($placeId) {
        // Si encontramos el ID por traducción, buscar por ID
        $stmt = $pdo->prepare("
            SELECT p.*, c.name AS category_name
            FROM places_of_interest p
            LEFT JOIN categories_places c ON p.category_id = c.id
            WHERE p.id = ? AND p.is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$placeId]);
    } else {
        // Buscar por slug original (español)
        $stmt = $pdo->prepare("
            SELECT p.*, c.name AS category_name
            FROM places_of_interest p
            LEFT JOIN categories_places c ON p.category_id = c.id
            WHERE p.slug = ? AND p.is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$slug]);
    }
    $lugar = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    if (!empty($lugar)) {
        // 2) Cargar las FAQs de la BD
        if (function_exists('getFaqs')) {
            $faqs = getFaqs($pdo, 'place', (int)$lugar['id'], $lang);
        }

        // 1) Primero: fotos legacy (photo1, photo2, photo3, photo4) - siempre en orden
        $fotosLegacy = [];
        for ($i = 1; $i <= 4; $i++) {
            $campo = 'photo' . $i;
            if (!empty($lugar[$campo])) {
                $url = $lugar[$campo];
                $fotosLegacy[] = preg_match('/^https?:\/\//', $url) ? $url : '/' . ltrim($url, '/');
            }
        }

        // 2) Segundo: fotos de entity_photos (aportadas por usuarios) - con créditos
        $fotosEntity = [];
        $fotosCredits = []; // Array para almacenar créditos de cada foto
        try {
            $stmtF = $pdo->prepare("
                SELECT file_url, author_name, author_instagram
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
                    // Excluir fotos que ya estén en legacy para evitar duplicados
                    $url = '/' . ltrim(str_replace('\\', '/', $f['file_url']), '/');
                    if (!in_array($url, $fotosLegacy)) {
                        $fotosEntity[] = $url;
                        // Guardar crédito: nombre + @instagram si existe
                        $credit = '';
                        if (!empty($f['author_name'])) {
                            $credit = $f['author_name'];
                            if (!empty($f['author_instagram'])) {
                                $credit .= ' (@' . $f['author_instagram'] . ')';
                            }
                        }
                        $fotosCredits[$url] = $credit;
                    }
                }
            }
        } catch (Exception $e) { /* ignorar */ }

        // Combinar: primero legacy, luego entity_photos
        $fotos = array_merge($fotosLegacy, $fotosEntity);
        $fotos = array_values(array_filter($fotos));
    }

} catch (Exception $e) {
    error_log('[lugar-modular] Error BD: ' . $e->getMessage());
}


    // 3) CARGAR TRADUCCIONES si el idioma no es español
    if (!empty($lugar) && $lang !== 'es') {
        try {
            $stmtTrad = $pdo->prepare("
                SELECT * FROM places_of_interest_trads 
                WHERE place_id = ? AND language_code = ?
                LIMIT 1
            ");
            $stmtTrad->execute([$lugar['id'], $lang]);
            $trad = $stmtTrad->fetch(PDO::FETCH_ASSOC);
            
            if ($trad) {
                // Sobrescribir campos con traducciones
                if (!empty($trad['name']))              $lugar['name']              = $trad['name'];
                if (!empty($trad['short_description'])) $lugar['short_description'] = $trad['short_description'];
                if (!empty($trad['description'])) {
                    $lugar['description'] = $trad['description'];
                    // IMPORTANTE: limpiar description_linked para que descripcion.php
                    // use la descripción traducida y no la versión en español enlazada
                    $lugar['description_linked'] = '';
                }
                if (!empty($trad['address']))           $lugar['address']           = $trad['address'];
                if (!empty($trad['municipality']))      $lugar['municipality']      = $trad['municipality'];
                if (!empty($trad['province']))          $lugar['province']          = $trad['province'];
                if (!empty($trad['opening_hours']))     $lugar['opening_hours']     = $trad['opening_hours'];
                if (!empty($trad['accessibility']))     $lugar['accessibility']     = $trad['accessibility'];
                if (!empty($trad['entry_fee']))         $lugar['entry_fee']         = $trad['entry_fee'];
                if (!empty($trad['entry_fee_details'])) $lugar['entry_fee_details'] = $trad['entry_fee_details'];
                if (!empty($trad['facilities']))        $lugar['facilities']        = $trad['facilities'];
                if (!empty($trad['meta_title']))        $lugar['meta_title']        = $trad['meta_title'];
                if (!empty($trad['meta_description'])) $lugar['meta_description']  = $trad['meta_description'];
            }
        } catch (Exception $e) {
            error_log('[lugar-modular] Error traducciones: ' . $e->getMessage());
        }
    }
// ─── TRADUCCIÓN DE CATEGORÍA ─────────────────────────────────────────────────
if (!empty($lugar['category_name']) && $lang !== 'es') {
    $cat_map = [
        'en' => [
            'Monumento'=>'Monument','Monumentos'=>'Monuments','Parque Natural'=>'Natural Park',
            'Parques Naturales'=>'Natural Parks','Parque'=>'Park','Naturaleza'=>'Nature',
            'Museo'=>'Museum','Museos'=>'Museums','Iglesia'=>'Church','Iglesias'=>'Churches',
            'Castillo'=>'Castle','Castillos'=>'Castles','Bodega'=>'Winery','Bodegas'=>'Wineries',
            'Gastronomía'=>'Gastronomy','Restaurante'=>'Restaurant','Restaurantes'=>'Restaurants',
            'Turismo Rural'=>'Rural Tourism','Patrimonio'=>'Heritage','Arqueología'=>'Archaeology',
            'Mirador'=>'Viewpoint','Miradores'=>'Viewpoints','Lago'=>'Lake','Laguna'=>'Lagoon',
            'Reserva Natural'=>'Nature Reserve','Río'=>'River','Cascada'=>'Waterfall',
            'Ruta'=>'Route','Puente'=>'Bridge','Ermita'=>'Hermitage','Convento'=>'Convent',
            'Catedral'=>'Cathedral','Plaza'=>'Square','Enoturismo'=>'Wine Tourism','Turismo'=>'Tourism',
        ],
        'fr' => [
            'Monumento'=>'Monument','Monumentos'=>'Monuments','Parque Natural'=>'Parc Naturel',
            'Parques Naturales'=>'Parcs Naturels','Parque'=>'Parc','Naturaleza'=>'Nature',
            'Museo'=>'Musée','Museos'=>'Musées','Iglesia'=>'Église','Iglesias'=>'Églises',
            'Castillo'=>'Château','Castillos'=>'Châteaux','Bodega'=>'Cave vinicole','Bodegas'=>'Caves vinicoles',
            'Gastronomía'=>'Gastronomie','Restaurante'=>'Restaurant','Restaurantes'=>'Restaurants',
            'Turismo Rural'=>'Tourisme Rural','Patrimonio'=>'Patrimoine','Arqueología'=>'Archéologie',
            'Mirador'=>'Belvédère','Miradores'=>'Belvédères','Lago'=>'Lac','Laguna'=>'Lagune',
            'Reserva Natural'=>'Réserve Naturelle','Río'=>'Rivière','Cascada'=>'Cascade',
            'Ruta'=>'Route','Puente'=>'Pont','Ermita'=>'Ermitage','Convento'=>'Couvent',
            'Catedral'=>'Cathédrale','Plaza'=>'Place','Enoturismo'=>'Œnotourisme','Turismo'=>'Tourisme',
        ],
        'de' => [
            'Monumento'=>'Denkmal','Monumentos'=>'Denkmäler','Parque Natural'=>'Naturpark',
            'Parques Naturales'=>'Naturparks','Parque'=>'Park','Naturaleza'=>'Natur',
            'Museo'=>'Museum','Museos'=>'Museen','Iglesia'=>'Kirche','Iglesias'=>'Kirchen',
            'Castillo'=>'Burg','Castillos'=>'Burgen','Bodega'=>'Weinkeller','Bodegas'=>'Weinkeller',
            'Gastronomía'=>'Gastronomie','Restaurante'=>'Restaurant','Restaurantes'=>'Restaurants',
            'Turismo Rural'=>'Ländlicher Tourismus','Patrimonio'=>'Erbe','Arqueología'=>'Archäologie',
            'Mirador'=>'Aussichtspunkt','Miradores'=>'Aussichtspunkte','Lago'=>'See','Laguna'=>'Lagune',
            'Reserva Natural'=>'Naturschutzgebiet','Río'=>'Fluss','Cascada'=>'Wasserfall',
            'Ruta'=>'Route','Puente'=>'Brücke','Ermita'=>'Einsiedelei','Convento'=>'Kloster',
            'Catedral'=>'Kathedrale','Plaza'=>'Platz','Enoturismo'=>'Weintourismus','Turismo'=>'Tourismus',
        ],
        'zh' => [
            'Monumento'=>'纪念碑','Monumentos'=>'纪念碑','Parque Natural'=>'自然公园',
            'Parques Naturales'=>'自然公园','Parque'=>'公园','Naturaleza'=>'自然',
            'Museo'=>'博物馆','Museos'=>'博物馆','Iglesia'=>'教堂','Iglesias'=>'教堂',
            'Castillo'=>'城堡','Castillos'=>'城堡','Bodega'=>'酒窖','Bodegas'=>'酒窖',
            'Gastronomía'=>'美食','Restaurante'=>'餐厅','Restaurantes'=>'餐厅',
            'Turismo Rural'=>'乡村旅游','Patrimonio'=>'遗产','Arqueología'=>'考古学',
            'Mirador'=>'观景台','Miradores'=>'观景台','Lago'=>'湖','Laguna'=>'泻湖',
            'Reserva Natural'=>'自然保护区','Río'=>'河流','Cascada'=>'瀑布',
            'Ruta'=>'路线','Puente'=>'桥','Ermita'=>'隐居所','Convento'=>'修道院',
            'Catedral'=>'大教堂','Plaza'=>'广场','Enoturismo'=>'葡萄酒旅游','Turismo'=>'旅游',
        ],
    ];
    $catEs = $lugar['category_name'];
    if (!empty($cat_map[$lang][$catEs])) {
        $lugar['category_name'] = $cat_map[$lang][$catEs];
    }
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
    'photos_credits' => $fotosCredits ?? [],
    'lang'         => $lang,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// ─── SSR NEARBY: Alojamientos, Lugares, Actividades y Eventos cercanos visibles al crawler ───
// Query ultraligera (LIMIT 4, usa la misma conexión PDO ya abierta)
// El resultado se renderiza en HTML estático → Google lo indexa sin JS
$ssr_nearby_alojamientos = [];
$ssr_nearby_lugares = [];
$ssr_nearby_actividades = [];
$ssr_nearby_eventos = [];
$ssr_prov = $lugar['province'] ?? '';
$ssr_lat  = !empty($lugar['latitude'])  ? (float)$lugar['latitude']  : null;
$ssr_lng  = !empty($lugar['longitude']) ? (float)$lugar['longitude'] : null;

if ($ssr_lat && $ssr_lng) {
    // Alojamientos más cercanos
    $ss = $pdo->prepare("
        SELECT name, slug, municipality, price_per_night, photo1, short_description,
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS dist
        FROM accommodations
        WHERE is_active = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL
        HAVING dist < 60
        ORDER BY dist ASC
        LIMIT 4
    ");
    $ss->execute([$ssr_lat, $ssr_lng, $ssr_lat]);
    $ssr_nearby_alojamientos = $ss->fetchAll(PDO::FETCH_ASSOC);

    // Lugares de interés más cercanos (excluye el actual)
    $ss2 = $pdo->prepare("
        SELECT name, slug, municipality, photo1,
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS dist
        FROM places_of_interest
        WHERE is_active = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL AND slug != ?
        HAVING dist < 60
        ORDER BY dist ASC
        LIMIT 4
    ");
    $ss2->execute([$ssr_lat, $ssr_lng, $ssr_lat, $lugar['slug']]);
    $ssr_nearby_lugares = $ss2->fetchAll(PDO::FETCH_ASSOC);

    // Actividades turísticas más cercanas
    $ss3 = $pdo->prepare("
        SELECT name, slug, municipality, photo1, price_adult, description,
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS dist
        FROM tourist_activities
        WHERE is_active = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL
        HAVING dist < 60
        ORDER BY dist ASC
        LIMIT 4
    ");
    $ss3->execute([$ssr_lat, $ssr_lng, $ssr_lat]);
    $ssr_nearby_actividades = $ss3->fetchAll(PDO::FETCH_ASSOC);

    // Eventos culturales cercanos (solo futuros o en curso)
    $ss4 = $pdo->prepare("
        SELECT name, slug, municipality, photo1, poster_image, start_date, is_free, ticket_price, description,
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS dist
        FROM cultural_events
        WHERE is_active = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL
            AND COALESCE(end_date, DATE_ADD(start_date, INTERVAL 1 DAY)) >= CURDATE()
        HAVING dist < 60
        ORDER BY dist ASC
        LIMIT 4
    ");
    $ss4->execute([$ssr_lat, $ssr_lng, $ssr_lat]);
    $ssr_nearby_eventos = $ss4->fetchAll(PDO::FETCH_ASSOC);

} elseif ($ssr_prov) {
    // Fallback por provincia si no hay coordenadas
    $ss = $pdo->prepare("SELECT name, slug, municipality, price_per_night, photo1, short_description, 0 AS dist FROM accommodations WHERE is_active = 1 AND province = ? ORDER BY RAND() LIMIT 4");
    $ss->execute([$ssr_prov]);
    $ssr_nearby_alojamientos = $ss->fetchAll(PDO::FETCH_ASSOC);

    $ss2 = $pdo->prepare("SELECT name, slug, municipality, photo1, 0 AS dist FROM places_of_interest WHERE is_active = 1 AND province = ? AND slug != ? ORDER BY RAND() LIMIT 4");
    $ss2->execute([$ssr_prov, $lugar['slug']]);
    $ssr_nearby_lugares = $ss2->fetchAll(PDO::FETCH_ASSOC);

    $ss3 = $pdo->prepare("SELECT name, slug, municipality, photo1, price_adult, description, 0 AS dist FROM tourist_activities WHERE is_active = 1 AND province = ? ORDER BY RAND() LIMIT 4");
    $ss3->execute([$ssr_prov]);
    $ssr_nearby_actividades = $ss3->fetchAll(PDO::FETCH_ASSOC);

    $ss4 = $pdo->prepare("SELECT name, slug, municipality, photo1, poster_image, start_date, is_free, ticket_price, description, 0 AS dist FROM cultural_events WHERE is_active = 1 AND province = ? AND COALESCE(end_date, DATE_ADD(start_date, INTERVAL 1 DAY)) >= CURDATE() ORDER BY start_date ASC LIMIT 4");
    $ss4->execute([$ssr_prov]);
    $ssr_nearby_eventos = $ss4->fetchAll(PDO::FETCH_ASSOC);
}

// ─── CARGAR SCHEMA ──────────────────────────────────────────────────────────
require_once __DIR__ . '/components/schema.php';

// ─── HEAD ────────────────────────────────────────────────────────────────────
require_once __DIR__ . '/components/head.php';

if (!isset($t) || !is_array($t)) {
    $t = [
        'no_encontrado_h1' => 'Lugar no encontrado',
        'no_encontrado_p'  => 'El lugar de interés que buscas no existe o ya no está disponible.',
        'volver_lista'     => '← Volver a los lugares de interés',
        'ubicacion'        => 'Ubicación',
        'ver_mapa'         => 'Ver en el mapa',
        'click_mapa'       => 'Haz clic para cargar el mapa interactivo',
        'mapa_hint'        => 'Se mostrarán alojamientos, lugares, actividades y eventos cercanos.',
        'actividades'      => 'Actividades',
        'dormir_cerca'     => '🏠 ¿Dónde dormir cerca?',
        'dormir_desc'      => 'Alojamientos rurales a pocos kilómetros',
        'activ_cercanas'   => '🎯 Actividades turísticas cercanas',
        'eventos_cercanos' => '🎭 Eventos culturales próximos',
        'lugares_cercanos' => '🏛️ Otros lugares de interés cerca',
        'ver_mas_aloj'     => 'Ver más alojamientos',
        'ver_mas_activ'    => 'Ver más actividades',
        'ver_mas_eventos'  => 'Ver más eventos',
        'ver_mas_lugares'  => 'Ver más lugares',
    ];
}

?>
<body>

<!-- <noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-PLACEHOLDER"
            height="1" width="1" style="display:none;visibility:hidden"
            title="Google Tag Manager"></iframe>
</noscript> -->

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
            <?php require __DIR__ . '/components/mapa.php'; ?>
            
            <?php 
            // Renderizar el acordeón visual de preguntas frecuentes
            if (file_exists(__DIR__ . '/../components/faq-accordion.php')) {
                include __DIR__ . '/../components/faq-accordion.php';
            }
            ?>

            <?php require __DIR__ . '/components/cercanos.php'; ?>

            <!-- ── SECCIÓN DINÁMICA DE VIATOR ── -->
            <?php 
            if (function_exists('mostrar_actividades_viator')) {
                // Selecciona dinámicamente la provincia del lugar activo
                $provincia_actual = $lugar['province'] ?? $provincia ?? '';
                mostrar_actividades_viator($provincia_actual, 3); 
            }
            ?>

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
        
        /* Estilos para los nuevos botones de enlace del mapa */
        .map-link-btn {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 8px 15px;
            background: var(--lug-primary);
            color: #fff;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s, transform 0.2s;
            white-space: nowrap;
        }
        .map-link-btn:hover {
            background: var(--lug-primary-l);
            transform: translateY(-1px);
            color: #fff; /* Asegurar que el color del texto no cambie en hover */
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

<!-- ── COMPONENTES FINALES ── -->
<?php require __DIR__ . '/components/footer.php'; ?>

<!-- Script para desregistrar Service Workers problemáticos -->
<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.getRegistrations().then(function(registrations) {
            for (let registration of registrations) {
                registration.unregister().then(function(boolean) {
                    console.log('Service Worker unregistered: ', boolean);
                });
            }
        });
    }
</script>
