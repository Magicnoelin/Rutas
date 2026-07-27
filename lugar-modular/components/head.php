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
$t = $ui[$lang] ?? $ui['es'];

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
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8'); ?>">

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

    <!-- ── Preconnect ── -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://unpkg.com" crossorigin>

    <!-- ── Preload imagen hero (LCP) ── -->
    <?php if (!empty($fotos[0])): ?>
    <?php $foto_hero = preg_match('/^https?:\/\//', $fotos[0]) ? $fotos[0] : '/' . ltrim($fotos[0], '/'); ?>
    <link rel="preload" as="image" href="<?php echo htmlspecialchars($foto_hero, ENT_QUOTES, 'UTF-8'); ?>" fetchpriority="high">
    <?php endif; ?>

    <!-- ── CSS externo lugar.css — preload (no render-blocking) ── -->
    <link rel="preload" href="/lugar-modular/css/lugar.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="/lugar-modular/css/lugar.css"></noscript>

    <!-- ── Fuentes locales Montserrat (font-face, font-display:swap) ── -->
    <style>
        @font-face {
            font-family: 'Montserrat';
            font-style: normal;
            font-weight: 400;
            font-display: swap;
            src: local('Montserrat Regular'), local('Montserrat-Regular'),
                 url('/fonts/montserrat-v31-latin-regular.woff2') format('woff2');
        }
        @font-face {
            font-family: 'Montserrat';
            font-style: normal;
            font-weight: 600;
            font-display: swap;
            src: local('Montserrat SemiBold'), local('Montserrat-SemiBold'),
                 url('/fonts/montserrat-v31-latin-600.woff2') format('woff2');
        }
        @font-face {
            font-family: 'Montserrat';
            font-style: normal;
            font-weight: 800;
            font-display: swap;
            src: local('Montserrat ExtraBold'), local('Montserrat-ExtraBold'),
                 url('/fonts/montserrat-v31-latin-800.woff2') format('woff2');
        }
        /* ── CSS crítico mínimo inline (above-the-fold, antes del lugar.css) ── */
        :root {
            --lug-primary:   #2F5233;
            --lug-primary-l: #3d6b42;
            --lug-primary-d: #1a3d1e;
            --lug-accent:    #81C784;
            --lug-warm:      #F9A825;
            --lug-text:      #333;
            --lug-text-l:    #666;
            --lug-bg:        #f5f7f5;
            --lug-white:     #fff;
            --lug-r:         12px;
            --lug-shadow:    0 4px 20px rgba(0,0,0,0.08);
            --lug-shadow-h:  0 8px 30px rgba(0,0,0,0.15);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Montserrat', 'Segoe UI', sans-serif;
            color: var(--lug-text);
            background: var(--lug-bg);
            line-height: 1.6;
            overflow-x: hidden;
        }
        img { max-width: 100%; height: auto; display: block; }
        /* Mínimo para el hero (above-the-fold crítico) */
        .lug-page { overflow-x: hidden; }
        .lug-hero {
            position: relative; min-height: 440px;
            display: flex; flex-direction: column; justify-content: flex-end;
            overflow: hidden; background: var(--lug-primary-d); margin-top: 0;
        }
        .lug-hero-bg-img {
            position: absolute; inset: 0;
            width: 100%; height: 100%;
            object-fit: cover; object-position: center; display: block;
        }
        .lug-hero-overlay {
            position: absolute; inset: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.08) 0%, rgba(0,0,0,0.72) 100%);
        }
        .lug-hero-content {
            position: relative; z-index: 2;
            padding: 28px 24px 40px;
            max-width: 1100px; margin: 0 auto; width: 100%;
        }
        .lug-hero h1 {
            font-size: clamp(1.6rem, 4.5vw, 2.9rem);
            font-weight: 800; color: #fff; line-height: 1.2; margin-bottom: 16px;
            text-shadow: 0 2px 8px rgba(0,0,0,0.35);
        }
        .lug-layout {
            max-width: 1100px; margin: -40px auto 60px; padding: 0 16px;
            display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start;
        }
        @media (max-width: 900px) { .lug-layout { grid-template-columns: 1fr; margin-top: -30px; } }
        .lug-card {
            background: var(--lug-white) !important;
            border-radius: var(--lug-r) !important;
            box-shadow: var(--lug-shadow) !important;
            overflow: hidden; margin-bottom: 24px;
        }
        .lug-card-body { padding: 28px; }
    </style>

    <!-- ── Schema.org JSON-LD ── -->
    <?php if (!empty($lugar)): ?>
    <?php renderLugarSchema($lugar, $fotos, $canonical, $page_title, $page_description, $lang); ?>
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
