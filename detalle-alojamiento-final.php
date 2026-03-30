<?php
/**
 * Página de Detalle de Alojamiento - VERSIÓN COMPLETA
 * URLs amigables: /alojamientos/casa-enrique-santervas
 */

require_once 'api/config_updated.php';

// Inicializar medidas de seguridad
initSecurity();

// Obtener slug de la URL
$slug = $_GET['slug'] ?? '';

// Si no hay slug, redirigir a la lista principal
if (empty($slug)) {
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: /alojamientos-turisticos-paginacion.html');
    exit();
}

try {
    $pdo = getDBConnection();
    
    // Buscar alojamiento por slug
    $stmt = $pdo->prepare("SELECT * FROM accommodations WHERE slug = ? AND is_active = 1");
    $stmt->execute([$slug]);
    $alojamiento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Si no se encuentra el alojamiento, mostrar página 404
    if (!$alojamiento) {
        http_response_code(404);
        $tituloPagina = 'Alojamiento no encontrado';
        $descripcion = 'El alojamiento que buscas no existe o ha sido eliminado.';
        $imagenPrincipal = 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&h=600&fit=crop';
    } else {
        $tituloPagina = $alojamiento['name'] . ' - ' . $alojamiento['municipality'] . ', ' . $alojamiento['province'];
        $descripcion = $alojamiento['description'] ?? 'Alojamiento turístico en ' . $alojamiento['municipality'];
        $imagenPrincipal = $alojamiento['photo1'] ?? 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&h=600&fit=crop';
    }
    
} catch (PDOException $e) {
    // Log error pero mostrar página con datos básicos
    error_log('Error al cargar alojamiento: ' . $e->getMessage());
    $alojamiento = null;
    $tituloPagina = 'Error al cargar alojamiento';
    $descripcion = 'Ha ocurrido un error al cargar los datos del alojamiento.';
    $imagenPrincipal = 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&h=600&fit=crop';
}

// Función para formatear precio
function formatearPrecio($precio) {
    if (empty($precio) || $precio <= 0) {
        return 'Consultar precio';
    }
    return number_format($precio, 0, ',', '.') . '€/noche';
}

// Función para obtener fotos del alojamiento
function obtenerFotosAlojamiento($alojamiento) {
    $fotos = [];
    
    // Priorizar array de fotos si existe
    if (!empty($alojamiento['photos']) && is_string($alojamiento['photos'])) {
        $fotosArray = json_decode($alojamiento['photos'], true);
        if (is_array($fotosArray)) {
            $fotos = $fotosArray;
        }
    }
    
    // Si no hay fotos en array, usar fotos individuales
    if (empty($fotos)) {
        $fotosIndividuales = [
            $alojamiento['photo1'],
            $alojamiento['photo2'], 
            $alojamiento['photo3'],
            $alojamiento['photo4']
        ];
        $fotos = array_filter($fotosIndividuales);
    }
    
    // Si no hay fotos, usar imagen por defecto
    if (empty($fotos)) {
        $fotos = ['https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&h=600&fit=crop'];
    }
    
    return array_values($fotos);
}

// Generar URL canónica
$urlCanon = 'https://rutasrurales.io/alojamientos/' . $slug;

