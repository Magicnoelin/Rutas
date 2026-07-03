<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  SCHEMA.ORG JSON-LD — Landings de Eventos Culturales
 *  Tipos: CollectionPage + BreadcrumbList + ItemList (ListItem con name+url)
 *  NOTA: El schema Event detallado reside SOLO en cada página de detalle
 *        (/evento/{slug}) para evitar canibalización de intenciones con Google.
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
            ['@type' => 'ListItem', 'position' => 2, 'name' => $bcLabel[1], 'item' => 'https://rutasrurales.io/eventos-culturales-paginacion.html'],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $filter_label . (!empty($province) ? ' · ' . $province : ''), 'item' => $canonical],
        ],
    ];

    // ── 3. ItemList con referencias simplificadas (sin Event embebido) ─────────
    //  Usamos ListItem con name + url para evitar canibalización con las páginas
    //  de detalle de cada evento, donde reside el schema Event completo.
    $listElements = [];
    foreach ($items as $idx => $ev) {
        $evCanonical = 'https://rutasrurales.io/evento/' . ($ev['slug'] ?? '');

        $listElements[] = [
            '@type'    => 'ListItem',
            'position' => $idx + 1,
            'name'     => $ev['name'] ?? '',
            'url'      => $evCanonical,
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
            'target'      => 'https://rutasrurales.io/eventos-culturales-paginacion.html?q={search_term_string}',
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
