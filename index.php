<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Reserva el mejor turismo rural en España con Rutas. Encuentra alojamientos con encanto, rutas de senderismo, actividades de aventura y eventos culturales. Tu plataforma para vivir experiencias auténticas en la naturaleza.">

  <!-- X (Twitter) Card meta tags -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:site" content="@rutas_rurales">
  <meta name="twitter:title" content="Turismo Rural en España | Reserva Alojamientos y Experiencias | Rutas">
  <meta name="twitter:description" content="Reserva alojamientos únicos, rutas mágicas y experiencias auténticas en toda España con Rutas, tu plataforma de turismo rural.">
  <meta name="twitter:image" content="https://rutasrurales.io/menu_images/Logo%20transparente.webp">

  <!-- Cache control (temporal para ver cambios) -->
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">

  <title>Turismo Rural en España | Reserva las Mejores Casas Rurales y Experiencias | Rutas</title>
  <link rel="canonical" href="https://rutasrurales.io/">

  <link rel="icon" href="/menu_images/Favicon.png" type="image/png">
  <link rel="stylesheet" href="styles.css?v=20260114">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* Ajuste para que el logo no rompa el diseño en móviles */
    @media (max-width: 768px) {
      .logo-subtitle {
        display: none; /* Ocultamos el subtítulo largo en móviles */
      }
      .logo {
        display: flex;
        align-items: center;
        gap: 8px;
      }
    }
  </style>

  <!-- Google Tag Manager - Cargado solo después de interacción del usuario -->
  <script>
    // Cargar GTM solo después de interacción del usuario o cuando la página esté completamente inactiva
    function loadGTM() {
      if (window.gtmLoaded) return;
      window.gtmLoaded = true;
      
      (function(w,d,s,l,i){
        w[l]=w[l]||[];
        w[l].push({'gtm.start': new Date().getTime(), event:'gtm.js'});
        var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),
        dl=l!='dataLayer'?'&l='+l:'';
        j.async=true;
        j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;
        f.parentNode.insertBefore(j,f);
      })(window,document,'script','dataLayer','GTM-MBP57VQM');
    }
    
    // Cargar después de interacción del usuario
    ['click', 'scroll', 'keydown', 'mousemove', 'touchstart'].forEach(function(event) {
      window.addEventListener(event, function() {
        setTimeout(loadGTM, 3000); // 3 segundos después de interacción
      }, { once: true });
    });
    
    // Cargar después de 10 segundos si no hay interacción
    setTimeout(loadGTM, 10000);
  </script>
  <!-- End Google Tag Manager -->

  <!-- Google Analytics - Cargado solo después de interacción del usuario -->
  <script>
    // Cargar Google Analytics solo después de interacción del usuario
    function loadGoogleAnalytics() {
      if (window.gaLoaded) return;
      window.gaLoaded = true;
      
      var script = document.createElement('script');
      script.async = true;
      script.src = 'https://www.googletagmanager.com/gtag/js?id=G-X990K5GE42';
      document.head.appendChild(script);
      
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-X990K5GE42');
    }
    
    // Cargar después de interacción del usuario (más tarde que GTM)
    ['click', 'scroll', 'keydown', 'mousemove', 'touchstart'].forEach(function(event) {
      window.addEventListener(event, function() {
        setTimeout(loadGoogleAnalytics, 5000); // 5 segundos después de interacción
      }, { once: true });
    });
    
    // Cargar después de 15 segundos si no hay interacción
    setTimeout(loadGoogleAnalytics, 15000);
  </script>
