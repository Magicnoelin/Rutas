<?php
/**
 * EVENTO DETALLE - Fusión SEO + Diseño Moderno
 */
require_once 'api/config.php'; 

// 1. OBTENER EL SLUG Y EL IDIOMA DESDE EL HTACCESS
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'es'; // Por defecto español

// 2. CONSULTA A BASE DE DATOS
$evento = null;
if (!empty($slug)) {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$conn->connect_error) {
            $conn->set_charset("utf8mb4");
            // Buscamos el evento con todos los campos necesarios
            $sql = "SELECT 
                    e.id,
                    e.name AS titulo,
                    e.slug,
                    e.description,
                    e.short_description,
                    e.meta_title,
                    e.meta_description,
                    e.start_date,
                    e.end_date,
                    e.venue_name AS localidad,
                    e.venue_address,
                    e.municipality,
                    e.province,
                    e.latitude,
                    e.longitude,
                    e.is_free,
                    e.ticket_price,
                    e.organizer,
                    e.photo1,
                    e.photo2,
                    e.photo3,
                    e.photo4,
                    e.poster_image,
                    e.category_id,
                    e.is_active,
                    e.status
                FROM cultural_events e 
                WHERE e.slug = ? AND e.is_active = 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $slug);
            $stmt->execute();
            $result = $stmt->get_result();
            $evento = $result->fetch_assoc();
            $stmt->close();
            $conn->close();
        }
    } catch (Exception $e) {
        error_log("Error en detalle: " . $e->getMessage());
    }
}

// 3. LÓGICA SEO MEJORADA
// Si existe el evento, usamos sus metas, si no, unos por defecto.
$page_title = ($evento) ? $evento['meta_title'] : "Evento Cultural | Rutas Rurales";
$page_desc = ($evento) ? $evento['meta_description'] : "Descubre este evento en Rutas Rurales";

