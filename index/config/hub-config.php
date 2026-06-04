<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  HUB-CONFIG.PHP — Configuración centralizada del Index Hub de Autoridad
 *  rutasrurales.io/
 *
 *  Contiene:
 *    - Mapa de idiomas y disponibilidad de traducciones por vertical
 *    - Provincias para el hub (subconjunto curado del mapa completo)
 *    - Combinaciones de alojamientos destacadas (filtro × provincia)
 *    - Combinaciones de eventos destacadas (filtro × provincia)
 *    - Temporadas para el hub de eventos
 * ════════════════════════════════════════════════════════════════════════════
 */

// ── IDIOMAS SOPORTADOS ────────────────────────────────────────────────────────
// 'available' indica qué verticales tienen traducción completa en ese idioma
// Esto controla el comportamiento de fallback en los hubs
const HUB_LANGS = [
    'es' => [
        'label'    => 'Español',
        'flag'     => '🇪🇸',
        'flag_svg' => 'https://hatscripts.github.io/circle-flags/flags/es.svg',
        'locale'   => 'es-ES',
        'dir'      => 'ltr',
        'available' => ['alojamientos', 'eventos', 'lugares', 'actividades'],
    ],
    'en' => [
        'label'    => 'English',
        'flag'     => '🇬🇧',
        'flag_svg' => 'https://hatscripts.github.io/circle-flags/flags/gb.svg',
        'locale'   => 'en-GB',
        'dir'      => 'ltr',
        'available' => ['alojamientos', 'eventos'],
    ],
    'fr' => [
        'label'    => 'Français',
        'flag'     => '🇫🇷',
        'flag_svg' => 'https://hatscripts.github.io/circle-flags/flags/fr.svg',
        'locale'   => 'fr-FR',
        'dir'      => 'ltr',
        'available' => ['alojamientos', 'eventos'],
    ],
    'de' => [
        'label'    => 'Deutsch',
        'flag'     => '🇩🇪',
        'flag_svg' => 'https://hatscripts.github.io/circle-flags/flags/de.svg',
        'locale'   => 'de-DE',
        'dir'      => 'ltr',
        'available' => ['alojamientos', 'eventos'],
    ],
    'zh' => [
        'label'    => '中文',
        'flag'     => '🇨🇳',
        'flag_svg' => 'https://hatscripts.github.io/circle-flags/flags/cn.svg',
        'locale'   => 'zh-CN',
        'dir'      => 'ltr',
        'available' => ['alojamientos', 'eventos'],
    ],
];

// ── PROVINCIAS HUB (curadas con icono e imagen representativa) ────────────────
const HUB_PROVINCIAS = [
    'soria'       => ['label'=>'Soria',       'emoji'=>'🌲', 'region'=>'Castilla y León'],
    'zamora'      => ['label'=>'Zamora',      'emoji'=>'🦌', 'region'=>'Castilla y León'],
    'leon'        => ['label'=>'León',        'emoji'=>'🏔️', 'region'=>'Castilla y León'],
    'burgos'      => ['label'=>'Burgos',      'emoji'=>'⚔️', 'region'=>'Castilla y León'],
    'valladolid'  => ['label'=>'Valladolid',  'emoji'=>'🍷', 'region'=>'Castilla y León'],
    'salamanca'   => ['label'=>'Salamanca',   'emoji'=>'🏛️', 'region'=>'Castilla y León'],
    'palencia'    => ['label'=>'Palencia',    'emoji'=>'🌊', 'region'=>'Castilla y León'],
    'segovia'     => ['label'=>'Segovia',     'emoji'=>'🏰', 'region'=>'Castilla y León'],
    'avila'       => ['label'=>'Ávila',       'emoji'=>'🧱', 'region'=>'Castilla y León'],
    'guadalajara' => ['label'=>'Guadalajara', 'emoji'=>'🌳', 'region'=>'Castilla-La Mancha'],
    'cuenca'      => ['label'=>'Cuenca',      'emoji'=>'🪨', 'region'=>'Castilla-La Mancha'],
    'ourense'     => ['label'=>'Ourense',     'emoji'=>'♨️', 'region'=>'Galicia'],
];

