<?php
// Detectar idioma desde variable $lang o desde el path
$lang = $lang ?? 'es';
$lang_prefix = ($lang != 'es') ? '/' . $lang : '';

// Traducciones del header
$translations = [
    'es' => [
        'title' => 'Rutas Rurales',
        'description' => 'Rutas Rurales - Red Unificada de Turistas, Alojamientos y Servicios',
        'accommodations' => 'Alojamientos',
        'places' => 'Lugares', 
        'events' => 'Eventos',
        'activities' => 'Actividades',
        'home' => 'Inicio',
        'routes' => 'Rutas',
        'login' => 'Acceso',
        'antonio' => 'Antonio',
        'support' => 'Apoyar'
    ],
    'en' => [
        'title' => 'Rural Routes',
        'description' => 'Rural Routes - Unified Network of Tourists, Accommodations and Services',
        'accommodations' => 'Stays',
        'places' => 'Places', 
        'events' => 'Events',
        'activities' => 'Activities',
        'home' => 'Home',
        'routes' => 'Routes',
        'login' => 'Login',
        'antonio' => 'Antonio',
        'support' => 'Support'
    ],
    'fr' => [
        'title' => 'Routes Rurales',
        'description' => 'Routes Rurales - Réseau Unifié de Touristes, Hébergements et Services',
        'accommodations' => 'Hébergements',
        'places' => 'Lieux', 
        'events' => 'Événements',
        'activities' => 'Activités',
        'home' => 'Accueil',
        'routes' => 'Randonnées',
        'login' => 'Connexion',
        'antonio' => 'Antonio',
        'support' => 'Soutenir'
    ],
    'de' => [
        'title' => 'Ländliche Wege',
        'description' => 'Ländliche Wege - Vereinigtes Netzwerk von Touristen, Unterkünften und Dienstleistungen',
        'accommodations' => 'Unterkünfte',
        'places' => 'Orte', 
        'events' => 'Veranstaltungen',
        'activities' => 'Aktivitäten',
        'home' => 'Startseite',
        'routes' => 'Routen',
        'login' => 'Anmeldung',
        'antonio' => 'Antonio',
        'support' => 'Unterstützen'
    ],
    'zh' => [
        'title' => '乡村路线',
        'description' => '乡村路线 - 旅游者、住宿和服务的统一网络',
        'accommodations' => '住宿',
        'places' => '地点', 
        'events' => '活动',
        'activities' => '活动',
        'home' => '首页',
        'routes' => '路线',
        'login' => '登录',
        'antonio' => '安东尼奥',
        'support' => '支持我们'
    ]
];

