<?php
/**
 * Página de Detalle de Evento - VERSIÓN FINAL CON ESTILO MEJORADO
 * Diseño responsive con fotos y campos de interés público
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

// Extraer información del slug
$slug_parts = explode('-', $slug);
$year = '';
$location = '';
$event_type = '';

// Buscar año
foreach ($slug_parts as $part) {
    if (preg_match('/^\d{4}$/', $part)) {
        $year = $part;
        break;
    }
}

// Buscar ubicaciones
$locations = ['soria', 'burgos', 'valladolid', 'palencia', 'leon', 'zamora', 'salamanca', 'avila', 'segovia'];
foreach ($slug_parts as $part) {
    if (in_array(strtolower($part), $locations)) {
        $location = ucfirst($part);
        break;
    }
}

// Determinar tipo de evento
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

// Meta tags personalizados
$meta_title = $titulo_desde_slug . " | " . $event_type . " | Rutas Rurales";
$meta_description = "Descubre " . $titulo_desde_slug . " en Rutas Rurales. " . $event_type . ($location ? " en " . $location : "") . ($year ? " " . $year : "") . ". Información, horarios y reservas.";
$short_description = $titulo_desde_slug . " - " . $event_type . ($location ? " en " . $location : "") . ". Información completa para visitantes.";

// Información del evento
$evento_titulo = $titulo_desde_slug;
$evento_tipo = $event_type;

// Ubicación
if ($location) {
    $evento_localidad = $location;
    $evento_provincia = ucfirst($location);
} else {
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

// Fecha
if ($year) {
    $evento_fecha = '15/06/' . $year;
} else {
    $evento_fecha = 'Próximamente';
}

// Descripción específica
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

// Precio
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

// Horario según tipo de evento
$horarios = [
    'Jornadas Gastronómicas' => '12:00 - 18:00h',
    'Fiestas Populares' => 'Todo el día',
    'Feria Tradicional' => '10:00 - 20:00h',
    'Festival Cultural' => 'Varía según actividad',
    'Jornadas de Matanza' => '11:00 - 17:00h',
    'Evento Gastronómico' => '13:00 - 16:00h',
    'Evento Cultural' => 'Varía según programación',
    'Concierto' => '20:00 - 23:00h',
    'Obra de Teatro' => '19:00 - 21:00h',
    'Exposición de Arte' => '10:00 - 14:00 y 16:00 - 20:00h'
];

$evento_horario = isset($horarios[$event_type]) ? $horarios[$event_type] : 'Consultar horarios';

// Duración
$duraciones = [
    'Jornadas Gastronómicas' => '1 día',
    'Fiestas Populares' => 'Varios días',
    'Feria Tradicional' => 'Fin de semana',
    'Festival Cultural' => 'Varios días',
    'Jornadas de Matanza' => '1 día',
    'Evento Gastronómico' => '3-4 horas',
    'Evento Cultural' => 'Varía',
    'Concierto' => '2-3 horas',
    'Obra de Teatro' => '2 horas',
    'Exposición de Arte' => '1 mes'
];

$evento_duracion = isset($duraciones[$event_type]) ? $duraciones[$event_type] : 'Consultar duración';

// Imagen según tipo de evento
$imagenes_eventos = [
    'Jornadas Gastronómicas' => '/img/eventos/gastronomia.jpg',
    'Fiestas Populares' => '/img/eventos/fiestas.jpg',
    'Feria Tradicional' => '/img/eventos/feria.jpg',
    'Festival Cultural' => '/img/eventos/festival.jpg',
    'Jornadas de Matanza' => '/img/eventos/matanza.jpg',
    'Evento Gastronómico' => '/img/eventos/gastronomia2.jpg',
    'Evento Cultural' => '/img/eventos/cultural.jpg',
    'Concierto' => '/img/eventos/concierto.jpg',
    'Obra de Teatro' => '/img/eventos/teatro.jpg',
    'Exposición de Arte' => '/img/eventos/arte.jpg'
];

$evento_imagen = isset($imagenes_eventos[$event_type]) ? $imagenes_eventos[$event_type] : '/img/eventos/default.jpg';

// Verificar si la imagen existe, si no usar una por defecto
if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $evento_imagen)) {
    $evento_imagen = 'https://images.unsplash.com/photo-1511578314322-379afb476865?ixlib=rb-1.2.1&auto=format&fit=crop&w=1200&q=80';
}

// Variables para header.php
$page_title = $meta_title;
$page_description = $meta_description;
$page_keywords = "evento, " . strtolower($event_type) . ", " . strtolower($evento_localidad) . ", turismo rural, rutas rurales";

// Incluir header
include 'header.php';
?>

<!-- Contenido principal -->
<main class="evento-detalle-main">
    <div class="container">
        <?php if (!empty($slug)): ?>
            <!-- Header con imagen -->
            <div class="evento-header">
                <div class="evento-imagen-container">
                    <img src="<?php echo $evento_imagen; ?>" alt="<?php echo $evento_titulo; ?>" class="evento-imagen">
                    <div class="evento-imagen-overlay">
                        <h1 class="evento-titulo"><?php echo $evento_titulo; ?></h1>
                        <div class="evento-subtitulo">
                            <span><i class="fas fa-map-marker-alt"></i> <?php echo $evento_localidad . ', ' . $evento_provincia; ?></span>
                            <span><i class="fas fa-calendar-alt"></i> <?php echo $evento_fecha; ?></span>
                            <span><i class="fas fa-tag"></i> <?php echo $evento_tipo; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Información principal -->
            <div class="evento-contenido">
                <div class="evento-info-grid">
                    <!-- Información básica -->
                    <div class="info-card">
                        <h3><i class="fas fa-info-circle"></i> Información básica</h3>
                        <div class="info-list">
                            <div class="info-item">
                                <span class="info-label">Fecha:</span>
                                <span class="info-value"><?php echo $evento_fecha; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Horario:</span>
                                <span class="info-value"><?php echo $evento_horario; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Duración:</span>
                                <span class="info-value"><?php echo $evento_duracion; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Precio:</span>
                                <span class="info-value"><?php echo $evento_precio; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Ubicación:</span>
                                <span class="info-value"><?php echo $evento_localidad . ', ' . $evento_provincia; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Tipo de evento:</span>
                                <span class="info-value"><?php echo $evento_tipo; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Descripción -->
                    <div class="descripcion-card">
                        <h3><i class="fas fa-align-left"></i> Descripción</h3>
                        <div class="descripcion-contenido">
                            <p><?php echo $evento_descripcion; ?></p>
                            
                            <div class="caracteristicas">
                                <h4><i class="fas fa-check"></i> Características principales</h4>
                                <ul>
                                    <li>Experiencia auténtica y tradicional</li>
                                    <li>Productos locales de calidad</li>
                                    <li>Ambiente familiar y acogedor</li>
                                    <li>Actividades para todas las edades</li>
                                    <li>Oportunidad para conocer la cultura local</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Información adicional -->
                <div class="info-adicional">
                    <h3><i class="fas fa-clipboard-list"></i> Información para visitantes</h3>
                    <div class="adicional-grid">
                        <div class="adicional-item">
                            <i class="fas fa-car"></i>
                            <h4>Cómo llegar</h4>
                            <p>Acceso por carretera con parking disponible. Transporte público disponible desde la capital provincial.</p>
                        </div>
                        <div class="adicional-item">
                            <i class="fas fa-utensils"></i>
                            <h4>Servicios</h4>
                            <p>Zona de restauración, aseos públicos, área de descanso y puntos de información.</p>
                        </div>
                        <div class="adicional-item">
                            <i class="fas fa-users"></i>
                            <h4>Recomendaciones</h4>
                            <p>Llegar con antelación, consultar programación específica y seguir indicaciones del personal.</p>
                        </div>
                        <div class="adicional-item">
                            <i class="fas fa-phone"></i>
                            <h4>Contacto</h4>
                            <p>Para más información: info@rutasrurales.io o consulta nuestra web.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Botones de acción -->
                <div class="botones-accion">
                    <a href="/eventos-culturales-paginacion.html" class="btn btn-volver">
                        <i class="fas fa-arrow-left"></i> Volver a Eventos
                    </a>
                    <a href="/" class="btn btn-inicio">
                        <i class="fas fa-home"></i> Ir al Inicio
                    </a>
                    <a href="/contacto" class="btn btn-info">
                        <i class="fas fa-envelope"></i> Más información
                    </a>
                </div>
            </div>
            
        <?php else: ?>
            <!-- Si no hay slug -->
            <div class="evento-no-encontrado">
                <h2>Evento no encontrado</h2>
                <p>No se ha especificado un evento válido en la URL.</p>
                <a href="/eventos-culturales-paginacion.html" class="btn btn-volver">
                    <i class="fas fa-arrow-left"></i> Volver a Eventos
                </a>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
    /* Estilos principales */
    .evento-detalle-main {
        padding: 20px 0 40px;
        background: #f8f9fa;
        min-height: 70vh;
    }
    
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 15px;
    }
    
    /* Header con imagen */
    .evento-header {
        margin-bottom: 30px;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .evento-imagen