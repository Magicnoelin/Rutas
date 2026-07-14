<?php
/**
 * ALOJAMIENTO MODULAR - Página de Detalle de Alojamiento
 * Versión 3.0 — Mismo patrón que evento-modular (que funciona bien)
 * Auto-contenida, sin include header.php, CSS inline, JS al final
 *
 * URL: /alojamiento-modular/{slug}
 */

// Suprimir warnings en producción
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

define('API_NO_HEADERS', true);
require_once '../api/config.php';
require_once __DIR__ . '/modules/schema.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$lang = isset($_GET['lang']) ? trim($_GET['lang']) : 'es';
$lang = in_array($lang, ['es', 'en', 'fr', 'de', 'zh']) ? $lang : 'es';

// ─── OBTENER DATOS CRÍTICOS DEL ALOJAMIENTO (SSR para SEO) ───────────────────
$alojamiento = null;
$fotos = [];

if (!empty($slug)) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT a.*, c.name as category_name
            FROM accommodations a
            LEFT JOIN categories_accommodations c ON a.category_id = c.id
            WHERE a.slug = ? AND a.is_active = 1
        ");
        $stmt->execute([$slug]);
        $alojamiento = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($alojamiento) {
            for ($i = 1; $i <= 10; $i++) {
                $campo = 'photo' . $i;
                if (!empty($alojamiento[$campo])) {
                    $url = $alojamiento[$campo];
                    if (!preg_match('/^https?:\/\//', $url)) {
                        $url = '/' . ltrim($url, '/');
                    }
                    $fotos[] = $url;
                }
            }
            if (empty($fotos)) {
                $fotos[] = 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1200&h=800&fit=crop&auto=format';
            }
        }
    } catch (Exception $e) {
        error_log('alojamiento-modular/index.php error: ' . $e->getMessage());
    }
}

// ─── SEO ─────────────────────────────────────────────────────────────────────
$page_title = $alojamiento
    ? ($alojamiento['meta_title'] ?: $alojamiento['name'] . ' — ' . ($alojamiento['municipality'] ?? '') . ' | Rutas Rurales')
    : 'Alojamiento | Rutas Rurales';
$page_desc = $alojamiento
    ? ($alojamiento['meta_description'] ?: substr(strip_tags($alojamiento['description'] ?? ''), 0, 160) ?: 'Alojamiento turístico en ' . ($alojamiento['municipality'] ?? ''))
    : 'Descubre este alojamiento en Rutas Rurales';
$page_description = $page_desc;
$canonical = 'https://rutasrurales.io/' . ($lang !== 'es' ? $lang . '/' : '') . 'alojamiento/' . $slug;
$foto_og   = !empty($fotos[0]) ? $fotos[0] : 'https://rutasrurales.io/menu_images/turismo_rural.webp';

// ─── TRADUCCIONES DE UI ───────────────────────────────────────────────────────
$ui = [
    'es' => [
        'alojamientos'  => 'Alojamientos',
        'tipo'          => 'Tipo',
        'capacidad'     => 'Capacidad',
        'personas'      => 'personas',
        'precio_noche'  => 'Precio por noche',
        'consultar'     => 'Consultar precio',
        'contacto'      => 'Contacto',
        'llamar'        => 'Llamar',
        'whatsapp'      => 'WhatsApp',
        'email'         => 'Email',
        'web'           => 'Visitar web',
        'descripcion'   => 'Descripción',
        'caracteristicas'=> 'Características',
        'servicios'     => 'Servicios',
        'ubicacion'     => 'Ubicación',
        'ver_mapa'      => 'Ver en el mapa',
        'click_mapa'    => 'Haz clic para cargar el mapa interactivo',
        'explora_alrededores' => 'Explora los alrededores',
        'descubre_alrededor'  => 'Descubre alojamientos, lugares de interés, actividades y eventos cerca de',
        'ver_en_rutas'        => 'Ver todo lo que hay alrededor',
        'cercanos'      => '¿Qué hay cerca?',
        'aloj_cercanos' => '🏠 Alojamientos cercanos',
        'ver_mas_aloj'  => 'Ver más alojamientos',
        'lugares_cercanos'=> '🏛️ Lugares de interés cercanos',
        'ver_mas_lugares'=> 'Ver más lugares',
        'activ_cercanas'=> '🎯 Actividades turísticas cercanas',
        'ver_mas_activ' => 'Ver más actividades',
        'eventos_cercanos'=> '🎭 Eventos culturales cercanos',
        'ver_mas_eventos'=> 'Ver más eventos',
        'cta_titulo'    => '¿Te gusta este alojamiento?',
        'cta_desc'      => 'Regístrate gratis para guardarlo en tus favoritos y recibir ofertas similares',
        'cta_register'  => '✨ Registrarme gratis',
        'cta_login'     => 'Ya tengo cuenta',
        'no_encontrado_h1'=> 'Alojamiento no encontrado',
        'no_encontrado_p' => 'El alojamiento que buscas no existe o ya no está disponible.',
        'volver_lista'  => '← Volver a la lista de alojamientos',
        'fotos'         => 'Galería de fotos',
        'km'            => 'km',
        'gratis'        => 'Gratis',
        'desde'         => 'desde',
        'checkin'       => 'Check-in',
        'checkout'      => 'Check-out',
        'cargando'      => 'Cargando…',
        'sin_resultados'=> 'No hay resultados cercanos',
        'reservar'      => 'Reservar ahora',
        'compartir'     => 'Compartir',
        'favorito'      => 'Guardar',
        'precio_desde'  => 'Desde',
        'noche'         => 'noche',
        'guardar'       => '🔖 Guardar alojamiento',
        'aviso_legal'   => 'Aviso Legal',
        'cookies'       => 'Cookies',
        'agradecimientos'=> 'Agradecimientos',
        'acceder_link'  => 'Acceder',
        'leer_mas'      => 'Leer más',
        'ver_todas'     => 'Ver todas las fotos',
    ],
    'en' => [
        'alojamientos'  => 'Accommodations',
        'tipo'          => 'Type',
        'capacidad'     => 'Capacity',
        'personas'      => 'people',
        'precio_noche'  => 'Price per night',
        'consultar'     => 'Check price',
        'contacto'      => 'Contact',
        'llamar'        => 'Call',
        'whatsapp'      => 'WhatsApp',
        'email'         => 'Email',
        'web'           => 'Visit website',
        'descripcion'   => 'Description',
        'caracteristicas'=> 'Features',
        'servicios'     => 'Services',
        'ubicacion'     => 'Location',
        'ver_mapa'      => 'View on map',
        'click_mapa'    => 'Click to load the interactive map',
        'explora_alrededores' => 'Explore the surroundings',
        'descubre_alrededor'  => 'Discover accommodations, points of interest, activities and events near',
        'ver_en_rutas'        => 'See everything around',
        'cercanos'      => 'What\'s nearby?',
        'aloj_cercanos' => '🏠 Nearby accommodation',
        'ver_mas_aloj'  => 'View more accommodation',
        'lugares_cercanos'=> '🏛️ Nearby places of interest',
        'ver_mas_lugares'=> 'View more places',
        'activ_cercanas'=> '🎯 Nearby tourist activities',
        'ver_mas_activ' => 'View more activities',
        'eventos_cercanos'=> '🎭 Nearby cultural events',
        'ver_mas_eventos'=> 'View more events',
        'cta_titulo'    => 'Do you like this accommodation?',
        'cta_desc'      => 'Sign up for free to save it to your favorites and receive similar offers',
        'cta_register'  => '✨ Sign up free',
        'cta_login'     => 'I already have an account',
        'no_encontrado_h1'=> 'Accommodation not found',
        'no_encontrado_p' => 'The accommodation you are looking for does not exist or is no longer available.',
        'volver_lista'  => '← Back to accommodation list',
        'fotos'         => 'Photo gallery',
        'km'            => 'km',
        'gratis'        => 'Free',
        'desde'         => 'from',
        'checkin'       => 'Check-in',
        'checkout'      => 'Check-out',
        'cargando'      => 'Loading…',
        'sin_resultados'=> 'No nearby results',
        'reservar'      => 'Book now',
        'compartir'     => 'Share',
        'favorito'      => 'Save',
        'precio_desde'  => 'From',
        'noche'         => 'night',
        'guardar'       => '🔖 Save accommodation',
        'aviso_legal'   => 'Legal Notice',
        'cookies'       => 'Cookies',
        'agradecimientos'=> 'Acknowledgements',
        'acceder_link'  => 'Log in',
        'leer_mas'      => 'Read more',
        'ver_todas'     => 'View all photos',
    ],
    'fr' => [
        'alojamientos'  => 'Hébergements',
        'tipo'          => 'Type',
        'capacidad'     => 'Capacité',
        'personas'      => 'personnes',
        'precio_noche'  => 'Prix par nuit',
        'consultar'     => 'Consulter le prix',
        'contacto'      => 'Contact',
        'llamar'        => 'Appeler',
        'whatsapp'      => 'WhatsApp',
        'email'         => 'Email',
        'web'           => 'Visiter le site',
        'descripcion'   => 'Description',
        'caracteristicas'=> 'Caractéristiques',
        'servicios'     => 'Services',
        'ubicacion'     => 'Emplacement',
        'ver_mapa'      => 'Voir sur la carte',
        'click_mapa'    => 'Cliquez pour charger la carte interactive',
        'explora_alrededores' => 'Explorez les environs',
        'descubre_alrededor'  => 'Découvrez hébergements, lieux d\'intérêt, activités et événements près de',
        'ver_en_rutas'        => 'Voir tout ce qui se trouve autour',
        'cercanos'      => 'Qu\'y a-t-il à proximité ?',
        'aloj_cercanos' => '🏠 Hébergements à proximité',
        'ver_mas_aloj'  => 'Voir plus d\'hébergements',
        'lugares_cercanos'=> '🏛️ Sites d\'intérêt à proximité',
        'ver_mas_lugares'=> 'Voir plus de lieux',
        'activ_cercanas'=> '🎯 Activités touristiques à proximité',
        'ver_mas_activ' => 'Voir plus d\'activités',
        'eventos_cercanos'=> '🎭 Événements culturels à proximité',
        'ver_mas_eventos'=> 'Voir plus d\'événements',
        'cta_titulo'    => 'Vous aimez cet hébergement ?',
        'cta_desc'      => 'Inscrivez-vous gratuitement pour l\'ajouter à vos favoris',
        'cta_register'  => '✨ S\'inscrire gratuitement',
        'cta_login'     => 'J\'ai déjà un compte',
        'no_encontrado_h1'=> 'Hébergement introuvable',
        'no_encontrado_p' => 'L\'hébergement que vous recherchez n\'existe pas ou n\'est plus disponible.',
        'volver_lista'  => '← Retour à la liste',
        'fotos'         => 'Galerie photos',
        'km'            => 'km',
        'gratis'        => 'Gratuit',
        'desde'         => 'à partir de',
        'checkin'       => 'Arrivée',
        'checkout'      => 'Départ',
        'cargando'      => 'Chargement…',
        'sin_resultados'=> 'Aucun résultat à proximité',
        'reservar'      => 'Réserver maintenant',
        'compartir'     => 'Partager',
        'favorito'      => 'Sauvegarder',
        'precio_desde'  => 'À partir de',
        'noche'         => 'nuit',
        'guardar'       => '🔖 Sauvegarder l\'hébergement',
        'aviso_legal'   => 'Mentions légales',
        'cookies'       => 'Cookies',
        'agradecimientos'=> 'Remerciements',
        'acceder_link'  => 'Se connecter',
        'leer_mas'      => 'Lire plus',
        'ver_todas'     => 'Voir toutes les photos',
    ],
    'de' => [
        'alojamientos'  => 'Unterkünfte',
        'tipo'          => 'Typ',
        'capacidad'     => 'Kapazität',
        'personas'      => 'Personen',
        'precio_noche'  => 'Preis pro Nacht',
        'consultar'     => 'Preis anfragen',
        'contacto'      => 'Kontakt',
        'llamar'        => 'Anrufen',
        'whatsapp'      => 'WhatsApp',
        'email'         => 'E-Mail',
        'web'           => 'Website besuchen',
        'descripcion'   => 'Beschreibung',
        'caracteristicas'=> 'Merkmale',
        'servicios'     => 'Dienstleistungen',
        'ubicacion'     => 'Standort',
        'ver_mapa'      => 'Auf der Karte anzeigen',
        'click_mapa'    => 'Klicken Sie, um die interaktive Karte zu laden',
        'explora_alrededores' => 'Erkunden Sie die Umgebung',
        'descubre_alrededor'  => 'Entdecken Sie Unterkünfte, Sehenswürdigkeiten, Aktivitäten und Veranstaltungen in der Nähe von',
        'ver_en_rutas'        => 'Alles in der Umgebung anzeigen',
        'cercanos'      => 'Was gibt es in der Nähe?',
        'aloj_cercanos' => '🏠 Unterkünfte in der Nähe',
        'ver_mas_aloj'  => 'Mehr Unterkünfte anzeigen',
        'lugares_cercanos'=> '🏛️ Sehenswürdigkeiten in der Nähe',
        'ver_mas_lugares'=> 'Mehr Orte anzeigen',
        'activ_cercanas'=> '🎯 Touristische Aktivitäten in der Nähe',
        'ver_mas_activ' => 'Mehr Aktivitäten anzeigen',
        'eventos_cercanos'=> '🎭 Kulturelle Veranstaltungen in der Nähe',
        'ver_mas_eventos'=> 'Mehr Veranstaltungen anzeigen',
        'cta_titulo'    => 'Gefällt Ihnen diese Unterkunft?',
        'cta_desc'      => 'Registrieren Sie sich kostenlos, um sie zu Ihren Favoriten hinzuzufügen',
        'cta_register'  => '✨ Kostenlos registrieren',
        'cta_login'     => 'Ich habe bereits ein Konto',
        'no_encontrado_h1'=> 'Unterkunft nicht gefunden',
        'no_encontrado_p' => 'Die gesuchte Unterkunft existiert nicht oder ist nicht mehr verfügbar.',
        'volver_lista'  => '← Zurück zur Liste',
        'fotos'         => 'Fotogalerie',
        'km'            => 'km',
        'gratis'        => 'Kostenlos',
        'desde'         => 'ab',
        'checkin'       => 'Check-in',
        'checkout'      => 'Check-out',
        'cargando'      => 'Laden…',
        'sin_resultados'=> 'Keine Ergebnisse in der Nähe',
        'reservar'      => 'Jetzt buchen',
        'compartir'     => 'Teilen',
        'favorito'      => 'Speichern',
        'precio_desde'  => 'Ab',
        'noche'         => 'Nacht',
        'guardar'       => '🔖 Unterkunft speichern',
        'aviso_legal'   => 'Impressum',
        'cookies'       => 'Cookies',
        'agradecimientos'=> 'Danksagungen',
        'acceder_link'  => 'Anmelden',
        'leer_mas'      => 'Mehr lesen',
        'ver_todas'     => 'Alle Fotos anzeigen',
    ],
    'zh' => [
        'alojamientos'  => '住宿列表',
        'tipo'          => '类型',
        'capacidad'     => '容量',
        'personas'      => '人',
        'precio_noche'  => '每晚价格',
        'consultar'     => '咨询价格',
        'contacto'      => '联系',
        'llamar'        => '打电话',
        'whatsapp'      => 'WhatsApp',
        'email'         => '电子邮件',
        'web'           => '访问网站',
        'descripcion'   => '描述',
        'caracteristicas'=> '特色',
        'servicios'     => '服务',
        'ubicacion'     => '位置',
        'ver_mapa'      => '在地图上查看',
        'click_mapa'    => '点击加载互动地图',
        'explora_alrededores' => '探索周边',
        'descubre_alrededor'  => '发现附近的住宿、景点、活动和活动',
        'ver_en_rutas'        => '查看周边所有内容',
        'cercanos'      => '附近有什么？',
        'aloj_cercanos' => '🏠 附近住宿',
        'ver_mas_aloj'  => '查看更多住宿',
        'lugares_cercanos'=> '🏛️ 附近景点',
        'ver_mas_lugares'=> '查看更多景点',
        'activ_cercanas'=> '🎯 附近旅游活动',
        'ver_mas_activ' => '查看更多活动',
        'eventos_cercanos'=> '🎭 附近文化活动',
        'ver_mas_eventos'=> '查看更多活动',
        'cta_titulo'    => '喜欢这个住宿吗？',
        'cta_desc'      => '免费注册，将其添加到收藏夹并接收类似优惠',
        'cta_register'  => '✨ 免费注册',
        'cta_login'     => '我已有账户',
        'no_encontrado_h1'=> '未找到住宿',
        'no_encontrado_p' => '您查找的住宿不存在或已不再提供。',
        'volver_lista'  => '← 返回住宿列表',
        'fotos'         => '照片库',
        'km'            => '公里',
        'gratis'        => '免费',
        'desde'         => '起',
        'checkin'       => '入住',
        'checkout'      => '退房',
        'cargando'      => '加载中…',
        'sin_resultados'=> '附近没有结果',
        'reservar'      => '立即预订',
        'compartir'     => '分享',
        'favorito'      => '收藏',
        'precio_desde'  => '起价',
        'noche'         => '晚',
        'guardar'       => '🔖 保存住宿',
        'aviso_legal'   => '法律声明',
        'cookies'       => 'Cookies',
        'agradecimientos'=> '致谢',
        'acceder_link'  => '登录',
        'leer_mas'      => '阅读更多',
        'ver_todas'     => '查看所有照片',
    ],
];

