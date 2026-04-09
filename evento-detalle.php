<?php
/**
 * Página de Detalle de Evento - Versión Simple y Funcional
 * Usa header.php y footer.php existentes
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

// Variables para el evento
$evento_titulo = $titulo_desde_slug;
$evento_localidad = '';
$evento_provincia = '';
$evento_fecha = '';
$evento_organizador = '';
$evento_precio = '';
$evento_descripcion = 'Estamos actualizando la información detallada de este evento. Pronto tendrás todos los detalles disponibles.';
$evento_municipality = '';

// Intentar cargar datos REALES desde la API
if (!empty($slug)) {
    $api_url = "/api/evento-slug.php?slug=" . urlencode($slug);
    $context = stream_context_create([
        'http' => [
            'timeout' => 2,
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

// Establecer variables para header.php
$page_title = $meta_title;
$page_description = $meta_description;
$page_keywords = "evento, " . $titulo_desde_slug . ", cultura, turismo, rutas rurales";

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
                <?php if (!empty($evento_localidad) && !empty($evento_provincia)): ?>
                    <div class="page-subtitle">
                        <i class="fas fa-map-marker-alt"></i> 
                        <?php 
                            $ubicacion = $evento_localidad;
                            if (!empty($evento_municipality) && $evento_municipality != $evento_localidad) {
                                $ubicacion .= ' (' . $evento_municipality . ')';
                            }
                            $ubicacion .= ', ' . $evento_provincia;
                            echo $ubicacion;
                        ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Información del evento -->
            <div class="event-details">
                <div class="event-info-card">
                    <h2><i class="fas fa-info-circle"></i> Información del Evento</h2>
                    
                    <div class="info-grid">
                        <?php if (!empty($evento_fecha)): ?>
                            <div class="info-item">
                                <span class="info-label"><i class="fas fa-calendar-alt"></i> Fecha:</span>
                                <span class="info-value"><?php echo $evento_fecha; ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($evento_organizador)): ?>
                            <div class="info-item">
                                <span class="info-label"><i class="fas fa-user-tie"></i> Organizador:</span>
                                <span class="info-value"><?php echo $evento_organizador; ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($evento_precio)): ?>
                            <div class="info-item">
                                <span class="info-label"><i class="fas fa-tag"></i> Precio:</span>
                                <span class="info-value"><?php echo $evento_precio; ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($evento_localidad) && !empty($evento_provincia)): ?>
                            <div class="info-item">
                                <span class="info-label"><i class="fas fa-map-marker-alt"></i> Ubicación:</span>
                                <span class="info-value">
                                    <?php 
                                        $ubicacion = $evento_localidad;
                                        if (!empty($evento_municipality) && $evento_municipality != $evento_localidad) {
                                            $ubicacion .= ' (' . $evento_municipality . ')';
                                        }
                                        $ubicacion .= ', ' . $evento_provincia;
                                        echo $ubicacion;
                                    ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Descripción del evento -->
                <div class="event-description">
                    <h2><i class="fas fa-align-left"></i> Descripción</h2>
                    <div class="description-content">
                        <?php echo $evento_descripcion; ?>
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
        background: #f8f9fa;
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
    }
    
    .page-title {
        color: #2F5233;
        font-size: 2.5rem;
        margin-bottom: 15px;
        font-weight: bold;
    }
    
    .page-subtitle {
        color: #666;
        font-size: 1.2rem;
        margin-bottom: 25px;
    }
    
    .page-subtitle i {
        margin-right: 10px;
        color: #2F5233;
    }
    
    .event-details {
        background: white;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.08);
    }
    
    .event-info-card {
        margin-bottom: 30px;
        padding-bottom: 25px;
        border-bottom: 2px solid #e8f5e9;
    }
    
    .event-info-card h2 {
        color: #2F5233;
        margin-bottom: 20px;
        font-size: 1.8rem;
    }
    
    .event-info-card h2 i {
        margin-right: 10px;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .info-item {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #2F5233;
    }
    
    .info-label {
        font-weight: bold;
        color: #2F5233;
        margin-right: 10px;
        min-width: 120px;
    }
    
    .info-label i {
        margin-right: 8px;
        width: 20px;
        text-align: center;
    }
    
    .info-value {
        color: #333;
        flex: 1;
    }
    
    .event-description {
        margin-bottom: 30px;
    }
    
    .event-description h2 {
        color: #2F5233;
        margin-bottom: 20px;
        font-size: 1.8rem;
    }
    
    .event-description h2 i {
        margin-right: 10px;
    }
    
    .description-content {
        line-height: 1.8;
        color: #444;
        font-size: 1.1rem;
    }
    
    .action-buttons {
        display: flex;
        gap: 15px;
        margin-top: 30px;
        flex-wrap: wrap;
    }
    
    .btn {
        padding: 12px 25px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: bold;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }
    
    .btn-primary {
        background: #2F5233;
        color: white;
    }
    
    .btn-primary:hover {
        background: #246634;
        transform: translateY(-2px);
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .btn-secondary:hover {
        background: #5a6268;
        transform: translateY(-2px);
    }
    
    .error-message {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.08);
    }
    
    .error-message h2 {
        color: #2F5233;
        margin-bottom: 20px;
    }
    
    .error-message p {
        color: #666;
        margin-bottom: 30px;
        font-size: 1.1rem;
    }
    
    @media (max-width: 768px) {
        .page-title {
            font-size: 2rem;
        }
        
        .info-grid {
            grid-template-columns: 1fr;
        }
        
        .info-item {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .info-label {
            margin-bottom: 5px;
            min-width: auto;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<?php
// Incluir footer
include 'footer.php';
?>