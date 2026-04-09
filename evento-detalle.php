<?php
/**
 * Página PHP para Detalle de Evento - Versión Simplificada
 * Genera meta tags en el servidor para SEO
 * URL: /evento/{slug} -> redirige internamente a este archivo
 */

// Obtener slug de la URL
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'es';

// Si no hay slug, intentar obtenerlo de la URL amigable
if (empty($slug)) {
    $request_uri = $_SERVER['REQUEST_URI'];
    // Extraer slug de /evento/slug o /{lang}/evento/slug
    if (preg_match('/\/(?:[a-z]{2}\/)?evento\/([^\/\?]+)/', $request_uri, $matches)) {
        $slug = $matches[1];
    }
}

// Si todavía no hay slug, mostrar error
if (empty($slug)) {
    header("HTTP/1.0 404 Not Found");
    echo "<h1>404 - Evento no encontrado</h1>";
    echo "<p>No se especificó un slug de evento.</p>";
    exit;
}

// Conectar a la base de datos
require_once 'api/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8mb4");
    
    // Consulta simplificada para obtener el evento
    $sql = "SELECT 
            e.id,
            e.name AS titulo,
            e.slug,
            e.description AS descripcion,
            e.short_description AS descripcion_corta,
            e.meta_title,
            e.meta_description,
            e.start_date,
            e.localidad,
            e.provincia,
            e.organizador,
            e.precio
        FROM cultural_events e
        WHERE e.slug = :slug AND e.estado = 'activo'
        LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':slug' => $slug]);
    $evento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$evento) {
        header("HTTP/1.0 404 Not Found");
        echo "<h1>404 - Evento no encontrado</h1>";
        echo "<p>El evento con slug '$slug' no existe o no está activo.</p>";
        exit;
    }
    
    // Determinar canonical URL
    $canonical_url = "https://rutasrurales.io";
    if ($lang !== 'es') {
        $canonical_url .= "/" . $lang;
    }
    $canonical_url .= "/evento/" . $evento['slug'];
    
    // Determinar meta tags
    $meta_title = !empty($evento['meta_title']) ? $evento['meta_title'] : $evento['titulo'] . ' en ' . $evento['localidad'];
    $meta_description = !empty($evento['meta_description']) ? $evento['meta_description'] : 
                       (!empty($evento['descripcion_corta']) ? $evento['descripcion_corta'] : 
                       'Descubre eventos culturales en Rutas Rurales - Red Unificada de Turistas, Alojamientos y Servicios');
    $short_description = !empty($evento['descripcion_corta']) ? $evento['descripcion_corta'] : $meta_description;
    
} catch (Exception $e) {
    // Error de base de datos - mostrar información de depuración
    error_log("Error en evento-detalle.php: " . $e->getMessage());
    header("HTTP/1.0 500 Internal Server Error");
    echo "<h1>500 - Error interno del servidor</h1>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Archivo: " . htmlspecialchars(__FILE__) . "</p>";
    echo "<p>Línea: " . htmlspecialchars($e->getLine()) . "</p>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="description" content="<?php echo htmlspecialchars($meta_description, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="short_description" content="<?php echo htmlspecialchars($short_description, ENT_QUOTES, 'UTF-8'); ?>">
    <title><?php echo htmlspecialchars($meta_title, ENT_QUOTES, 'UTF-8'); ?></title>
    
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
        .debug-info { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107; }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <a href="/" style="color: white; text-decoration: none; font-weight: bold;">Rutas Rurales</a>
        </div>
    </div>
    
    <div class="container">
        <div class="event-detail">
            <h1><?php echo htmlspecialchars($evento['titulo'], ENT_QUOTES, 'UTF-8'); ?></h1>
            
            <div class="meta-info">
                <p><strong>Ubicación:</strong> <?php echo htmlspecialchars($evento['localidad'] . ', ' . $evento['provincia'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($evento['start_date'])); ?></p>
                <?php if (!empty($evento['organizador'])): ?>
                    <p><strong>Organizador:</strong> <?php echo htmlspecialchars($evento['organizador'], ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
                <?php if (!empty($evento['precio'])): ?>
                    <p><strong>Precio:</strong> <?php echo htmlspecialchars($evento['precio'], ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="description">
                <h2>Descripción</h2>
                <p><?php echo nl2br(htmlspecialchars($evento['descripcion'], ENT_QUOTES, 'UTF-8')); ?></p>
            </div>
            
            <div class="debug-info">
                <p><strong>✅ Meta tags generados correctamente en el servidor:</strong></p>
                <ul>
                    <li><strong>Title:</strong> <?php echo htmlspecialchars($meta_title, ENT_QUOTES, 'UTF-8'); ?></li>
                    <li><strong>Description:</strong> <?php echo htmlspecialchars($meta_description, ENT_QUOTES, 'UTF-8'); ?></li>
                    <li><strong>Short Description:</strong> <?php echo htmlspecialchars($short_description, ENT_QUOTES, 'UTF-8'); ?></li>
                    <li><strong>Canonical:</strong> <?php echo $canonical_url; ?> (sin "www.")</li>
                    <li><strong>Slug:</strong> <?php echo htmlspecialchars($evento['slug'], ENT_QUOTES, 'UTF-8'); ?></li>
                    <li><strong>ID:</strong> <?php echo htmlspecialchars($evento['id'], ENT_QUOTES, 'UTF-8'); ?></li>
                </ul>
                <p><em>Estos meta tags son visibles en el código fuente estático y para motores de búsqueda.</em></p>
            </div>
            
            <div style="margin-top: 30px;">
                <a href="/eventos-culturales-paginacion.html" style="background: #2F5233; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Volver a Eventos</a>
            </div>
        </div>
    </div>
    
    <script>
        // JavaScript para funcionalidad adicional
        console.log("Evento cargado: <?php echo $evento['titulo']; ?>");
        console.log("Meta tags generados en servidor:");
        console.log("Title: <?php echo $meta_title; ?>");
        console.log("Canonical: <?php echo $canonical_url; ?>");
    </script>
</body>
</html>