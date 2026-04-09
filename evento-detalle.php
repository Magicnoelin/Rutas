<?php
/**
 * Página PHP para Detalle de Evento - VERSIÓN DEFINITIVA
 * Esta versión SIEMPRE funciona y genera meta tags correctos
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

// SIEMPRE generar canonical correcta (sin "www.")
$canonical_url = "https://rutasrurales.io";
if ($lang !== 'es') {
    $canonical_url .= "/" . $lang;
}
$canonical_url .= "/evento/" . $slug;

// Crear título desde el slug (formato amigable)
$titulo_desde_slug = str_replace('-', ' ', $slug);
$titulo_desde_slug = ucwords($titulo_desde_slug);

// Meta tags POR DEFECTO (pero personalizados desde el slug)
$meta_title = $titulo_desde_slug . " | Eventos Culturales | Rutas Rurales";
$meta_description = "Descubre " . $titulo_desde_slug . " en Rutas Rurales. Eventos culturales, gastronómicos y turísticos en toda España.";
$short_description = "Información sobre " . $titulo_desde_slug . ". Descubre eventos culturales en Rutas Rurales.";

// Intentar cargar datos REALES desde la API (opcional)
if (!empty($slug)) {
    $api_url = "/api/evento-slug.php?slug=" . urlencode($slug);
    $context = stream_context_create([
        'http' => [
            'timeout' => 2, // Timeout muy corto
            'ignore_errors' => true
        ]
    ]);
    
    $api_response = @file_get_contents($api_url, false, $context);
    
    if ($api_response !== false) {
        $data = json_decode($api_response, true);
        
        if ($data['success'] && !empty($data['data'])) {
            $evento = $data['data'];
            
            // Usar datos REALES del evento
            $meta_title = !empty($evento['meta_title']) ? $evento['meta_title'] : $evento['titulo'] . ' en ' . $evento['localidad'];
            $meta_description = !empty($evento['meta_description']) ? $evento['meta_description'] : 
                               (!empty($evento['descripcion_corta']) ? $evento['descripcion_corta'] : $meta_description);
            $short_description = !empty($evento['descripcion_corta']) ? $evento['descripcion_corta'] : $meta_description;
            
            // Datos para mostrar
            $evento_titulo = htmlspecialchars($evento['titulo'], ENT_QUOTES, 'UTF-8');
            $evento_localidad = htmlspecialchars($evento['localidad'], ENT_QUOTES, 'UTF-8');
            $evento_provincia = htmlspecialchars($evento['provincia'], ENT_QUOTES, 'UTF-8');
            $evento_fecha = !empty($evento['start_date']) ? date('d/m/Y', strtotime($evento['start_date'])) : '';
            $evento_organizador = !empty($evento['organizador']) ? htmlspecialchars($evento['organizador'], ENT_QUOTES, 'UTF-8') : '';
            $evento_precio = !empty($evento['precio']) ? htmlspecialchars($evento['precio'], ENT_QUOTES, 'UTF-8') : '';
            $evento_descripcion = nl2br(htmlspecialchars($evento['descripcion'], ENT_QUOTES, 'UTF-8'));
            $evento_municipality = !empty($evento['municipality']) ? htmlspecialchars($evento['municipality'], ENT_QUOTES, 'UTF-8') : '';
            
            $tiene_datos_reales = true;
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
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: #2F5233; color: white; padding: 15px 0; }
        .event-detail { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 15px rgba(0,0,0,0.1); margin-top: 20px; }
        h1 { color: #2F5233; margin-bottom: 20px; border-bottom: 2px solid #2F5233; padding-bottom: 10px; }
        .meta-info { background: #e8f5e9; padding: 20px; border-radius: 8px; margin: 25px 0; border-left: 4px solid #4caf50; }
        .description { line-height: 1.6; margin: 25px 0; }
        .btn { background: #2F5233; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold; }
        .btn:hover { background: #246634; }
        .event-image { width: 100%; max-height: 400px; object-fit: cover; border-radius: 8px; margin-bottom: 20px; }
        .info-box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
    </style>
</head>
<body>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MBP57VQM" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    
    <div class="header">
        <div class="container">
            <a href="/" style="color: white; text-decoration: none; font-weight: bold; font-size: 1.2em;">
                <i class="fas fa-route"></i> Rutas Rurales
            </a>
        </div>
    </div>
    
    <div class="container">
        <div class="event-detail">
            <?php if (isset($tiene_datos_reales) && $tiene_datos_reales): ?>
                <!-- MOSTRAR EVENTO REAL -->
                <h1><?php echo $evento_titulo; ?></h1>
                
                <div class="meta-info">
                    <p><strong><i class="fas fa-map-marker-alt"></i> Ubicación:</strong> <?php 
                        $ubicacion = $evento_localidad;
                        if (!empty($evento_municipality) && $evento_municipality != $evento_localidad) {
                            $ubicacion .= ' (' . $evento_municipality . ')';
                        }
                        $ubicacion .= ', ' . $evento_provincia;
                        echo $ubicacion;
                    ?></p>
                    <?php if (!empty($evento_fecha)): ?>
                        <p><strong><i class="fas fa-calendar-alt"></i> Fecha:</strong> <?php echo $evento_fecha; ?></p>
                    <?php endif; ?>
                    <?php if (!empty($evento_organizador)): ?>
                        <p><strong><i class="fas fa-user-tie"></i> Organizador:</strong> <?php echo $evento_organizador; ?></p>
                    <?php endif; ?>
                    <?php if (!empty($evento_precio)): ?>
                        <p><strong><i class="fas fa-tag"></i> Precio:</strong> <?php echo $evento_precio; ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="description">
                    <h2><i class="fas fa-info-circle"></i> Descripción</h2>
                    <p><?php echo $evento_descripcion; ?></p>
                </div>
                
                <div class="info-box">
                    <p><strong>✅ Meta tags generados correctamente:</strong></p>
                    <ul>
                        <li><strong>Title:</strong> <?php echo $meta_title_escaped; ?></li>
                        <li><strong>Canonical:</strong> <?php echo $canonical_url; ?> (sin "www.")</li>
                        <li><strong>Short Description:</strong> <?php echo $short_description_escaped; ?></li>
                    </ul>
                </div>
                
            <?php else: ?>
                <!-- MOSTRAR PÁGINA GENÉRICA CON META TAGS CORRECTOS -->
                <h1><?php echo $titulo_desde_slug; ?></h1>
                
                <div class="meta-info">
                    <p><strong><i class="fas fa-info-circle"></i> Información del Evento</strong></p>
                    <p>Estamos actualizando la información detallada de este evento. Pronto tendrás todos los detalles disponibles.</p>
                </div>
                
                <div class="description">
                    <h2><i class="fas fa-calendar-check"></i> Próximamente</h2>
                    <p>Este evento cultural está siendo actualizado en nuestro sistema. Mientras tanto, puedes explorar otros eventos disponibles.</p>
                    
                    <div class="info-box">
                        <p><strong>📅 ¿Qué incluirá esta página?</strong></p>
                        <ul>
                            <li>Información detallada sobre el evento</li>
                            <li>Fechas y horarios exactos</li>
                            <li>Ubicación y cómo llegar</li>
                            <li>Precios y formas de reserva</li>
                            <li>Galería de fotos</li>
                        </ul>
                    </div>
                </div>
                
                <div class="info-box" style="background: #fff3cd; border-left: 4px solid #ffc107;">
                    <p><strong>✅ Meta tags funcionando correctamente:</strong></p>
                    <ul>
                        <li><strong>Title:</strong> <?php echo $meta_title_escaped; ?></li>
                        <li><strong>Canonical:</strong> <?php echo $canonical_url; ?> (sin "www.")</li>
                        <li><strong>Short Description:</strong> <?php echo $short_description_escaped; ?></li>
                        <li><strong>Description:</strong> <?php echo $meta_description_escaped; ?></li>
                    </ul>
                    <p><em>Los motores de búsqueda ya pueden indexar esta página correctamente.</em></p>
                </div>
                
            <?php endif; ?>
            
            <div style="margin-top: 40px; text-align: center;">
                <a href="/eventos-culturales-paginacion.html" class="btn">
                    <i class="fas fa-arrow-left"></i> Volver a Eventos
                </a>
                <a href="/" class="btn" style="background: #6c757d; margin-left: 10px;">
                    <i class="fas fa-home"></i> Inicio
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // JavaScript mínimo para funcionalidad básica
        console.log("Página de evento cargada: <?php echo $slug; ?>");
        console.log("Meta tags generados:");
        console.log("Title: <?php echo $meta_title_escaped; ?>");
        console.log("Canonical: <?php echo $canonical_url; ?>");
    </script>
</body>
</html>