$t = $translations[$lang] ?? $translations['es'];
$page_description = $page_description ?? $t['description'];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-MBP57VQM');</script>
    <!-- End Google Tag Manager -->
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    
    <meta name="description" content="<?php echo $page_description; ?>" />
    <title><?php echo $page_title ?? $t['title']; ?></title>
    <link rel="canonical" href="<?php echo $page_canonical ?? 'https://www.rutasrurales.io' . $_SERVER['REQUEST_URI']; ?>">
    
    <link rel="icon" href="/menu_images/Favicon.png" type="image/png">
    <link rel="stylesheet" href="/styles.css">
    <?php if (empty($defer_fontawesome)): ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php endif; ?>
    
    <style>
        /* Fuentes locales Montserrat - cargadas desde el servidor */
        @font-face {
            font-family: 'Montserrat';
            font-style: normal;
            font-weight: 400;
            font-display: swap;
            src: local('Montserrat Regular'), local('Montserrat-Regular'), url('/fonts/montserrat-v31-latin-regular.woff2') format('woff2');
        }
        @font-face {
            font-family: 'Montserrat';
            font-style: normal;
            font-weight: 500;
            font-display: swap;
            src: local('Montserrat Medium'), local('Montserrat-Medium'), url('/fonts/montserrat-v31-latin-500.woff2') format('woff2');
        }
        @font-face {
            font-family: 'Montserrat';
            font-style: normal;
            font-weight: 600;
            font-display: swap;
            src: local('Montserrat SemiBold'), local('Montserrat-SemiBold'), url('/fonts/montserrat-v31-latin-600.woff2') format('woff2');
        }

        /* RESET GLOBAL */
        #navMenu a, #navMenu a:visited, #navMenu a:active {
            text-decoration: none !important;
            color: inherit !important;
            -webkit-tap-highlight-color: transparent;
        }

        /* --- DISEÑO PARA PC --- */
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
                color: var(--accent-color) !important;
                font-weight: 600;
            }
            .nav-row li a span {
                color: var(--accent-color) !important;
            }
            .nav-row li a i {
                color: var(--accent-color) !important;
                font-size: 1.1rem;
            }
            .nav-row li a:hover, .nav-row li a:hover span, .nav-row li a:hover i {
                color: #ffffff !important;
            }
        }

        /* --- DISEÑO PARA MÓVIL (ULTRA COMPACTO Y ESTABLE) --- */
        @media (max-width: 992px) {
            html, body {
                overflow-x: hidden !important;
                width: 100% !important;
                position: relative;
            }

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

            .logo {
                flex-shrink: 0 !important;
                margin-right: 2px !important;
                display: block !important;
            }

            .logo img {
                height: 35px !important;
                width: auto !important;
            }

            .logo-text {
                display: none !important;
            }

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

            .nav-row li {
                display: block !important;
            }

            .nav-row li a {
                background: rgba(255, 255, 255, 0.1) !important;
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

            .nav-row li a i {
                font-size: 0.8rem !important;
                margin-bottom: 0px !important;
                color: #ffffff !important;
            }

            /* Evitar zoom automático en inputs */
            input[type="text"], input[type="number"], input[type="search"], textarea, select {
                font-size: 16px !important;
            }
            
            /* Forzar que el mapa no rompa el ancho */
            #map { width: 100% !important; }
        }

        .asistente-avatar {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            object-fit: cover;
            border: 1.5px solid #ffffff;
            vertical-align: middle;
        }

        @media (max-width: 992px) {
            .asistente-avatar {
                width: 18px !important;
                height: 18px !important;
                margin-bottom: 0px;
            }
        }

        /* ── BOTÓN APOYAR ── */
        .btn-apoyar {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(255,143,0,0.18);
            border: 1.5px solid rgba(255,143,0,0.55);
            color: #ffd080 !important;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.78rem;
            font-weight: 700;
            padding: 5px 11px;
            border-radius: 20px;
            text-decoration: none !important;
            white-space: nowrap;
            transition: background 0.2s, border-color 0.2s, color 0.2s;
            line-height: 1;
        }
        .btn-apoyar:hover {
            background: rgba(255,143,0,0.38) !important;
            border-color: #ff8f00 !important;
            color: #ffffff !important;
        }

        /* Desktop: junto al logo */
        @media (min-width: 993px) {
            .logo-apoyar-wrap {
                display: flex;
                align-items: center;
                gap: 10px;
                flex-shrink: 0;
            }
            .apoyar-mobile-row { display: none !important; }
        }

        /* Móvil: ocultar el pill del logo, mostrar fila debajo */
        @media (max-width: 992px) {
            .logo-apoyar-wrap {
                display: flex;
                align-items: center;
                gap: 0;
                flex-shrink: 0 !important;
            }
            .btn-apoyar-desktop { display: none !important; }

            .apoyar-mobile-row {
                display: grid !important;
                grid-template-columns: 1fr !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                list-style: none !important;
                gap: 0 !important;
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
            .apoyar-mobile-row li a i {
                font-size: 0.65rem !important;
                color: #ffd080 !important;
                margin-bottom: 0 !important;
            }
        }
    </style>
</head>
<body>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MBP57VQM"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    <header class="header">
        <nav class="navbar">
            <div class="container">
                <!-- Logo + botón Apoyar (desktop: en línea; móvil: solo logo) -->
                <div class="logo-apoyar-wrap">
                    <div class="logo">
                        <a href="/index.html"><img src="/menu_images/Logo%20transparente.webp" alt="Rutas Logo"></a>
                    </div>
                    <a href="<?php echo $lang_prefix; ?>/apoyar.php" class="btn-apoyar btn-apoyar-desktop" title="<?php echo $t['support']; ?>">
                        ☕ <?php echo $t['support']; ?>
                    </a>
                </div>

                <div class="nav-menu" id="navMenu">
                    <ul class="nav-row">
                        <li><a href="<?php echo $lang_prefix; ?>/alojamientos-turisticos.html">
                            <i class="fas fa-bed"></i>
                            <span><?php echo $t['accommodations']; ?></span>
                        </a></li>
                        <li><a href="<?php echo $lang_prefix; ?>/lugares-interes-paginacion.html">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo $t['places']; ?></span>
                        </a></li>
                        <li><a href="<?php echo $lang_prefix; ?>/eventos-culturales-paginacion.html">
                            <i class="fas fa-calendar-alt"></i>
                            <span><?php echo $t['events']; ?></span>
                        </a></li>
                        <li><a href="<?php echo $lang_prefix; ?>/actividades-turisticas.html">
                            <i class="fas fa-hiking"></i>
                            <span><?php echo $t['activities']; ?></span>
                        </a></li>
                    </ul>
                    
                    <ul class="nav-row">
                        <li><a href="<?php echo $lang_prefix; ?>/index.html#inicio">
                            <i class="fas fa-home"></i>
                            <span><?php echo $t['home']; ?></span>
                        </a></li>
                        <li><a href="<?php echo $lang_prefix; ?>/rutas.php">
                            <i class="fas fa-route"></i>
                            <span><?php echo $t['routes']; ?></span>
                        </a></li>
                        <li><a href="<?php echo $lang_prefix; ?>/login.html">
                            <i class="fas fa-user-circle"></i>
                            <span><?php echo $t['login']; ?></span>
                        </a></li>
                        <li><a href="<?php echo $lang_prefix; ?>/index.html#asistente">
                            <img src="/antonio.jpg" alt="Antonio" class="asistente-avatar">
                            <span><?php echo $t['antonio']; ?></span>
                        </a></li>
                    </ul>

                    <!-- Fila Apoyar solo en móvil -->
                    <ul class="apoyar-mobile-row">
                        <li><a href="<?php echo $lang_prefix; ?>/apoyar.php">
                            <i class="fas fa-heart"></i>
                            <?php echo $t['support']; ?>
                        </a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
