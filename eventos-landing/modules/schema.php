<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  SCHEMA.ORG JSON-LD — Landings de Eventos Culturales
 *  Tipos: CollectionPage + BreadcrumbList + ItemList (con Event por item)
 * ════════════════════════════════════════════════════════════════════════════
 *
 *  Llamada: renderEventosLandingSchema($context)
 *  $context debe contener:
 *    canonical, page_title, page_desc, lang_locale,
 *    province_label, filter_label,
 *    stats (total, free_count), items (array de eventos)
 */

function renderEventosLandingSchema(array $ctx): void
{
    $canonical    = $ctx['canonical']      ?? '';
    $page_title   = $ctx['page_title']     ?? '';
    $page_desc    = $ctx['page_desc']      ?? '';
    $lang_locale  = $ctx['lang_locale']    ?? 'es-ES';
    $province     = $ctx['province_label'] ?? '';
    $filter_label = $ctx['filter_label']   ?? 'Eventos culturales';
    $stats        = $ctx['stats']          ?? ['total' => 0, 'free_count' => 0, 'towns' => 0];
    $items        = $ctx['items']          ?? [];
    $lang         = substr($lang_locale, 0, 2);

    // ── 1. CollectionPage ────────────────────────────────────────────────────
    $collectionPage = [
        '@type'       => 'CollectionPage',
        '@id'         => $canonical . '#collection',
        'url'         => $canonical,
        'name'        => $page_title,
        'description' => $page_desc,
        'inLanguage'  => $lang_locale,
        'isPartOf'    => ['@id' => 'https://rutasrurales.io/#website'],
        'breadcrumb'  => ['@id' => $canonical . '#breadcrumb'],
        'mainEntity'  => ['@id' => $canonical . '#itemlist'],
        'about'       => [
            '@type' => 'Thing',
            'name'  => $filter_label . (!empty($province) ? ' · ' . $province : ''),
        ],
    ];

    // ── 2. BreadcrumbList ────────────────────────────────────────────────────
    $bcLabels = [
        'es' => ['Inicio', 'Eventos culturales'],
        'en' => ['Home',   'Cultural events'],
        'fr' => ['Accueil','Événements culturels'],
        'de' => ['Startseite', 'Kulturveranstaltungen'],
        'zh' => ['首页',   '文化活动'],
    ];
    $bcLabel = $bcLabels[$lang] ?? $bcLabels['es'];

    $breadcrumb = [
        '@type' => 'BreadcrumbList',
        '@id'   => $canonical . '#breadcrumb',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => $bcLabel[0], 'item' => 'https://rutasrurales.io/'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $bcLabel[1], 'item' => 'https://rutasrurales.io/eventos-culturales'],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $filter_label . (!empty($province) ? ' · ' . $province : ''), 'item' => $canonical],
        ],
    ];

    // ── 3. ItemList con Event embebido ────────────────────────────────────────
    $listElements = [];
    foreach ($items as $idx => $ev) {
        $evCanonical = 'https://rutasrurales.io/evento/' . ($ev['slug'] ?? '');
        $imageUrl    = $ev['photo_url'] ?? 'https://rutasrurales.io/menu_images/og-default.jpg';

        $event = [
            '@type'     => 'Event',
            '@id'       => $evCanonical . '#event',
            'name'      => $ev['name'] ?? '',
            'url'       => $evCanonical,
            'image'     => $imageUrl,
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
        ];

        // Fechas
        if (!empty($ev['start_date'])) {
            $event['startDate'] = $ev['start_date'];
        }
        if (!empty($ev['end_date'])) {
            $event['endDate'] = $ev['end_date'];
        }

        // Descripción
        if (!empty($ev['short_description'])) {
            $event['description'] = mb_substr(strip_tags($ev['short_description']), 0, 200);
        }

        // Localización
        $locationName = $ev['venue_name'] ?? $ev['municipality'] ?? $province;
        if (!empty($locationName)) {
            $location = [
                '@type' => 'Place',
                'name'  => $locationName,
            ];
            if (!empty($ev['municipality'])) {
                $location['address'] = [
                    '@type'           => 'PostalAddress',
                    'addressLocality' => $ev['municipality'],
                    'addressRegion'   => $ev['province'] ?? $province,
                    'addressCountry'  => 'ES',
                ];
            }
            if (!empty($ev['latitude']) && !empty($ev['longitude'])) {
                $location['geo'] = [
                    '@type'     => 'GeoCoordinates',
                    'latitude'  => (float)$ev['latitude'],
                    'longitude' => (float)$ev['longitude'],
                ];
            }
            $event['location'] = $location;
        }

        // Precio / oferta
        if (!empty($ev['is_free']) && $ev['is_free']) {
            $event['isAccessibleForFree'] = true;
            $event['offers'] = [
                '@type'         => 'Offer',
                'price'         => '0',
                'priceCurrency' => 'EUR',
                'availability'  => 'https://schema.org/InStock',
                'url'           => $evCanonical,
            ];
        } elseif (!empty($ev['ticket_price']) && $ev['ticket_price'] > 0) {
            $event['isAccessibleForFree'] = false;
            $event['offers'] = [
                '@type'         => 'Offer',
                'price'         => (float)$ev['ticket_price'],
                'priceCurrency' => 'EUR',
                'availability'  => 'https://schema.org/InStock',
                'url'           => $evCanonical,
            ];
        }

        // Organizador
        if (!empty($ev['organizer'])) {
            $event['organizer'] = [
                '@type' => 'Organization',
                'name'  => $ev['organizer'],
            ];
        }

        $listElements[] = [
            '@type'    => 'ListItem',
            'position' => $idx + 1,
            'item'     => $event,
        ];
    }

    $itemList = [
        '@type'           => 'ItemList',
        '@id'             => $canonical . '#itemlist',
        'name'            => $page_title,
        'description'     => $page_desc,
        'url'             => $canonical,
        'numberOfItems'   => $stats['total'],
        'itemListElement' => $listElements,
    ];

    // ── 4. WebSite (señal SiteLinks Searchbox) ────────────────────────────────
    $website = [
        '@type' => 'WebSite',
        '@id'   => 'https://rutasrurales.io/#website',
        'url'   => 'https://rutasrurales.io/',
        'name'  => 'rutasrurales.io',
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => 'https://rutasrurales.io/eventos-culturales?q={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ],
    ];

    // ── Graph final ──────────────────────────────────────────────────────────
    $graph = [
        '@context' => 'https://schema.org',
        '@graph'   => [$website, $collectionPage, $breadcrumb, $itemList],
    ];

    echo '<script type="application/ld+json">'
        . json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        . '</script>' . "\n";
}
