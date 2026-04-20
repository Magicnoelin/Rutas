<?php
/**
 * ALOJAMIENTO MODULAR - Página de Detalle de Alojamiento
 * Versión optimizada para velocidad y enganche de turistas
 *
 * URL: /alojamiento-modular/{slug}
 * Prueba: https://rutasrurales.io/alojamiento-modular/{slug}
 * Producción final: /alojamiento/{slug}
 */

// Suprimir warnings en producción para que no rompan el HTML/JS
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

define('API_NO_HEADERS', true);
require_once '../api/config.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$lang = isset($_GET['lang']) ? trim($_GET['lang']) : 'es';
$lang = in_array($lang, ['es', 'en', 'fr', 'de', 'zh']) ? $lang : 'es';

// ─── OBTENER DATOS CRÍTICOS DEL ALOJAMIENTO (SSR para SEO) ───────────────────
$alojamiento = null;
$fotos = [];

if (!empty($slug)) {
    try {
        $pdo = getDBConnection();

        $stmt = $pdo->prepare("
            SELECT a.*, c.name as category_name
            FROM accommodations a
            LEFT JOIN categories_accommodations c ON a.category_id = c.id
            WHERE a.slug = ? AND a.is_active = 1
        ");
        $stmt->execute([$slug]);
        $alojamiento = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($alojamiento) {
            for ($i = 1; $i <= 10; $i++) {
                $campo = 'photo' . $i;
                if (!empty($alojamiento[$campo])) {
                    $url = $alojamiento[$campo];
                    if (!preg_match('/^https?:\/\//', $url)) {
                        $url = '/' . ltrim($url, '/');
                    }
                    $fotos[] = $url;
                }
            }
            if (empty($fotos)) {
                $fotos[] = 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1200&h=800&fit=crop&auto=format';
            }
        }
    } catch (Exception $e) {
        error_log('alojamiento-modular/index.php error: ' . $e->getMessage());
    }
}

// ─── SEO ─────────────────────────────────────────────────────────────────────
$page_title       = $alojamiento
    ? ($alojamiento['meta_title'] ?: $alojamiento['name'] . ' — ' . ($alojamiento['municipality'] ?? '') . ' | Rutas Rurales')
    : 'Alojamiento | Rutas Rurales';
$page_desc        = $alojamiento
    ? ($alojamiento['meta_description'] ?: substr(strip_tags($alojamiento['description'] ?? ''), 0, 160) ?: 'Alojamiento turístico en ' . ($alojamiento['municipality'] ?? ''))
    : 'Descubre este alojamiento en Rutas Rurales';
$canonical        = 'https://rutasrurales.io/' . ($lang !== 'es' ? $lang . '/' : '') . 'alojamiento/' . $slug;
$foto_og          = !empty($fotos[0]) ? $fotos[0] : 'https://rutasrurales.io/menu_images/og-default.jpg';
$page_description = $page_desc;
$page_canonical   = $canonical;

// ─── TRADUCCIONES DE UI ───────────────────────────────────────────────────────
$ui = [
    'es' => [
        'alojamiento'          => 'Alojamiento',
        'alojamientos'         => 'Alojamientos',
        'capacidad'            => 'Capacidad',
        'personas'             => 'personas',
        'tipo'                 => 'Tipo',
        'precio_noche'         => 'Precio por noche',
        'consultar'            => 'Consultar precio',
        'contacto'             => 'Contacto',
        'llamar'               => 'Llamar',
        'whatsapp'             => 'WhatsApp',
        'email'                => 'Email',
        'web'                  => 'Visitar web',
        'descripcion'          => 'Descripción',
        'caracteristicas'      => 'Características',
        'servicios'            => 'Servicios',
        'ubicacion'            => 'Ubicación',
        'ver_mapa'             => 'Ver en el mapa',
        'click_mapa'           => 'Haz clic para cargar el mapa interactivo',
        'cercanos'             => '¿Qué hay cerca?',
        'alojamientos_cercanos'=> '🏠 Alojamientos cercanos',
        'lugares_cercanos'     => '🏛️ Lugares de interés',
        'eventos_cercanos'     => '🎭 Eventos culturales',
        'actividades_cercanas' => '🎯 Actividades turísticas',
        'ver_mas'              => 'Ver más',
        'no_encontrado_h1'     => 'Alojamiento no encontrado',
        'no_encontrado_p'      => 'El alojamiento que buscas no existe o ya no está disponible.',
        'volver_lista'         => '← Volver a la lista de alojamientos',
        'cta_titulo'           => '¿Te gusta este alojamiento?',
        'cta_desc'             => 'Regístrate gratis para guardarlo en tus favoritos y recibir ofertas similares',
        'cta_register'         => '✨ Registrarme gratis',
        'cta_login'            => 'Ya tengo cuenta',
        'fotos'                => 'Galería de fotos',
        'km'                   => 'km',
        'gratis'               => 'Gratis',
        'desde'                => 'desde',
        'checkin'              => 'Check-in',
        'checkout'             => 'Check-out',
        'no_fotos'             => 'Sin fotos disponibles',
        'cargando'             => 'Cargando…',
        'sin_resultados'       => 'No hay resultados cercanos',
        'reservar'             => 'Reservar ahora',
        'compartir'            => 'Compartir',
        'favorito'             => 'Guardar',
        'precio_desde'         => 'Desde',
        'noche'                => 'noche',
    ],
    'en' => [
        'alojamiento'          => 'Accommodation',
        'alojamientos'         => 'Accommodations',
        'capacidad'            => 'Capacity',
        'personas'             => 'people',
        'tipo'                 => 'Type',
        'precio_noche'         => 'Price per night',
        'consultar'            => 'Check price',
        'contacto'             => 'Contact',
        'llamar'               => 'Call',
        'whatsapp'             => 'WhatsApp',
        'email'                => 'Email',
        'web'                  => 'Visit website',
        'descripcion'          => 'Description',
        'caracteristicas'      => 'Features',
        'servicios'            => 'Services',
        'ubicacion'            => 'Location',
        'ver_mapa'             => 'View on map',
        'click_mapa'           => 'Click to load the interactive map',
        'cercanos'             => 'What\'s nearby?',
        'alojamientos_cercanos'=> '🏠 Nearby accommodation',
        'lugares_cercanos'     => '🏛️ Places of interest',
        'eventos_cercanos'     => '🎭 Cultural events',
        'actividades_cercanas' => '🎯 Tourist activities',
        'ver_mas'              => 'View more',
        'no_encontrado_h1'     => 'Accommodation not found',
        'no_encontrado_p'      => 'The accommodation you are looking for does not exist or is no longer available.',
        'volver_lista'         => '← Back to accommodation list',
        'cta_titulo'           => 'Do you like this accommodation?',
        'cta_desc'             => 'Sign up for free to save it to your favorites and receive similar offers',
        'cta_register'         => '✨ Sign up free',
        'cta_login'            => 'I already have an account',
        'fotos'                => 'Photo gallery',
        'km'                   => 'km',
        'gratis'               => 'Free',
        'desde'                => 'from',
        'checkin'              => 'Check-in',
        'checkout'             => 'Check-out',
        'no_fotos'             => 'No photos available',
        'cargando'             => 'Loading…',
        'sin_resultados'       => 'No nearby results',
        'reservar'             => 'Book now',
        'compartir'            => 'Share',
        'favorito'             => 'Save',
        'precio_desde'         => 'From',
        'noche'                => 'night',
    ],
    'fr' => [
        'alojamiento'          => 'Hébergement',
        'alojamientos'         => 'Hébergements',
        'capacidad'            => 'Capacité',
        'personas'             => 'personnes',
        'tipo'                 => 'Type',
        'precio_noche'         => 'Prix par nuit',
        'consultar'            => 'Consulter le prix',
        'contacto'             => 'Contact',
        'llamar'               => 'Appeler',
        'whatsapp'             => 'WhatsApp',
        'email'                => 'Email',
        'web'                  => 'Visiter le site',
        'descripcion'          => 'Description',
        'caracteristicas'      => 'Caractéristiques',
        'servicios'            => 'Services',
        'ubicacion'            => 'Emplacement',
        'ver_mapa'             => 'Voir sur la carte',
        'click_mapa'           => 'Cliquez pour charger la carte interactive',
        'cercanos'             => 'Qu\'y a-t-il à proximité ?',
        'alojamientos_cercanos'=> '🏠 Hébergements à proximité',
        'lugares_cercanos'     => '🏛️ Sites d\'intérêt',
        'eventos_cercanos'     => '🎭 Événements culturels',
        'actividades_cercanas' => '🎯 Activités touristiques',
        'ver_mas'              => 'Voir plus',
        'no_encontrado_h1'     => 'Hébergement introuvable',
        'no_encontrado_p'      => 'L\'hébergement que vous recherchez n\'existe pas ou n\'est plus disponible.',
        'volver_lista'         => '← Retour à la liste',
        'cta_titulo'           => 'Vous aimez cet hébergement ?',
        'cta_desc'             => 'Inscrivez-vous gratuitement pour l\'ajouter à vos favoris',
        'cta_register'         => '✨ S\'inscrire gratuitement',
        'cta_login'            => 'J\'ai déjà un compte',
        'fotos'                => 'Galerie photos',
        'km'                   => 'km',
        'gratis'               => 'Gratuit',
        'desde'                => 'à partir de',
        'checkin'              => 'Arrivée',
        'checkout'             => 'Départ',
        'no_fotos'             => 'Pas de photos disponibles',
        'cargando'             => 'Chargement…',
        'sin_resultados'       => 'Aucun résultat à proximité',
        'reservar'             => 'Réserver maintenant',
        'compartir'            => 'Partager',
        'favorito'             => 'Sauvegarder',
        'precio_desde'         => 'À partir de',
        'noche'                => 'nuit',
    ],
    'de' => [
        'alojamiento'          => 'Unterkunft',
        'alojamientos'         => 'Unterkünfte',
        'capacidad'            => 'Kapazität',
        'personas'             => 'Personen',
        'tipo'                 => 'Typ',
        'precio_noche'         => 'Preis pro Nacht',
        'consultar'            => 'Preis anfragen',
        'contacto'             => 'Kontakt',
        'llamar'               => 'Anrufen',
        'whatsapp'             => 'WhatsApp',
        'email'                => 'E-Mail',
        'web'                  => 'Website besuchen',
        'descripcion'          => 'Beschreibung',
        'caracteristicas'      => 'Merkmale',
        'servicios'            => 'Dienstleistungen',
        'ubicacion'            => 'Standort',
        'ver_mapa'             => 'Auf der Karte anzeigen',
        'click_mapa'           => 'Klicken Sie, um die interaktive Karte zu laden',
        'cercanos'             => 'Was gibt es in der Nähe?',
        'alojamientos_cercanos'=> '🏠 Unterkünfte in der Nähe',
        'lugares_cercanos'     => '🏛️ Sehenswürdigkeiten',
        'eventos_cercanos'     => '🎭 Kulturelle Veranstaltungen',
        'actividades_cercanas' => '🎯 Touristische Aktivitäten',
        'ver_mas'              => 'Mehr anzeigen',
        'no_encontrado_h1'     => 'Unterkunft nicht gefunden',
        'no_encontrado_p'      => 'Die gesuchte Unterkunft existiert nicht oder ist nicht mehr verfügbar.',
        'volver_lista'         => '← Zurück zur Liste',
        'cta_titulo'           => 'Gefällt Ihnen diese Unterkunft?',
        'cta_desc'             => 'Registrieren Sie sich kostenlos, um sie zu Ihren Favoriten hinzuzufügen',
        'cta_register'         => '✨ Kostenlos registrieren',
        'cta_login'            => 'Ich habe bereits ein Konto',
        'fotos'                => 'Fotogalerie',
        'km'                   => 'km',
        'gratis'               => 'Kostenlos',
        'desde'                => 'ab',
        'checkin'              => 'Check-in',
        'checkout'             => 'Check-out',
        'no_fotos'             => 'Keine Fotos verfügbar',
        'cargando'             => 'Laden…',
        'sin_resultados'       => 'Keine Ergebnisse in der Nähe',
        'reservar'             => 'Jetzt buchen',
        'compartir'            => 'Teilen',
        'favorito'             => 'Speichern',
        'precio_desde'         => 'Ab',
        'noche'                => 'Nacht',
    ],
    'zh' => [
        'alojamiento'          => '住宿',
        'alojamientos'         => '住宿列表',
        'capacidad'            => '容量',
        'personas'             => '人',
        'tipo'                 => '类型',
        'precio_noche'         => '每晚价格',
        'consultar'            => '咨询价格',
        'contacto'             => '联系',
        'llamar'               => '打电话',
        'whatsapp'             => 'WhatsApp',
        'email'                => '电子邮件',
        'web'                  => '访问网站',
        'descripcion'          => '描述',
        'caracteristicas'      => '特色',
        'servicios'            => '服务',
        'ubicacion'            => '位置',
        'ver_mapa'             => '在地图上查看',
        'click_mapa'           => '点击加载互动地图',
        'cercanos'             => '附近有什么？',
        'alojamientos_cercanos'=> '🏠 附近住宿',
        'lugares_cercanos'     => '🏛️ 附近景点',
        'eventos_cercanos'     => '🎭 附近文化活动',
        'actividades_cercanas' => '🎯 附近旅游活动',
        'ver_mas'              => '查看更多',
        'no_encontrado_h1'     => '未找到住宿',
        'no_encontrado_p'      => '您查找的住宿不存在或已不再提供。',
        'volver_lista'         => '← 返回住宿列表',
        'cta_titulo'           => '喜欢这个住宿吗？',
        'cta_desc'             => '免费注册，将其添加到收藏夹并接收类似优惠',
        'cta_register'         => '✨ 免费注册',
        'cta_login'            => '我已有账户',
        'fotos'                => '照片库',
        'km'                   => '公里',
        'gratis'               => '免费',
        'desde'                => '起',
        'checkin'              => '入住',
        'checkout'             => '退房',
        'no_fotos'             => '暂无照片',
        'cargando'             => '加载中…',
        'sin_resultados'       => '附近没有结果',
        'reservar'             => '立即预订',
        'compartir'            => '分享',
        'favorito'             => '收藏',
        'precio_desde'         => '起价',
        'noche'                => '晚',
    ],
];

$t = $ui[$lang] ?? $ui['es'];

// Precio formateado
$precio_display = '';
if ($alojamiento) {
    if (!empty($alojamiento['price_per_night']) && $alojamiento['price_per_night'] > 0) {
        $precio_display = number_format($alojamiento['price_per_night'], 0, ',', '.') . ' €';
    } else {
        $precio_display = $t['consultar'];
    }
}

$tipo_display     = $alojamiento['category_name'] ?? $alojamiento['accommodation_type'] ?? $t['alojamiento'];
$capacidad_display = ($alojamiento['capacity'] ?? 0) > 0 ? $alojamiento['capacity'] . ' ' . $t['personas'] : '';

// JSON-LD Schema.org LodgingBusiness
$jsonld = '';
if ($alojamiento) {
    $jsonld_data = [
        '@context'     => 'https://schema.org',
        '@type'        => 'LodgingBusiness',
        'name'         => $alojamiento['name'],
        'description'  => substr(strip_tags($alojamiento['description'] ?? ''), 0, 300),
        'address'      => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => $alojamiento['address'] ?? '',
            'addressLocality' => $alojamiento['municipality'] ?? '',
            'addressRegion'   => $alojamiento['province'] ?? '',
            'addressCountry'  => 'ES',
        ],
        'priceRange'   => $alojamiento['price_per_night'] ? $alojamiento['price_per_night'] . '€' : '',
        'telephone'    => $alojamiento['phone'] ?? '',
        'email'        => $alojamiento['email'] ?? '',
        'url'          => $canonical,
        'image'        => $fotos,
        'checkinTime'  => $alojamiento['check_in_time'] ?? '15:00',
        'checkoutTime' => $alojamiento['check_out_time'] ?? '12:00',
    ];
    if (!empty($alojamiento['latitude']) && !empty($alojamiento['longitude'])) {
        $jsonld_data['geo'] = [
            '@type'     => 'GeoCoordinates',
            'latitude'  => $alojamiento['latitude'],
            'longitude' => $alojamiento['longitude'],
        ];
    }
    $jsonld = json_encode($jsonld_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

// Datos para JavaScript (evitar segunda llamada API para datos básicos)
$alojamiento_js = $alojamiento ? json_encode([
    'id'          => $alojamiento['id'],
    'name'        => $alojamiento['name'],
    'slug'        => $alojamiento['slug'],
    'latitude'    => $alojamiento['latitude'],
    'longitude'   => $alojamiento['longitude'],
    'province'    => $alojamiento['province'],
    'municipality'=> $alojamiento['municipality'],
    'address'     => $alojamiento['address'] ?? '',
    'fotos'       => $fotos,
    'tipo'        => $tipo_display,
    'precio_noche'=> $alojamiento['price_per_night'] ?? 0,
    'capacidad'   => $alojamiento['capacity'] ?? 0,
    'phone'       => $alojamiento['phone'] ?? '',
    'email'       => $alojamiento['email'] ?? '',
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) : 'null';

// Indicar al header que NO cargue FA de forma bloqueante (lo cargamos nosotros de forma diferida)
$defer_fontawesome = true;

// Incluir header.php (emite <!DOCTYPE html>, <html>, <head> con GTM, styles.css, etc.)
include '../header.php';
?>

<!-- ═══════════════════════════════════════════════════════════════════════════
     ESTILOS CRÍTICOS INLINE — solo lo necesario para el primer render
     ═══════════════════════════════════════════════════════════════════════════ -->
<style>
/* ── Variables ── */
:root {
    --primary:       #2F5233;
    --primary-light: #3d6b42;
    --primary-dark:  #1a3d1e;
    --accent:        #81C784;
    --accent-warm:   #F9A825;
    --accent-red:    #e53935;
    --text:          #2d2d2d;
    --text-light:    #666;
    --text-muted:    #999;
    --bg:            #f4f6f4;
    --bg-card:       #ffffff;
    --white:         #ffffff;
    --radius:        14px;
    --radius-sm:     8px;
    --shadow:        0 2px 16px rgba(0,0,0,0.07);
    --shadow-md:     0 4px 24px rgba(0,0,0,0.11);
    --shadow-hover:  0 8px 32px rgba(0,0,0,0.16);
    --transition:    0.22s ease;
}

/* ── Reset mínimo ── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body {
    font-family: 'Montserrat', 'Segoe UI', system-ui, sans-serif;
    color: var(--text);
    background: var(--bg);
    line-height: 1.65;
    overflow-x: hidden;
}
img { max-width: 100%; height: auto; display: block; }
a { color: var(--primary); text-decoration: none; }
a:hover { color: var(--primary-light); }

/* ── Layout ── */
.alo-container { max-width: 1200px; margin: 0 auto; padding: 0 18px; }

/* ══════════════════════════════════════════════════════
   HERO CON FOTO DE FONDO
   ══════════════════════════════════════════════════════ */
.alo-hero {
    margin-top: 70px;
    position: relative;
    min-height: 420px;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    overflow: hidden;
    background: var(--primary-dark);
}
.alo-hero-bg {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    transform: scale(1.04);
    transition: transform 8s ease;
    will-change: transform;
}
.alo-hero-bg::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(
        to bottom,
        rgba(0,0,0,0.15) 0%,
        rgba(0,0,0,0.25) 40%,
        rgba(0,0,0,0.72) 100%
    );
}
.alo-hero:hover .alo-hero-bg { transform: scale(1); }

.alo-hero-content {
    position: relative;
    z-index: 2;
    padding: 28px 24px 36px;
    max-width: 1200px;
    margin: 0 auto;
    width: 100%;
}
.alo-breadcrumb {
    font-size: 0.78rem;
    color: rgba(255,255,255,0.75);
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}
.alo-breadcrumb a { color: rgba(255,255,255,0.75); }
.alo-breadcrumb a:hover { color: #fff; }
.alo-breadcrumb .sep { opacity: 0.5; }
.alo-breadcrumb .current { color: #fff; font-weight: 600; }

.alo-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--accent-warm);
    color: #1a1a1a;
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.4px;
    text-transform: uppercase;
    margin-bottom: 14px;
}
.alo-hero-title {
    font-size: clamp(1.7rem, 4.5vw, 3rem);
    font-weight: 800;
    color: #fff;
    line-height: 1.15;
    margin-bottom: 16px;
    text-shadow: 0 2px 8px rgba(0,0,0,0.4);
}
.alo-hero-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 18px;
    font-size: 0.92rem;
    color: rgba(255,255,255,0.92);
}
.alo-hero-meta-item {
    display: flex;
    align-items: center;
    gap: 7px;
}
.alo-hero-meta-item i { font-size: 0.95rem; color: var(--accent); }
.alo-hero-price-badge {
    background: var(--accent);
    color: var(--primary-dark);
    font-weight: 800;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 1rem;
}

/* Botones de acción rápida en hero */
.alo-hero-actions {
    position: absolute;
    top: 20px;
    right: 20px;
    z-index: 3;
    display: flex;
    gap: 10px;
}
.alo-hero-action-btn {
    background: rgba(255,255,255,0.18);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.3);
    color: #fff;
    border-radius: 50%;
    width: 42px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--transition);
    font-size: 1rem;
    text-decoration: none;
}
.alo-hero-action-btn:hover {
    background: rgba(255,255,255,0.35);
    color: #fff;
    transform: scale(1.08);
}

