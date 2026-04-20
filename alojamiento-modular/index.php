<?php
/**
 * ALOJAMIENTO MODULAR - Página de Detalle de Alojamiento
 * Versión optimizada para velocidad y enganche de turistas
 * 
 * URL: /alojamiento-modular/{slug}
 * Prueba: https://rutasrurales.io/alojamiento-modular/{slug}
 * Producción final: /alojamiento/{slug} (reemplazará alojamiento-detalle.php)
 */

define('API_NO_HEADERS', true);
require_once '../api/config.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$lang = isset($_GET['lang']) ? trim($_GET['lang']) : 'es';
$lang = in_array($lang, ['es', 'en', 'fr', 'de', 'zh']) ? $lang : 'es';

// ─── OBTENER DATOS CRÍTICOS DEL ALOJAMIENTO (SSR para SEO) ────────────────────────
$alojamiento = null;
$fotos = [];

if (!empty($slug)) {
    try {
        $pdo = getDBConnection();
        
        // Query principal con JOIN para categoría
        $stmt = $pdo->prepare("
            SELECT a.*, c.name as category_name
            FROM accommodations a
            LEFT JOIN categories_accommodations c ON a.category_id = c.id
            WHERE a.slug = ? AND a.is_active = 1
        ");
        $stmt->execute([$slug]);
        $alojamiento = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($alojamiento) {
            // Construir array de fotos
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
            // Si no hay fotos, usar una por defecto
            if (empty($fotos)) {
                $fotos[] = 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&h=600&fit=crop';
            }
        }
    } catch (Exception $e) {
        error_log('alojamiento-modular/index.php error: ' . $e->getMessage());
    }
}

// ─── SEO ─────────────────────────────────────────────────────────────────────
$page_title = $alojamiento ? ($alojamiento['meta_title'] ?: $alojamiento['name'] . ' - ' . $alojamiento['municipality'] . ' | Rutas Rurales') : 'Alojamiento | Rutas Rurales';
$page_desc = $alojamiento ? ($alojamiento['meta_description'] ?: $alojamiento['description'] ?: 'Alojamiento turístico en ' . $alojamiento['municipality']) : 'Descubre este alojamiento en Rutas Rurales';
$canonical = 'https://rutasrurales.io/' . ($lang !== 'es' ? $lang . '/' : '') . 'alojamiento/' . $slug;
$foto_og = !empty($fotos[0]) ? $fotos[0] : 'https://rutasrurales.io/menu_images/og-default.jpg';

// ─── TRADUCCIONES DE UI ───────────────────────────────────────────────────────
$ui = [
    'es' => [
        'alojamiento' => 'Alojamiento',
        'capacidad' => 'Capacidad',
        'personas' => 'personas',
        'tipo' => 'Tipo',
        'precio_noche' => 'Precio por noche',
        'consultar' => 'Consultar',
        'contacto' => 'Contacto',
        'llamar' => 'Llamar',
        'whatsapp' => 'WhatsApp',
        'email' => 'Email',
        'descripcion' => 'Descripción',
        'caracteristicas' => 'Características',
        'servicios' => 'Servicios',
        'ubicacion' => 'Ubicación',
        'ver_mapa' => 'Ver en el mapa',
        'click_mapa' => 'Haz clic para cargar el mapa interactivo',
        'cercanos' => '¿Qué hay cerca?',
        'alojamientos_cercanos' => '🏠 Alojamientos cercanos',
        'lugares_cercanos' => '🏛️ Lugares de interés cercanos',
        'eventos_cercanos' => '🎭 Eventos culturales cercanos',
        'actividades_cercanas' => '🎯 Actividades turísticas cercanas',
        'ver_mas' => 'Ver más',
        'no_encontrado_h1' => 'Alojamiento no encontrado',
        'no_encontrado_p' => 'El alojamiento que buscas no existe o ya no está disponible.',
        'volver_lista' => '← Volver a la lista de alojamientos',
        'cta_titulo' => '¿Te gusta este alojamiento?',
        'cta_desc' => 'Regístrate gratis para guardarlo en tus favoritos y recibir ofertas similares',
        'cta_register' => '✨ Registrarme gratis',
        'cta_login' => 'Ya tengo cuenta',
        'fotos' => 'Fotos',
        'km' => 'km',
        'gratis' => 'Gratis',
        'desde' => 'desde',
    ],
    'en' => [
        'alojamiento' => 'Accommodation',
        'capacidad' => 'Capacity',
        'personas' => 'people',
        'tipo' => 'Type',
        'precio_noche' => 'Price per night',
        'consultar' => 'Check price',
        'contacto' => 'Contact',
        'llamar' => 'Call',
        'whatsapp' => 'WhatsApp',
        'email' => 'Email',
        'descripcion' => 'Description',
        'caracteristicas' => 'Features',
        'servicios' => 'Services',
        'ubicacion' => 'Location',
        'ver_mapa' => 'View on map',
        'click_mapa' => 'Click to load the interactive map',
        'cercanos' => 'What\'s nearby?',
        'alojamientos_cercanos' => '🏠 Nearby accommodation',
        'lugares_cercanos' => '🏛️ Nearby places of interest',
        'eventos_cercanos' => '🎭 Nearby cultural events',
        'actividades_cercanas' => '🎯 Nearby tourist activities',
        'ver_mas' => 'View more',
        'no_encontrado_h1' => 'Accommodation not found',
        'no_encontrado_p' => 'The accommodation you are looking for does not exist or is no longer available.',
        'volver_lista' => '← Back to accommodation list',
        'cta_titulo' => 'Do you like this accommodation?',
        'cta_desc' => 'Sign up for free to save it to your favorites and receive similar offers',
        'cta_register' => '✨ Sign up free',
        'cta_login' => 'I already have an account',
        'fotos' => 'Photos',
        'km' => 'km',
        'gratis' => 'Free',
        'desde' => 'from',
    ],
    'fr' => [
        'alojamiento' => 'Hébergement',
        'capacidad' => 'Capacité',
        'personas' => 'personnes',
        'tipo' => 'Type',
        'precio_noche' => 'Prix par nuit',
        'consultar' => 'Consulter le prix',
        'contacto' => 'Contact',
        'llamar' => 'Appeler',
        'whatsapp' => 'WhatsApp',
        'email' => 'Email',
        'descripcion' => 'Description',
        'caracteristicas' => 'Caractéristiques',
        'servicios' => 'Services',
        'ubicacion' => 'Emplacement',
        'ver_mapa' => 'Voir sur la carte',
        'click_mapa' => 'Cliquez pour charger la carte interactive',
        'cercanos' => 'Qu\'y a-t-il à proximité ?',
        'alojamientos_cercanos' => '🏠 Hébergements à proximité',
        'lugares_cercanos' => '🏛️ Sites d\'intérêt à proximité',
        'eventos_cercanos' => '🎭 Événements culturels à proximité',
        'actividades_cercanas' => '🎯 Activités touristiques à proximité',
        'ver_mas' => 'Voir plus',
        'no_encontrado_h1' => 'Hébergement introuvable',
        'no_encontrado_p' => 'L\'hébergement que vous recherchez n\'existe pas ou n\'est plus disponible.',
        'volver_lista' => '← Retour à la liste des hébergements',
        'cta_titulo' => 'Vous aimez cet hébergement ?',
        'cta_desc' => 'Inscrivez-vous gratuitement pour l\'ajouter à vos favoris et recevoir des offres similaires',
        'cta_register' => '✨ S\'inscrire gratuitement',
        'cta_login' => 'J\'ai déjà un compte',
        'fotos' => 'Photos',
        'km' => 'km',
        'gratis' => 'Gratuit',
        'desde' => 'à partir de',
    ],
    'de' => [
        'alojamiento' => 'Unterkunft',
        'capacidad' => 'Kapazität',
        'personas' => 'Personen',
        'tipo' => 'Typ',
        'precio_noche' => 'Preis pro Nacht',
        'consultar' => 'Preis anfragen',
        'contacto' => 'Kontakt',
        'llamar' => 'Anrufen',
        'whatsapp' => 'WhatsApp',
        'email' => 'E-Mail',
        'descripcion' => 'Beschreibung',
        'caracteristicas' => 'Merkmale',
        'servicios' => 'Dienstleistungen',
        'ubicacion' => 'Standort',
        'ver_mapa' => 'Auf der Karte anzeigen',
        'click_mapa' => 'Klicken Sie, um die interaktive Karte zu laden',
        'cercanos' => 'Was gibt es in der Nähe?',
        'alojamientos_cercanos' => '🏠 Unterkünfte in der Nähe',
        'lugares_cercanos' => '🏛️ Sehenswürdigkeiten in der Nähe',
        'eventos_cercanos' => '🎭 Kulturelle Veranstaltungen in der Nähe',
        'actividades_cercanas' => '🎯 Touristische Aktivitäten in der Nähe',
        'ver_mas' => 'Mehr anzeigen',
        'no_encontrado_h1' => 'Unterkunft nicht gefunden',
        'no_encontrado_p' => 'Die gesuchte Unterkunft existiert nicht oder ist nicht mehr verfügbar.',
        'volver_lista' => '← Zurück zur Unterkunftsliste',
        'cta_titulo' => 'Gefällt Ihnen diese Unterkunft?',
        'cta_desc' => 'Registrieren Sie sich kostenlos, um sie zu Ihren Favoriten hinzuzufügen und ähnliche Angebote zu erhalten',
        'cta_register' => '✨ Kostenlos registrieren',
        'cta_login' => 'Ich habe bereits ein Konto',
        'fotos' => 'Fotos',
        'km' => 'km',
        'gratis' => 'Kostenlos',
        'desde' => 'ab',
    ],
    'zh' => [
        'alojamiento' => '住宿',
        'capacidad' => '容量',
        'personas' => '人',
        'tipo' => '类型',
        'precio_noche' => '每晚价格',
        'consultar' => '咨询价格',
        'contacto' => '联系',
        'llamar' => '打电话',
        'whatsapp' => 'WhatsApp',
        'email' => '电子邮件',
        'descripcion' => '描述',
        'caracteristicas' => '特色',
        'servicios' => '服务',
        'ubicacion' => '位置',
        'ver_mapa' => '在地图上查看',
        'click_mapa' => '点击加载互动地图',
        'cercanos' => '附近有什么？',
        'alojamientos_cercanos' => '🏠 附近住宿',
        'lugares_cercanos' => '🏛️ 附近景点',
        'eventos_cercanos' => '🎭 附近文化活动',
        'actividades_cercanas' => '🎯 附近旅游活动',
        'ver_mas' => '查看更多',
        'no_encontrado_h1' => '未找到住宿',
        'no_encontrado_p' => '您查找的住宿不存在或已不再提供。',
        'volver_lista' => '← 返回住宿列表',
        'cta_titulo' => '喜欢这个住宿吗？',
        'cta_desc' => '免费注册，将其添加到收藏夹并接收类似优惠',
        'cta_register' => '✨ 免费注册',
        'cta_login' => '我已有账户',
        'fotos' => '照片',
        'km' => '公里',
        'gratis' => '免费',
        'desde' => '起',
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

// Tipo de alojamiento
$tipo_display = $alojamiento['category_name'] ?? $alojamiento['accommodation_type'] ?? $t['alojamiento'];

// Capacidad
$capacidad_display = ($alojamiento['capacity'] ?? 0) > 0 ? $alojamiento['capacity'] . ' ' . $t['personas'] : '';

// JSON-LD para SEO (Schema.org LodgingBusiness)
$jsonld = '';
if ($alojamiento) {
    $jsonld_data = [
        '@context' => 'https://schema.org',
        '@type' => 'LodgingBusiness',
        'name' => $alojamiento['name'],
        'description' => strip_tags($alojamiento['description'] ?? ''),
        'address' => [
            '@type' => 'PostalAddress',
            'addressLocality' => $alojamiento['municipality'],
            'addressRegion' => $alojamiento['province'],
            'addressCountry' => 'ES'
        ],
        'priceRange' => $alojamiento['price_per_night'] ? $alojamiento['price_per_night'] . '€' : '',
        'telephone' => $alojamiento['phone'] ?? '',
        'email' => $alojamiento['email'] ?? '',
        'url' => $canonical,
        'image' => $fotos[0] ?? '',
        'checkinTime' => $alojamiento['check_in_time'] ?? '15:00',
        'checkoutTime' => $alojamiento['check_out_time'] ?? '12:00',
    ];
    if ($alojamiento['latitude'] && $alojamiento['longitude']) {
        $jsonld_data['geo'] = [
            '@type' => 'GeoCoordinates',
            'latitude' => $alojamiento['latitude'],
            'longitude' => $alojamiento['longitude']
        ];
    }
    $jsonld = json_encode($jsonld_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

// Datos para JavaScript (evitar segunda llamada API)
$alojamiento_js = $alojamiento ? json_encode([
    'id' => $alojamiento['id'],
    'name' => $alojamiento['name'],
    'slug' => $alojamiento['slug'],
    'latitude' => $alojamiento['latitude'],
    'longitude' => $alojamiento['longitude'],
    'province' => $alojamiento['province'],
    'municipality' => $alojamiento['municipality'],
    'address' => $alojamiento['address'] ?? '',
    'fotos' => $fotos,
    'tipo' => $tipo_display,
    'precio_noche' => $alojamiento['price_per_night'] ?? 0,
    'capacidad' => $alojamiento['capacity'] ?? 0,
    'phone' => $alojamiento['phone'] ?? '',
    'email' => $alojamiento['email'] ?? '',
    'description' => $alojamiento['description'] ?? '',
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) : 'null';

// Variables para header.php
$page_title = $page_title;
$page_description = $page_desc;
$page_canonical = $canonical;
$lang = $lang;

// Incluir header.php
include '../header.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_desc); ?>">
    <link rel="canonical" href="<?php echo $canonical; ?>">

    <!-- hreflang: SEO multiidioma -->
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

    <!-- JSON-LD Schema.org -->
    <?php if (!empty($jsonld)): ?>
    <script type="application/ld+json"><?php echo $jsonld; ?></script>
    <?php endif; ?>

    <!-- Favicon -->
    <link rel="icon" href="/menu_images/Favicon.png" type="image/png">

    <!-- Preconnect solo para recursos críticos -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>

    <!-- Fuentes locales (Montserrat) -->
    <style>
        @font-face {
            font-family: 'Montserrat';
            font-style: normal;
            font-weight: 400;
            font-display: swap;
            src: url('/fonts/Montserrat-Regular.woff2') format('woff2');
        }
        @font-face {
            font-family: 'Montserrat';
            font-style: normal;
            font-weight: 700;
            font-display: swap;
            src: url('/fonts/Montserrat-Bold.woff2') format('woff2');
        }
    </style>

    <!-- CSS CRÍTICO INLINE (solo lo esencial para el primer render) -->
    <style>
        /* ── Variables ── */
        :root {
            --primary: #2F5233;
            --primary-light: #3d6b42;
            --accent: #81C784;
            --accent-warm: #F9A825;
            --text: #333;
            --text-light: #666;
            --bg: #f8f9fa;
            --white: #fff;
            --radius: 12px;
            --shadow: 0 4px 20px rgba(0,0,0,0.08);
            --shadow-hover: 0 8px 30px rgba(0,0,0,0.15);
        }

        /* ── Reset mínimo ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Montserrat', 'Segoe UI', sans-serif;
            color: var(--text);
            background: var(--bg);
            line-height: 1.6;
            overflow-x: hidden;
        }
        img { max-width: 100%; height: auto; display: block; }
        a { color: var(--primary); text-decoration: none; }
        a:hover { color: var(--primary-light); }

        /* ── Layout principal ── */
        .alojamiento-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 16px;
        }

        /* ── Hero ── */
        .alojamiento-hero {
            margin-top: 70px;
            background: linear-gradient(135deg, var(--primary) 0%, #1a3d1e 100%);
            color: var(--white);
            padding: 30px 20px 50px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .hero-breadcrumb {
            max-width: 1200px;
            margin: 0 auto 20px;
            text-align: left;
        }
        .breadcrumb-nav a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
        }
        .breadcrumb-nav a:hover {
            color: var(--white);
        }
        .breadcrumb-nav span {
            color: var(--white);
            font-weight: 600;
        }
        .hero-badge {
            display: inline-block;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            color: var(--white);
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
            text-transform: uppercase;
        }
        .hero-title {
            font-size: clamp(1.6rem, 4vw, 2.8rem);
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 16px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .hero-meta {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            font-size: 0.95rem;
            opacity: 0.92;
        }
        .hero-meta span {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .hero-meta .icon { font-size: 1rem; }

        /* ── Grid principal ── */
        .alojamiento-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
            margin: -30px auto 60px;
            max-width: 1200px;
            padding: 0 16px;
        }
        @media (max-width: 900px) {
            .alojamiento-layout { grid-template-columns: 1fr; margin-top: -20px; }
        }

        /* ── Secciones ── */
        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--accent);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ── Galería ── */
        .alojamiento-gallery {
            background: var(--white);
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }
        .gallery-main {
            margin-bottom: 15px;
        }
        .main-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 10px;
        }
        .gallery-thumbnails {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
        }
        .thumbnail {
            width: 100%;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.2s;
        }
        .thumbnail:hover, .thumbnail.active {
            opacity: 1;
        }

        /* ── Descripción ── */
        .alojamiento-description {
            background: var(--white);
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }
        .description-content {
            line-height: 1.8;
            margin-bottom: 25px;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px;
            background: var(--bg);
            border-radius: 8px;
        }
        .feature-item i {
            color: var(--primary);
            font-size: 1.2rem;
            margin-top: 2px;
        }

        /* ── Contacto ── */
        .alojamiento-contact {
            background: var(--white);
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }
        .contact-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        .btn-contact {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-phone { background: var(--primary); color: var(--white); }
        .btn-whatsapp { background: #25D366; color: var(--white); }
        .btn-email { background: var(--accent-warm); color: var(--text); }
        .btn-website { background: var(--accent); color: var(--text); }
        .btn-contact:hover { transform: translateY(-2px); box-shadow: var(--shadow-hover); }
        .contact-address {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        /* ── Mapa ── */
        .alojamiento-map {
            background: var(--white);
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }
        .map-container {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        .map-placeholder {
            height: 300px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #e8f0e8, #d4e8d4);
            color: var(--primary);
            gap: 12px;
            cursor: pointer;
        }
        .map-placeholder:hover {
            background: linear-gradient(135deg, #d4e8d4, #c0e0c0);
        }
        .map-icon { font-size: 3rem; }
        .map { height: 300px; width: 100%; }

        /* ── Cercanos ── */
        .alojamiento-nearby {
            background: var(--white);
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }
        .nearby-sections {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }
        .nearby-section h3 {
            font-size: 1rem;
            color: var(--text);
            margin-bottom: 15px;
        }
        .nearby-grid {
            min-height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .loading-placeholder {
            text-align: center;
            color: var(--text-light);
        }

        /* ── CTA ── */
        .alojamiento-cta {
            background: var(--white);
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }
        .cta-card {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: var(--white);
            border-radius: var(--radius);
            padding: 25px;
            text-align: center;
        }
        .cta-card h3 {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }
        .cta-card p {
            opacity: 0.9;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .cta-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .btn-cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 20px;
            border-radius: 8px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-register {
            background: var(--white);
            color: var(--primary);
        }
        .btn-login {
            background: transparent;
            color: var(--white);
            border: 2px solid rgba(255,255,255,0.6);
        }
        .btn-cta:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        /* ── Error ── */
        .error-container {
            text-align: center;
            padding: 100px 20px;
        }
        .error-container i {
            font-size: 4rem;
            color: orange;
            margin-bottom: 20px;
        }

        /* ── Footer spacing ── */
        .footer-spacing {
            margin-bottom: 50px;
        }
    </style>
</head>
<body>
    <!-- Header ya incluido por header.php -->
    
    <main>
        <?php if ($alojamiento): ?>
            <?php include 'modules/hero.php'; ?>
            
            <div class="alojamiento-layout">
                <div class="main-content">
                    <?php include 'modules/galeria.php'; ?>
                    <?php include 'modules/descripcion.php'; ?>
                    <?php include 'modules/contacto.php'; ?>
                    <?php include 'modules/mapa.php'; ?>
                    <?php include 'modules/cercanos.php'; ?>
                </div>
                
                <div class="sidebar">
                    <?php include 'modules/cta.php'; ?>
                    <!-- Espacio para más widgets laterales -->
                </div>
            </div>
            
            <div class="footer-spacing"></div>
            
        <?php else: ?>
            <div class="error-container">
                <i class="fas fa-exclamation-triangle"></i>
                <h1><?php echo $t['no_encontrado_h1']; ?></h1>
                <p><?php echo $t['no_encontrado_p']; ?></p>
                <a href="/alojamientos-turisticos.html" class="btn-cta btn-register" style="margin-top: 20px;">
                    <?php echo $t['volver_lista']; ?>
                </a>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <?php include '../footer.php'; ?>

    <!-- JavaScript diferido -->
    <script>
        // Datos del alojamiento para JS
        const alojamientoData = <?php echo $alojamiento_js; ?>;
        
        // Cargar contenido cercano después del render inicial
        if (alojamientoData && alojamientoData.latitude && alojamientoData.longitude) {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(() => {
                    fetch(`/alojamiento-modular/api/alojamiento-data.php?slug=<?php echo $slug; ?>&lat=${alojamientoData.latitude}&lng=${alojamientoData.longitude}&radius=50`)
                        .then(res => res.json())
                        .then(data => {
                            // Aquí procesaremos los datos cercanos
                            console.log('Datos cercanos cargados:', data);
                        })
                        .catch(err => console.error('Error cargando datos cercanos:', err));
                }, 1000);
            });
        }
        
        // Mapa lazy load
        document.addEventListener('DOMContentLoaded', function() {
            const placeholder = document.getElementById('map-placeholder');
            if (placeholder) {
                placeholder.addEventListener('click', function() {
                    loadMap();
                });
            }
        });
        
        function loadMap() {
            if (!alojamientoData.latitude || !alojamientoData.longitude) return;
            
            // Cargar Leaflet dinámicamente
            const leafletCSS = document.createElement('link');
            leafletCSS.rel = 'stylesheet';
            leafletCSS.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            leafletCSS.integrity = 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=';
            leafletCSS.crossOrigin = '';
            document.head.appendChild(leafletCSS);
            
            const leafletJS = document.createElement('script');
            leafletJS.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            leafletJS.integrity = 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=';
            leafletJS.crossOrigin = '';
            leafletJS.onload = function() {
                // Ocultar placeholder, mostrar mapa
                document.getElementById('map-placeholder').style.display = 'none';
                const mapEl = document.getElementById('map');
                mapEl.style.display = 'block';
                
                // Inicializar mapa
                const map = L.map('map').setView([alojamientoData.latitude, alojamientoData.longitude], 13);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);
                
                // Marcador del alojamiento
                L.marker([alojamientoData.latitude, alojamientoData.longitude])
                    .addTo(map)
                    .bindPopup(`<b>${alojamientoData.name}</b><br>${alojamientoData.address || ''}`)
                    .openPopup();
                
                // Cargar marcadores cercanos
                fetch(`/alojamiento-modular/api/alojamiento-data.php?slug=<?php echo $slug; ?>&lat=${alojamientoData.latitude}&lng=${alojamientoData.longitude}&radius=50&mode=nearby`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && data.data) {
                            // Aquí agregaríamos marcadores para alojamientos, lugares, eventos cercanos
                            console.log('Marcadores cercanos cargados:', data.data);
                        }
                    });
            };
            document.head.appendChild(leafletJS);
        }
        
        // Cargar contenido cercano
        function loadNearbyContent() {
            if (!alojamientoData.latitude || !alojamientoData.longitude) return;
            
            fetch(`/alojamiento-modular/api/alojamiento-data.php?slug=<?php echo $slug; ?>&lat=${alojamientoData.latitude}&lng=${alojamientoData.longitude}&radius=50&mode=nearby`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.data) {
                        // Actualizar las secciones de contenido cercano
                        updateNearbySection('nearby-accommodations', data.data.alojamientos || []);
                        updateNearbySection('nearby-places', data.data.lugares || []);
                        updateNearbySection('nearby-events', data.data.eventos_similares || []);
                        updateNearbySection('nearby-activities', data.data.actividades || []);
                    }
                })
                .catch(err => console.error('Error cargando contenido cercano:', err));
        }
        
        function updateNearbySection(sectionId, items) {
            const section = document.getElementById(sectionId);
            if (!section || !items.length) return;
            
            section.innerHTML = '';
            items.slice(0, 3).forEach(item => {
                const card = createNearbyCard(item);
                section.appendChild(card);
            });
            
            if (items.length > 3) {
                const showMore = document.createElement('button');
                showMore.className = 'show-more-btn';
                showMore.textContent = '<?php echo $t['ver_mas']; ?>';
                showMore.onclick = () => {
                    // Aquí podríamos mostrar más items o redirigir a una página de búsqueda
                    window.location.href = `/rutas.php?lat=${alojamientoData.latitude}&lng=${alojamientoData.longitude}&radius=50`;
                };
                section.appendChild(showMore);
            }
        }
        
        function createNearbyCard(item) {
            const card = document.createElement('div');
            card.className = 'nearby-card';
            card.innerHTML = `
                <div class="nearby-card-img">
                    <img src="${item.main_image || '/img/placeholder.jpg'}" alt="${item.name}" loading="lazy">
                </div>
                <div class="nearby-card-body">
                    <div class="nearby-card-name">${item.name}</div>
                    <div class="nearby-card-meta">${item.municipality || ''}${item.distance ? ` · ${item.distance} km` : ''}</div>
                    ${item.price_per_night ? `<div class="nearby-card-price">${item.price_per_night}€</div>` : ''}
                </div>
            `;
            card.onclick = () => {
                if (item.url) window.location.href = item.url;
            };
            return card;
        }
        
        // Cargar contenido cercano después de 1 segundo
        if (alojamientoData && alojamientoData.latitude && alojamientoData.longitude) {
            setTimeout(loadNearbyContent, 1000);
        }
    </script>
</body>
</html>
           
