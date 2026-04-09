<?php
/**
 * Página de Detalle de Evento - VERSIÓN SIMPLE Y COMPLETA
 * CSS completo incluido, diseño responsive
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

// Crear título desde el slug
$titulo_desde_slug = str_replace('-', ' ', $slug);
$titulo_desde_slug = ucwords($titulo_desde_slug);

// Extraer información del slug
$slug_parts = explode('-', $slug);
$year = '';
$location = '';
$event_type = 'Evento Cultural';

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

foreach ($event_keywords as $keyword => $type) {
    if (strpos($slug, $keyword) !== false) {
        $event_type = $type;
        break;
    }
}

// Meta tags
$meta_title = $titulo_desde_slug . " | " . $event_type . " | Rutas Rurales";
$meta_description = "Descubre " . $titulo_desde_slug . " en Rutas Rurales. " . $event_type . ($location ? " en " . $location : "") . ($year ? " " . $year : "") . ". Información completa.";
$short_description = $titulo_desde_slug . " - " . $event_type . ($location ? " en " . $location : "") . ". Información para visitantes.";

// Información del evento
$evento_titulo = $titulo_desde_slug;
$evento_tipo = $event_type;

// Ubicación
if ($location) {
    $evento_localidad = $location;
    $evento_provincia = $location;
} else {
    $evento_localidad = 'Castilla y León';
    $evento_provincia = 'Castilla y León';
}

// Fecha
$evento_fecha = $year ? '15/06/' . $year : 'Próximamente';

// Descripción
$descripciones = [
    'Jornadas Gastronómicas' => 'Disfruta de una experiencia gastronómica única con los mejores productos locales.',
    'Fiestas Populares' => 'Vive la auténtica tradición castellana con fiestas llenas de color y folclore.',
    'Feria Tradicional' => 'Mercado artesanal y productos locales en un ambiente tradicional.',
    'Festival Cultural' => 'Espectáculos y actividades culturales para todos los públicos.',
    'Jornadas de Matanza' => 'Revive la tradición ancestral de la matanza del cerdo.',
    'Evento Gastronómico' => 'Sabores auténticos y recetas tradicionales.',
    'Evento Cultural' => 'Arte, historia y tradición en un evento único.',
    'Concierto' => 'Disfruta de la mejor música en un entorno especial.',
    'Obra de Teatro' => 'Representaciones teatrales con historias locales.',
    'Exposición de Arte' => 'Descubre el talento local a través del arte.'
];

$evento_descripcion = isset($descripciones[$event_type]) ? $descripciones[$event_type] : 'Evento cultural único en el corazón de la España rural.';

// Precio
$evento_precio = 'Consultar precios';

// Variables para header.php
$page_title = $meta_title;
$page_description = $meta_description;
$page_keywords = "evento, " . strtolower($event_type) . ", turismo rural";

// Incluir header
include 'header.php';
?>

<!-- Contenido principal -->
<main class="evento-detalle">
    <div class="container">
        <?php if (!empty($slug)): ?>
            <!-- Header con imagen -->
            <div class="evento-header-con-imagen">
                <div class="evento-imagen-fondo">
                    <div class="evento-imagen-overlay">
                        <h1 class="evento-titulo"><?php echo $evento_titulo; ?></h1>
                        <div class="evento-subtitulo">
                            <span class="evento-lugar"><i class="fas fa-map-marker-alt"></i> <?php echo $evento_localidad . ', ' . $evento_provincia; ?></span>
                            <span class="evento-fecha"><i class="fas fa-calendar-alt"></i> <?php echo $evento_fecha; ?></span>
                            <span class="evento-tipo"><i class="fas fa-tag"></i> <?php echo $evento_tipo; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Contenido principal -->
            <div class="evento-contenido">
                <!-- Información básica -->
                <div class="evento-info">
                    <h2><i class="fas fa-info-circle"></i> Información del evento</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-icon"><i class="fas fa-calendar"></i></div>
                            <div class="info-content">
                                <h3>Fecha</h3>
                                <p><?php echo $evento_fecha; ?></p>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon"><i class="fas fa-clock"></i></div>
                            <div class="info-content">
                                <h3>Horario</h3>
                                <p>Consultar programación</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon"><i class="fas fa-ticket-alt"></i></div>
                            <div class="info-content">
                                <h3>Precio</h3>
                                <p><?php echo $evento_precio; ?></p>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                            <div class="info-content">
                                <h3>Ubicación</h3>
                                <p><?php echo $evento_localidad . ', ' . $evento_provincia; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Descripción -->
                <div class="evento-descripcion">
                    <h2><i class="fas fa-align-left"></i> Descripción</h2>
                    <div class="descripcion-texto">
                        <p><?php echo $evento_descripcion; ?></p>
                        <p>Este evento ofrece una experiencia única para conocer la cultura y tradiciones locales en un ambiente auténtico y acogedor.</p>
                    </div>
                    
                    <div class="evento-caracteristicas">
                        <h3><i class="fas fa-check-circle"></i> Características</h3>
                        <ul>
                            <li><i class="fas fa-check"></i> Experiencia auténtica</li>
                            <li><i class="fas fa-check"></i> Productos locales</li>
                            <li><i class="fas fa-check"></i> Ambiente familiar</li>
                            <li><i class="fas fa-check"></i> Actividades variadas</li>
                            <li><i class="fas fa-check"></i> Cultura tradicional</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Información adicional -->
                <div class="evento-adicional">
                    <h2><i class="fas fa-clipboard-list"></i> Información práctica</h2>
                    <div class="adicional-grid">
                        <div class="adicional-item">
                            <i class="fas fa-car"></i>
                            <h4>Cómo llegar</h4>
                            <p>Acceso por carretera con parking disponible.</p>
                        </div>
                        <div class="adicional-item">
                            <i class="fas fa-utensils"></i>
                            <h4>Servicios</h4>
                            <p>Zona de restauración y aseos públicos.</p>
                        </div>
                        <div class="adicional-item">
                            <i class="fas fa-users"></i>
                            <h4>Para familias</h4>
                            <p>Actividades adecuadas para todas las edades.</p>
                        </div>
                        <div class="adicional-item">
                            <i class="fas fa-info"></i>
                            <h4>Más info</h4>
                            <p>Consulta nuestra web o redes sociales.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Botones -->
                <div class="evento-botones">
                    <a href="/eventos-culturales-paginacion.html" class="btn btn-primario">
                        <i class="fas fa-arrow-left"></i> Volver a Eventos
                    </a>
                    <a href="/" class="btn btn-secundario">
                        <i class="fas fa-home"></i> Ir al Inicio
                    </a>
                </div>
            </div>
            
        <?php else: ?>
            <!-- Si no hay slug -->
            <div class="evento-error">
                <h2>Evento no encontrado</h2>
                <p>No se ha especificado un evento válido.</p>
                <a href="/eventos-culturales-paginacion.html" class="btn btn-primario">
                    <i class="fas fa-arrow-left"></i> Volver a Eventos
                </a>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
    /* Estilos generales */
    .evento-detalle {
        padding: 30px 0;
        background: #f8f9fa;
        min-height: 70vh;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    /* Header con imagen */
    .evento-header-con-imagen {
        margin-bottom: 30px;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .evento-imagen-fondo {
        background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.7)), 
                    url('https://images.unsplash.com/photo-1511578314322-379afb476865?ixlib=rb-1.2.1&auto=format&fit=crop&w=1200&q=80');
        background-size: cover;
        background-position: center;
        min-height: 300px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
    }
    
    .evento-imagen-overlay {
        text-align: center;
        color: white;
        max-width: 800px;
    }
    
    .evento-titulo {
        color: white;
        font-size: 2.5rem;
        margin-bottom: 20px;
        font-weight: 700;
        line-height: 1.2;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
    }
    
    .evento-subtitulo {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 25px;
        color: rgba(255, 255, 255, 0.9);
        font-size: 1.2rem;
    }
    
    .evento-subtitulo span {
        display: flex;
        align-items: center;
        gap: 10px;
        background: rgba(47, 82, 51, 0.8);
        padding: 8px 15px;
        border-radius: 20px;
    }
    
    .evento-subtitulo i {
        color: #a3d9a5;
    }
    
    /* Contenido principal */
    .evento-contenido {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    }
    
    /* Información básica */
    .evento-info h2 {
        color: #2F5233;
        font-size: 1.8rem;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }
    
    .info-item {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        border-left: 4px solid #2F5233;
        display: flex;
        align-items: flex-start;
        gap: 15px;
    }
    
    .info-icon {
        background: #2F5233;
        color: white;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .info-content h3 {
        color: #333;
        font-size: 1.1rem;
        margin-bottom: 5px;
        font-weight: 600;
    }
    
    .info-content p {
        color: #666;
        margin: 0;
    }
    
    /* Descripción */
    .evento-descripcion {
        margin-bottom: 40px;
    }
    
    .evento-descripcion h2 {
        color: #2F5233;
        font-size: 1.8rem;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .descripcion-texto {
        line-height: 1.7;
        color: #444;
        margin-bottom: 25px;
        font-size: 1.1rem;
    }
    
    .evento-caracteristicas {
        background: #f0f7f0;
        padding: 20px;
        border-radius: 8px;
        border-left: 4px solid #4CAF50;
    }
    
    .evento-caracteristicas h3 {
        color: #2F5233;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .evento-caracteristicas ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .evento-caracteristicas li {
        padding: 8px 0;
        display: flex;
        align-items: center;
        gap: 10px;
        color: #555;
    }
    
    .evento-caracteristicas li i {
        color: #4CAF50;
    }
    
    /* Información adicional */
    .evento-adicional {
        margin-bottom: 40px;
    }
    
    .evento-adicional h2 {
        color: #2F5233;
        font-size: 1.8rem;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .adicional-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .adicional-item {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
        transition: transform 0.3s ease;
    }
    
    .adicional-item:hover {
        transform: translateY(-5px);
    }
    
    .adicional-item i {
        font-size: 2rem;
        color: #2F5233;
        margin-bottom: 15px;
    }
    
    .adicional-item h4 {
        color: #333;
        margin-bottom: 10px;
        font-size: 1.2rem;
    }
    
    .adicional-item p {
        color: #666;
        margin: 0;
        font-size: 0.95rem;
        line-height: 1.5;
    }
    
    /* Botones */
    .evento-botones {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        justify-content: center;
        margin-top: 30px;
    }
    
    .btn {
        padding: 12px 25px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        font-size: 1rem;
    }
    
    .btn-primario {
        background: #2F5233;
        color: white;
    }
    
    .btn-primario:hover {
        background: #246634;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .btn-secundario {
        background: #6c757d;
        color: white;
    }
    
    .btn-secundario:hover {
        background: #5a6268;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    /* Error */
    .evento-error {
        text-align: center;
        padding: 60px 30px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    }
    
    .evento-error h2 {
        color: #2F5233;
        margin-bottom: 15px;
    }
    
    .evento-error p {
        color: #666;
        margin-bottom: 25px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .evento-titulo {
            font-size: 1.8rem;
        }
        
        .evento-subtitulo {
            flex-direction: column;
            gap: 10px;
            align-items: center;
        }
        
        .info-grid {
            grid-template-columns: 1fr;
        }
        
        .adicional-grid {
            grid-template-columns: 1fr;
        }
        
        .evento-botones {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
            justify-content: center;
        }
    }
    
    @media (max-width: 480px) {
        .evento-header,
        .evento-contenido {
            padding: 20px;
        }
        
        .evento-titulo {
            font-size: 1.6rem;
        }
        
        .info-item {
            flex-direction: column;
            text-align: center;
        }
        
        .info-icon {
            margin: 0 auto 10px;
        }
    }
</style>

<?php
// Incluir footer
include 'footer.php';
?>
    