/* ══════════════════════════════════════════════════════
   LAYOUT PRINCIPAL
   ══════════════════════════════════════════════════════ */
.alo-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 28px;
    max-width: 1200px;
    margin: -24px auto 60px;
    padding: 0 18px;
    position: relative;
    z-index: 3;
}
@media (max-width: 960px) {
    .alo-layout { grid-template-columns: 1fr; margin-top: -16px; }
}

/* ── Tarjeta base ── */
.alo-card {
    background: var(--bg-card);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 26px;
    margin-bottom: 24px;
    transition: box-shadow var(--transition);
}
.alo-card:hover { box-shadow: var(--shadow-md); }

.section-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid var(--accent);
    display: flex;
    align-items: center;
    gap: 9px;
}
.section-title i { font-size: 1.1rem; }

/* ══════════════════════════════════════════════════════
   GALERÍA
   ══════════════════════════════════════════════════════ */
.gallery-main {
    position: relative;
    border-radius: var(--radius-sm);
    overflow: hidden;
    margin-bottom: 12px;
    cursor: pointer;
    background: #111;
}
.gallery-main-img {
    width: 100%;
    height: 380px;
    object-fit: cover;
    display: block;
    transition: transform 0.4s ease, opacity 0.25s;
}
.gallery-main:hover .gallery-main-img { transform: scale(1.02); }
.gallery-counter {
    position: absolute;
    bottom: 12px;
    right: 14px;
    background: rgba(0,0,0,0.55);
    color: #fff;
    font-size: 0.78rem;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 12px;
    backdrop-filter: blur(4px);
}
.gallery-expand-btn {
    position: absolute;
    top: 12px;
    right: 14px;
    background: rgba(0,0,0,0.45);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 7px 12px;
    font-size: 0.8rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    backdrop-filter: blur(4px);
    transition: background var(--transition);
}
.gallery-expand-btn:hover { background: rgba(0,0,0,0.7); }

