<?php
/**
 * head.php — Componente <head> SEO para lugar-modular
 * =====================================================
 * Renderiza dinámicamente:
 *   - Metatags SEO (Title, Description, Canonical, Robots)
 *   - OpenGraph y Twitter Cards
 *   - hreflang para todos los idiomas (lugares = contenido editorial, no necesita membresía)
 *   - Preload imagen hero (LCP)
 *   - Script JSON-LD inyectado desde components/schema.php
 *   - Fuentes Montserrat locales (font-face, no bloquea render)
 *   - CSS crítico inline mínimo (variables + reset)
 *   - CSS lugar.css cargado con preload (no render-blocking)
 *   - Variables JS globales para lugar.js
 *
 * Variables requeridas (deben estar definidas en index.php antes del include):
 *   $lugar, $fotos, $slug, $lang, $canonical, $foto_og,
 *   $page_title, $page_description, $lugar_js, $t
 *
 * Traducciones de UI ($ui array completo en 5 idiomas):
 *   Definido aquí y exportado como $t para uso en los demás componentes.
 */

// ─── SISTEMA DE TRADUCCIONES UI ──────────────────────────────────────────────
// Mismo patrón que alojamiento-modular: array $ui con 5 idiomas
$ui = [
    'es' => [
        'lugares'          => 'Lugares de interés',
        'inicio'           => 'Inicio',
        'descripcion'      => '📋 Descripción',
        'info_practica'    => 'ℹ️ Información práctica',
        'contacto'         => '📞 Contacto y acceso',
        'horario'          => 'Horario',
        'entrada'          => 'Entrada',
        'entrada_gratuita' => '✅ Entrada gratuita',
        'duracion_visita'  => 'Duración visita',
        'mejor_epoca'      => 'Mejor época',
        'accesibilidad'    => 'Accesibilidad',
        'mascotas'         => 'Mascotas',
        'admite_mascotas'  => 'Admitidas',
        'ninos'            => 'Familias',
        'apto_ninos'       => 'Apto para niños',
        'instalaciones'    => 'Instalaciones',
        'fotos'            => '📸 Galería de fotos',
        'ver_todas'        => '🔍 Ver todas',
        'llamar'           => '📞 Llamar',
        'whatsapp'         => '💬 WhatsApp',
        'email'            => '✉️ Email',
        'web_oficial'      => '🌐 Web oficial',
        'ver_mapa'         => 'Ver en el mapa',
        'click_mapa'       => 'Haz clic para cargar el mapa interactivo',
        'en_un_vistazo'    => '📌 En un vistazo',
        'como_llegar'      => '🗺️ Cómo llegar (Google Maps)',
        'te_gusta'         => '¿Te gusta este lugar?',
        'cta_desc'         => 'Guárdalo en favoritos y recibe alertas de eventos y actividades cercanas',
        'registrarme'      => '✨ Registrarme gratis',
        'ya_cuenta'        => 'Ya tengo cuenta',
        'compartir'        => 'Compartir este lugar',
        'dormir_cerca'     => '🏠 ¿Dónde dormir cerca?',
        'dormir_desc'      => 'Alojamientos rurales a pocos kilómetros',
        'activ_cercanas'   => '🎯 Actividades turísticas cercanas',
        'eventos_cercanos' => '🎭 Eventos culturales próximos',
        'lugares_cercanos' => '🏛️ Otros lugares de interés cerca',
        'ver_mas_aloj'     => 'Ver más alojamientos',
        'ver_mas_activ'    => 'Ver más actividades',
        'ver_mas_eventos'  => 'Ver más eventos',
        'ver_mas_lugares'  => 'Ver más lugares',
        'leer_mas'         => '↓ Leer más',
        'leer_menos'       => '↑ Leer menos',
        'no_encontrado_h1' => 'Lugar no encontrado',
        'no_encontrado_p'  => 'El lugar de interés que buscas no existe o ya no está disponible.',
        'volver_lista'     => '← Volver a los lugares de interés',
        'km'               => 'km',
        'gratis'           => 'Gratis',
        'aviso_legal'      => 'Aviso Legal',
        'cookies'          => 'Cookies',
        'agradecimientos'  => 'Agradecimientos',
    ],
    'en' => [
        'lugares'          => 'Places of interest',
        'inicio'           => 'Home',
        'descripcion'      => '📋 Description',
        'info_practica'    => 'ℹ️ Practical information',
        'contacto'         => '📞 Contact & access',
        'horario'          => 'Opening hours',
        'entrada'          => 'Admission',
        'entrada_gratuita' => '✅ Free admission',
        'duracion_visita'  => 'Visit duration',
        'mejor_epoca'      => 'Best season',
        'accesibilidad'    => 'Accessibility',
        'mascotas'         => 'Pets',
        'admite_mascotas'  => 'Allowed',
        'ninos'            => 'Families',
        'apto_ninos'       => 'Kid-friendly',
        'instalaciones'    => 'Facilities',
        'fotos'            => '📸 Photo gallery',
        'ver_todas'        => '🔍 View all',
        'llamar'           => '📞 Call',
        'whatsapp'         => '💬 WhatsApp',
        'email'            => '✉️ Email',
        'web_oficial'      => '🌐 Official website',
        'ver_mapa'         => 'View on map',
        'click_mapa'       => 'Click to load the interactive map',
        'en_un_vistazo'    => '📌 At a glance',
        'como_llegar'      => '🗺️ Directions (Google Maps)',
        'te_gusta'         => 'Do you like this place?',
        'cta_desc'         => 'Save it to your favourites and get alerts for nearby events and activities',
        'registrarme'      => '✨ Sign up free',
        'ya_cuenta'        => 'I already have an account',
        'compartir'        => 'Share this place',
        'dormir_cerca'     => '🏠 Where to sleep nearby?',
        'dormir_desc'      => 'Rural accommodation a few kilometres away',
        'activ_cercanas'   => '🎯 Nearby tourist activities',
        'eventos_cercanos' => '🎭 Upcoming cultural events',
        'lugares_cercanos' => '🏛️ Other nearby places of interest',
        'ver_mas_aloj'     => 'View more accommodation',
        'ver_mas_activ'    => 'View more activities',
        'ver_mas_eventos'  => 'View more events',
        'ver_mas_lugares'  => 'View more places',
        'leer_mas'         => '↓ Read more',
        'leer_menos'       => '↑ Read less',
        'no_encontrado_h1' => 'Place not found',
        'no_encontrado_p'  => 'The place you are looking for does not exist or is no longer available.',
        'volver_lista'     => '← Back to places of interest',
        'km'               => 'km',
        'gratis'           => 'Free',
        'aviso_legal'      => 'Legal Notice',
        'cookies'          => 'Cookies',
        'agradecimientos'  => 'Acknowledgements',
    ],
    'fr' => [
        'lugares'          => 'Lieux d\'intérêt',
        'inicio'           => 'Accueil',
        'descripcion'      => '📋 Description',
        'info_practica'    => 'ℹ️ Informations pratiques',
        'contacto'         => '📞 Contact et accès',
        'horario'          => 'Horaires',
        'entrada'          => 'Entrée',
        'entrada_gratuita' => '✅ Entrée gratuite',
        'duracion_visita'  => 'Durée de la visite',
        'mejor_epoca'      => 'Meilleure saison',
        'accesibilidad'    => 'Accessibilité',
        'mascotas'         => 'Animaux',
        'admite_mascotas'  => 'Admis',
        'ninos'            => 'Familles',
        'apto_ninos'       => 'Adapté aux enfants',
        'instalaciones'    => 'Installations',
        'fotos'            => '📸 Galerie photos',
        'ver_todas'        => '🔍 Voir toutes',
        'llamar'           => '📞 Appeler',
        'whatsapp'         => '💬 WhatsApp',
        'email'            => '✉️ E-mail',
        'web_oficial'      => '🌐 Site officiel',
        'ver_mapa'         => 'Voir sur la carte',
        'click_mapa'       => 'Cliquez pour charger la carte interactive',
        'en_un_vistazo'    => '📌 En un coup d\'œil',
        'como_llegar'      => '🗺️ Comment y aller (Google Maps)',
        'te_gusta'         => 'Vous aimez cet endroit ?',
        'cta_desc'         => 'Ajoutez-le à vos favoris et recevez des alertes sur les événements et activités à proximité',
        'registrarme'      => '✨ S\'inscrire gratuitement',
        'ya_cuenta'        => 'J\'ai déjà un compte',
        'compartir'        => 'Partager ce lieu',
        'dormir_cerca'     => '🏠 Où dormir à proximité ?',
        'dormir_desc'      => 'Hébergements ruraux à quelques kilomètres',
        'activ_cercanas'   => '🎯 Activités touristiques à proximité',
        'eventos_cercanos' => '🎭 Événements culturels à venir',
        'lugares_cercanos' => '🏛️ Autres lieux d\'intérêt proches',
        'ver_mas_aloj'     => 'Voir plus d\'hébergements',
        'ver_mas_activ'    => 'Voir plus d\'activités',
        'ver_mas_eventos'  => 'Voir plus d\'événements',
        'ver_mas_lugares'  => 'Voir plus de lieux',
        'leer_mas'         => '↓ Lire plus',
        'leer_menos'       => '↑ Lire moins',
        'no_encontrado_h1' => 'Lieu introuvable',
        'no_encontrado_p'  => 'Le lieu que vous recherchez n\'existe pas ou n\'est plus disponible.',
        'volver_lista'     => '← Retour aux lieux d\'intérêt',
        'km'               => 'km',
        'gratis'           => 'Gratuit',
        'aviso_legal'      => 'Mentions légales',
        'cookies'          => 'Cookies',
        'agradecimientos'  => 'Remerciements',
    ],
    'de' => [
        'lugares'          => 'Sehenswürdigkeiten',
        'inicio'           => 'Startseite',
        'descripcion'      => '📋 Beschreibung',
        'info_practica'    => 'ℹ️ Praktische Informationen',
        'contacto'         => '📞 Kontakt & Anreise',
        'horario'          => 'Öffnungszeiten',
        'entrada'          => 'Eintritt',
        'entrada_gratuita' => '✅ Freier Eintritt',
        'duracion_visita'  => 'Besuchsdauer',
        'mejor_epoca'      => 'Beste Jahreszeit',
        'accesibilidad'    => 'Barrierefreiheit',
        'mascotas'         => 'Haustiere',
        'admite_mascotas'  => 'Erlaubt',
        'ninos'            => 'Familien',
        'apto_ninos'       => 'Kinderfreundlich',
        'instalaciones'    => 'Einrichtungen',
        'fotos'            => '📸 Fotogalerie',
        'ver_todas'        => '🔍 Alle anzeigen',
        'llamar'           => '📞 Anrufen',
        'whatsapp'         => '💬 WhatsApp',
        'email'            => '✉️ E-Mail',
        'web_oficial'      => '🌐 Offizielle Website',
        'ver_mapa'         => 'Auf der Karte anzeigen',
        'click_mapa'       => 'Klicken Sie, um die interaktive Karte zu laden',
        'en_un_vistazo'    => '📌 Auf einen Blick',
        'como_llegar'      => '🗺️ Anfahrt (Google Maps)',
        'te_gusta'         => 'Gefällt Ihnen dieser Ort?',
        'cta_desc'         => 'Speichern Sie ihn in Ihren Favoriten und erhalten Sie Benachrichtigungen über nahegelegene Events und Aktivitäten',
        'registrarme'      => '✨ Kostenlos registrieren',
        'ya_cuenta'        => 'Ich habe bereits ein Konto',
        'compartir'        => 'Diesen Ort teilen',
        'dormir_cerca'     => '🏠 Wo kann man in der Nähe übernachten?',
        'dormir_desc'      => 'Ländliche Unterkünfte nur wenige Kilometer entfernt',
        'activ_cercanas'   => '🎯 Touristische Aktivitäten in der Nähe',
        'eventos_cercanos' => '🎭 Bevorstehende Kulturveranstaltungen',
        'lugares_cercanos' => '🏛️ Weitere Sehenswürdigkeiten in der Nähe',
        'ver_mas_aloj'     => 'Mehr Unterkünfte anzeigen',
        'ver_mas_activ'    => 'Mehr Aktivitäten anzeigen',
        'ver_mas_eventos'  => 'Mehr Veranstaltungen anzeigen',
        'ver_mas_lugares'  => 'Mehr Orte anzeigen',
        'leer_mas'         => '↓ Mehr lesen',
        'leer_menos'       => '↑ Weniger lesen',
        'no_encontrado_h1' => 'Ort nicht gefunden',
        'no_encontrado_p'  => 'Der gesuchte Ort existiert nicht oder ist nicht mehr verfügbar.',
        'volver_lista'     => '← Zurück zu den Sehenswürdigkeiten',
        'km'               => 'km',
        'gratis'           => 'Kostenlos',
        'aviso_legal'      => 'Impressum',
        'cookies'          => 'Cookies',
        'agradecimientos'  => 'Danksagungen',
    ],
    'zh' => [
        'lugares'          => '旅游景点',
        'inicio'           => '首页',
        'descripcion'      => '📋 描述',
        'info_practica'    => 'ℹ️ 实用信息',
        'contacto'         => '📞 联系与交通',
        'horario'          => '开放时间',
        'entrada'          => '门票',
        'entrada_gratuita' => '✅ 免费入场',
        'duracion_visita'  => '参观时长',
        'mejor_epoca'      => '最佳季节',
        'accesibilidad'    => '无障碍设施',
        'mascotas'         => '宠物',
        'admite_mascotas'  => '允许携带',
        'ninos'            => '家庭',
        'apto_ninos'       => '适合儿童',
        'instalaciones'    => '设施',
        'fotos'            => '📸 照片库',
        'ver_todas'        => '🔍 查看全部',
        'llamar'           => '📞 致电',
        'whatsapp'         => '💬 WhatsApp',
        'email'            => '✉️ 电子邮件',
        'web_oficial'      => '🌐 官方网站',
        'ver_mapa'         => '在地图上查看',
        'click_mapa'       => '点击加载互动地图',
        'en_un_vistazo'    => '📌 一目了然',
        'como_llegar'      => '🗺️ 如何到达（谷歌地图）',
        'te_gusta'         => '喜欢这个地方吗？',
        'cta_desc'         => '将其收藏，获取附近活动和事件的提醒',
        'registrarme'      => '✨ 免费注册',
        'ya_cuenta'        => '我已有账户',
        'compartir'        => '分享此地',
        'dormir_cerca'     => '🏠 附近哪里可以住宿？',
        'dormir_desc'      => '距离仅几公里的乡村住宿',
        'activ_cercanas'   => '🎯 附近旅游活动',
        'eventos_cercanos' => '🎭 即将举行的文化活动',
        'lugares_cercanos' => '🏛️ 附近其他景点',
        'ver_mas_aloj'     => '查看更多住宿',
        'ver_mas_activ'    => '查看更多活动',
        'ver_mas_eventos'  => '查看更多活动',
        'ver_mas_lugares'  => '查看更多景点',
        'leer_mas'         => '↓ 阅读更多',
        'leer_menos'       => '↑ 收起',
        'no_encontrado_h1' => '未找到景点',
        'no_encontrado_p'  => '您查找的景点不存在或已不再可用。',
        'volver_lista'     => '← 返回景点列表',
        'km'               => '公里',
        'gratis'           => '免费',
        'aviso_legal'      => '法律声明',
        'cookies'          => 'Cookies',
        'agradecimientos'  => '致谢',
    ],
];