// Obtener fotos
$fotos = $alojamiento ? obtenerFotosAlojamiento($alojamiento) : [$imagenPrincipal];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-X990K5GE42"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-X990K5GE42');
    </script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tituloPagina, ENT_QUOTES, 'UTF-8'); ?></title>
    
    <!-- Meta tags básicos -->
    <meta name="description" content="<?php echo htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="keywords" content="alojamiento turístico, <?php echo $alojamiento ? htmlspecialchars($alojamiento['municipality'], ENT_QUOTES, 'UTF-8') : ''; ?>, turismo rural, Castilla y León">
    <meta name="author" content="Rutas Rurales">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $urlCanon; ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($tituloPagina, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image" content="<?php echo $imagenPrincipal; ?>">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo $urlCanon; ?>">
    <meta property="twitter:title" content="<?php echo htmlspecialchars($tituloPagina, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="twitter:description" content="<?php echo htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="twitter:image" content="<?php echo $imagenPrincipal; ?>">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?php echo $urlCanon; ?>">
    
    <!-- Schema.org JSON-LD -->
    <?php if ($alojamiento): ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "LodgingBusiness",
        "name": "<?php echo htmlspecialchars($alojamiento['name'], ENT_QUOTES, 'UTF-8'); ?>",
        "description": "<?php echo htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8'); ?>",
        "address": {
            "@type": "PostalAddress",
            "addressLocality": "<?php echo htmlspecialchars($alojamiento['municipality'], ENT_QUOTES, 'UTF-8'); ?>",
            "addressRegion": "<?php echo htmlspecialchars($alojamiento['province'], ENT_QUOTES, 'UTF-8'); ?>",
            "addressCountry": "ES"
        },
        "telephone": "<?php echo htmlspecialchars($alojamiento['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>",
        "email": "<?php echo htmlspecialchars($alojamiento['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>",
        "url": "<?php echo $urlCanon; ?>",
        <?php if ($alojamiento['price_per_night'] > 0): ?>
        "priceRange": "€€",
        <?php endif; ?>
        "amenityFeature": [
            {"@type": "LocationFeatureSpecification", "name": "WiFi", "value": true},
            {"@type": "LocationFeatureSpecification", "name": "Capacidad", "value": <?php echo $alojamiento['capacity']; ?>}
        ]
    }
    </script>
    <?php endif; ?>
    
    <link rel="icon" href="favicon.png" type="image/png">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .breadcrumb {
            background: #f8f9fa;
            padding: 1rem 0;
            margin-top: 80px;
        }
        .breadcrumb-nav {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        .breadcrumb-nav a {
            color: var(--primary-color);
            text-decoration: none;
        }
        .breadcrumb-nav a:hover {
            text-decoration: underline;
        }
        .detalle-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .detalle-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .detalle-titulo {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        .detalle-ubicacion {
            font-size: 1.2rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }
        .detalle-precio {
            font-size: 1.8rem;
            color: var(--accent-color);
            font-weight: bold;
        }
        .fotos-galeria {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .foto-principal {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        .fotos-secundarias {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        .foto-secundaria {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s;
        }
        .foto-secundaria:hover {
            transform: scale(1.05);
        }
        .detalle-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .info-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .info-card h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        .detalle-descripcion {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .contacto-botones {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }
        .btn-contacto {
            padding: 1rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }
        .btn-whatsapp {
            background: #25D366;
            color: white;
        }
        .btn-whatsapp:hover {
            background: #20b558;
            transform: translateY(-2px);
        }
        .btn-telefono {
            background: var(--primary-color);
            color: white;
        }
        .btn-telefono:hover {
            background: #2d5a2d;
            transform: translateY(-2px);
        }
        .btn-email {
            background: var(--secondary-color);
            color: white;
        }
        .btn-email:hover {
            background: #4a7c4a;
            transform: translateY(-2px);
        }
        .btn-web {
            background: #007cba;
            color: white;
        }
        .btn-web:hover {
            background: #005a87;
            transform: translateY(-2px);
        }
        .volver-lista {
            text-align: center;
            margin: 3rem 0;
        }
        @media (max-width: 768px) {
            .detalle-info {
                grid-template-columns: 1fr;
            }
            .detalle-titulo {
                font-size: 2rem;
            }
            .fotos-secundarias {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar">
            <div class="container">
                <div class="logo">
                    <img src="logo_990x1076_verde.png" alt="Rutas Logo" style="height: 50px; margin-right: 10px;">
                </div>
                <ul class="nav-menu">
                    <li><a href="index.html">Inicio</a></li>
                    <li class="dropdown">
                        <a href="index.html#alojamientos">Alojamientos Turísticos <i class="fas fa-chevron-down"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="alojamientos.html">Ver Alojamientos Rurales</a></li>
                            <li><a href="alojamientos-turisticos.html">Ver Alojamientos Turísticos</a></li>
                            <li><a href="agregar-alojamiento.html">Añadir Alojamiento</a></li>
                        </ul>
                    </li>
                    <li><a href="rutas-turisticas.html">Rutas Turísticas</a></li>
                    <li><a href="eventos-culturales.html">Eventos Culturales</a></li>
                    <li><a href="compromiso-social.html">Nuestro Compromiso Social</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <nav class="breadcrumb-nav">
            <a href="index.html">Inicio</a> 
            <span> / </span>
            <a href="alojamientos-turisticos-paginacion.html">Alojamientos Turísticos</a>
            <span> / </span>
            <span><?php echo $alojamiento ? htmlspecialchars($alojamiento['name'], ENT_QUOTES, 'UTF-8') : 'No encontrado'; ?></span>
        </nav>
    </div>

    <div class="detalle-container">
        <?php if ($alojamiento): ?>
            <!-- Header del alojamiento -->
            <div class="detalle-header">
                <h1 class="detalle-titulo"><?php echo htmlspecialchars($alojamiento['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="detalle-ubicacion">
                    <i class="fas fa-map-marker-alt"></i> 
                    <?php echo htmlspecialchars($alojamiento['municipality'], ENT_QUOTES, 'UTF-8'); ?>, <?php echo htmlspecialchars($alojamiento['province'], ENT_QUOTES, 'UTF-8'); ?>
                </p>
                <div class="detalle-precio">
                    <?php echo formatearPrecio($alojamiento['price_per_night']); ?>
                </div>
            </div>

            <!-- Galería de fotos -->
            <div class="fotos-galeria">
                <h2><i class="fas fa-camera"></i> Galería de Fotos</h2>
                <img src="<?php echo $fotos[0]; ?>" alt="Foto principal de <?php echo htmlspecialchars($alojamiento['name'], ENT_QUOTES, 'UTF-8'); ?>" class="foto-principal">
                
                <?php if (count($fotos) > 1): ?>
                <div class="fotos-secundarias">
                    <?php foreach (array_slice($fotos, 1, 4) as $index => $foto): ?>
                    <img src="<?php echo $foto; ?>" alt="Foto <?php echo $index + 2; ?> de <?php echo htmlspecialchars($alojamiento['name'], ENT_QUOTES, 'UTF-8'); ?>" class="foto-secundaria" onclick="cambiarFotoPrincipal('<?php echo $foto; ?>', '<?php echo htmlspecialchars($alojamiento['name'], ENT_QUOTES, 'UTF-8'); ?>')">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Información del alojamiento -->
            <div class="detalle-info">
                <!-- Características -->
                <div class="info-card">
                    <h3><i class="fas fa-info-circle"></i> Características</h3>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 0.5rem;"><i class
