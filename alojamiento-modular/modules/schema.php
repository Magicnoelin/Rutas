<?php
/**
 * Schema.org JSON-LD — Ficha de Alojamiento
 * ============================================
 * Genera marcado estructurado multi-graph:
 *   1. WebSite   (sitelinks searchbox)
 *   2. WebPage   (con primaryImageOfPage + breadcrumb)
 *   3. BreadcrumbList
 *   4. LodgingBusiness / Hotel / Hostel / BedAndBreakfast / VacationRental
 *   5. FAQPage   (preguntas frecuentes dinámicas)
 *
 * Uso:
 *   require_once __DIR__ . '/modules/schema.php';
 *   renderAlojamientoSchema($alojamiento, $fotos, $canonical, $page_title, $page_desc, $lang);
 *
 * @param array  $alojamiento  Fila completa de la BD
 * @param array  $fotos        URLs absolutas de las fotos
 * @param string $canonical    URL canónica de la página
 * @param string $page_title   Título SEO
 * @param string $page_desc    Descripción SEO
 * @param string $lang         Código de idioma (es|en|fr|de|zh)
 */

function renderAlojamientoSchema(
    array  $alojamiento,
    array  $fotos,
    string $canonical,
    string $page_title,
    string $page_desc,
    string $lang = 'es'
): void {

    $baseUrl = 'https://rutasrurales.io';

    // ── 1. Tipo Schema.org más específico según categoría ─────────────────────
    $cat_raw   = strtolower($alojamiento['category_name'] ?? $alojamiento['accommodation_type'] ?? '');
    $typeMap   = [
        // Hoteles
        'hotel rural'         => 'Hotel',
        'hotel'               => 'Hotel',
        // Hostales y albergues
        'hostal'              => 'Hostel',
        'hostel'              => 'Hostel',
        'albergue'            => 'Hostel',
        // B&B / Casas de huéspedes
        'bed and breakfast'   => 'BedAndBreakfast',
        'b&b'                 => 'BedAndBreakfast',
        'casa de huéspedes'   => 'BedAndBreakfast',
        'posada'              => 'BedAndBreakfast',
        // Alquiler vacacional completo (lo más frecuente en turismo rural español)
        'casa rural'          => 'VacationRental',
        'casas rurales'       => 'VacationRental',
        'apartamento rural'   => 'VacationRental',
        'apartamento turístico'=> 'VacationRental',
        'apartamento'         => 'VacationRental',
        'villa'               => 'VacationRental',
        'cabaña'              => 'VacationRental',
        'casa de campo'       => 'VacationRental',
        'chalet'              => 'VacationRental',
        'masía'               => 'VacationRental',
        'cortijo'             => 'VacationRental',
        'finca rural'         => 'VacationRental',
        'alojamiento rural'   => 'VacationRental',
        'turismo rural'       => 'VacationRental',
        // Glamping
        'glamping'            => 'Campground',
        'camping'             => 'Campground',
    ];
    $schemaType = 'VacationRental'; // fallback más apropiado para turismo rural
    foreach ($typeMap as $kw => $st) {
        if (str_contains($cat_raw, $kw)) { $schemaType = $st; break; }
    }

    // ── 2. ImageObject array ──────────────────────────────────────────────────
    $imageObjects = [];
    foreach ($fotos as $idx => $fotoUrl) {
        $fullUrl        = str_starts_with($fotoUrl, 'http') ? $fotoUrl : $baseUrl . $fotoUrl;
        $imageObjects[] = [
            '@type'       => 'ImageObject',
            '@id'         => $canonical . '#photo' . ($idx + 1),
            'url'         => $fullUrl,
            'contentUrl'  => $fullUrl,
            'name'        => htmlspecialchars_decode($alojamiento['name']) . ' — foto ' . ($idx + 1),
            'description' => htmlspecialchars_decode($alojamiento['name']) . ' en ' . ($alojamiento['municipality'] ?? 'Soria'),
            'inLanguage'  => $lang === 'es' ? 'es-ES' : strtolower($lang) . '-' . strtoupper($lang),
        ];
    }

    // ── 3. AmenityFeature ─────────────────────────────────────────────────────
    $amenityFeatures = [];
    $addFeature      = function (string $name, bool $val = true) use (&$amenityFeatures): void {
        $amenityFeatures[] = ['@type' => 'LocationFeatureSpecification', 'name' => $name, 'value' => $val];
    };

    // Amenidades desde JSON (campo genérico)
    if (!empty($alojamiento['amenities'])) {
        $ams = json_decode($alojamiento['amenities'], true);
        if (is_array($ams)) {
            foreach ($ams as $am) { $addFeature((string)$am); }
        }
    }
    // Campos booleanos específicos
    $boolFeatures = [
        'pet_friendly'          => 'Admite mascotas',
        'kitchen_available'     => 'Cocina disponible',
        'suitable_for_children' => 'Apto para niños',
        'wifi'                  => 'WiFi gratuito',
        'parking'               => 'Aparcamiento gratuito',
        'swimming_pool'         => 'Piscina',
        'barbecue'              => 'Barbacoa',
        'air_conditioning'      => 'Aire acondicionado',
        'heating'               => 'Calefacción',
        'washing_machine'       => 'Lavadora',
        'dishwasher'            => 'Lavavajillas',
        'tv'                    => 'Televisión',
        'terrace'               => 'Terraza',
        'garden'                => 'Jardín',
        'fireplace'             => 'Chimenea',
        'accessible'            => 'Accesible para personas con movilidad reducida',
    ];
    foreach ($boolFeatures as $field => $label) {
        if (!empty($alojamiento[$field]) && (int)$alojamiento[$field] === 1) {
            $addFeature($label);
        }
    }

    // ── 4. Check-in / Check-out ───────────────────────────────────────────────
    // schema.org espera formato "HH:MM" simple (sin prefijo T)
    $ci = $alojamiento['check_in_time']  ?? '15:00';
    $co = $alojamiento['check_out_time'] ?? '11:00';
    $checkinTime  = substr($ci, 0, 5);   // "15:00"
    $checkoutTime = substr($co, 0, 5);   // "11:00"

    // ── 5. LodgingBusiness / VacationRental ───────────────────────────────────
    $descRaw   = strip_tags($alojamiento['description'] ?? '');
    $descShort = mb_substr($descRaw, 0, 500);

    $address = [
        '@type'           => 'PostalAddress',
        'streetAddress'   => $alojamiento['address']     ?? '',
        'addressLocality' => $alojamiento['municipality'] ?? '',
        'addressRegion'   => $alojamiento['province']    ?? 'Soria',
        'postalCode'      => $alojamiento['postal_code'] ?? '',
        'addressCountry'  => 'ES',
    ];
    // Eliminar claves vacías del address
    $address = array_filter($address, fn($v) => $v !== '');

    // Imágenes consolidadas (mínimo necesario: todas las disponibles)
    $allImages = !empty($imageObjects) ? $imageObjects : array_map(
        fn($u) => str_starts_with($u, 'http') ? $u : $baseUrl . $u,
        $fotos
    );

    $lodging = [
        '@type'         => $schemaType,
        '@id'           => $canonical . '#lodging',
        // identifier: REQUERIDO por Google para VacationRental
        // Debe ser un string simple (URL canónica) — PropertyValue no es válido aquí
        'identifier'    => $canonical,
        'name'          => $alojamiento['name'],
        'description'   => $descShort,
        'url'           => $canonical,
        'image'         => $allImages,
        'address'       => $address,
        'checkinTime'   => $checkinTime,
        'checkoutTime'  => $checkoutTime,
        'currenciesAccepted' => 'EUR',
        'paymentAccepted'    => 'Cash, Credit Card',
    ];

    // Contacto
    if (!empty($alojamiento['phone']))   $lodging['telephone']  = $alojamiento['phone'];
    if (!empty($alojamiento['email']))   $lodging['email']      = $alojamiento['email'];
    if (!empty($alojamiento['website'])) $lodging['sameAs']     = [$alojamiento['website']];

    // Precio
    if (!empty($alojamiento['price_per_night']) && (float)$alojamiento['price_per_night'] > 0) {
        $precio = (float)$alojamiento['price_per_night'];
        $lodging['priceRange'] = 'Desde ' . number_format($precio, 0, ',', '.') . '€/noche';
        $lodging['offers']     = [
            '@type'         => 'Offer',
            'name'          => 'Precio por noche — ' . $alojamiento['name'],
            'price'         => number_format($precio, 2, '.', ''),
            'priceCurrency' => 'EUR',
            'availability'  => 'https://schema.org/InStock',
            'url'           => $canonical,
            'seller'        => ['@type' => 'Organization', 'name' => 'Rutas Rurales', 'url' => $baseUrl],
        ];
    }

    // Capacidad — QuantitativeValue requiere 'value' (no solo maxValue)
    if (!empty($alojamiento['capacity']) && (int)$alojamiento['capacity'] > 0) {
        $lodging['occupancy'] = [
            '@type'    => 'QuantitativeValue',
            'value'    => (int)$alojamiento['capacity'],
            'maxValue' => (int)$alojamiento['capacity'],
            'unitCode' => 'C62', // personas
        ];
    }

    // Habitaciones
    if (!empty($alojamiento['bedrooms']) && (int)$alojamiento['bedrooms'] > 0) {
        $lodging['numberOfRooms'] = (int)$alojamiento['bedrooms'];
    }

    // Baños
    if (!empty($alojamiento['bathrooms']) && (int)$alojamiento['bathrooms'] > 0) {
        $lodging['numberOfBathroomsTotal'] = (int)$alojamiento['bathrooms'];
    }

    // Coordenadas
    if (!empty($alojamiento['latitude']) && !empty($alojamiento['longitude'])) {
        $lat = (float)$alojamiento['latitude'];
        $lng = (float)$alojamiento['longitude'];
        $lodging['geo']    = ['@type' => 'GeoCoordinates', 'latitude' => $lat, 'longitude' => $lng];
        $lodging['hasMap'] = 'https://www.google.com/maps?q=' . $lat . ',' . $lng;
    }

    // containsPlace: REQUERIDO por Google para VacationRental
    // Describe las unidades de alojamiento que contiene la propiedad
    // (habitaciones, zonas, etc.)
    $containsPlaceObj = [
        '@type' => 'Accommodation',
        'name'  => 'Alojamiento completo — ' . $alojamiento['name'],
    ];
    // numberOfBedrooms (correcto para Accommodation, Google lo requiere)
    if (!empty($alojamiento['bedrooms']) && (int)$alojamiento['bedrooms'] > 0) {
        $containsPlaceObj['numberOfBedrooms']    = (int)$alojamiento['bedrooms'];
        $containsPlaceObj['numberOfRooms']       = (int)$alojamiento['bedrooms']; // alias
    }
    // occupancy: QuantitativeValue REQUIERE 'value' (además de maxValue)
    if (!empty($alojamiento['capacity']) && (int)$alojamiento['capacity'] > 0) {
        $containsPlaceObj['occupancy'] = [
            '@type'    => 'QuantitativeValue',
            'value'    => (int)$alojamiento['capacity'],
            'maxValue' => (int)$alojamiento['capacity'],
            'unitCode' => 'C62',
        ];
    }
    if (!empty($alojamiento['bathrooms']) && (int)$alojamiento['bathrooms'] > 0) {
        $containsPlaceObj['numberOfBathroomsTotal'] = (int)$alojamiento['bathrooms'];
    }
    if (!empty($amenityFeatures)) {
        $containsPlaceObj['amenityFeature'] = $amenityFeatures;
    }
    $lodging['containsPlace'] = $containsPlaceObj;

    // Amenidades
    if (!empty($amenityFeatures)) {
        $lodging['amenityFeature'] = $amenityFeatures;
    }

    // Mascotas
    if (isset($alojamiento['pet_friendly'])) {
        $lodging['petsAllowed'] = (bool)(int)$alojamiento['pet_friendly'];
    }

    // Rating (si existe en BD)
    if (!empty($alojamiento['rating_avg']) && !empty($alojamiento['rating_count'])) {
        $lodging['aggregateRating'] = [
            '@type'       => 'AggregateRating',
            'ratingValue' => number_format((float)$alojamiento['rating_avg'], 1, '.', ''),
            'reviewCount' => (int)$alojamiento['rating_count'],
            'bestRating'  => '5',
            'worstRating' => '1',
        ];
    }

    // ── 6. BreadcrumbList ─────────────────────────────────────────────────────
    $bcLabels = [
        'es' => ['Inicio', 'Alojamientos turísticos'],
        'en' => ['Home',   'Accommodations'],
        'fr' => ['Accueil','Hébergements'],
        'de' => ['Startseite', 'Unterkünfte'],
        'zh' => ['首页', '住宿列表'],
    ];
    $bl            = $bcLabels[$lang] ?? $bcLabels['es'];
    $listingUrl    = $baseUrl . ($lang !== 'es' ? "/$lang" : '') . '/alojamientos-turisticos';
    $breadcrumb    = [
        '@type'           => 'BreadcrumbList',
        '@id'             => $canonical . '#breadcrumb',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => $bl[0], 'item' => $baseUrl . '/'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $bl[1], 'item' => $listingUrl],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $alojamiento['name'], 'item' => $canonical],
        ],
    ];

    // ── 7. WebPage ────────────────────────────────────────────────────────────
    $inLanguage = $lang === 'es' ? 'es-ES' : strtolower($lang) . '-' . strtoupper($lang);
    $webpage    = [
        '@type'          => 'WebPage',
        '@id'            => $canonical . '#webpage',
        'url'            => $canonical,
        'name'           => $page_title,
        'description'    => $page_desc,
        'inLanguage'     => $inLanguage,
        'isPartOf'       => ['@id' => $baseUrl . '/#website'],
        'about'          => ['@id' => $canonical . '#lodging'],
        'breadcrumb'     => ['@id' => $canonical . '#breadcrumb'],
        'datePublished'  => $alojamiento['created_at'] ?? date('Y-m-d'),
        'dateModified'   => $alojamiento['updated_at'] ?? date('Y-m-d'),
        'speakable'      => [
            '@type'    => 'SpeakableSpecification',
            'cssSelector' => ['.alo-hero h1', '.desc-text'],
        ],
    ];
    if (!empty($imageObjects)) {
        $webpage['primaryImageOfPage'] = ['@id' => $canonical . '#photo1'];
    }

    // ── 8. WebSite (sitelinks searchbox) ─────────────────────────────────────
    $website = [
        '@type'            => 'WebSite',
        '@id'              => $baseUrl . '/#website',
        'name'             => 'Rutas Rurales',
        'url'              => $baseUrl . '/',
        'description'      => 'Turismo rural en España: alojamientos, rutas, eventos y lugares de interés',
        'inLanguage'       => 'es-ES',
        'publisher'        => [
            '@type' => 'Organization',
            '@id'   => $baseUrl . '/#organization',
            'name'  => 'Rutas Rurales',
            'url'   => $baseUrl . '/',
            'logo'  => [
                '@type' => 'ImageObject',
                'url'   => $baseUrl . '/menu_images/Logo%20transparente.webp',
                'width' => 300,
                'height'=> 80,
            ],
            'sameAs' => ['https://twitter.com/rutasrurales'],
        ],
        'potentialAction'  => [
            '@type'       => 'SearchAction',
            'target'      => [
                '@type'       => 'EntryPoint',
                'urlTemplate' => $baseUrl . '/alojamientos-turisticos?q={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];

    // ── 9. FAQPage dinámico ───────────────────────────────────────────────────
    $nombre   = $alojamiento['name'];
    $municipio= $alojamiento['municipality'] ?? 'Soria';
    $provincia= $alojamiento['province']    ?? 'Soria';
    $capacidad= (int)($alojamiento['capacity'] ?? 0);
    $precioNoche = !empty($alojamiento['price_per_night']) && (float)$alojamiento['price_per_night'] > 0
        ? number_format((float)$alojamiento['price_per_night'], 0, ',', '.') . ' € por noche'
        : 'consultar directamente';
    $petFriendly  = !empty($alojamiento['pet_friendly'])     && (int)$alojamiento['pet_friendly']     === 1;
    $tieneWifi    = !empty($alojamiento['wifi'])              && (int)$alojamiento['wifi']              === 1;
    $tienePiscina = !empty($alojamiento['swimming_pool'])     && (int)$alojamiento['swimming_pool']     === 1;
    $tieneParking = !empty($alojamiento['parking'])           && (int)$alojamiento['parking']           === 1;
    $aptaNinos    = !empty($alojamiento['suitable_for_children']) && (int)$alojamiento['suitable_for_children'] === 1;

    $faqItems = [
        [
            '@type'          => 'Question',
            'name'           => '¿Dónde está ubicado ' . $nombre . '?',
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => $nombre . ' se encuentra en ' . $municipio . ', provincia de ' . $provincia . '. '
                    . (!empty($alojamiento['address']) ? 'La dirección exacta es ' . $alojamiento['address'] . '.' : ''),
            ],
        ],
        [
            '@type'          => 'Question',
            'name'           => '¿Cuánto cuesta alojarse en ' . $nombre . '?',
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => 'El precio en ' . $nombre . ' es de ' . $precioNoche . '. '
                    . 'Para reservar o consultar disponibilidad, puedes contactar directamente con el alojamiento.',
            ],
        ],
    ];

    // FAQ: capacidad
    if ($capacidad > 0) {
        $faqItems[] = [
            '@type'          => 'Question',
            'name'           => '¿Cuántas personas puede alojar ' . $nombre . '?',
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => $nombre . ' tiene capacidad para ' . $capacidad . ' persona' . ($capacidad === 1 ? '' : 's') . '.',
            ],
        ];
    }

    // FAQ: mascotas
    $faqItems[] = [
        '@type'          => 'Question',
        'name'           => '¿Se admiten mascotas en ' . $nombre . '?',
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text'  => $petFriendly
                ? $nombre . ' admite mascotas. Es un alojamiento pet-friendly ideal para viajeros con animales de compañía.'
                : 'Por el momento ' . $nombre . ' no indica que admita mascotas. Te recomendamos consultar directamente con el alojamiento.',
        ],
    ];

    // FAQ: WiFi
    $faqItems[] = [
        '@type'          => 'Question',
        'name'           => '¿Tiene WiFi ' . $nombre . '?',
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text'  => $tieneWifi
                ? $nombre . ' dispone de WiFi gratuito para sus huéspedes.'
                : 'Consulta directamente con ' . $nombre . ' si dispone de conexión WiFi.',
        ],
    ];

    // FAQ: niños
    $faqItems[] = [
        '@type'          => 'Question',
        'name'           => '¿Es ' . $nombre . ' apto para familias con niños?',
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text'  => $aptaNinos
                ? $nombre . ' está especialmente indicado para familias con niños. Es una opción perfecta para disfrutar del turismo rural en ' . $municipio . '.'
                : $nombre . ' en ' . $municipio . ' es un alojamiento rural en plena naturaleza, ideal para desconectar. Consulta con el establecimiento si se adapta a las necesidades de tu familia.',
        ],
    ];

    // FAQ: check-in/check-out
    $faqItems[] = [
        '@type'          => 'Question',
        'name'           => '¿Cuáles son los horarios de entrada y salida en ' . $nombre . '?',
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text'  => 'El check-in en ' . $nombre . ' es a partir de las ' . ltrim($ci, 'T')
                . ' y el check-out hasta las ' . ltrim($co, 'T') . '. '
                . 'Para llegadas fuera de horario, contacta con el alojamiento con antelación.',
        ],
    ];

    // FAQ: qué hay cerca
    $faqItems[] = [
        '@type'          => 'Question',
        'name'           => '¿Qué se puede hacer cerca de ' . $nombre . ' en ' . $municipio . '?',
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text'  => $municipio . ', en la provincia de ' . $provincia . ', ofrece numerosas actividades y atractivos turísticos.'
                . ' Puedes explorar rutas de senderismo, monumentos, gastronomía local y festividades tradicionales. '
                . 'Consulta nuestra web para descubrir alojamientos, lugares de interés, actividades y eventos cercanos a ' . $nombre . '.',
        ],
    ];

    $faqPage = [
        '@type'      => 'FAQPage',
        '@id'        => $canonical . '#faq',
        'mainEntity' => $faqItems,
    ];

    // ── 10. Ensamblar @graph y emitir bloques <script> separados ──────────────
    // Google recomienda un @graph por página, separamos WebSite en bloque propio
    // para no mezclar entidades independientes.

    $graph = [
        '@context' => 'https://schema.org',
        '@graph'   => [$website, $webpage, $breadcrumb, $lodging],
    ];

    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;

    // Bloque principal (entidades relacionadas en @graph)
    echo '<script type="application/ld+json">' . "\n";
    echo json_encode($graph, $flags);
    echo "\n</script>\n";

    // FAQPage en bloque separado (entidad independiente)
    echo '<script type="application/ld+json">' . "\n";
    echo json_encode(['@context' => 'https://schema.org'] + $faqPage, $flags);
    echo "\n</script>\n";
}