// ── FILTROS ALOJAMIENTOS DESTACADOS (para el hub visual) ────────────────────
// Ordenados por popularidad SEO
const HUB_FILTROS_ALO = [
    'casas-rurales'        => ['icon'=>'🏡', 'es'=>'Casas rurales',         'en'=>'Rural houses',     'fr'=>'Maisons rurales',       'de'=>'Landhäuser',          'zh'=>'乡村民宿'],
    'con-chimenea'         => ['icon'=>'🔥', 'es'=>'Con chimenea',          'en'=>'With fireplace',   'fr'=>'Avec cheminée',         'de'=>'Mit Kamin',           'zh'=>'带壁炉'],
    'con-piscina'          => ['icon'=>'🏊', 'es'=>'Con piscina',           'en'=>'With pool',        'fr'=>'Avec piscine',          'de'=>'Mit Pool',            'zh'=>'带游泳池'],
    'con-mascotas'         => ['icon'=>'🐾', 'es'=>'Para mascotas',         'en'=>'Pet-friendly',     'fr'=>'Pour animaux',          'de'=>'Haustierfreundlich',  'zh'=>'宠物友好'],
    'romantico'            => ['icon'=>'💑', 'es'=>'Románticos',            'en'=>'Romantic',         'fr'=>'Romantiques',           'de'=>'Romantisch',          'zh'=>'浪漫'],
    'con-jacuzzi'          => ['icon'=>'♨️', 'es'=>'Con jacuzzi',          'en'=>'With jacuzzi',     'fr'=>'Avec jacuzzi',          'de'=>'Mit Jacuzzi',         'zh'=>'带按摩浴缸'],
    'grandes-grupos'       => ['icon'=>'👥', 'es'=>'Grupos grandes',        'en'=>'Large groups',     'fr'=>'Grands groupes',        'de'=>'Für Gruppen',         'zh'=>'大团体适用'],
    'con-cocina'           => ['icon'=>'🍳', 'es'=>'Con cocina equipada',   'en'=>'Full kitchen',     'fr'=>'Cuisine équipée',       'de'=>'Mit Küche',           'zh'=>'含厨房'],
    'turismo-rural'        => ['icon'=>'🌿', 'es'=>'Turismo rural',         'en'=>'Rural tourism',    'fr'=>'Tourisme rural',        'de'=>'Landurlaub',          'zh'=>'乡村旅游'],
    'baratos'              => ['icon'=>'💰', 'es'=>'Económicos',            'en'=>'Budget-friendly',  'fr'=>'Économiques',           'de'=>'Günstig',             'zh'=>'经济实惠'],
    'apartamentos-rurales' => ['icon'=>'🏠', 'es'=>'Apartamentos rurales',  'en'=>'Rural apartments', 'fr'=>'Appts ruraux',          'de'=>'Landapartments',      'zh'=>'乡村公寓'],
    'para-ninos'           => ['icon'=>'👨‍👩‍👧', 'es'=>'Para niños',    'en'=>'Child-friendly',   'fr'=>'Pour enfants',          'de'=>'Kinderfreundlich',    'zh'=>'亲子友好'],
];

// ── COMBINACIONES ESTRELLA (alojamientos) — las 20 más buscadas ──────────────
// Formato: [filtro, provincia] → genera /alojamientos/{filtro}-{provincia}
const HUB_COMBIS_ALO = [
    ['casas-rurales', 'con-chimenea', 'soria'],
    ['casas-rurales', 'con-chimenea', 'zamora'],
    ['casas-rurales', 'con-chimenea', 'leon'],
    ['casas-rurales', 'con-piscina',  'soria'],
    ['casas-rurales', 'con-piscina',  'zamora'],
    ['turismo-rural', 'con-mascotas', 'soria'],
    ['turismo-rural', 'con-mascotas', 'burgos'],
    ['turismo-rural', 'con-mascotas', 'leon'],
    ['casas-rurales', 'romantico',    'valladolid'],
    ['casas-rurales', 'romantico',    'salamanca'],
    ['casas-rurales', 'grandes-grupos', 'soria'],
    ['casas-rurales', 'grandes-grupos', 'burgos'],
    ['turismo-rural', 'baratos',      'soria'],
    ['turismo-rural', 'baratos',      'zamora'],
    ['casas-rurales', 'para-ninos',   'leon'],
    ['casas-rurales', 'para-ninos',   'soria'],
    ['turismo-rural', 'con-jacuzzi',  'salamanca'],
    ['turismo-rural', 'con-jacuzzi',  'segovia'],
    ['casas-rurales', 'con-cocina',   'valladolid'],
    ['turismo-rural', 'con-chimenea', 'avila'],
];