// El canonical ahora respeta el idioma según tu .htaccess
$canonical = "https://rutasrurales.io/" . ($lang != 'es' ? $lang . '/' : '') . "evento/" . ($evento ? $evento['slug'] : $slug);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_desc); ?>">
    <link rel="canonical" href="<?php echo $canonical; ?>">
    
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
    
    <!-- Preconnect solo para recursos CRÍTICOS que se usarán inmediatamente -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://www.googletagmanager.com">
    <!-- NOTA: No se usan Google Fonts en esta página (fuentes locales Montserrat) -->
    
    <!-- Preload para recursos críticos (optimización segura) -->
    <link rel="preload" href="/styles.css" as="style">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style">
    
    <!-- Cargar CSS normalmente (forma segura) -->
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS crítico inline mínimo (solo lo esencial) -->
    <style>
        /* Solo estilos críticos absolutamente necesarios */
        :root { --primary: #2F5233; --accent: #81C784; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            line-height: 1.6; 
            color: #333;
            margin: 0;
            padding: 0;
        }
        .hero-event { 
            background: var(--primary); 
            color: white; 
            padding: 60px 20px; 
            text-align: center; 
            margin-top: 80px; /* Espacio para el header */
        }
        .event-container { 
            max-width: 900px; 
            margin: -40px auto 40px; 
            background: white; 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            padding: 40px; 
        }
        .meta-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 20px; 
            margin: 30px 0; 
            border-top: 1px solid #eee; 
            padding-top: 20px; 
        }
        .info-item i { 
            color: var(--primary); 
            margin-right: 10px; 
        }
        /* Estilos para la descripción del evento */
        .event-description {
            margin: 30px 0;
            line-height: 1.8;
            font-size: 1.1rem;
        }
        .event-description p {
            margin-bottom: 1.5rem;
        }
        .event-description h2, 
        .event-description h3, 
        .event-description h4 {
            color: var(--primary);
            margin: 2rem 0 1rem 0;
        }
        .event-description ul, 
        .event-description ol {
            margin-left: 2rem;
            margin-bottom: 1.5rem;
        }
        .event-description li {
            margin-bottom: 0.5rem;
        }
        .event-description strong {
            color: var(--dark-color, #1A2E1A);
        }
        .event-description a {
            color: var(--primary);
            text-decoration: underline;
        }
        .event-description a:hover {
            color: var(--accent);
        }
        /* Solo estilos de galería que necesitan estar inline */
        .gallery-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .gallery-item {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .gallery-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
    </style>
</head>
<body>
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MBP57VQM"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

    <?php 
    // Incluir el header (pasar la variable $lang)
    include 'header.php'; 
    ?>

    <header class="hero-event">
    <h1><?php echo $evento ? htmlspecialchars($evento['titulo']) : 'Evento no encontrado'; ?></h1>
    
    <?php if ($evento && !empty($evento['start_date'])): ?>
        <p><i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($evento['start_date'])); ?></p>
    <?php endif; ?>
</header>

    <main class="event-container">
        <?php if ($evento): ?>
            <section class="event-description">
    <?php 
    if ($evento) {
        // Usamos echo directo porque las descripciones suelen traer HTML de la base de datos
        echo $evento['description']; 
    } else {
        echo "No hay información detallada disponible para este evento.";
    }
    ?>
</section>

            <?php
            // Obtener fotos del evento
            $fotos = [];
            if ($evento) {
                // Revisar campos de fotos: photo1, photo2, photo3, photo4, poster_image
                $campos_fotos = ['photo1', 'photo2', 'photo3', 'photo4', 'poster_image'];
                foreach ($campos_fotos as $campo) {
                    if (!empty($evento[$campo]) && trim($evento[$campo]) !== '') {
                        $foto_url = $evento[$campo];
                        // Si la URL no comienza con http, asumimos que es relativa al sitio
                        if (!preg_match('/^https?:\/\//', $foto_url)) {
                            // Si no comienza con /, agregarlo
                            if (strpos($foto_url, '/') !== 0) {
                                $foto_url = '/' . $foto_url;
                            }
                        }
                        $fotos[] = $foto_url;
                    }
                }
            }
            
            // Mostrar galería si hay fotos
            if (!empty($fotos)): ?>
            <section class="event-gallery" style="margin: 30px 0;">
                <h3 style="color: var(--primary); margin-bottom: 20px;">Galería de Fotos</h3>
                <div class="gallery-container">
                    <?php foreach ($fotos as $index => $foto): ?>
                    <div class="gallery-item">
                        <a href="<?php echo htmlspecialchars($foto); ?>" target="_blank">
                            <img src="<?php echo htmlspecialchars($foto); ?>" 
                                 alt="Foto <?php echo $index + 1; ?> del evento <?php echo htmlspecialchars($evento['titulo'] ?? ''); ?>"
                                 onmouseover="this.style.transform='scale(1.05)'"
                                 onmouseout="this.style.transform='scale(1)'">
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <div class="meta-grid">
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <strong>Ubicación:</strong><br>
                    <?php 
                    $ubicacion = '';
                    if (!empty($evento['localidad'])) {
                        $ubicacion = $evento['localidad'];
                        if (!empty($evento['municipality'])) {
                            $ubicacion .= ', ' . $evento['municipality'];
                        }
                        if (!empty($evento['province'])) {
                            $ubicacion .= ' (' . $evento['province'] . ')';
                        }
                    } elseif (!empty($evento['venue_address'])) {
                        $ubicacion = $evento['venue_address'];
                    }
                    echo htmlspecialchars($ubicacion ?: 'Ubicación no especificada');
                    ?>
                </div>
                <div class="info-item">
                    <i class="fas fa-tag"></i>
                    <strong>Categoría:</strong><br>
                    <?php 
                    // Mapear category_id a nombre de categoría
                    $categorias = [
                        1 => 'Fiestas Populares',
                        2 => 'Fiestas Patronales',
                        3 => 'Fiestas Tradicionales',
                        4 => 'Romerías',
                        5 => 'Carnavales',
                        6 => 'Cultura y Espectáculos',
                        7 => 'Conciertos',
                        8 => 'Teatro',
                        9 => 'Exposiciones',
                        10 => 'Festivales de Música',
                        11 => 'Cine',
                        12 => 'Gastronomía y Ferias',
                        13 => 'Ferias Gastronómicas',
                        14 => 'Jornadas Gastronómicas',
                        15 => 'Mercados Tradicionales',
                        16 => 'Ferias de Productos Locales',
                        17 => 'Deportes',
                        18 => 'Carreras Populares',
                        19 => 'Maratones y Medias',
                        20 => 'Competiciones Ciclistas',
                        21 => 'Eventos Deportivos',
                        22 => 'Religión y Tradición',
                        23 => 'Semana Santa',
                        24 => 'Procesiones',
                        25 => 'Celebraciones Religiosas'
                    ];
                    $categoria_id = $evento['category_id'] ?? 0;
                    echo htmlspecialchars($categorias[$categoria_id] ?? 'Cultura');
                    ?>
                </div>
                <div class="info-item">
                    <i class="fas fa-euro-sign"></i>
                    <strong>Precio:</strong><br>
                    <?php 
                    if ($evento['is_free'] == 1) {
                        echo 'Gratis';
                    } elseif (!empty($evento['ticket_price']) && $evento['ticket_price'] > 0) {
                        echo number_format($evento['ticket_price'], 2) . '€';
                    } else {
                        echo 'Consultar precio';
                    }
                    ?>
                </div>
                <div class="info-item">
                    <i class="fas fa-user-tie"></i>
                    <strong>Organiza:</strong><br>
                    <?php echo htmlspecialchars($evento['organizer'] ?? 'Rutas Rurales'); ?>
                </div>
            </div>

            <div id="map" style="height: 400px; width: 100%; border-radius: 10px; margin-top: 20px; background: #f5f5f5; display: flex; align-items: center; justify-content: center;">
                <div id="map-loading" style="text-align: center;">
                    <p style="color: #666; margin-bottom: 10px;">Cargando mapa...</p>
                    <button id="load-map-btn" style="background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">
                        Cargar Mapa
                    </button>
                </div>
            </div>
            <script>
                // Función para cargar Google Maps solo cuando sea necesario
                function loadGoogleMaps() {
                    const mapElement = document.getElementById('map');
                    const loadingElement = document.getElementById('map-loading');
                    
                    // Mostrar que está cargando
                    loadingElement.innerHTML = '<p style="color: #666;">Cargando mapa...</p>';
                    
                    // Crear script para Google Maps
                    const script = document.createElement('script');
                    script.src = 'https://maps.googleapis.com/maps/api/js?key=AIzaSyBjNdQ1eauGeKUTMLAJ5_TwwRxo91wWsPg&callback=initMap';
                    script.async = true;
                    script.defer = true;
                    
                    // Definir función initMap
                    window.initMap = function() {
                        const pos = { lat: <?php echo $evento['latitude'] ?? 0; ?>, lng: <?php echo $evento['longitude'] ?? 0; ?> };
                        const map = new google.maps.Map(mapElement, {
                            zoom: 15,
                            center: pos,
                        });
                        new google.maps.Marker({ position: pos, map: map });
                        
                        // Ocultar elemento de carga
                        loadingElement.style.display = 'none';
                        mapElement.style.background = 'transparent';
                    };
                    
                    // Agregar script al documento
                    document.head.appendChild(script);
                }
                
                // Cargar mapa cuando el usuario haga clic en el botón
                document.getElementById('load-map-btn').addEventListener('click', loadGoogleMaps);
                
                // También cargar automáticamente cuando el usuario haga scroll cerca del mapa
                window.addEventListener('scroll', function() {
                    const mapElement = document.getElementById('map');
                    const rect = mapElement.getBoundingClientRect();
                    const isNearViewport = rect.top < window.innerHeight + 300;
                    
                    if (isNearViewport && !window.googleMapsLoaded) {
                        window.googleMapsLoaded = true;
                        loadGoogleMaps();
                    }
                }, { once: true });
            </script>

        <?php else: ?>
            <div style="text-align:center; padding: 50px;">
                <h2>Lo sentimos, no encontramos el evento</h2>
                <p>El evento solicitado no existe o ya no está disponible.</p>
                <a href="/" style="color: var(--primary);">Volver al inicio</a>
            </div>
        <?php endif; ?>
    </main>

    <?php 
    // Incluir el footer
    include 'footer.php'; 
    ?>
</body>
</html>