$t = $ui[$lang] ?? $ui['es'];

// Precio formateado
$precio_display = '';
if ($alojamiento) {
    if (!empty($alojamiento['price_per_night']) && $alojamiento['price_per_night'] > 0) {
        $precio_display = number_format($alojamiento['price_per_night'], 0, ',', '.') . ' €';
    } else {
        $precio_display = $t['consultar'];
    }
}

$tipo_display      = $alojamiento['category_name'] ?? $alojamiento['accommodation_type'] ?? 'Alojamiento';
$capacidad_display = ($alojamiento['capacity'] ?? 0) > 0 ? $alojamiento['capacity'] . ' ' . $t['personas'] : '';

// Ubicación
$ubicacion_display = '';
if ($alojamiento) {
    $partes = array_filter([$alojamiento['municipality'] ?? '', $alojamiento['province'] ?? '']);
    $ubicacion_display = implode(', ', $partes);
}

// JSON-LD Schema.org — Multi-graph (WebPage + BreadcrumbList + LodgingBusiness)
$jsonld = '';
if ($alojamiento) {

    // ── Tipo Schema.org más específico según categoría ──
    $cat_lower   = strtolower($alojamiento['category_name'] ?? $alojamiento['accommodation_type'] ?? '');
    $schema_type = 'LodgingBusiness';
    $type_map    = [
        'hotel'      => 'Hotel',
        'hostal'     => 'Hostel',
        'hostel'     => 'Hostel',
        'albergue'   => 'Hostel',
        'bed and breakfast' => 'BedAndBreakfast',
        'b&b'        => 'BedAndBreakfast',
        'casa de huéspedes' => 'BedAndBreakfast',
    ];
    foreach ($type_map as $kw => $stype) {
        if (strpos($cat_lower, $kw) !== false) { $schema_type = $stype; break; }
    }

    // ── ImageObject array ──
    $image_objects = [];
    foreach ($fotos as $idx => $foto_url) {
        $full_url = preg_match('/^https?:\/\//', $foto_url) ? $foto_url : 'https://rutasrurales.io' . $foto_url;
        $image_objects[] = [
            '@type'       => 'ImageObject',
            '@id'         => $canonical . '#photo' . ($idx + 1),
            'url'         => $full_url,
            'name'        => $alojamiento['name'] . ' — foto ' . ($idx + 1),
            'description' => $alojamiento['name'] . ' en ' . ($alojamiento['municipality'] ?? ''),
        ];
    }

    // ── Amenidades como LocationFeatureSpecification ──
    $amenity_features = [];
    if (!empty($alojamiento['amenities'])) {
        $ams = json_decode($alojamiento['amenities'], true);
        if (is_array($ams)) {
            foreach ($ams as $am) {
                $amenity_features[] = ['@type' => 'LocationFeatureSpecification', 'name' => $am, 'value' => true];
            }
        }
    }
    if (!empty($alojamiento['pet_friendly'])         && $alojamiento['pet_friendly'] == 1)
        $amenity_features[] = ['@type' => 'LocationFeatureSpecification', 'name' => 'Admite mascotas', 'value' => true];
    if (!empty($alojamiento['kitchen_available'])    && $alojamiento['kitchen_available'] == 1)
        $amenity_features[] = ['@type' => 'LocationFeatureSpecification', 'name' => 'Cocina disponible', 'value' => true];
    if (!empty($alojamiento['suitable_for_children'])&& $alojamiento['suitable_for_children'] == 1)
        $amenity_features[] = ['@type' => 'LocationFeatureSpecification', 'name' => 'Apto para niños', 'value' => true];
    if (!empty($alojamiento['wifi']) && $alojamiento['wifi'] == 1)
        $amenity_features[] = ['@type' => 'LocationFeatureSpecification', 'name' => 'WiFi gratuito', 'value' => true];

    // ── checkin/checkout en formato ISO 8601 (T15:00) ──
    $ci_raw = $alojamiento['check_in_time']  ?? '15:00';
    $co_raw = $alojamiento['check_out_time'] ?? '12:00';
    $checkin_time  = 'T' . (strlen($ci_raw) <= 5 ? $ci_raw : substr($ci_raw, 0, 5));
    $checkout_time = 'T' . (strlen($co_raw) <= 5 ? $co_raw : substr($co_raw, 0, 5));

    // ── LodgingBusiness ──
    $lodging = [
        '@type'        => $schema_type,
        '@id'          => $canonical . '#lodging',
        'name'         => $alojamiento['name'],
        'description'  => substr(strip_tags($alojamiento['description'] ?? ''), 0, 500),
        'url'          => $canonical,
        'image'        => !empty($image_objects) ? $image_objects : $fotos,
        'address'      => array_filter([
            '@type'           => 'PostalAddress',
            'streetAddress'   => $alojamiento['address'] ?? '',
            'addressLocality' => $alojamiento['municipality'] ?? '',
            'addressRegion'   => $alojamiento['province'] ?? '',
            'postalCode'      => $alojamiento['postal_code'] ?? '',
            'addressCountry'  => 'ES',
        ]),
        'checkinTime'  => $checkin_time,
        'checkoutTime' => $checkout_time,
    ];

    if (!empty($alojamiento['phone']))   $lodging['telephone']  = $alojamiento['phone'];
    if (!empty($alojamiento['email']))   $lodging['email']      = $alojamiento['email'];
    if (!empty($alojamiento['website'])) $lodging['sameAs']     = [$alojamiento['website']];

    if (!empty($alojamiento['price_per_night']) && $alojamiento['price_per_night'] > 0) {
        $lodging['priceRange'] = number_format($alojamiento['price_per_night'], 0, ',', '.') . '€/noche';
        $lodging['offers']     = [
            '@type'         => 'Offer',
            'name'          => 'Precio por noche en ' . $alojamiento['name'],
            'price'         => (float)$alojamiento['price_per_night'],
            'priceCurrency' => 'EUR',
            'availability'  => 'https://schema.org/InStock',
            'url'           => $canonical,
        ];
    }

    if (!empty($alojamiento['capacity']) && $alojamiento['capacity'] > 0) {
        $lodging['occupancy'] = [
            '@type'    => 'QuantitativeValue',
            'maxValue' => (int)$alojamiento['capacity'],
            'unitCode' => 'C62',
        ];
    }

    if (!empty($alojamiento['bedrooms']) && $alojamiento['bedrooms'] > 0) {
        $lodging['numberOfRooms'] = (int)$alojamiento['bedrooms'];
    }

    if (!empty($alojamiento['latitude']) && !empty($alojamiento['longitude'])) {
        $lodging['geo'] = [
            '@type'     => 'GeoCoordinates',
            'latitude'  => (float)$alojamiento['latitude'],
            'longitude' => (float)$alojamiento['longitude'],
        ];
        $lodging['hasMap'] = 'https://www.google.com/maps?q=' . $alojamiento['latitude'] . ',' . $alojamiento['longitude'];
    }

    if (!empty($amenity_features)) {
        $lodging['amenityFeature'] = $amenity_features;
    }

    if (isset($alojamiento['pet_friendly'])) {
        $lodging['petsAllowed'] = (bool)(int)$alojamiento['pet_friendly'];
    }

    // ── BreadcrumbList ──
    $breadcrumb_labels = [
        'es' => ['Inicio', 'Alojamientos turísticos'],
        'en' => ['Home', 'Accommodations'],
        'fr' => ['Accueil', 'Hébergements'],
        'de' => ['Startseite', 'Unterkünfte'],
        'zh' => ['首页', '住宿列表'],
    ];
    $bl = $breadcrumb_labels[$lang] ?? $breadcrumb_labels['es'];
    $breadcrumb_name = !empty($alojamiento['name']) ? $alojamiento['name'] : $slug;
    $breadcrumb = [
        '@type' => 'BreadcrumbList',
        '@id'   => $canonical . '#breadcrumb',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => $bl[0], 'item' => 'https://rutasrurales.io/'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $bl[1], 'item' => 'https://rutasrurales.io/alojamientos-turisticos'],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $breadcrumb_name, 'item' => $canonical],
        ],
    ];

    // ── WebPage ──
    $webpage = [
        '@type'       => 'WebPage',
        '@id'         => $canonical . '#webpage',
        'url'         => $canonical,
        'name'        => $page_title,
        'description' => $page_desc,
        'inLanguage'  => $lang === 'es' ? 'es-ES' : $lang . '-' . strtoupper($lang),
        'isPartOf'    => ['@id' => 'https://rutasrurales.io/#website'],
        'about'       => ['@id' => $canonical . '#lodging'],
        'breadcrumb'  => ['@id' => $canonical . '#breadcrumb'],
    ];
    if (!empty($image_objects)) {
        $webpage['primaryImageOfPage'] = ['@id' => $canonical . '#photo1'];
    }

    $jsonld_data = [
        '@context' => 'https://schema.org',
        '@graph'   => [$webpage, $breadcrumb, $lodging],
    ];
    $jsonld = json_encode($jsonld_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}

