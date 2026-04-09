<?php
/**
 * Página PHP para Detalle de Evento - Versión Ultra Simple
 * Esta versión funciona en cualquier servidor que soporte PHP
 */

// Obtener slug de la URL
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'es';

// Si no hay slug, intentar obtenerlo de la URL amigable
if (empty($slug)) {
    $request_uri = $_SERVER['REQUEST_URI'];
    if (preg_match('/\/(?:[a-z]{2}\/)?evento\/([^\/\?]+)/', $request_uri, $matches)) {
        $slug = $matches[1];
    }
}

// Valores por defecto
$meta_title = "Detalle del Evento | Rutas Rurales";
$meta_description = "Descubre eventos culturales en Rutas Rurales - Red Unificada de Turistas, Alojamientos y Servicios";
$short_description = "Descubre eventos culturales en Rutas Rurales - Red Unificada de Turistas, Alojamientos y Servicios";
$canonical_url = "https://rutasrurales.io/evento-detalle.html";

// Si hay slug, intentar cargar datos
if (!empty($slug)) {
    // Intentar cargar datos desde la API
    $api_url = "/api/evento-slug.php?slug=" . urlencode($slug);
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'ignore_errors' => true
        ]
    ]);
    
    $api_response = @file_get_contents($api_url, false, $context);
    
    if ($api_response !== false) {
        $data = json_decode($api_response, true);
        
        if ($data['success'] && !empty($data['data'])) {
            $evento = $data['data'];
            
            // Actualizar meta tags con datos reales
            $meta_title = !empty($evento['meta_title']) ? $evento['meta_title'] : $evento['titulo'] . ' en ' . $evento['localidad'];
            $meta_description = !empty($evento['meta_description']) ? $evento['meta_description'] : 
                               (!empty($evento['descripcion_corta']) ? $evento['descripcion_corta'] : $meta_description);
            $short_description = !empty($evento['descripcion_corta']) ? $evento['descripcion_corta'] : $meta_description;
            
            // Canonical URL
            $canonical_url = "https://rutasrurales.io";
            if ($lang !== 'es') {
                $canonical_url .= "/" . $lang;
            }
            $canonical_url .= "/evento/" . $evento['slug'];
            
            // Datos para mostrar
            $evento_titulo = htmlspecialchars($evento['titulo'], ENT_QUOTES, 'UTF-8');
            $evento_localidad = htmlspecialchars($evento['localidad'], ENT_QUOTES, 'UTF-8');
            $evento_provincia = htmlspecialchars($evento['provincia'], ENT_QUOTES, 'UTF-8');
            $evento_fecha = !empty($evento['start_date']) ? date('d/m/Y', strtotime($evento['start_date'])) : '';
            $evento_organizador = !empty($evento['organizador']) ? htmlspecialchars($evento['organizador'], ENT_QUOTES, 'UTF-8') : '';
            $evento_precio = !empty($evento['precio']) ? htmlspecialchars($evento['precio'], ENT_QUOTES, 'UTF-8') : '';
            $evento_descripcion = nl2br(htmlspecialchars($evento['descripcion'], ENT_QUOTES, 'UTF-8'));
            $evento_municipality = !empty($evento['municipality']) ? htmlspecialchars($evento['municipality'], ENT_QUOTES, 'UTF-8') : '';
            
            $tiene_datos = true;
        }
    }
}

// Escapar para HTML
$meta_title_escaped = htmlspecialchars($meta_title, ENT_QUOTES, 'UTF-8');
$meta_description_escaped = htmlspecialchars($meta_description, ENT_QUOTES, 'UTF-8');
$short_description_escaped = htmlspecialchars($short_description, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="description" content="<?php echo $meta_description_escaped; ?>">
    <meta name="short_description" content="<?php echo $short_description_escaped; ?>">
    <title><?php echo $meta_title_escaped; ?></title>
    
    <!-- Google tag (gtag.js) -->
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-MBP57VQM');</script>
    <!-- End Google Tag Manager -->
    
    <link rel="canonical" href="<?php echo $canonical_url; ?>">
    <link rel="icon" href="/menu_images/Favicon.png" type="image/png">
    
    <!-- CSS asíncrono para evitar bloqueo de renderizado -->
    <link rel="preload" href="/styles.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="/styles.css"></noscript>
    
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>
    
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: #2F5233; color: white; padding: 10px 0; }
        .event-detail { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 20px; }
        h1 { color: #2F5233; margin-bottom: 20px; }
        .meta-info { background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .loading { text-align: center; padding: 50px; }
    </style>
</head>
<body>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MBP57VQM" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    
    <div class="header">
        <div class="container">
            <a href="/" style="color: white; text-decoration: none; font-weight: bold;">Rutas Rurales</a>
        </div>
    </div>
    
    <div class="container">
        <?php if (isset($tiene_datos) && $tiene_datos): ?>
            <div class="event-detail">
                <h1><?php echo $evento_titulo; ?></h1>
                
                <div class="meta-info">
                    <p><strong>Ubicación:</strong> <?php 
                        $ubicacion = $evento_localidad;
                        if (!empty($evento_municipality) && $evento_municipality != $evento_localidad) {
                            $ubicacion .= ' (' . $evento_municipality . ')';
                        }
                        $ubicacion .= ', ' . $evento_provincia;
                        echo $ubicacion;
                    ?></p>
                    <?php if (!empty($evento_fecha)): ?>
                        <p><strong>Fecha:</strong> <?php echo $evento_fecha; ?></p>
                    <?php endif; ?>
                    <?php if (!empty($evento_organizador)): ?>
                        <p><strong>Organizador:</strong> <?php echo $evento_organizador; ?></p>
                    <?php endif; ?>
                    <?php if (!empty($evento_precio)): ?>
                        <p><strong>Precio:</strong> <?php echo $evento_precio; ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="description">
                    <h2>Descripción</h2>
                    <p><?php echo $evento_descripcion; ?></p>
                </div>
                
                <div style="margin-top: 30px;">
                    <a href="/eventos-culturales-paginacion.html" style="background: #2F5233; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Volver a Eventos</a>
                </div>
            </div>
        <?php else: ?>
            <div class="event-detail">
                <h1>Evento no encontrado</h1>
                <p>El evento solicitado no está disponible en este momento.</p>
                <div style="margin-top: 30px;">
                    <a href="/eventos-culturales-paginacion.html" style="background: #2F5233; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Volver a Eventos</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>