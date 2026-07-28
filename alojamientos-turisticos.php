<?php
// -----------------------------------------------------------------------------
// 1. CONFIGURACIÓN Y OBTENCIÓN DE DATOS (SERVER-SIDE)
// -----------------------------------------------------------------------------

// Incluir configuración de la base de datos y funciones de ayuda
// Asegúrate de que la ruta sea correcta desde tu archivo.
// Asumo que 'api/config.php' contiene la conexión PDO.
require_once __DIR__ . '/api/config.php'; // Usar __DIR__ para ruta absoluta

// Función para obtener alojamientos. Similar a tu API, pero para uso interno.
function get_initial_accommodations($pdo, $limit = 20) {
    try {
        // Consulta base para obtener los alojamientos activos
        // Ajusta los campos según tu esquema de base de datos real
        $stmt = $pdo->prepare(
            "SELECT id, name, slug, main_image, short_description, municipality, province, price_per_night, capacity, registration_number, accommodation_type
             FROM accommodations
             WHERE is_active = 1
             ORDER BY is_featured DESC, updated_at DESC
             LIMIT :limit"
        );
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // En un entorno de producción, registraríamos el error en lugar de mostrarlo.
        error_log("Error al obtener alojamientos iniciales: " . $e->getMessage());
        return []; // Devolver un array vacío en caso de error
    }
}

// Función para renderizar una tarjeta de alojamiento
function render_accommodation_card($alojamiento) {
    // Sanitizar datos para evitar XSS
    $id = htmlspecialchars($alojamiento['id'] ?? '', ENT_QUOTES, 'UTF-8');
    $name = htmlspecialchars($alojamiento['name'] ?? 'Alojamiento sin nombre', ENT_QUOTES, 'UTF-8');
    $municipality = htmlspecialchars($alojamiento['municipality'] ?? 'Sin localidad', ENT_QUOTES, 'UTF-8');
    $province = htmlspecialchars($alojamiento['province'] ?? 'Soria', ENT_QUOTES, 'UTF-8');
    $short_description = htmlspecialchars($alojamiento['short_description'] ?? '', ENT_QUOTES, 'UTF-8');
    $slug = htmlspecialchars($alojamiento['slug'] ?? '', ENT_QUOTES, 'UTF-8');
    $url = '/alojamiento/' . $slug; // Asumiendo que tienes una URL amigable para el detalle
    $main_image = htmlspecialchars($alojamiento['main_image'] ?? '/menu_images/image_not_found.webp', ENT_QUOTES, 'UTF-8');
    $price_per_night = (float)($alojamiento['price_per_night'] ?? 0);
    $priceHTML = $price_per_night > 0 ? htmlspecialchars($price_per_night, ENT_QUOTES, 'UTF-8') . '€' : 'Consultar';
    $capacity = (int)($alojamiento['capacity'] ?? 1);
    $registration_number = htmlspecialchars($alojamiento['registration_number'] ?? '', ENT_QUOTES, 'UTF-8');
    $accommodation_type = htmlspecialchars($alojamiento['accommodation_type'] ?? 'Sin tipo', ENT_QUOTES, 'UTF-8');

    // Aquí deberías adaptar la lógica para las fotos si tu DB las devuelve como un array o múltiples campos
    // Por simplicidad, asumo 'main_image' por ahora. Si tienes 'photo1', 'photo2', etc., necesitarías ajustarlo.
    // O si tienes una tabla de fotos relacionadas, hacer un JOIN o una segunda consulta.
    // Para este ejemplo, solo usaremos main_image.
    $fotos = [$main_image]; // Simplificado para SSR

    $carouselId = "carousel-{$id}";
    $carouselHTML = '';
    if (!empty($fotos)) {
        $carouselHTML .= '<div class="photo-carousel" id="' . $carouselId . '" style="position: relative; height: 250px; overflow: hidden; border-radius: 15px 15px 0 0;">';
        $carouselHTML .= '<div class="carousel-slides" style="display: flex; height: 100%; transition: transform 0.3s ease;">';
        foreach ($fotos as $index => $foto) {
            $carouselHTML .= '<img src="' . $foto . '" alt="Fotografía ' . ($index + 1) . ' de ' . $name . '" class="card-image" loading="lazy" decoding="async" width="400" height="250" onerror="this.src=\'/menu_images/image_not_found.webp\'" style="width: 100%; height: 100%; object-fit: cover; flex-shrink: 0;">';
        }
        $carouselHTML .= '</div>';

        // Solo añadir controles si hay más de una foto (aunque aquí solo tenemos una por simplificación)
        // En un escenario real con múltiples fotos, esta lógica sería más compleja.
        if (count($fotos) > 1) {
            $carouselHTML .= '<button class="carousel-prev" onclick="cambiarFoto(\'' . $carouselId . '\', -1)" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.5); color: white; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;"><i class="fas fa-chevron-left"></i></button>';
            $carouselHTML .= '<button class="carousel-next" onclick="cambiarFoto(\'' . $carouselId . '\', 1)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.5); color: white; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;"><i class="fas fa-chevron-right"></i></button>';
            $carouselHTML .= '<div class="carousel-indicators" style="position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%); display: flex; gap: 5px;">';
            foreach ($fotos as $index => $foto) {
                $carouselHTML .= '<span class="indicator ' . ($index === 0 ? 'active' : '') . '" onclick="irAFoto(\'' . $carouselId . '\', ' . $index . ')" style="width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,0.5); cursor: pointer; transition: background 0.3s;"></span>';
            }
            $carouselHTML .= '</div>';
        }
        $carouselHTML .= '</div>';
    } else {
        // Fallback si no hay fotos
        $carouselHTML = '<div class="photo-carousel" style="height: 250px; background: #e0e0e0; display: flex; align-items: center; justify-content: center; border-radius: 15px 15px 0 0;"><i class="fas fa-image fa-3x" style="color: #bbb;"></i></div>';
    }


    $caracteristicasHTML = ''; // Si tu DB tiene una columna de características, la procesarías aquí
    // Ejemplo: if (isset($alojamiento['caracteristicas_json'])) { $caracteristicas = json_decode($alojamiento['caracteristicas_json'], true); ... }

    $botonesHTML = '<a href="' . $url . '" class="btn-primary" style="margin-right: 0.5rem;"><i class="fas fa-eye"></i> Ver detalle</a>';
    // Si tu DB tiene web o teléfono, los añadirías aquí
    // if (!empty($alojamiento['website'])) { $botonesHTML .= '<a href="' . htmlspecialchars($alojamiento['website']) . '" target="_blank" class="btn-secondary" style="margin-right: 0.5rem;"><i class="fas fa-globe"></i> Web</a>'; }
    // if (!empty($alojamiento['phone'])) { $botonesHTML .= '<a href="tel:' . htmlspecialchars($alojamiento['phone']) . '" class="btn-secondary"><i class="fas fa-phone"></i> Llamar</a>'; }


    return <<<HTML
    <div class="card" data-id="{$id}">
        {$carouselHTML}
        <div class="card-content">
            <h3>{$name}</h3>
            {$registration_number ? '<p style="margin: 0.5rem 0; color: var(--primary-color); font-weight: 500;">Nº Registro: ' . $registration_number . '</p>' : ''}
            <p class="location"><i class="fas fa-map-marker-alt"></i> {$municipality}, {$province}</p>
            <div class="card-features">
                <span><i class="fas fa-users"></i> {$capacity} plazas</span>
                <span><i class="fas fa-home"></i> {$accommodation_type}</span>
                {$caracteristicasHTML}
            </div>
            <p class="price">Desde {$priceHTML} / noche</p>
            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 1rem;">
                {$botonesHTML}
            </div>
        </div>
    </div>
HTML;
}

// Obtener la conexión PDO
$pdo = getDBConnection(); // Asumo que esta función está en api/config.php

// Obtener los alojamientos iniciales
$initial_accommodations = get_initial_accommodations($pdo);

// Cerrar la conexión
$pdo = null;

?>
<!DOCTYPE html>
<html lang="es">
<head>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-MBP57VQM');</script>
<!-- End Google Tag Manager -->
<!-- Google tag (gtag.js) -->
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-MBP57VQM');</script>
<!-- End Google Tag Manager -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Descubre los mejores alojamientos turísticos rurales en España. Casas rurales, apartamentos y alojamientos con encanto para tu escapada perfecta." />

    <!-- X (Twitter) Card meta tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@rutas_rurales">
    <meta name="twitter:title" content="Alojamientos Turísticos en España | Reserva Casas Rurales con Encanto | Rutas">
    <meta name="twitter:description" content="Descubre los mejores alojamientos turísticos rurales en España. Casas rurales, apartamentos y alojamientos con encanto para tu escapada perfecta.">
    <meta name="twitter:image" content="https://rutasrurales.io/menu_images/Logo%20transparente.webp">

    <title>Alojamientos Turísticos en España | Reserva Casas Rurales con Encanto | Rutas</title>
    <!-- SEO TÉCNICO: URL Canónica Unificada -->
    <link rel="canonical" href="https://rutasrurales.io/alojamientos-turisticos">
    <link rel="icon" href="/menu_images/Favicon.png" type="image/png">
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="/css/alojamientos.css"> <!-- Añadido si existe un CSS específico -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* RESET GLOBAL */
        #navMenu a, #navMenu a:visited, #navMenu a:active {
            text-decoration: none !important;
            color: inherit !important;
            -webkit-tap-highlight-color: transparent;
        }

        /* --- DISEÑO PARA PC --- */
        @media (min-width: 993px) {
            .hamburger { display: none !important; }
            .nav-menu {
                display: flex !important;
                flex-direction: column;
                gap: 10px;
                align-items: center;
                font-family: 'Montserrat', sans-serif;
                margin-left: auto;
            }
            .nav-row {
                display: flex !important;
                list-style: none !important;
                margin: 0; padding: 0;
                width: 650px;
                justify-content: center;
            }
            .nav-row li { flex: 1; text-align: center; }
            .nav-row li a {
                font-size: 0.9rem;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                text-transform: capitalize;
                color: var(--accent-color) !important;
                font-weight: 600;
            }
            .nav-row li a span { color: var(--accent-color) !important; }
            .nav-row li a i { color: var(--accent-color) !important; font-size: 1.1rem; }
            .nav-row li a:hover, .nav-row li a:hover span, .nav-row li a:hover i { color: #ffffff !important; }
        }

        /* --- DISEÑO PARA MÓVIL --- */
        @media (max-width: 992px) {
            html, body { overflow-x: hidden !important; width: 100% !important; position: relative; }
            .header, .navbar { height: auto !important; padding: 2px 0 !important; position: fixed !important; top: 0 !important; width: 100% !important; z-index: 9999 !important; background-color: var(--primary-color) !important; }
            .navbar .container { flex-direction: row !important; justify-content: flex-start !important; align-items: center !important; gap: 5px !important; padding: 0 5px !important; display: flex !important; width: 100% !important; }
            .logo { flex-shrink: 0 !important; margin-right: 2px !important; display: block !important; }
            .logo img { height: 35px !important; width: auto !important; }
            .nav-menu { display: flex !important; position: static !important; width: auto !important; height: auto !important; background: transparent !important; flex-direction: column !important; flex: 1 !important; gap: 1px !important; padding: 0 !important; margin: 0 !important; box-shadow: none !important; }
            .nav-row { display: grid !important; grid-template-columns: repeat(4, 1fr) !important; gap: 2px !important; width: 100% !important; list-style: none !important; padding: 0 !important; margin: 0 !important; }
            .nav-row li { display: block !important; }
            .nav-row li a { background: rgba(255, 255, 255, 0.1) !important; min-height: 30px !important; padding: 1px !important; border-radius: 4px !important; display: flex !important; flex-direction: column !important; align-items: center !important; justify-content: center; }
            .nav-row li a span { font-size: 0.50rem !important; font-weight: 600 !important; line-height: 1 !important; text-align: center !important; white-space: nowrap !important; color: #d4a574 !important; margin: 0 !important; }
            .nav-row li a i { font-size: 0.8rem !important; margin-bottom: 0px !important; color: #ffffff !important; }
            input[type="text"], input[type="number"], input[type="search"], textarea, select { font-size: 16px !important; }
        }

        .asistente-avatar { width: 26px; height: 26px; border-radius: 50%; object-fit: cover; border: 1.5px solid #ffffff; vertical-align: middle; }
        @media (max-width: 992px) { .asistente-avatar { width: 18px !important; height: 18px !important; margin-bottom: 0px; } }

        .filters {
            background-color: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .filter-group {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-group label {
            font-weight: bold;
            color: var(--primary-color);
        }
        .filter-group select,
        .filter-group input {
            padding: 0.5rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-size: 1rem;
            outline: none;
        }
        .filter-group select:focus,
        .filter-group input:focus {
            border-color: var(--primary-color);
        }
        .stats {
            text-align: center;
            margin: 2rem 0;
            font-size: 1.2rem;
            color: var(--secondary-color);
        }
        .loading {
            text-align: center;
            padding: 3rem;
            font-size: 1.5rem;
            color: var(--primary-color);
        }

        /* Mobile optimizations */
        @media (max-width: 768px) {
            .filters {
                padding: 1rem;
            }
            .filter-group {
                flex-direction: column;
                align-items: stretch;
                gap: 0.5rem;
            }
            .filter-group select,
            .filter-group input {
                width: 100%;
                font-size: 1rem;
            }
            .photo-carousel {
                height: 200px !important;
            }
            .card-content {
                padding: 1rem !important;
            }
            .card-content h3 {
                font-size: 1.1rem !important;
            }
        }

        /* Lazy loading for images */
        .lazy {
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .lazy.loaded {
            opacity: 1;
        }

        /* Optimize carousel for mobile */
        @media (max-width: 768px) {
            .carousel-prev,
            .carousel-next {
                width: 25px !important;
                height: 25px !important;
            }
            .carousel-indicators {
                bottom: 5px !important;
            }
        }
    </style>
</head>
<body>
    <!-- Header completo como en el menú de inicio -->
    <header class="header">
        <nav class="navbar">
            <div class="container">
                <div class="logo">
                    <a href="/index.html"><img src="/menu_images/Logo%20transparente.webp" alt="Rutas Logo"></a>
                </div>

                <div class="nav-menu" id="navMenu">
                    <ul class="nav-row">
                        <li><a href="/alojamientos-turisticos">
                            <i class="fas fa-bed"></i>
                            <span>Alojamientos</span>
                        </a></li>
                        <li><a href="/lugares-de-interes">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Lugares</span>
                        </a></li>
                        <li><a href="/eventos-culturales-paginacion.html">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Eventos</span>
                        </a></li>
                        <li><a href="/actividades-turisticas">
                            <i class="fas fa-hiking"></i>
                            <span>Actividades</span>
                        </a></li>
                    </ul>

                    <ul class="nav-row">
                        <li><a href="/index.html">
                            <i class="fas fa-home"></i>
                            <span>Inicio</span>
                        </a></li>
                        <li><a href="/rutas.php">
                            <i class="fas fa-route"></i>
                            <span>Rutas</span>
                        </a></li>
                        <li><a href="/login.html">
                            <i class="fas fa-user-circle"></i>
                            <span>Acceso</span>
                        </a></li>
                        <li><a href="/index.html#asistente">
                            <img src="/antonio.jpg" alt="Antonio" class="asistente-avatar">
                            <span>Antonio</span>
                        </a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Sección de Alojamientos Turísticos -->
    <section class="section" style="margin-top: 80px;">
        <div class="container">
            <h1 class="section-title">
                <i class="fas fa-hotel"></i> Alojamientos Turísticos
            </h1>

            <!-- Filtros -->
            <div class="filters">
                <div class="filter-group">
                    <label for="filterProvincia">
                        <i class="fas fa-map"></i> Provincia:
                    </label>
                    <select id="filterProvincia">
                        <option value="Soria" selected>Soria</option>
                        <option value="Burgos">Burgos</option>
                        <option value="Segovia">Segovia</option>
                        <option value="Ávila">Ávila</option>
                        <option value="Valladolid">Valladolid</option>
                        <option value="Palencia">Palencia</option>
                        <option value="León">León</option>
                        <option value="Zamora">Zamora</option>
                        <option value="Salamanca">Salamanca</option>
                    </select>

                    <label for="filterLocalidad">
                        <i class="fas fa-map-marker-alt"></i> Localidad:
                    </label>
                    <select id="filterLocalidad">
                        <option value="">Todas</option>
                    </select>

                    <label for="filterTipo">
                        <i class="fas fa-building"></i> Tipo:
                    </label>
                    <select id="filterTipo">
                        <option value="">Todos</option>
                        <option value="Casa">Casa</option>
                        <option value="Piso">Piso</option>
                        <option value="Chalé">Chalé</option>
                        <option value="Hotel">Hotel</option>
                        <option value="Apartamento">Apartamento</option>
                    </select>

                    <label for="filterPlazas">
                        <i class="fas fa-users"></i> Plazas mínimas:
                    </label>
                    <input type="number" id="filterPlazas" min="1" max="20" value="1">
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="stats">
                <span id="statsText">Mostrando <?= count($initial_accommodations) ?> alojamientos...</span>
            </div>

            <!-- Loading -->
            <div id="loading" class="loading" style="display: none;">
                <i class="fas fa-spinner fa-spin"></i> Cargando alojamientos turísticos...
            </div>

            <!-- Grid de Alojamientos Turísticos -->
            <div id="alojamientosGrid" class="grid">
                <?php
                if (!empty($initial_accommodations)) {
                    foreach ($initial_accommodations as $alojamiento) {
                        echo render_accommodation_card($alojamiento);
                    }
                } else {
                    echo '<p>No se encontraron alojamientos en este momento. Inténtalo de nuevo más tarde.</p>';
                }
                ?>
            </div>

            <!-- Sin resultados -->
            <div id="noResults" style="display: none; text-align: center; padding: 3rem;">
                <i class="fas fa-search" style="font-size: 3rem; color: var(--secondary-color);"></i>
                <h3 style="color: var(--primary-color); margin-top: 1rem;">No se encontraron alojamientos turísticos</h3>
                <p>Intenta cambiar los filtros de búsqueda</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content-simple">
                <div class="footer-info">
                    <span><i class="fas fa-envelope"></i> olgamarin@rutasrurales.io</span>
                    <span><i class="fas fa-phone"></i> +34 605 249 696</span>
                </div>
                <div class="footer-links">
                    <a href="/aviso-legal.html">Aviso Legal</a>
                    <a href="/politica-cookies.html">Política de Cookies</a>
                    <a href="/agradecimientos.html">Agradecimientos</a>
                </div>
                <div class="footer-social">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                    <a href="https://www.instagram.com/rutas_rurales/" target="_blank" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
            <div class="footer-copyright">
                <p>&copy; 2025 rutasrurales.io. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- Script principal de la web (si existe) -->
    <script src="/script.js"></script>

    <!-- 
      Inyectamos los datos iniciales como un objeto JSON en el HTML.
      El script JS podrá leer esto en lugar de hacer una petición inicial.
    -->
    <script>
        window.INITIAL_ACCOMMODATIONS = <?= json_encode($initial_accommodations, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    </script>

    <!-- Tu script JS de alojamientos -->
    <script src="/js/alojamientos.js"></script>

</body>
</html>