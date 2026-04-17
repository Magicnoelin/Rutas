<?php
/**
 * Schema.org JSON-LD dinámico para rutas temáticas
 * Genera: TouristTrip + ItemList (alojamientos, lugares, eventos)
 * + FAQPage + BreadcrumbList
 */

function renderSchema(array $ruta, array $alojamientos, array $lugares, array $actividades, array $eventos): void
{
    $baseUrl  = 'https://rutasrurales.io';
    $rutaUrl  = $baseUrl . '/rutas/' . $ruta['slug'];
    $heroImg  = $ruta['hero_image'] ?? $baseUrl . '/menu_images/Logo%20transparente.webp';

    // ── TouristTrip ──────────────────────────────────────────────────────────
    $touristTrip = [
        '@context'    => 'https://schema.org',
        '@type'       => 'TouristTrip',
        'name'        => $ruta['seo_title'] ?? $ruta['name'],
        'description' => $ruta['seo_description'] ?? substr(strip_tags($ruta['description']), 0, 300),
        'url'         => $rutaUrl,
        'image'       => $heroImg,
        'touristType' => ['Turismo rural', 'Turismo cultural', 'Turismo de naturaleza'],
        'itinerary'   => [],
        'offers'      => [
            '@type'         => 'Offer',
            'price'         => 0,
            'priceCurrency' => 'EUR',
            'availability'  => 'https://schema.org/InStock',
        ],
    ];

    // Añadir días del itinerario
    if (!empty($ruta['itinerary_json'])) {
        foreach ($ruta['itinerary_json'] as $dia) {
            $touristTrip['itinerary'][] = [
                '@type'       => 'TouristAttraction',
                'name'        => $dia['titulo'] ?? '',
                'description' => $dia['descripcion'] ?? '',
            ];
        }
    }

    // ── ItemList de alojamientos ─────────────────────────────────────────────
    $alojList = null;
    if (!empty($alojamientos)) {
        $alojItems = [];
        foreach ($alojamientos as $i => $a) {
            $foto = $a['fotos'][0] ?? $heroImg;
            $alojItems[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'item'     => [
                    '@type'       => 'LodgingBusiness',
                    'name'        => $a['name'],
                    'url'         => $a['url'],
                    'image'       => $foto,
                    'description' => substr(strip_tags($a['short_description'] ?? $a['description'] ?? ''), 0, 200),
                    'address'     => [
                        '@type'           => 'PostalAddress',
                        'addressLocality' => $a['municipality'] ?? '',
                        'addressRegion'   => $a['province'] ?? 'Soria',
                        'addressCountry'  => 'ES',
                    ],
                    'priceRange' => !empty($a['price_per_night']) ? 'Desde ' . $a['price_per_night'] . '€/noche' : '$$',
                ],
            ];
        }
        $alojList = [
            '@context'        => 'https://schema.org',
            '@type'           => 'ItemList',
            'name'            => 'Alojamientos recomendados — ' . $ruta['name'],
            'numberOfItems'   => count($alojamientos),
            'itemListElement' => $alojItems,
        ];
    }

    // ── ItemList de eventos ──────────────────────────────────────────────────
    $eventosList = null;
    if (!empty($eventos)) {
        $evItems = [];
        foreach ($eventos as $i => $e) {
            $evItems[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'item'     => [
                    '@type'       => 'Event',
                    'name'        => $e['title'],
                    'url'         => $e['url'],
                    'image'       => $e['imagen'] ?? $heroImg,
                    'description' => substr(strip_tags($e['short_description'] ?? $e['description'] ?? ''), 0, 200),
                    'startDate'   => $e['start_date'] ?? '',
                    'endDate'     => $e['end_date'] ?? $e['start_date'] ?? '',
                    'location'    => [
                        '@type'   => 'Place',
                        'name'    => $e['venue_name'] ?? $e['municipality'] ?? 'Soria',
                        'address' => [
                            '@type'           => 'PostalAddress',
                            'addressLocality' => $e['municipality'] ?? '',
                            'addressRegion'   => $e['province'] ?? 'Soria',
                            'addressCountry'  => 'ES',
                        ],
                    ],
                    'offers' => [
                        '@type'         => 'Offer',
                        'price'         => $e['ticket_price'] ?? 0,
                        'priceCurrency' => 'EUR',
                    ],
                ],
            ];
        }
        $eventosList = [
            '@context'        => 'https://schema.org',
            '@type'           => 'ItemList',
            'name'            => 'Eventos durante ' . $ruta['name'],
            'numberOfItems'   => count($eventos),
            'itemListElement' => $evItems,
        ];
    }

    // ── BreadcrumbList ───────────────────────────────────────────────────────
    $breadcrumb = [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio',         'item' => $baseUrl . '/'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Rutas',          'item' => $baseUrl . '/rutas/'],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $ruta['name'],    'item' => $rutaUrl],
        ],
    ];

    // ── FAQPage ──────────────────────────────────────────────────────────────
    $provincia = $ruta['province'] ?? 'Soria';
    $faqPage = [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => [
            [
                '@type'          => 'Question',
                'name'           => '¿Qué hacer en ' . $provincia . ' el puente del 1 de mayo?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'En ' . $provincia . ' el puente del 1 de mayo puedes visitar el yacimiento de Numancia, hacer senderismo en la Laguna Negra, explorar el Cañón del Río Lobos y disfrutar de eventos culturales locales. Es uno de los destinos rurales más auténticos de España.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => '¿Cuánto cuesta una escapada rural a ' . $provincia . ' el puente de mayo?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                     'text'  => 'Una escapada de 3 días a ' . $provincia . ' el puente de mayo tiene un coste estimado de 350€ por persona, incluyendo alojamiento en casa rural, actividades y gastronomía local.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => '¿Hay casas rurales disponibles en ' . $provincia . ' para el puente del 1 de mayo?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'Sí, en rutasrurales.io encontrarás casas rurales y apartamentos turísticos en ' . $provincia . ' disponibles para el puente del 1 de mayo. Te recomendamos reservar con antelación ya que es una fecha muy demandada.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => '¿Cuántos días necesito para visitar ' . $provincia . ' el puente de mayo?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'Con ' . ($ruta['duration_days'] ?? 3) . ' días tienes tiempo suficiente para disfrutar de los principales atractivos de ' . $provincia . '. El itinerario recomendado incluye historia, naturaleza, gastronomía y cultura.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => '¿Es ' . $provincia . ' un buen destino para el puente del 1 de mayo con niños?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => $provincia . ' es un destino ideal para familias con niños el puente de mayo. Las rutas de senderismo de dificultad baja, los yacimientos arqueológicos y los espacios naturales hacen de esta provincia un lugar perfecto para el turismo familiar.',
                ],
            ],
        ],
    ];

    // ── Renderizar todos los schemas ─────────────────────────────────────────
    $schemas = [$touristTrip, $breadcrumb, $faqPage];
    if ($alojList)   $schemas[] = $alojList;
    if ($eventosList) $schemas[] = $eventosList;

    foreach ($schemas as $schema) {
        echo '<script type="application/ld+json">' . "\n";
        echo json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        echo "\n</script>\n";
    }
}