// Datos para JavaScript (evitar segunda llamada API)
$alo_js = $alojamiento ? json_encode([
    'id'          => $alojamiento['id'],
    'name'        => $alojamiento['name'],
    'slug'        => $alojamiento['slug'],
    'latitude'    => $alojamiento['latitude'],
    'longitude'   => $alojamiento['longitude'],
    'province'    => $alojamiento['province'],
    'municipality'=> $alojamiento['municipality'],
    'address'     => $alojamiento['address'] ?? '',
    'fotos'       => $fotos,
    'tipo'        => $tipo_display,
    'precio_noche'=> $alojamiento['price_per_night'] ?? 0,
    'capacidad'   => $alojamiento['capacity'] ?? 0,
    'phone'       => $alojamiento['phone'] ?? '',
    'email'       => $alojamiento['email'] ?? '',
    'website'     => $alojamiento['website'] ?? '',
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) : 'null';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_desc); ?>">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <link rel="canonical" href="<?php echo $canonical; ?>">

    <!-- hreflang — todos los idiomas disponibles -->
    <?php if ($alojamiento): ?>
    <link rel="alternate" hreflang="es"       href="https://rutasrurales.io/alojamiento/<?php echo htmlspecialchars($alojamiento['slug']); ?>">
    <link rel="alternate" hreflang="en"       href="https://rutasrurales.io/en/alojamiento/<?php echo htmlspecialchars($alojamiento['slug']); ?>">
    <link rel="alternate" hreflang="fr"       href="https://rutasrurales.io/fr/alojamiento/<?php echo htmlspecialchars($alojamiento['slug']); ?>">
    <link rel="alternate" hreflang="de"       href="https://rutasrurales.io/de/alojamiento/<?php echo htmlspecialchars($alojamiento['slug']); ?>">
    <link rel="alternate" hreflang="zh"       href="https://rutasrurales.io/zh/alojamiento/<?php echo htmlspecialchars($alojamiento['slug']); ?>">
    <link rel="alternate" hreflang="x-default" href="https://rutasrurales.io/alojamiento/<?php echo htmlspecialchars($alojamiento['slug']); ?>">
    <?php endif; ?>

    <!-- Open Graph -->
    <meta property="og:type"        content="place">
    <meta property="og:title"       content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($page_desc); ?>">
    <meta property="og:image"       content="<?php echo htmlspecialchars(preg_match('/^https?:\/\//', $foto_og) ? $foto_og : 'https://rutasrurales.io' . $foto_og); ?>">
    <meta property="og:image:width"  content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt"    content="<?php echo htmlspecialchars($alojamiento['name'] ?? 'Alojamiento en Rutas Rurales'); ?>">
    <meta property="og:url"         content="<?php echo $canonical; ?>">
    <meta property="og:site_name"   content="Rutas Rurales">
    <meta property="og:locale"      content="<?php echo $lang === 'es' ? 'es_ES' : ($lang === 'en' ? 'en_GB' : ($lang === 'fr' ? 'fr_FR' : ($lang === 'de' ? 'de_DE' : 'zh_CN'))); ?>">
    <?php if ($alojamiento && !empty($alojamiento['municipality'])): ?>
    <meta property="place:location:latitude"  content="<?php echo htmlspecialchars($alojamiento['latitude'] ?? ''); ?>">
    <meta property="place:location:longitude" content="<?php echo htmlspecialchars($alojamiento['longitude'] ?? ''); ?>">
    <?php endif; ?>

    <!-- Twitter / X Card -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:site"        content="@rutasrurales">
    <meta name="twitter:title"       content="<?php echo htmlspecialchars($page_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($page_desc); ?>">
    <meta name="twitter:image"       content="<?php echo htmlspecialchars(preg_match('/^https?:\/\//', $foto_og) ? $foto_og : 'https://rutasrurales.io' . $foto_og); ?>">
    <meta name="twitter:image:alt"   content="<?php echo htmlspecialchars($alojamiento['name'] ?? 'Alojamiento en Rutas Rurales'); ?>">

    <!-- Favicon -->
    <link rel="icon" href="/menu_images/Favicon.png" type="image/png">

    <!-- Preconnect -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://unpkg.com" crossorigin>

    <!-- Preload imagen hero -->
    <?php if (!empty($fotos[0])): ?>
    <link rel="preload" as="image" href="<?php echo htmlspecialchars($fotos[0]); ?>">
    <?php endif; ?>

    <!-- Fuentes locales -->
    <style>
        @font-face {
            font-family: 'Montserrat';
            font-style: normal;
            font-weight: 400;
            font-display: swap;
            src: local('Montserrat Regular'), local('Montserrat-Regular'),
                 url('/fonts/montserrat-v31-latin-regular.woff2') format('woff2');
        }
        @font-face {
            font-family: 'Montserrat';
            font-style: normal;
            font-weight: 500;
            font-display: swap;
            src: local('Montserrat Medium'), local('Montserrat-Medium'),
                 url('/fonts/montserrat-v31-latin-500.woff2') format('woff2');
        }
        @font-face {
            font-family: 'Montserrat';
            font-style: normal;
            font-weight: 600;
            font-display: swap;
            src: local('Montserrat SemiBold'), local('Montserrat-SemiBold'),
                 url('/fonts/montserrat-v31-latin-600.woff2') format('woff2');
        }
    </style>

    <!-- CSS CRÍTICO INLINE -->
    <style>
        /* ── Variables ── */
        :root {
            --primary: #2F5233;
            --primary-light: #3d6b42;
            --primary-dark: #1a3d1e;
            --accent: #81C784;
            --accent-warm: #F9A825;
            --text: #333;
            --text-light: #666;
            --bg: #f8f9fa;
            --white: #fff;
            --radius: 12px;
            --shadow: 0 4px 20px rgba(0,0,0,0.08);
            --shadow-hover: 0 8px 30px rgba(0,0,0,0.15);
            --transition: 0.2s ease;
        }

        /* ── Reset ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Montserrat', 'Segoe UI', sans-serif;
            color: var(--text);
            background: var(--bg);
            line-height: 1.6;
            overflow-x: hidden;
        }
        img { max-width: 100%; height: auto; display: block; }
        a { color: var(--primary); text-decoration: none; }
        a:hover { color: var(--primary-light); }

        /* ── Header ── */
        .site-header {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            background: var(--white);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: 70px;
            display: flex;
            align-items: center;
            padding: 0 20px;
            gap: 16px;
        }
        .site-header .logo {
            font-weight: 800;
            color: var(--primary);
            font-size: 1.1rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .site-header nav {
            margin-left: auto;
            display: flex;
            gap: 20px;
            font-size: 0.88rem;
            align-items: center;
        }
        .site-header nav a { color: var(--text); font-weight: 600; }
        .site-header nav a:hover { color: var(--primary); }
        .site-header nav .btn-login {
            background: var(--primary);
            color: var(--white);
            padding: 8px 18px;
            border-radius: 8px;
            font-weight: 700;
        }
        .site-header nav .btn-login:hover { background: var(--primary-light); color: var(--white); }

        /* ── Hero con foto de fondo ── */
        .alo-hero {
            margin-top: 70px;
            position: relative;
            min-height: 420px;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            overflow: hidden;
            background: var(--primary-dark);
        }
        .alo-hero-bg {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
        }
        .alo-hero-bg::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.1) 0%, rgba(0,0,0,0.65) 100%);
        }
        .alo-hero-content {
            position: relative;
            z-index: 2;
            padding: 28px 24px 36px;
            max-width: 1100px;
            margin: 0 auto;
            width: 100%;
        }
        .alo-breadcrumb {
            font-size: 0.78rem;
            color: rgba(255,255,255,0.75);
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .alo-breadcrumb a { color: rgba(255,255,255,0.75); }
        .alo-breadcrumb a:hover { color: #fff; }
        .alo-hero-badge {
            display: inline-block;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            color: var(--white);
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            text-transform: uppercase;
        }
        .alo-hero h1 {
            font-size: clamp(1.6rem, 4vw, 2.8rem);
            font-weight: 800;
            color: var(--white);
            line-height: 1.2;
            margin-bottom: 16px;
            text-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }
        .alo-hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            font-size: 0.92rem;
            color: rgba(255,255,255,0.92);
        }
        .alo-hero-meta span {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .alo-hero-price {
            background: var(--accent);
            color: var(--primary-dark);
            font-weight: 800;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 0.95rem;
        }
        /* Botones flotantes hero */
        .alo-hero-actions {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 3;
            display: flex;
            gap: 10px;
        }
        .alo-hero-btn {
            background: rgba(255,255,255,0.18);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.3);
            color: #fff;
            border-radius: 50%;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            font-size: 1rem;
            text-decoration: none;
        }
        .alo-hero-btn:hover { background: rgba(255,255,255,0.35); color: #fff; }

        /* ── Layout principal ── */
        .alo-layout {
            max-width: 1100px;
            margin: -40px auto 60px;
            padding: 0 16px;
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 24px;
            align-items: start;
        }
        @media (max-width: 900px) {
            .alo-layout { grid-template-columns: 1fr; margin-top: -30px; }
        }

        /* ── Card base ── */
        .alo-card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 24px;
        }
        .alo-card-body { padding: 28px; }
        .alo-card-title {
            font-size: 1.1rem !important;
            font-weight: 700 !important;
            color: #2F5233 !important;
            margin-bottom: 18px !important;
            padding-bottom: 12px !important;
            border-bottom: 2px solid #81C784 !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        /* ── Galería ── */
        .gallery-main {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 10px;
            cursor: pointer;
            background: #111;
        }
        .gallery-main-img {
            width: 100%;
            height: 380px;
            object-fit: cover;
            display: block;
            transition: transform 0.4s ease;
        }
        .gallery-main:hover .gallery-main-img { transform: scale(1.02); }
        .gallery-counter {
            position: absolute;
            bottom: 12px;
            right: 14px;
            background: rgba(0,0,0,0.55);
            color: #fff;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 12px;
        }
        .gallery-expand-btn {
            position: absolute;
            top: 12px;
            right: 14px;
            background: rgba(0,0,0,0.45);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 7px 12px;
            font-size: 0.8rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .gallery-expand-btn:hover { background: rgba(0,0,0,0.7); }
        .gallery-thumbs {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 8px;
        }
        .gallery-thumb {
            height: 68px;
            border-radius: 6px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            transition: border-color var(--transition);
        }
        .gallery-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .gallery-thumb.active { border-color: var(--primary); }
        .gallery-thumb:hover { border-color: var(--accent); }

        /* ── Descripción ── */
        .desc-text {
            line-height: 1.85;
            color: var(--text);
            font-size: 0.97rem;
        }
        /* Inbound links dentro de la descripción — visibles y diferenciados */
        .desc-text a,
        .desc-text a:visited {
            color: #2F5233;
            text-decoration: underline;
            text-decoration-color: rgba(47, 82, 51, 0.4);
            text-underline-offset: 2px;
            font-weight: 600;
            transition: color 0.15s, text-decoration-color 0.15s;
        }
        .desc-text a:hover {
            color: #1a3a1e;
            text-decoration-color: #2F5233;
        }
        .desc-text h2 a, .desc-text h3 a, .desc-text h4 a {
            font-size: inherit;
            font-weight: inherit;
        }
        .desc-text.collapsed {
            max-height: 130px;
            overflow: hidden;
            position: relative;
        }
        .desc-text.collapsed::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 50px;
            background: linear-gradient(transparent, var(--white));
        }
        .desc-expand-btn {
            background: none;
            border: 1px solid var(--accent);
            color: var(--primary);
            padding: 7px 18px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 12px;
            transition: all var(--transition);
        }
        .desc-expand-btn:hover { background: var(--primary); color: #fff; border-color: var(--primary); }

        /* ── Meta grid (características) ── */
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 14px;
            margin-top: 20px;
        }
        .meta-item {
            background: var(--bg);
            border-radius: 8px;
            padding: 14px;
            border-left: 3px solid var(--accent);
        }
        .meta-item .meta-icon { font-size: 1.2rem; margin-bottom: 6px; }
        .meta-item .meta-label {
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .meta-item .meta-value {
            font-size: 0.92rem;
            font-weight: 600;
            color: var(--text);
        }

        /* ── Contacto ── */
        .contact-btns {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 16px;
        }
        .btn-contact {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 20px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all var(--transition);
            border: none;
            cursor: pointer;
        }
        .btn-phone    { background: var(--primary); color: #fff; }
        .btn-whatsapp { background: #25D366; color: #fff; }
        .btn-email    { background: var(--accent-warm); color: #1a1a1a; }
        .btn-website  { background: var(--accent); color: var(--primary-dark); }
        .btn-contact:hover { transform: translateY(-2px); box-shadow: var(--shadow-hover); }
        .contact-address {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            color: var(--text-light);
            font-size: 0.88rem;
            padding: 12px;
            background: var(--bg);
            border-radius: 8px;
        }

        /* ── Card de ubicación (enlace a rutas.php) ── */
        .location-card {
            background: linear-gradient(135deg, #e8f0e8, #d4e8d4);
            border-radius: var(--radius);
            padding: 28px;
            margin-bottom: 24px;
            text-align: center;
            border: 1px solid #c8e6c9;
        }
        .location-card .loc-icon { font-size: 3rem; margin-bottom: 12px; }
        .location-card h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
        }
        .location-card p {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 6px;
            line-height: 1.5;
        }
        .location-card .loc-address {
            font-size: 0.88rem;
            color: var(--text);
            font-weight: 600;
            margin-bottom: 18px;
            padding: 8px 12px;
            background: rgba(255,255,255,0.6);
            border-radius: 8px;
            display: inline-block;
        }
        .location-card .loc-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--primary);
            color: #fff;
            padding: 12px 28px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.95rem;
            text-decoration: none;
            transition: all var(--transition);
        }
        .location-card .loc-btn:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
            color: #fff;
        }

        /* ── Contenido cercano ── */
        .nearby-section { margin-bottom: 24px; }
        .nearby-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
        }
        .nearby-card {
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #eee;
            transition: all 0.2s;
            background: var(--white);
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .nearby-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-3px);
            color: inherit;
        }
        .nearby-card-img {
            height: 120px;
            background: #e8f0e8;
            overflow: hidden;
            position: relative;
        }
        .nearby-card-img img { width: 100%; height: 100%; object-fit: cover; }
        .nearby-card-img-placeholder {
            width: 100%; height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            background: linear-gradient(135deg, #e8f0e8, #d0e4d0);
        }
        .nearby-card-dist {
            position: absolute;
            bottom: 6px; right: 8px;
            background: rgba(0,0,0,0.55);
            color: #fff;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 10px;
        }
        .nearby-card-body { padding: 10px 12px; }
        .nearby-card-name {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .nearby-card-meta {
            font-size: 0.75rem;
            color: var(--text-light);
            margin-bottom: 4px;
        }
        .nearby-card-price {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--primary);
            margin-top: 4px;
        }
        .nearby-card-free {
            font-size: 0.75rem;
            font-weight: 700;
            color: #2e7d32;
            background: #e8f5e9;
            padding: 2px 8px;
            border-radius: 10px;
            display: inline-block;
            margin-top: 4px;
        }
        .nearby-show-more {
            text-align: center;
            margin-top: 12px;
        }
        .nearby-show-more button {
            background: none;
            border: 1px solid var(--primary);
            color: var(--primary);
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.85rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }
        .nearby-show-more button:hover { background: var(--primary); color: var(--white); }
        .nearby-empty {
            grid-column: 1 / -1;
            text-align: center;
            padding: 30px;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        /* ── Skeleton ── */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s infinite;
            border-radius: 4px;
        }
        @keyframes skeleton-loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        .skeleton-card { height: 120px; border-radius: 8px; }

        /* ── Sidebar ── */
        .alo-sidebar { position: sticky; top: 90px; }

        /* ── Price card ── */
        .price-card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: 0 4px 24px rgba(0,0,0,0.11);
            padding: 24px;
            margin-bottom: 16px;
            border-top: 4px solid var(--primary);
        }
        .price-amount {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
            margin-bottom: 4px;
        }
        .price-label {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-bottom: 16px;
        }
        .price-features {
            list-style: none;
            margin-bottom: 16px;
            padding: 0;
        }
        .price-features li {
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            font-size: 0.85rem !important;
            font-weight: 500 !important;
            color: #333333 !important;
            padding: 7px 0 !important;
            border-bottom: 1px solid #f0f0f0 !important;
            line-height: 1.4 !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        .price-features li:last-child { border-bottom: none !important; }
        /* Amenities chips */
        .amenity-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 16px;
        }
        .amenity-chip {
            background: #e8f5e9;
            color: #2F5233;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            border: 1px solid #c8e6c9;
        }
        /* Botones sidebar compactos */
        .sidebar-btns {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .btn-sidebar {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 8px !important;
            padding: 10px 16px !important;
            border-radius: 8px !important;
            font-weight: 700 !important;
            font-size: 0.88rem !important;
            cursor: pointer !important;
            border: none !important;
            transition: all 0.2s ease !important;
            text-decoration: none !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
        .btn-sidebar-primary { background: #2F5233 !important; color: #fff !important; }
        .btn-sidebar-primary:hover { background: #3d6b42 !important; color: #fff !important; }
        .btn-sidebar-wa { background: #25D366 !important; color: #fff !important; }
        .btn-sidebar-wa:hover { background: #1da851 !important; color: #fff !important; }
        .btn-sidebar-email { background: #F9A825 !important; color: #1a1a1a !important; }
        .btn-sidebar-email:hover { background: #f57f17 !important; color: #fff !important; }
        .btn-sidebar-web { background: #e8f5e9 !important; color: #2F5233 !important; border: 1px solid #c8e6c9 !important; }
        .btn-sidebar-web:hover { background: #c8e6c9 !important; color: #2F5233 !important; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
            text-decoration: none;
            width: 100%;
        }
        .btn-primary { background: var(--primary); color: var(--white); }
        .btn-primary:hover { background: var(--primary-light); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(47,82,51,0.3); color: var(--white); }
        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
            margin-top: 8px;
        }
        .btn-outline:hover { background: var(--primary); color: var(--white); }
        .btn-whatsapp-full { background: #25D366; color: #fff; margin-top: 8px; }
        .btn-whatsapp-full:hover { background: #1da851; color: #fff; }

        /* ── CTA card ── */
        .cta-card {
            background: linear-gradient(135deg, #2F5233, #1a3d1e);
            color: #ffffff;
            border-radius: var(--radius);
            padding: 22px;
            margin-bottom: 16px;
            text-align: center;
        }
        .cta-card h3 {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: #ffffff !important;
        }
        .cta-card p {
            font-size: 0.82rem;
            color: rgba(255,255,255,0.88) !important;
            margin-bottom: 14px;
            line-height: 1.5;
        }
        .btn-white {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background: #ffffff !important;
            color: #2F5233 !important;
            padding: 10px 16px !important;
            border-radius: 8px !important;
            font-weight: 700 !important;
            font-size: 0.85rem !important;
            text-decoration: none !important;
            margin-bottom: 8px !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
        .btn-white:hover { background: #f0f0f0 !important; color: #2F5233 !important; }
        .btn-outline-white {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background: transparent !important;
            color: #ffffff !important;
            border: 2px solid rgba(255,255,255,0.6) !important;
            padding: 9px 16px !important;
            border-radius: 8px !important;
            font-weight: 600 !important;
            font-size: 0.82rem !important;
            text-decoration: none !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
        .btn-outline-white:hover { background: rgba(255,255,255,0.1) !important; border-color: #fff !important; color: #fff !important; }

        /* ── Lightbox ── */
        .lightbox-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.93);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        .lightbox-overlay.active { display: flex; }
        .lightbox-img {
            max-width: 92vw;
            max-height: 88vh;
            border-radius: 6px;
            object-fit: contain;
        }
        .lightbox-close {
            position: absolute;
            top: 18px; right: 22px;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            background: none;
            border: none;
            line-height: 1;
            opacity: 0.8;
        }
        .lightbox-close:hover { opacity: 1; }
        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255,255,255,0.15);
            border: none;
            color: white;
            font-size: 1.5rem;
            padding: 12px 16px;
            cursor: pointer;
            border-radius: 4px;
        }
        .lightbox-prev { left: 16px; }
        .lightbox-next { right: 16px; }
        .lightbox-caption {
            position: absolute;
            bottom: 20px;
            color: rgba(255,255,255,0.7);
            font-size: 0.85rem;
        }

        /* ── Toast ── */
        .toast {
            position: fixed;
            bottom: 24px; right: 24px;
            background: var(--primary);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            z-index: 9998;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        .toast.show { transform: translateY(0); opacity: 1; }

        /* ── Footer ── */
        .site-footer {
            background: #1a2e1a;
            color: rgba(255,255,255,0.8);
            padding: 32px 20px;
            text-align: center;
            font-size: 0.85rem;
        }
        .footer-links { display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; margin-bottom: 12px; }
        .footer-links a { color: rgba(255,255,255,0.7); }
        .footer-links a:hover { color: var(--accent); }
        .footer-social { display: flex; justify-content: center; gap: 16px; margin-bottom: 16px; }
        .footer-social a { color: rgba(255,255,255,0.7); font-size: 1.2rem; }
        .footer-social a:hover { color: var(--accent); }

        /* ── Error ── */
        .error-container {
            text-align: center;
            padding: 120px 20px 60px;
            max-width: 500px;
            margin: 0 auto;
        }
        .error-container .error-icon { font-size: 4rem; margin-bottom: 20px; }
        .error-container h1 { font-size: 1.6rem; margin-bottom: 12px; color: var(--primary); }
        .error-container p { color: var(--text-light); margin-bottom: 24px; }

        /* ── Responsive ── */
        @media (max-width: 600px) {
            .alo-hero { min-height: 320px; }
            .alo-hero h1 { font-size: 1.5rem; }
            .alo-card-body { padding: 20px; }
            .gallery-main-img { height: 240px; }
            .meta-grid { grid-template-columns: 1fr 1fr; }
            .nearby-grid { grid-template-columns: 1fr 1fr; }
            .contact-btns { flex-direction: column; }
            .btn-contact { justify-content: center; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
        }
    </style>

    <!-- Schema.org JSON-LD (LodgingBusiness/Hotel + FAQPage + WebSite + BreadcrumbList) -->
    <?php if ($alojamiento): renderAlojamientoSchema($alojamiento, $fotos, $canonical, $page_title, $page_desc, $lang); endif; ?>

    <!-- Datos para JS -->
    <script>
        window.ALO_DATA = <?php echo $alo_js; ?>;
        window.ALO_SLUG = <?php echo json_encode($slug); ?>;
        window.ALO_LANG = <?php echo json_encode($lang); ?>;
        window.ALO_T = <?php echo json_encode([
            'ver_mas'        => $t['ver_mas_aloj'] ?? 'Ver más',
            'sin_resultados' => $t['sin_resultados'] ?? 'Sin resultados',
            'gratis'         => $t['gratis'] ?? 'Gratis',
            'km'             => $t['km'] ?? 'km',
            'noche'          => $t['noche'] ?? 'noche',
            'desde'          => $t['desde'] ?? 'desde',
        ]); ?>;
    </script>
</head>
<body>

<!-- ── HEADER ── -->
<?php
$header_path = dirname(__DIR__) . '/header.php';
if (file_exists($header_path)) {
    // Capturar output de header.php y extraer SOLO el elemento <header>
    // header.php genera <!DOCTYPE html><html><head>...</head><body>... que duplicaría
    // <title>, <meta description> y <link canonical> — debemos evitarlo.
    ob_start();
    include $header_path;
    $header_html = ob_get_clean();

    // Eliminar SOLO los tags que duplicarían SEO en nuestro propio <head>
    // Conservamos TODO lo demás: styles.css, Font Awesome, <style> de nav, GTM, <header>
    $header_html = preg_replace('/<!DOCTYPE[^>]*>\s*/i', '', $header_html);
    $header_html = preg_replace('/<html[^>]*>\s*/i', '', $header_html);
    $header_html = preg_replace('/<\/html>\s*/i', '', $header_html);
    // Quitar los wrappers <head> y </head> pero dejar su contenido (CSS, FA, etc.)
    $header_html = preg_replace('/<head[^>]*>\s*/i', '', $header_html);
    $header_html = preg_replace('/<\/head>\s*/i', '', $header_html);
    // Quitar tags SEO duplicados
    $header_html = preg_replace('/<title[^>]*>.*?<\/title>\s*/is', '', $header_html);
    $header_html = preg_replace('/<meta\s[^>]*name=["\']description["\'][^>]*>\s*/i', '', $header_html);
    $header_html = preg_replace('/<link\s[^>]*rel=["\']canonical["\'][^>]*>\s*/i', '', $header_html);
    // Quitar la apertura <body> (header.php no la cierra, la cerramos nosotros al final)
    $header_html = preg_replace('/<body[^>]*>\s*/i', '', $header_html);

    echo $header_html;
} else {
    echo '<header class="site-header">
        <a href="/" class="logo">🌿 Rutas Rurales</a>
        <nav>
            <a href="/alojamientos-turisticos.html">' . ($t['alojamientos'] ?? 'Alojamientos') . '</a>
            <a href="/eventos-culturales-paginacion.html">Eventos</a>
            <a href="/login.html" class="btn-login">' . ($t['acceder_link'] ?? 'Acceder') . '</a>
        </nav>
    </header>';
}
?>

<!-- ── OVERRIDE CSS (después del header.php para ganar a styles.css) ── -->
<style>
/* Anular colisiones con styles.css global */
.alo-card { background:#fff !important; border-radius:12px !important; box-shadow:0 4px 20px rgba(0,0,0,0.08) !important; overflow:hidden !important; margin-bottom:24px !important; transform:none !important; }
.alo-card:hover { transform:none !important; box-shadow:0 8px 30px rgba(0,0,0,0.12) !important; }
.alo-card-body { padding:28px !important; }
.alo-card-title { font-size:1.1rem !important; font-weight:700 !important; color:#2F5233 !important; margin-bottom:18px !important; padding-bottom:12px !important; border-bottom:2px solid #81C784 !important; display:flex !important; align-items:center !important; gap:8px !important; visibility:visible !important; opacity:1 !important; }
.cta-card { background:linear-gradient(135deg,#2F5233,#1a3d1e) !important; color:#fff !important; border-radius:12px !important; padding:22px !important; margin-bottom:16px !important; text-align:center !important; }
.cta-card h3 { font-size:1rem !important; font-weight:700 !important; color:#ffffff !important; margin-bottom:8px !important; opacity:1 !important; visibility:visible !important; }
.cta-card p { font-size:0.82rem !important; color:rgba(255,255,255,0.88) !important; margin-bottom:14px !important; line-height:1.5 !important; opacity:1 !important; visibility:visible !important; }
.price-features li { display:flex !important; align-items:center !important; gap:8px !important; font-size:0.85rem !important; font-weight:500 !important; color:#333 !important; padding:7px 0 !important; border-bottom:1px solid #f0f0f0 !important; visibility:visible !important; opacity:1 !important; }
.price-features li:last-child { border-bottom:none !important; }
.amenity-chip { background:#e8f5e9 !important; color:#2F5233 !important; font-size:0.75rem !important; font-weight:600 !important; padding:4px 10px !important; border-radius:20px !important; border:1px solid #c8e6c9 !important; display:inline-block !important; }
.btn-sidebar { display:flex !important; align-items:center !important; justify-content:center !important; gap:8px !important; padding:10px 16px !important; border-radius:8px !important; font-weight:700 !important; font-size:0.88rem !important; text-decoration:none !important; width:100% !important; box-sizing:border-box !important; }
.btn-sidebar-primary { background:#2F5233 !important; color:#fff !important; }
.btn-sidebar-wa { background:#25D366 !important; color:#fff !important; }
.btn-sidebar-email { background:#F9A825 !important; color:#1a1a1a !important; }
.btn-sidebar-web { background:#e8f5e9 !important; color:#2F5233 !important; border:1px solid #c8e6c9 !important; }
.nearby-card-name { font-size:0.85rem !important; font-weight:700 !important; color:#333 !important; }
.nearby-card-meta { font-size:0.75rem !important; color:#666 !important; }
.nearby-card-price { font-size:0.8rem !important; font-weight:700 !important; color:#2F5233 !important; }
/* ── Footer links — forzar color blanco sobre fondo oscuro ── */
.site-footer .footer-links a,
.site-footer .footer-links a:visited { color: rgba(255,255,255,0.85) !important; text-decoration: none !important; opacity: 1 !important; }
.site-footer .footer-links a:hover { color: #81C784 !important; }
.site-footer .footer-social a,
.site-footer .footer-social a:visited { color: rgba(255,255,255,0.75) !important; opacity: 1 !important; }
.site-footer .footer-social a:hover { color: #81C784 !important; }
</style>

<?php if ($alojamiento): ?>

<!-- ── HERO ── -->
<section class="alo-hero">
    <div class="alo-hero-bg" style="background-image:url('<?php echo htmlspecialchars($fotos[0]); ?>')"></div>

    <div class="alo-hero-actions">
        <button class="alo-hero-btn" id="btn-share" title="<?php echo $t['compartir']; ?>" aria-label="<?php echo $t['compartir']; ?>">🔗</button>
        <button class="alo-hero-btn" id="btn-fav" title="<?php echo $t['favorito']; ?>" aria-label="<?php echo $t['favorito']; ?>">🤍</button>
    </div>

    <div class="alo-hero-content">
        <nav class="alo-breadcrumb" aria-label="breadcrumb">
            <a href="/">🏠 Inicio</a>
            <span>/</span>
            <a href="/alojamientos-turisticos"><?php echo $t['alojamientos']; ?></a>
            <span>/</span>
            <span><?php echo htmlspecialchars($alojamiento['name']); ?></span>
        </nav>

        <div class="alo-hero-badge"><?php echo htmlspecialchars($tipo_display); ?></div>

        <h1><?php echo htmlspecialchars($alojamiento['name']); ?></h1>

        <div class="alo-hero-meta">
            <?php if ($ubicacion_display): ?>
            <span>📍 <?php echo htmlspecialchars($ubicacion_display); ?></span>
            <?php endif; ?>
            <?php if ($capacidad_display): ?>
            <span>👥 <?php echo htmlspecialchars($capacidad_display); ?></span>
            <?php endif; ?>
            <?php if (!empty($alojamiento['check_in_time'])): ?>
            <span>🕐 <?php echo $t['checkin']; ?>: <?php echo htmlspecialchars($alojamiento['check_in_time']); ?></span>
            <?php endif; ?>
            <?php if ($precio_display): ?>
            <span class="alo-hero-price"><?php echo $t['precio_desde']; ?> <?php echo htmlspecialchars($precio_display); ?> / <?php echo $t['noche']; ?></span>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ── LAYOUT PRINCIPAL ── -->
<div class="alo-layout">

    <!-- ── COLUMNA PRINCIPAL ── -->
    <main>

        <!-- Galería de fotos -->
        <?php if (!empty($fotos)): ?>
        <div class="alo-card">
            <div class="alo-card-body">
                <h2 class="alo-card-title" style="font-size:1.1rem;font-weight:700;color:#2F5233;margin-bottom:18px;padding-bottom:12px;border-bottom:2px solid #81C784;display:flex;align-items:center;gap:8px;">📸 Galería de fotos</h2>
                <div class="gallery-main" id="gallery-main" onclick="openLightbox(currentGalleryIdx)">
                    <img id="gallery-main-img"
                         src="<?php echo htmlspecialchars($fotos[0]); ?>"
                         alt="<?php echo htmlspecialchars($alojamiento['name']); ?>"
                         class="gallery-main-img"
                         loading="eager"
                         width="800" height="380">
                    <?php if (count($fotos) > 1): ?>
                    <span class="gallery-counter" id="gallery-counter">1 / <?php echo count($fotos); ?></span>
                    <button class="gallery-expand-btn" onclick="event.stopPropagation();openLightbox(currentGalleryIdx)" type="button">
                        🔍 <?php echo $t['ver_todas']; ?>
                    </button>
                    <?php endif; ?>
                </div>
                <?php if (count($fotos) > 1): ?>
                <div class="gallery-thumbs" id="gallery-thumbs">
                    <?php foreach ($fotos as $i => $foto): ?>
                    <div class="gallery-thumb <?php echo $i === 0 ? 'active' : ''; ?>"
                         data-index="<?php echo $i; ?>"
                         onclick="setGalleryPhoto(<?php echo $i; ?>)"
                         role="button" tabindex="0">
                        <img src="<?php echo htmlspecialchars($foto); ?>"
                             alt="Foto <?php echo $i+1; ?>"
                             loading="<?php echo $i < 3 ? 'eager' : 'lazy'; ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Descripción -->
        <div class="alo-card">
            <div class="alo-card-body">
                <h2 class="alo-card-title" style="font-size:1.1rem;font-weight:700;color:#2F5233;margin-bottom:18px;padding-bottom:12px;border-bottom:2px solid #81C784;display:flex;align-items:center;gap:8px;">📋 Descripción</h2>
                <?php if (!empty($alojamiento['description'])): ?>
                <?php
                // Usar description_linked (inbound links pre-generados, cero overhead en velocidad)
                // Fallback a description si aún no está regenerado
                // IMPORTANTE: incluir <a> en allowed_tags para que los inbound links se preserven
                $allowed_tags = '<strong><b><em><i><u><p><br><ul><ol><li><h2><h3><h4><span><a>';
                $desc_raw  = !empty($alojamiento['description_linked'])
                    ? $alojamiento['description_linked']
                    : $alojamiento['description'];
                $desc_safe = strip_tags($desc_raw, $allowed_tags);
                $longDesc  = strlen(strip_tags($desc_safe)) > 350;
                ?>
                <div class="desc-text <?php echo $longDesc ? 'collapsed' : ''; ?>" id="desc-text">
                    <?php echo nl2br($desc_safe); ?>
                </div>
                <?php if ($longDesc): ?>
                <button class="desc-expand-btn" id="desc-expand-btn" onclick="expandDesc()">
                    ↓ <?php echo $t['leer_mas']; ?>
                </button>
                <?php endif; ?>
                <?php else: ?>
                <p style="color:var(--text-light);font-style:italic;">No hay descripción disponible.</p>
                <?php endif; ?>

                <!-- Características -->
                <div class="meta-grid">
                    <?php if ($tipo_display): ?>
                    <div class="meta-item">
                        <div class="meta-icon">🏠</div>
                        <div class="meta-label"><?php echo $t['tipo']; ?></div>
                        <div class="meta-value"><?php echo htmlspecialchars($tipo_display); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($capacidad_display): ?>
                    <div class="meta-item">
                        <div class="meta-icon">👥</div>
                        <div class="meta-label"><?php echo $t['capacidad']; ?></div>
                        <div class="meta-value"><?php echo htmlspecialchars($capacidad_display); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($alojamiento['check_in_time'])): ?>
                    <div class="meta-item">
                        <div class="meta-icon">🔑</div>
                        <div class="meta-label"><?php echo $t['checkin']; ?></div>
                        <div class="meta-value"><?php echo htmlspecialchars($alojamiento['check_in_time']); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($alojamiento['check_out_time'])): ?>
                    <div class="meta-item">
                        <div class="meta-icon">🚪</div>
                        <div class="meta-label"><?php echo $t['checkout']; ?></div>
                        <div class="meta-value"><?php echo htmlspecialchars($alojamiento['check_out_time']); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($alojamiento['services'])): ?>
                    <div class="meta-item">
                        <div class="meta-icon">⭐</div>
                        <div class="meta-label"><?php echo $t['servicios']; ?></div>
                        <div class="meta-value"><?php echo htmlspecialchars($alojamiento['services']); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($alojamiento['price_per_night']) && $alojamiento['price_per_night'] > 0): ?>
                    <div class="meta-item">
                        <div class="meta-icon">💶</div>
                        <div class="meta-label"><?php echo $t['precio_noche']; ?></div>
                        <div class="meta-value"><?php echo htmlspecialchars($precio_display); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Contacto -->
        <?php if (!empty($alojamiento['phone']) || !empty($alojamiento['email']) || !empty($alojamiento['website'])): ?>
        <div class="alo-card">
            <div class="alo-card-body">
                <h2 class="alo-card-title" style="font-size:1.1rem;font-weight:700;color:#2F5233;margin-bottom:18px;padding-bottom:12px;border-bottom:2px solid #81C784;display:flex;align-items:center;gap:8px;">📞 Contacto</h2>
                <div class="contact-btns">
                    <?php if (!empty($alojamiento['phone'])): ?>
                    <a href="tel:<?php echo htmlspecialchars($alojamiento['phone']); ?>" class="btn-contact btn-phone">
                        📞 <?php echo $t['llamar']; ?>
                    </a>
                    <a href="https://wa.me/34<?php echo preg_replace('/[^0-9]/', '', $alojamiento['phone']); ?>"
                       target="_blank" rel="noopener" class="btn-contact btn-whatsapp">
                        💬 <?php echo $t['whatsapp']; ?>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($alojamiento['email'])): ?>
                    <a href="mailto:<?php echo htmlspecialchars($alojamiento['email']); ?>" class="btn-contact btn-email">
                        ✉️ <?php echo $t['email']; ?>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($alojamiento['website'])): ?>
                    <a href="<?php echo htmlspecialchars($alojamiento['website']); ?>"
                       target="_blank" rel="noopener" class="btn-contact btn-website">
                        🌐 <?php echo $t['web']; ?>
                    </a>
                    <?php endif; ?>
                </div>
                <?php if (!empty($alojamiento['address'])): ?>
                <div class="contact-address">
                    <span>📍</span>
                    <span><?php
                        echo htmlspecialchars($alojamiento['address']);
                        if (!empty($alojamiento['municipality'])) echo ', ' . htmlspecialchars($alojamiento['municipality']);
                        if (!empty($alojamiento['province'])) echo ' (' . htmlspecialchars($alojamiento['province']) . ')';
                    ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Card de ubicación con enlace a rutas.php -->
        <?php if (!empty($alojamiento['latitude']) && !empty($alojamiento['longitude'])): 
            $rutas_url = 'https://rutasrurales.io/rutas.php'
                . '?lat=' . urlencode($alojamiento['latitude'])
                . '&lng=' . urlencode($alojamiento['longitude'])
                . '&provincia=' . urlencode($alojamiento['municipality'] ?? $alojamiento['province'] ?? '')
                . '&radius=30';
            $direccion_completa = trim(($alojamiento['address'] ?? '') . ', ' . ($alojamiento['municipality'] ?? '') . ', ' . ($alojamiento['province'] ?? ''), ', ');
        ?>
        <div class="location-card">
            <div class="loc-icon">🗺️</div>
            <h3><?php echo $t['explora_alrededores']; ?></h3>
            <p><?php echo $t['descubre_alrededor']; ?> <strong><?php echo htmlspecialchars($alojamiento['municipality'] ?? $alojamiento['province'] ?? ''); ?></strong></p>
            <?php if ($direccion_completa): ?>
            <div class="loc-address">📍 <?php echo htmlspecialchars($direccion_completa); ?></div>
            <?php endif; ?>
            <a href="<?php echo $rutas_url; ?>" class="loc-btn" target="_blank" rel="noopener">
                🗺️ <?php echo $t['ver_en_rutas']; ?>
            </a>
        </div>
        <?php endif; ?>

        <!-- CONTENIDO CERCANO -->
        <div id="nearby-alojamientos-section" class="alo-card" style="display:none;">
            <div class="alo-card-body">
                <h2 class="alo-card-title" style="font-size:1.1rem;font-weight:700;color:#2F5233;margin-bottom:18px;padding-bottom:12px;border-bottom:2px solid #81C784;display:flex;align-items:center;gap:8px;">🏠 Alojamientos cercanos</h2>
                <div id="nearby-alojamientos" class="nearby-grid">
                    <div class="skeleton skeleton-card"></div>
                    <div class="skeleton skeleton-card"></div>
                    <div class="skeleton skeleton-card"></div>
                </div>
                <div class="nearby-show-more" id="more-alojamientos" style="display:none;">
                    <button onclick="showMoreNearby('alojamientos')"><?php echo $t['ver_mas_aloj']; ?></button>
                </div>
            </div>
        </div>

        <div id="nearby-lugares-section" class="alo-card" style="display:none;">
            <div class="alo-card-body">
                <h2 class="alo-card-title" style="font-size:1.1rem;font-weight:700;color:#2F5233;margin-bottom:18px;padding-bottom:12px;border-bottom:2px solid #81C784;display:flex;align-items:center;gap:8px;">🏛️ Lugares de interés cercanos</h2>
                <div id="nearby-lugares" class="nearby-grid">
                    <div class="skeleton skeleton-card"></div>
                    <div class="skeleton skeleton-card"></div>
                    <div class="skeleton skeleton-card"></div>
                </div>
                <div class="nearby-show-more" id="more-lugares" style="display:none;">
                    <button onclick="showMoreNearby('lugares')"><?php echo $t['ver_mas_lugares']; ?></button>
                </div>
            </div>
        </div>

        <div id="nearby-actividades-section" class="alo-card" style="display:none;">
            <div class="alo-card-body">
                <h2 class="alo-card-title" style="font-size:1.1rem;font-weight:700;color:#2F5233;margin-bottom:18px;padding-bottom:12px;border-bottom:2px solid #81C784;display:flex;align-items:center;gap:8px;">🎯 Actividades turísticas cercanas</h2>
                <div id="nearby-actividades" class="nearby-grid">
                    <div class="skeleton skeleton-card"></div>
                    <div class="skeleton skeleton-card"></div>
                    <div class="skeleton skeleton-card"></div>
                </div>
                <div class="nearby-show-more" id="more-actividades" style="display:none;">
                    <button onclick="showMoreNearby('actividades')"><?php echo $t['ver_mas_activ']; ?></button>
                </div>
            </div>
        </div>

        <div id="nearby-eventos-section" class="alo-card" style="display:none;">
            <div class="alo-card-body">
                <h2 class="alo-card-title" style="font-size:1.1rem;font-weight:700;color:#2F5233;margin-bottom:18px;padding-bottom:12px;border-bottom:2px solid #81C784;display:flex;align-items:center;gap:8px;">🎭 Eventos culturales cercanos</h2>
                <div id="nearby-eventos" class="nearby-grid">
                    <div class="skeleton skeleton-card"></div>
                    <div class="skeleton skeleton-card"></div>
                    <div class="skeleton skeleton-card"></div>
                </div>
                <div class="nearby-show-more" id="more-eventos" style="display:none;">
                    <button onclick="showMoreNearby('eventos')"><?php echo $t['ver_mas_eventos']; ?></button>
                </div>
            </div>
        </div>

    </main>

    <!-- ── SIDEBAR ── -->
    <aside class="alo-sidebar">

        <!-- Precio + Reserva -->
        <div class="price-card">
            <?php if (!empty($alojamiento['price_per_night']) && $alojamiento['price_per_night'] > 0): ?>
            <div class="price-amount"><?php echo htmlspecialchars($precio_display); ?></div>
            <div class="price-label"><?php echo $t['precio_noche']; ?></div>
            <?php else: ?>
            <div class="price-amount" style="font-size:1.3rem;color:#2F5233;"><?php echo $t['consultar']; ?></div>
            <div class="price-label" style="margin-bottom:14px;"></div>
            <?php endif; ?>

            <!-- Datos del alojamiento -->
            <ul class="price-features">
                <?php if ($tipo_display): ?>
                <li style="display:flex!important;align-items:center!important;gap:8px!important;color:#333!important;font-size:0.85rem!important;padding:7px 0!important;border-bottom:1px solid #f0f0f0!important;">
                    <span>🏠</span><span><?php echo htmlspecialchars($tipo_display); ?></span>
                </li>
                <?php endif; ?>
                <?php if (!empty($alojamiento['capacity']) && $alojamiento['capacity'] > 0): ?>
                <li style="display:flex!important;align-items:center!important;gap:8px!important;color:#333!important;font-size:0.85rem!important;padding:7px 0!important;border-bottom:1px solid #f0f0f0!important;">
                    <span>👥</span><span><?php echo (int)$alojamiento['capacity']; ?> <?php echo $t['personas']; ?></span>
                </li>
                <?php endif; ?>
                <?php if (!empty($alojamiento['bedrooms']) && $alojamiento['bedrooms'] > 0): ?>
                <li style="display:flex!important;align-items:center!important;gap:8px!important;color:#333!important;font-size:0.85rem!important;padding:7px 0!important;border-bottom:1px solid #f0f0f0!important;">
                    <span>🛏️</span><span><?php echo (int)$alojamiento['bedrooms']; ?> habitaciones</span>
                </li>
                <?php endif; ?>
                <?php if (!empty($alojamiento['bathrooms']) && $alojamiento['bathrooms'] > 0): ?>
                <li style="display:flex!important;align-items:center!important;gap:8px!important;color:#333!important;font-size:0.85rem!important;padding:7px 0!important;border-bottom:1px solid #f0f0f0!important;">
                    <span>🚿</span><span><?php echo (int)$alojamiento['bathrooms']; ?> baños</span>
                </li>
                <?php endif; ?>
                <?php if (!empty($alojamiento['min_nights']) && $alojamiento['min_nights'] > 1): ?>
                <li style="display:flex!important;align-items:center!important;gap:8px!important;color:#333!important;font-size:0.85rem!important;padding:7px 0!important;border-bottom:1px solid #f0f0f0!important;">
                    <span>📅</span><span>Mín. <?php echo (int)$alojamiento['min_nights']; ?> noches</span>
                </li>
                <?php endif; ?>
                <?php if (!empty($alojamiento['municipality'])): ?>
                <li style="display:flex!important;align-items:center!important;gap:8px!important;color:#333!important;font-size:0.85rem!important;padding:7px 0!important;border-bottom:1px solid #f0f0f0!important;">
                    <span>📍</span><span><?php echo htmlspecialchars($alojamiento['municipality']); ?><?php if (!empty($alojamiento['province'])): ?>, <?php echo htmlspecialchars($alojamiento['province']); ?><?php endif; ?></span>
                </li>
                <?php endif; ?>
                <?php if (!empty($alojamiento['pet_friendly']) && $alojamiento['pet_friendly'] == 1): ?>
                <li style="display:flex!important;align-items:center!important;gap:8px!important;color:#333!important;font-size:0.85rem!important;padding:7px 0!important;border-bottom:1px solid #f0f0f0!important;">
                    <span>🐾</span><span>Admite mascotas</span>
                </li>
                <?php endif; ?>
                <?php if (!empty($alojamiento['suitable_for_children']) && $alojamiento['suitable_for_children'] == 1): ?>
                <li style="display:flex!important;align-items:center!important;gap:8px!important;color:#333!important;font-size:0.85rem!important;padding:7px 0!important;border-bottom:1px solid #f0f0f0!important;">
                    <span>👶</span><span>Apto para niños</span>
                </li>
                <?php endif; ?>
                <?php if (!empty($alojamiento['kitchen_available']) && $alojamiento['kitchen_available'] == 1): ?>
                <li style="display:flex!important;align-items:center!important;gap:8px!important;color:#333!important;font-size:0.85rem!important;padding:7px 0!important;border-bottom:1px solid #f0f0f0!important;">
                    <span>🍳</span><span>Cocina disponible</span>
                </li>
                <?php endif; ?>
            </ul>

            <?php
            // Amenities como chips
            $amenities = [];
            if (!empty($alojamiento['amenities'])) {
                $decoded = json_decode($alojamiento['amenities'], true);
                if (is_array($decoded)) $amenities = $decoded;
            }
            if (!empty($amenities)):
            ?>
            <div class="amenity-chips">
                <?php foreach (array_slice($amenities, 0, 6) as $am): ?>
                <span class="amenity-chip">✓ <?php echo htmlspecialchars($am); ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Botones compactos -->
            <div class="sidebar-btns">
                <?php if (!empty($alojamiento['phone'])): ?>
                <a href="tel:<?php echo htmlspecialchars($alojamiento['phone']); ?>"
                   class="btn-sidebar btn-sidebar-primary">
                    📞 Llamar · <?php echo htmlspecialchars($alojamiento['phone']); ?>
                </a>
                <a href="https://wa.me/34<?php echo preg_replace('/[^0-9]/', '', $alojamiento['phone']); ?>?text=Hola%2C+me+interesa+<?php echo urlencode($alojamiento['name']); ?>"
                   target="_blank" rel="noopener"
                   class="btn-sidebar btn-sidebar-wa">
                    💬 WhatsApp
                </a>
                <?php endif; ?>
                <?php if (!empty($alojamiento['email'])): ?>
                <a href="mailto:<?php echo htmlspecialchars($alojamiento['email']); ?>"
                   class="btn-sidebar btn-sidebar-email">
                    ✉️ Email
                </a>
                <?php endif; ?>
                <?php if (!empty($alojamiento['website'])): ?>
                <a href="<?php echo htmlspecialchars($alojamiento['website']); ?>"
                   target="_blank" rel="noopener"
                   class="btn-sidebar btn-sidebar-web">
                    🌐 Web oficial
                </a>
                <?php endif; ?>
                <?php if (!empty($alojamiento['airbnb_url'])): ?>
                <a href="<?php echo htmlspecialchars($alojamiento['airbnb_url']); ?>"
                   target="_blank" rel="noopener"
                   class="btn-sidebar btn-sidebar-web">
                    🏡 Ver en Airbnb
                </a>
                <?php endif; ?>
                <?php if (!empty($alojamiento['booking_url'])): ?>
                <a href="<?php echo htmlspecialchars($alojamiento['booking_url']); ?>"
                   target="_blank" rel="noopener"
                   class="btn-sidebar btn-sidebar-web">
                    🏨 Ver en Booking
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- CTA Registro -->
        <div class="cta-card">
            <div style="font-size:1.8rem;margin-bottom:8px;line-height:1;">🌿</div>
            <h3 style="font-size:1rem;font-weight:700;color:#fff!important;margin-bottom:8px;"><?php echo htmlspecialchars($t['cta_titulo']); ?></h3>
            <p style="font-size:0.8rem;color:rgba(255,255,255,0.85)!important;margin-bottom:14px;line-height:1.5;"><?php echo htmlspecialchars($t['cta_desc']); ?></p>
            <a href="/login.html?action=register&ref=alojamiento&slug=<?php echo urlencode($slug); ?>"
               style="display:flex;align-items:center;justify-content:center;background:#fff;color:#2F5233;padding:10px 16px;border-radius:8px;font-weight:700;font-size:0.85rem;text-decoration:none;margin-bottom:8px;width:100%;box-sizing:border-box;">
                <?php echo htmlspecialchars($t['cta_register']); ?>
            </a>
            <a href="/login.html?ref=alojamiento&slug=<?php echo urlencode($slug); ?>"
               style="display:flex;align-items:center;justify-content:center;background:transparent;color:#fff;border:2px solid rgba(255,255,255,0.6);padding:9px 16px;border-radius:8px;font-weight:600;font-size:0.82rem;text-decoration:none;width:100%;box-sizing:border-box;">
                <?php echo htmlspecialchars($t['cta_login']); ?>
            </a>
        </div>

        <!-- Compartir -->
        <div style="background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.08);padding:18px;text-align:center;">
            <p style="font-size:0.82rem;color:#666;margin-bottom:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">Compartir</p>
            <div style="display:flex;justify-content:center;gap:14px;">
                <button onclick="shareAlo('whatsapp')" style="background:none;border:none;cursor:pointer;font-size:1.6rem;line-height:1;" title="WhatsApp">💬</button>
                <button onclick="shareAlo('facebook')" style="background:none;border:none;cursor:pointer;font-size:1.6rem;line-height:1;" title="Facebook">📘</button>
                <button onclick="shareAlo('twitter')" style="background:none;border:none;cursor:pointer;font-size:1.6rem;line-height:1;" title="Twitter">🐦</button>
                <button onclick="shareAlo('copy')" style="background:none;border:none;cursor:pointer;font-size:1.6rem;line-height:1;" title="Copiar enlace">🔗</button>
            </div>
        </div>

    </aside>

</div><!-- /.alo-layout -->

<?php else: ?>
<!-- Alojamiento no encontrado -->
<div class="error-container">
    <div class="error-icon">😕</div>
    <h1><?php echo $t['no_encontrado_h1']; ?></h1>
    <p><?php echo $t['no_encontrado_p']; ?></p>
    <a href="/alojamientos-turisticos" class="btn btn-primary" style="display:inline-flex;width:auto;"><?php echo $t['volver_lista']; ?></a>
</div>
<?php endif; ?>

<!-- ── LIGHTBOX ── -->
<div class="lightbox-overlay" id="lightbox" onclick="closeLightboxOnOverlay(event)">
    <button class="lightbox-close" onclick="closeLightbox()">✕</button>
    <button class="lightbox-nav lightbox-prev" onclick="lightboxNav(-1)">‹</button>
    <img class="lightbox-img" id="lightbox-img" src="" alt="">
    <button class="lightbox-nav lightbox-next" onclick="lightboxNav(1)">›</button>
    <div class="lightbox-caption" id="lightbox-caption"></div>
</div>

<!-- ── TOAST ── -->
<div class="toast" id="toast"></div>

<!-- ── FOOTER ── -->
<footer class="site-footer">
    <div class="footer-social">
        <a href="https://www.instagram.com/rutas_rurales/" target="_blank" rel="noopener" aria-label="Instagram">📸</a>
        <a href="https://www.facebook.com/rutasrurales.io" target="_blank" rel="noopener" aria-label="Facebook">📘</a>
        <a href="https://twitter.com/rutasrurales" target="_blank" rel="noopener" aria-label="Twitter">🐦</a>
    </div>
    <div class="footer-links">
        <a href="/aviso-legal.html"><?php echo $t['aviso_legal']; ?></a>
        <a href="/politica-cookies.html"><?php echo $t['cookies']; ?></a>
        <a href="/agradecimientos.html"><?php echo $t['agradecimientos']; ?></a>
    </div>
    <p style="color:rgba(255,255,255,0.5);font-size:0.8rem;">© 2026 rutasrurales.io · Todos los derechos reservados</p>
</footer>

<!-- ── SCRIPTS DIFERIDOS ── -->

<!-- Leaflet CSS eliminado — ahora se usa enlace a rutas.php -->

<!-- GTM diferido (después de interacción) -->
<script>
(function() {
    var gtmLoaded = false;
    function loadGTM() {
        if (gtmLoaded) return;
        gtmLoaded = true;
        (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});
        var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';
        j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;
        f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-MBP57VQM');
    }
    ['click','scroll','keydown','touchstart'].forEach(function(e) {
        window.addEventListener(e, loadGTM, {once: true, passive: true});
    });
    setTimeout(loadGTM, 8000);
})();
</script>

<!-- ── JAVASCRIPT PRINCIPAL (inline, sin dependencias externas) ── -->
<script>
(function() {
    'use strict';

    var alo   = window.ALO_DATA;
    var T     = window.ALO_T || {};
    var fotos = (alo && alo.fotos) ? alo.fotos : [];

    // ── Galería ──────────────────────────────────────────────────────────────
    window.currentGalleryIdx = 0;

    window.setGalleryPhoto = function(idx) {
        if (!fotos[idx]) return;
        currentGalleryIdx = idx;
        var mainImg = document.getElementById('gallery-main-img');
        var counter = document.getElementById('gallery-counter');
        var thumbs  = document.querySelectorAll('.gallery-thumb');
        if (mainImg) {
            mainImg.style.opacity = '0.6';
            mainImg.src = fotos[idx];
            mainImg.onload = function() { mainImg.style.opacity = '1'; };
        }
        thumbs.forEach(function(t) {
            t.classList.toggle('active', parseInt(t.dataset.index) === idx);
        });
        if (counter) counter.textContent = (idx + 1) + ' / ' + fotos.length;
    };

    // ── Lightbox ─────────────────────────────────────────────────────────────
    window.openLightbox = function(idx) {
        if (!fotos.length) return;
        currentGalleryIdx = idx;
        var overlay = document.getElementById('lightbox');
        var img     = document.getElementById('lightbox-img');
        var caption = document.getElementById('lightbox-caption');
        if (!overlay || !img) return;
        overlay.classList.add('active');
        img.src = fotos[idx];
        img.alt = (alo ? alo.name : '') + ' — foto ' + (idx + 1);
        if (caption) caption.textContent = (idx + 1) + ' / ' + fotos.length;
        document.body.style.overflow = 'hidden';
        // Ocultar nav si solo hay 1 foto
        var prev = document.querySelector('.lightbox-prev');
        var next = document.querySelector('.lightbox-next');
        var show = fotos.length > 1 ? '' : 'none';
        if (prev) prev.style.display = show;
        if (next) next.style.display = show;
    };

    window.closeLightbox = function() {
        var overlay = document.getElementById('lightbox');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    };

    window.closeLightboxOnOverlay = function(e) {
        if (e.target === document.getElementById('lightbox')) closeLightbox();
    };

    window.lightboxNav = function(dir) {
        if (!fotos.length) return;
        currentGalleryIdx = (currentGalleryIdx + dir + fotos.length) % fotos.length;
        openLightbox(currentGalleryIdx);
    };

    document.addEventListener('keydown', function(e) {
        var overlay = document.getElementById('lightbox');
        if (!overlay || !overlay.classList.contains('active')) return;
        if (e.key === 'Escape')     closeLightbox();
        if (e.key === 'ArrowLeft')  lightboxNav(-1);
        if (e.key === 'ArrowRight') lightboxNav(1);
    });

    // ── Descripción expandible ────────────────────────────────────────────────
    window.expandDesc = function() {
        var text = document.getElementById('desc-text');
        var btn  = document.getElementById('desc-expand-btn');
        if (text) text.classList.remove('collapsed');
        if (btn)  btn.remove();
    };

    // ── Mapa eliminado — ahora se usa enlace a rutas.php ──────────────────────

    // ── Contenido cercano ─────────────────────────────────────────────────────
    var nearbyData   = null;
    var nearbyLoaded = false;

    var nearbyConfig = {
        alojamientos: { section: 'nearby-alojamientos-section', grid: 'nearby-alojamientos', more: 'more-alojamientos', key: 'alojamientos',      emoji: '🏠' },
        lugares:      { section: 'nearby-lugares-section',      grid: 'nearby-lugares',      more: 'more-lugares',      key: 'lugares',            emoji: '🏛️' },
        actividades:  { section: 'nearby-actividades-section',  grid: 'nearby-actividades',  more: 'more-actividades',  key: 'actividades',        emoji: '🎯' },
        eventos:      { section: 'nearby-eventos-section',      grid: 'nearby-eventos',      more: 'more-eventos',      key: 'eventos_similares',  emoji: '🎭' }
    };

    var nearbyAllItems = {};

    function loadNearby() {
        if (nearbyLoaded || !alo || !alo.latitude || !alo.longitude) return;
        nearbyLoaded = true;

        var url = '/alojamiento-modular/api/alojamiento-data.php'
            + '?slug=' + encodeURIComponent(alo.slug)
            + '&lat='  + alo.latitude
            + '&lng='  + alo.longitude
            + '&radius=50'
            + '&mode=nearby';

        fetch(url)
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                if (!resp.success || !resp.data) return;
                nearbyData = resp.data;
                window._nearbyData = nearbyData;

                // Renderizar secciones
                Object.keys(nearbyConfig).forEach(function(type) {
                    var cfg   = nearbyConfig[type];
                    var items = nearbyData[cfg.key] || [];
                    nearbyAllItems[type] = items;
                    renderNearbySection(type, items);
                });
            })
            .catch(function(err) {
                console.error('Error cargando contenido cercano:', err);
            });
    }

    function renderNearbySection(type, items) {
        var cfg     = nearbyConfig[type];
        var section = document.getElementById(cfg.section);
        var grid    = document.getElementById(cfg.grid);
        var moreBtn = document.getElementById(cfg.more);
        if (!section || !grid) return;

        if (!items || items.length === 0) {
            // No mostrar sección si no hay resultados
            return;
        }

        section.style.display = 'block';
        grid.innerHTML = '';

        var shown = items.slice(0, 4);
        shown.forEach(function(item) {
            grid.appendChild(createNearbyCard(item, type, cfg.emoji));
        });

        if (items.length > 4 && moreBtn) {
            moreBtn.style.display = 'block';
        }
    }

    window.showMoreNearby = function(type) {
        var cfg     = nearbyConfig[type];
        var grid    = document.getElementById(cfg.grid);
        var moreBtn = document.getElementById(cfg.more);
        var items   = nearbyAllItems[type] || [];
        if (!grid) return;
        items.slice(4).forEach(function(item) {
            grid.appendChild(createNearbyCard(item, type, cfg.emoji));
        });
        if (moreBtn) moreBtn.style.display = 'none';
    };

    function createNearbyCard(item, type, emoji) {
        var card = document.createElement('a');
        card.className = 'nearby-card';
        card.href = item.url || '#';

        // Imagen
        var imgWrap = document.createElement('div');
        imgWrap.className = 'nearby-card-img';

        if (item.main_image) {
            var img = document.createElement('img');
            img.src     = fixUrl(item.main_image);
            img.alt     = item.name || '';
            img.loading = 'lazy';
            img.onerror = function() {
                imgWrap.innerHTML = '<div class="nearby-card-img-placeholder">' + emoji + '</div>';
            };
            imgWrap.appendChild(img);
        } else {
            imgWrap.innerHTML = '<div class="nearby-card-img-placeholder">' + emoji + '</div>';
        }

        if (item.distance > 0) {
            var dist = document.createElement('span');
            dist.className   = 'nearby-card-dist';
            dist.textContent = item.distance + ' ' + (T.km || 'km');
            imgWrap.appendChild(dist);
        }

        // Cuerpo
        var body = document.createElement('div');
        body.className = 'nearby-card-body';

        var name = document.createElement('div');
        name.className   = 'nearby-card-name';
        name.textContent = item.name || '';
        body.appendChild(name);

        if (item.municipality) {
            var meta = document.createElement('div');
            meta.className   = 'nearby-card-meta';
            meta.textContent = '📍 ' + item.municipality;
            body.appendChild(meta);
        }

        // Precio / info extra
        if (type === 'alojamientos' && item.price_per_night > 0) {
            var p = document.createElement('div');
            p.className   = 'nearby-card-price';
            p.textContent = item.price_per_night + '€ / ' + (T.noche || 'noche');
            body.appendChild(p);
        } else if (type === 'actividades' && item.price > 0) {
            var p2 = document.createElement('div');
            p2.className   = 'nearby-card-price';
            p2.textContent = (T.desde || 'desde') + ' ' + item.price + '€';
            body.appendChild(p2);
        } else if (type === 'eventos') {
            if (item.is_free == 1) {
                var fr = document.createElement('span');
                fr.className   = 'nearby-card-free';
                fr.textContent = T.gratis || 'Gratis';
                body.appendChild(fr);
            } else if (item.ticket_price > 0) {
                var tp = document.createElement('div');
                tp.className   = 'nearby-card-price';
                tp.textContent = item.ticket_price + '€';
                body.appendChild(tp);
            }
            if (item.start_date) {
                var dt = document.createElement('div');
                dt.className   = 'nearby-card-meta';
                dt.textContent = '📅 ' + fmtDate(item.start_date);
                body.appendChild(dt);
            }
        }

        card.appendChild(imgWrap);
        card.appendChild(body);
        return card;
    }

    // Cargar nearby automáticamente tras 1.5s (sin esperar scroll)
    setTimeout(loadNearby, 1500);

    // ── Compartir ─────────────────────────────────────────────────────────────
    window.shareAlo = function(platform) {
        var url   = window.location.href;
        var title = alo ? alo.name : document.title;
        var links = {
            whatsapp: 'https://wa.me/?text=' + encodeURIComponent(title + ' ' + url),
            facebook: 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url),
            twitter:  'https://twitter.com/intent/tweet?text=' + encodeURIComponent(title) + '&url=' + encodeURIComponent(url),
        };
        if (platform === 'copy') {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function() { showToast('✅ Enlace copiado'); });
            } else {
                showToast('✅ ' + url);
            }
            return;
        }
        if (navigator.share && (platform === 'whatsapp' || platform === 'copy')) {
            navigator.share({ title: title, url: url }).catch(function(){});
            return;
        }
        window.open(links[platform], '_blank', 'width=600,height=400');
    };

    // Botón compartir del hero
    var btnShare = document.getElementById('btn-share');
    if (btnShare) {
        btnShare.addEventListener('click', function() {
            if (navigator.share) {
                navigator.share({ title: alo ? alo.name : document.title, url: window.location.href }).catch(function(){});
            } else {
                shareAlo('copy');
            }
        });
    }

    // ── Favorito ──────────────────────────────────────────────────────────────
    var btnFav = document.getElementById('btn-fav');
    if (btnFav && alo) {
        var favKey = 'fav_alo_' + alo.id;
        if (localStorage.getItem(favKey) === '1') btnFav.textContent = '❤️';
        btnFav.addEventListener('click', function() {
            if (localStorage.getItem(favKey) === '1') {
                localStorage.removeItem(favKey);
                btnFav.textContent = '🤍';
                showToast('Eliminado de favoritos');
            } else {
                localStorage.setItem(favKey, '1');
                btnFav.textContent = '❤️';
                showToast('❤️ Guardado en favoritos');
            }
        });
    }

    // ── Toast ─────────────────────────────────────────────────────────────────
    function showToast(msg) {
        var toast = document.getElementById('toast');
        if (!toast) return;
        toast.textContent = msg;
        toast.classList.add('show');
        setTimeout(function() { toast.classList.remove('show'); }, 3000);
    }

    // ── Utilidades ────────────────────────────────────────────────────────────
    function fixUrl(url) {
        if (!url) return '';
        if (/^https?:\/\//.test(url)) return url;
        return '/' + url.replace(/^\/+/, '');
    }

    function fmtDate(s) {
        try {
            return new Date(s).toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });
        } catch(e) { return s; }
    }

    function escHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

})();
</script>

</body>
</html>