.gallery-thumbs {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
    gap: 8px;
}
.gallery-thumb {
    height: 72px;
    border-radius: 6px;
    overflow: hidden;
    cursor: pointer;
    border: 2px solid transparent;
    transition: border-color var(--transition), transform var(--transition);
}
.gallery-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}
.gallery-thumb:hover { transform: scale(1.04); }
.gallery-thumb.active { border-color: var(--primary); }
.gallery-thumb:hover img { transform: scale(1.08); }

/* Lightbox */
.lightbox-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.93);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    flex-direction: column;
}
.lightbox-overlay.active { display: flex; }
.lightbox-img {
    max-width: 92vw;
    max-height: 82vh;
    object-fit: contain;
    border-radius: 6px;
    box-shadow: 0 8px 40px rgba(0,0,0,0.6);
}
.lightbox-close {
    position: absolute;
    top: 18px;
    right: 22px;
    color: #fff;
    font-size: 2rem;
    cursor: pointer;
    background: none;
    border: none;
    line-height: 1;
    opacity: 0.8;
    transition: opacity var(--transition);
}
.lightbox-close:hover { opacity: 1; }
.lightbox-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255,255,255,0.12);
    border: none;
    color: #fff;
    font-size: 1.8rem;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background var(--transition);
}
.lightbox-nav:hover { background: rgba(255,255,255,0.25); }
.lightbox-prev { left: 16px; }
.lightbox-next { right: 16px; }
.lightbox-caption {
    color: rgba(255,255,255,0.7);
    font-size: 0.85rem;
    margin-top: 14px;
    text-align: center;
}