// ── FILTROS EVENTOS DESTACADOS ────────────────────────────────────────────────
const HUB_FILTROS_EVT = [
    'musica'       => ['icon'=>'🎵', 'es'=>'Música',                 'en'=>'Music',            'fr'=>'Musique',              'de'=>'Musik',                'zh'=>'音乐'],
    'gastronomia'  => ['icon'=>'🍷', 'es'=>'Gastronomía',            'en'=>'Food & wine',      'fr'=>'Gastronomie',          'de'=>'Gastronomie',          'zh'=>'美食'],
    'tradiciones'  => ['icon'=>'🎪', 'es'=>'Tradiciones',            'en'=>'Traditions',       'fr'=>'Traditions',           'de'=>'Traditionen',          'zh'=>'传统'],
    'teatro'       => ['icon'=>'🎭', 'es'=>'Teatro y danza',         'en'=>'Theatre & dance',  'fr'=>'Théâtre et danse',     'de'=>'Theater & Tanz',       'zh'=>'戏剧'],
    'exposiciones' => ['icon'=>'🎨', 'es'=>'Exposiciones y arte',    'en'=>'Art & exhibitions','fr'=>'Art et expositions',   'de'=>'Kunst',                'zh'=>'艺术展览'],
    'gratuitos'    => ['icon'=>'🎁', 'es'=>'Eventos gratuitos',      'en'=>'Free events',      'fr'=>'Gratuits',             'de'=>'Kostenlos',            'zh'=>'免费活动'],
    'mercados'     => ['icon'=>'🛖', 'es'=>'Mercados medievales',    'en'=>'Medieval markets', 'fr'=>'Marchés médiévaux',    'de'=>'Mittelaltermärkte',    'zh'=>'中世纪集市'],
    'infantil'     => ['icon'=>'👨‍👩‍👧', 'es'=>'Familiar',     'en'=>'Family',           'fr'=>'Famille',              'de'=>'Familie',              'zh'=>'家庭活动'],
    'verano'       => ['icon'=>'☀️', 'es'=>'Verano',                 'en'=>'Summer',           'fr'=>'Été',                  'de'=>'Sommer',               'zh'=>'夏季'],
    'otono'        => ['icon'=>'🍂', 'es'=>'Otoño',                  'en'=>'Autumn',           'fr'=>'Automne',              'de'=>'Herbst',               'zh'=>'秋季'],
];

// ── COMBINACIONES ESTRELLA (eventos) — las más relevantes ────────────────────
const HUB_COMBIS_EVT = [
    ['musica',      'soria'],
    ['musica',      'burgos'],
    ['musica',      'salamanca'],
    ['gastronomia', 'valladolid'],
    ['gastronomia', 'soria'],
    ['gastronomia', 'zamora'],
    ['tradiciones', 'zamora'],
    ['tradiciones', 'burgos'],
    ['tradiciones', 'avila'],
    ['teatro',      'salamanca'],
    ['teatro',      'valladolid'],
    ['exposiciones','segovia'],
    ['exposiciones','salamanca'],
    ['gratuitos',   'soria'],
    ['gratuitos',   'leon'],
    ['mercados',    'segovia'],
    ['mercados',    'palencia'],
    ['infantil',    'burgos'],
    ['verano',      'soria'],
    ['verano',      'salamanca'],
];

// ── MESES POR TEMPORADA (para título dinámico del hub de eventos) ─────────────
const HUB_TEMPORADAS = [
    'invierno'  => [12, 1, 2],
    'primavera' => [3, 4, 5],
    'verano'    => [6, 7, 8],
    'otono'     => [9, 10, 11],
];

/**
 * Devuelve la temporada actual basada en el mes del servidor.
 */
function getTemporadaActual(): string {
    $mes = (int)date('n');
    foreach (HUB_TEMPORADAS as $t => $meses) {
        if (in_array($mes, $meses, true)) return $t;
    }
    return 'primavera';
}

/**
 * Construye la URL de una landing de alojamiento con soporte multilingüe y fallback.
 *
 * @param  string $slug  Slug de la landing (ej: casas-rurales-con-chimenea-soria)
 * @param  string $lang  Idioma activo
 * @param  string $vertical Vertical: 'alojamientos' | 'eventos'
 * @return string URL absoluta
 */
function hubUrl(string $slug, string $lang, string $vertical = 'alojamientos'): string {
    $base = 'https://rutasrurales.io';
    // Comprobamos si el idioma tiene traducción para esta vertical
    $hasTranslation = in_array($vertical, HUB_LANGS[$lang]['available'] ?? [], true);
    if ($lang === 'es' || $hasTranslation) {
        return $lang === 'es'
            ? "{$base}/{$vertical}/{$slug}"
            : "{$base}/{$lang}/{$vertical}/{$slug}";
    }
    // Fallback elegante: versión española
    return "{$base}/{$vertical}/{$slug}";
}

/**
 * Construye el slug de una combinación de alojamiento [filtro1, filtro2?, provincia].
 */
function buildAloSlug(array $parts): string {
    return implode('-', $parts);
}
