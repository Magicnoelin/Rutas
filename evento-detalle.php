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
            // Buscamos el evento
            $sql = "SELECT e.*, e.name AS titulo FROM cultural_events e WHERE e.slug = ? AND e.is_active = 1";
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
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_desc); ?>">
    <link rel="canonical" href="<?php echo $canonical; ?>">
    
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-MBP57VQM');</script>
    
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Aquí incluiremos el CSS moderno de tu archivo de 800 líneas */
        :root { --primary: #2F5233; --accent: #81C784; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
        .hero-event { background: var(--primary); color: white; padding: 60px 20px; text-align: center; }
        .event-container { max-width: 900px; margin: -40px auto 40px; background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); padding: 40px; }
        .meta-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 30px 0; border-top: 1px solid #eee; padding-top: 20px; }
        .info-item i { color: var(--primary); margin-right: 10px; }
    </style>
</head>
<body>
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MBP57VQM"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

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

            <div class="meta-grid">
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <strong>Ubicación:</strong><br>
                    <?php echo htmlspecialchars($evento['location']); ?>
                </div>
                <div class="info-item">
                    <i class="fas fa-tag"></i>
                    <strong>Categoría:</strong><br>
                    <?php echo htmlspecialchars($evento['category_name'] ?? 'Cultura'); ?>
                </div>
                <div class="info-item">
                    <i class="fas fa-euro-sign"></i>
                    <strong>Precio:</strong><br>
                    <?php echo ($evento['price'] > 0) ? $evento['price'] . '€' : 'Gratis'; ?>
                </div>
                <div class="info-item">
                    <i class="fas fa-user-tie"></i>
                    <strong>Organiza:</strong><br>
                    <?php echo htmlspecialchars($evento['organizer_name'] ?? 'Rutas Rurales'); ?>
                </div>
            </div>

            <div id="map" style="height: 400px; width: 100%; border-radius: 10px; margin-top: 20px;"></div>
            <script>
                function initMap() {
                    const pos = { lat: <?php echo $evento['latitude'] ?? 0; ?>, lng: <?php echo $evento['longitude'] ?? 0; ?> };
                    const map = new google.maps.Map(document.getElementById("map"), {
                        zoom: 15,
                        center: pos,
                    });
                    new google.maps.Marker({ position: pos, map: map });
                }
            </script>
            <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBjNdQ1eauGeKUTMLAJ5_TwwRxo91wWsPg&callback=initMap" async defer></script>

        <?php else: ?>
            <div style="text-align:center; padding: 50px;">
                <h2>Lo sentimos, no encontramos el evento</h2>
                <p>El evento solicitado no existe o ya no está disponible.</p>
                <a href="/" style="color: var(--primary);">Volver al inicio</a>
            </div>
        <?php endif; ?>
    </main>

    <footer style="text-align: center; padding: 20px; font-size: 0.9em; color: #666;">
        &copy; <?php echo date('Y'); ?> Rutas Rurales. Todos los derechos reservados.
    </footer>
</body>
</html>