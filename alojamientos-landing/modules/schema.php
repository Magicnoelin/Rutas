<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  SCHEMA.ORG JSON-LD — Landings de Alojamientos
 *  Tipos: CollectionPage + BreadcrumbList + ItemList (con LodgingBusiness por item)
 * ════════════════════════════════════════════════════════════════════════════
 *
 *  Llamada: renderLandingSchema($context)
 *  $context debe contener:
 *    canonical, page_title, page_desc, lang_locale,
 *    province_label, filter_label,
 *    stats (total, avg_price), items (array de alojamientos)
 */

function renderLandingSchema(array $ctx): void
{
    $canonical    = $ctx['canonical']     ?? '';
    $page_title   = $ctx['page_title']    ?? '';
    $page_desc    = $ctx['page_desc']     ?? '';
    $lang_locale  = $ctx['lang_locale']   ?? 'es-ES';
    $province     = $ctx['province_label']?? '';
    $filter_label = $ctx['filter_label']  ?? 'Alojamientos rurales';
    $stats        = $ctx['stats']         ?? ['total' => 0, 'avg_price' => 0, 'towns' => 0];
    $items        = $ctx['items']         ?? [];
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
    ];

    // Formatear priceRange para CollectionPage (formato válido de schema.org)
    if (!empty($stats['avg_price']) && $stats['avg_price'] > 0) {
        $priceValue = number_format((float)$stats['avg_price'], 0, ',', '.');
        $priceRange = $lang === 'es'
            ? 'Desde ' . $priceValue . ' €'
            : 'From ' . $priceValue . ' €';
        $collectionPage['description'] .= ' | ' . $priceRange;
    }

    // ── 2. BreadcrumbList ────────────────────────────────────────────────────
    $bcLabels = [
        'es' => ['Inicio', 'Alojamientos rurales'],
        'en' => ['Home',   'Rural accommodation'],
        'fr' => ['Accueil','Hébergements ruraux'],
        'de' => ['Startseite', 'Ländliche Unterkünfte'],
        'zh' => ['首页',   '乡村住宿'],
    ];
    $bcLabel = $bcLabels[$lang] ?? $bcLabels['es'];

    // Prefijo de idioma para las URLs del breadcrumb (vacío en español)
    $langPrefix = ($lang !== 'es') ? '/' . $lang : '';

    // Dinamizar segundo nivel del breadcrumb: usar filtro si existe, sino "turismo-rural"
    $bcLevel2Name = !empty($filter_label) && $filter_label !== 'Alojamientos rurales'
        ? $filter_label
        : $bcLabel[1];
    $bcLevel2Url = !empty($filter_label) && $filter_label !== 'Alojamientos rurales'
        ? 'https://rutasrurales.io' . $langPrefix . '/alojamientos/' . ($ctx['slug'] ?? 'turismo-rural')
        : 'https://rutasrurales.io' . $langPrefix . '/alojamientos/turismo-rural';

    $breadcrumb = [
        '@type' => 'BreadcrumbList',
        '@id'   => $canonical . '#breadcrumb',
        'itemListElement' => [ 
            ['@type' => 'ListItem', 'position' => 1, 'name' => $bcLabel[0],   'item' => 'https://rutasrurales.io' . $langPrefix . '/'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $bcLevel2Name, 'item' => $bcLevel2Url],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $filter_label . (!empty($province) ? ' · ' . $province : ''), 'item' => $canonical],
        ],
    ];

    // ── 3. ItemList con LodgingBusiness embebido ──────────────────────────────
    $listElements = [];
    foreach ($items as $idx => $alo) {
        $acanonical  = 'https://rutasrurales.io/alojamiento/' . ($alo['slug'] ?? '');
        $imageUrl    = $alo['photo_url'] ?? 'https://rutasrurales.io/menu_images/turismo_rural.webp';

        // Tipo Schema más específico
        $catLower    = strtolower($alo['category_name'] ?? $alo['accommodation_type'] ?? '');
        $schemaType  = 'LodgingBusiness';
        if (str_contains($catLower, 'hotel'))              $schemaType = 'Hotel';
        elseif (str_contains($catLower, 'hostal') ||
                str_contains($catLower, 'hostel'))         $schemaType = 'Hostel';
        elseif (str_contains($catLower, 'bed') ||
                str_contains($catLower, 'b&b'))            $schemaType = 'BedAndBreakfast';

        $lodging = [
            '@type'       => $schemaType,
            '@id'         => $acanonical . '#lodging',
            'name'        => $alo['name'],
            'url'         => $acanonical,
            'image'       => $imageUrl,
            'address'     => [
                '@type'           => 'PostalAddress',
                'addressLocality' => $alo['municipality'] ?? '',
                'addressRegion'   => $alo['province']    ?? '',
                'addressCountry'  => 'ES',
            ],
        ];

        // Validar que price > 0 antes de renderizar offers
        $priceValue = !empty($alo['price_per_night']) ? (float)$alo['price_per_night'] : 0;
        if ($priceValue > 0) {
            // priceRange con formato válido (solo número, sin texto adicional)
            $lodging['priceRange'] = number_format($priceValue, 0, ',', '.') . '€';
            $lodging['offers']     = [
                '@type'         => 'Offer',
                'price'         => $priceValue,
                'priceCurrency' => 'EUR',
                'priceValidUntil' => date('Y-12-31'),
                'availability'  => 'https://schema.org/InStock',
                'url'           => $acanonical,
            ];
        }

        if (!empty($alo['latitude']) && !empty($alo['longitude'])) {
            $lodging['geo'] = [
                '@type'     => 'GeoCoordinates',
                'latitude'  => (float)$alo['latitude'],
                'longitude' => (float)$alo['longitude'],
            ];
        }

        if (!empty($alo['capacity']) && $alo['capacity'] > 0) {
            $lodging['occupancy'] = ['@type' => 'QuantitativeValue', 'value' => (int)$alo['capacity'], 'maxValue' => (int)$alo['capacity'], 'unitCode' => 'C62'];
        }

        // Amenities desde amenities_arr
        $amenFeatures = [];
        if (!empty($alo['pet_friendly']))          $amenFeatures[] = ['@type'=>'LocationFeatureSpecification','name'=>'Admite mascotas','value'=>true];
        if (!empty($alo['wifi']))                   $amenFeatures[] = ['@type'=>'LocationFeatureSpecification','name'=>'WiFi gratuito','value'=>true];
        if (!empty($alo['suitable_for_children']))  $amenFeatures[] = ['@type'=>'LocationFeatureSpecification','name'=>'Apto para niños','value'=>true];
        if (!empty($alo['kitchen_available']))       $amenFeatures[] = ['@type'=>'LocationFeatureSpecification','name'=>'Cocina disponible','value'=>true];
        if (!empty($amenFeatures)) $lodging['amenityFeature'] = $amenFeatures;
        if (isset($alo['pet_friendly']))             $lodging['petsAllowed'] = (bool)(int)$alo['pet_friendly'];

        $listElements[] = [
            '@type'    => 'ListItem',
            'position' => $idx + 1,
            'item'     => $lodging,
        ];
    }

    // Usar count($items) para numberOfItems (items reales en esta página, no total BD)
    $itemList = [
        '@type'           => 'ItemList',
        '@id'             => $canonical . '#itemlist',
        'name'            => $page_title,
        'description'     => $page_desc,
        'url'             => $canonical,
        'numberOfItems'   => count($items),
        'itemListElement' => $listElements,
    ];

    // ── 4. WebSite (solo una vez, para SiteLinks Searchbox señal) ────────────
    $website = [
        '@type' => 'WebSite',
        '@id'   => 'https://rutasrurales.io/#website',
        'url'   => 'https://rutasrurales.io/',
        'name'  => 'rutasrurales.io',
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => 'https://rutasrurales.io/alojamientos-turisticos?q={search_term_string}',
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