/* ══════════════════════════════════════════════════════
   DESCRIPCIÓN
   ══════════════════════════════════════════════════════ */
.desc-text {
    line-height: 1.85;
    color: var(--text);
    margin-bottom: 24px;
    font-size: 0.97rem;
}
.desc-text.collapsed {
    max-height: 120px;
    overflow: hidden;
    position: relative;
}
.desc-text.collapsed::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 50px;
    background: linear-gradient(transparent, var(--bg-card));
}
.desc-expand-btn {
    background: none;
    border: 1px solid var(--accent);
    color: var(--primary);
    padding: 7px 18px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    margin-bottom: 22px;
    transition: all var(--transition);
}
.desc-expand-btn:hover { background: var(--primary); color: #fff; border-color: var(--primary); }

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
    gap: 12px;
}
.feature-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px;
    background: var(--bg);
    border-radius: var(--radius-sm);
    border-left: 3px solid var(--accent);
}
.feature-item i {
    color: var(--primary);
    font-size: 1.15rem;
    margin-top: 2px;
    flex-shrink: 0;
}
.feature-item strong { display: block; font-size: 0.78rem; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 2px; }
.feature-item p { font-size: 0.92rem; font-weight: 600; color: var(--text); margin: 0; }

/* ══════════════════════════════════════════════════════
   CONTACTO
   ══════════════════════════════════════════════════════ */
.contact-btns {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 18px;
}
.btn-contact {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 20px;
    border-radius: var(--radius-sm);
    font-weight: 700;
    font-size: 0.9rem;
    text-decoration: none;
    transition: all var(--transition);
    border: none;
    cursor: pointer;
}
.btn-phone    { background: var(--primary); color: #fff; }
.btn-whatsapp { background: #25D366; color: #fff; }
.btn-email    { background: var(--accent-warm); color: #1a1a1a; }
.btn-website  { background: var(--accent); color: var(--primary-dark); }
.btn-contact:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }

.contact-address {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    color: var(--text-light);
    font-size: 0.88rem;
    padding: 12px;
    background: var(--bg);
    border-radius: var(--radius-sm);
}
.contact-address i { color: var(--primary); margin-top: 2px; flex-shrink: 0; }

/* ══════════════════════════════════════════════════════
   MAPA
   ══════════════════════════════════════════════════════ */
.map-wrapper {
    border-radius: var(--radius-sm);
    overflow: hidden;
    box-shadow: var(--shadow);
    position: relative;
}
.map-placeholder {
    height: 320px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #e8f0e8 0%, #c8dfc8 100%);
    color: var(--primary);
    gap: 14px;
    cursor: pointer;
    transition: background var(--transition);
}
.map-placeholder:hover { background: linear-gradient(135deg, #d4e8d4 0%, #b8d8b8 100%); }
.map-placeholder-icon { font-size: 3.5rem; opacity: 0.7; }
.map-placeholder h3 { font-size: 1.1rem; font-weight: 700; }
.map-placeholder p { font-size: 0.85rem; opacity: 0.75; }
.map-placeholder .map-hint {
    background: var(--primary);
    color: #fff;
    padding: 8px 20px;
    border-radius: 20px;
    font-size: 0.82rem;
    font-weight: 600;
    margin-top: 4px;
}
#alo-map { height: 320px; width: 100%; display: none; }

/* ══════════════════════════════════════════════════════
   CONTENIDO CERCANO
   ══════════════════════════════════════════════════════ */
.nearby-tabs {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}
.nearby-tab {
    padding: 7px 16px;
    border-radius: 20px;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    border: 2px solid #ddd;
    background: #fff;
    color: var(--text-light);
    transition: all var(--transition);
}
.nearby-tab.active {
    background: var(--primary);
    color: #fff;
    border-color: var(--primary);
}
.nearby-tab:hover:not(.active) {
    border-color: var(--primary);
    color: var(--primary);
}
.nearby-panel { display: none; }
.nearby-panel.active { display: block; }

.nearby-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 14px;
}
.nearby-card {
    background: var(--bg-card);
    border-radius: var(--radius-sm);
    overflow: hidden;
    box-shadow: var(--shadow);
    cursor: pointer;
    transition: transform var(--transition), box-shadow var(--transition);
    text-decoration: none;
    color: inherit;
    display: block;
}
.nearby-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-hover);
    color: inherit;
}
.nearby-card-img {
    height: 130px;
    overflow: hidden;
    background: #e0e8e0;
    position: relative;
}
.nearby-card-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.35s;
}
.nearby-card:hover .nearby-card-img img { transform: scale(1.07); }
.nearby-card-img-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    background: linear-gradient(135deg, #e8f0e8, #d0e4d0);
}
.nearby-card-dist {
    position: absolute;
    bottom: 8px;
    right: 8px;
    background: rgba(0,0,0,0.55);
    color: #fff;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 10px;
    backdrop-filter: blur(3px);
}
.nearby-card-body { padding: 12px; }
.nearby-card-name {
    font-weight: 700;
    font-size: 0.88rem;
    margin-bottom: 4px;
    color: var(--text);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.35;
}
.nearby-card-meta {
    font-size: 0.78rem;
    color: var(--text-light);
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 4px;
}
.nearby-card-meta i { font-size: 0.72rem; }
.nearby-card-price {
    font-weight: 800;
    color: var(--primary);
    font-size: 0.9rem;
}
.nearby-card-free {
    font-weight: 700;
    color: #2e7d32;
    font-size: 0.82rem;
    background: #e8f5e9;
    padding: 2px 8px;
    border-radius: 10px;
    display: inline-block;
}
.nearby-card-date {
    font-size: 0.78rem;
    color: var(--accent-warm);
    font-weight: 600;
}

