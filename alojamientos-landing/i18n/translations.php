<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 *  TRADUCCIONES DEL SISTEMA DE LANDINGS — 5 idiomas
 *  es · en · fr · de · zh
 * ════════════════════════════════════════════════════════════════════════════
 *
 *  Claves dinámicas (usar sprintf/str_replace con {PROVINCE}, {FILTER_TYPE}):
 *    h1_template, meta_title, meta_desc, intro_p1, intro_p2
 */

const LANDING_I18N = [

    // ── ESPAÑOL ──────────────────────────────────────────────────────────────
    'es' => [
        'lang_code'   => 'es',
        'lang_locale' => 'es-ES',
        'dir'         => 'ltr',

        // Meta
        'meta_title'  => '{FILTER_LABEL} en {PROVINCE} — Alojamientos Rurales | rutasrurales.io',
        'meta_desc'   => 'Descubre los mejores {FILTER_LABEL_LOWER} en {PROVINCE}. Casas rurales con encanto, {FILTER_FEATURE} y naturaleza auténtica. Reserva directa sin intermediarios.',

        // Encabezados
        'h1_template'   => '{FILTER_LABEL} en {PROVINCE}',
        'h1_only_prov'  => 'Alojamientos rurales en {PROVINCE}',
        'h2_listing'    => 'Alojamientos disponibles en {PROVINCE}',
        'h2_semantico'  => 'Qué visitar cerca de tu alojamiento en {PROVINCE}',
        'h2_rutas'      => 'Rutas temáticas en {PROVINCE}',
        'h2_porque'     => '¿Por qué alojarse en {PROVINCE}?',

        // Stats hero
        'stat_count'    => 'alojamientos',
        'stat_price'    => 'precio medio/noche',
        'stat_reviews'  => 'reseñas positivas',
        'stat_towns'    => 'municipios con oferta',

        // Breadcrumb
        'bc_home'       => 'Inicio',
        'bc_listings'   => 'Alojamientos rurales',

        // Tarjetas
        'card_desde'    => 'Desde',
        'card_noche'    => 'noche',
        'card_consultar'=> 'Consultar precio',
        'card_personas' => 'personas',
        'card_ver'      => 'Ver alojamiento',
        'card_gratis'   => 'Sin precio publicado',

        // Badges
        'badge_pet'     => '🐾 Mascotas',
        'badge_wifi'    => '📶 WiFi',
        'badge_kids'    => '👶 Niños',
        'badge_pool'    => '🏊 Piscina',
        'badge_chimney' => '🔥 Chimenea',
        'badge_bbq'     => '🍖 Barbacoa',
        'badge_jacuzzi' => '♨️ Jacuzzi',
        'badge_terrace' => '🌅 Terraza',

        // Cruce semántico
        'semantic_places'  => 'Monumentos y lugares de interés',
        'semantic_routes'  => 'Rutas temáticas recomendadas',
        'semantic_events'  => 'Próximos eventos en {PROVINCE}',
        'semantic_intro'   => 'Lo que Booking no te cuenta: en {PROVINCE} hay mucho más que alojarse.',
        'semantic_cta'     => 'Ver todos los lugares de interés',
        'semantic_cta_rt'  => 'Ver todas las rutas',
        'entry_fee_free'   => 'Entrada gratuita',

        // Intro texto SEO
        'intro_p1' => 'Si buscas {FILTER_LABEL_LOWER} en {PROVINCE}, has llegado al lugar adecuado. {PROVINCE_VIBE}.',
        'intro_p2' => 'A diferencia de las grandes plataformas, te ofrecemos alojamientos verificados con contacto directo y sin comisiones. Explora opciones en {INTERLINKING_LIST}.',
        'intro_tip' => '💡 <strong>Consejo local:</strong> La mayoría de propietarios ofrecen descuentos para estancias superiores a 3 noches. Contáctales directamente.',

        // Paginación
        'page_prev'     => '← Anterior',
        'page_next'     => 'Siguiente →',
        'page_of'       => 'de',
        'page_results'  => 'resultados',

        // Sin resultados
        'no_results_h2' => 'Sin resultados exactos',
        'no_results_p'  => 'No encontramos alojamientos con todos esos filtros en {PROVINCE}. Prueba eliminando alguna característica o explora todos los alojamientos de la zona.',
        'no_results_cta'=> 'Ver todos los alojamientos en {PROVINCE}',

        // Hero — enlace a todos los alojamientos de la provincia (sin filtros)
        'hero_all_prov' => 'Ver todos los alojamientos en {PROVINCE}',

        // Footer CTA
        'cta_title'  => '¿No encuentras lo que buscas?',
        'cta_desc'   => 'Cuéntanos qué necesitas y te ayudamos a encontrar el alojamiento perfecto en {PROVINCE}.',
        'cta_button' => 'Explorar todos los alojamientos',

        // Footer links
        'footer_home'     => 'Inicio',
        'footer_listings' => 'Alojamientos',
        'footer_places'   => 'Lugares de interés',
        'footer_events'   => 'Eventos culturales',
        'footer_legal'    => 'Aviso legal',
        'footer_copy'     => '© {YEAR} rutasrurales.io — Turismo rural auténtico',
    ],

    // ── ENGLISH ───────────────────────────────────────────────────────────────
    'en' => [
        'lang_code'   => 'en',
        'lang_locale' => 'en-GB',
        'dir'         => 'ltr',

        'meta_title'  => '{FILTER_LABEL} in {PROVINCE} — Rural Accommodation | rutasrurales.io',
        'meta_desc'   => 'Find the best {FILTER_LABEL_LOWER} in {PROVINCE}, Spain. Charming rural houses, {FILTER_FEATURE} and authentic nature. Book direct, no middlemen.',

        'h1_template'   => '{FILTER_LABEL} in {PROVINCE}',
        'h1_only_prov'  => 'Rural accommodation in {PROVINCE}',
        'h2_listing'    => 'Available accommodation in {PROVINCE}',
        'h2_semantico'  => 'What to visit near your accommodation in {PROVINCE}',
        'h2_rutas'      => 'Themed routes in {PROVINCE}',
        'h2_porque'     => 'Why stay in {PROVINCE}?',

        'stat_count'    => 'accommodations',
        'stat_price'    => 'avg price/night',
        'stat_reviews'  => 'positive reviews',
        'stat_towns'    => 'towns with listings',

        'bc_home'       => 'Home',
        'bc_listings'   => 'Rural accommodation',

        'card_desde'    => 'From',
        'card_noche'    => 'night',
        'card_consultar'=> 'Check price',
        'card_personas' => 'guests',
        'card_ver'      => 'View accommodation',
        'card_gratis'   => 'Price on request',

        'badge_pet'     => '🐾 Pets OK',
        'badge_wifi'    => '📶 WiFi',
        'badge_kids'    => '👶 Kids',
        'badge_pool'    => '🏊 Pool',
        'badge_chimney' => '🔥 Fireplace',
        'badge_bbq'     => '🍖 BBQ',
        'badge_jacuzzi' => '♨️ Jacuzzi',
        'badge_terrace' => '🌅 Terrace',

        'semantic_places'  => 'Monuments & places of interest',
        'semantic_routes'  => 'Recommended themed routes',
        'semantic_events'  => 'Upcoming events in {PROVINCE}',
        'semantic_intro'   => 'What Booking doesn\'t tell you: there\'s much more to {PROVINCE} than just a place to sleep.',
        'semantic_cta'     => 'See all points of interest',
        'semantic_cta_rt'  => 'See all routes',
        'entry_fee_free'   => 'Free entry',

        'intro_p1' => 'If you are looking for {FILTER_LABEL_LOWER} in {PROVINCE}, you have come to the right place. {PROVINCE_VIBE}.',
        'intro_p2' => 'Unlike large platforms, we offer verified accommodations with direct owner contact and no commissions. Explore options in {INTERLINKING_LIST}.',
        'intro_tip' => '💡 <strong>Local tip:</strong> Most owners offer discounts for stays longer than 3 nights. Contact them directly.',

        'page_prev'     => '← Previous',
        'page_next'     => 'Next →',
        'page_of'       => 'of',
        'page_results'  => 'results',

        'no_results_h2' => 'No exact matches',
        'no_results_p'  => 'We couldn\'t find accommodations with all those filters in {PROVINCE}. Try removing a filter or explore all listings in the area.',
        'no_results_cta'=> 'View all accommodation in {PROVINCE}',

        // Hero — link to all accommodations in province (without filters)
        'hero_all_prov' => 'View all accommodation in {PROVINCE}',

        'cta_title'  => 'Can\'t find what you\'re looking for?',
        'cta_desc'   => 'Tell us what you need and we\'ll help you find the perfect stay in {PROVINCE}.',
        'cta_button' => 'Explore all accommodations',

        'footer_home'     => 'Home',
        'footer_listings' => 'Accommodations',
        'footer_places'   => 'Places of interest',
        'footer_events'   => 'Cultural events',
        'footer_legal'    => 'Legal notice',
        'footer_copy'     => '© {YEAR} rutasrurales.io — Authentic rural tourism',
    ],

    // ── FRANÇAIS ──────────────────────────────────────────────────────────────
    'fr' => [
        'lang_code'   => 'fr',
        'lang_locale' => 'fr-FR',
        'dir'         => 'ltr',

        'meta_title'  => '{FILTER_LABEL} en {PROVINCE} — Hébergements ruraux | rutasrurales.io',
        'meta_desc'   => 'Trouvez les meilleurs {FILTER_LABEL_LOWER} en {PROVINCE}, Espagne. Maisons rurales de charme, {FILTER_FEATURE} et nature authentique. Réservation directe.',

        'h1_template'   => '{FILTER_LABEL} en {PROVINCE}',
        'h1_only_prov'  => 'Hébergements ruraux en {PROVINCE}',
        'h2_listing'    => 'Hébergements disponibles en {PROVINCE}',
        'h2_semantico'  => 'Que visiter près de votre hébergement en {PROVINCE}',
        'h2_rutas'      => 'Itinéraires thématiques en {PROVINCE}',
        'h2_porque'     => 'Pourquoi séjourner en {PROVINCE} ?',

        'stat_count'    => 'hébergements',
        'stat_price'    => 'prix moyen/nuit',
        'stat_reviews'  => 'avis positifs',
        'stat_towns'    => 'communes avec offre',

        'bc_home'       => 'Accueil',
        'bc_listings'   => 'Hébergements ruraux',

        'card_desde'    => 'Dès',
        'card_noche'    => 'nuit',
        'card_consultar'=> 'Demander le prix',
        'card_personas' => 'personnes',
        'card_ver'      => 'Voir l\'hébergement',
        'card_gratis'   => 'Prix sur demande',

        'badge_pet'     => '🐾 Animaux',
        'badge_wifi'    => '📶 WiFi',
        'badge_kids'    => '👶 Enfants',
        'badge_pool'    => '🏊 Piscine',
        'badge_chimney' => '🔥 Cheminée',
        'badge_bbq'     => '🍖 Barbecue',
        'badge_jacuzzi' => '♨️ Jacuzzi',
        'badge_terrace' => '🌅 Terrasse',

        'semantic_places'  => 'Monuments et lieux d\'intérêt',
        'semantic_routes'  => 'Itinéraires thématiques recommandés',
        'semantic_events'  => 'Prochains événements en {PROVINCE}',
        'semantic_intro'   => 'Ce que Booking ne vous dit pas : il y a bien plus en {PROVINCE} qu\'un simple endroit pour dormir.',
        'semantic_cta'     => 'Voir tous les lieux d\'intérêt',
        'semantic_cta_rt'  => 'Voir tous les itinéraires',
        'entry_fee_free'   => 'Entrée gratuite',

        'intro_p1' => 'Si vous cherchez des {FILTER_LABEL_LOWER} en {PROVINCE}, vous êtes au bon endroit. {PROVINCE_VIBE}.',
        'intro_p2' => 'Contrairement aux grandes plateformes, nous proposons des hébergements vérifiés avec contact direct du propriétaire et sans commissions. Découvrez des options en {INTERLINKING_LIST}.',
        'intro_tip' => '💡 <strong>Conseil local :</strong> La plupart des propriétaires offrent des réductions pour les séjours de plus de 3 nuits. Contactez-les directement.',

        'page_prev'     => '← Précédent',
        'page_next'     => 'Suivant →',
        'page_of'       => 'sur',
        'page_results'  => 'résultats',

        'no_results_h2' => 'Aucun résultat exact',
        'no_results_p'  => 'Nous n\'avons pas trouvé d\'hébergements avec tous ces filtres en {PROVINCE}. Essayez de supprimer un filtre ou explorez tous les hébergements de la région.',
        'no_results_cta'=> 'Voir tous les hébergements en {PROVINCE}',

        // Hero — lien vers tous les hébergements de la province (sans filtres)
        'hero_all_prov' => 'Voir tous les hébergements en {PROVINCE}',

        'cta_title'  => 'Vous ne trouvez pas ce que vous cherchez ?',
        'cta_desc'   => 'Dites-nous ce dont vous avez besoin et nous vous aiderons à trouver l\'hébergement parfait en {PROVINCE}.',
        'cta_button' => 'Explorer tous les hébergements',

        'footer_home'     => 'Accueil',
        'footer_listings' => 'Hébergements',
        'footer_places'   => 'Lieux d\'intérêt',
        'footer_events'   => 'Événements culturels',
        'footer_legal'    => 'Mentions légales',
        'footer_copy'     => '© {YEAR} rutasrurales.io — Tourisme rural authentique',
    ],

    // ── DEUTSCH ───────────────────────────────────────────────────────────────
    'de' => [
        'lang_code'   => 'de',
        'lang_locale' => 'de-DE',
        'dir'         => 'ltr',

        'meta_title'  => '{FILTER_LABEL} in {PROVINCE} — Ländliche Unterkünfte | rutasrurales.io',
        'meta_desc'   => 'Finden Sie die besten {FILTER_LABEL_LOWER} in {PROVINCE}, Spanien. Charmante Landhäuser, {FILTER_FEATURE} und authentische Natur. Direkt buchen, ohne Vermittler.',

        'h1_template'   => '{FILTER_LABEL} in {PROVINCE}',
        'h1_only_prov'  => 'Ländliche Unterkünfte in {PROVINCE}',
        'h2_listing'    => 'Verfügbare Unterkünfte in {PROVINCE}',
        'h2_semantico'  => 'Was Sie in der Nähe Ihrer Unterkunft in {PROVINCE} besuchen können',
        'h2_rutas'      => 'Thematische Routen in {PROVINCE}',
        'h2_porque'     => 'Warum in {PROVINCE} übernachten?',

        'stat_count'    => 'Unterkünfte',
        'stat_price'    => 'ø Preis/Nacht',
        'stat_reviews'  => 'positive Bewertungen',
        'stat_towns'    => 'Orte mit Angebot',

        'bc_home'       => 'Startseite',
        'bc_listings'   => 'Ländliche Unterkünfte',

        'card_desde'    => 'Ab',
        'card_noche'    => 'Nacht',
        'card_consultar'=> 'Preis anfragen',
        'card_personas' => 'Personen',
        'card_ver'      => 'Unterkunft ansehen',
        'card_gratis'   => 'Preis auf Anfrage',

        'badge_pet'     => '🐾 Haustiere',
        'badge_wifi'    => '📶 WLAN',
        'badge_kids'    => '👶 Kinder',
        'badge_pool'    => '🏊 Pool',
        'badge_chimney' => '🔥 Kamin',
        'badge_bbq'     => '🍖 Grill',
        'badge_jacuzzi' => '♨️ Jacuzzi',
        'badge_terrace' => '🌅 Terrasse',

        'semantic_places'  => 'Sehenswürdigkeiten & Denkmäler',
        'semantic_routes'  => 'Empfohlene Themenrouten',
        'semantic_events'  => 'Kommende Veranstaltungen in {PROVINCE}',
        'semantic_intro'   => 'Was Booking Ihnen nicht sagt: In {PROVINCE} gibt es weit mehr als nur einen Schlafplatz.',
        'semantic_cta'     => 'Alle Sehenswürdigkeiten ansehen',
        'semantic_cta_rt'  => 'Alle Routen ansehen',
        'entry_fee_free'   => 'Freier Eintritt',

        'intro_p1' => 'Wenn Sie {FILTER_LABEL_LOWER} in {PROVINCE} suchen, sind Sie hier genau richtig. {PROVINCE_VIBE}.',
        'intro_p2' => 'Im Gegensatz zu großen Plattformen bieten wir verifizierte Unterkünfte mit direktem Kontakt zum Eigentümer und ohne Provisionen. Entdecken Sie Optionen in {INTERLINKING_LIST}.',
        'intro_tip' => '💡 <strong>Lokaler Tipp:</strong> Die meisten Eigentümer bieten Rabatte für Aufenthalte über 3 Nächte. Kontaktieren Sie sie direkt.',

        'page_prev'     => '← Zurück',
        'page_next'     => 'Weiter →',
        'page_of'       => 'von',
        'page_results'  => 'Ergebnisse',

        'no_results_h2' => 'Keine genauen Treffer',
        'no_results_p'  => 'Wir konnten keine Unterkünfte mit all diesen Filtern in {PROVINCE} finden. Versuchen Sie, einen Filter zu entfernen oder alle Unterkünfte in der Region zu erkunden.',
        'no_results_cta'=> 'Alle Unterkünfte in {PROVINCE} ansehen',

        // Hero — Link zu allen Unterkünften der Provinz (ohne Filter)
        'hero_all_prov' => 'Alle Unterkünfte in {PROVINCE} ansehen',

        'cta_title'  => 'Nicht gefunden, was Sie suchen?',
        'cta_desc'   => 'Teilen Sie uns Ihre Wünsche mit und wir helfen Ihnen, die perfekte Unterkunft in {PROVINCE} zu finden.',
        'cta_button' => 'Alle Unterkünfte erkunden',

        'footer_home'     => 'Startseite',
        'footer_listings' => 'Unterkünfte',
        'footer_places'   => 'Sehenswürdigkeiten',
        'footer_events'   => 'Kulturveranstaltungen',
        'footer_legal'    => 'Impressum',
        'footer_copy'     => '© {YEAR} rutasrurales.io — Authentischer Landurlaub',
    ],

    // ── 中文 ──────────────────────────────────────────────────────────────────
    'zh' => [
        'lang_code'   => 'zh',
        'lang_locale' => 'zh-CN',
        'dir'         => 'ltr',

        'meta_title'  => '{PROVINCE}{FILTER_LABEL} — 乡村住宿 | rutasrurales.io',
        'meta_desc'   => '在西班牙{PROVINCE}寻找最优质的{FILTER_LABEL_LOWER}。迷人的乡村民宿，{FILTER_FEATURE}，真实自然体验。直接预订，无中间商。',

        'h1_template'   => '{PROVINCE}的{FILTER_LABEL}',
        'h1_only_prov'  => '{PROVINCE}乡村住宿',
        'h2_listing'    => '{PROVINCE}可用住宿',
        'h2_semantico'  => '{PROVINCE}住宿周边游览推荐',
        'h2_rutas'      => '{PROVINCE}主题路线',
        'h2_porque'     => '为什么选择在{PROVINCE}住宿？',

        'stat_count'    => '处住宿',
        'stat_price'    => '均价/晚',
        'stat_reviews'  => '好评',
        'stat_towns'    => '有住宿的城镇',

        'bc_home'       => '首页',
        'bc_listings'   => '乡村住宿',

        'card_desde'    => '起价',
        'card_noche'    => '晚',
        'card_consultar'=> '询价',
        'card_personas' => '人',
        'card_ver'      => '查看住宿',
        'card_gratis'   => '价格待询',

        'badge_pet'     => '🐾 宠物友好',
        'badge_wifi'    => '📶 WiFi',
        'badge_kids'    => '👶 儿童友好',
        'badge_pool'    => '🏊 游泳池',
        'badge_chimney' => '🔥 壁炉',
        'badge_bbq'     => '🍖 烧烤',
        'badge_jacuzzi' => '♨️ 按摩浴缸',
        'badge_terrace' => '🌅 露台',

        'semantic_places'  => '纪念碑与景点',
        'semantic_routes'  => '推荐主题路线',
        'semantic_events'  => '{PROVINCE}近期活动',
        'semantic_intro'   => 'Booking不会告诉你的：{PROVINCE}不只是住宿，更是一段真实的旅程。',
        'semantic_cta'     => '查看所有景点',
        'semantic_cta_rt'  => '查看所有路线',
        'entry_fee_free'   => '免费入场',

        'intro_p1' => '如果您正在寻找{PROVINCE}的{FILTER_LABEL_LOWER}，您来对地方了。{PROVINCE_VIBE}。',
        'intro_p2' => '与大型平台不同，我们提供经过验证的住宿，可直接与业主联系，无佣金。探索{INTERLINKING_LIST}的选项。',
        'intro_tip' => '💡 <strong>本地贴士：</strong>大多数业主为3晚以上住宿提供折扣，直接联系他们即可。',

        'page_prev'     => '← 上一页',
        'page_next'     => '下一页 →',
        'page_of'       => '共',
        'page_results'  => '个结果',

        'no_results_h2' => '无精确匹配',
        'no_results_p'  => '我们在{PROVINCE}未能找到符合所有筛选条件的住宿。请尝试删除某个筛选条件，或浏览该地区所有住宿。',
        'no_results_cta'=> '查看{PROVINCE}所有住宿',

        // Hero — 省份所有住宿链接（不含筛选器）
        'hero_all_prov' => '查看{PROVINCE}全部住宿',

        'cta_title'  => '没找到您想要的？',
        'cta_desc'   => '告诉我们您的需求，我们将帮您在{PROVINCE}找到完美住宿。',
        'cta_button' => '浏览所有住宿',

        'footer_home'     => '首页',
        'footer_listings' => '住宿',
        'footer_places'   => '景点',
        'footer_events'   => '文化活动',
        'footer_legal'    => '法律声明',
        'footer_copy'     => '© {YEAR} rutasrurales.io — 真实乡村旅游',
    ],
];

/**
 * Carga el array de traducciones para el idioma solicitado.
 * Fallback: español.
 */
function getLandingTranslations(string $lang): array
{
    return LANDING_I18N[$lang] ?? LANDING_I18N['es'];
}

/**
 * Interpola variables en una cadena de traducción.
 *
 * @param string $template  Cadena con {PLACEHOLDER}
 * @param array  $vars      ['PLACEHOLDER' => 'valor', ...]
 */
function t(string $template, array $vars = []): string
{
    foreach ($vars as $key => $value) {
        $template = str_replace('{' . $key . '}', (string)$value, $template);
    }
    return $template;
}
