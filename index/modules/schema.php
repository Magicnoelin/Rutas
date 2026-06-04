<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  SCHEMA.PHP — JSON-LD estructurado para el Index Hub
 *  Incluye: WebSite (SearchAction), Organization, BreadcrumbList
 * ════════════════════════════════════════════════════════════════════════════
 *
 * @param array $ctx  Contexto del Index (lang, t, stats)
 */
function renderHubSchema(array $ctx): void {
    $lang   = $ctx['lang'];
    $base   = 'https://rutasrurales.io';
    $canon  = $lang === 'es' ? $base . '/' : $base . '/' . $lang . '/';

    // ── WebSite + SearchAction (Sitelinks Search Box) ─────────────────────────
    $website = [
        '@context'        => 'https://schema.org',
        '@type'           => 'WebSite',
        'name'            => 'Rutas Rurales',
        'alternateName'   => 'rutasrurales.io',
        'url'             => $base . '/',
        'description'     => $ctx['t']['meta_desc'] ?? '',
        'inLanguage'      => $ctx['t']['lang_locale'] ?? 'es-ES',
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => [
                '@type'       => 'EntryPoint',
                'urlTemplate' => $base . '/alojamientos-turisticos?q={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];

    // ── Organization ─────────────────────────────────────────────────────────
    $organization = [
        '@context'       => 'https://schema.org',
        '@type'          => 'Organization',
        'name'           => 'Rutas Rurales',
        'url'            => $base . '/',
        'logo'           => [
            '@type' => 'ImageObject',
            'url'   => $base . '/menu_images/Logo%20transparente.webp',
            'width' => 512,
            'height'=> 512,
        ],
        'sameAs' => [
            'https://twitter.com/rutas_rurales',
        ],
        'contactPoint' => [
            '@type'             => 'ContactPoint',
            'telephone'         => '+34-605-249-696',
            'contactType'       => 'customer support',
            'availableLanguage' => ['Spanish', 'English'],
        ],
    ];

    // ── TouristDestination (entidad principal) ────────────────────────────────
    $destination = [
        '@context'    => 'https://schema.org',
        '@type'       => 'TouristDestination',
        'name'        => 'Castilla y León, España',
        'url'         => $base . '/',
        'description' => 'Región de turismo rural auténtico con castillos medievales, naturaleza virgen y gastronomía tradicional.',
        'geo'         => [
            '@type'     => 'GeoCoordinates',
            'latitude'  => 41.6528,
            'longitude' => -4.7239,
        ],
        'touristType' => [
            'Rural tourism',
            'Cultural tourism',
            'Gastronomy tourism',
            'Wine tourism',
        ],
    ];

    echo '<script type="application/ld+json">' . "\n";
    echo json_encode($website, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    echo "\n</script>\n";

    echo '<script type="application/ld+json">' . "\n";
    echo json_encode($organization, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    echo "\n</script>\n";

    echo '<script type="application/ld+json">' . "\n";
    echo json_encode($destination, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    echo "\n</script>\n";
}