</head>
<body>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MBP57VQM"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    <!-- Header -->
    <header class="header">
        <nav class="navbar">
            <div class="container">
                <div class="logo">
                    <img src="/menu_images/Logo%20transparente.webp" alt="Rutas Logo" width="60" height="60">
                    <div class="logo-text">
                        <span class="logo-title">Rutas</span>
                        <span class="logo-subtitle">Red Unificada de Turistas, Alojamientos y Servicios</span>
                    </div>
                </div>
                
                <!-- Selector de idiomas desktop -->
                <div class="language-selector-nav">
                    <button class="language-btn-nav" id="languageBtnNav" aria-label="Seleccionar idioma">
                        <img src="https://hatscripts.github.io/circle-flags/flags/es.svg" alt="Español" width="24" height="24">
                        <span>ES</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="language-dropdown-nav" id="languageDropdownNav">
                        <a href="index.html">
                            <img src="https://hatscripts.github.io/circle-flags/flags/es.svg" alt="Español" width="24" height="24">
                            Español
                        </a>
                        <a href="en/index.html">
                            <img src="https://hatscripts.github.io/circle-flags/flags/gb.svg" alt="English" width="24" height="24">
                            English
                        </a>
                        <a href="fr/index.html">
                            <img src="https://hatscripts.github.io/circle-flags/flags/fr.svg" alt="Français" width="24" height="24">
                            Français
                        </a>
                        <a href="de/index.html">
                            <img src="https://hatscripts.github.io/circle-flags/flags/de.svg" alt="Deutsch" width="24" height="24">
                            Deutsch
                        </a>
                        <a href="zh/index.html">
                            <img src="https://hatscripts.github.io/circle-flags/flags/cn.svg" alt="中文" width="24" height="24">
                            中文
                        </a>
                    </div>
                </div>
                
                <button class="hamburger" id="hamburger" aria-label="Menú">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <ul class="nav-menu" id="navMenu">
                    <li><a href="#inicio"><i class="fas fa-home"></i> Inicio</a></li>
                    <li><a href="alojamientos-turisticos.html"><i class="fas fa-bed"></i> Alojamientos Turísticos</a></li>
                    <li><a href="lugares-interes-paginacion.html"><i class="fas fa-route"></i> Lugares de interés</a></li>
                    <li><a href="https://rutasrurales.io/actividades-turisticas.html"><i class="fas fa-hiking"></i> Actividades Turísticas</a></li>
                    <li><a href="https://rutasrurales.io/eventos-culturales-paginacion.html"><i class="fas fa-map-marker-alt"></i> Eventos culturales</a></li>
                    <li><a href="https://rutasrurales.io/rutas.php"><i class="fas fa-route"></i> Rutas</a></li>
                    <li><a href="compromiso-social.html"><i class="fas fa-heart"></i> Compromiso Social</a></li>
                    <li><a href="login.html"><i class="fas fa-user"></i> Iniciar Sesión</a></li>
                    <li><a href="#asistente" class="nav-asistente">
                        <img src="/menu_images/antonio.jpg" alt="Antonio - Asistente" class="asistente-avatar">
                        Antonio
                    </a></li>
                    
                    <!-- Selector de idiomas móvil -->
                    <li class="language-selector-mobile">
                        <h4>🌐 Idiomas</h4>
                        <div class="language-options-mobile">
                            <a href="index.html">
                                <img src="https://hatscripts.github.io/circle-flags/flags/es.svg" alt="Español" width="24" height="24">
                                ES
                            </a>
                            <a href="en/index.html">
                                <img src="https://hatscripts.github.io/circle-flags/flags/gb.svg" alt="English" width="24" height="24">
                                EN
                            </a>
                            <a href="fr/index.html">
                                <img src="https://hatscripts.github.io/circle-flags/flags/fr.svg" alt="Français" width="24" height="24">
                                FR
                            </a>
                            <a href="de/index.html">
                                <img src="https://hatscripts.github.io/circle-flags/flags/de.svg" alt="Deutsch" width="24" height="24">
                                DE
                            </a>
                            <a href="zh/index.html">
                                <img src="https://hatscripts.github.io/circle-flags/flags/cn.svg" alt="中文" width="24" height="24">
                                ZH
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>
        <div class="hero" id="inicio">
            <div class="hero-content">
                <h1>Reserva las Mejores Experiencias de Turismo Rural en España</h1>
                <p>Tu plataforma unificada para encontrar alojamientos con encanto, rutas únicas y actividades inolvidables en toda España</p>
                <button class="btn-primary" onclick="abrirAntonio()">
                    <i class="fas fa-robot"></i> Hablar con Antonio
                </button>
            </div>
        </div>
    </header>

    <!-- Sección de Alojamientos -->
    <section id="alojamientos" class="section">
        <div class="container">
            <h2 class="section-title">
                <i class="fas fa-home"></i> Alojamientos Rurales
            </h2>
            <p style="text-align: center; margin-bottom: 2rem;">
                <a href="alojamientos-turisticos.html" class="btn-primary">
                    <i class="fas fa-search"></i> Ver Todos los Alojamientos
                </a>
            </p>
            <div class="grid">
                <div class="card">
                    <img src="https://drive.google.com/thumbnail?id=1lCFCuPzjFknI25AJG5yMhDvs9l6c2FS6&sz=w400" alt="Casa Enrique" onerror="this.src='https://rutasrurales.io/menu_images/Casa_enrique.jpg'">
                    <div class="card-content">
                        <h3><strong>Casa Enrique</strong> 🏔️</h3>
                        <p class="location"><i class="fas fa-map-marker-alt"></i> Santervas de la Sierra, Soria</p>
        <p>Vive la <strong>experiencia rural definitiva</strong> en nuestra casa acogedora y confortable. Situada en plena naturaleza soriana, es el refugio perfecto para familias o grupos de hasta <strong>6 personas</strong> que buscan vistas impresionantes y desconectar del estrés urbano.</p>

        <p>Sabemos que <strong>viajar con niños</strong> requiere comodidad y un entorno estimulante. Nuestra casa rural ofrece un espacio seguro y natural donde los más pequeños pueden jugar en libertad mientras tú disfrutas del silencio y el aire puro de la provincia de Soria.</p>
                        <div class="card-features">
                            <span><i class="fas fa-users"></i> <strong>6 plazas</strong></span>
                            <span><i class="fas fa-mountain"></i> <strong>Vistas espectaculares</strong></span>
                            <span><i class="fas fa-wifi"></i> WiFi</span>
                        </div>
                        <p class="price"><strong>Desde 225€/noche</strong></p>
                        <a href="https://sites.google.com/view/casaruralenrique/inicio" target="_blank" class="btn-secondary">✨ Ver disponibilidad</a>
                    </div>
                </div>
                <div class="card">
                    <img src="https://rutasrurales.io/Alojamientos_Images/4758.Foto1.213750.jpg" alt="La Plaza Vinuesa">
                    <div class="card-content">
                        <h3><strong>La Plaza</strong> 💑</h3>
                        <p class="location"><i class="fas fa-map-marker-alt"></i> Vinuesa, Soria</p>
                        <p>Disfruta de una estancia inolvidable en <strong>Vinuesa, Soria</strong>, uno de los pueblos más pintorescos de España. El <strong>Apartamento La Plaza</strong> es el alojamiento ideal para parejas que buscan combinar romance y naturaleza en un entorno privilegiado.</p>

                        <p>Situado en la plaza principal, este <strong>apartamento céntrico</strong> te permite disfrutar del encanto histórico del pueblo a pie. Es el punto de partida perfecto para explorar la <strong>comarca de Pinares</strong> y desconectar en un ambiente lleno de magia y tranquilidad.</p>
                        <div class="card-features">
                            <span><i class="fas fa-users"></i> <strong>4 plazas</strong></span>
                            <span><i class="fas fa-tree"></i> <strong>Naturaleza pura</strong></span>
                            <span><i class="fas fa-parking"></i> Parking gratis</span>
                        </div>
                        <p class="price"><strong>Consultar oferta especial</strong></p>
                        <a href="tel:629820592" class="btn-secondary">📞 Reservar ahora</a>
                    </div>
                </div>
                <div class="card">
                    <img src="https://rutasrurales.io/menu_images/casa_chaparrete_1.jpg" alt="Casa Chaparrete">
                    <div class="card-content">
                        <h3><strong>Casa Chaparrete</strong> 🔥</h3>
                        <p class="location"><i class="fas fa-map-marker-alt"></i> Deza, Soria</p>
                        <p>¿Buscas el <strong>refugio perfecto</strong> en el corazón de Castilla? <strong>Casa Chaparrete</strong> es una casa rural tradicional ubicada en el encantador pueblo de <strong>Deza, Soria</strong>. Un espacio diseñado para quienes desean desconectar del ruido y reconectar con lo esencial en un entorno auténtico.</p>

                        <p>Nuestra casa destaca por su calidez. Disfruta de una <strong>chimenea auténtica de leña</strong>, el alma de la casa, ideal para las tardes de invierno tras una jornada explorando la provincia de Soria.</p>
                        <div class="card-features">
                            <span><i class="fas fa-users"></i> <strong>6 plazas</strong></span>
                            <span><i class="fas fa-fire"></i> <strong>Chimenea</strong></span>
                            <span><i class="fas fa-hiking"></i> Senderismo</span>
                        </div>
                        <p class="price"><strong>Consultar precio</strong></p>
                        <a href="tel:625594262" class="btn-secondary">📞 Contactar</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sección de Lugares de Interés -->
    <section id="lugares" class="section">
        <div class="container">
            <h2 class="section-title">
                <i class="fas fa-landmark"></i> Lugares de Interés
            </h2>
            <p style="text-align: center; margin-bottom: 2rem;">
                <a href="lugares-interes-paginacion.html" class="btn-primary">
                    <i class="fas fa-search"></i> Ver Todos los Lugares de Interés
                </a>
            </p>
            <div class="grid">
                <div class="card">
                    <img src="https://rutasrurales.io/menu_images/Yacimiento_Numancia.jpg" alt="Numancia">
                    <div class="card-content">
                        <h3>⚔️ <strong>Yacimiento de Numancia</strong></h3>
                        <p class="location"><i class="fas fa-map-marker-alt"></i> Garray | <strong>¡Leyenda viva!</strong></p>
                        <p><strong>Historia que inspira.</strong> La ciudad celtíbera que <strong>resistió heroicamente</strong> al poderoso Imperio Romano durante 20 años. <strong>Imprescindible</strong> para amantes de la historia.</p>
                        <div class="card-features">
                            <span><i class="fas fa-ticket-alt"></i> <strong>5€ entrada</strong></span>
                            <span><i class="fas fa-history"></i> <strong>Historia única</strong></span>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <img src="https://rutasrurales.io/menu_images/San_Juan_de_Duero.jpg" alt="San Juan de Duero">
                    <div class="card-content">
                        <h3>⛪ <strong>Monasterio de San Juan de Duero</strong></h3>
                        <p class="location"><i class="fas fa-map-marker-alt"></i> Soria Capital | <strong>¡Espectacular!</strong></p>
                        <p><strong>Joy del románico.</strong> Descubre el <strong>claustro único en el mundo</strong> con sus arcos entrelazados de inspiración mudéjar. Un lugar <strong>fotogénico y mágico</strong>.</p>
                        <div class="card-features">
                            <span><i class="fas fa-church"></i> <strong>Arte Románico</strong></span>
                            <span><i class="fas fa-camera"></i> <strong>Top foto</strong></span>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <img src="https://rutasrurales.io/menu_images/villa_de_medinaceli.jpg" alt="Medinaceli">
                    <div class="card-content">
                        <h3>👑 <strong>Villa de Medinaceli</strong></h3>
                        <p class="location"><i class="fas fa-map-marker-alt"></i> Medinaceli | <strong>Historia UNESCO</strong></p>
                        <p><strong>Joy de Castilla.</strong> Admira el <strong>único arco romano de tres vanos</strong> de España en este Conjunto Histórico Declarado. Un tesoro que <strong>no puedes perderte</strong>.</p>
                        <div class="card-features">
                            <span><i class="fas fa-crown"></i> <strong>Conjunto Histórico</strong></span>
                            <span><i class="fas fa-archway"></i> <strong>Arco Romano único</strong></span>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <img src="https://rutasrurales.io/menu_images/El_burgo_de_Osma.jpg" alt="Burgo de Osma">
                    <div class="card-content">
                        <h3>🏰 <strong>El Burgo de Osma</strong></h3>
                        <p class="location"><i class="fas fa-map-marker-alt"></i> Burgo de Osma | <strong>¡Maravilloso!</strong></p>
                        <p><strong>Paseo por el tiempo.</strong> Explora la impresionante <strong>catedral gótica</strong> y el casco histórico medieval de esta villa episcopal única. <strong>Encanto asegurdo</strong>.</p>
                        <div class="card-features">
                            <span><i class="fas fa-church"></i> <strong>Catedral Gótica</strong></span>
                            <span><i class="fas fa-walking"></i> <strong>Casco histórico</strong></span>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <img src="https://rutasrurales.io/menu_images/urbion.jpg" alt="Picos de Urbión">
                    <div class="card-content">
                        <h3>🏔️ <strong>Picos de Urbión</strong></h3>
                        <p class="location"><i class="fas fa-map-marker-alt"></i> Sistema Ibérico | <strong>2.228m de emoción</strong></p>
                        <p><strong>Cima de Soria.</strong> La montaña más alta de la provincia esconde <strong>lagunas glaciares de ensueño</strong> y paisajes que quitan el aliento. <strong>Aventura garantizado</strong>.</p>
                        <div class="card-features">
                            <span><i class="fas fa-mountain"></i> <strong>2.228m altitud</strong></span>
                            <span><i class="fas fa-water"></i> <strong>Lagunas glaciares</strong></span>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <img src="https://rutasrurales.io/menu_images/almenar.jpg" alt="Castillo de Almenar">
                    <div class="card-content">
                        <h3>🏰 <strong>Castillo de Almenar</strong></h3>
                        <p class="location"><i class="fas fa-map-marker-alt"></i> Almenar de Soria | <strong>¡Imponente!</strong></p>
                        <p><strong>Fortaleza legendaria.</strong> Admirar el <strong>castillo medieval del siglo XIII</strong> con sus murallas y torreones perfectamente conservados. <strong>Historia viva</strong>.</p>
                        <div class="card-features">
                            <span><i class="fas fa-chess-rook"></i> <strong>Siglo XIII</strong></span>
                            <span><i class="fas fa-shield-alt"></i> <strong>Fortaleza</strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sección de Actividades -->
    <section id="actividades" class="section section-alt">
        <div class="container">
            <h2 class="section-title">
                <i class="fas fa-hiking"></i> Actividades Turísticas
            </h2>
            <p style="text-align: center; margin-bottom: 2rem;">
                <a href="actividades-turisticas.html" class="btn-primary">
                    <i class="fas fa-search"></i> Ver Todas las Actividades
                </a>
            </p>
            <div class="grid">
                <div class="card">
                    <img src="https://images.unsplash.com/photo-1551632811-561732d1e306?w=400&h=300&fit=crop" alt="Senderismo">
                    <div class="card-content">
                        <h3>🏔️ <strong>Senderismo en la Laguna Negra</strong></h3>
                        <p class="location"><i class="fas fa-clock"></i> 3-4 horas | <strong>12 km</strong></p>
                        <p><strong>¡Vive la aventura!</strong> Descubre la mítica Laguna Negra con una <strong>ruta circular impresionante</strong> de alta montaña. Paisajes de ensueño que te quitarán el aliento.</p>
                        <div class="card-features">
                            <span><i class="fas fa-signal"></i> <strong>Dificultad: Media</strong></span>
                            <span><i class="fas fa-mountain"></i> <strong>Naturaleza salvaje</strong></span>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <img src="https://rutasrurales.io/menu_images/canon_rio_lobos.jpg" alt="Cañón del Río Lobos">
                    <div class="card-content">
                        <h3>🌄 <strong>Cañón del Río Lobos</strong></h3>
                        <p class="location"><i class="fas fa-clock"></i> 2-5 horas | <strong>¡Imprescindible!</strong></p>
                        <p><strong>Maravilla natural única.</strong> Explora formaciones rocosas espectaculares y la emblemática ermita de San Bartolomé en este <strong>Parque Natural de cuento</strong>.</p>
                        <div class="card-features">
                            <span><i class="fas fa-signal"></i> <strong>Dificultad: Baja</strong></span>
                            <span><i class="fas fa-binoculars"></i> <strong>Observación fauna</strong></span>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <img src="https://rutasrurales.io/menu_images/ruta_micolica.jpg" alt="Micología">
                    <div class="card-content">
                        <h3>🍄 <strong>Ruta Micológica</strong></h3>
                        <p class="location"><i class="fas fa-clock"></i> <strong>Otoño (Sep-Nov)</strong></p>
                        <p><strong>¡Busca tesoros del bosque!</strong> Descubre los mejores spot de setas de la mano de <strong>expertos micólogos</strong>. Aventura, naturaleza y gastronomía aseguradas.</p>
                        <div class="card-features">
                            <span><i class="fas fa-user-tie"></i> <strong>Con Guía Experto</strong></span>
                            <span><i class="fas fa-leaf"></i> <strong>100% Naturaleza</strong></span>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <img src="https://rutasrurales.io/menu_images/observación_astronomica.png" alt="Astronomía">
                    <div class="card-content">
                        <h3>✨ <strong>Observación Astronómica</strong></h3>
                        <p class="location"><i class="fas fa-clock"></i> <strong>Experiencia nocturna única</strong></p>
                        <p><strong>Contempla el universo.</strong> Soria ostenta el prestigioso certificado <strong>Starlight</strong> por sus cielos limpios. Vive una experiencia cósmica unforgettable.</p>
                        <div class="card-features">
                            <span><i class="fas fa-moon"></i> <strong>Cielo Starlight</strong></span>
                            <span><i class="fas fa-telescope"></i> <strong>Telescopios profesionales</strong></span>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <img src="https://rutasrurales.io/menu_images/ciclismo.jpg" alt="Ciclismo">
                    <div class="card-content">
                        <h3>🚴 <strong>Ciclismo de Montaña</strong></h3>
                        <p class="location"><i class="fas fa-clock"></i> 2-4 horas | <strong>Adrenalina pura</strong></p>
                        <p><strong>Desafía los picos de Urbión.</strong> Rutas de BTT emocionantes por paisajes únicos. La mejor dosis de <strong>aventura y naturaleza</strong> para cyclists.</p>
                        <div class="card-features">
                            <span><i class="fas fa-signal"></i> <strong>Dificultad: Media-Alta</strong></span>
                            <span><i class="fas fa-mountain"></i> <strong>Paisajes épicos</strong></span>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <img src="https://rutasrurales.io/menu_images/torreznos.jpg" alt="Gastronomía">
                    <div class="card-content">
                        <h3>🍖 <strong>Gastronomía Soriana</strong></h3>
                        <p class="location"><i class="fas fa-clock"></i> <strong>Todo el año</strong> | <strong>Sabores auténticos</strong></p>
                        <p><strong>Degustación única.</strong> Saborea la auténtica cocina soriana: <strong>caldereta, migas, asados</strong> y prestigiosos vinos DO. Una experiencia para el paladar.</p>
                        <div class="card-features">
                            <span><i class="fas fa-utensils"></i> <strong>Tradición 100%</strong></span>
                            <span><i class="fas fa-wine-glass"></i> <strong>Vinos DO Soria</strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sección de Eventos Culturales -->
    <section id="eventos" class="section">
        <div class="container">
            <h2 class="section-title">
                <i class="fas fa-calendar-alt"></i> Eventos Culturales
            </h2>
            <p style="text-align: center; margin-bottom: 2rem;">
                <a href="eventos-culturales.html" class="btn-primary">
                    <i class="fas fa-search"></i> Ver Todos los Eventos
                </a>
            </p>
            <div class="grid-eventos">
                <div class="card-evento">
                    <div class="evento-fecha">
                        <span class="dia">15</span>
                        <span class="mes">AGO</span>
                    </div>
                    <img src="menu_images/concierto_verano.webp" alt="Concierto">
                    <div class="card-content">
                        <span class="evento-categoria musica"><i class="fas fa-music"></i> Música</span>
                        <h3>Concierto de Verano</h3>
                        <p class="evento-descripcion">Música clásica al aire libre en un entorno incomparable.</p>
                        <a href="eventos-culturales.html" class="btn-evento">Más info <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                <div class="card-evento">
                    <div class="evento-fecha">
                        <span class="dia">22</span>
                        <span class="mes">SEP</span>
                    </div>
                    <img src="https://images.unsplash.com/photo-1508700115892-45ecd05ae2ad?w=400&h=300&fit=crop" alt="Teatro">
                    <div class="card-content">
                        <span class="evento-categoria teatro"><i class="fas fa-theater-masks"></i> Teatro</span>
                        <h3>Festival de Teatro</h3>
                        <p class="evento-descripcion">Obras clásicas y modernas en los teatros de la provincia.</p>
                        <a href="eventos-culturales.html" class="btn-evento">Más info <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                <div class="card-evento">
                    <div class="evento-fecha">
                        <span class="dia">10</span>
                        <span class="mes">OCT</span>
                    </div>
                    <img src="https://images.unsplash.com/photo-1551632811-561732d1e306?w=400&h=300&fit=crop" alt="Gastronomía">
                    <div class="card-content">
                        <span class="evento-categoria gastronomia"><i class="fas fa-utensils"></i> Gastronomía</span>
                        <h3>Jornadas Micológicas</h3>
                        <p class="evento-descripcion">Degustación de setas y hongos de los bosques sorianos.</p>
                        <a href="eventos-culturales.html" class="btn-evento">Más info <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sección del Asistente de IA (Versión Mejorada) -->
    <section id="asistente" class="section section-alt asistente-section">
        <div class="container">
            <h2 class="section-title">
                <i class="fas fa-robot"></i> Antonio - Tu Experto Local
            </h2>
            <p class="asistente-intro">
                Conoce a <strong>Antonio</strong>, nuestro experto local que te ayudará a descubrir lo mejor de nuestro destino 
                usando exclusivamente nuestras bases de datos internas de alojamientos, lugares, actividades y eventos.
            </p>
            
            <div style="max-width: 800px; margin: 0 auto; text-align: center;">
                <div style="background: linear-gradient(135deg, #f7faf7 0%, #e8f5e9 100%); border-radius: 20px; padding: 2rem; margin-bottom: 2rem; border: 2px solid #2c5f2d;">
                    <img src="/antonio.jpg" alt="Antonio - Experto local" style="width: 120px; height: 120px; border-radius: 50%; border: 4px solid #2c5f2d; margin-bottom: 1rem;">
                    <h3 style="color: #2c5f2d; margin-bottom: 0.5rem;">Antonio, el experto local</h3>
                    <p style="color: #666; margin-bottom: 1.5rem;">Servicial, entusiasta y conciso. Te ayuda mientras caminas por la calle 📱</p>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                        <div style="background: white; padding: 1rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            <div style="font-size: 2rem; margin-bottom: 0.5rem;">🏨</div>
                            <strong>Alojamientos</strong>
                            <p style="font-size: 0.9rem; color: #666; margin-top: 0.5rem;">Casas rurales, hoteles, apartamentos</p>
                        </div>
                        <div style="background: white; padding: 1rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            <div style="font-size: 2rem; margin-bottom: 0.5rem;">🏛️</div>
                            <strong>Lugares de interés</strong>
                            <p style="font-size: 0.9rem; color: #666; margin-top: 0.5rem;">Patrimonio, naturaleza, monumentos</p>
                        </div>
                        <div style="background: white; padding: 1rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            <div style="font-size: 2rem; margin-bottom: 0.5rem;">🥾</div>
                            <strong>Actividades</strong>
                            <p style="font-size: 0.9rem; color: #666; margin-top: 0.5rem;">Senderismo, rutas, experiencias</p>
                        </div>
                        <div style="background: white; padding: 1rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            <div style="font-size: 2rem; margin-bottom: 0.5rem;">🎭</div>
                            <strong>Eventos</strong>
                            <p style="font-size: 0.9rem; color: #666; margin-top: 0.5rem;">Festivales, conciertos, exposiciones</p>
                        </div>
                    </div>
                    
                    <button class="btn-primary" onclick="abrirAntonio()" style="background: linear-gradient(135deg, #2c5f2d, #4a8c4b);">
                        <i class="fas fa-comments"></i> Hablar con Antonio
                    </button>
                    
                    <p style="font-size: 0.9rem; color: #666; margin-top: 1rem;">
                        <i class="fas fa-info-circle"></i> Antonio solo usa nuestras 4 bases de datos internas
                    </p>
                </div>
            </div>
        </div>
    </section>

<footer class="footer">
        <div class="container">
            <div class="footer-content-simple">
                <div class="footer-info">
                    <span><i class="fas fa-envelope"></i> olgamarin@rutasrurales.io</span>
                    <span><i class="fas fa-phone"></i> +34 605 249 696</span>
                </div>
            <div class="footer-links">
                <a href="aviso-legal.html">Aviso Legal</a>
                <a href="politica-cookies.html">Política de Cookies</a>
                <a href="agradecimientos.html">Agradecimientos</a>
            </div>
            </div>
            <div class="footer-copyright">
                <p>&copy; 2026 rutasrurales.io. Todos los derechos reservados.</p>
            </div>
        </div> </footer> 
    <script src="script.js?v=20260114"></script>
    <script src="antonio_improved.js"></script>
</body>
</html>
