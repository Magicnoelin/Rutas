<?php
/**
 * Schema.org JSON-LD — Ficha de Lugar de Interés
 * ================================================
 * Genera marcado estructurado multi-graph:
 *   1. WebSite   (sitelinks searchbox)
 *   2. WebPage   (con primaryImageOfPage + breadcrumb + speakable)
 *   3. BreadcrumbList
 *   4. TouristAttraction (con GeoCoordinates, OpeningHours, isAccessibleForFree)
 *   5. FAQPage   (prioridad BD, fallback autogenerado)
 *
 * Uso:
 *   require_once __DIR__ . '/components/schema.php';
 *   renderLugarSchema($lugar, $fotos, $canonical, $page_title, $page_desc, $lang, $faqs);
 *
 * @param array  $lugar       Fila completa de la BD (places_of_interest)
 * @param array  $fotos       URLs de las fotos (relativas o absolutas)
 * @param string $canonical   URL canónica de la página
 * @param string $page_title Título SEO
 * @param string $page_desc   Descripción SEO
 * @param string $lang        Código de idioma (es|en|fr|de|zh)
 * @param array  $faqs        Array de FAQs obtenido de la BD con getFaqs()
 */

function renderLugarSchema(
    array  $lugar,
    array  $fotos,
    string $canonical,
    string $page_title,
    string $page_desc,
    string $lang = 'es',
    array  $faqs = [] // <-- Nuevo parámetro opcional con las FAQs de la BD
): void {

    $baseUrl    = 'https://rutasrurales.io';
    $inLanguage = $lang === 'es' ? 'es-ES' : strtolower($lang) . '-' . strtoupper($lang);

    // ── 1. ImageObject array ──────────────────────────────────────────────────
    $imageObjects = [];
    foreach ($fotos as $idx => $fotoUrl) {
        $fullUrl        = preg_match('/^https?:\/\//', $fotoUrl) ? $fotoUrl : $baseUrl . $fotoUrl;
        $imageObjects[] = [
            '@type'      => 'ImageObject',
            '@id'        => $canonical . '#photo' . ($idx + 1),
            'url'        => $fullUrl,
            'contentUrl' => $fullUrl,
            'name'       => htmlspecialchars_decode($lugar['name']) . ' — foto ' . ($idx + 1),
            'description'=> htmlspecialchars_decode($lugar['name']) . ' en ' . ($lugar['municipality'] ?? 'España'),
            'inLanguage' => $inLanguage,
        ];
    }

    // Imagen genérica de fallback
    $genericImage = $baseUrl . '/menu_images/turismo_rural.webp';
    if (empty($imageObjects)) {
        $imageObjects[] = [
            '@type'      => 'ImageObject',
            '@id'        => $canonical . '#photo1',
            'url'        => $genericImage,
            'contentUrl' => $genericImage,
            'name'       => htmlspecialchars_decode($lugar['name']),
            'inLanguage' => $inLanguage,
        ];
    }

    // ── 2. TouristAttraction ──────────────────────────────────────────────────
    $descRaw   = strip_tags($lugar['short_description'] ?? $lugar['description'] ?? '');
    $descShort = mb_substr($descRaw, 0, 500);

    $address = array_filter([
        '@type'           => 'PostalAddress',
        'streetAddress'   => $lugar['address']      ?? '',
        'addressLocality' => $lugar['municipality']  ?? '',
        'addressRegion'   => $lugar['province']      ?? '',
        'postalCode'      => $lugar['postal_code']   ?? '',
        'addressCountry'  => 'ES',
    ], fn($v) => $v !== '');

    $tourist = [
        '@type'       => 'TouristAttraction',
        '@id'         => $canonical . '#lugar',
        'name'        => $lugar['name'],
        'description' => $descShort,
        'url'         => $canonical,
        'image'       => $imageObjects,
        'address'     => $address,
    ];

    // Coordenadas geográficas
    if (!empty($lugar['latitude']) && !empty($lugar['longitude'])) {
        $lat = (float)$lugar['latitude'];
        $lng = (float)$lugar['longitude'];
        $tourist['geo']    = ['@type' => 'GeoCoordinates', 'latitude' => $lat, 'longitude' => $lng];
        $tourist['hasMap'] = 'https://www.google.com/maps?q=' . $lat . ',' . $lng;
    }

    // Contacto
    if (!empty($lugar['phone']))   $tourist['telephone'] = $lugar['phone'];
    if (!empty($lugar['email']))   $tourist['email']     = $lugar['email'];
    if (!empty($lugar['website'])) $tourist['sameAs']    = [$lugar['website']];

    // Horario de apertura
    if (!empty($lugar['opening_hours'])) {
        $tourist['openingHours'] = $lugar['opening_hours'];
    }

    // Accesibilidad
    if (!empty($lugar['accessibility'])) {
        $tourist['amenityFeature'] = [
            ['@type' => 'LocationFeatureSpecification', 'name' => $lugar['accessibility'], 'value' => true],
        ];
    }

    // Entrada gratuita
    $esGratis = empty($lugar['entry_fee']) || $lugar['entry_fee'] == 0;
    $tourist['isAccessibleForFree'] = $esGratis;

    // Precio de entrada
    if (!empty($lugar['entry_fee']) && (float)$lugar['entry_fee'] > 0) {
        $tourist['offers'] = [
            '@type'         => 'Offer',
            'price'         => number_format((float)$lugar['entry_fee'], 2, '.', ''),
            'priceCurrency' => 'EUR',
            'availability'  => 'https://schema.org/InStock',
            'url'           => $canonical,
            'seller'        => ['@type' => 'Organization', 'name' => 'Rutas Rurales', 'url' => $baseUrl],
        ];
    }

    // Tipo turístico según categoría (touristType)
    $cat = strtolower($lugar['category_name'] ?? '');
    $touristTypeMap = [
        'naturaleza'   => 'Naturaleza',
        'senderismo'   => 'Excursionista',
        'familia'      => 'Familia',
        'cultural'     => 'Turismo cultural',
        'monument'     => 'Turismo cultural',
        'iglesia'      => 'Turismo cultural',
        'castillo'     => 'Turismo cultural',
        'gastronomía'  => 'Gastrónomo',
        'restauran'    => 'Gastrónomo',
        'bodega'       => 'Enoturismo',
        'playa'        => 'Turismo de playa',
        'ski'          => 'Deportes de invierno',
        'aventura'     => 'Turismo de aventura',
    ];
    foreach ($touristTypeMap as $kw => $tipo) {
        if (str_contains($cat, $kw)) {
            $tourist['touristType'] = ['@type' => 'Audience', 'audienceType' => $tipo];
            break;
        }
    }

    // ── 3. BreadcrumbList ─────────────────────────────────────────────────────
    $bcLabels = [
        'es' => ['Inicio', 'Lugares de interés'],
        'en' => ['Home',   'Places of interest'],
        'fr' => ['Accueil','Lieux d\'intérêt'],
        'de' => ['Startseite', 'Sehenswürdigkeiten'],
        'zh' => ['首页', '旅游景点'],
    ];
    $bl         = $bcLabels[$lang] ?? $bcLabels['es'];
    $listingUrl = $baseUrl . '/lugares-de-interes';

    $breadcrumb = [
        '@type'           => 'BreadcrumbList',
        '@id'             => $canonical . '#breadcrumb',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => $bl[0], 'item' => $baseUrl . '/'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $bl[1], 'item' => $listingUrl],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $lugar['name'], 'item' => $canonical],
        ],
    ];

    // ── 4. WebPage ────────────────────────────────────────────────────────────
    $webpage = [
        '@type'         => 'WebPage',
        '@id'           => $canonical . '#webpage',
        'url'           => $canonical,
        'name'          => $page_title,
        'description'   => $page_desc,
        'inLanguage'    => $inLanguage,
        'isPartOf'      => ['@id' => $baseUrl . '/#website'],
        'about'         => ['@id' => $canonical . '#lugar'],
        'breadcrumb'    => ['@id' => $canonical . '#breadcrumb'],
        'datePublished' => $lugar['created_at'] ?? date('Y-m-d'),
        'dateModified'  => $lugar['updated_at'] ?? date('Y-m-d'),
        'speakable'     => [
            '@type'       => 'SpeakableSpecification',
            'cssSelector' => ['.lug-hero h1', '.desc-text'],
        ],
    ];
    if (!empty($imageObjects)) {
        $webpage['primaryImageOfPage'] = ['@id' => $canonical . '#photo1'];
    }

    // ── 5. WebSite (sitelinks searchbox) ─────────────────────────────────────
    $website = [
        '@type'       => 'WebSite',
        '@id'         => $baseUrl . '/#website',
        'name'        => 'Rutas Rurales',
        'url'         => $baseUrl . '/',
        'description' => 'Turismo rural en España: alojamientos, rutas, eventos y lugares de interés',
        'inLanguage'  => 'es-ES',
        'publisher'   => [
            '@type' => 'Organization',
            '@id'   => $baseUrl . '/#organization',
            'name'  => 'Rutas Rurales',
            'url'   => $baseUrl . '/',
            'logo'  => [
                '@type'  => 'ImageObject',
                'url'    => $baseUrl . '/menu_images/Logo%20transparente.webp',
                'width'  => 300,
                'height' => 80,
            ],
            'sameAs' => ['https://twitter.com/rutasrurales'],
        ],
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => [
                '@type'       => 'EntryPoint',
                'urlTemplate' => $baseUrl . '/lugares-de-interes?q={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];

    // ── 6. FAQPage dinámico (Prioridad: BD -> Fallback: Autogeneradas) ────────
    $faqItems = [];

    if (!empty($faqs)) {
        // A) Si existen preguntas guardadas en BD para esta ficha
        foreach ($faqs as $faq) {
            $faqItems[] = [
                '@type'          => 'Question',
                'name'           => strip_tags($faq['question']),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => strip_tags($faq['answer'])
                ]
            ];
        }
    } else {
        // B) FALLBACK: Si NO hay preguntas en BD, generamos las preguntas por defecto
        $nombre      = $lugar['name'];
        $municipio   = $lugar['municipality'] ?? 'España';
        $provincia   = $lugar['province']     ?? 'España';
        $esGratisStr = $esGratis
            ? 'La entrada es gratuita.'
            : 'El precio de entrada es de ' . ($lugar['entry_fee'] ?? '') . '€.' . (!empty($lugar['entry_fee_details']) ? ' ' . $lugar['entry_fee_details'] . '.' : '');

        $faqItems = [
            [
                '@type'          => 'Question',
                'name'           => '¿Dónde está ' . $nombre . '?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => $nombre . ' se encuentra en ' . $municipio . ', provincia de ' . $provincia . '.'
                        . (!empty($lugar['address']) ? ' La dirección es ' . $lugar['address'] . '.' : '')
                        . (!empty($lugar['latitude']) && !empty($lugar['longitude'])
                            ? ' Puedes ver su ubicación exacta en Google Maps.'
                            : ''),
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => '¿Cuánto cuesta visitar ' . $nombre . '?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => $esGratisStr . ' Consulta la información actualizada antes de tu visita.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => '¿Cuál es el horario de ' . $nombre . '?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => !empty($lugar['opening_hours'])
                        ? 'El horario de ' . $nombre . ' es: ' . $lugar['opening_hours'] . '. Te recomendamos confirmar el horario antes de tu visita.'
                        : 'El horario de ' . $nombre . ' puede variar según la temporada. Te recomendamos contactar directamente o consultar su web oficial para obtener información actualizada.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => '¿Cuánto tiempo se tarda en visitar ' . $nombre . '?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => !empty($lugar['visit_duration'])
                        ? 'La duración aproximada de la visita a ' . $nombre . ' es de ' . $lugar['visit_duration'] . '.'
                        : 'La duración de la visita a ' . $nombre . ' depende del ritmo de cada visitante. Generalmente se recomienda reservar entre 1 y 2 horas para disfrutarlo con tranquilidad.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => '¿Qué se puede hacer cerca de ' . $nombre . '?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => $municipio . ', en la provincia de ' . $provincia . ', ofrece numerosas actividades y atractivos turísticos. '
                        . 'Puedes explorar rutas de senderismo, monumentos históricos, gastronomía local y festividades tradicionales. '
                        . 'Consulta Rutas Rurales para descubrir alojamientos, lugares de interés, actividades y eventos cercanos a ' . $nombre . '.',
                ],
            ],
        ];

        if (!empty($lugar['best_season'])) {
            $faqItems[] = [
                '@type'          => 'Question',
                'name'           => '¿Cuándo es la mejor época para visitar ' . $nombre . '?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'La mejor época para visitar ' . $nombre . ' es ' . $lugar['best_season'] . '.',
                ],
            ];
        }

        if (!empty($lugar['pet_friendly'])) {
            $faqItems[] = [
                '@type'          => 'Question',
                'name'           => '¿Se pueden llevar mascotas a ' . $nombre . '?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => $nombre . ' admite mascotas. Es un lugar pet-friendly ideal para visitar con tu animal de compañía.',
                ],
            ];
        }

        if (!empty($lugar['suitable_for_children'])) {
            $faqItems[] = [
                '@type'          => 'Question',
                'name'           => '¿Es ' . $nombre . ' apto para niños?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => $nombre . ' está indicado para familias con niños. Es una visita perfecta para disfrutar del turismo rural en ' . $municipio . '.',
                ],
            ];
        }
    }

    $faqPage = [
        '@type'      => 'FAQPage',
        '@id'        => $canonical . '#faq',
        'mainEntity' => $faqItems,
    ];

    // ── 7. Ensamblar @graph y emitir ──────────────────────────────────────────
    $graph = [
        '@context' => 'https://schema.org',
        '@graph'   => [$website, $webpage, $breadcrumb, $tourist],
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