.nearby-loading {
    grid-column: 1 / -1;
    text-align: center;
    padding: 40px 20px;
    color: var(--text-light);
}
.nearby-loading i { font-size: 1.8rem; margin-bottom: 10px; display: block; }
.nearby-empty {
    grid-column: 1 / -1;
    text-align: center;
    padding: 30px 20px;
    color: var(--text-muted);
    font-size: 0.9rem;
}
.nearby-empty i { font-size: 2rem; margin-bottom: 8px; display: block; opacity: 0.4; }

.nearby-show-more {
    display: block;
    width: 100%;
    margin-top: 14px;
    padding: 10px;
    background: var(--bg);
    border: 1.5px solid #ddd;
    border-radius: var(--radius-sm);
    color: var(--primary);
    font-weight: 700;
    font-size: 0.88rem;
    cursor: pointer;
    transition: all var(--transition);
    text-align: center;
}
.nearby-show-more:hover { background: var(--primary); color: #fff; border-color: var(--primary); }

/* ══════════════════════════════════════════════════════
   SIDEBAR
   ══════════════════════════════════════════════════════ */
.sidebar { position: relative; }
.sidebar-sticky {
    position: sticky;
    top: 90px;
}

/* Precio sidebar */
.price-card {
    background: var(--bg-card);
    border-radius: var(--radius);
    box-shadow: var(--shadow-md);
    padding: 24px;
    margin-bottom: 20px;
    border-top: 4px solid var(--primary);
}
.price-card-amount {
    font-size: 2rem;
    font-weight: 800;
    color: var(--primary);
    line-height: 1;
    margin-bottom: 4px;
}
.price-card-label {
    font-size: 0.8rem;
    color: var(--text-light);
    margin-bottom: 18px;
}
.price-card-features {
    list-style: none;
    margin-bottom: 20px;
}
.price-card-features li {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.88rem;
    padding: 6px 0;
    border-bottom: 1px solid #f0f0f0;
    color: var(--text);
}
.price-card-features li:last-child { border-bottom: none; }
.price-card-features li i { color: var(--primary); font-size: 0.85rem; width: 16px; }
.btn-reservar {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 14px;
    background: var(--primary);
    color: #fff;
    border: none;
    border-radius: var(--radius-sm);
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    transition: all var(--transition);
    margin-bottom: 10px;
}
.btn-reservar:hover { background: var(--primary-light); color: #fff; transform: translateY(-2px); box-shadow: var(--shadow-md); }
.btn-contactar {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 12px;
    background: transparent;
    color: var(--primary);
    border: 2px solid var(--primary);
    border-radius: var(--radius-sm);
    font-size: 0.9rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    transition: all var(--transition);
}
.btn-contactar:hover { background: var(--primary); color: #fff; }

/* CTA card */
.cta-card {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: #fff;
    border-radius: var(--radius);
    padding: 24px;
    text-align: center;
    margin-bottom: 20px;
}
.cta-card h3 { font-size: 1.1rem; margin-bottom: 8px; }
.cta-card p { opacity: 0.88; margin-bottom: 18px; font-size: 0.85rem; line-height: 1.5; }
.cta-btns { display: flex; flex-direction: column; gap: 10px; }
.btn-cta-register {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 13px;
    background: #fff;
    color: var(--primary);
    border-radius: var(--radius-sm);
    font-weight: 700;
    font-size: 0.9rem;
    text-decoration: none;
    transition: all var(--transition);
}
.btn-cta-register:hover { background: var(--accent); color: var(--primary-dark); transform: translateY(-2px); }
.btn-cta-login {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px;
    background: transparent;
    color: rgba(255,255,255,0.85);
    border: 1.5px solid rgba(255,255,255,0.4);
    border-radius: var(--radius-sm);
    font-weight: 600;
    font-size: 0.88rem;
    text-decoration: none;
    transition: all var(--transition);
}
.btn-cta-login:hover { background: rgba(255,255,255,0.12); color: #fff; }

/* ── Error ── */
.error-container {
    text-align: center;
    padding: 100px 20px;
    max-width: 500px;
    margin: 0 auto;
}
.error-container .error-icon { font-size: 4rem; color: var(--accent-warm); margin-bottom: 20px; }
.error-container h1 { font-size: 1.6rem; margin-bottom: 12px; color: var(--primary); }
.error-container p { color: var(--text-light); margin-bottom: 24px; }
.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: var(--primary);
    color: #fff;
    border-radius: var(--radius-sm);
    font-weight: 700;
    text-decoration: none;
    transition: all var(--transition);
}
.btn-back:hover { background: var(--primary-light); color: #fff; }

/* ── Skeleton loader ── */
@keyframes shimmer {
    0% { background-position: -400px 0; }
    100% { background-position: 400px 0; }
}
.skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 800px 100%;
    animation: shimmer 1.4s infinite;
    border-radius: 6px;
}

/* ── Responsive ── */
@media (max-width: 768px) {
    .alo-hero { min-height: 320px; margin-top: 60px; }
    .alo-hero-title { font-size: 1.6rem; }
    .gallery-main-img { height: 260px; }
    .alo-layout { padding: 0 12px; }
    .alo-card { padding: 18px; }
    .features-grid { grid-template-columns: 1fr 1fr; }
    .nearby-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); }
    .alo-hero-actions { top: 12px; right: 12px; }
}
@media (max-width: 480px) {
    .alo-hero-title { font-size: 1.35rem; }
    .gallery-main-img { height: 220px; }
    .gallery-thumbs { grid-template-columns: repeat(4, 1fr); }
    .gallery-thumb { height: 58px; }
    .features-grid { grid-template-columns: 1fr; }
    .nearby-grid { grid-template-columns: 1fr 1fr; }
    .contact-btns { flex-direction: column; }
    .btn-contact { justify-content: center; }
}

/* ── Animación entrada ── */
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(18px); }
    to   { opacity: 1; transform: translateY(0); }
}
.alo-card { animation: fadeUp 0.4s ease both; }
.alo-card:nth-child(1) { animation-delay: 0.05s; }
.alo-card:nth-child(2) { animation-delay: 0.10s; }
.alo-card:nth-child(3) { animation-delay: 0.15s; }
.alo-card:nth-child(4) { animation-delay: 0.20s; }
.alo-card:nth-child(5) { animation-delay: 0.25s; }

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
    }
}
</style>

<!-- JSON-LD Schema.org -->
<?php if (!empty($jsonld)): ?>
<script type="application/ld+json"><?php echo $jsonld; ?></script>
<?php endif; ?>

<!-- hreflang -->
<?php if ($alojamiento): ?>
<link rel="alternate" hreflang="es" href="https://rutasrurales.io/alojamiento/<?php echo htmlspecialchars($alojamiento['slug']); ?>">
<link rel="alternate" hreflang="x-default" href="https://rutasrurales.io/alojamiento/<?php echo htmlspecialchars($alojamiento['slug']); ?>">
<?php endif; ?>

<!-- Open Graph -->
<meta property="og:type" content="website">
<meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($page_desc); ?>">
<meta property="og:image" content="<?php echo htmlspecialchars($foto_og); ?>">
<meta property="og:url" content="<?php echo $canonical; ?>">
<meta property="og:site_name" content="Rutas Rurales">

