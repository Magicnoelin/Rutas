<?php
/**
 * Página de Detalle de Evento - VERSIÓN DEFINITIVA SIN API
 * No depende de API, genera contenido desde el slug
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

// Extraer información del slug para hacerlo más específico
$slug_parts = explode('-', $slug);
$year = '';
$location = '';
$event_type = '';

// Buscar año (2026, 2025, etc.)
foreach ($slug_parts as $part) {
    if (preg_match('/^\d{4}$/', $part)) {
        $year = $part;
        break;
    }
}

// Buscar ubicaciones comunes
$locations = ['soria', 'burgos', 'valladolid', 'palencia', 'leon', 'zamora', 'salamanca', 'avila', 'segovia'];
foreach ($slug_parts as $part) {
    if (in_array(strtolower($part), $locations)) {
        $location = ucfirst($part);
        break;
    }
}

// Determinar tipo de evento basado en palabras clave
$event_keywords = [
    'jornadas' => 'Jornadas Gastronómicas',
    'fiestas' => 'Fiestas Populares',
    'feria' => 'Feria Tradicional',
    'festival' => 'Festival Cultural',
    'matanza' => 'Jornadas de Matanza',
    'gastronomia' => 'Evento Gastronómico',
    'cultural' => 'Evento Cultural',
    'musica' => 'Concierto',
    'teatro' => 'Obra de Teatro',
    'arte' => 'Exposición de Arte'
];

$event_type = 'Evento Cultural';
foreach ($event_keywords as $keyword => $type) {
    if (strpos($slug, $keyword) !== false) {
        $event_type = $type;
        break;
    }
}

// Meta tags personalizados desde el slug
$meta_title = $titulo_desde_slug . " | " . $event_type . " | Rutas Rurales";
$meta_description = "Descubre " . $titulo_desde_slug . " en Rutas Rurales. " . $event_type . ($location ? " en " . $location : "") . ($year ? " " . $year : "") . ". Reserva tu experiencia única.";
$short_description = $titulo_desde_slug . " - " . $event_type . ($location ? " en " . $location : "") . ". Descubre eventos únicos en Rutas Rurales.";

// Generar información del evento desde el slug
$evento_titulo = $titulo_desde_slug;
$evento_tipo = $event_type;

// Generar ubicación inteligente
if ($location) {
    $evento_localidad = $location;
    $evento_provincia = ucfirst($location); // Para simplificar
} else {
    // Intentar adivinar desde el slug
    $possible_locations = ['El Burgo de Osma', 'Soria', 'Burgos', 'Valladolid'];
    foreach ($possible_locations as $loc) {
        if (stripos($slug, str_replace(' ', '-', strtolower($loc))) !== false) {
            $evento_localidad = $loc;
            $evento_provincia = $loc;
            break;
        }
    }
    
    if (empty($evento_localidad)) {
        $evento_localidad = 'Varias localidades';
        $evento_provincia = 'Castilla y León';
    }
}

// Generar fecha (si hay año, usar ese año)
if ($year) {
    $evento_fecha = '15/06/' . $year; // Fecha por defecto
} else {
    $evento_fecha = 'Próximamente';
}

// Descripción más específica basada en el tipo de evento
$descripciones = [
    'Jornadas Gastronómicas' => 'Disfruta de una experiencia gastronómica única con los mejores productos de la tierra. Degustaciones, talleres y showcooking con chefs expertos.',
    'Fiestas Populares' => 'Vive la auténtica tradición castellana con fiestas populares llenas de color, música y folclore. Una experiencia cultural inolvidable.',
    'Feria Tradicional' => 'Mercado artesanal, productos locales y tradiciones ancestrales. Descubre la esencia de la cultura rural en esta feria única.',
    'Festival Cultural' => 'Espectáculos, exposiciones y actividades culturales para todos los públicos. Sumérgete en la riqueza cultural de la región.',
    'Jornadas de Matanza' => 'Revive la tradición más ancestral de la matanza del cerdo. Demostraciones, degustaciones y todo el saber hacer tradicional.',
    'Evento Gastronómico' => 'Sabores auténticos, productos de calidad y recetas tradicionales. Una celebración de la gastronomía local.',
    'Evento Cultural' => 'Arte, historia y tradición se unen en este evento cultural único. Descubre el patrimonio de la región.',
    'Concierto' => 'Disfruta de la mejor música en un entorno único. Conciertos acústicos, grupos locales y artistas invitados.',
    'Obra de Teatro' => 'Representaciones teatrales que rescatan historias y tradiciones locales. Cultura y entretenimiento para toda la familia.',
    'Exposición de Arte' => 'Descubre el talento local a través de exposiciones de pintura, escultura y fotografía. Arte y cultura en el medio rural.'
];

$evento_descripcion = isset($descripciones[$event_type]) ? $descripciones[$event_type] : 'Descubre este evento único que combina tradición, cultura y experiencias inolvidables en el corazón de la España rural.';

// Precio por defecto basado en tipo de evento
$precios = [
    'Jornadas Gastronómicas' => 'Desde 35€',
    'Fiestas Populares' => 'Gratuito',
    'Feria Tradicional' => 'Entrada libre',
    'Festival Cultural' => 'Desde 15€',
    'Jornadas de Matanza' => 'Desde 45€',
    'Evento Gastronómico' => 'Desde 25€',
    'Evento Cultural' => 'Desde 10€',
    'Concierto' => 'Desde 20€',
    'Obra de Teatro' => 'Desde 12€',
    'Exposición de Arte' => 'Gratuito'
];

$evento_precio = isset($precios[$event_type]) ? $precios[$event_type] : 'Consultar precios';

// Establecer variables para header.php
$page_title = $meta_title;
$page_description = $meta_description;
$page_keywords = "evento, " . strtolower($event_type) . ", " . strtolower($evento_localidad) . ", turismo rural, rutas rurales";

// Incluir header
include 'header.php';
?>

<!-- Contenido principal -->
<main class="main-content">
    <div class="container">
        <?php if (!empty($slug)): ?>
            <!-- Header del evento -->
            <div class="page-header">
                <h1 class="page-title"><?php echo $evento_titulo; ?></h1>
                <div class="page-subtitle">
                    <i class="fas fa-map-marker-alt"></i> <?php echo $evento_localidad . ', ' . $evento_provincia; ?>
                    <span style="margin: 0 10px;">•</span>
                    <i class="fas fa-calendar-alt"></i> <?php echo $evento_fecha; ?>
                    <span style="margin: 0 10px;">•</span>
                    <i class="fas fa-tag"></i> <?php echo $evento_tipo; ?>
                </div>
            </div>
            
            <!-- Información del evento -->
            <div class="event-details">
                <div class="event-highlights">
                    <div class="highlight-card">
                        <i class="fas fa-calendar-check"></i>
                        <h3>Fecha</h3>
                        <p><?php echo $evento_fecha; ?></p>
                    </div>
                    <div class="highlight-card">
                        <i class="fas fa-map-marker-alt"></i>
                        <h3>Ubicación</h3>
                        <p><?php echo $evento_localidad . ', ' . $evento_provincia; ?></p>
                    </div>
                    <div class="highlight-card">
                        <i class="fas fa-ticket-alt"></i>
                        <h3>Precio</h3>
                        <p><?php echo $evento_precio; ?></p>
                    </div>
                    <div class="highlight-card">
                        <i class="fas fa-star"></i>
                        <h3>Tipo</h3>
                        <p><?php echo $evento_tipo; ?></p>
                    </div>
                </div>
                
                <!-- Descripción del evento -->
                <div class="event-description">
                    <h2><i class="fas fa-align-left"></i> Sobre este evento</h2>
                    <div class="description-content">
                        <p><?php echo $evento_descripcion; ?></p>
                        
                        <div class="event-features">
                            <h3><i class="fas fa-check-circle"></i> ¿Qué incluye?</h3>
                            <ul>
                                <li><i class="fas fa-utensils"></i> Experiencias gastronómicas únicas</li>
                                <li><i class="fas fa-music"></i> Actividades culturales y musicales</li>
                                <li><i class="fas fa-users"></i> Ambiente familiar y acogedor</li>
                                <li><i class="fas fa-leaf"></i> Productos locales y sostenibles</li>
                                <li><i class="fas fa-history"></i> Tradiciones ancestrales</li>
                                <li><i class="fas fa-camera"></i> Oportunidades fotográficas únicas</li>
                            </ul>
                        </div>
                        
                        <div class="event-tips">
                            <h3><i class="fas fa-lightbulb"></i> Recomendaciones</h3>
                            <ul>
                                <li>Reserva con antelación para garantizar tu plaza</li>
                                <li>Llega con tiempo para disfrutar de todas las actividades</li>
                                <li>Consulta la programación completa en nuestra web</li>
                                <li>Sigue nuestras redes sociales para actualizaciones</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Información adicional -->
                <div class="additional-info">
                    <h2><i class="fas fa-info-circle"></i> Información práctica</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <h4>Horario</h4>
                                <p>Consultar programación específica</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-car"></i>
                            <div>
                                <h4>Acceso</h4>
                                <p>Parking disponible en las inmediaciones</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-wheelchair"></i>
                            <div>
                                <h4>Accesibilidad</h4>
                                <p>Espacios adaptados para movilidad reducida</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-child"></i>
                            <div>
                                <h4>Familias</h4>
                                <p>Actividades para todas las edades</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Botones de acción -->
                <div class="action-buttons">
                    <a href="/eventos-culturales-paginacion.html" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Volver a Eventos
                    </a>
                    <a href="/" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Ir al Inicio
                    </a>
                    <a href="/contacto" class="btn btn-success">
                        <i class="fas fa-envelope"></i> Más información
                    </a>
                </div>
                
                <!-- Meta tags info (oculto para usuarios, solo para SEO) -->
                <div style="display: none;">
                    <p>Meta title: <?php echo htmlspecialchars($meta_title); ?></p>
                    <p>Meta description: <?php echo htmlspecialchars($meta_description); ?></p>
                    <p>Short description: <?php echo htmlspecialchars($short_description); ?></p>
                    <p>Canonical: <?php echo htmlspecialchars($canonical_url); ?></p>
                </div>
            </div>
            
        <?php else: ?>
            <!-- Si no hay slug -->
            <div class="error-message">
                <h2>Evento no encontrado</h2>
                <p>No se ha especificado un evento válido en la URL.</p>
                <a href="/eventos-culturales-paginacion.html" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Volver a Eventos
                </a>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
    /* Estilos específicos para la página de evento */
    .main-content {
        padding: 40px 0;
        background: linear-gradient(135deg, #f8f9fa 0%, #e8f5e9 100%);
        min-height: 70vh;
    }
    
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    .page-header {
        margin-bottom: 40px;
        text-align: center;
        padding: 30px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .page-title {
        color: #2F5233;
        font-size: 2.8rem;
        margin-bottom: 20px;
        font-weight: bold;
        line-height: 1.2;
    }
    
    .page-subtitle {
        color: #666;
        font-size: 1.3rem;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .page-subtitle i {
        margin-right: 8px;
        color: #2F5233;
    }
    
    .event-details {
        background: white;
        border-radius: 15px;
        padding: 40px;
        box-shadow: 0 5px 25px rgba(0,0,0,0.08);
        margin-top: 20px;
    }
    
    .event-highlights {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 25px;
        margin-bottom: 40px;
    }
    
    .highlight-card {
        background: #f8f9fa;
        padding: 25px;
        border-radius: 12px;
        text-align: center;
        border-top: 4px solid #2F5233;
        transition: transform 0.3s ease;
    }
    
    .highlight-card:hover {
        transform: translateY(-5px);
    }
    
    .highlight-card i {
        font-size: 2.5rem;
        color: #2F5233;
        margin-bottom: 15px;
    }
    
    .highlight-card h3 {
        color: #2F5233;
        margin-bottom: 10px;
        font-size: 1.3rem;
    }
    
    .highlight-card p {
        color: #555;
        font-size: 1.1rem;
        font-weight: 500;
    }
    
    .event-description {
        margin-bottom: 40px;
        padding-bottom: 30px;
        border-bottom: 2px solid #e8f5e9;
    }
    
    .event-description h2 {
        color: #2F5233;
        margin-bottom: 25px;
        font-size: 2rem;
        display: flex;
        align-items: center;
        gap: