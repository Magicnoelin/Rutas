<?php
/**
 * Gestor de Redirecciones Universal (Lugares, Actividades, Alojamientos)
 * Maneja redirecciones 301 y sirve el contenido HTML correspondiente.
 */

// 1. SEGURIDAD: Evitar que errores de PHP rompan el diseño HTML
error_reporting(0);
ini_set('display_errors', 0);

// Obtener parámetros
$slug = $_GET['slug'] ?? '';
$type = $_GET['type'] ?? 'lugar'; // Por defecto 'lugar'
$tryOriginal = $_GET['try_original'] ?? false; // Si true, intentará buscar sin -2

// Redirección 301 específica para 'la-cabana-del-risco'
if ($type === 'alojamiento' && $slug === 'la-cabana-del-risco') {
    header("Location: /alojamientos/sierra-de-gredos", true, 301);
    exit;
}

// 2. Definir redirecciones por tipo (Slug Antiguo => Slug Nuevo)
$redirects = [
    'lugar' => [
        'restaurante-santo-domingo-ii' => 'restaurante-santo-domingo-2-soria',
        'ermita-de-san-saturio'        => 'ermita-de-san-saturio-soria',
        'la-perdiz'                    => 'la-perdiz-brugo-de-osma',
        'asador-el-burgo'              => 'asador-el-burgo-de-osma',
        'asador-el-burgo-de osma'      => 'asador-el-burgo-de-osma',
        'pico-urbion'                  => 'pico-urbion-duruelo-de-la-sierra',
    ],
    'actividad' => [
        // Ejemplo: 'ruta-caballo-vieja' => 'ruta-caballo-soria',
    ],
    'alojamiento' => [
        // Ejemplo: 'casa-pepe' => 'casa-rural-pepe',
        'ca-ada-real'          => 'canada-real',
        'alquer-a-de-segovia'  => 'alqueria-de-segovia',
        'alojamiento-la-plaza-apartamento-turistico-en-vinuesa' => 'la-plaza-vinuesa',
    ]
];

// Array para slugs que deben devolver 410 Gone
$gone = [
    'alojamiento' => [
        'entrepinos',
        'casa-olvido',
    ]
];

// 3. Definir qué archivo HTML carga cada tipo
$templates = [
    'lugar'       => 'lugar-interes.html',
    'actividad'   => 'actividad.html',
    'alojamiento' => 'detalle.html'
];

// 3. Verificar si el slug debe devolver un 410 Gone
if (isset($gone[$type]) && in_array($slug, $gone[$type])) {
    header("HTTP/1.0 410 Gone");
    // Mostrar una página 410 amigable
    echo <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alojamiento no disponible - Rutas Rurales</title>
    <link rel="icon" href="/menu_images/Favicon.png" type="image/png">
    <style>
        :root { --primary-color: #2F5233; }
        body { text-align: center; padding: 50px; font-family: 'Poppins', sans-serif, system-ui; color: #333; background-color: #f9f9f9; margin: 0; }
        .container { max-width: 600px; margin: 0 auto; }
        h1 { font-size: 2.5rem; color: var(--primary-color); margin-bottom: 1rem; }
        p { font-size: 1.1rem; color: #666; line-height: 1.6; }
        .boton { 
            display: inline-block; padding: 12px 25px; margin-top: 20px;
            background: var(--primary-color); color: white; 
            text-decoration: none; border-radius: 25px; font-weight: 600;
            transition: background-color 0.3s, transform 0.3s;
        }
        .boton:hover { background-color: #1a3a1c; transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="container">
        <h1>Este alojamiento ya no está disponible</h1>
        <p>Lo sentimos, esta propiedad ha sido retirada de nuestra plataforma de forma permanente. Pero tu próximo viaje no tiene por qué detenerse aquí; tenemos un lugar esperándote que te gustará aún más.</p>
        <a href="/alojamientos-turisticos.html" class="boton">Ver otros alojamientos</a>
    </div>
</body>
</html>
HTML;
    exit;
}

// 4. Verificar si existe una redirección para este tipo y slug
if (isset($redirects[$type]) && array_key_exists($slug, $redirects[$type])) {
    $newSlug = $redirects[$type][$slug];
    
    // Determinar la base de la URL para la redirección
    $basePath = match ($type) {
        'actividad'   => '/actividad/',
        'alojamiento' => '/alojamiento/',
        default       => '/lugar/',
    };

    // Realizar redirección 301 permanente (SEO friendly)
    header("Location: " . $basePath . $newSlug, true, 301);
    exit;
}

// 5. Si no hay redirección, servir la plantilla correspondiente
$templateFile = $templates[$type] ?? 'lugar-interes.html';

// Definir basePath para uso en canonical (antes de cualquier redirección)
$basePath = match ($type) {
    'actividad'   => '/actividad/',
    'alojamiento' => '/alojamiento/',
    default       => '/lugar/',
};

if (file_exists($templateFile)) {
    // Asegurar que se sirve como HTML
    header('Content-Type: text/html; charset=utf-8');
    
    // Cargar el contenido del archivo HTML en memoria
    $html = file_get_contents($templateFile);

    // Inyectar clase al body para estilos específicos (ej: page-actividad)
    $safeType = htmlspecialchars($type, ENT_QUOTES, 'UTF-8');
    $html = str_replace('<body>', '<body class="page-' . $safeType . '">', $html);

    // INYECTAR SLUG COMO VARIABLE GLOBAL PARA USO DEL JS
    if ($type === 'alojamiento' || $type === 'lugar' || $type === 'actividad') {
        $slugInject = "<script>window.currentSlug = '" . addslashes($slug) . "';</script>";
        $html = str_ireplace('</head>', $slugInject . "\n</head>", $html);
    }

    // INYECTAR HEADER Y FOOTER PARA TODAS LAS PÁGINAS (LUGAR, ALOJAMIENTO, ACTIVIDAD)
    if ($type === 'lugar' || $type === 'alojamiento' || $type === 'actividad') {
        // Obtener contenido del header
        ob_start();
        include 'header.php';
        $headerContent = ob_get_clean();
        
        // Obtener contenido del footer
        ob_start();
        include 'footer.php';
        $footerContent = ob_get_clean();
        
        // LIMPIEZA: Eliminar etiquetas de cierre propias de la plantilla para evitar duplicados
        // ya que $footerContent (proveniente de footer.php) ya incluye </body></html>
        $html = preg_replace('/<\/body>\s*<\/html>\s*$/is', '', trim($html));
        
        if ($type === 'actividad') {
            // Para actividad, reemplazar marcadores existentes
            $html = str_replace('<!-- HEADER_PLACEHOLDER -->', $headerContent, $html);
            $html = str_replace('<!-- FOOTER_PLACEHOLDER -->', $footerContent, $html);
        } else {
            // Para lugar y alojamiento, reemplazar el header y footer existentes
            // Eliminar el header existente (desde <header class="header"> hasta el siguiente </header>)
            // Usamos un patrón más robusto que maneja contenido anidado
            $headerPattern = '/<header\b[^>]*\bclass\s*=\s*["\']header["\'][^>]*>.*?<\/header>/is';
            $html = preg_replace($headerPattern, $headerContent, $html, 1);
            
            // Eliminar el footer existente (desde <footer class="footer"> hasta el siguiente </footer>)
            $footerPattern = '/<footer\b[^>]*\bclass\s*=\s*["\']footer["\'][^>]*>.*?<\/footer>/is';
            $html = preg_replace($footerPattern, $footerContent, $html, 1);
            
            // Si no se reemplazó (por ejemplo, si el patrón no coincide), intentar con un patrón más simple
            if (strpos($html, $headerContent) === false) {
                // Intentar con cualquier header que tenga class que contenga "header"
                $headerPattern2 = '/<header\b[^>]*\bclass\s*=\s*["\'][^"\']*header[^"\']*["\'][^>]*>.*?<\/header>/is';
                $html = preg_replace($headerPattern2, $headerContent, $html, 1);
            }
            
            if (strpos($html, $footerContent) === false) {
                // Intentar con cualquier footer que tenga class que contenga "footer"
                $footerPattern2 = '/<footer\b[^>]*\bclass\s*=\s*["\'][^"\']*footer[^"\']*["\'][^>]*>.*?<\/footer>/is';
                $html = preg_replace($footerPattern2, $footerContent, $html, 1);
            }
        }
    }

    // 6. INYECCIÓN SEO: Cambiar título dinámicamente
    if (!empty($slug)) {
        // Usamos buffering para capturar cualquier error invisible y evitar que rompa el HTML
        ob_start();
        try {
            // Credenciales directas para máxima seguridad y aislamiento
            $dbHost = 'localhost';
            $dbName = 'u412199647_Rutas';
            $dbUser = 'u412199647_olgamarin';
            $dbPass = 'Rutas5Rurales7$';
            
            $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
            // Reducir posibles bloqueos/timeout en la conexión SEO
            $pdoOptions = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
                PDO::ATTR_TIMEOUT => 2
            ];
            // Modo silencioso: si falla, no dice nada, solo carga la web normal
            $pdo = new PDO($dsn, $dbUser, $dbPass, $pdoOptions);
            
            if ($pdo) {
                $tituloSEO = '';
                $descSEO = '';

                // Buscar datos según el tipo
                if ($type === 'alojamiento') {
                    $stmt = $pdo->prepare("SELECT * FROM accommodations WHERE slug = :slug LIMIT 1");
                    $stmt->execute([':slug' => $slug]);
                    $data = $stmt->fetch(PDO::FETCH_ASSOC);
                } elseif ($type === 'lugar') {
                    $stmt = $pdo->prepare("SELECT * FROM places_of_interest WHERE slug = :slug LIMIT 1");
                    $stmt->execute([':slug' => $slug]);
                    $data = $stmt->fetch(PDO::FETCH_ASSOC);
                } elseif ($type === 'actividad') {
                    $stmt = $pdo->prepare("SELECT * FROM tourist_activities WHERE slug = :slug LIMIT 1");
                    $stmt->execute([':slug' => $slug]);
                    $data = $stmt->fetch(PDO::FETCH_ASSOC);
                }

                // Si no se encuentra el slug y try_original está activo, intentar sin -2
                if (!$data && $tryOriginal && preg_match('/^(.+)-2$/', $slug, $matches)) {
                    $originalSlug = $matches[1];
                    
                    // Buscar con el slug original (sin -2)
                    if ($type === 'alojamiento') {
                        $stmt = $pdo->prepare("SELECT * FROM accommodations WHERE slug = :slug LIMIT 1");
                        $stmt->execute([':slug' => $originalSlug]);
                        $data = $stmt->fetch(PDO::FETCH_ASSOC);
                    } elseif ($type === 'lugar') {
                        $stmt = $pdo->prepare("SELECT * FROM places_of_interest WHERE slug = :slug LIMIT 1");
                        $stmt->execute([':slug' => $originalSlug]);
                        $data = $stmt->fetch(PDO::FETCH_ASSOC);
                    } elseif ($type === 'actividad') {
                        $stmt = $pdo->prepare("SELECT * FROM tourist_activities WHERE slug = :slug LIMIT 1");
                        $stmt->execute([':slug' => $originalSlug]);
                        $data = $stmt->fetch(PDO::FETCH_ASSOC);
                    }
                    
                    // Si se encontró, hacer redirect 301 al slug correcto
                    if ($data) {
                        $correctSlug = $originalSlug;
                        $canonicalURL = 'https://rutasrurales.io' . rtrim($basePath, '/') . '/' . $correctSlug;
                        header("Location: " . $canonicalURL, true, 301);
                        exit;
                    }
                }

                if ($data) {
                    $tituloSEO = $data['name'];
                    // Usar meta_description si existe, de lo contrario usar description
                    $descSEO = $data['meta_description'] ?? $data['description'] ?? '';

                    // Reemplazar el <title> en el HTML
                    if ($tituloSEO) {
                        $tituloCompleto = htmlspecialchars($tituloSEO) . ' | Rutas Rurales';
                        $html = preg_replace('/<title>(.*?)<\/title>/i', "<title>$tituloCompleto</title>", $html);
                    }

                    // Inyectar URL Canónica
                    $canonicalURL = 'https://rutasrurales.io' . rtrim($basePath, '/') . '/' . $slug;
                    // Buscar la etiqueta canonical con cualquier formato (con o sin href, con id, etc.)
                    // Primero intentar con regex más flexible que capture cualquier variant
                    $canonicalPattern = '/<link\s+[^>]*rel\s*=\s*["\']canonical["\'][^>]*>/i';
                    if (preg_match($canonicalPattern, $html)) {
                        // Reemplazar la etiqueta canónica existente con href completo
                        $html = preg_replace($canonicalPattern, '<link rel="canonical" href="' . $canonicalURL . '">', $html);
                    } else {
                        // O inyectarla si no existe
                        $html = str_ireplace('</head>', "    <link rel=\"canonical\" href=\"$canonicalURL\">\n</head>", $html);
                    }

                    // Inyectar Meta Description si existe
                    if ($descSEO) {
                        $descLimpia = htmlspecialchars(mb_substr(strip_tags($descSEO), 0, 155) . '...');
                        if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\'].*?["\']\s*\/?>/i', $html)) {
                            $html = preg_replace('/<meta\s+name=["\']description["\']\s+content=["\'].*?["\']\s*\/?>/i', '<meta name="description" content="' . $descLimpia . '">', $html);
                        } else {
                            $html = str_ireplace('<head>', "<head>\n    <meta name=\"description\" content=\"$descLimpia\">", $html);
                        }
                    }

                    // Generar Schema.org JSON-LD (Datos Estructurados)
                    $schemaJson = '';
                    $nearbySchemaJson = '';
                    $activitiesSchemaJson = '';
                    $eventsSchemaJson = '';
                    
                    if ($type === 'alojamiento') {
                        // Determinar URL de imagen
                        $imageUrl = $data['photo1'] ?? $data['image1'] ?? '';
                        if ($imageUrl && !preg_match('/^https?:\/\//', $imageUrl)) {
                            $imageUrl = 'https://rutasrurales.io' . (strpos($imageUrl, '/') === 0 ? '' : '/') . $imageUrl;
                        }
                        
                        $schema = [
                            '@context' => 'https://schema.org',
                            '@type' => 'LodgingBusiness',
                            'name' => $data['name'] ?? '',
                            'description' => strip_tags($data['description'] ?? ''),
                            'url' => $canonicalURL,
                            'address' => [
                                '@type' => 'PostalAddress',
                                'streetAddress' => $data['address'] ?? '',
                                'addressLocality' => $data['municipality'] ?? '',
                                'addressRegion' => $data['province'] ?? '',
                                'postalCode' => $data['postal_code'] ?? '',
                                'addressCountry' => 'ES'
                            ]
                        ];

                        if (!empty($data['latitude']) && !empty($data['longitude'])) {
                            $schema['geo'] = [
                                '@type' => 'GeoCoordinates',
                                'latitude' => $data['latitude'],
                                'longitude' => $data['longitude']
                            ];
                        }
                        
                        if ($imageUrl) {
                            $schema['image'] = $imageUrl;
                        }
                        
                        // Precio (intentar varios campos posibles)
                        $price = $data['price'] ?? $data['price_per_night'] ?? $data['Precio'] ?? null;
                        if (!empty($price)) {
                            $schema['priceRange'] = $price . ' EUR';
                        }
                        
                        if (!empty($data['phone'])) {
                            $schema['telephone'] = $data['phone'];
                        }
                        
                        $schemaJson = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                        // Buscar lugares de interés cercanos (municipio o provincia) para Schema
                        if (!empty($data['municipality']) || !empty($data['province'])) {
                            try {
                                $stmtPlaces = $pdo->prepare("SELECT name, slug FROM places_of_interest WHERE (municipality = :municipality OR province = :province) AND is_active = 1 ORDER BY CASE WHEN municipality = :municipality THEN 0 ELSE 1 END LIMIT 5");
                                $stmtPlaces->execute([':municipality' => $data['municipality'], ':province' => $data['province']]);
                                $places = $stmtPlaces->fetchAll(PDO::FETCH_ASSOC);

                                if ($places) {
                                    $itemList = [
                                        '@context' => 'https://schema.org',
                                        '@type' => 'ItemList',
                                        'name' => 'Lugares de interés cercanos',
                                        'itemListElement' => []
                                    ];

                                    foreach ($places as $idx => $place) {
                                        $pUrl = 'https://rutasrurales.io/lugar/' . $place['slug'];
                                        $itemList['itemListElement'][] = [
                                            '@type' => 'ListItem',
                                            'position' => $idx + 1,
                                            'item' => [
                                                '@type' => 'TouristAttraction',
                                                'name' => $place['name'],
                                                'url' => $pUrl
                                            ]
                                        ];
                                    }
                                    $nearbySchemaJson = json_encode($itemList, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                                }
                            } catch (Exception $e) {
                                // Ignorar errores en lugares cercanos para no romper la carga principal
                            }
                        }

                        // Buscar actividades turísticas cercanas (municipio o provincia)
                        if (!empty($data['municipality']) || !empty($data['province'])) {
                            try {
                                $stmtActivities = $pdo->prepare("SELECT name, slug FROM tourist_activities WHERE (municipality = :municipality OR province = :province) AND is_active = 1 ORDER BY CASE WHEN municipality = :municipality THEN 0 ELSE 1 END LIMIT 5");
                                $stmtActivities->execute([':municipality' => $data['municipality'], ':province' => $data['province']]);
                                $activities = $stmtActivities->fetchAll(PDO::FETCH_ASSOC);

                                if ($activities) {
                                    $itemList = [
                                        '@context' => 'https://schema.org',
                                        '@type' => 'ItemList',
                                        'name' => 'Actividades turísticas cercanas',
                                        'itemListElement' => []
                                    ];

                                    foreach ($activities as $idx => $act) {
                                        $aUrl = 'https://rutasrurales.io/actividad/' . $act['slug'];
                                        $itemList['itemListElement'][] = [
                                            '@type' => 'ListItem',
                                            'position' => $idx + 1,
                                            'item' => [
                                                '@type' => 'TouristAttraction',
                                                'name' => $act['name'],
                                                'url' => $aUrl
                                            ]
                                        ];
                                    }
                                    $activitiesSchemaJson = json_encode($itemList, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                                }
                            } catch (Exception $e) { }
                        }

                        // Buscar eventos culturales cercanos (municipio o provincia)
                        if (!empty($data['municipality']) || !empty($data['province'])) {
                            try {
                                $today = date('Y-m-d');
                                $stmtEvents = $pdo->prepare("SELECT * FROM cultural_events WHERE (municipality = :municipality OR province = :province) AND is_active = 1 AND COALESCE(end_date, DATE_ADD(start_date, INTERVAL 1 DAY)) >= :today ORDER BY start_date ASC, CASE WHEN municipality = :municipality THEN 0 ELSE 1 END LIMIT 5");
                                $stmtEvents->execute([':municipality' => $data['municipality'], ':province' => $data['province'], ':today' => $today]);
                                $events = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);

                                if ($events) {
                                    $itemList = [
                                        '@context' => 'https://schema.org',
                                        '@type' => 'ItemList',
                                        'name' => 'Eventos culturales próximos',
                                        'itemListElement' => []
                                    ];

                                    foreach ($events as $idx => $evt) {
                                        $eUrl = 'https://rutasrurales.io/evento/' . $evt['slug'];
                                        
                                        // Construir objeto Event completo para evitar errores de validación
                                        $eventSchema = [
                                            '@type' => 'Event',
                                            'name' => $evt['name'] ?? $evt['title'] ?? 'Evento Cultural',
                                            'url' => $eUrl,
                                            'startDate' => $evt['start_date'],
                                            'eventStatus' => 'https://schema.org/EventScheduled',
                                            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
                                            'description' => strip_tags($evt['description'] ?? $evt['short_description'] ?? 'Evento cultural en ' . ($evt['municipality'] ?? 'Soria')),
                                            'location' => [
                                                '@type' => 'Place',
                                                'name' => $evt['venue_name'] ?? $evt['venue'] ?? $evt['municipality'] ?? 'Soria',
                                                'address' => [
                                                    '@type' => 'PostalAddress',
                                                    'streetAddress' => $evt['venue_address'] ?? $evt['address'] ?? '',
                                                    'addressLocality' => $evt['municipality'] ?? '',
                                                    'addressRegion' => $evt['province'] ?? '',
                                                    'addressCountry' => 'ES'
                                                ]
                                            ],
                                            'organizer' => [
                                                '@type' => 'Organization',
                                                'name' => $evt['organizer'] ?? 'Organizador del evento'
                                            ],
                                            'offers' => [
                                                '@type' => 'Offer',
                                                'price' => $evt['price'] ?? $evt['ticket_price'] ?? '0',
                                                'priceCurrency' => 'EUR',
                                                'availability' => 'https://schema.org/InStock',
                                                'url' => $eUrl
                                            ]
                                        ];

                                        // Imagen (opcional pero recomendada)
                                        $eImg = $evt['poster_image'] ?? $evt['photo1'] ?? '';
                                        if (empty($eImg) && !empty($evt['slug'])) {
                                            $eImg = '/cultural_events_images/' . $evt['slug'] . '.webp';
                                        }
                                        
                                        if ($eImg) {
                                            if (!preg_match('/^https?:\/\//', $eImg)) {
                                                $eImg = 'https://rutasrurales.io' . (strpos($eImg, '/') === 0 ? '' : '/') . $eImg;
                                            }
                                            $eventSchema['image'] = $eImg;
                                        }

                                        // Fecha fin (opcional, fallback a start_date)
                                        if (!empty($evt['end_date'])) {
                                            $eventSchema['endDate'] = $evt['end_date'];
                                        } else {
                                            $eventSchema['endDate'] = $evt['start_date'];
                                        }

                                        $itemList['itemListElement'][] = [
                                            '@type' => 'ListItem',
                                            'position' => $idx + 1,
                                            'item' => $eventSchema
                                        ];
                                    }
                                    $eventsSchemaJson = json_encode($itemList, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                                }
                            } catch (Exception $e) { }
                        }
                    } elseif ($type === 'lugar') {
                        // Schema básico para lugares
                        // Determinar URL de imagen para lugar
                        $imageUrl = $data['photo1'] ?? $data['image1'] ?? '';
                        if ($imageUrl && !preg_match('/^https?:\/\//', $imageUrl)) {
                            $imageUrl = 'https://rutasrurales.io' . (strpos($imageUrl, '/') === 0 ? '' : '/') . $imageUrl;
                        }

                        $schema = [
                            '@context' => 'https://schema.org',
                            '@type' => 'TouristAttraction',
                            'name' => $data['name'] ?? '',
                            'description' => strip_tags($data['description'] ?? ''),
                            'url' => $canonicalURL,
                            'address' => [
                                '@type' => 'PostalAddress',
                                'streetAddress' => $data['address'] ?? '',
                                'addressLocality' => $data['municipality'] ?? '',
                                'addressRegion' => $data['province'] ?? '',
                                'postalCode' => $data['postal_code'] ?? '',
                                'addressCountry' => 'ES'
                            ]
                        ];

                        if (!empty($data['latitude']) && !empty($data['longitude'])) {
                            $schema['geo'] = [
                                '@type' => 'GeoCoordinates',
                                'latitude' => $data['latitude'],
                                'longitude' => $data['longitude']
                            ];
                        }

                        if ($imageUrl) {
                            $schema['image'] = $imageUrl;
                        }
                        $schemaJson = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    } elseif ($type === 'actividad') {
                        // Determinar URL de imagen para actividad
                        $imageUrl = $data['photo1'] ?? $data['image1'] ?? '';
                        if ($imageUrl && !preg_match('/^https?:\/\//', $imageUrl)) {
                            $imageUrl = 'https://rutasrurales.io' . (strpos($imageUrl, '/') === 0 ? '' : '/') . $imageUrl;
                        }

                        $schema = [
                            '@context' => 'https://schema.org',
                            '@type' => 'TouristAttraction',
                            'name' => $data['name'] ?? '',
                            'description' => strip_tags($data['description'] ?? ''),
                            'url' => $canonicalURL,
                            'address' => [
                                '@type' => 'PostalAddress',
                                'streetAddress' => $data['meeting_point'] ?? '',
                                'addressLocality' => $data['municipality'] ?? '',
                                'addressRegion' => $data['province'] ?? '',
                                'addressCountry' => 'ES'
                            ]
                        ];

                        if (!empty($data['latitude']) && !empty($data['longitude'])) {
                            $schema['geo'] = [
                                '@type' => 'GeoCoordinates',
                                'latitude' => $data['latitude'],
                                'longitude' => $data['longitude']
                            ];
                        }

                        if ($imageUrl) {
                            $schema['image'] = $imageUrl;
                        }
                        $price = $data['price'] ?? $data['price_adult'] ?? null;
                        if (!empty($price)) {
                            $schema['priceRange'] = $price . ' EUR';
                        }
                        $schemaJson = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    }

                    // Inyectar Schema en el HTML
                    if ($schemaJson) {
                        $scripts = "\n    <script type=\"application/ld+json\">\n    $schemaJson\n    </script>";
                        if ($nearbySchemaJson) {
                            $scripts .= "\n    <script type=\"application/ld+json\">\n    $nearbySchemaJson\n    </script>";
                        }
                        if ($activitiesSchemaJson) {
                            $scripts .= "\n    <script type=\"application/ld+json\">\n    $activitiesSchemaJson\n    </script>";
                        }
                        if ($eventsSchemaJson) {
                            $scripts .= "\n    <script type=\"application/ld+json\">\n    $eventsSchemaJson\n    </script>";
                        }
                        $html = str_ireplace('</head>', "$scripts\n</head>", $html);
                    }
                }
            }
        } catch (Exception $e) {
            // Si falla algo, no hacemos nada y se muestra la web original
        }
        // Limpiar cualquier "basura" que haya podido generar la conexión
        ob_end_clean();
    }

    // Imprimir el HTML final (modificado o no)
    echo $html;
} else {
    // Fallback por si acaso
    header("HTTP/1.0 404 Not Found");
    echo "<h1>Error 404</h1><p>La página solicitada no se encuentra.</p>";
}
