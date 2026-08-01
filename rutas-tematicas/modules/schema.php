<?php
/**
 * Schema.org JSON-LD dinámico para rutas temáticas
 * Genera: TouristTrip + ItemList (alojamientos, lugares, eventos)
 * + FAQPage + BreadcrumbList
 */

function renderSchema(array $ruta, array $alojamientos, array $lugares, array $actividades, array $eventos, array $faqs = []): void
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
            'url'           => $rutaUrl,
            'validFrom'     => date('Y-m-d') . 'T00:00:00+02:00',
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
                    'name'        => $e['title'] ?? $e['name'] ?? '',
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
                                    'eventStatus'         => 'https://schema.org/EventScheduled',
                                    'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
                                    // organizer: requerido por Google Search Console.
                                    // Si hay dato real en BD se usa; si no, fallback al municipio/provincia.
                                    'organizer'           => [
                                        '@type' => 'Organization',
                                        'name'  => !empty($e['organizer'])
                                                    ? $e['organizer']
                                                    : (!empty($e['municipality'])
                                                        ? 'Ayuntamiento de ' . $e['municipality']
                                                        : (!empty($e['province'])
                                                            ? 'Ayuntamiento de ' . $e['province']
                                                            : 'Organización local')),
                                    ],
                                    // performer: recomendado por Google. Para eventos populares/tradicionales,
                                    // la entidad organizadora actúa también como ejecutora del evento.
                                    'performer'           => [
                                        '@type' => 'Organization',
                                        'name'  => !empty($e['organizer'])
                                                    ? $e['organizer']
                                                    : (!empty($e['municipality'])
                                                        ? $e['municipality']
                                                        : ($e['province'] ?? 'Organización local')),
                                    ],
                    'offers' => [
                        '@type'         => 'Offer',
                        'price'         => isset($e['ticket_price']) && $e['ticket_price'] > 0 ? number_format((float)$e['ticket_price'], 2, '.', '') : '0',
                        'priceCurrency' => 'EUR',
                        'availability'  => 'https://schema.org/InStock',
                        'url'           => $e['url'],
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
    $breadcrumb_name = !empty($ruta['name']) ? $ruta['name'] : ($ruta['slug'] ?? 'Ruta');
    $breadcrumb = [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio',         'item' => $baseUrl . '/'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Rutas',          'item' => $baseUrl . '/rutas/'],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $breadcrumb_name, 'item' => $rutaUrl],
        ],
    ];

    // ── FAQPage (JSON-LD Schema) ─────────────────────────────────────────────
    $provincia = $ruta['province'] ?? 'Soria';
    $duracion  = (int)($ruta['duration_days'] ?? 3);

    // Determinar época del año automáticamente
    $mes_actual = (int)date('m');
    $evento_proximo = match(true) {
        $mes_actual >= 3 && $mes_actual <= 5  => 'el puente de mayo o primavera',
        $mes_actual >= 6 && $mes_actual <= 8  => 'las vacaciones de verano',
        $mes_actual >= 9 && $mes_actual <= 11 => 'el puente de diciembre u otoño',
        default                               => 'las vacaciones de Navidad o invierno',
    };

    // Construir mainEntity para FAQPage
    $faqMainEntity = [];

    if (!empty($faqs)) {
        // PRIORIDAD 1: FAQs desde BD
        foreach ($faqs as $f) {
            $faqMainEntity[] = [
                '@type'          => 'Question',
                'name'           => $f['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => $f['answer'],
                ],
            ];
        }
    } else {
        // PRIORIDAD 2: Fallback automático con lógica temporal
        $faqMainEntity = [
            [
                '@type'          => 'Question',
                'name'           => '¿Qué hacer en ' . $provincia . ' durante ' . $evento_proximo . '?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'En ' . $provincia . ' durante ' . $evento_proximo . ' puedes visitar sus monumentos y parajes naturales, hacer senderismo y rutas por la zona y disfrutar de la gastronomía local. Es uno de los destinos rurales más auténticos de España.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => '¿Cuánto cuesta una escapada rural a ' . $provincia . '?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'Una escapada de ' . $duracion . ' días a ' . $provincia . ' tiene un coste estimado de 350€ por persona, incluyendo alojamiento en casa rural, actividades y gastronomía local.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => '¿Hay casas rurales disponibles en ' . $provincia . ' para ' . $evento_proximo . '?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'Sí, en rutasrurales.io encontrarás casas rurales y apartamentos turísticos en ' . $provincia . ' disponibles para ' . $evento_proximo . '. Te recomendamos reservar con antelación ya que es una fecha muy demandada.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => '¿Cuántos días necesito para visitar ' . $provincia . '?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => 'Con ' . $duracion . ' días tienes tiempo suficiente para disfrutar de los principales atractivos de ' . $provincia . '. El itinerario recomendado incluye historia, naturaleza, gastronomía y cultura.',
                ],
            ],
            [
                '@type'          => 'Question',
                'name'           => '¿Es ' . $provincia . ' un buen destino para ' . $evento_proximo . ' con niños?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => $provincia . ' es un destino ideal para familias con niños durante ' . $evento_proximo . '. Las rutas de senderismo de dificultad baja, los centros de interpretación y los espacios naturales hacen de esta provincia un lugar perfecto para el turismo familiar.',
                ],
            ],
        ];
    }

    $faqPage = [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $faqMainEntity,
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