// Exportar traducciones del idioma activo
// Merge con el array 'es' como base para garantizar que TODAS las claves existen
// aunque el array del idioma no esté completo
$t = array_merge($ui['es'], $ui[$lang] ?? []);

// ─── LOCALE OG según idioma ───────────────────────────────────────────────────
$og_locale_map = [
    'es' => 'es_ES', 'en' => 'en_GB',
    'fr' => 'fr_FR', 'de' => 'de_DE', 'zh' => 'zh_CN',
];
$og_locale = $og_locale_map[$lang] ?? 'es_ES';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- ── SEO ── -->
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <link rel="canonical" href="<?php echo htmlspecialchars(rtrim($canonical, '/'), ENT_QUOTES, 'UTF-8'); ?>">
    
    <!-- ── hreflang — todos los idiomas (contenido editorial, sin restricción de membresía) ── -->
    <link rel="alternate" hreflang="es"        href="https://rutasrurales.io/lugar/<?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="alternate" hreflang="en"        href="https://rutasrurales.io/en/lugar/<?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="alternate" hreflang="fr"        href="https://rutasrurales.io/fr/lugar/<?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="alternate" hreflang="de"        href="https://rutasrurales.io/de/lugar/<?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="alternate" hreflang="zh"        href="https://rutasrurales.io/zh/lugar/<?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="alternate" hreflang="x-default" href="https://rutasrurales.io/lugar/<?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>">

    <!-- ── Open Graph ── -->
    <meta property="og:type"         content="place">
    <meta property="og:title"        content="<?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:description"  content="<?php echo htmlspecialchars($page_description, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image"        content="<?php echo htmlspecialchars($foto_og, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image:width"  content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt"    content="<?php echo htmlspecialchars($lugar['name'] ?? 'Lugar de interés en Rutas Rurales', ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:url"          content="<?php echo htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:site_name"    content="Rutas Rurales">
    <meta property="og:locale"       content="<?php echo $og_locale; ?>">
    <?php if (!empty($lugar['latitude'])): ?>
    <meta property="place:location:latitude"  content="<?php echo htmlspecialchars($lugar['latitude'], ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="place:location:longitude" content="<?php echo htmlspecialchars($lugar['longitude'], ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>

    <!-- ── Twitter / X Card ── -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:site"        content="@rutasrurales">
    <meta name="twitter:title"       content="<?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($page_description, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:image"       content="<?php echo htmlspecialchars($foto_og, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:image:alt"   content="<?php echo htmlspecialchars($lugar['name'] ?? 'Lugar en Rutas Rurales', ENT_QUOTES, 'UTF-8'); ?>">

    <!-- ── Favicon ── -->
    <link rel="icon" href="/menu_images/Favicon.png" type="image/png">

    <!-- ── Estilos globales del proyecto (incluye nav, header, footer, etc.) ── -->
    <link rel="stylesheet" href="/styles.css">

    <!-- ── Estilos del navbar (header.php los omite con HEADER_NO_HTML_HEAD) ── -->
    <style>
        /* RESET GLOBAL navMenu */
        #navMenu a, #navMenu a:visited, #navMenu a:active {
            text-decoration: none !important;
            color: inherit !important;
            -webkit-tap-highlight-color: transparent;
        }

        /* DESKTOP */
        @media (min-width: 993px) {
            .hamburger { display: none !important; }
            .nav-menu {
                display: flex !important;
                flex-direction: column;
                gap: 10px;
                align-items: center;
                font-family: 'Montserrat', sans-serif;
                margin-left: auto;
            }
            .nav-row {
                display: flex !important;
                list-style: none !important;
                margin: 0; padding: 0;
                width: 650px;
                justify-content: center;
            }
            .nav-row li { flex: 1; text-align: center; }
            .nav-row li a {
                font-size: 0.9rem;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                text-transform: capitalize;
                color: var(--accent-color, #81C784) !important;
                font-weight: 600;
            }
            .nav-row li a span { color: var(--accent-color, #81C784) !important; }
            .nav-row li a i  { color: var(--accent-color, #81C784) !important; font-size: 1.1rem; }
            .nav-row li a:hover,
            .nav-row li a:hover span,
            .nav-row li a:hover i { color: #ffffff !important; }
            .logo-apoyar-wrap { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
            .apoyar-mobile-row { display: none !important; }
        }

        /* MÓVIL */
        @media (max-width: 992px) {
            .header, .navbar {
                height: auto !important;
                padding: 2px 0 !important;
                position: fixed !important;
                top: 0 !important;
                width: 100% !important;
                z-index: 9999 !important;
                background-color: #2F5233 !important;
            }
            .navbar .container {
                flex-direction: row !important;
                justify-content: flex-start !important;
                align-items: center !important;
                gap: 5px !important;
                padding: 0 5px !important;
                display: flex !important;
                width: 100% !important;
            }
            .logo { flex-shrink: 0 !important; margin-right: 2px !important; display: block !important; }
            .logo img { height: 35px !important; width: auto !important; }
            .logo-text { display: none !important; }
            .nav-menu {
                display: flex !important;
                position: static !important;
                width: auto !important;
                height: auto !important;
                background: transparent !important;
                flex-direction: column !important;
                flex: 1 !important;
                gap: 1px !important;
                padding: 0 !important;
                margin: 0 !important;
                box-shadow: none !important;
            }
            .nav-row {
                display: grid !important;
                grid-template-columns: repeat(4, 1fr) !important;
                gap: 2px !important;
                width: 100% !important;
                list-style: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .nav-row li { display: block !important; }
            .nav-row li a {
                background: rgba(255,255,255,0.1) !important;
                min-height: 30px !important;
                padding: 1px !important;
                border-radius: 4px !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center;
            }
            .nav-row li a span {
                font-size: 0.50rem !important;
                font-weight: 600 !important;
                line-height: 1 !important;
                text-align: center !important;
                white-space: nowrap !important;
                color: #d4a574 !important;
                margin: 0 !important;
            }
            .nav-row li a i { font-size: 0.8rem !important; margin-bottom: 0 !important; color: #ffffff !important; }
            .logo-apoyar-wrap { display: flex; align-items: center; gap: 0; flex-shrink: 0 !important; }
            .btn-apoyar-desktop { display: none !important; }
            .apoyar-mobile-row {
                display: grid !important;
                grid-template-columns: 1fr !important;
                width: 100% !important;
                margin: 0 !important; padding: 0 !important;
                list-style: none !important; gap: 0 !important;
            }
            .apoyar-mobile-row li a {
                background: rgba(255,143,0,0.15) !important;
                border: 1px solid rgba(255,143,0,0.35) !important;
                min-height: 22px !important;
                padding: 2px 6px !important;
                border-radius: 4px !important;
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 4px !important;
                color: #ffd080 !important;
                font-size: 0.55rem !important;
                font-weight: 700 !important;
                white-space: nowrap !important;
                text-decoration: none !important;
            }
            .apoyar-mobile-row li a i { font-size: 0.65rem !important; color: #ffd080 !important; margin-bottom: 0 !important; }
            .asistente-avatar { width: 18px !important; height: 18px !important; margin-bottom: 0; }
            input[type="text"], input[type="number"], input[type="search"], textarea, select { font-size: 16px !important; }
        }

        /* Avatar asistente (desktop) */
        .asistente-avatar {
            width: 26px; height: 26px;
            border-radius: 50%; object-fit: cover;
            border: 1.5px solid #ffffff; vertical-align: middle;
        }

        /* Botón Apoyar */
        .btn-apoyar {
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(255,143,0,0.18);
            border: 1.5px solid rgba(255,143,0,0.55);
            color: #ffd080 !important;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.78rem; font-weight: 700;
            padding: 5px 11px; border-radius: 20px;
            text-decoration: none !important; white-space: nowrap;
            transition: background 0.2s, border-color 0.2s, color 0.2s; line-height: 1;
        }
        .btn-apoyar:hover {
            background: rgba(255,143,0,0.38) !important;
            border-color: #ff8f00 !important; color: #ffffff !important;
        }

        /* Ajuste hero para compensar la altura del navbar en móvil */
        @media (max-width: 992px) {
            .lug-hero { margin-top: 90px; }
        }
    </style>

    <!-- ── Preconnect ── -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://unpkg.com" crossorigin>

    <!-- ── Preload imagen hero (LCP) ── -->
    <?php if (!empty($fotos[0])): ?>
    <?php $foto_hero = preg_match('/^https?:\/\//', $fotos[0]) ? $fotos[0] : '/' . ltrim($fotos[0], '/'); ?>
    <link rel="preload" as="image" href="<?php echo htmlspecialchars($foto_hero, ENT_QUOTES, 'UTF-8'); ?>" fetchpriority="high">
    <?php endif; ?>

    <!-- ── CSS COMPLETO INLINE (mismo patrón que alojamiento-modular: no depende de archivo externo) ── -->
    <style>
        @font-face { font-family:'Montserrat'; font-style:normal; font-weight:400; font-display:swap; src:local('Montserrat Regular'),url('/fonts/montserrat-v31-latin-regular.woff2') format('woff2'); }
        @font-face { font-family:'Montserrat'; font-style:normal; font-weight:600; font-display:swap; src:local('Montserrat SemiBold'),url('/fonts/montserrat-v31-latin-600.woff2') format('woff2'); }
        @font-face { font-family:'Montserrat'; font-style:normal; font-weight:800; font-display:swap; src:local('Montserrat ExtraBold'),url('/fonts/montserrat-v31-latin-800.woff2') format('woff2'); }

        :root {
            --lug-primary:#2F5233; --lug-primary-l:#3d6b42; --lug-primary-d:#1a3d1e;
            --lug-accent:#81C784; --lug-warm:#F9A825;
            --lug-text:#333; --lug-text-l:#666; --lug-bg:#f5f7f5; --lug-white:#fff;
            --lug-r:12px; --lug-shadow:0 4px 20px rgba(0,0,0,0.08); --lug-shadow-h:0 8px 30px rgba(0,0,0,0.15);
        }
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        html{scroll-behavior:smooth;}
        body{font-family:'Montserrat','Segoe UI',sans-serif;color:var(--lug-text);background:var(--lug-bg);line-height:1.6;overflow-x:hidden;}
        img{max-width:100%;height:auto;display:block;}
        a{color:var(--lug-primary);text-decoration:none;}
        a:hover{color:var(--lug-primary-l);}

        /* ── HERO ── */
        .lug-page{overflow-x:hidden;}
        .lug-hero{position:relative;min-height:440px;display:flex;flex-direction:column;justify-content:flex-end;overflow:hidden;background:var(--lug-primary-d);margin-top:0;}
        .lug-hero-bg-img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center;display:block;}
        .lug-hero-overlay{position:absolute;inset:0;background:linear-gradient(to bottom,rgba(0,0,0,0.08) 0%,rgba(0,0,0,0.72) 100%);pointer-events:none;}
        .lug-hero-content{position:relative;z-index:2;padding:28px 24px 40px;max-width:1100px;margin:0 auto;width:100%;}
        .lug-hero h1{font-size:clamp(1.6rem,4.5vw,2.9rem);font-weight:800;color:#fff;line-height:1.2;margin-bottom:16px;text-shadow:0 2px 8px rgba(0,0,0,0.35);}
        .lug-hero-location{font-size:.95rem;color:rgba(255,255,255,.88);margin-bottom:12px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
        .lug-stars{letter-spacing:2px;}
        .lug-badges{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px;}
        .lug-badge{display:inline-flex;align-items:center;padding:4px 12px;border-radius:20px;font-size:.78rem;font-weight:700;}
        .lug-badge-cat{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:#fff;}
        .lug-badge-free{background:#81C784;color:#1a3d1e;}
        .lug-badge-pet,.lug-badge-kids{background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.25);}
        .lug-entry-badge{background:rgba(0,0,0,.35);color:#fff;padding:6px 16px;border-radius:8px;font-size:.88rem;font-weight:700;display:inline-block;margin-top:8px;border:1px solid rgba(255,255,255,.2);}

        /* ── BREADCRUMB ── */
        .lug-breadcrumb{font-size:.78rem;color:rgba(255,255,255,.75);margin-bottom:14px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
        .lug-breadcrumb a{color:rgba(255,255,255,.75);text-decoration:none;}
        .lug-breadcrumb a:hover{color:#fff;text-decoration:underline;}
        .lug-breadcrumb ol{list-style:none;padding:0;margin:0;display:flex;flex-wrap:wrap;gap:4px;align-items:center;}
        .lug-breadcrumb ol li{display:flex;align-items:center;}
        .lug-breadcrumb ol li:not(:last-child)::after{content:'›';margin:0 4px;color:rgba(255,255,255,.5);}
        .lug-breadcrumb ol li:last-child{color:rgba(255,255,255,.9);}

        /* ── LAYOUT ── */
        .lug-layout{max-width:1100px;margin:-40px auto 60px;padding:0 16px;display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;}
        @media(max-width:900px){.lug-layout{grid-template-columns:1fr;margin-top:-30px;}}

        /* ── CARD ── */
        .lug-card{background:var(--lug-white)!important;border-radius:var(--lug-r)!important;box-shadow:var(--lug-shadow)!important;overflow:hidden;margin-bottom:24px;transform:none!important;}
        .lug-card:hover{transform:none!important;}
        .lug-card-body{padding:28px;}
        .lug-card-title{font-size:1.1rem!important;font-weight:700!important;color:var(--lug-primary)!important;margin-bottom:18px!important;padding-bottom:12px!important;border-bottom:2px solid var(--lug-accent)!important;display:flex!important;align-items:center!important;gap:8px!important;visibility:visible!important;opacity:1!important;}

        /* ── GALERÍA ── */
        .gallery-main{position:relative;border-radius:8px;overflow:hidden;margin-bottom:10px;cursor:pointer;background:#111;}
        .gallery-main-img{width:100%;height:380px;object-fit:cover;display:block;transition:transform .4s ease;}
        .gallery-main:hover .gallery-main-img{transform:scale(1.02);}
        .gallery-counter{position:absolute;bottom:12px;right:14px;background:rgba(0,0,0,.55);color:#fff;font-size:.78rem;font-weight:600;padding:4px 10px;border-radius:12px;}
        .gallery-expand-btn{position:absolute;top:12px;right:14px;background:rgba(0,0,0,.45);color:#fff;border:none;border-radius:8px;padding:7px 12px;font-size:.8rem;cursor:pointer;display:flex;align-items:center;gap:6px;font-family:inherit;}
        .gallery-expand-btn:hover{background:rgba(0,0,0,.7);}
        .gallery-thumbs{display:grid;grid-template-columns:repeat(auto-fill,minmax(80px,1fr));gap:8px;}
        .gallery-thumb{height:68px;border-radius:6px;overflow:hidden;cursor:pointer;border:2px solid transparent;transition:border-color .2s;}
        .gallery-thumb img{width:100%;height:100%;object-fit:cover;}
        .gallery-thumb.active{border-color:var(--lug-primary);}
        .gallery-thumb:hover{border-color:var(--lug-accent);}

        /* ── DESCRIPCIÓN ── */
        .desc-text{line-height:1.85;color:var(--lug-text);font-size:.97rem;}
        .desc-text a,.desc-text a:visited{color:#2F5233;text-decoration:underline;text-decoration-color:rgba(47,82,51,.4);text-underline-offset:2px;font-weight:600;transition:color .15s,text-decoration-color .15s;}
        .desc-text a:hover{color:#1a3a1e;text-decoration-color:#2F5233;}
        .desc-text.collapsed{max-height:130px;overflow:hidden;position:relative;}
        .desc-text.collapsed::after{content:'';position:absolute;bottom:0;left:0;right:0;height:50px;background:linear-gradient(transparent,var(--lug-white));}
        .desc-toggle{background:none;border:1px solid var(--lug-accent);color:var(--lug-primary);padding:7px 18px;border-radius:20px;font-size:.85rem;font-weight:600;cursor:pointer;margin-top:12px;transition:all .2s;font-family:inherit;}
        .desc-toggle:hover{background:var(--lug-primary);color:#fff;border-color:var(--lug-primary);}

        /* ── INFO DL ── */
        .info-dl{display:flex;flex-direction:column;gap:0;}
        .info-dl-row{display:flex;gap:12px;padding:10px 0;border-bottom:1px solid #f0f0f0;align-items:flex-start;}
        .info-dl-row:last-child{border-bottom:none;}
        .info-dl-row dt{font-size:.8rem;font-weight:700;color:var(--lug-text-l);min-width:130px;flex-shrink:0;padding-top:1px;}
        .info-dl-row dd{font-size:.9rem;color:var(--lug-text);line-height:1.4;}

        /* ── CONTACTO ── */
        .contact-btns{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:14px;}
        .contact-btn{display:inline-flex;align-items:center;gap:8px;padding:10px 18px;border-radius:8px;font-weight:700;font-size:.88rem;text-decoration:none;transition:all .2s;border:none;cursor:pointer;font-family:inherit;}
        .contact-btn:hover{transform:translateY(-2px);box-shadow:var(--lug-shadow-h);}
        .contact-phone{background:var(--lug-primary);color:#fff;}
        .contact-whatsapp{background:#25D366;color:#fff;}
        .contact-email{background:var(--lug-warm);color:#1a1a1a;}
        .contact-web{background:var(--lug-accent);color:var(--lug-primary-d);}

        /* ── MAPA ── */
        .map-wrapper{border-radius:var(--lug-r);overflow:hidden;margin-top:16px;}
        .map-placeholder{height:260px;display:flex;flex-direction:column;align-items:center;justify-content:center;background:linear-gradient(135deg,#e8f0e8,#d4e8d4);color:var(--lug-primary);gap:10px;cursor:pointer;border-radius:var(--lug-r);transition:background .2s;}
        .map-placeholder:hover{background:linear-gradient(135deg,#d4e8d4,#c0dcc0);}
        .map-placeholder-text{font-size:.9rem;font-weight:600;}
        #map{height:280px;width:100%;border-radius:var(--lug-r);}

        /* ── NEARBY ── */
        .nearby-subtitle{font-size:.88rem;color:var(--lug-text-l);margin-bottom:14px;}
        .nearby-grid{display:grid!important;grid-template-columns:repeat(auto-fill,minmax(200px,1fr))!important;gap:12px!important;list-style:none!important;padding:0!important;margin:0!important;}
        .nearby-card{border-radius:8px!important;overflow:hidden!important;border:1px solid #eee!important;transition:all .2s!important;background:var(--lug-white)!important;text-decoration:none!important;color:inherit!important;display:block!important;float:none!important;}
        .nearby-card:hover{box-shadow:var(--lug-shadow-h)!important;transform:translateY(-3px)!important;color:inherit!important;}
        .nearby-card-img{height:120px!important;background:#e8f0e8!important;overflow:hidden!important;position:relative!important;display:block!important;}
        .nearby-card-img img{width:100%!important;height:100%!important;object-fit:cover!important;display:block!important;}
        .nearby-card-img-ph{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:2.5rem;background:linear-gradient(135deg,#e8f0e8,#d0e4d0);}
        .nearby-card-dist{position:absolute;bottom:6px;right:8px;background:rgba(0,0,0,.55);color:#fff;font-size:.7rem;font-weight:700;padding:2px 7px;border-radius:10px;}
        .nearby-card-body{padding:10px 12px!important;display:block!important;}
        .nearby-card-name{font-size:.85rem!important;font-weight:700!important;color:var(--lug-text)!important;margin-bottom:4px!important;display:-webkit-box!important;-webkit-line-clamp:2!important;-webkit-box-orient:vertical!important;overflow:hidden!important;}
        .nearby-card-meta{font-size:.75rem!important;color:var(--lug-text-l)!important;margin-bottom:4px!important;display:block!important;}
        .nearby-card-price{font-size:.8rem!important;font-weight:700!important;color:var(--lug-primary)!important;margin-top:4px!important;}
        .nearby-card-free{font-size:.75rem!important;font-weight:700!important;color:#2e7d32!important;background:#e8f5e9!important;padding:2px 8px!important;border-radius:10px!important;display:inline-block!important;margin-top:4px!important;}
        .nearby-ver-mas{display:inline-flex;align-items:center;background:none;border:1px solid var(--lug-primary);color:var(--lug-primary);padding:8px 20px;border-radius:20px;font-size:.85rem;font-weight:600;cursor:pointer;text-decoration:none;transition:all .2s;font-family:inherit;}
        .nearby-ver-mas:hover{background:var(--lug-primary);color:#fff;}

        /* ── SKELETON ── */
        .skeleton{background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);background-size:200% 100%;animation:sk-load 1.5s infinite;border-radius:4px;}
        @keyframes sk-load{0%{background-position:200% 0}100%{background-position:-200% 0}}
        .skeleton-card{border-radius:8px;overflow:hidden;background:#f0f0f0;height:160px;}
        .skeleton-img{width:100%;height:100px;background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);background-size:200% 100%;animation:sk-load 1.5s infinite;}
        .skeleton-body{padding:8px;}
        .skeleton-title{height:14px;border-radius:4px;margin-bottom:6px;width:80%;background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);background-size:200% 100%;animation:sk-load 1.5s infinite;}
        .skeleton-text{height:10px;border-radius:4px;width:60%;background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);background-size:200% 100%;animation:sk-load 1.5s infinite;}

        /* ── SIDEBAR ── */
        .lug-sidebar{position:sticky;top:90px;}
        .info-card{background:var(--lug-white);border-radius:var(--lug-r);box-shadow:0 4px 24px rgba(0,0,0,.11);padding:24px;margin-bottom:16px;border-top:4px solid var(--lug-primary);}
        .info-card-title{font-size:.85rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--lug-text-l);margin-bottom:14px;}
        .info-list{list-style:none;padding:0;margin:0;}
        .info-list li{display:flex;align-items:flex-start;gap:10px;font-size:.85rem;color:var(--lug-text);padding:8px 0;border-bottom:1px solid #f0f0f0;line-height:1.4;}
        .info-list li:last-child{border-bottom:none;}
        .li-icon{font-size:1rem;flex-shrink:0;margin-top:1px;}
        .info-list a{color:var(--lug-primary);text-decoration:underline;text-underline-offset:2px;}
        .info-list a:hover{color:var(--lug-primary-d);}

        /* ── CTA ── */
        .cta-card{background:linear-gradient(135deg,#2F5233,#1a3d1e)!important;color:#fff!important;border-radius:var(--lug-r);padding:22px;margin-bottom:16px;text-align:center;}
        .cta-card h3{font-size:1rem;font-weight:700;color:#fff!important;opacity:1!important;visibility:visible!important;margin-bottom:8px;}
        .cta-card p{font-size:.82rem;color:rgba(255,255,255,.88);margin-bottom:14px;line-height:1.5;}
        .btn-cta-primary{display:flex!important;align-items:center!important;justify-content:center!important;background:#fff!important;color:#2F5233!important;padding:10px 16px!important;border-radius:8px!important;font-weight:700!important;font-size:.85rem!important;text-decoration:none!important;margin-bottom:8px!important;width:100%!important;box-sizing:border-box!important;}
        .btn-cta-primary:hover{background:#f0f0f0!important;color:#2F5233!important;}
        .btn-cta-secondary{display:flex!important;align-items:center!important;justify-content:center!important;background:transparent!important;color:#fff!important;border:2px solid rgba(255,255,255,.6)!important;padding:9px 16px!important;border-radius:8px!important;font-weight:600!important;font-size:.82rem!important;text-decoration:none!important;width:100%!important;box-sizing:border-box!important;}
        .btn-cta-secondary:hover{background:rgba(255,255,255,.1)!important;border-color:#fff!important;color:#fff!important;}

        /* ── COMPARTIR ── */
        .share-card{background:var(--lug-white);border-radius:var(--lug-r);box-shadow:var(--lug-shadow);padding:18px;text-align:center;margin-bottom:16px;}
        .share-label{font-size:.82rem;color:#666;margin-bottom:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;}
        .share-btns{display:flex;justify-content:center;gap:14px;}
        .share-btns button{background:none;border:none;cursor:pointer;font-size:1.6rem;line-height:1;}

        /* ── LIGHTBOX ── */
        .lbox-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.93);z-index:9999;align-items:center;justify-content:center;}
        .lbox-overlay.active{display:flex;}
        .lbox-img{max-width:92vw;max-height:88vh;border-radius:6px;object-fit:contain;}
        .lbox-close{position:absolute;top:18px;right:22px;color:#fff;font-size:2rem;cursor:pointer;background:none;border:none;opacity:.8;}
        .lbox-close:hover{opacity:1;}
        .lbox-nav{position:absolute;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.15);border:none;color:#fff;font-size:1.5rem;padding:12px 16px;cursor:pointer;border-radius:4px;}
        .lbox-prev{left:16px;} .lbox-next{right:16px;}
        .lbox-caption{position:absolute;bottom:20px;color:rgba(255,255,255,.7);font-size:.85rem;}

        /* ── TOAST ── */
        .toast{position:fixed;bottom:24px;right:24px;background:var(--lug-primary);color:#fff;padding:12px 20px;border-radius:8px;font-size:.9rem;font-weight:600;z-index:9998;transform:translateY(100px);opacity:0;transition:all .3s ease;box-shadow:0 4px 20px rgba(0,0,0,.2);}
        .toast.show{transform:translateY(0);opacity:1;}

        /* ── ERROR ── */
        .error-container{text-align:center;padding:80px 20px 60px;max-width:500px;margin:0 auto;}
        .error-icon{font-size:4rem;margin-bottom:20px;}
        .error-container h1{font-size:1.6rem;margin-bottom:12px;color:var(--lug-primary);}
        .error-container p{color:var(--lug-text-l);margin-bottom:24px;}
        .btn-back{display:inline-flex;align-items:center;gap:8px;background:var(--lug-primary);color:#fff;padding:12px 24px;border-radius:8px;font-weight:700;text-decoration:none;}

        /* ── RESPONSIVE ── */
        @media(max-width:600px){
            .lug-hero{min-height:320px;}
            .lug-hero h1{font-size:1.5rem;}
            .lug-card-body{padding:18px;}
            .gallery-main-img{height:240px;}
            .nearby-grid{grid-template-columns:1fr 1fr;}
        }
        @media(prefers-reduced-motion:reduce){*,*::before,*::after{animation-duration:.01ms!important;transition-duration:.01ms!important;}}

        /* ── CONTACT ROW (botones en fila, uno al lado del otro) ── */
        .contact-row{
            display:flex;flex-wrap:wrap;gap:10px;margin-bottom:16px;
        }
        .contact-row .contact-btn{ flex:1;min-width:130px;justify-content:center; }
        /* Teléfono: mejor contraste — fondo verde oscuro con texto blanco y mayor peso */
        .contact-phone{
            background:#1b4d22 !important;color:#fff !important;
            font-weight:800 !important;
            border:2px solid #2F5233;
            letter-spacing:0.3px;
        }
        .contact-phone:hover{ background:#143c1a !important; }
        /* WhatsApp verde más saturado */
        .contact-whatsapp{ background:#128C44 !important;color:#fff !important; }
        .contact-whatsapp:hover{ background:#0e6e36 !important; }
        /* Email con mejor contraste (fondo ámbar + texto oscuro) */
        .contact-email{ background:#E65100 !important;color:#fff !important; }
        .contact-email:hover{ background:#bf4000 !important; }
        /* Web oficial — color acento con texto muy oscuro */
        .contact-web{ background:#2e7d32 !important;color:#fff !important; }
        .contact-web:hover{ background:#1b5e20 !important; }

        /* ── CTA TURISTA SIDEBAR ── */
        .lug-cta-turista{
            background:linear-gradient(145deg,#1b4d22 0%,#2F5233 55%,#3a6b3f 100%);
            border-radius:var(--lug-r);padding:22px 20px 18px;
            margin-bottom:16px;color:#fff;
            box-shadow:0 6px 24px rgba(47,82,51,0.35);
        }
        .lug-cta-icon{ font-size:1.8rem;margin-bottom:6px;line-height:1; }
        .lug-cta-titulo{ font-size:1rem;font-weight:700;margin-bottom:6px;line-height:1.3;color:#fff; }
        .lug-cta-sub{ font-size:0.78rem;opacity:0.85;margin-bottom:14px;line-height:1.4; }

        /* Formulario de fechas */
        .lug-cta-form{}
        .lug-cta-fields{
            display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px;
        }
        .lug-cta-field--per{ grid-column:1/-1; }
        .lug-cta-field label{
            display:block;font-size:0.68rem;font-weight:700;
            text-transform:uppercase;letter-spacing:0.3px;
            opacity:0.8;margin-bottom:3px;
        }
        .lug-cta-field input,.lug-cta-field select{
            width:100%;padding:8px 10px;border:none;border-radius:7px;
            font-size:0.9rem;background:#fff;color:#333;
            font-family:inherit;box-sizing:border-box;
        }
        .lug-cta-field input:focus,.lug-cta-field select:focus{
            outline:none;box-shadow:0 0 0 3px rgba(249,168,37,0.5);
        }
        .lug-cta-btn-main{
            width:100%;padding:11px 16px;background:#F9A825;color:#1a2e1a;
            border:none;border-radius:8px;font-weight:800;font-size:0.9rem;
            cursor:pointer;font-family:inherit;transition:background 0.2s;
            letter-spacing:0.2px;
        }
        .lug-cta-btn-main:hover{ background:#e69800; }

        /* Separador y botones auth */
        .lug-cta-divider{
            display:flex;align-items:center;gap:8px;margin:12px 0 10px;opacity:0.65;
        }
        .lug-cta-divider::before,.lug-cta-divider::after{
            content:'';flex:1;height:1px;background:rgba(255,255,255,0.3);
        }
        .lug-cta-divider span{ font-size:0.7rem;white-space:nowrap;color:#fff; }
        .lug-cta-btns-row{ display:flex;gap:8px; }
        .lug-cta-btn-reg{
            flex:1;text-align:center;background:rgba(255,255,255,0.12);
            border:1.5px solid rgba(255,255,255,0.35);color:#fff;
            padding:9px 8px;border-radius:8px;font-size:0.78rem;
            font-weight:700;text-decoration:none;transition:background 0.2s;
        }
        .lug-cta-btn-reg:hover{ background:rgba(255,255,255,0.22);color:#fff; }
        .lug-cta-btn-login{
            flex:1;text-align:center;color:rgba(255,255,255,0.65);
            padding:9px 8px;border-radius:8px;font-size:0.78rem;
            font-weight:600;text-decoration:none;
            border:1.5px solid rgba(255,255,255,0.2);
            transition:color 0.2s,border-color 0.2s;
        }
        .lug-cta-btn-login:hover{ color:#fff;border-color:rgba(255,255,255,0.5); }

        /* ── BARRA FIJA MÓVIL CTA TURISTA ── */
        /* Siempre oculta en desktop — solo visible en móvil real */
        #lug-mob-bar{ display:none !important; }
        #lug-mob-overlay{ display:none; }

        @media(max-width:768px){
            /* Ocultar CTA sidebar en móvil — va en la barra fija */
            #lug-cta-sidebar{ display:none !important; }

            /* Mostrar barra fija inferior */
            #lug-mob-bar{
                display:flex !important;position:fixed;bottom:0;left:0;right:0;
                z-index:500;background:linear-gradient(90deg,#1b4d22 0%,#2F5233 100%);
                padding:10px 16px;align-items:center;gap:10px;
                box-shadow:0 -3px 16px rgba(0,0,0,0.28);
                padding-bottom:max(10px,env(safe-area-inset-bottom));
            }
            .lmb-text{
                flex:1;color:#F9A825;font-size:0.78rem;line-height:1.2;
            }
            .lmb-text strong{ display:block;font-size:0.83rem;color:#F9A825; }
            .lmb-text span{ font-size:0.72rem;opacity:0.85;color:#fff; }
            .lmb-btn{
                background:#F9A825;color:#1a2e1a;border:none;border-radius:8px;
                padding:10px 16px;font-weight:800;font-size:0.85rem;
                cursor:pointer;white-space:nowrap;font-family:inherit;flex-shrink:0;
            }
            .lmb-close{
                background:none;border:none;color:rgba(255,255,255,0.55);
                font-size:1.2rem;cursor:pointer;padding:4px;line-height:1;flex-shrink:0;
            }

            /* Overlay bottom sheet */
            #lug-mob-overlay{
                display:none;position:fixed;inset:0;z-index:600;
                background:rgba(0,0,0,0.55);align-items:flex-end;
            }
            #lug-mob-overlay.open{ display:flex; }
            .lmo-box{
                width:100%;background:linear-gradient(160deg,#1b4d22 0%,#2F5233 100%);
                border-radius:16px 16px 0 0;
                padding:20px 20px max(20px,env(safe-area-inset-bottom));
                color:#fff;
            }
            .lmo-header{
                display:flex;align-items:center;justify-content:space-between;
                margin-bottom:14px;
            }
            .lmo-title{ font-size:1rem;font-weight:700; }
            .lmo-close{
                background:none;border:none;color:rgba(255,255,255,0.7);
                font-size:1.4rem;cursor:pointer;line-height:1;padding:4px;
            }

            /* Espacio al final del contenido para que la barra no tape nada */
            body{ padding-bottom:72px; }
        }
    </style>

    <!-- ── Schema.org JSON-LD ── -->
    <?php if (!empty($lugar)): ?>
    <?php renderLugarSchema($lugar, $fotos, $canonical, $page_title, $page_description, $lang, $faqs ?? []); ?>
    <?php endif; ?>

    <!-- ── Datos JS globales (evita segunda llamada API) ── -->
    <script>
        window.LUG_DATA = <?php echo $lugar_js; ?>;
        window.LUG_SLUG = <?php echo json_encode($slug); ?>;
        window.LUG_LANG = <?php echo json_encode($lang); ?>;
    </script>

    <!-- ── Google Tag Manager (diferido, no bloquea LCP) ── -->
    <script>
        setTimeout(function() {
            (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
            new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
            j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;
            j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
            })(window,document,'script','dataLayer','GTM-XXXXXXX');
        }, 2000);
    </script>
</head>
