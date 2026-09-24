<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  SCHEMA.ORG JSON-LD — Landings de Lugares de Interés
 *  Tipos: WebSite + CollectionPage + BreadcrumbList + ItemList
 *         (con TouristAttraction + GeoCoordinates + ImageObject por ítem)
 * ════════════════════════════════════════════════════════════════════════════
 *
 *  Uso: renderLugaresLandingSchema($context)
 *  $context: canonical, page_title, page_desc, lang, lang_locale,
 *    mode ('categoria'|'provincia'), bc_label, cat_name, province,
 *    slug, base_domain, path_prefix,
 *    items (array con id,slug,name,municipality,province,
 *           short_description,photo1,entry_fee,latitude,longitude)
 *
 *  Rendimiento: json_encode() sobre $items ya en memoria → < 1 ms
 *  Sin consultas extra a BD ni peticiones de red adicionales.
 * ════════════════════════════════════════════════════════════════════════════
 */

if (!function_exists('renderLugaresLandingSchema')) {

function renderLugaresLandingSchema(array $ctx): void
{
    $canonical   = $ctx['canonical']    ?? '';
    $page_title  = $ctx['page_title']   ?? '';
    $page_desc   = $ctx['page_desc']    ?? '';
    $lang        = $ctx['lang']         ?? 'es';
    $lang_locale = $ctx['lang_locale']  ?? 'es-ES';
    $mode        = $ctx['mode']         ?? 'categoria';
    $bc_label    = $ctx['bc_label']     ?? '';
    $slug        = $ctx['slug']         ?? '';
    $base_domain = $ctx['base_domain']  ?? 'https://rutasrurales.io';
    $path_prefix = $ctx['path_prefix']  ?? '';
    $items       = $ctx['items']        ?? [];

    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;

    // ── Helper: texto limpio para JSON-LD ────────────────────────────────────
    $clean = static function (string $txt): string {
        $txt = strip_tags($txt);
        $txt = html_entity_decode($txt, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return preg_replace('/\s+/', ' ', trim($txt));
    };

    $clean_title = $clean($page_title);
    $clean_desc  = $clean($page_desc);
    $lang_prefix = ($lang !== 'es') ? '/' . $lang : '';

    // ── touristType según slug de categoría ──────────────────────────────────
    $tourist_type_map = [
        'castillos'    => 'Amantes del patrimonio histórico medieval',
        'iglesias'     => 'Turismo religioso y patrimonial',
        'monasterios'  => 'Turismo religioso y cultural',
        'patrimonio'   => 'Turismo cultural y patrimonial',
        'monumentos'   => 'Turismo cultural y monumental',
        'museos'       => 'Turismo cultural y museístico',
        'bodegas'      => 'Turismo enológico y gastronómico',
        'restauracion' => 'Turismo gastronómico',
        'restaurantes' => 'Turismo gastronómico',
        'gastronomia'  => 'Turismo gastronómico',
        'naturaleza'   => 'Ecoturismo y turismo de naturaleza',
        'parques'      => 'Ecoturismo y turismo familiar',
        'miradores'    => 'Turismo de paisaje y fotografía',
        'rutas'        => 'Senderismo y turismo activo',
        'termas'       => 'Turismo de salud y bienestar',
        'mercados'     => 'Turismo de experiencias locales',
    ];
    $tourist_type = $tourist_type_map[$slug] ?? null;

    // ── 1. WebSite (SiteLinks Searchbox signal) ──────────────────────────────
    $website = [
        '@type'           => 'WebSite',
        '@id'             => $base_domain . '/#website',
        'url'             => $base_domain . '/',
        'name'            => 'Rutas Rurales',
        'description'     => 'Portal de turismo rural: lugares de interés, alojamientos, eventos y actividades en la España rural',
        'inLanguage'      => $lang_locale,
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => [
                '@type'       => 'EntryPoint',
                'urlTemplate' => $base_domain . '/rutas.php?q={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];

    // ── 2. BreadcrumbList multiidioma ────────────────────────────────────────
    $bc_labels = [
        'es' => ['Inicio',     'Lugares de interés'],
        'en' => ['Home',       'Places of interest'],
        'fr' => ['Accueil',    'Lieux d\'intérêt'],
        'de' => ['Startseite', 'Sehenswürdigkeiten'],
        'zh' => ['首页',        '景点'],
    ];
    $bc = $bc_labels[$lang] ?? $bc_labels['es'];

    $breadcrumb = [
        '@type'           => 'BreadcrumbList',
        '@id'             => $canonical . '#breadcrumb',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => $bc[0], 'item' => $base_domain . $lang_prefix . '/'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $bc[1], 'item' => $base_domain . $lang_prefix . '/lugares/'],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $bc_label, 'item' => $canonical],
        ],
    ];

    // ── 3. CollectionPage ────────────────────────────────────────────────────
    $collectionPage = [
        '@type'       => 'CollectionPage',
        '@id'         => $canonical . '#collection',
        'url'         => $canonical,
        'name'        => $clean_title,
        'description' => $clean_desc,
        'inLanguage'  => $lang_locale,
        'isPartOf'    => ['@id' => $base_domain . '/#website'],
        'breadcrumb'  => ['@id' => $canonical . '#breadcrumb'],
        'mainEntity'  => ['@id' => $canonical . '#itemlist'],
        'publisher'   => [
            '@type' => 'Organization',
            '@id'   => $base_domain . '/#organization',
            'name'  => 'Rutas Rurales',
            'url'   => $base_domain . '/',
            'logo'  => [
                '@type' => 'ImageObject',
                'url'   => $base_domain . '/menu_images/Logo%20transparente.webp',
            ],
        ],
        // SpeakableSpecification → señal para Google SGE / Assistant
        'speakable'   => [
            '@type'       => 'SpeakableSpecification',
            'cssSelector' => ['#ll-h1', '.lnd-hero__sub'],
        ],
    ];

    // ── 4. ItemList + TouristAttraction por cada lugar ───────────────────────
    $listElements = [];

    foreach ($items as $idx => $place) {
        $place_slug   = $place['slug']              ?? '';
        $place_name   = $clean($place['name']       ?? '');
        $place_desc   = $clean($place['short_description'] ?? '');
        $municipality = $place['municipality']      ?? '';
        $prov         = $place['province']          ?? '';
        $photo1       = $place['photo1']            ?? '';
        $entry_fee    = isset($place['entry_fee'])  ? (float)$place['entry_fee'] : 0.0;
        $lat          = (isset($place['latitude'])  && $place['latitude']  !== '') ? (float)$place['latitude']  : null;
        $lng          = (isset($place['longitude']) && $place['longitude'] !== '') ? (float)$place['longitude'] : null;

        if (empty($place_name)) continue; // saltar ítems vacíos

        $place_url = $base_domain . $lang_prefix . '/lugar/' . $place_slug;

        $attraction = [
            '@type' => 'TouristAttraction',
            '@id'   => $place_url . '#lugar',
            'url'   => $place_url,
            'name'  => $place_name,
        ];

        if (!empty($place_desc)) {
            $attraction['description'] = $place_desc;
        }

        // ImageObject — foto principal
        if (!empty($photo1)) {
            $photo_url = preg_match('/^https?:\/\//', $photo1)
                ? $photo1
                : $base_domain . '/' . ltrim($photo1, '/');
            $attraction['image'] = [
                '@type'      => 'ImageObject',
                'url'        => $photo_url,
                'contentUrl' => $photo_url,
                'name'       => $place_name,
            ];
        }

        // GeoCoordinates — solo si existen coordenadas válidas
        if ($lat !== null && $lng !== null && !($lat === 0.0 && $lng === 0.0)) {
            $attraction['geo'] = [
                '@type'     => 'GeoCoordinates',
                'latitude'  => $lat,
                'longitude' => $lng,
            ];
        }

        // PostalAddress
        if (!empty($municipality) || !empty($prov)) {
            $attraction['address'] = array_filter([
                '@type'           => 'PostalAddress',
                'addressLocality' => $municipality,
                'addressRegion'   => $prov,
                'addressCountry'  => 'ES',
            ]);
        }

        // containedInPlace — jerarquía geográfica para Google
        if (!empty($municipality) && !empty($prov)) {
            $attraction['containedInPlace'] = [
                '@type' => 'Place',
                'name'  => $municipality . ', ' . $prov . ', España',
            ];
        }

        // Accesibilidad económica (entrada libre vs. de pago)
        $attraction['isAccessibleForFree'] = ($entry_fee === 0.0);

        // touristType — perfil de viajero objetivo (solo en páginas de categoría)
        if ($mode === 'categoria' && !empty($tourist_type)) {
            $attraction['touristType'] = $tourist_type;
        }

        // Schema.org spec: ListItem referencia la entidad solo dentro de 'item'.
        // El 'url' aquí arriba es redundante y puede confundir el parser de Google.
        $listElements[] = [
            '@type'    => 'ListItem',
            'position' => $idx + 1,
            'item'     => $attraction,   // la url ya vive en $attraction['url'] y @id
        ];
    }

    // ── 5. ItemList contenedor ───────────────────────────────────────────────
    $itemList = [
        '@type'           => 'ItemList',
        '@id'             => $canonical . '#itemlist',
        'name'            => $clean_title,
        'description'     => $clean_desc,
        'url'             => $canonical,
        'numberOfItems'   => count($listElements),
        'itemListOrder'   => 'https://schema.org/ItemListOrderAscending',
        'itemListElement' => $listElements,
    ];

    // ── 6. @graph final ──────────────────────────────────────────────────────
    $graph = [
        '@context' => 'https://schema.org',
        '@graph'   => [$website, $collectionPage, $breadcrumb, $itemList],
    ];

    echo "\n<!-- ═══ Schema.org JSON-LD: ItemList + TouristAttraction + GeoCoordinates ═══ -->\n";
    echo '<script type="application/ld+json">' . "\n";
    echo json_encode($graph, $flags);
    echo "\n</script>\n\n";
}

} // end if (!function_exists)