<!-- Preload imagen hero -->
<?php if (!empty($fotos[0])): ?>
<link rel="preload" as="image" href="<?php echo htmlspecialchars($fotos[0]); ?>">
<?php endif; ?>

<main>
<?php if ($alojamiento): ?>

<!-- ═══════════════════════════════════════════════════════════════════════════
     HERO
     ═══════════════════════════════════════════════════════════════════════════ -->
<section class="alo-hero" id="alo-hero">
    <div class="alo-hero-bg" id="heroBg"
         style="background-image: url('<?php echo htmlspecialchars($fotos[0]); ?>')">
    </div>

    <!-- Botones flotantes -->
    <div class="alo-hero-actions">
        <button class="alo-hero-action-btn" id="btnShare" title="<?php echo $t['compartir'] ?? 'Compartir'; ?>" aria-label="<?php echo $t['compartir'] ?? 'Compartir'; ?>">
            <i class="fas fa-share-alt"></i>
        </button>
        <button class="alo-hero-action-btn" id="btnFav" title="<?php echo $t['favorito'] ?? 'Favorito'; ?>" aria-label="<?php echo $t['favorito'] ?? 'Favorito'; ?>">
            <i class="far fa-heart"></i>
        </button>
    </div>

    <div class="alo-hero-content">
        <!-- Breadcrumb -->
        <nav class="alo-breadcrumb" aria-label="breadcrumb">
            <a href="/index.html"><i class="fas fa-home"></i></a>
            <span class="sep">/</span>
            <a href="/alojamientos-turisticos.html"><?php echo $t['alojamientos'] ?? 'Alojamientos'; ?></a>
            <span class="sep">/</span>
            <span class="current"><?php echo htmlspecialchars($alojamiento['name'], ENT_QUOTES, 'UTF-8'); ?></span>
        </nav>

        <div class="alo-hero-badge">
            <i class="fas fa-home"></i>
            <?php echo htmlspecialchars($tipo_display, ENT_QUOTES, 'UTF-8'); ?>
        </div>

        <h1 class="alo-hero-title">
            <?php echo htmlspecialchars($alojamiento['name'], ENT_QUOTES, 'UTF-8'); ?>
        </h1>

        <div class="alo-hero-meta">
            <?php if (!empty($alojamiento['municipality']) || !empty($alojamiento['province'])): ?>
            <div class="alo-hero-meta-item">
                <i class="fas fa-map-marker-alt"></i>
                <span><?php
                    $loc = [];
                    if (!empty($alojamiento['municipality'])) $loc[] = htmlspecialchars($alojamiento['municipality'], ENT_QUOTES, 'UTF-8');
                    if (!empty($alojamiento['province']))     $loc[] = htmlspecialchars($alojamiento['province'], ENT_QUOTES, 'UTF-8');
                    echo implode(', ', $loc);
                ?></span>
            </div>
            <?php endif; ?>

            <?php if (!empty($capacidad_display)): ?>
            <div class="alo-hero-meta-item">
                <i class="fas fa-users"></i>
                <span><?php echo htmlspecialchars($capacidad_display, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <?php endif; ?>

            <?php if (!empty($alojamiento['check_in_time'])): ?>
            <div class="alo-hero-meta-item">
                <i class="fas fa-clock"></i>
                <span><?php echo $t['checkin']; ?>: <?php echo htmlspecialchars($alojamiento['check_in_time'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <?php endif; ?>

            <?php if (!empty($precio_display)): ?>
            <div class="alo-hero-price-badge">
                <?php echo $t['precio_desde'] ?? 'Desde'; ?> <?php echo htmlspecialchars($precio_display, ENT_QUOTES, 'UTF-8'); ?> / <?php echo $t['noche'] ?? 'noche'; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════════════════
     LAYOUT PRINCIPAL
     ═══════════════════════════════════════════════════════════════════════════ -->
<div class="alo-layout">

    <!-- ── COLUMNA PRINCIPAL ── -->
    <div class="main-col">

        <!-- GALERÍA -->
        <?php if (!empty($fotos)): ?>
        <div class="alo-card" id="secGaleria">
            <h2 class="section-title"><i class="fas fa-images"></i> <?php echo $t['fotos'] ?? 'Fotos'; ?></h2>

            <div class="gallery-main" id="galleryMain" role="button" tabindex="0" aria-label="Abrir galería">
                <img id="galleryMainImg"
                     src="<?php echo htmlspecialchars($fotos[0], ENT_QUOTES, 'UTF-8'); ?>"
                     alt="<?php echo htmlspecialchars($alojamiento['name'], ENT_QUOTES, 'UTF-8'); ?>"
                     class="gallery-main-img"
                     loading="eager"
                     width="800" height="380">
                <?php if (count($fotos) > 1): ?>
                <span class="gallery-counter" id="galleryCounter">1 / <?php echo count($fotos); ?></span>
                <button class="gallery-expand-btn" id="galleryExpandBtn" type="button">
                    <i class="fas fa-expand-alt"></i> Ver todas
                </button>
                <?php endif; ?>
            </div>

            <?php if (count($fotos) > 1): ?>
            <div class="gallery-thumbs" id="galleryThumbs">
                <?php foreach ($fotos as $i => $foto): ?>
                <div class="gallery-thumb <?php echo $i === 0 ? 'active' : ''; ?>"
                     data-index="<?php echo $i; ?>"
                     data-src="<?php echo htmlspecialchars($foto, ENT_QUOTES, 'UTF-8'); ?>"
                     role="button" tabindex="0"
                     aria-label="Foto <?php echo $i+1; ?>">
                    <img src="<?php echo htmlspecialchars($foto, ENT_QUOTES, 'UTF-8'); ?>"
                         alt="Foto <?php echo $i+1; ?> de <?php echo htmlspecialchars($alojamiento['name'], ENT_QUOTES, 'UTF-8'); ?>"
                         loading="<?php echo $i < 3 ? 'eager' : 'lazy'; ?>">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- DESCRIPCIÓN -->
        <div class="alo-card" id="secDescripcion">
            <h2 class="section-title"><i class="fas fa-align-left"></i> <?php echo $t['descripcion'] ?? 'Descripción'; ?></h2>

            <?php if (!empty($alojamiento['description'])): ?>
            <?php $desc = nl2br(htmlspecialchars($alojamiento['description'], ENT_QUOTES, 'UTF-8')); ?>
            <?php $longDesc = strlen($alojamiento['description']) > 300; ?>
            <div class="desc-text <?php echo $longDesc ? 'collapsed' : ''; ?>" id="descText">
                <?php echo $desc; ?>
            </div>
            <?php if ($longDesc): ?>
            <button class="desc-expand-btn" id="descExpandBtn" type="button">
                <i class="fas fa-chevron-down"></i> Leer más
            </button>
            <?php endif; ?>
            <?php else: ?>
            <p style="color: var(--text-light); font-style: italic;">No hay descripción disponible.</p>
            <?php endif; ?>

            <div class="features-grid">
                <?php if (!empty($tipo_display)): ?>
                <div class="feature-item">
                    <i class="fas fa-home"></i>
                    <div>
                        <strong><?php echo $t['tipo'] ?? 'Tipo'; ?></strong>
                        <p><?php echo htmlspecialchars($tipo_display, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($capacidad_display)): ?>
                <div class="feature-item">
                    <i class="fas fa-users"></i>
                    <div>
                        <strong><?php echo $t['capacidad'] ?? 'Capacidad'; ?></strong>
                        <p><?php echo htmlspecialchars($capacidad_display, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($alojamiento['check_in_time'])): ?>
                <div class="feature-item">
                    <i class="fas fa-sign-in-alt"></i>
                    <div>
                        <strong><?php echo $t['checkin'] ?? 'Entrada'; ?></strong>
                        <p><?php echo htmlspecialchars($alojamiento['check_in_time'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($alojamiento['check_out_time'])): ?>
                <div class="feature-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <div>
                        <strong><?php echo $t['checkout'] ?? 'Salida'; ?></strong>
                        <p><?php echo htmlspecialchars($alojamiento['check_out_time'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($alojamiento['services'])): ?>
                <div class="feature-item">
                    <i class="fas fa-concierge-bell"></i>
                    <div>
                        <strong><?php echo $t['servicios'] ?? 'Servicios'; ?></strong>
                        <p><?php echo htmlspecialchars($alojamiento['services'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($alojamiento['price_per_night']) && $alojamiento['price_per_night'] > 0): ?>
                <div class="feature-item">
                    <i class="fas fa-euro-sign"></i>
                    <div>
                        <strong><?php echo $t['precio_noche'] ?? 'Precio'; ?></strong>
                        <p><?php echo htmlspecialchars($precio_display, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- CONTACTO -->
        <?php if (!empty($alojamiento['phone']) || !empty($alojamiento['email']) || !empty($alojamiento['website'])): ?>
        <div class="alo-card" id="secContacto">
            <h2 class="section-title"><i class="fas fa-phone-alt"></i> <?php echo $t['contacto'] ?? 'Contacto'; ?></h2>

            <div class="contact-btns">
                <?php if (!empty($alojamiento['phone'])): ?>
                <a href="tel:<?php echo htmlspecialchars($alojamiento['phone'], ENT_QUOTES, 'UTF-8'); ?>"
                   class="btn-contact btn-phone">
                    <i class="fas fa-phone"></i> <?php echo $t['llamar'] ?? 'Llamar'; ?>
                </a>
                <a href="https://wa.me/34<?php echo preg_replace('/[^0-9]/', '', $alojamiento['phone']); ?>"
                   target="_blank" rel="noopener"
                   class="btn-contact btn-whatsapp">
                    <i class="fab fa-whatsapp"></i> <?php echo $t['whatsapp'] ?? 'WhatsApp'; ?>
                </a>
                <?php endif; ?>

                <?php if (!empty($alojamiento['email'])): ?>
                <a href="mailto:<?php echo htmlspecialchars($alojamiento['email'], ENT_QUOTES, 'UTF-8'); ?>"
                   class="btn-contact btn-email">
                    <i class="fas fa-envelope"></i> <?php echo $t['email'] ?? 'Email'; ?>
                </a>
                <?php endif; ?>

                <?php if (!empty($alojamiento['website'])): ?>
                <a href="<?php echo htmlspecialchars($alojamiento['website'], ENT_QUOTES, 'UTF-8'); ?>"
                   target="_blank" rel="noopener"
                   class="btn-contact btn-website">
                    <i class="fas fa-globe"></i> <?php echo $t['web'] ?? 'Web'; ?>
                </a>
                <?php endif; ?>
            </div>

            <?php if (!empty($alojamiento['address'])): ?>
            <div class="contact-address">
                <i class="fas fa-map-marker-alt"></i>
                <span><?php echo htmlspecialchars($alojamiento['address'], ENT_QUOTES, 'UTF-8'); ?><?php
                    if (!empty($alojamiento['municipality'])) echo ', ' . htmlspecialchars($alojamiento['municipality'], ENT_QUOTES, 'UTF-8');
                    if (!empty($alojamiento['province']))     echo ' (' . htmlspecialchars($alojamiento['province'], ENT_QUOTES, 'UTF-8') . ')';
                ?></span>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- MAPA -->
        <?php if (!empty($alojamiento['latitude']) && !empty($alojamiento['longitude'])): ?>
        <div class="alo-card" id="secMapa">
            <h2 class="section-title"><i class="fas fa-map"></i> <?php echo $t['ubicacion'] ?? 'Ubicación'; ?></h2>
            <div class="map-wrapper">
                <div id="mapPlaceholder" class="map-placeholder" role="button" tabindex="0" aria-label="<?php echo $t['ver_mapa'] ?? 'Ver mapa'; ?>">
                    <div class="map-placeholder-icon">🗺️</div>
                    <h3><?php echo $t['ver_mapa'] ?? 'Ver mapa'; ?></h3>
                    <p><?php echo htmlspecialchars($alojamiento['municipality'] ?? '', ENT_QUOTES, 'UTF-8'); ?><?php echo !empty($alojamiento['province']) ? ', ' . htmlspecialchars($alojamiento['province'], ENT_QUOTES, 'UTF-8') : ''; ?></p>
                    <span class="map-hint"><i class="fas fa-mouse-pointer"></i> <?php echo $t['click_mapa'] ?? 'Haz clic para ver mapa'; ?></span>
                </div>
                <div id="alo-map"></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- CONTENIDO CERCANO -->
        <div class="alo-card" id="secCercanos">
            <h2 class="section-title"><i class="fas fa-compass"></i> <?php echo $t['cercanos'] ?? '¿Qué hay cerca?'; ?></h2>

            <div class="nearby-tabs" role="tablist">
                <button class="nearby-tab active" data-tab="alojamientos" role="tab" aria-selected="true">
                    <?php echo $t['alojamientos_cercanos'] ?? 'Alojamientos'; ?>
                </button>
                <button class="nearby-tab" data-tab="lugares" role="tab" aria-selected="false">
                    <?php echo $t['lugares_cercanos'] ?? 'Lugares'; ?>
                </button>
                <button class="nearby-tab" data-tab="eventos" role="tab" aria-selected="false">
                    <?php echo $t['eventos_cercanos'] ?? 'Eventos'; ?>
                </button>
                <button class="nearby-tab" data-tab="actividades" role="tab" aria-selected="false">
                    <?php echo $t['actividades_cercanas'] ?? 'Actividades'; ?>
                </button>
            </div>

            <div id="nearby-alojamientos" class="nearby-panel active" role="tabpanel">
                <div class="nearby-grid">
                    <div class="nearby-loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span><?php echo $t['cargando'] ?? 'Cargando...'; ?></span>
                    </div>
                </div>
            </div>
            <div id="nearby-lugares" class="nearby-panel" role="tabpanel">
                <div class="nearby-grid">
                    <div class="nearby-loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span><?php echo $t['cargando'] ?? 'Cargando...'; ?></span>
                    </div>
                </div>
            </div>
            <div id="nearby-eventos" class="nearby-panel" role="tabpanel">
                <div class="nearby-grid">
                    <div class="nearby-loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span><?php echo $t['cargando'] ?? 'Cargando...'; ?></span>
                    </div>
                </div>
            </div>
            <div id="nearby-actividades" class="nearby-panel" role="tabpanel">
                <div class="nearby-grid">
                    <div class="nearby-loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span><?php echo $t['cargando'] ?? 'Cargando...'; ?></span>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /main-col -->

    <!-- ── SIDEBAR ── -->
    <aside class="sidebar">
        <div class="sidebar-sticky">

            <!-- Precio + Reserva -->
            <div class="price-card">
                <?php if (!empty($alojamiento['price_per_night']) && $alojamiento['price_per_night'] > 0): ?>
                <div class="price-card-amount"><?php echo htmlspecialchars($precio_display, ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="price-card-label"><?php echo $t['precio_noche'] ?? 'Precio por noche'; ?></div>
                <?php else: ?>
                <div class="price-card-amount" style="font-size:1.2rem;"><?php echo $t['consultar'] ?? 'Consultar'; ?></div>
                <div class="price-card-label" style="margin-bottom:18px;"></div>
                <?php endif; ?>

                <ul class="price-card-features">
                    <?php if (!empty($capacidad_display)): ?>
                    <li><i class="fas fa-users"></i> <?php echo htmlspecialchars($capacidad_display, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endif; ?>
                    <?php if (!empty($alojamiento['check_in_time'])): ?>
                    <li><i class="fas fa-sign-in-alt"></i> <?php echo $t['checkin'] ?? 'Check-in'; ?>: <?php echo htmlspecialchars($alojamiento['check_in_time'], ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endif; ?>
                    <?php if (!empty($alojamiento['check_out_time'])): ?>
                    <li><i class="fas fa-sign-out-alt"></i> <?php echo $t['checkout'] ?? 'Check-out'; ?>: <?php echo htmlspecialchars($alojamiento['check_out_time'], ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endif; ?>
                    <?php if (!empty($alojamiento['municipality'])): ?>
                    <li><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($alojamiento['municipality'], ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endif; ?>
                </ul>

                <?php if (!empty($alojamiento['phone'])): ?>
                <a href="tel:<?php echo htmlspecialchars($alojamiento['phone'], ENT_QUOTES, 'UTF-8'); ?>" class="btn-reservar">
                    <i class="fas fa-phone"></i> <?php echo $t['reservar'] ?? 'Reservar'; ?>
                </a>
                <?php elseif (!empty($alojamiento['email'])): ?>
                <a href="mailto:<?php echo htmlspecialchars($alojamiento['email'], ENT_QUOTES, 'UTF-8'); ?>" class="btn-reservar">
                    <i class="fas fa-envelope"></i> <?php echo $t['reservar'] ?? 'Reservar'; ?>
                </a>
                <?php else: ?>
                <a href="#secContacto" class="btn-reservar">
                    <i class="fas fa-calendar-check"></i> <?php echo $t['reservar'] ?? 'Reservar'; ?>
                </a>
                <?php endif; ?>

                <?php if (!empty($alojamiento['phone'])): ?>
                <a href="https://wa.me/34<?php echo preg_replace('/[^0-9]/', '', $alojamiento['phone']); ?>"
                   target="_blank" rel="noopener" class="btn-contactar">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
                <?php endif; ?>
            </div>

            <!-- CTA Registro -->
            <div class="cta-card">
                <h3><?php echo $t['cta_titulo'] ?? '¿Te gusta este alojamiento?'; ?></h3>
                <p><?php echo $t['cta_desc'] ?? 'Regístrate para guardarlo en favoritos'; ?></p>
                <div class="cta-btns">
                    <a href="/register.html" class="btn-cta-register">
                        <i class="fas fa-user-plus"></i> <?php echo $t['cta_register'] ?? 'Regístrate'; ?>
                    </a>
                    <a href="/login.html" class="btn-cta-login">
                        <i class="fas fa-sign-in-alt"></i> <?php echo $t['cta_login'] ?? 'Entrar'; ?>
                    </a>
                </div>
            </div>

        </div>
    </aside>

</div><!-- /alo-layout -->

<!-- LIGHTBOX -->
<div class="lightbox-overlay" id="lightbox" role="dialog" aria-modal="true" aria-label="Galería de fotos">
    <button class="lightbox-close" id="lightboxClose" aria-label="Cerrar">&times;</button>
    <button class="lightbox-nav lightbox-prev" id="lightboxPrev" aria-label="Anterior"><i class="fas fa-chevron-left"></i></button>
    <img class="lightbox-img" id="lightboxImg" src="" alt="">
    <button class="lightbox-nav lightbox-next" id="lightboxNext" aria-label="Siguiente"><i class="fas fa-chevron-right"></i></button>
    <div class="lightbox-caption" id="lightboxCaption"></div>
</div>

<?php else: ?>
<!-- ERROR: Alojamiento no encontrado -->
<div class="error-container">
    <div class="error-icon"><i class="fas fa-exclamation-triangle"></i></div>
    <h1><?php echo $t['no_encontrado_h1'] ?? 'No encontrado'; ?></h1>
    <p><?php echo $t['no_encontrado_p'] ?? 'El alojamiento solicitado no existe.'; ?></p>
    <a href="/alojamientos-turisticos.html" class="btn-back">
        <i class="fas fa-arrow-left"></i> <?php echo $t['volver_lista'] ?? 'Volver'; ?>
    </a>
</div>
<?php endif; ?>
</main>

<!-- ── FOOTER PROPIO (sin include compartido para evitar conflictos) ── -->
<footer class="site-footer" style="background:#2F5233;color:#fff;padding:30px 15px;font-family:'Montserrat',sans-serif;">
    <div style="max-width:1200px;margin:0 auto;">
        <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:20px;margin-bottom:20px;">
            <div style="display:flex;flex-wrap:wrap;gap:20px;align-items:center;font-size:0.85rem;">
                <span><a href="mailto:olgamarin@rutasrurales.io" style="color:#fff;text-decoration:none;">✉ olgamarin@rutasrurales.io</a></span>
                <span><a href="tel:+34605249696" style="color:#fff;text-decoration:none;">📞 +34 605 249 696</a></span>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:20px;font-size:0.85rem;">
                <a href="/aviso-legal.html" style="color:#fff;text-decoration:none;">Aviso Legal</a>
                <a href="/politica-cookies.html" style="color:#fff;text-decoration:none;">Cookies</a>
                <a href="/agradecimientos.html" style="color:#fff;text-decoration:none;">Agradecimientos</a>
            </div>
            <div style="display:flex;gap:16px;">
                <a href="https://www.facebook.com/rutasrurales.io" target="_blank" style="color:#fff;font-size:1.1rem;text-decoration:none;">📘</a>
                <a href="https://www.instagram.com/rutas_rurales/" target="_blank" style="color:#fff;font-size:1.1rem;text-decoration:none;">📸</a>
                <a href="https://twitter.com/rutasrurales" target="_blank" style="color:#fff;font-size:1.1rem;text-decoration:none;">🐦</a>
            </div>
        </div>
        <div style="border-top:1px solid rgba(255,255,255,0.1);padding-top:15px;text-align:center;opacity:0.7;font-size:0.8rem;">
            <p>&copy; 2026 <strong>rutasrurales.io</strong>. Todos los derechos reservados.</p>
        </div>
    </div>
</footer>

<!-- ── SCRIPTS DIFERIDOS ── -->

<!-- Font Awesome diferido -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>

<!-- Leaflet CSS diferido (solo si hay mapa) -->
<?php if (!empty($alojamiento['latitude']) && !empty($alojamiento['longitude'])): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" media="print" onload="this.media='all'">
<?php endif; ?>

<!-- Datos del alojamiento para JS -->
<script>
const ALO = <?php echo $alojamiento_js; ?>;
const ALO_LANG = <?php echo json_encode([
    'ver_mas'        => $t['ver_mas']        ?? 'Ver más',
    'sin_resultados' => $t['sin_resultados'] ?? 'Sin resultados',
    'cargando'       => $t['cargando']       ?? 'Cargando…',
    'gratis'         => $t['gratis']         ?? 'Gratis',
    'km'             => $t['km']             ?? 'km',
    'noche'          => $t['noche']          ?? 'noche',
    'desde'          => $t['desde']          ?? 'desde',
]); ?>;
const ALO_FOTOS = <?php echo json_encode($fotos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>

<!-- Script principal diferido -->
<script defer src="/alojamiento-modular/js/alojamiento.js"></script>

<!-- Script global del sitio -->
<script defer src="/script.js?v=20260114"></script>

</body>
</html>
