<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  TRADUCCIONES DEL SISTEMA DE LANDINGS DE EVENTOS — 5 idiomas
 *  es · en · fr · de · zh
 * ════════════════════════════════════════════════════════════════════════════
 *
 *  Claves dinámicas (usar t() con {PROVINCE}, {FILTER_LABEL}, etc.):
 *    h1_template, meta_title, meta_desc, intro_p1, intro_p2
 */

const EVENTOS_I18N = [

    // ── ESPAÑOL ──────────────────────────────────────────────────────────────
    'es' => [
        'lang_code'   => 'es',
        'lang_locale' => 'es-ES',
        'dir'         => 'ltr',

        // Meta
        'meta_title'  => '{FILTER_LABEL} en {PROVINCE} — Agenda Cultural | rutasrurales.io',
        'meta_desc'   => 'Descubre los mejores {FILTER_LABEL_LOWER} en {PROVINCE}. Agenda cultural actualizada, {FILTER_FEATURE} y eventos únicos del turismo rural. Información completa y gratuita.',

        // Encabezados
        'h1_template'   => '{FILTER_LABEL} en {PROVINCE}',
        'h1_only_prov'  => 'Agenda cultural en {PROVINCE}',
        'h2_listing'    => 'Eventos disponibles en {PROVINCE}',
        'h2_semantico'  => 'Alojamientos y lugares de interés cerca de los eventos en {PROVINCE}',
        'h2_rutas'      => 'Rutas temáticas en {PROVINCE}',
        'h2_porque'     => '¿Por qué visitar {PROVINCE} para sus eventos culturales?',

        // Stats hero
        'stat_count'    => 'eventos',
        'stat_free'     => 'eventos gratuitos',
        'stat_towns'    => 'municipios con agenda',
        'stat_upcoming' => 'próximos eventos',

        // Breadcrumb
        'bc_home'       => 'Inicio',
        'bc_listings'   => 'Eventos culturales',

        // Tarjetas de evento
        'card_gratis'   => 'Entrada gratuita',
        'card_precio'   => 'Desde',
        'card_ver'      => 'Ver evento',
        'card_fecha'    => 'Fecha',
        'card_lugar'    => 'Lugar',
        'card_hasta'    => 'Hasta',

        // Badges de filtro activo
        'badge_free'    => '🎁 Gratuito',
        'badge_music'   => '🎵 Música',
        'badge_theatre' => '🎭 Teatro',
        'badge_art'     => '🎨 Arte',
        'badge_food'    => '🍷 Gastronomía',
        'badge_folk'    => '🎪 Folklore',
        'badge_market'  => '🛖 Mercado',
        'badge_family'  => '👨‍👩‍👧 Familiar',
        'badge_nature'  => '🌿 Naturaleza',

        // Cruce semántico (alojamientos + lugares)
        'h2_alojamientos'  => 'Dónde alojarse cerca de los eventos en {PROVINCE}',
        'semantic_places'  => 'Qué visitar en {PROVINCE}',
        'semantic_stays'   => 'Alojamientos rurales en {PROVINCE}',
        'semantic_routes'  => 'Rutas temáticas recomendadas',
        'semantic_intro'   => 'Los eventos culturales de {PROVINCE} son solo el principio. Descubre también dónde alojarte y qué visitar en los alrededores.',
        'semantic_cta_alo' => 'Ver todos los alojamientos',
        'semantic_cta_poi' => 'Ver todos los lugares de interés',
        'semantic_cta_rt'  => 'Ver todas las rutas',
        'entry_fee_free'   => 'Entrada gratuita',
        'price_per_night'  => '/noche',

        // Intro texto SEO
        'intro_p1' => 'Si buscas {FILTER_LABEL_LOWER} en {PROVINCE}, has encontrado la agenda cultural más completa. {PROVINCE_VIBE}.',
        'intro_p2' => 'A diferencia de otras plataformas, en rutasrurales.io encontrarás eventos verificados y actualizados, con información de acceso directo y sin intermediarios. Muchos de estos eventos se celebran a pocos minutos de {ATTRACTIONS_LIST}, por lo que puedes combinar cultura y turismo rural en un mismo viaje.',
        'intro_tip' => '💡 <strong>Consejo local:</strong> Reserva alojamiento con antelación durante los festivales principales. Los alojamientos rurales de la zona se llenan semanas antes.',

        // Paginación
        'page_prev'     => '← Anterior',
        'page_next'     => 'Siguiente →',
        'page_of'       => 'de',
        'page_results'  => 'eventos',

        // Sin resultados
        'no_results_h2' => 'Sin eventos con esos filtros',
        'no_results_p'  => 'No encontramos eventos con esos criterios en {PROVINCE}. Prueba con otra categoría o consulta todos los eventos de la zona.',
        'no_results_cta'=> 'Ver todos los eventos en {PROVINCE}',

        // Footer CTA
        'cta_title'  => '¿Buscas más eventos en {PROVINCE}?',
        'cta_desc'   => 'Explora la agenda completa de {PROVINCE} con todos sus eventos culturales, fiestas y celebraciones.',
        'cta_button' => 'Ver agenda completa',

        // Footer links
        'footer_home'     => 'Inicio',
        'footer_events'   => 'Agenda cultural',
        'footer_places'   => 'Lugares de interés',
        'footer_stays'    => 'Alojamientos rurales',
        'footer_legal'    => 'Aviso legal',
        'footer_copy'     => '© {YEAR} rutasrurales.io — Turismo rural auténtico',
    ],

    // ── ENGLISH ───────────────────────────────────────────────────────────────
    'en' => [
        'lang_code'   => 'en',
        'lang_locale' => 'en-GB',
        'dir'         => 'ltr',

        'meta_title'  => '{FILTER_LABEL} in {PROVINCE} — Cultural Events | rutasrurales.io',
        'meta_desc'   => 'Discover the best {FILTER_LABEL_LOWER} in {PROVINCE}, Spain. Updated cultural calendar, {FILTER_FEATURE} and unique rural tourism events. Complete and free information.',

        'h1_template'   => '{FILTER_LABEL} in {PROVINCE}',
        'h1_only_prov'  => 'Cultural events in {PROVINCE}',
        'h2_listing'    => 'Events in {PROVINCE}',
        'h2_semantico'  => 'Accommodation & places of interest near events in {PROVINCE}',
        'h2_rutas'      => 'Themed routes in {PROVINCE}',
        'h2_porque'     => 'Why visit {PROVINCE} for its cultural events?',

        'stat_count'    => 'events',
        'stat_free'     => 'free events',
        'stat_towns'    => 'towns with events',
        'stat_upcoming' => 'upcoming events',

        'bc_home'       => 'Home',
        'bc_listings'   => 'Cultural events',

        'card_gratis'   => 'Free entry',
        'card_precio'   => 'From',
        'card_ver'      => 'View event',
        'card_fecha'    => 'Date',
        'card_lugar'    => 'Venue',
        'card_hasta'    => 'Until',

        'badge_free'    => '🎁 Free',
        'badge_music'   => '🎵 Music',
        'badge_theatre' => '🎭 Theatre',
        'badge_art'     => '🎨 Art',
        'badge_food'    => '🍷 Food & wine',
        'badge_folk'    => '🎪 Folklore',
        'badge_market'  => '🛖 Market',
        'badge_family'  => '👨‍👩‍👧 Family',
        'badge_nature'  => '🌿 Nature',

        'h2_alojamientos'  => 'Where to stay near events in {PROVINCE}',
        'semantic_places'  => 'What to visit in {PROVINCE}',
        'semantic_stays'   => 'Rural accommodation in {PROVINCE}',
        'semantic_routes'  => 'Recommended themed routes',
        'semantic_intro'   => 'The cultural events of {PROVINCE} are just the beginning. Discover also where to stay and what to visit in the surroundings.',
        'semantic_cta_alo' => 'See all accommodation',
        'semantic_cta_poi' => 'See all places of interest',
        'semantic_cta_rt'  => 'See all routes',
        'entry_fee_free'   => 'Free entry',
        'price_per_night'  => '/night',

        'intro_p1' => 'If you are looking for {FILTER_LABEL_LOWER} in {PROVINCE}, you have found the most complete cultural calendar. {PROVINCE_VIBE}.',
        'intro_p2' => 'Unlike other platforms, at rutasrurales.io you will find verified and up-to-date events, with direct access information and no middlemen. Many of these events are held just minutes from {ATTRACTIONS_LIST}, so you can combine culture and rural tourism in the same trip.',
        'intro_tip' => '💡 <strong>Local tip:</strong> Book accommodation in advance during major festivals. Rural properties in the area fill up weeks ahead.',

        'page_prev'     => '← Previous',
        'page_next'     => 'Next →',
        'page_of'       => 'of',
        'page_results'  => 'events',

        'no_results_h2' => 'No events found with those filters',
        'no_results_p'  => 'We couldn\'t find events matching those criteria in {PROVINCE}. Try a different category or browse all events in the area.',
        'no_results_cta'=> 'See all events in {PROVINCE}',

        'cta_title'  => 'Looking for more events in {PROVINCE}?',
        'cta_desc'   => 'Explore the full calendar of {PROVINCE} with all its cultural events, festivals and celebrations.',
        'cta_button' => 'View full calendar',

        'footer_home'     => 'Home',
        'footer_events'   => 'Cultural events',
        'footer_places'   => 'Places of interest',
        'footer_stays'    => 'Rural accommodation',
        'footer_legal'    => 'Legal notice',
        'footer_copy'     => '© {YEAR} rutasrurales.io — Authentic rural tourism',
    ],

    // ── FRANÇAIS ──────────────────────────────────────────────────────────────
    'fr' => [
        'lang_code'   => 'fr',
        'lang_locale' => 'fr-FR',
        'dir'         => 'ltr',

        'meta_title'  => '{FILTER_LABEL} en {PROVINCE} — Agenda culturel | rutasrurales.io',
        'meta_desc'   => 'Découvrez les meilleurs {FILTER_LABEL_LOWER} en {PROVINCE}, Espagne. Agenda culturel mis à jour, {FILTER_FEATURE} et événements uniques du tourisme rural. Information complète et gratuite.',

        'h1_template'   => '{FILTER_LABEL} en {PROVINCE}',
        'h1_only_prov'  => 'Agenda culturel en {PROVINCE}',
        'h2_listing'    => 'Événements disponibles en {PROVINCE}',
        'h2_semantico'  => 'Hébergements et lieux d\'intérêt près des événements en {PROVINCE}',
        'h2_rutas'      => 'Itinéraires thématiques en {PROVINCE}',
        'h2_porque'     => 'Pourquoi visiter {PROVINCE} pour ses événements culturels ?',

        'stat_count'    => 'événements',
        'stat_free'     => 'événements gratuits',
        'stat_towns'    => 'communes avec agenda',
        'stat_upcoming' => 'prochains événements',

        'bc_home'       => 'Accueil',
        'bc_listings'   => 'Événements culturels',

        'card_gratis'   => 'Entrée gratuite',
        'card_precio'   => 'Dès',
        'card_ver'      => 'Voir l\'événement',
        'card_fecha'    => 'Date',
        'card_lugar'    => 'Lieu',
        'card_hasta'    => 'Jusqu\'au',

        'badge_free'    => '🎁 Gratuit',
        'badge_music'   => '🎵 Musique',
        'badge_theatre' => '🎭 Théâtre',
        'badge_art'     => '🎨 Art',
        'badge_food'    => '🍷 Gastronomie',
        'badge_folk'    => '🎪 Folklore',
        'badge_market'  => '🛖 Marché',
        'badge_family'  => '👨‍👩‍👧 Famille',
        'badge_nature'  => '🌿 Nature',

        'h2_alojamientos'  => 'Où séjourner près des événements en {PROVINCE}',
        'semantic_places'  => 'Que visiter en {PROVINCE}',
        'semantic_stays'   => 'Hébergements ruraux en {PROVINCE}',
        'semantic_routes'  => 'Itinéraires thématiques recommandés',
        'semantic_intro'   => 'Les événements culturels de {PROVINCE} ne sont que le début. Découvrez aussi où séjourner et que visiter dans les environs.',
        'semantic_cta_alo' => 'Voir tous les hébergements',
        'semantic_cta_poi' => 'Voir tous les lieux d\'intérêt',
        'semantic_cta_rt'  => 'Voir tous les itinéraires',
        'entry_fee_free'   => 'Entrée gratuite',
        'price_per_night'  => '/nuit',

        'intro_p1' => 'Si vous cherchez des {FILTER_LABEL_LOWER} en {PROVINCE}, vous avez trouvé l\'agenda culturel le plus complet. {PROVINCE_VIBE}.',
        'intro_p2' => 'Contrairement à d\'autres plateformes, rutasrurales.io propose des événements vérifiés et mis à jour, avec des informations d\'accès directes et sans intermédiaires. Beaucoup de ces événements se déroulent à quelques minutes de {ATTRACTIONS_LIST}, ce qui permet de combiner culture et tourisme rural dans un même voyage.',
        'intro_tip' => '💡 <strong>Conseil local :</strong> Réservez votre hébergement à l\'avance pendant les grands festivals. Les propriétés rurales de la région se remplissent des semaines à l\'avance.',

        'page_prev'     => '← Précédent',
        'page_next'     => 'Suivant →',
        'page_of'       => 'sur',
        'page_results'  => 'événements',

        'no_results_h2' => 'Aucun événement avec ces filtres',
        'no_results_p'  => 'Nous n\'avons trouvé aucun événement correspondant à ces critères en {PROVINCE}. Essayez une autre catégorie ou consultez tous les événements de la région.',
        'no_results_cta'=> 'Voir tous les événements en {PROVINCE}',

        'cta_title'  => 'Vous cherchez plus d\'événements en {PROVINCE} ?',
        'cta_desc'   => 'Explorez l\'agenda complet de {PROVINCE} avec tous ses événements culturels, fêtes et célébrations.',
        'cta_button' => 'Voir l\'agenda complet',

        'footer_home'     => 'Accueil',
        'footer_events'   => 'Événements culturels',
        'footer_places'   => 'Lieux d\'intérêt',
        'footer_stays'    => 'Hébergements ruraux',
        'footer_legal'    => 'Mentions légales',
        'footer_copy'     => '© {YEAR} rutasrurales.io — Tourisme rural authentique',
    ],

    // ── DEUTSCH ───────────────────────────────────────────────────────────────
    'de' => [
        'lang_code'   => 'de',
        'lang_locale' => 'de-DE',
        'dir'         => 'ltr',

        'meta_title'  => '{FILTER_LABEL} in {PROVINCE} — Kulturveranstaltungen | rutasrurales.io',
        'meta_desc'   => 'Entdecken Sie die besten {FILTER_LABEL_LOWER} in {PROVINCE}, Spanien. Aktualisierter Kulturkalender, {FILTER_FEATURE} und einzigartige Veranstaltungen des Landtourismus. Vollständige und kostenlose Informationen.',

        'h1_template'   => '{FILTER_LABEL} in {PROVINCE}',
        'h1_only_prov'  => 'Kulturveranstaltungen in {PROVINCE}',
        'h2_listing'    => 'Verfügbare Veranstaltungen in {PROVINCE}',
        'h2_semantico'  => 'Unterkünfte und Sehenswürdigkeiten in der Nähe von Veranstaltungen in {PROVINCE}',
        'h2_rutas'      => 'Thematische Routen in {PROVINCE}',
        'h2_porque'     => 'Warum {PROVINCE} für seine Kulturveranstaltungen besuchen?',

        'stat_count'    => 'Veranstaltungen',
        'stat_free'     => 'kostenlose Veranstaltungen',
        'stat_towns'    => 'Orte mit Programm',
        'stat_upcoming' => 'bevorstehende Veranstaltungen',

        'bc_home'       => 'Startseite',
        'bc_listings'   => 'Kulturveranstaltungen',

        'card_gratis'   => 'Freier Eintritt',
        'card_precio'   => 'Ab',
        'card_ver'      => 'Veranstaltung ansehen',
        'card_fecha'    => 'Datum',
        'card_lugar'    => 'Ort',
        'card_hasta'    => 'Bis',

        'badge_free'    => '🎁 Kostenlos',
        'badge_music'   => '🎵 Musik',
        'badge_theatre' => '🎭 Theater',
        'badge_art'     => '🎨 Kunst',
        'badge_food'    => '🍷 Gastronomie',
        'badge_folk'    => '🎪 Folklore',
        'badge_market'  => '🛖 Markt',
        'badge_family'  => '👨‍👩‍👧 Familie',
        'badge_nature'  => '🌿 Natur',

        'h2_alojamientos'  => 'Wo übernachten in der Nähe der Veranstaltungen in {PROVINCE}',
        'semantic_places'  => 'Was in {PROVINCE} besichtigen',
        'semantic_stays'   => 'Ländliche Unterkünfte in {PROVINCE}',
        'semantic_routes'  => 'Empfohlene Themenrouten',
        'semantic_intro'   => 'Die Kulturveranstaltungen von {PROVINCE} sind nur der Anfang. Entdecken Sie auch, wo Sie übernachten und was Sie in der Umgebung besuchen können.',
        'semantic_cta_alo' => 'Alle Unterkünfte ansehen',
        'semantic_cta_poi' => 'Alle Sehenswürdigkeiten ansehen',
        'semantic_cta_rt'  => 'Alle Routen ansehen',
        'entry_fee_free'   => 'Freier Eintritt',
        'price_per_night'  => '/Nacht',

        'intro_p1' => 'Wenn Sie {FILTER_LABEL_LOWER} in {PROVINCE} suchen, haben Sie den vollständigsten Kulturkalender gefunden. {PROVINCE_VIBE}.',
        'intro_p2' => 'Im Gegensatz zu anderen Plattformen bietet rutasrurales.io verifizierte und aktualisierte Veranstaltungen mit direkten Zugangsinformationen ohne Zwischenhändler. Viele dieser Veranstaltungen finden nur Minuten von {ATTRACTIONS_LIST} entfernt statt, sodass Sie Kultur und Landtourismus auf derselben Reise verbinden können.',
        'intro_tip' => '💡 <strong>Lokaler Tipp:</strong> Buchen Sie Ihre Unterkunft im Voraus während der Hauptfestivals. Ländliche Unterkünfte in der Region sind Wochen im Voraus ausgebucht.',

        'page_prev'     => '← Zurück',
        'page_next'     => 'Weiter →',
        'page_of'       => 'von',
        'page_results'  => 'Veranstaltungen',

        'no_results_h2' => 'Keine Veranstaltungen mit diesen Filtern',
        'no_results_p'  => 'Wir konnten keine Veranstaltungen mit diesen Kriterien in {PROVINCE} finden. Versuchen Sie eine andere Kategorie oder durchsuchen Sie alle Veranstaltungen in der Region.',
        'no_results_cta'=> 'Alle Veranstaltungen in {PROVINCE} ansehen',

        'cta_title'  => 'Suchen Sie mehr Veranstaltungen in {PROVINCE}?',
        'cta_desc'   => 'Erkunden Sie den vollständigen Kalender von {PROVINCE} mit allen Kulturveranstaltungen, Festen und Feiern.',
        'cta_button' => 'Vollständigen Kalender ansehen',

        'footer_home'     => 'Startseite',
        'footer_events'   => 'Kulturveranstaltungen',
        'footer_places'   => 'Sehenswürdigkeiten',
        'footer_stays'    => 'Ländliche Unterkünfte',
        'footer_legal'    => 'Impressum',
        'footer_copy'     => '© {YEAR} rutasrurales.io — Authentischer Landurlaub',
    ],

    // ── 中文 ──────────────────────────────────────────────────────────────────
    'zh' => [
        'lang_code'   => 'zh',
        'lang_locale' => 'zh-CN',
        'dir'         => 'ltr',

        'meta_title'  => '{PROVINCE}{FILTER_LABEL} — 文化活动 | rutasrurales.io',
        'meta_desc'   => '发现西班牙{PROVINCE}最精彩的{FILTER_LABEL_LOWER}。更新的文化日历，{FILTER_FEATURE}和独特的乡村旅游活动。完整免费信息。',

        'h1_template'   => '{PROVINCE}的{FILTER_LABEL}',
        'h1_only_prov'  => '{PROVINCE}文化活动',
        'h2_listing'    => '{PROVINCE}活动',
        'h2_semantico'  => '{PROVINCE}活动附近的住宿和景点',
        'h2_rutas'      => '{PROVINCE}主题路线',
        'h2_porque'     => '为什么到{PROVINCE}参加文化活动？',

        'stat_count'    => '项活动',
        'stat_free'     => '项免费活动',
        'stat_towns'    => '个有活动的城镇',
        'stat_upcoming' => '项即将到来的活动',

        'bc_home'       => '首页',
        'bc_listings'   => '文化活动',

        'card_gratis'   => '免费入场',
        'card_precio'   => '起价',
        'card_ver'      => '查看活动',
        'card_fecha'    => '日期',
        'card_lugar'    => '地点',
        'card_hasta'    => '至',

        'badge_free'    => '🎁 免费',
        'badge_music'   => '🎵 音乐',
        'badge_theatre' => '🎭 戏剧',
        'badge_art'     => '🎨 艺术',
        'badge_food'    => '🍷 美食',
        'badge_folk'    => '🎪 民俗',
        'badge_market'  => '🛖 集市',
        'badge_family'  => '👨‍👩‍👧 家庭',
        'badge_nature'  => '🌿 自然',

        'h2_alojamientos'  => '{PROVINCE}活动附近住宿',
        'semantic_places'  => '{PROVINCE}游览景点',
        'semantic_stays'   => '{PROVINCE}乡村住宿',
        'semantic_routes'  => '推荐主题路线',
        'semantic_intro'   => '{PROVINCE}的文化活动只是个开始，探索周边的住宿和景点，打造完美乡村之旅。',
        'semantic_cta_alo' => '查看所有住宿',
        'semantic_cta_poi' => '查看所有景点',
        'semantic_cta_rt'  => '查看所有路线',
        'entry_fee_free'   => '免费入场',
        'price_per_night'  => '/晚',

        'intro_p1' => '如果您正在寻找{PROVINCE}的{FILTER_LABEL_LOWER}，您找到了最完整的文化日历。{PROVINCE_VIBE}。',
        'intro_p2' => '与其他平台不同，rutasrurales.io提供经过核实和更新的活动，提供直接访问信息，无需中间商。这些活动许多都在距{ATTRACTIONS_LIST}仅几分钟的地方举行，让您可以在同一次旅行中将文化与乡村旅游相结合。',
        'intro_tip' => '💡 <strong>本地贴士：</strong>主要节日期间提前预订住宿。该地区的乡村住宿往往提前数周就会满员。',

        'page_prev'     => '← 上一页',
        'page_next'     => '下一页 →',
        'page_of'       => '共',
        'page_results'  => '项活动',

        'no_results_h2' => '未找到符合条件的活动',
        'no_results_p'  => '我们在{PROVINCE}未能找到符合这些标准的活动。请尝试其他类别或浏览该地区所有活动。',
        'no_results_cta'=> '查看{PROVINCE}所有活动',

        'cta_title'  => '想找更多{PROVINCE}活动？',
        'cta_desc'   => '探索{PROVINCE}的完整日历，包括所有文化活动、节日和庆典。',
        'cta_button' => '查看完整日历',

        'footer_home'     => '首页',
        'footer_events'   => '文化活动',
        'footer_places'   => '景点',
        'footer_stays'    => '乡村住宿',
        'footer_legal'    => '法律声明',
        'footer_copy'     => '© {YEAR} rutasrurales.io — 真实乡村旅游',
    ],
];

/**
 * Carga el array de traducciones para el idioma solicitado.
 * Fallback: español.
 */
function getEventosLandingTranslations(string $lang): array
{
    return EVENTOS_I18N[$lang] ?? EVENTOS_I18N['es'];
}

/**
 * Interpola variables en una cadena de traducción.
 * (Reutilizable si t() no está definida en el scope)
 *
 * @param string $template  Cadena con {PLACEHOLDER}
 * @param array  $vars      ['PLACEHOLDER' => 'valor', ...]
 */
if (!function_exists('t')) {
    function t(string $template, array $vars = []): string
    {
        foreach ($vars as $key => $value) {
            $template = str_replace('{' . $key . '}', (string)$value, $template);
        }
        return $template;
    }
}
