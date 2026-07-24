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

        // ─── CONTROL DE ACCESO POR MEMBRESÍA ───────────────────────────────
        // Si el alojamiento NO es premium y se accede desde un idioma que NO es español,
        // redirigir 301 a la versión en español (consolidar SEO, eliminar 404)
        if ($alojamiento && empty($alojamiento['is_premium']) && $lang !== 'es') {
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: https://rutasrurales.io/alojamiento/' . rawurlencode($alojamiento['slug']));
            exit();
        }

        if ($alojamiento) {
            for ($i = 1; $i <= 20; $i++) {
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

            // ─── SSR NEARBY: Alojamientos, Lugares, Actividades y Eventos cercanos visibles al crawler ───
            // Query ultraligera (LIMIT 4, usa la misma conexión PDO ya abierta)
            // El resultado se renderiza en HTML estático → Google lo indexa sin JS
            $ssr_nearby_alojamientos = [];
            $ssr_nearby_lugares = [];
            $ssr_nearby_actividades = [];
            $ssr_nearby_eventos = [];
            $ssr_prov = $alojamiento['province'] ?? '';
            $ssr_lat  = !empty($alojamiento['latitude'])  ? (float)$alojamiento['latitude']  : null;
            $ssr_lng  = !empty($alojamiento['longitude']) ? (float)$alojamiento['longitude'] : null;

            if ($ssr_lat && $ssr_lng) {
                // Alojamientos más cercanos (excluye el actual)
                $ss = $pdo->prepare("
                    SELECT name, slug, municipality, price_per_night, photo1,
                        (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS dist
                    FROM accommodations
                    WHERE is_active = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL AND slug != ?
                    HAVING dist < 60
                    ORDER BY dist ASC
                    LIMIT 4
                ");
                $ss->execute([$ssr_lat, $ssr_lng, $ssr_lat, $alojamiento['slug']]);
                $ssr_nearby_alojamientos = $ss->fetchAll(PDO::FETCH_ASSOC);

                // Lugares de interés más cercanos
                $ss2 = $pdo->prepare("
                    SELECT name, slug, municipality, photo1,
                        (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS dist
                    FROM places_of_interest
                    WHERE is_active = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL
                    HAVING dist < 60
                    ORDER BY dist ASC
                    LIMIT 4
                ");
                $ss2->execute([$ssr_lat, $ssr_lng, $ssr_lat]);
                $ssr_nearby_lugares = $ss2->fetchAll(PDO::FETCH_ASSOC);

                // Actividades turísticas más cercanas
                $ss3 = $pdo->prepare("
                    SELECT name, slug, municipality, photo1,
                        (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS dist
                    FROM tourist_activities
                    WHERE is_active = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL
                    HAVING dist < 60
                    ORDER BY dist ASC
                    LIMIT 4
                ");
                $ss3->execute([$ssr_lat, $ssr_lng, $ssr_lat]);
                $ssr_nearby_actividades = $ss3->fetchAll(PDO::FETCH_ASSOC);

                // Eventos culturales cercanos (solo futuros o en curso)
                $ss4 = $pdo->prepare("
                    SELECT name, slug, municipality, photo1, poster_image, start_date, is_free, ticket_price,
                        (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS dist
                    FROM cultural_events
                    WHERE is_active = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL
                        AND COALESCE(end_date, DATE_ADD(start_date, INTERVAL 1 DAY)) >= CURDATE()
                    HAVING dist < 60
                    ORDER BY dist ASC
                    LIMIT 4
                ");
                $ss4->execute([$ssr_lat, $ssr_lng, $ssr_lat]);
                $ssr_nearby_eventos = $ss4->fetchAll(PDO::FETCH_ASSOC);

            } elseif ($ssr_prov) {
                // Fallback por provincia si no hay coordenadas
                $ss = $pdo->prepare("SELECT name, slug, municipality, price_per_night, photo1, 0 AS dist FROM accommodations WHERE is_active = 1 AND province = ? AND slug != ? ORDER BY RAND() LIMIT 4");
                $ss->execute([$ssr_prov, $alojamiento['slug']]);
                $ssr_nearby_alojamientos = $ss->fetchAll(PDO::FETCH_ASSOC);

                $ss2 = $pdo->prepare("SELECT name, slug, municipality, photo1, 0 AS dist FROM places_of_interest WHERE is_active = 1 AND province = ? ORDER BY RAND() LIMIT 4");
                $ss2->execute([$ssr_prov]);
                $ssr_nearby_lugares = $ss2->fetchAll(PDO::FETCH_ASSOC);

                $ss3 = $pdo->prepare("SELECT name, slug, municipality, photo1, 0 AS dist FROM tourist_activities WHERE is_active = 1 AND province = ? ORDER BY RAND() LIMIT 4");
                $ss3->execute([$ssr_prov]);
                $ssr_nearby_actividades = $ss3->fetchAll(PDO::FETCH_ASSOC);

                $ss4 = $pdo->prepare("SELECT name, slug, municipality, photo1, poster_image, start_date, is_free, ticket_price, 0 AS dist FROM cultural_events WHERE is_active = 1 AND province = ? AND COALESCE(end_date, DATE_ADD(start_date, INTERVAL 1 DAY)) >= CURDATE() ORDER BY start_date ASC LIMIT 4");
                $ss4->execute([$ssr_prov]);
                $ssr_nearby_eventos = $ss4->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    } catch (Exception $e) {
        error_log('alojamiento-modular/index.php error: ' . $e->getMessage());
    }
}

// ─── FALLBACK: Si no se encuentra el alojamiento, redirigir a landing de provincia ──
// Si el slug tiene formato de landing (filtro-provincia), redirigir a /alojamientos/{slug}
// Ej: /alojamiento/accesibles-leon → /alojamientos/accesibles-leon
// Si no se puede parsear como landing, redirigir al listado general
if (empty($alojamiento) && !empty($slug)) {
    // Cargar parseLandingSlug desde config/filters.php
    $filtersConfigPath = __DIR__ . '/../alojamientos-landing/config/filters.php';
    if (file_exists($filtersConfigPath)) {
        require_once $filtersConfigPath;
        $parsed = parseLandingSlug($slug);
        if ($parsed['valid']) {
            // Redirigir 301 a la landing page correspondiente
            $prefix = ($lang !== 'es') ? "/$lang" : '';
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: https://rutasrurales.io' . $prefix . '/alojamientos/' . $slug);
            exit;
        }
    }
    // Si no se pudo parsear como landing, redirigir al listado general
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: https://rutasrurales.io/alojamientos-turisticos');
    exit;
}

// ─── SEO — Títulos y meta descriptions con keywords long-tail ────────────────
if ($alojamiento) {
    // Tipo de alojamiento para incluir en el título
    $tipo_seo = $alojamiento['category_name'] ?? $alojamiento['accommodation_type'] ?? 'Alojamiento rural';

    // Construir título optimizado (si no hay meta_title manual en BD)
    if (!empty($alojamiento['meta_title'])) {
        $page_title = $alojamiento['meta_title'];
    } else {
        $title_parts = [$tipo_seo, $alojamiento['name']];
        $loc_parts   = array_filter([$alojamiento['municipality'] ?? '', $alojamiento['province'] ?? '']);
        $loc_str     = implode(', ', $loc_parts);
        if ($loc_str) $title_parts[] = 'en ' . $loc_str;
        if (!empty($alojamiento['price_per_night']) && $alojamiento['price_per_night'] > 0) {
            $title_parts[] = 'desde ' . number_format($alojamiento['price_per_night'], 0, ',', '.') . '€/noche';
        }
        $title_parts[] = 'Rutas Rurales';
        $page_title = implode(' · ', $title_parts);
        // Limitar a ~60 chars para evitar truncamiento en SERPs
        if (mb_strlen($page_title) > 65) {
            $page_title = $tipo_seo . ' ' . $alojamiento['name'];
            if ($loc_str) $page_title .= ' en ' . $loc_str;
            $page_title .= ' | Rutas Rurales';
        }
    }

    // Construir meta description optimizada (si no hay meta_description manual en BD)
    if (!empty($alojamiento['meta_description'])) {
        $page_desc = $alojamiento['meta_description'];
    } else {
        // Recopilar características destacadas para la descripción
        $desc_features = [];
        if (!empty($alojamiento['capacity']) && $alojamiento['capacity'] > 0) {
            $desc_features[] = 'capacidad para ' . $alojamiento['capacity'] . ' personas';
        }
        if (!empty($alojamiento['wifi']) && $alojamiento['wifi'] == 1)           $desc_features[] = 'WiFi';
        if (!empty($alojamiento['pet_friendly']) && $alojamiento['pet_friendly'] == 1) $desc_features[] = 'admite mascotas';
        if (!empty($alojamiento['swimming_pool']) && $alojamiento['swimming_pool'] == 1) $desc_features[] = 'piscina';
        if (!empty($alojamiento['parking']) && $alojamiento['parking'] == 1)    $desc_features[] = 'parking gratuito';
        if (!empty($alojamiento['kitchen_available']) && $alojamiento['kitchen_available'] == 1) $desc_features[] = 'cocina equipada';

        $loc_parts = array_filter([$alojamiento['municipality'] ?? '', $alojamiento['province'] ?? '']);
        $loc_str   = implode(', ', $loc_parts);

        $precio_str = (!empty($alojamiento['price_per_night']) && $alojamiento['price_per_night'] > 0)
            ? 'Desde ' . number_format($alojamiento['price_per_night'], 0, ',', '.') . '€/noche. '
            : '';

        // Usar descripción de BD si existe (primeros 100 chars)
        $desc_base = substr(strip_tags($alojamiento['description'] ?? ''), 0, 100);
        if ($desc_base) $desc_base = rtrim($desc_base, '.,;:') . '. ';

        $features_str = !empty($desc_features) ? implode(', ', array_slice($desc_features, 0, 3)) . '. ' : '';

        $page_desc = trim(
            $tipo_seo . ($loc_str ? ' en ' . $loc_str : '') . '. '
            . ($desc_base ?: '')
            . $features_str
            . $precio_str
            . 'Reserva fácil en Rutas Rurales.'
        );
        // Limitar a 160 chars
        if (mb_strlen($page_desc) > 160) {
            $page_desc = mb_substr($page_desc, 0, 157) . '...';
        }
    }
} else {
    $page_title = 'Alojamiento | Rutas Rurales';
    $page_desc  = 'Descubre este alojamiento en Rutas Rurales';
}
$page_description = $page_desc;
$canonical = 'https://rutasrurales.io/' . ($lang !== 'es' ? $lang . '/' : '') . 'alojamiento/' . $slug;
$foto_og   = !empty($fotos[0]) ? $fotos[0] : 'https://rutasrurales.io/menu_images/turismo_rural.webp';

// ─── TEXTO ENRIQUECIDO PARA EL BOTÓN DE COMPARTIR EN X/TWITTER ───────────────
$twitter_text = '';
if ($alojamiento) {
    $twitter_text = $alojamiento['name'];
    $loc_tw = array_filter([$alojamiento['municipality'] ?? '', $alojamiento['province'] ?? '']);
    if (!empty($loc_tw)) $twitter_text .= ' en ' . implode(', ', $loc_tw);
    if (!empty($alojamiento['price_per_night']) && $alojamiento['price_per_night'] > 0) {
        $twitter_text .= '. Desde ' . number_format($alojamiento['price_per_night'], 0, ',', '.') . '€/noche';
    }
    $twitter_text .= '. ¡Reserva tu escapada rural! 🌿';
} else {
    $twitter_text = '¡Descubre este alojamiento rural en Rutas Rurales! 🌿';
}

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

// ─── EFECTO CONTRASTE: Precio estimado en OTA (Booking / Airbnb) ─────────────
// Se calcula como el precio base + 15% de media de comisiones OTA
// El redondeo al múltiplo de 5 más cercano simula los precios reales de OTA
$precio_ota_num        = 0;
$precio_ota_display    = '';
$ahorro_pct_ota        = 15; // porcentaje medio de ahorro vs OTA
$pasaporte_desc_pct    = 5; // descuento Pasaporte Rural mínimo (5% garantizado; usuarios con más puntos pueden obtener hasta el 10%)
$precio_pasaporte_num  = 0;
$precio_pasaporte_display = '';

if ($alojamiento && !empty($alojamiento['price_per_night']) && $alojamiento['price_per_night'] > 0) {
    $base = (float)$alojamiento['price_per_night'];
    // Precio OTA: base + 15%, redondeado al múltiplo de 5 superior
    $precio_ota_num     = ceil(($base * 1.15) / 5) * 5;
    $precio_ota_display = number_format($precio_ota_num, 0, ',', '.') . ' €';
    // Precio con Pasaporte Rural: base - 10%
    $precio_pasaporte_num     = round($base * (1 - $pasaporte_desc_pct / 100));
    $precio_pasaporte_display = number_format($precio_pasaporte_num, 0, ',', '.') . ' €';
}

$tipo_display      = $alojamiento['category_name'] ?? $alojamiento['accommodation_type'] ?? 'Alojamiento';
$capacidad_display = ($alojamiento['capacity'] ?? 0) > 0 ? $alojamiento['capacity'] . ' ' . $t['personas'] : '';

// Ubicación
$ubicacion_display = '';
if ($alojamiento) {
    $partes = array_filter([$alojamiento['municipality'] ?? '', $alojamiento['province'] ?? '']);
    $ubicacion_display = implode(', ', $partes);
}

// ─── JSON-LD: generado por modules/schema.php → renderAlojamientoSchema() ──────
// (El marcado se inyecta directamente en el <head> via la función del módulo)

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

    <!-- hreflang — SOLO para alojamientos Premium (con derecho a traducciones) -->
    <?php if ($alojamiento && !empty($alojamiento['is_premium'])): ?>
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
        .alo-hero-btn--share {
            border-radius: 24px;
            width: auto;
            height: auto;
            padding: 8px 18px;
            gap: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            font-family: inherit;
            line-height: 1.4;
        }
        .alo-hero-btn--share:hover,
        .alo-hero-btn--share:focus-visible { background: rgba(255,255,255,0.28); outline: none; }
        .alo-hero-btn--share:active { transform: scale(0.96); }
        .alo-hero-btn--share.alo-hero-btn--copied { background: rgba(129,199,132,0.25); border-color: var(--accent); }
        @media (max-width: 480px) {
            .alo-hero-btn--share { padding: 10px 18px; font-size: 0.88rem; }
        }

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

/* ══ EFECTO CONTRASTE + PASAPORTE RURAL + GARANTÍA ══════════════════════════ */

/* Tarifa Web Directa badge */
.price-tarifa-badge {
    display: inline-block;
    background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
    color: #2F5233;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.6px;
    text-transform: uppercase;
    padding: 4px 12px;
    border-radius: 20px;
    border: 1px solid #a5d6a7;
    margin-bottom: 10px;
}

/* Comparativa OTA */
.price-ota-compare {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    background: #fff8e1;
    border: 1px solid #ffe082;
    border-radius: 8px;
    padding: 9px 12px;
    margin: 8px 0 6px;
    font-size: 0.82rem;
    line-height: 1.5;
}
.price-ota-icon { font-size: 1rem; flex-shrink: 0; margin-top: 1px; }
.price-ota-text { color: #5d4037; }
.price-ota-tachado { color: #b71c1c; font-weight: 600; text-decoration: line-through; }
.price-ota-ahorro { display: block; color: #e65100; font-size: 0.78rem; margin-top: 2px; }

/* Badge ahorro */
.price-ahorro-badge {
    background: linear-gradient(135deg, #ff6f00, #e65100);
    color: #fff;
    font-size: 0.76rem;
    font-weight: 700;
    padding: 6px 12px;
    border-radius: 8px;
    margin-bottom: 14px;
    text-align: center;
    line-height: 1.4;
}

/* Toggle Pasaporte Rural */
.pasaporte-toggle-wrap {
    background: linear-gradient(135deg, #e8f5e9, #f1f8e9);
    border: 1px solid #a5d6a7;
    border-radius: 10px;
    padding: 12px 14px;
    margin-bottom: 14px;
}
.pasaporte-toggle-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    user-select: none;
}
.pasaporte-icon { font-size: 1.3rem; flex-shrink: 0; }
.pasaporte-texto { font-size: 0.85rem; font-weight: 700; color: #2F5233; flex: 1; }

/* Switch CSS */
.pasaporte-switch { position: relative; display: inline-block; width: 44px; height: 24px; flex-shrink: 0; }
.pasaporte-input { opacity: 0; width: 0; height: 0; position: absolute; }
.pasaporte-slider {
    position: absolute; inset: 0;
    background: #ccc;
    border-radius: 24px;
    transition: background 0.25s;
    cursor: pointer;
}
.pasaporte-slider::before {
    content: '';
    position: absolute;
    width: 18px; height: 18px;
    left: 3px; bottom: 3px;
    background: #fff;
    border-radius: 50%;
    transition: transform 0.25s;
    box-shadow: 0 1px 4px rgba(0,0,0,0.25);
}
.pasaporte-input:checked + .pasaporte-slider { background: #2F5233; }
.pasaporte-input:checked + .pasaporte-slider::before { transform: translateX(20px); }

/* Resultado del descuento */
.pasaporte-resultado {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px dashed #a5d6a7;
    text-align: center;
    animation: fadeInDown 0.25s ease;
}
@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
}
.pasaporte-precio-original {
    font-size: 1rem;
    color: #999;
    text-decoration: line-through;
    margin-bottom: 4px;
}
.pasaporte-precio-nuevo {
    font-size: 1.9rem;
    font-weight: 800;
    color: #2F5233;
    line-height: 1;
    margin-bottom: 6px;
}
.pasaporte-ahorro-text {
    font-size: 0.78rem;
    color: #388e3c;
    font-weight: 600;
}

/* Banner Garantía de Trato Directo */
.garantia-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    padding: 18px 20px;
    margin-bottom: 16px;
    border-left: 4px solid #2F5233;
}
.garantia-titulo {
    font-size: 0.82rem;
    font-weight: 700;
    color: #2F5233;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-bottom: 12px;
}
.garantia-items { display: flex; flex-direction: column; gap: 10px; }
.garantia-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
}
.garantia-item-icon { font-size: 1.3rem; flex-shrink: 0; margin-top: 1px; }
.garantia-item strong {
    display: block;
    font-size: 0.84rem;
    font-weight: 700;
    color: #1a1a1a;
    line-height: 1.3;
}
.garantia-item small {
    display: block;
    font-size: 0.75rem;
    color: #666;
    line-height: 1.4;
    margin-top: 1px;
}
</style>

<?php if ($alojamiento): ?>

<!-- ── HERO ── -->
<section class="alo-hero">
    <div class="alo-hero-bg" style="background-image:url('<?php echo htmlspecialchars($fotos[0]); ?>')"></div>

    <div class="alo-hero-actions">
        <button class="alo-hero-btn alo-hero-btn--share" id="btn-share" title="<?php echo $t['compartir']; ?>" aria-label="<?php echo $t['compartir']; ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/>
                <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
            </svg>
            <span><?php echo $t['compartir']; ?></span>
        </button>
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
                📍 ¡Mira la ubicación y qué ver cerca!
            </a>
        </div>
        <?php endif; ?>

        <!-- ══ SSR NEARBY: Visible al crawler de Google (HTML estático) ══════════
             Los 4 alojamientos y lugares más cercanos se renderizan en PHP,
             sin JS, para que Google los indexe y siga los enlaces internos.
             Las secciones JS de abajo siguen cargando los 8 resultados completos.
        ════════════════════════════════════════════════════════════════════════ -->
        <?php if (!empty($ssr_nearby_alojamientos)): ?>
        <div class="alo-card" id="ssr-nearby-alojamientos">
            <div class="alo-card-body">
                <h2 class="alo-card-title" style="font-size:1.1rem;font-weight:700;color:#2F5233;margin-bottom:18px;padding-bottom:12px;border-bottom:2px solid #81C784;display:flex;align-items:center;gap:8px;">🏠 Alojamientos rurales cercanos</h2>
                <div class="nearby-grid">
                    <?php foreach ($ssr_nearby_alojamientos as $nr_alo): ?>
                    <?php
                        $nr_url   = '/alojamiento/' . $nr_alo['slug'];
                        $nr_name  = htmlspecialchars($nr_alo['name']);
                        $nr_munic = htmlspecialchars($nr_alo['municipality'] ?? '');
                        $nr_price = !empty($nr_alo['price_per_night']) && $nr_alo['price_per_night'] > 0
                            ? number_format($nr_alo['price_per_night'], 0, ',', '.') . '€/noche'
                            : '';
                        $nr_img   = !empty($nr_alo['photo1']) ? htmlspecialchars($nr_alo['photo1']) : '';
                        if ($nr_img && !preg_match('/^https?:\/\//', $nr_img)) $nr_img = '/' . ltrim($nr_img, '/');
                        $nr_dist  = isset($nr_alo['dist']) && $nr_alo['dist'] > 0 ? round($nr_alo['dist'], 1) . ' km' : '';
                    ?>
                    <a href="<?php echo $nr_url; ?>" class="nearby-card" title="<?php echo $nr_name; ?> en <?php echo $nr_munic; ?>">
                        <div class="nearby-card-img">
                            <?php if ($nr_img): ?>
                            <img src="<?php echo $nr_img; ?>" alt="<?php echo $nr_name; ?>" loading="lazy" width="200" height="120">
                            <?php else: ?>
                            <div class="nearby-card-img-placeholder">🏠</div>
                            <?php endif; ?>
                            <?php if ($nr_dist): ?><span class="nearby-card-dist"><?php echo $nr_dist; ?></span><?php endif; ?>
                        </div>
                        <div class="nearby-card-body">
                            <div class="nearby-card-name"><?php echo $nr_name; ?></div>
                            <?php if ($nr_munic): ?><div class="nearby-card-meta">📍 <?php echo $nr_munic; ?></div><?php endif; ?>
                            <?php if ($nr_price): ?><div class="nearby-card-price"><?php echo $nr_price; ?></div><?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php if (!empty($alojamiento['province'])): ?>
                <div style="text-align:center;margin-top:16px;">
                    <a href="/alojamientos/<?php echo strtolower(htmlspecialchars($alojamiento['province'])); ?>"
                       style="display:inline-flex;align-items:center;gap:6px;background:none;border:1px solid #2F5233;color:#2F5233;padding:8px 20px;border-radius:20px;font-size:0.85rem;font-weight:600;text-decoration:none;">
                        Ver todos los alojamientos en <?php echo htmlspecialchars($alojamiento['province']); ?> →
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($ssr_nearby_lugares)): ?>
        <div class="alo-card" id="ssr-nearby-lugares">
            <div class="alo-card-body">
                <h2 class="alo-card-title" style="font-size:1.1rem;font-weight:700;color:#2F5233;margin-bottom:18px;padding-bottom:12px;border-bottom:2px solid #81C784;display:flex;align-items:center;gap:8px;">🏛️ Lugares de interés cercanos</h2>
                <div class="nearby-grid">
                    <?php foreach ($ssr_nearby_lugares as $nr_lug): ?>
                    <?php
                        $nl_url   = '/lugar/' . $nr_lug['slug'];
                        $nl_name  = htmlspecialchars($nr_lug['name']);
                        $nl_munic = htmlspecialchars($nr_lug['municipality'] ?? '');
                        $nl_img   = !empty($nr_lug['photo1']) ? htmlspecialchars($nr_lug['photo1']) : '';
                        if ($nl_img && !preg_match('/^https?:\/\//', $nl_img)) $nl_img = '/' . ltrim($nl_img, '/');
                        $nl_dist  = isset($nr_lug['dist']) && $nr_lug['dist'] > 0 ? round($nr_lug['dist'], 1) . ' km' : '';
                    ?>
                    <a href="<?php echo $nl_url; ?>" class="nearby-card" title="<?php echo $nl_name; ?>">
                        <div class="nearby-card-img">
                            <?php if ($nl_img): ?>
                            <img src="<?php echo $nl_img; ?>" alt="<?php echo $nl_name; ?>" loading="lazy" width="200" height="120">
                            <?php else: ?>
                            <div class="nearby-card-img-placeholder">🏛️</div>
                            <?php endif; ?>
                            <?php if ($nl_dist): ?><span class="nearby-card-dist"><?php echo $nl_dist; ?></span><?php endif; ?>
                        </div>
                        <div class="nearby-card-body">
                            <div class="nearby-card-name"><?php echo $nl_name; ?></div>
                            <?php if ($nl_munic): ?><div class="nearby-card-meta">📍 <?php echo $nl_munic; ?></div><?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ══ SSR: Actividades turísticas cercanas (visible al crawler de Google) ══ -->
        <?php if (!empty($ssr_nearby_actividades)): ?>
        <div class="alo-card" id="ssr-nearby-actividades">
            <div class="alo-card-body">
                <h2 class="alo-card-title" style="font-size:1.1rem;font-weight:700;color:#2F5233;margin-bottom:18px;padding-bottom:12px;border-bottom:2px solid #81C784;display:flex;align-items:center;gap:8px;">🎯 Actividades turísticas cercanas</h2>
                <div class="nearby-grid">
                    <?php foreach ($ssr_nearby_actividades as $nr_act): ?>
                    <?php
                        $na_url   = '/actividad/' . $nr_act['slug'];
                        $na_name  = htmlspecialchars($nr_act['name']);
                        $na_munic = htmlspecialchars($nr_act['municipality'] ?? '');
                        $na_img   = !empty($nr_act['photo1']) ? htmlspecialchars($nr_act['photo1']) : '';
                        if ($na_img && !preg_match('/^https?:\/\//', $na_img)) $na_img = '/' . ltrim($na_img, '/');
                        $na_dist  = isset($nr_act['dist']) && $nr_act['dist'] > 0 ? round($nr_act['dist'], 1) . ' km' : '';
                    ?>
                    <a href="<?php echo $na_url; ?>" class="nearby-card" title="<?php echo $na_name; ?>">
                        <div class="nearby-card-img">
                            <?php if ($na_img): ?>
                            <img src="<?php echo $na_img; ?>" alt="<?php echo $na_name; ?>" loading="lazy" width="200" height="120">
                            <?php else: ?>
                            <div class="nearby-card-img-placeholder">🎯</div>
                            <?php endif; ?>
                            <?php if ($na_dist): ?><span class="nearby-card-dist"><?php echo $na_dist; ?></span><?php endif; ?>
                        </div>
                        <div class="nearby-card-body">
                            <div class="nearby-card-name"><?php echo $na_name; ?></div>
                            <?php if ($na_munic): ?><div class="nearby-card-meta">📍 <?php echo $na_munic; ?></div><?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ══ SSR: Eventos culturales cercanos (visible al crawler de Google) ══ -->
        <?php if (!empty($ssr_nearby_eventos)): ?>
        <div class="alo-card" id="ssr-nearby-eventos">
            <div class="alo-card-body">
                <h2 class="alo-card-title" style="font-size:1.1rem;font-weight:700;color:#2F5233;margin-bottom:18px;padding-bottom:12px;border-bottom:2px solid #81C784;display:flex;align-items:center;gap:8px;">🎭 Eventos culturales cercanos</h2>
                <div class="nearby-grid">
                    <?php foreach ($ssr_nearby_eventos as $nr_ev): ?>
                    <?php
                        $ne_url   = '/evento/' . $nr_ev['slug'];
                        $ne_name  = htmlspecialchars($nr_ev['name']);
                        $ne_munic = htmlspecialchars($nr_ev['municipality'] ?? '');
                        // Imagen: preferir poster_image, fallback a photo1
                        $ne_img   = !empty($nr_ev['poster_image']) ? $nr_ev['poster_image'] : ($nr_ev['photo1'] ?? '');
                        if ($ne_img && !preg_match('/^https?:\/\//', $ne_img)) $ne_img = '/' . ltrim($ne_img, '/');
                        $ne_img   = htmlspecialchars($ne_img);
                        $ne_dist  = isset($nr_ev['dist']) && $nr_ev['dist'] > 0 ? round($nr_ev['dist'], 1) . ' km' : '';
                        // Fecha formateada
                        $ne_fecha = '';
                        if (!empty($nr_ev['start_date'])) {
                            try {
                                $dt = new DateTime($nr_ev['start_date']);
                                $ne_fecha = $dt->format('d/m/Y');
                            } catch (Exception $e) {}
                        }
                        // Precio
                        $ne_precio = '';
                        if (!empty($nr_ev['is_free']) && $nr_ev['is_free'] == 1) {
                            $ne_precio = 'gratis';
                        } elseif (!empty($nr_ev['ticket_price']) && $nr_ev['ticket_price'] > 0) {
                            $ne_precio = number_format($nr_ev['ticket_price'], 0, ',', '.') . '€';
                        }
                    ?>
                    <a href="<?php echo $ne_url; ?>" class="nearby-card" title="<?php echo $ne_name; ?>">
                        <div class="nearby-card-img">
                            <?php if ($ne_img): ?>
                            <img src="<?php echo $ne_img; ?>" alt="<?php echo $ne_name; ?>" loading="lazy" width="200" height="120">
                            <?php else: ?>
                            <div class="nearby-card-img-placeholder">🎭</div>
                            <?php endif; ?>
                            <?php if ($ne_dist): ?><span class="nearby-card-dist"><?php echo $ne_dist; ?></span><?php endif; ?>
                        </div>
                        <div class="nearby-card-body">
                            <div class="nearby-card-name"><?php echo $ne_name; ?></div>
                            <?php if ($ne_munic): ?><div class="nearby-card-meta">📍 <?php echo $ne_munic; ?></div><?php endif; ?>
                            <?php if ($ne_fecha): ?><div class="nearby-card-meta">📅 <?php echo $ne_fecha; ?></div><?php endif; ?>
                            <?php if ($ne_precio === 'gratis'): ?>
                            <span class="nearby-card-free">Gratis</span>
                            <?php elseif ($ne_precio): ?>
                            <div class="nearby-card-price"><?php echo htmlspecialchars($ne_precio); ?></div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php if (!empty($alojamiento['province'])): ?>
                <div style="text-align:center;margin-top:16px;">
                    <a href="/eventos-culturales?provincia=<?php echo urlencode($alojamiento['province']); ?>"
                       style="display:inline-flex;align-items:center;gap:6px;background:none;border:1px solid #2F5233;color:#2F5233;padding:8px 20px;border-radius:20px;font-size:0.85rem;font-weight:600;text-decoration:none;">
                        Ver todos los eventos en <?php echo htmlspecialchars($alojamiento['province']); ?> →
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ══ INTERNAL LINKING: Categorías y páginas relacionadas (SSR) ════════ -->
        <?php
        $prov_slug   = !empty($alojamiento['province']) ? strtolower(str_replace(' ', '-', $alojamiento['province'])) : '';
        $tipo_slug   = '';
        $cat_lower   = strtolower($alojamiento['category_name'] ?? $alojamiento['accommodation_type'] ?? '');
        if (str_contains($cat_lower, 'rural') || str_contains($cat_lower, 'casa'))   $tipo_slug = 'casas-rurales';
        elseif (str_contains($cat_lower, 'apartamento'))                              $tipo_slug = 'apartamentos';
        elseif (str_contains($cat_lower, 'hotel'))                                   $tipo_slug = 'hoteles-rurales';
        ?>
        <?php if ($prov_slug): ?>
        <div class="alo-card" id="internal-links-section">
            <div class="alo-card-body">
                <h2 class="alo-card-title" style="font-size:1.1rem;font-weight:700;color:#2F5233;margin-bottom:18px;padding-bottom:12px;border-bottom:2px solid #81C784;display:flex;align-items:center;gap:8px;">🌿 Más alojamientos en <?php echo htmlspecialchars($alojamiento['province']); ?></h2>
                <div style="display:flex;flex-wrap:wrap;gap:10px;">
                    <a href="/alojamientos/<?php echo $prov_slug; ?>"
                       style="display:inline-flex;align-items:center;gap:6px;background:#e8f5e9;border:1px solid #c8e6c9;color:#2F5233;padding:8px 16px;border-radius:20px;font-size:0.85rem;font-weight:600;text-decoration:none;">
                        🏠 Alojamientos en <?php echo htmlspecialchars($alojamiento['province']); ?>
                    </a>
                    <?php if ($tipo_slug && $prov_slug): ?>
                    <a href="/alojamientos/<?php echo $tipo_slug . '-' . $prov_slug; ?>"
                       style="display:inline-flex;align-items:center;gap:6px;background:#e8f5e9;border:1px solid #c8e6c9;color:#2F5233;padding:8px 16px;border-radius:20px;font-size:0.85rem;font-weight:600;text-decoration:none;">
                        🏡 <?php echo htmlspecialchars(ucfirst(str_replace('-', ' ', $tipo_slug))); ?> en <?php echo htmlspecialchars($alojamiento['province']); ?>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($alojamiento['pet_friendly']) && $alojamiento['pet_friendly'] == 1): ?>
                    <a href="/alojamientos/mascotas-<?php echo $prov_slug; ?>"
                       style="display:inline-flex;align-items:center;gap:6px;background:#e8f5e9;border:1px solid #c8e6c9;color:#2F5233;padding:8px 16px;border-radius:20px;font-size:0.85rem;font-weight:600;text-decoration:none;">
                        🐾 Alojamientos para mascotas en <?php echo htmlspecialchars($alojamiento['province']); ?>
                    </a>
                    <?php endif; ?>
                    <a href="/alojamientos-turisticos"
                       style="display:inline-flex;align-items:center;gap:6px;background:#fff;border:1px solid #ccc;color:#666;padding:8px 16px;border-radius:20px;font-size:0.85rem;font-weight:600;text-decoration:none;">
                        🔍 Ver todos los alojamientos
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- CONTENIDO CERCANO (dinámico JS — carga 8 resultados después) -->
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

            <!-- ── ETIQUETA "TARIFA WEB DIRECTA" ── -->
            <div class="price-tarifa-badge">🏷️ Tarifa Web Directa</div>

            <div class="price-amount" id="precio-principal"><?php echo htmlspecialchars($precio_display); ?></div>
            <div class="price-label"><?php echo $t['precio_noche']; ?></div>

            <!-- ── EFECTO CONTRASTE: Comparativa con OTA ── -->
            <?php if ($precio_ota_num > 0): ?>
            <div class="price-ota-compare">
                <span class="price-ota-icon">💡</span>
                <span class="price-ota-text">
                    En Booking/Airbnb: <s class="price-ota-tachado"><?php echo $precio_ota_display; ?></s>
                    <strong class="price-ota-ahorro">≈ +<?php echo $ahorro_pct_ota; ?>% con sus comisiones</strong>
                </span>
            </div>
            <div class="price-ahorro-badge">
                🔥 Aquí un <?php echo $ahorro_pct_ota; ?>% más barato · Trato directo sin intermediarios
            </div>
            <?php endif; ?>

            <!-- ── CALCULADORA PASAPORTE RURAL ── -->
            <?php if ($precio_pasaporte_num > 0): ?>
            <div class="pasaporte-toggle-wrap">
                <label class="pasaporte-toggle-label" for="toggle-pasaporte">
                    <span class="pasaporte-icon">💳</span>
                    <span class="pasaporte-texto">¿Tienes <a href="https://rutasrurales.io/pasaporte_rural/como-funciona.php" target="_blank" rel="noopener" style="color:#2F5233;font-weight:700;text-decoration:underline;text-decoration-color:rgba(47,82,51,0.4);">Pasaporte Rural</a>?</span>
                    <span class="pasaporte-switch">
                        <input type="checkbox" id="toggle-pasaporte" class="pasaporte-input"
                               data-precio-base="<?php echo (float)$alojamiento['price_per_night']; ?>"
                               data-precio-pasaporte="<?php echo $precio_pasaporte_num; ?>"
                               data-precio-pasaporte-display="<?php echo htmlspecialchars($precio_pasaporte_display); ?>"
                               data-precio-original-display="<?php echo htmlspecialchars($precio_display); ?>"
                               data-descuento-pct="<?php echo $pasaporte_desc_pct; ?>">
                        <span class="pasaporte-slider"></span>
                    </span>
                </label>
                <div class="pasaporte-resultado" id="pasaporte-resultado" style="display:none;">
                    <div class="pasaporte-precio-original" id="pasaporte-precio-original"></div>
                    <div class="pasaporte-precio-nuevo" id="pasaporte-precio-nuevo"></div>
                    <div class="pasaporte-ahorro-text">✅ Descuento del <?php echo $pasaporte_desc_pct; ?>% del Pasaporte Rural aplicado</div>
                </div>
            </div>
            <?php endif; ?>

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

        <!-- ── BANNER GARANTÍA DE TRATO DIRECTO ── -->
        <div class="garantia-card">
            <div class="garantia-titulo">✅ Por qué reservar aquí</div>
            <div class="garantia-items">
                <div class="garantia-item">
                    <span class="garantia-item-icon">🤝</span>
                    <div>
                        <strong>Sin comisiones</strong>
                        <small>El 100% del precio va al propietario</small>
                    </div>
                </div>
                <div class="garantia-item">
                    <span class="garantia-item-icon">📞</span>
                    <div>
                        <strong>Trato directo</strong>
                        <small>Habla con quien te aloja, no con una app</small>
                    </div>
                </div>
                <div class="garantia-item">
                    <span class="garantia-item-icon">💰</span>
                    <div>
                        <strong>Mejor precio garantizado</strong>
                        <small>≈ <?php echo $ahorro_pct_ota; ?>% más barato que en OTA</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Registro -->
        <div class="cta-card">
            <div style="font-size:1.8rem;margin-bottom:8px;line-height:1;">🌿</div>
            <h3 style="font-size:1rem;font-weight:700;color:#fff!important;margin-bottom:8px;"><?php echo htmlspecialchars($t['cta_titulo']); ?></h3>
            <p style="font-size:0.8rem;color:rgba(255,255,255,0.85)!important;margin-bottom:14px;line-height:1.5;"><?php echo htmlspecialchars($t['cta_desc']); ?></p>
            <!-- ✅ REFACTORIZADO: <a href> nativos — Google los sigue, accesibles con teclado -->
            <a href="/login.html?action=register&ref=alojamiento&slug=<?php echo urlencode($slug); ?>"
               class="btn-white">
                ✨ <?php echo $t['cta_register'] ?? 'Registrarme gratis'; ?>
            </a>
            <a href="/login.html?ref=alojamiento&slug=<?php echo urlencode($slug); ?>"
               class="btn-outline-white">
                <?php echo $t['cta_login'] ?? 'Ya tengo cuenta'; ?>
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
        var url          = window.location.href;
        var title        = alo ? alo.name : document.title;
        var twitterText  = <?php echo json_encode($twitter_text, JSON_UNESCAPED_UNICODE); ?>;
        var links = {
            whatsapp: 'https://wa.me/?text=' + encodeURIComponent(title + ' ' + url),
            facebook: 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url),
            twitter:  'https://x.com/intent/tweet?text=' + encodeURIComponent(twitterText) + '&url=' + encodeURIComponent(url) + '&hashtags=turismorural,escapadarural,rutasrurales',
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

    // Botón compartir del hero (con feedback visual como en eventos_landing)
    var btnShare = document.getElementById('btn-share');
    if (btnShare) {
        btnShare.addEventListener('click', function() {
            var url = window.location.href;
            var title = alo ? alo.name : document.title;
            if (navigator.share) {
                navigator.share({ title: title, url: url }).catch(function(){});
            } else if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function() {
                    var span = btnShare.querySelector('span');
                    var orig = span ? span.textContent : '';
                    if (span) span.textContent = '✅ Enlace copiado';
                    btnShare.classList.add('alo-hero-btn--copied');
                    setTimeout(function() {
                        if (span) span.textContent = orig;
                        btnShare.classList.remove('alo-hero-btn--copied');
                    }, 2500);
                }).catch(function(){});
            } else {
                // Fallback
                var input = document.createElement('input');
                input.value = url;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                var span = btnShare.querySelector('span');
                var orig = span ? span.textContent : '';
                if (span) span.textContent = '✅ Enlace copiado';
                btnShare.classList.add('alo-hero-btn--copied');
                setTimeout(function() {
                    if (span) span.textContent = orig;
                    btnShare.classList.remove('alo-hero-btn--copied');
                }, 2500);
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

    // ── Toggle Pasaporte Rural ────────────────────────────────────────────────
    (function() {
        var toggle = document.getElementById('toggle-pasaporte');
        if (!toggle) return;

        var resultado   = document.getElementById('pasaporte-resultado');
        var precioOrig  = document.getElementById('pasaporte-precio-original');
        var precioNuevo = document.getElementById('pasaporte-precio-nuevo');

        var displayOrig  = toggle.dataset.precioOriginalDisplay  || '';
        var displayPasp  = toggle.dataset.precioPasaportDisplay  || '';
        // Fallback: leer el atributo exacto del HTML (con guion)
        if (!displayPasp) displayPasp = toggle.getAttribute('data-precio-pasaporte-display') || '';

        toggle.addEventListener('change', function() {
            if (this.checked) {
                // Activar descuento Pasaporte Rural
                if (precioOrig)  precioOrig.textContent  = displayOrig + ' / noche';
                if (precioNuevo) precioNuevo.textContent = displayPasp + ' / noche';
                if (resultado)   resultado.style.display = 'block';
            } else {
                // Desactivar descuento
                if (resultado) resultado.style.display = 'none';
            }
        });
    })();

})();
</script>

</body>
</html>
