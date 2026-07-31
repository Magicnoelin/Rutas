<?php
/**
 * EVENTO MODULAR - Página de Detalle de Evento
 * Versión optimizada para velocidad y enganche de usuarios
 * 
 * URL: /evento-modular/{slug}
 * Prueba: https://rutasrurales.io/evento-modular/{slug}
 * Producción final: /evento/{slug} (reemplazará evento-detalle.php)
 */

define('API_NO_HEADERS', true);
require_once 'api/config.php';

// ── SEO: indicar explícitamente a Bing/Google que indexe Y archive estas páginas
// Esto elimina el warning "NOARCHIVE" de Bing Webmaster Tools / Copilot
// IMPORTANTE: "archive" debe aparecer explícitamente para que Bing no lo omita
header('X-Robots-Tag: index, follow, archive, max-image-preview:large, max-snippet:-1, max-video-preview:-1');

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$lang = isset($_GET['lang']) ? trim($_GET['lang']) : 'es';
$lang = in_array($lang, ['es', 'en', 'fr', 'de', 'zh']) ? $lang : 'es';

// ─── OBTENER DATOS CRÍTICOS DEL EVENTO (SSR para SEO) ────────────────────────
$evento = null;
$traduccion = null;

if (!empty($slug)) {
    try {
        $pdo = getDBConnection();

        if ($lang === 'es') {
            $stmt = $pdo->prepare("
                SELECT e.id, e.name AS titulo, e.slug, e.description, e.description_linked,
                       e.short_description,
                       e.meta_title, e.meta_description, e.start_date, e.end_date,
                       e.venue_name AS localidad, e.venue_address, e.municipality, e.province,
                       e.latitude, e.longitude, e.is_free, e.ticket_price, e.organizer, e.hero_image,
                       e.photo1, e.photo2, e.photo3, e.photo4, e.poster_image,
                       e.category_id, e.is_active, e.status,
                       e.program, e.target_audience, e.accessibility
                FROM cultural_events e
                WHERE e.slug = ? AND e.is_active = 1
            ");
            $stmt->execute([$slug]);
        } else {
            $stmt_trad = $pdo->prepare("
                SELECT event_id, name AS titulo_trad, slug AS slug_trad,
                       short_description AS short_desc_trad, description AS descripcion_trad,
                       meta_title AS meta_title_trad, meta_description AS meta_desc_trad,
                       program AS programa_trad, target_audience AS audiencia_trad,
                       accessibility AS accesibilidad_trad
                FROM cultural_events_trads
                WHERE slug = ? AND language_code = ?
            ");
            $stmt_trad->execute([$slug, $lang]);
            $traduccion = $stmt_trad->fetch(PDO::FETCH_ASSOC);

            if ($traduccion) {
                $stmt = $pdo->prepare("
                    SELECT e.id, e.name AS titulo, e.slug, e.description, e.short_description,
                           e.meta_title, e.meta_description, e.start_date, e.end_date,
                           e.venue_name AS localidad, e.venue_address, e.municipality, e.province, e.hero_image,
                           e.latitude, e.longitude, e.is_free, e.ticket_price, e.organizer,
                           e.photo1, e.photo2, e.photo3, e.photo4, e.poster_image,
                           e.category_id, e.is_active, e.status
                    FROM cultural_events e WHERE e.id = ? AND e.is_active = 1
                ");
                $stmt->execute([$traduccion['event_id']]);
            } else {
                $stmt = $pdo->prepare("
                    SELECT e.id, e.name AS titulo, e.slug, e.description, e.short_description,
                           e.meta_title, e.meta_description, e.start_date, e.end_date,
                           e.venue_name AS localidad, e.venue_address, e.municipality, e.province, e.hero_image,
                           e.latitude, e.longitude, e.is_free, e.ticket_price, e.organizer,
                           e.photo1, e.photo2, e.photo3, e.photo4, e.poster_image,
                           e.category_id, e.is_active, e.status
                    FROM cultural_events e WHERE e.slug = ? AND e.is_active = 1
                ");
                $stmt->execute([$slug]);
            }
        }

        $evento = $stmt->fetch(PDO::FETCH_ASSOC);

        // ── Fallback: si no se encontró traducción por slug (p.ej. la URL usa el
        // slug en español pero el idioma solicitado es otro), buscarla por event_id.
        // Esto ocurre cuando Google/usuarios acceden a /de/evento/<slug-español>.
        if (!$traduccion && $evento && $lang !== 'es') {
            $stmt_trad_fallback = $pdo->prepare("
                SELECT event_id, name AS titulo_trad, slug AS slug_trad,
                       short_description AS short_desc_trad, description AS descripcion_trad,
                       meta_title AS meta_title_trad, meta_description AS meta_desc_trad,
                       program AS programa_trad, target_audience AS audiencia_trad,
                       accessibility AS accesibilidad_trad
                FROM cultural_events_trads
                WHERE event_id = ? AND language_code = ?
            ");
            $stmt_trad_fallback->execute([$evento['id'], $lang]);
            $traduccion = $stmt_trad_fallback->fetch(PDO::FETCH_ASSOC);
        }

        if ($traduccion && $evento) {
            $evento['titulo']            = $traduccion['titulo_trad']      ?? $evento['titulo'];
            $evento['description']       = $traduccion['descripcion_trad'] ?? $evento['description'];
            $evento['short_description'] = $traduccion['short_desc_trad']  ?? $evento['short_description'];
            $evento['meta_title']        = $traduccion['meta_title_trad']  ?? $evento['meta_title'];
            $evento['meta_description']  = $traduccion['meta_desc_trad']   ?? $evento['meta_description'];
            $evento['programa']          = $traduccion['programa_trad']    ?? '';
            $evento['audiencia']         = $traduccion['audiencia_trad']   ?? '';
            $evento['accesibilidad']     = $traduccion['accesibilidad_trad'] ?? '';
        } elseif ($evento) {
            // Para español: mapear columnas nativas al alias esperado en las vistas
            $evento['programa']     = $evento['program']          ?? '';
            $evento['audiencia']    = $evento['target_audience']  ?? '';
            $evento['accesibilidad']= $evento['accessibility']    ?? '';
        }

    } catch (Exception $e) {
        error_log("evento-modular/index.php error: " . $e->getMessage());
    }
}

// ─── SEO ─────────────────────────────────────────────────────────────────────
$page_title = $evento ? ($evento['meta_title'] ?: $evento['titulo'] . ' | Rutas Rurales') : 'Evento Cultural | Rutas Rurales';
$page_desc  = $evento ? ($evento['meta_description'] ?: $evento['short_description'] ?: '') : 'Descubre este evento en Rutas Rurales';
$page_description = $page_desc;
$slug_canonical = $slug;
if ($evento) {
    $slug_canonical = ($lang !== 'es' && !empty($traduccion['slug_trad'])) ? $traduccion['slug_trad'] : $evento['slug'];
}
$canonical = 'https://rutasrurales.io/' . ($lang !== 'es' ? $lang . '/' : '') . 'evento/' . $slug_canonical;

// ── Cargar TODAS las traducciones disponibles para hreflang completo ──────────
// Necesario para que cada página declare todos los idiomas alternativos,
// no solo el español y el idioma actual.
$todas_trads = []; // [lang_code => slug_traducido]
if ($evento && isset($pdo)) {
    try {
        $stmtTrads = $pdo->prepare("
            SELECT language_code, slug
            FROM cultural_events_trads
            WHERE event_id = ?
              AND language_code IN ('en', 'fr', 'de', 'zh')
              AND slug IS NOT NULL
              AND slug != ''
        ");
        $stmtTrads->execute([$evento['id']]);
        foreach ($stmtTrads->fetchAll(PDO::FETCH_ASSOC) as $tr) {
            $todas_trads[$tr['language_code']] = $tr['slug'];
        }
    } catch (Exception $e) {
        // Si falla, continuar sin hreflang de traducciones
        $todas_trads = [];
    }
}

// Fotos — deduplicadas. hero_image se usa SOLO como fondo del hero, NO va a la galería.
// Lógica: poster_image primero (portada), luego photo1-4.
$fotos = [];
$hero_bg_url = ''; // URL para el fondo del .event-hero
if ($evento) {
    // hero_image: solo para el fondo del hero (cabecera visual)
    if (!empty($evento['hero_image'])) {
        $hi = $evento['hero_image'];
        if (!preg_match('/^https?:\/\//', $hi)) $hi = '/' . ltrim($hi, '/');
        $hero_bg_url = $hi;
    }

    $fotos_raw = [];
    // poster_image primero (imagen de portada)
    if (!empty($evento['poster_image'])) {
        $fotos_raw[] = $evento['poster_image'];
    }
    // luego las fotos del evento
    foreach (['photo1','photo2','photo3','photo4'] as $campo) {
        if (!empty($evento[$campo])) {
            $fotos_raw[] = $evento[$campo];
        }
    }
    // Normalizar y deduplicar (compara solo el nombre de archivo)
    $seen = [];
    foreach ($fotos_raw as $raw) {
        $url = $raw;
        if (!preg_match('/^https?:\/\//', $url)) $url = '/' . ltrim($url, '/');
        // Clave de deduplicación: basename sin query string
        $key = strtolower(basename(parse_url($url, PHP_URL_PATH)));
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $fotos[] = $url;
        }
    }
}
$foto_og = !empty($fotos[0]) ? $fotos[0] : 'https://rutasrurales.io/menu_images/turismo_rural.webp';
// Asegurar URL absoluta (og:image requiere URL completa según spec de Open Graph)
if (!preg_match('/^https?:\/\//', $foto_og)) {
    $foto_og = 'https://rutasrurales.io' . (str_starts_with($foto_og, '/') ? '' : '/') . $foto_og;
}

// ─── TRADUCCIONES DE UI ───────────────────────────────────────────────────────
$ui = ['es' => [
        'categorias' => [
            1=>'Fiestas Populares',2=>'Fiestas Patronales',3=>'Fiestas Tradicionales',
            4=>'Romerías',5=>'Carnavales',6=>'Cultura y Espectáculos',7=>'Conciertos',
            8=>'Teatro',9=>'Exposiciones',10=>'Festivales de Música',11=>'Cine',
            12=>'Gastronomía y Ferias',13=>'Ferias Gastronómicas',14=>'Jornadas Gastronómicas',
            15=>'Mercados Tradicionales',16=>'Ferias de Productos Locales',17=>'Deportes',
            18=>'Carreras Populares',19=>'Maratones y Medias',20=>'Competiciones Ciclistas',
            21=>'Eventos Deportivos',22=>'Religión y Tradición',23=>'Semana Santa',
            24=>'Procesiones',25=>'Celebraciones Religiosas'
        ],
        'gratis'           => 'Gratis',
        'consultar'        => 'Consultar',
        'sobre_evento'     => '📋 Sobre el evento',
        'programa'         => '📅 Programa',
        'publico'          => '👥 Público',
        'accesibilidad'    => '♿ Accesibilidad',
        'info_evento'      => 'ℹ️ Información del evento',
        'fecha_inicio'     => 'Fecha inicio',
        'fecha_fin'        => 'Fecha fin',
        'ubicacion'        => 'Ubicación',
        'direccion'        => 'Dirección',
        'categoria'        => 'Categoría',
        'precio'           => 'Precio',
        'organiza'         => 'Organiza',
        'ver_mapa'         => 'Ver en el mapa',
        'click_mapa'       => 'Haz clic para cargar el mapa interactivo',
        'btn_evento'       => '📍 Evento',
        'btn_alojamientos' => '🏠 Alojamientos',
        'btn_lugares'      => '🏛️ Lugares',
        'btn_actividades'  => '🎯 Actividades',
        'aloj_cercanos'    => '🏠 Alojamientos turísticos cercanos',
        'ver_mas_aloj'     => 'Ver más alojamientos',
        'lugares_cercanos' => '🏛️ Lugares de interés cercanos',
        'ver_mas_lugares'  => 'Ver más lugares',
        'activ_cercanas'   => '🎯 Actividades turísticas cercanas',
        'ver_mas_activ'    => 'Ver más actividades',
        'eventos_similares'=> '🎭 Eventos similares',
        'cta_titulo'       => '¡No te pierdas ningún evento!',
        'cta_desc'         => 'Regístrate gratis y recibe alertas de eventos similares en',
        'cta_register'     => '✨ Registrarme gratis',
        'cta_login'        => 'Ya tengo cuenta',
        'visitas'          => 'Visitas',
        'likes'            => 'likes',
        'guardar'          => '🔖 Guardar evento',
        'anadir_ruta'      => '🗺️ Añadir a mi ruta',
        'suscripcion_titulo'=> 'Eventos similares',
        'suscripcion_desc' => 'Avísame cuando haya eventos de',
        'suscripcion_en'   => 'en',
        'suscripcion_btn'  => '🔔 Suscribirme',
        'no_encontrado_h1' => 'Evento no encontrado',
        'no_encontrado_p'  => 'El evento que buscas no existe o ya no está disponible.',
        'ver_todos'        => 'Ver todos los eventos',
        'aviso_legal'      => 'Aviso Legal',
        'cookies'          => 'Cookies',
        'agradecimientos'  => 'Agradecimientos',
        'eventos_link'     => 'Eventos',
        'aloj_link'        => 'Alojamientos',
        'acceder_link'     => 'Acceder',
        'cultura_fallback' => 'Cultura',
        'dias'             => 'días',
        'al'               => 'al',
        'share_btn'        => 'Compartir esta página',
        'share_title'      => '¡Mira este evento!',
        'share_copy'       => 'Enlace copiado ✓',
    ],
    'en' => [
        'categorias' => [
            1=>'Popular Festivals',2=>'Patron Saint Festivals',3=>'Traditional Festivals',
            4=>'Pilgrimages',5=>'Carnivals',6=>'Culture & Shows',7=>'Concerts',
            8=>'Theatre',9=>'Exhibitions',10=>'Music Festivals',11=>'Cinema',
            12=>'Gastronomy & Fairs',13=>'Gastronomic Fairs',14=>'Gastronomic Days',
            15=>'Traditional Markets',16=>'Local Product Fairs',17=>'Sports',
            18=>'Popular Races',19=>'Marathons & Half-Marathons',20=>'Cycling Competitions',
            21=>'Sporting Events',22=>'Religion & Tradition',23=>'Holy Week',
            24=>'Processions',25=>'Religious Celebrations'
        ],
        'gratis'           => 'Free',
        'consultar'        => 'Check price',
        'sobre_evento'     => '📋 About the event',
        'programa'         => '📅 Programme',
        'publico'          => '👥 Audience',
        'accesibilidad'    => '♿ Accessibility',
        'info_evento'      => 'ℹ️ Event information',
        'fecha_inicio'     => 'Start date',
        'fecha_fin'        => 'End date',
        'ubicacion'        => 'Location',
        'direccion'        => 'Address',
        'categoria'        => 'Category',
        'precio'           => 'Price',
        'organiza'         => 'Organiser',
        'ver_mapa'         => 'View on map',
        'click_mapa'       => 'Click to load the interactive map',
        'btn_evento'       => '📍 Event',
        'btn_alojamientos' => '🏠 Accommodation',
        'btn_lugares'      => '🏛️ Places',
        'btn_actividades'  => '🎯 Activities',
        'aloj_cercanos'    => '🏠 Nearby accommodation',
        'ver_mas_aloj'     => 'View more accommodation',
        'lugares_cercanos' => '🏛️ Nearby places of interest',
        'ver_mas_lugares'  => 'View more places',
        'activ_cercanas'   => '🎯 Nearby tourist activities',
        'ver_mas_activ'    => 'View more activities',
        'eventos_similares'=> '🎭 Similar events',
        'cta_titulo'       => 'Don\'t miss any event!',
        'cta_desc'         => 'Sign up for free and receive alerts for similar events in',
        'cta_register'     => '✨ Sign up free',
        'cta_login'        => 'I already have an account',
        'visitas'          => 'Views',
        'likes'            => 'likes',
        'guardar'          => '🔖 Save event',
        'anadir_ruta'      => '🗺️ Add to my route',
        'suscripcion_titulo'=> 'Similar events',
        'suscripcion_desc' => 'Notify me when there are events of',
        'suscripcion_en'   => 'in',
        'suscripcion_btn'  => '🔔 Subscribe',
        'no_encontrado_h1' => 'Event not found',
        'no_encontrado_p'  => 'The event you are looking for does not exist or is no longer available.',
        'ver_todos'        => 'View all events',
        'aviso_legal'      => 'Legal Notice',
        'cookies'          => 'Cookies',
        'agradecimientos'  => 'Acknowledgements',
        'eventos_link'     => 'Events',
        'aloj_link'        => 'Accommodation',
        'acceder_link'     => 'Log in',
        'cultura_fallback' => 'Culture',
        'dias'             => 'days',
        'al'               => 'to',
        'share_btn'        => 'Share this page',
        'share_title'      => 'Check out this event!',
        'share_copy'       => 'Link copied ✓',
    ],
    'fr' => [
        'categorias' => [
            1=>'Fêtes Populaires',2=>'Fêtes Patronales',3=>'Fêtes Traditionnelles',
            4=>'Pèlerinages',5=>'Carnavals',6=>'Culture & Spectacles',7=>'Concerts',
            8=>'Théâtre',9=>'Expositions',10=>'Festivals de Musique',11=>'Cinéma',
            12=>'Gastronomie & Foires',13=>'Foires Gastronomiques',14=>'Journées Gastronomiques',
            15=>'Marchés Traditionnels',16=>'Foires de Produits Locaux',17=>'Sports',
            18=>'Courses Populaires',19=>'Marathons & Semi-Marathons',20=>'Compétitions Cyclistes',
            21=>'Événements Sportifs',22=>'Religion & Tradition',23=>'Semaine Sainte',
            24=>'Processions',25=>'Célébrations Religieuses'
        ],
        'gratis'           => 'Gratuit',
        'consultar'        => 'Consulter le prix',
        'sobre_evento'     => '📋 À propos de l\'événement',
        'programa'         => '📅 Programme',
        'publico'          => '👥 Public',
        'accesibilidad'    => '♿ Accessibilité',
        'info_evento'      => 'ℹ️ Informations sur l\'événement',
        'fecha_inicio'     => 'Date de début',
        'fecha_fin'        => 'Date de fin',
        'ubicacion'        => 'Lieu',
        'direccion'        => 'Adresse',
        'categoria'        => 'Catégorie',
        'precio'           => 'Prix',
        'organiza'         => 'Organisateur',
        'ver_mapa'         => 'Voir sur la carte',
        'click_mapa'       => 'Cliquez pour charger la carte interactive',
        'btn_evento'       => '📍 Événement',
        'btn_alojamientos' => '🏠 Hébergements',
        'btn_lugares'      => '🏛️ Lieux',
        'btn_actividades'  => '🎯 Activités',
        'aloj_cercanos'    => '🏠 Hébergements à proximité',
        'ver_mas_aloj'     => 'Voir plus d\'hébergements',
        'lugares_cercanos' => '🏛️ Sites d\'intérêt à proximité',
        'ver_mas_lugares'  => 'Voir plus de lieux',
        'activ_cercanas'   => '🎯 Activités touristiques à proximité',
        'ver_mas_activ'    => 'Voir plus d\'activités',
        'eventos_similares'=> '🎭 Événements similaires',
        'cta_titulo'       => 'Ne manquez aucun événement !',
        'cta_desc'         => 'Inscrivez-vous gratuitement et recevez des alertes pour des événements similaires à',
        'cta_register'     => '✨ S\'inscrire gratuitement',
        'cta_login'        => 'J\'ai déjà un compte',
        'visitas'          => 'Vues',
        'likes'            => 'j\'aime',
        'guardar'          => '🔖 Sauvegarder l\'événement',
        'anadir_ruta'      => '🗺️ Ajouter à mon itinéraire',
        'suscripcion_titulo'=> 'Événements similaires',
        'suscripcion_desc' => 'Prévenez-moi quand il y a des événements de',
        'suscripcion_en'   => 'à',
        'suscripcion_btn'  => '🔔 S\'abonner',
        'no_encontrado_h1' => 'Événement introuvable',
        'no_encontrado_p'  => 'L\'événement que vous recherchez n\'existe pas ou n\'est plus disponible.',
        'ver_todos'        => 'Voir tous les événements',
        'aviso_legal'      => 'Mentions légales',
        'cookies'          => 'Cookies',
        'agradecimientos'  => 'Remerciements',
        'eventos_link'     => 'Événements',
        'aloj_link'        => 'Hébergements',
        'acceder_link'     => 'Se connecter',
        'cultura_fallback' => 'Culture',
        'dias'             => 'jours',
        'al'               => 'au',
        'share_btn'        => 'Partager cette page',
        'share_title'      => 'Découvrez cet événement !',
        'share_copy'       => 'Lien copié ✓',
    ],
    'de' => [
        'categorias' => [
            1=>'Volksfeste',2=>'Patronatsfeste',3=>'Traditionelle Feste',
            4=>'Wallfahrten',5=>'Karnevals',6=>'Kultur & Shows',7=>'Konzerte',
            8=>'Theater',9=>'Ausstellungen',10=>'Musikfestivals',11=>'Kino',
            12=>'Gastronomie & Messen',13=>'Gastronomische Messen',14=>'Gastronomische Tage',
            15=>'Traditionelle Märkte',16=>'Messen für lokale Produkte',17=>'Sport',
            18=>'Volksläufe',19=>'Marathons & Halbmarathons',20=>'Radrennen',
            21=>'Sportveranstaltungen',22=>'Religion & Tradition',23=>'Karwoche',
            24=>'Prozessionen',25=>'Religiöse Feiern'
        ],
        'gratis'           => 'Kostenlos',
        'consultar'        => 'Preis anfragen',
        'sobre_evento'     => '📋 Über die Veranstaltung',
        'programa'         => '📅 Programm',
        'publico'          => '👥 Zielgruppe',
        'accesibilidad'    => '♿ Barrierefreiheit',
        'info_evento'      => 'ℹ️ Veranstaltungsinfo',
        'fecha_inicio'     => 'Startdatum',
        'fecha_fin'        => 'Enddatum',
        'ubicacion'        => 'Ort',
        'direccion'        => 'Adresse',
        'categoria'        => 'Kategorie',
        'precio'           => 'Preis',
        'organiza'         => 'Veranstalter',
        'ver_mapa'         => 'Auf der Karte anzeigen',
        'click_mapa'       => 'Klicken Sie, um die interaktive Karte zu laden',
        'btn_evento'       => '📍 Veranstaltung',
        'btn_alojamientos' => '🏠 Unterkünfte',
        'btn_lugares'      => '🏛️ Orte',
        'btn_actividades'  => '🎯 Aktivitäten',
        'aloj_cercanos'    => '🏠 Unterkünfte in der Nähe',
        'ver_mas_aloj'     => 'Mehr Unterkünfte anzeigen',
        'lugares_cercanos' => '🏛️ Sehenswürdigkeiten in der Nähe',
        'ver_mas_lugares'  => 'Mehr Orte anzeigen',
        'activ_cercanas'   => '🎯 Touristische Aktivitäten in der Nähe',
        'ver_mas_activ'    => 'Mehr Aktivitäten anzeigen',
        'eventos_similares'=> '🎭 Ähnliche Veranstaltungen',
        'cta_titulo'       => 'Verpassen Sie keine Veranstaltung!',
        'cta_desc'         => 'Registrieren Sie sich kostenlos und erhalten Sie Benachrichtigungen für ähnliche Veranstaltungen in',
        'cta_register'     => '✨ Kostenlos registrieren',
        'cta_login'        => 'Ich habe bereits ein Konto',
        'visitas'          => 'Aufrufe',
        'likes'            => 'Likes',
        'guardar'          => '🔖 Veranstaltung speichern',
        'anadir_ruta'      => '🗺️ Zur Route hinzufügen',
        'suscripcion_titulo'=> 'Ähnliche Veranstaltungen',
        'suscripcion_desc' => 'Benachrichtige mich, wenn es Veranstaltungen von',
        'suscripcion_en'   => 'in',
        'suscripcion_btn'  => '🔔 Abonnieren',
        'no_encontrado_h1' => 'Veranstaltung nicht gefunden',
        'no_encontrado_p'  => 'Die gesuchte Veranstaltung existiert nicht oder ist nicht mehr verfügbar.',
        'ver_todos'        => 'Alle Veranstaltungen anzeigen',
        'aviso_legal'      => 'Impressum',
        'cookies'          => 'Cookies',
        'agradecimientos'  => 'Danksagungen',
        'eventos_link'     => 'Veranstaltungen',
        'aloj_link'        => 'Unterkünfte',
        'acceder_link'     => 'Anmelden',
        'cultura_fallback' => 'Kultur',
        'dias'             => 'Tage',
        'al'               => 'bis',
        'share_btn'        => 'Diese Seite teilen',
        'share_title'      => 'Schau dir diese Veranstaltung an!',
        'share_copy'       => 'Link kopiert ✓',
    ],
    'zh' => [
        'categorias' => [
            1=>'民间节日',2=>'守护神节日',3=>'传统节日',
            4=>'朝圣活动',5=>'狂欢节',6=>'文化与演出',7=>'音乐会',
            8=>'戏剧',9=>'展览',10=>'音乐节',11=>'电影',
            12=>'美食与集市',13=>'美食集市',14=>'美食节',
            15=>'传统市场',16=>'本地产品集市',17=>'体育',
            18=>'大众赛跑',19=>'马拉松与半马',20=>'自行车赛',
            21=>'体育赛事',22=>'宗教与传统',23=>'圣周',
            24=>'宗教游行',25=>'宗教庆典'
        ],
        'gratis'           => '免费',
        'consultar'        => '咨询价格',
        'sobre_evento'     => '📋 关于活动',
        'programa'         => '📅 活动日程',
        'publico'          => '👥 目标受众',
        'accesibilidad'    => '♿ 无障碍设施',
        'info_evento'      => 'ℹ️ 活动信息',
        'fecha_inicio'     => '开始日期',
        'fecha_fin'        => '结束日期',
        'ubicacion'        => '地点',
        'direccion'        => '地址',
        'categoria'        => '类别',
        'precio'           => '价格',
        'organiza'         => '主办方',
        'ver_mapa'         => '在地图上查看',
        'click_mapa'       => '点击加载互动地图',
        'btn_evento'       => '📍 活动',
        'btn_alojamientos' => '🏠 住宿',
        'btn_lugares'      => '🏛️ 景点',
        'btn_actividades'  => '🎯 活动项目',
        'aloj_cercanos'    => '🏠 附近住宿',
        'ver_mas_aloj'     => '查看更多住宿',
        'lugares_cercanos' => '🏛️ 附近景点',
        'ver_mas_lugares'  => '查看更多景点',
        'activ_cercanas'   => '🎯 附近旅游活动',
        'ver_mas_activ'    => '查看更多活动',
        'eventos_similares'=> '🎭 类似活动',
        'cta_titulo'       => '不要错过任何活动！',
        'cta_desc'         => '免费注册，接收类似活动提醒，地区：',
        'cta_register'     => '✨ 免费注册',
        'cta_login'        => '我已有账户',
        'visitas'          => '浏览量',
        'likes'            => '点赞',
        'guardar'          => '🔖 保存活动',
        'anadir_ruta'      => '🗺️ 添加到我的路线',
        'suscripcion_titulo'=> '类似活动',
        'suscripcion_desc' => '当有以下类型的活动时通知我：',
        'suscripcion_en'   => '地区：',
        'suscripcion_btn'  => '🔔 订阅',
        'no_encontrado_h1' => '未找到活动',
        'no_encontrado_p'  => '您查找的活动不存在或已不再提供。',
        'ver_todos'        => '查看所有活动',
        'aviso_legal'      => '法律声明',
        'cookies'          => 'Cookies',
        'agradecimientos'  => '致谢',
        'eventos_link'     => '活动',
        'aloj_link'        => '住宿',
        'acceder_link'     => '登录',
        'cultura_fallback' => '文化',
        'dias'             => '天',
        'al'               => '至',
        'share_btn'        => '分享此页面',
        'share_title'      => '看看这个活动！',
        'share_copy'       => '链接已复制 ✓',
    ],
];

$t = $ui[$lang] ?? $ui['es'];

// Categorías (en el idioma correcto)
$categorias = $t['categorias'];
$categoria_nombre = $categorias[$evento['category_id'] ?? 0] ?? $t['cultura_fallback'];

// Fechas formateadas
$fecha_display = '';
if ($evento && !empty($evento['start_date'])) {
    $start = date('d/m/Y', strtotime($evento['start_date']));
    if (!empty($evento['end_date']) && $evento['end_date'] !== $evento['start_date']) {
        $end = date('d/m/Y', strtotime($evento['end_date']));
        $diff = (new DateTime($evento['start_date']))->diff(new DateTime($evento['end_date']));
        $days = $diff->days + 1;
        $fecha_display = $days <= 2 ? "$start - $end" : "$start {$t['al']} $end ($days {$t['dias']})";
    } else {
        $fecha_display = $start;
    }
}

// Precio
$precio_display = '';
if ($evento) {
    if ($evento['is_free'] == 1) $precio_display = $t['gratis'];
    elseif (!empty($evento['ticket_price']) && $evento['ticket_price'] > 0) $precio_display = number_format($evento['ticket_price'], 2) . '€';
    else $precio_display = $t['consultar'];
}

// Ubicación
$ubicacion_display = '';
if ($evento) {
    $partes = array_filter([$evento['localidad'] ?? '', $evento['municipality'] ?? '', $evento['province'] ?? '']);
    $ubicacion_display = implode(', ', $partes);
}

// ─── JSON-LD: Schema.org (Event + WebPage + BreadcrumbList) ──────────────────
// Se generan múltiples bloques schema para máxima cobertura SEO:
//   1. Event  → datos del evento (el más importante para Rich Results)
//   2. WebPage → vincula la URL canónica, descripción e imagen
//   3. BreadcrumbList → navegación estructurada (mejora snippets)
// Los tres se emiten en un único <script> como @graph para no repetir @context.
// Sin consultas adicionales a BD → impacto 0 en velocidad.
$jsonld = '';
if ($evento) {

    // ── imagen absoluta ──────────────────────────────────────────────────────
    $jsonld_image = !empty($fotos[0]) ? $fotos[0] : $foto_og;
    if (!preg_match('/^https?:\/\//', $jsonld_image)) {
        $jsonld_image = 'https://rutasrurales.io' . (str_starts_with($jsonld_image, '/') ? '' : '/') . $jsonld_image;
    }

    // ── organizer / performer ────────────────────────────────────────────────
    $org_name = !empty($evento['organizer'])
        ? $evento['organizer']
        : (!empty($evento['municipality'])
            ? 'Ayuntamiento de ' . $evento['municipality']
            : (!empty($evento['province'])
                ? 'Ayuntamiento de ' . $evento['province']
                : 'Rutas Rurales'));
    // URL del organizador: si tenemos municipio, construimos URL de búsqueda; si no, la web principal
    $org_url = !empty($evento['municipality'])
        ? 'https://rutasrurales.io/eventos-culturales-paginacion.html?municipio=' . urlencode($evento['municipality'])
        : 'https://rutasrurales.io';
    $perf_name = !empty($evento['organizer'])
        ? $evento['organizer']
        : (!empty($evento['municipality'])
            ? $evento['municipality']
            : ($evento['province'] ?? 'Rutas Rurales'));

    // ── descripción limpia ───────────────────────────────────────────────────
    $desc_plain = trim(strip_tags($evento['short_description'] ?? '') ?: strip_tags($evento['description'] ?? ''));
    if (empty($desc_plain)) {
        $parts = array_filter([
            $categoria_nombre,
            !empty($ubicacion_display) ? 'en ' . $ubicacion_display : null,
            !empty($evento['start_date']) ? date('d/m/Y', strtotime($evento['start_date'])) : null,
        ]);
        $desc_plain = $evento['titulo'] . (count($parts) ? '. ' . implode(', ', $parts) . '.' : '.');
    }

    // ── location ─────────────────────────────────────────────────────────────
    $location_data = [
        '@type' => 'Place',
        'name'  => $evento['localidad'] ?: $evento['municipality'],
        'address' => [
            '@type'           => 'PostalAddress',
            'addressLocality' => $evento['municipality'],
            'addressRegion'   => $evento['province'],
            'addressCountry'  => 'ES',
        ],
    ];
    if (!empty($evento['latitude']) && !empty($evento['longitude'])) {
        $location_data['geo'] = [
            '@type'     => 'GeoCoordinates',
            'latitude'  => $evento['latitude'],
            'longitude' => $evento['longitude'],
        ];
    }

    // ── Fechas en formato ISO 8601 completo (requerido por Google para Rich Results) ──
    // Las fechas en BD vienen como YYYY-MM-DD; Schema.org exige ISO 8601 con hora y zona.
    // Usamos T00:00:00+02:00 (hora española peninsular, CEST en verano).
    // Si start_date está vacío (no debería) usamos la fecha de hoy como fallback.
    $tz_offset = '+02:00'; // Spain/Europe Madrid (CEST verano). Cambiar a +01:00 en invierno si se quiere.
    $start_iso = !empty($evento['start_date'])
        ? date('Y-m-d', strtotime($evento['start_date'])) . 'T00:00:00' . $tz_offset
        : date('Y-m-d') . 'T00:00:00' . $tz_offset;
    // endDate: si no hay fecha fin, usar la de inicio (Google acepta eventos de 1 día así)
    $end_raw  = !empty($evento['end_date']) ? $evento['end_date'] : $evento['start_date'];
    $end_iso  = date('Y-m-d', strtotime($end_raw)) . 'T23:59:00' . $tz_offset;
    // validFrom para offers: publicamos desde la fecha de inicio del evento
    $valid_from_iso = $start_iso;

    // ── offers ───────────────────────────────────────────────────────────────
    // Todos los casos incluyen price, priceCurrency y validFrom (evitan errores GSC)
    if ($evento['is_free'] == 1) {
        $offers_data = [
            '@type'        => 'Offer',
            'url'          => $canonical,
            'price'        => '0',
            'priceCurrency'=> 'EUR',
            'availability' => 'https://schema.org/InStock',
            'validFrom'    => $valid_from_iso,
        ];
    } elseif (!empty($evento['ticket_price']) && $evento['ticket_price'] > 0) {
        $offers_data = [
            '@type'        => 'Offer',
            'url'          => $canonical,
            'price'        => number_format((float)$evento['ticket_price'], 2, '.', ''),
            'priceCurrency'=> 'EUR',
            'availability' => 'https://schema.org/InStock',
            'validFrom'    => $valid_from_iso,
        ];
    } else {
        // Precio a consultar: Google recomienda incluir price y priceCurrency incluso
        // cuando no se conoce el precio. Usamos price=0 e indicamos que hay que consultar
        // en la descripción, o se puede omitir offers; pero incluirlo evita el error GSC.
        $offers_data = [
            '@type'        => 'Offer',
            'url'          => $canonical,
            'price'        => '0',
            'priceCurrency'=> 'EUR',
            'availability' => 'https://schema.org/InStock',
            'validFrom'    => $valid_from_iso,
        ];
    }

    // ── URLs alternativas (para sameAs en Event) ─────────────────────────────
    // Lista todas las versiones del evento en todos los idiomas disponibles,
    // incluida la española. Permite a los motores de búsqueda entender que
    // son el mismo evento en distintos idiomas.
    $same_as_urls = ['https://rutasrurales.io/evento/' . $evento['slug']];
    foreach ($todas_trads as $altLang => $altSlug) {
        $same_as_urls[] = 'https://rutasrurales.io/' . $altLang . '/evento/' . $altSlug;
    }
    // Eliminar duplicado si la URL canónica ya está en la lista
    $same_as_urls = array_unique(array_filter($same_as_urls, fn($u) => $u !== $canonical));

    // ── 1. Nodo Event ────────────────────────────────────────────────────────
    $node_event = [
        '@type'               => 'Event',
        '@id'                 => $canonical . '#event',
        'name'                => $evento['titulo'],
        'description'         => $desc_plain,
        'startDate'           => $start_iso,
        'endDate'             => $end_iso,
        'eventStatus'         => 'https://schema.org/EventScheduled',
        'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
        'location'            => $location_data,
        'organizer'           => [
            '@type' => 'Organization',
            'name'  => $org_name,
            'url'   => $org_url,
        ],
        'performer'           => [
            '@type' => 'PerformingGroup',
            'name'  => $perf_name,
        ],
        'isAccessibleForFree' => $evento['is_free'] == 1,
        'url'                 => $canonical,
        'image'               => $jsonld_image,
        'offers'              => $offers_data,
    ];
    if (!empty($same_as_urls)) {
        $node_event['sameAs'] = array_values($same_as_urls);
    }

    // ── 2. Nodo WebPage ──────────────────────────────────────────────────────
    // inLanguage usa el código de idioma BCP-47 (zh-Hans para chino simplificado)
    $lang_bcp47 = $lang === 'zh' ? 'zh-Hans' : $lang;
    $node_webpage = [
        '@type'       => 'WebPage',
        '@id'         => $canonical . '#webpage',
        'url'         => $canonical,
        'name'        => $page_title,
        'description' => $page_desc,
        'inLanguage'  => $lang_bcp47,
        'isPartOf'    => ['@id' => 'https://rutasrurales.io/#website'],
        'about'       => ['@id' => $canonical . '#event'],
        'image'       => $jsonld_image,
        'publisher'   => [
            '@type' => 'Organization',
            'name'  => 'Rutas Rurales',
            'url'   => 'https://rutasrurales.io',
            'logo'  => [
                '@type' => 'ImageObject',
                'url'   => 'https://rutasrurales.io/menu_images/Logo%20transparente.webp',
            ],
        ],
    ];

    // ── 3. Nodo BreadcrumbList ───────────────────────────────────────────────
    // Traducciones de la miga de pan según idioma actual
    $crumb_home   = ['es'=>'Inicio','en'=>'Home','fr'=>'Accueil','de'=>'Startseite','zh'=>'首页'];
    $crumb_events = ['es'=>'Eventos','en'=>'Events','fr'=>'Événements','de'=>'Veranstaltungen','zh'=>'活动'];
    $lang_prefix_url = $lang !== 'es' ? $lang . '/' : '';

    $node_breadcrumb = [
        '@type'           => 'BreadcrumbList',
        '@id'             => $canonical . '#breadcrumb',
        'itemListElement' => [
            [
                '@type'    => 'ListItem',
                'position' => 1,
                'name'     => $crumb_home[$lang] ?? 'Inicio',
                'item'     => 'https://rutasrurales.io/' . ($lang !== 'es' ? $lang . '/index.html' : 'index.html'),
            ],
            [
                '@type'    => 'ListItem',
                'position' => 2,
                'name'     => $crumb_events[$lang] ?? 'Eventos',
                'item'     => 'https://rutasrurales.io/' . $lang_prefix_url . 'eventos-culturales-paginacion.html',
            ],
            [
                '@type'    => 'ListItem',
                'position' => 3,
                'name'     => $evento['titulo'],
                'item'     => $canonical,
            ],
        ],
    ];

    // ── Emitir como @graph (un único bloque, más limpio y semántico) ─────────
    $jsonld_graph = [
        '@context' => 'https://schema.org',
        '@graph'   => [$node_event, $node_webpage, $node_breadcrumb],
    ];
    $jsonld = json_encode($jsonld_graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}

// Datos para JavaScript (evitar segunda llamada API)
$evento_js = $evento ? json_encode([
    'id'          => $evento['id'],
    'titulo'      => $evento['titulo'],
    'slug'        => $evento['slug'],
    'start_date'  => $evento['start_date'],
    'end_date'    => $evento['end_date'],
    'latitude'    => $evento['latitude'],
    'longitude'   => $evento['longitude'],
    'province'    => $evento['province'],
    'municipality'=> $evento['municipality'],
    'fotos'       => $fotos,
    'categoria'   => $categoria_nombre,
    'is_free'     => $evento['is_free'],
    'ticket_price'=> $evento['ticket_price'],
    'organizer'   => $evento['organizer'],
    'localidad'   => $evento['localidad'],
    'venue_address'=> $evento['venue_address'],
    'programa'    => $evento['programa'] ?? '',
    'audiencia'   => $evento['audiencia'] ?? '',
    'accesibilidad'=> $evento['accesibilidad'] ?? '',
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) : 'null';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- SEO: "archive" explícito → elimina el warning NOARCHIVE de Bing Copilot/Grounding.
         Bing requiere la directiva "archive" de forma expresa; sin ella marca la página
         como NOARCHIVE aunque no haya una instrucción contraria en el código. -->
    <meta name="robots" content="index, follow, archive, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_desc); ?>">
    <link rel="canonical" href="<?php echo $canonical; ?>">

    <!-- hreflang: SEO multiidioma — declaramos TODOS los idiomas del sitio.
         Si un idioma no tiene slug traducido en BD, usamos el slug español como
         fallback para que Google sepa que existe versión en ese idioma (aunque
         redirecte al contenido español). Esto evita que solo aparezca 1 hreflang. -->
    <?php if ($evento): ?>
    <?php
    // Construir mapa completo de hreflang: todos los idiomas del sitio
    $hreflang_map = [
        'es'      => 'https://rutasrurales.io/evento/' . $evento['slug'],
        'en'      => 'https://rutasrurales.io/en/evento/' . ($todas_trads['en'] ?? $evento['slug']),
        'fr'      => 'https://rutasrurales.io/fr/evento/' . ($todas_trads['fr'] ?? $evento['slug']),
        'de'      => 'https://rutasrurales.io/de/evento/' . ($todas_trads['de'] ?? $evento['slug']),
        'zh-Hans' => 'https://rutasrurales.io/zh/evento/' . ($todas_trads['zh'] ?? $evento['slug']),
    ];
    ?>
    <?php foreach ($hreflang_map as $hLang => $hUrl): ?>
    <link rel="alternate" hreflang="<?php echo htmlspecialchars($hLang); ?>" href="<?php echo htmlspecialchars($hUrl); ?>">
    <?php endforeach; ?>
    <link rel="alternate" hreflang="x-default" href="https://rutasrurales.io/evento/<?php echo htmlspecialchars($evento['slug']); ?>">
    <?php endif; ?>

    <!-- Open Graph — og:type="event" para que Bing/Facebook reconozcan el contenido -->
    <meta property="og:type" content="event">
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($page_desc); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($foto_og); ?>">
    <meta property="og:image:secure_url" content="<?php echo htmlspecialchars($foto_og); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:type" content="<?php echo (preg_match('/\.webp$/i', $foto_og) ? 'image/webp' : (preg_match('/\.png$/i', $foto_og) ? 'image/png' : 'image/jpeg')); ?>">
    <meta property="og:url" content="<?php echo $canonical; ?>">
    <meta property="og:site_name" content="Rutas Rurales">

    <!-- Favicon -->
    <link rel="icon" href="/menu_images/Favicon.png" type="image/png">

    <!-- Estilos globales (normalmente cargados por header.php, aquí incluidos
         directamente porque header.php omite su <head> en esta página) -->
    <link rel="stylesheet" href="/styles.css">

    <!-- Preconnect solo para recursos críticos -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://unpkg.com" crossorigin>

    <!-- ── Estilos del navbar (header.php los omite con HEADER_NO_HTML_HEAD) ── -->
    <!-- Necesarios para que el menú de navegación se vea correctamente.         -->
    <style>
        /* RESET GLOBAL navMenu */
        #navMenu a, #navMenu a:visited, #navMenu a:active {
            text-decoration: none !important;
            color: inherit !important;
            -webkit-tap-highlight-color: transparent;
        }

        /* DESKTOP */
        @media (min-width: 993px) {
            .hamburger { display: none !important; }
            .nav-menu {
                display: flex !important;
                flex-direction: column;
                gap: 10px;
                align-items: center;
                font-family: 'Montserrat', sans-serif;
                margin-left: auto;
            }
            .nav-row {
                display: flex !important;
                list-style: none !important;
                margin: 0; padding: 0;
                width: 650px;
                justify-content: center;
            }
            .nav-row li { flex: 1; text-align: center; }
            .nav-row li a {
                font-size: 0.9rem;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                text-transform: capitalize;
                color: var(--accent-color, #81C784) !important;
                font-weight: 600;
            }
            .nav-row li a span { color: var(--accent-color, #81C784) !important; }
            .nav-row li a i  { color: var(--accent-color, #81C784) !important; font-size: 1.1rem; }
            .nav-row li a:hover,
            .nav-row li a:hover span,
            .nav-row li a:hover i { color: #ffffff !important; }
            .logo-apoyar-wrap { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
            .apoyar-mobile-row { display: none !important; }
        }

        /* MÓVIL */
        @media (max-width: 992px) {
            .header, .navbar {
                height: auto !important;
                padding: 2px 0 !important;
                position: fixed !important;
                top: 0 !important;
                width: 100% !important;
                z-index: 9999 !important;
                background-color: #2F5233 !important;
            }
            .navbar .container {
                flex-direction: row !important;
                justify-content: flex-start !important;
                align-items: center !important;
                gap: 5px !important;
                padding: 0 5px !important;
                display: flex !important;
                width: 100% !important;
            }
            .logo { flex-shrink: 0 !important; margin-right: 2px !important; display: block !important; }
            .logo img { height: 35px !important; width: auto !important; }
            .logo-text { display: none !important; }
            .nav-menu {
                display: flex !important;
                position: static !important;
                width: auto !important;
                height: auto !important;
                background: transparent !important;
                flex-direction: column !important;
                flex: 1 !important;
                gap: 1px !important;
                padding: 0 !important;
                margin: 0 !important;
                box-shadow: none !important;
            }
            .nav-row {
                display: grid !important;
                grid-template-columns: repeat(4, 1fr) !important;
                gap: 2px !important;
                width: 100% !important;
                list-style: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .nav-row li { display: block !important; }
            .nav-row li a {
                background: rgba(255,255,255,0.1) !important;
                min-height: 30px !important;
                padding: 1px !important;
                border-radius: 4px !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center;
            }
            .nav-row li a span {
                font-size: 0.50rem !important;
                font-weight: 600 !important;
                line-height: 1 !important;
                text-align: center !important;
                white-space: nowrap !important;
                color: #d4a574 !important;
                margin: 0 !important;
            }
            .nav-row li a i { font-size: 0.8rem !important; margin-bottom: 0 !important; color: #ffffff !important; }
            /* Botón Apoyar móvil */
            .logo-apoyar-wrap { display: flex; align-items: center; gap: 0; flex-shrink: 0 !important; }
            .btn-apoyar-desktop { display: none !important; }
            .apoyar-mobile-row {
                display: grid !important;
                grid-template-columns: 1fr !important;
                width: 100% !important;
                margin: 0 !important; padding: 0 !important;
                list-style: none !important; gap: 0 !important;
            }
            .apoyar-mobile-row li a {
                background: rgba(255,143,0,0.15) !important;
                border: 1px solid rgba(255,143,0,0.35) !important;
                min-height: 22px !important;
                padding: 2px 6px !important;
                border-radius: 4px !important;
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 4px !important;
                color: #ffd080 !important;
                font-size: 0.55rem !important;
                font-weight: 700 !important;
                white-space: nowrap !important;
                text-decoration: none !important;
            }
            .apoyar-mobile-row li a i { font-size: 0.65rem !important; color: #ffd080 !important; margin-bottom: 0 !important; }
            /* Avatar asistente en móvil */
            .asistente-avatar { width: 18px !important; height: 18px !important; margin-bottom: 0; }
            /* Evitar zoom en inputs */
            input[type="text"], input[type="number"], input[type="search"], textarea, select { font-size: 16px !important; }
        }

        /* Avatar asistente (desktop) */
        .asistente-avatar {
            width: 26px; height: 26px;
            border-radius: 50%; object-fit: cover;
            border: 1.5px solid #ffffff; vertical-align: middle;
        }

        /* Botón Apoyar (general) */
        .btn-apoyar {
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(255,143,0,0.18);
            border: 1.5px solid rgba(255,143,0,0.55);
            color: #ffd080 !important;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.78rem; font-weight: 700;
            padding: 5px 11px; border-radius: 20px;
            text-decoration: none !important; white-space: nowrap;
            transition: background 0.2s, border-color 0.2s, color 0.2s; line-height: 1;
        }
        .btn-apoyar:hover {
            background: rgba(255,143,0,0.38) !important;
            border-color: #ff8f00 !important; color: #ffffff !important;
        }

        /* Ajuste hero para compensar la altura variable del navbar en móvil */
        @media (max-width: 992px) {
            .event-hero { margin-top: 90px; }
        }
    </style>

    <!-- Fuentes locales (Montserrat) -->
    <style>
        @font-face {
            font-family: 'Montserrat';
            font-style: normal;
            font-weight: 400;
            font-display: swap;
            src: url('/fonts/montserrat-v31-latin-regular.woff2') format('woff2');
        }
        @font-face {
            font-family: 'Montserrat';
            font-style: normal;
            font-weight: 700;
            font-display: swap;
            src: url('/fonts/montserrat-v31-latin-600.woff2') format('woff2');
        }
    </style>

    <!-- CSS CRÍTICO INLINE (solo lo esencial para el primer render) -->
    <style>
        /* ── Variables ── */
        :root {
            --primary: #2F5233;
            --primary-light: #3d6b42;
            --accent: #81C784;
            --accent-warm: #F9A825;
            --text: #333;
            --text-light: #666;
            --bg: #f8f9fa;
            --white: #fff;
            --radius: 12px;
            --shadow: 0 4px 20px rgba(0,0,0,0.08);
            --shadow-hover: 0 8px 30px rgba(0,0,0,0.15);
        }

        /* ── Reset mínimo ── */
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

        /* ── Hero ── */
        .event-hero {
            margin-top: 70px; /* altura del .header/.navbar fijo */
            background: linear-gradient(135deg, var(--primary) 0%, #1a3d1e 100%);
            color: var(--white);
            padding: 50px 20px 70px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        /* Hero con foto de portada: overlay oscuro encima de la imagen */
        .event-hero.has-hero-img {
            background-size: cover;
            background-position: center top;
            background-repeat: no-repeat;
        }
        .event-hero.has-hero-img::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                180deg,
                rgba(0,0,0,0.45) 0%,
                rgba(15,40,18,0.72) 60%,
                rgba(15,40,18,0.85) 100%
            );
            z-index: 0;
        }
        /* Todo el contenido del hero queda por encima del overlay */
        .event-hero.has-hero-img > * { position: relative; z-index: 1; }
        .event-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .event-hero-badge {
            display: inline-block;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            color: var(--white);
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
            text-transform: uppercase;
        }
        .event-hero h1 {
            font-size: clamp(1.6rem, 4vw, 2.8rem);
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 16px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .event-hero-meta {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 16px;
            font-size: 0.95rem;
            opacity: 0.92;
        }
        .event-hero-meta span {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .event-hero-meta .icon { font-size: 1rem; }

        /* ── Contenedor principal ── */
        .event-layout {
            max-width: 1100px;
            margin: -40px auto 60px;
            padding: 0 16px;
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 24px;
            align-items: start;
        }
        @media (max-width: 900px) {
            .event-layout { grid-template-columns: 1fr; margin-top: -30px; }
        }

        /* ── Card base ── */
        .card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .card-body { padding: 28px; }
        .card-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--accent);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ── Galería ── */
        .event-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }
        .gallery-item {
            border-radius: 8px;
            overflow: hidden;
            aspect-ratio: 4/3;
            cursor: pointer;
            position: relative;
        }
        .gallery-item img {
            width: 100%; height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .gallery-item:hover img { transform: scale(1.05); }

        /* ── Descripción ── */
        .event-description {
            line-height: 1.8;
            font-size: 1rem;
            color: var(--text);
        }
        .event-description p { margin-bottom: 1.2rem; }
        /* ── Inbound links: visibles en todos los contextos (párrafos, headings) */
        .event-description a {
            color: #2F5233;
            text-decoration: underline;
            text-decoration-color: rgba(47, 82, 51, 0.4);
            text-underline-offset: 2px;
            font-weight: 600;
            transition: color 0.15s, text-decoration-color 0.15s;
        }
        .event-description a:hover {
            color: #1a3a1e;
            text-decoration-color: #2F5233;
        }
        .event-description h2 a,
        .event-description h3 a,
        .event-description h4 a {
            /* En headings: heredar tamaño y peso, pero mantener el color de link */
            font-size: inherit;
            font-weight: inherit;
        }

        .event-description h2, .event-description h3 {
            color: var(--primary);
            margin: 1.5rem 0 0.8rem;
        }

        /* ── Meta grid ── */
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 16px;
            margin: 24px 0;
        }
        .meta-item {
            background: var(--bg);
            border-radius: 8px;
            padding: 14px;
            border-left: 3px solid var(--accent);
        }
        .meta-item .meta-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .meta-item .meta-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text);
        }
        .meta-item .meta-icon {
            font-size: 1.2rem;
            margin-bottom: 6px;
        }

        /* ── Sidebar ── */
        .event-sidebar { position: sticky; top: 90px; }

        /* ── CTA Principal ── */
        .cta-card {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: var(--white);
            border-radius: var(--radius);
            padding: 24px;
            margin-bottom: 16px;
            text-align: center;
        }
        .cta-card h3 { font-size: 1.1rem; margin-bottom: 8px; }
        .cta-card p { font-size: 0.85rem; opacity: 0.85; margin-bottom: 16px; }
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
        .btn-white {
            background: var(--white);
            color: var(--primary);
        }
        .btn-white:hover {
            background: #f0f0f0;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .btn-outline-white {
            background: transparent;
            color: var(--white);
            border: 2px solid rgba(255,255,255,0.6);
            margin-top: 8px;
        }
        .btn-outline-white:hover {
            background: rgba(255,255,255,0.1);
            border-color: var(--white);
        }
        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }
        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(47,82,51,0.3);
        }
        .btn-accent {
            background: var(--accent-warm);
            color: #333;
        }
        .btn-accent:hover {
            background: #f0a000;
            transform: translateY(-1px);
        }

        /* ── Mapa ── */
        #event-map-container {
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            margin-bottom: 24px;
        }
        #event-map {
            height: 380px;
            width: 100%;
            background: #e8f0e8;
        }
        .map-placeholder {
            height: 380px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #e8f0e8, #d4e8d4);
            color: var(--primary);
            gap: 12px;
            cursor: pointer;
        }
        .map-placeholder .map-icon { font-size: 3rem; }
        .map-placeholder p { font-size: 0.9rem; color: var(--text-light); }
        .map-controls {
            background: var(--white);
            padding: 12px 16px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            border-top: 1px solid #eee;
        }
        .map-toggle-btn {
            padding: 6px 14px;
            border-radius: 20px;
            border: 1px solid #ddd;
            background: var(--white);
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 600;
        }
        .map-toggle-btn.active {
            background: var(--primary);
            color: var(--white);
            border-color: var(--primary);
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
        }
        .nearby-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-2px);
        }
        .nearby-card-img {
            height: 120px;
            background: #e8f0e8;
            overflow: hidden;
        }
        .nearby-card-img img {
            width: 100%; height: 100%;
            object-fit: cover;
        }
        .nearby-card-body { padding: 10px 12px; }
        .nearby-card-name {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .nearby-card-meta {
            font-size: 0.75rem;
            color: var(--text-light);
        }
        .nearby-card-price {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--primary);
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
        .nearby-show-more button:hover {
            background: var(--primary);
            color: var(--white);
        }

        /* ── Eventos similares ── */
        .similar-events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 16px;
        }
        .similar-event-card {
            border-radius: var(--radius);
            overflow: hidden;
            border: 1px solid #eee;
            background: var(--white);
            transition: all 0.2s;
        }
        .similar-event-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-3px);
        }
        .similar-event-img {
            height: 150px;
            background: linear-gradient(135deg, #e8f0e8, #c8dcc8);
            overflow: hidden;
            position: relative;
        }
        .similar-event-img img { width: 100%; height: 100%; object-fit: cover; }
        .similar-event-badge {
            position: absolute;
            top: 8px; right: 8px;
            background: var(--primary);
            color: var(--white);
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 700;
        }
        .similar-event-body { padding: 14px; }
        .similar-event-name {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 6px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .similar-event-meta {
            font-size: 0.78rem;
            color: var(--text-light);
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        /* ── Suscripción ── */
        .subscribe-card {
            background: linear-gradient(135deg, #fff8e1, #fff3cd);
            border: 1px solid #ffe082;
            border-radius: var(--radius);
            padding: 24px;
            text-align: center;
        }
        .subscribe-card h3 {
            color: #e65100;
            font-size: 1rem;
            margin-bottom: 8px;
        }
        .subscribe-card p {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-bottom: 16px;
        }
        .subscribe-form {
            display: flex;
            gap: 8px;
            flex-direction: column;
        }
        .subscribe-form input {
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.9rem;
            width: 100%;
        }
        .subscribe-form input:focus {
            outline: none;
            border-color: var(--accent-warm);
            box-shadow: 0 0 0 3px rgba(249,168,37,0.15);
        }

        /* ── Skeleton loading ── */
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
        .skeleton-text { height: 14px; margin-bottom: 8px; }
        .skeleton-text.short { width: 60%; }

        /* ── Lightbox ── */
        .lightbox-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.92);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        .lightbox-overlay.active { display: flex; }
        .lightbox-img {
            max-width: 90vw;
            max-height: 90vh;
            border-radius: 8px;
            object-fit: contain;
        }
        .lightbox-close {
            position: absolute;
            top: 20px; right: 24px;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            background: none;
            border: none;
            line-height: 1;
        }
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

        /* ── Responsive ── */
        @media (max-width: 600px) {
            .event-hero { padding: 40px 16px 60px; }
            .event-hero h1 { font-size: 1.5rem; }
            .event-hero-meta { gap: 10px; font-size: 0.85rem; }
            .card-body { padding: 16px; }
            .similar-events-grid { grid-template-columns: 1fr; }

            /* ── Meta-grid compacta en móvil ── */
            .meta-grid {
                grid-template-columns: 1fr;
                gap: 0;
                border: 1px solid #e8e8e8;
                border-radius: 8px;
                overflow: hidden;
            }
            .meta-item {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 10px 14px;
                border-left: none;
                border-bottom: 1px solid #f0f0f0;
                border-radius: 0;
                background: var(--white);
            }
            .meta-item:last-child { border-bottom: none; }
            .meta-item:nth-child(even) { background: #fafafa; }
            .meta-icon {
                font-size: 1rem;
                flex-shrink: 0;
                width: 22px;
                text-align: center;
            }
            .meta-item-text { flex: 1; display: flex; flex-direction: row; align-items: baseline; gap: 6px; flex-wrap: wrap; }
            .meta-label {
                font-size: 0.7rem;
                color: var(--text-light);
                text-transform: uppercase;
                letter-spacing: 0.3px;
                font-weight: 600;
                white-space: nowrap;
                margin-bottom: 0;
            }
            .meta-value {
                font-size: 0.85rem;
                font-weight: 600;
                color: var(--text);
            }
        }

        /* ── CTA barra fija móvil ── */
        #cta-mobile-bar {
            display: none; /* oculta en desktop */
        }
        @media (max-width: 900px) {
            /* Ocultar el CTA del sidebar en móvil (va abajo de todo el scroll) */
            #cta-register { display: none !important; }

            /* Mostrar barra fija inferior */
            #cta-mobile-bar {
                display: flex;
                position: fixed;
                bottom: 0; left: 0; right: 0;
                z-index: 500;
                background: linear-gradient(90deg, #1e4d22 0%, #2F5233 100%);
                padding: 10px 16px;
                align-items: center;
                gap: 10px;
                box-shadow: 0 -3px 16px rgba(0,0,0,0.25);
                /* Dejar espacio al navbar inferior si lo hubiera */
                padding-bottom: max(10px, env(safe-area-inset-bottom));
            }
            #cta-mobile-bar .cmb-text {
                flex: 1;
                color: #F9A825;
                font-size: 0.8rem;
                line-height: 1.2;
            }
            #cta-mobile-bar .cmb-text strong { display: block; font-size: 0.85rem; color: #F9A825; }
            #cta-mobile-bar .cmb-btn {
                background: #F9A825;
                color: #1a2e1a;
                border: none;
                border-radius: 8px;
                padding: 10px 18px;
                font-weight: 700;
                font-size: 0.88rem;
                cursor: pointer;
                white-space: nowrap;
                font-family: inherit;
                flex-shrink: 0;
            }
            #cta-mobile-bar .cmb-close {
                background: none;
                border: none;
                color: rgba(255,255,255,0.6);
                font-size: 1.2rem;
                cursor: pointer;
                padding: 4px;
                line-height: 1;
                flex-shrink: 0;
            }

            /* Mini-overlay del formulario CTA en móvil */
            #cta-mobile-overlay {
                display: none;
                position: fixed;
                inset: 0;
                z-index: 600;
                background: rgba(0,0,0,0.55);
                align-items: flex-end;
            }
            #cta-mobile-overlay.open {
                display: flex;
            }
            #cta-mobile-overlay .cmo-box {
                width: 100%;
                background: linear-gradient(160deg, #1e4d22 0%, #2F5233 100%);
                border-radius: 16px 16px 0 0;
                padding: 20px 20px max(20px, env(safe-area-inset-bottom));
                color: #fff;
            }
            #cta-mobile-overlay .cmo-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 14px;
            }
            #cta-mobile-overlay .cmo-title {
                font-size: 1rem;
                font-weight: 700;
            }
            #cta-mobile-overlay .cmo-close {
                background: none;
                border: none;
                color: rgba(255,255,255,0.7);
                font-size: 1.4rem;
                cursor: pointer;
                line-height: 1;
                padding: 4px;
            }

            /* Espacio al final del contenido para que la barra no tape nada */
            .site-footer { padding-bottom: 72px; }
        }
    </style>

    <!-- JSON-LD Schema.org -->
    <?php if ($jsonld): ?>
    <script type="application/ld+json"><?php echo $jsonld; ?></script>
    <?php endif; ?>

    <!-- Datos del evento para JS (evita segunda llamada API) -->
    <script>
        window.EVENTO_DATA = <?php echo $evento_js; ?>;
        window.EVENTO_SLUG = <?php echo json_encode($slug); ?>;
        window.EVENTO_LANG = <?php echo json_encode($lang); ?>;
        window.EVENTO_UI = <?php echo json_encode([
            // Etiquetas HTML (SSR fallback via JS)
            'sobre_evento'         => $t['sobre_evento'] ?? '',
            'info_evento'          => $t['info_evento'] ?? '',
            'fecha_inicio'         => $t['fecha_inicio'] ?? '',
            'fecha_fin'            => $t['fecha_fin'] ?? '',
            'ubicacion'            => $t['ubicacion'] ?? '',
            'direccion'            => $t['direccion'] ?? '',
            'categoria'            => $t['categoria'] ?? '',
            'precio'               => $t['precio'] ?? '',
            'organiza'             => $t['organiza'] ?? '',
            'ver_mapa'             => $t['ver_mapa'] ?? '',
            'click_mapa'           => $t['click_mapa'] ?? '',
            'btn_evento'           => $t['btn_evento'] ?? '',
            'btn_alojamientos'     => $t['btn_alojamientos'] ?? '',
            'btn_lugares'          => $t['btn_lugares'] ?? '',
            'btn_actividades'      => $t['btn_actividades'] ?? '',
            'aloj_cercanos'        => $t['aloj_cercanos'] ?? '',
            'ver_mas_aloj'         => $t['ver_mas_aloj'] ?? '',
            'lugares_cercanos'     => $t['lugares_cercanos'] ?? '',
            'ver_mas_lugares'      => $t['ver_mas_lugares'] ?? '',
            'activ_cercanas'       => $t['activ_cercanas'] ?? '',
            'ver_mas_activ'        => $t['ver_mas_activ'] ?? '',
            'eventos_similares'    => $t['eventos_similares'] ?? '',
            'cta_titulo'           => $t['cta_titulo'] ?? '',
            'cta_desc'             => $t['cta_desc'] ?? '',
            'cta_register'         => $t['cta_register'] ?? '',
            'cta_login'            => $t['cta_login'] ?? '',
            'visitas'              => $t['visitas'] ?? '',
            'likes'                => $t['likes'] ?? '',
            'guardar'              => $t['guardar'] ?? '',
            'anadir_ruta'          => $t['anadir_ruta'] ?? '',
            'suscripcion_titulo'   => $t['suscripcion_titulo'] ?? '',
            'suscripcion_desc'     => $t['suscripcion_desc'] ?? '',
            'suscripcion_en'       => $t['suscripcion_en'] ?? '',
            'suscripcion_btn'      => $t['suscripcion_btn'] ?? '',
            'programa'             => $t['programa'] ?? '',
            'publico'              => $t['publico'] ?? '',
            'accesibilidad'        => $t['accesibilidad'] ?? '',
            // JS labels
            'gratis'               => $t['gratis'] ?? '',
            'consultar'            => $t['consultar'] ?? '',
            'noche'                => $lang === 'es' ? '/noche' : ($lang === 'en' ? '/night' : ($lang === 'fr' ? '/nuit' : ($lang === 'de' ? '/Nacht' : '/晚'))),
            'persona'              => $lang === 'es' ? '/persona' : ($lang === 'en' ? '/person' : ($lang === 'fr' ? '/personne' : ($lang === 'de' ? '/Person' : '/人'))),
            'ver_mas'              => $lang === 'es' ? 'Ver más →' : ($lang === 'en' ? 'View more →' : ($lang === 'fr' ? 'Voir plus →' : ($lang === 'de' ? 'Mehr →' : '查看更多 →'))),
            'cargando_mapa'        => $lang === 'es' ? 'Cargando mapa...' : ($lang === 'en' ? 'Loading map...' : ($lang === 'fr' ? 'Chargement de la carte...' : ($lang === 'de' ? 'Karte wird geladen...' : '地图加载中...'))),
            'guardar_evento'       => $t['guardar'],
            'guardado'             => $lang === 'es' ? '✅ Guardado' : ($lang === 'en' ? '✅ Saved' : ($lang === 'fr' ? '✅ Sauvegardé' : ($lang === 'de' ? '✅ Gespeichert' : '✅ 已保存'))),
            'evento_guardado'      => $lang === 'es' ? '✅ Evento guardado correctamente' : ($lang === 'en' ? '✅ Event saved successfully' : ($lang === 'fr' ? '✅ Événement sauvegardé' : ($lang === 'de' ? '✅ Veranstaltung gespeichert' : '✅ 活动已保存'))),
            'evento_eliminado'     => $lang === 'es' ? 'Evento eliminado de guardados' : ($lang === 'en' ? 'Event removed from saved' : ($lang === 'fr' ? 'Événement supprimé des favoris' : ($lang === 'de' ? 'Veranstaltung aus Gespeicherten entfernt' : '活动已从收藏中删除'))),
            'ya_en_ruta'           => $lang === 'es' ? 'Este evento ya está en tu ruta' : ($lang === 'en' ? 'This event is already in your route' : ($lang === 'fr' ? 'Cet événement est déjà dans votre itinéraire' : ($lang === 'de' ? 'Diese Veranstaltung ist bereits in Ihrer Route' : '此活动已在您的路线中'))),
            'inicia_sesion'        => $lang === 'es' ? 'Inicia sesión para añadir a tu ruta' : ($lang === 'en' ? 'Log in to add to your route' : ($lang === 'fr' ? 'Connectez-vous pour ajouter à votre itinéraire' : ($lang === 'de' ? 'Melden Sie sich an, um zur Route hinzuzufügen' : '请登录以添加到您的路线'))),
            'anadido_ruta'         => $t['anadir_ruta'],
            'suscripcion_ok'       => $lang === 'es' ? '🔔 ¡Suscripción confirmada!' : ($lang === 'en' ? '🔔 Subscription confirmed!' : ($lang === 'fr' ? '🔔 Abonnement confirmé !' : ($lang === 'de' ? '🔔 Abonnement bestätigt!' : '🔔 订阅已确认！'))),
            'suscripcion_ok_h3'    => $lang === 'es' ? '¡Suscripción confirmada!' : ($lang === 'en' ? 'Subscription confirmed!' : ($lang === 'fr' ? 'Abonnement confirmé !' : ($lang === 'de' ? 'Abonnement bestätigt!' : '订阅已确认！'))),
            'suscripcion_ok_p1'    => $lang === 'es' ? 'Te avisaremos de eventos de' : ($lang === 'en' ? 'We will notify you of events of' : ($lang === 'fr' ? 'Nous vous informerons des événements de' : ($lang === 'de' ? 'Wir benachrichtigen Sie über Veranstaltungen von' : '我们将通知您以下类型的活动：'))),
            'suscripcion_ok_p2'    => $lang === 'es' ? 'en' : ($lang === 'en' ? 'in' : ($lang === 'fr' ? 'à' : ($lang === 'de' ? 'in' : '地区：'))),
            'esta_categoria'       => $lang === 'es' ? 'esta categoría' : ($lang === 'en' ? 'this category' : ($lang === 'fr' ? 'cette catégorie' : ($lang === 'de' ? 'dieser Kategorie' : '此类别'))),
            'tu_zona'              => $lang === 'es' ? 'tu zona' : ($lang === 'en' ? 'your area' : ($lang === 'fr' ? 'votre région' : ($lang === 'de' ? 'Ihrer Region' : '您的地区'))),
            'error_suscripcion'    => $lang === 'es' ? 'Error al suscribirse. Inténtalo de nuevo.' : ($lang === 'en' ? 'Error subscribing. Please try again.' : ($lang === 'fr' ? 'Erreur lors de l\'abonnement. Réessayez.' : ($lang === 'de' ? 'Fehler beim Abonnieren. Bitte erneut versuchen.' : '订阅出错，请重试。'))),
            'enviando'             => $lang === 'es' ? '⏳ Enviando...' : ($lang === 'en' ? '⏳ Sending...' : ($lang === 'fr' ? '⏳ Envoi...' : ($lang === 'de' ? '⏳ Senden...' : '⏳ 发送中...'))),
            'enlace_copiado'       => $lang === 'es' ? '🔗 Enlace copiado' : ($lang === 'en' ? '🔗 Link copied' : ($lang === 'fr' ? '🔗 Lien copié' : ($lang === 'de' ? '🔗 Link kopiert' : '🔗 链接已复制'))),
            'error_copiar'         => $lang === 'es' ? 'No se pudo copiar el enlace' : ($lang === 'en' ? 'Could not copy the link' : ($lang === 'fr' ? 'Impossible de copier le lien' : ($lang === 'de' ? 'Link konnte nicht kopiert werden' : '无法复制链接'))),
            'te_gusta'             => $lang === 'es' ? '❤️ ¡Te gusta este evento!' : ($lang === 'en' ? '❤️ You like this event!' : ($lang === 'fr' ? '❤️ Vous aimez cet événement !' : ($lang === 'de' ? '❤️ Ihnen gefällt diese Veranstaltung!' : '❤️ 您喜欢此活动！'))),
            'me_gusta_eliminado'   => $lang === 'es' ? '🤍 Me gusta eliminado' : ($lang === 'en' ? '🤍 Like removed' : ($lang === 'fr' ? '🤍 J\'aime supprimé' : ($lang === 'de' ? '🤍 Gefällt mir entfernt' : '🤍 已取消点赞'))),
            'locale'               => $lang === 'zh' ? 'zh-CN' : ($lang === 'de' ? 'de-DE' : ($lang === 'fr' ? 'fr-FR' : ($lang === 'en' ? 'en-GB' : 'es-ES'))),
        ], JSON_UNESCAPED_UNICODE); ?>
    </script>
</head>
<body>

<!-- ── HEADER (compatible con el existente) ── -->
<?php
// Intentar incluir el header existente.
// HEADER_NO_HTML_HEAD le indica a header.php que omita el bloque
// <!DOCTYPE html>...<head>...</head><body> (ya generado arriba)
// y solo añada el <header> de navegación, evitando así los duplicados
// de <title>, <meta description> y <link canonical> que reporta Bing.
$header_path = __DIR__ . '/header.php';
if (file_exists($header_path)) {
    define('HEADER_NO_HTML_HEAD', true);
    include $header_path;
} else {
    // Header ligero de fallback
    $ev_link = $lang !== 'es' ? "/{$lang}/eventos-culturales-paginacion.html" : '/eventos-culturales-paginacion.html';
    echo '<header class="site-header" style="display:flex;align-items:center;padding:0 20px;gap:16px;">
        <a href="/" style="font-weight:700;color:var(--primary);font-size:1.1rem;text-decoration:none;">🌿 Rutas Rurales</a>
        <nav style="margin-left:auto;display:flex;gap:16px;font-size:0.9rem;">
            <a href="' . $ev_link . '" style="color:var(--text);">' . $t['eventos_link'] . '</a>
            <a href="/alojamientos-turisticos.html" style="color:var(--text);">' . $t['aloj_link'] . '</a>
            <a href="/login.html" style="color:var(--primary);font-weight:700;">' . $t['acceder_link'] . '</a>
        </nav>
    </header>';
}
?>

<!-- ── HERO ── -->
<?php if ($evento): ?>
<section class="event-hero<?php echo $hero_bg_url ? ' has-hero-img' : ''; ?>"<?php echo $hero_bg_url ? ' style="background-image:url(\'' . htmlspecialchars($hero_bg_url, ENT_QUOTES) . '\')"' : ''; ?>>
    <div class="event-hero-badge"><?php echo htmlspecialchars($categoria_nombre); ?></div>
    <h1><?php echo htmlspecialchars($evento['titulo']); ?></h1>
    <div class="event-hero-meta">
        <?php if ($fecha_display): ?>
        <span><span class="icon">📅</span> <?php echo htmlspecialchars($fecha_display); ?></span>
        <?php endif; ?>
        <?php if ($ubicacion_display): ?>
        <span><span class="icon">📍</span> <?php echo htmlspecialchars($ubicacion_display); ?></span>
        <?php endif; ?>
        <?php if ($precio_display): ?>
        <span><span class="icon">🎟️</span> <?php echo htmlspecialchars($precio_display); ?></span>
        <?php endif; ?>
    </div>

    <!-- Botón compartir (móvil: Web Share API, desktop: clipboard) -->
    <div class="event-hero__share">
        <button type="button" class="event-share-btn" id="btnCompartir"
                aria-label="<?php echo ($t['share_btn'] ?? 'Compartir esta página'); ?>"
                onclick="compartirPagina(this)">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/>
                <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
            </svg>
            <span><?php echo ($t['share_btn'] ?? 'Compartir esta página'); ?></span>
        </button>
    </div>

    <style>
    .event-hero__share{margin-top:16px}
    .event-share-btn{display:inline-flex;align-items:center;gap:8px;
      background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.25);
      color:#fff;padding:8px 18px;border-radius:24px;font-size:.82rem;font-weight:600;
      cursor:pointer;transition:background .18s ease,transform .12s ease;
      font-family:inherit;line-height:1.4;backdrop-filter:blur(4px)}
    .event-share-btn:hover,.event-share-btn:focus-visible{background:rgba(255,255,255,.22);outline:none}
    .event-share-btn:active{transform:scale(.96)}
    .event-share-btn--copied{background:rgba(129,199,132,.25);border-color:var(--accent)}
    @media(max-width:480px){.event-share-btn{width:100%;justify-content:center;padding:10px 18px;font-size:.88rem}}
    </style>

    <script>
    function compartirPagina(btn){
      var url = window.location.href;
      var title = '<?php echo htmlspecialchars($t['share_title'] ?? '¡Mira este evento!', ENT_QUOTES); ?>';
      if(navigator.share){
        navigator.share({title:title,url:url}).catch(function(){});
      }else{
        if(navigator.clipboard && navigator.clipboard.writeText){
          navigator.clipboard.writeText(url).then(function(){
            var span = btn.querySelector('span');
            var orig = span.textContent;
            span.textContent = '<?php echo htmlspecialchars($t['share_copy'] ?? 'Enlace copiado ✓', ENT_QUOTES); ?>';
            btn.classList.add('event-share-btn--copied');
            setTimeout(function(){
              span.textContent = orig;
              btn.classList.remove('event-share-btn--copied');
            },2500);
          }).catch(function(){});
        }else{
          // Fallback: seleccionar la URL manualmente
          var input = document.createElement('input');
          input.value = url;
          document.body.appendChild(input);
          input.select();
          document.execCommand('copy');
          document.body.removeChild(input);
          var span = btn.querySelector('span');
          var orig = span.textContent;
          span.textContent = '<?php echo htmlspecialchars($t['share_copy'] ?? 'Enlace copiado ✓', ENT_QUOTES); ?>';
          btn.classList.add('event-share-btn--copied');
          setTimeout(function(){
            span.textContent = orig;
            btn.classList.remove('event-share-btn--copied');
          },2500);
        }
      }
    }
    </script>

</section>

<!-- ── LAYOUT PRINCIPAL ── -->
<div class="event-layout">

    <!-- ── COLUMNA PRINCIPAL ── -->
    <main>

        <!-- Galería de fotos -->
        <?php if (!empty($fotos)): ?>
        <div class="card" style="margin-bottom:24px;">
            <div class="card-body">
                <div class="event-gallery" id="event-gallery">
                    <?php foreach ($fotos as $i => $foto): ?>
                    <div class="gallery-item" onclick="openLightbox(<?php echo $i; ?>)">
                        <img src="<?php echo htmlspecialchars($foto); ?>"
                             alt="Foto <?php echo $i+1; ?> de <?php echo htmlspecialchars($evento['titulo']); ?>"
                             loading="<?php echo $i === 0 ? 'eager' : 'lazy'; ?>"
                             width="400" height="300">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Descripción del evento -->
        <div class="card" style="margin-bottom:24px;">
            <div class="card-body">
                <h2 class="card-title"><?php echo ($t['sobre_evento'] ?? ''); ?></h2>
                <div class="event-description">
                    <?php
                    // Usar description_linked (inbound links ya insertados en BD, SSR puro → cero impacto en velocidad)
                    // Fallback a description si aún no se ha regenerado (contenido previo al sistema)
                    $desc_html = !empty($evento['description_linked'])
                        ? $evento['description_linked']
                        : $evento['description'];

                    // ── Normalizar rutas de imágenes ───────────────────────────────────────
                    // Las rutas relativas en <img src="..."> se rompen en URLs multiidioma
                    // (ej: /de/evento/slug resuelve src="equipajes3.webp" como /de/evento/equipajes3.webp)
                    // Convertimos rutas que no empiecen por / o http(s) a rutas absolutas con /
                    $desc_html = preg_replace(
                        '/<img\s+([^>]*?)src\s*=\s*"((?!https?:\/\/|\/|data:)[^"]+)"/i',
                        '<img $1src="/$2"',
                        $desc_html
                    );

                    echo $desc_html;
                    ?>
                </div>

                <!-- Info adicional: programa, público, accesibilidad -->
                <?php
                $programa = $evento['programa'] ?? '';
                $audiencia = $evento['audiencia'] ?? '';
                $accesibilidad = $evento['accesibilidad'] ?? '';
                if ($programa || $audiencia || $accesibilidad):
                ?>
                <div style="margin-top:24px;display:flex;flex-direction:column;gap:16px;">
                    <?php if ($programa): ?>
                    <div style="background:var(--bg);padding:20px;border-radius:8px;border-left:3px solid var(--accent);">
                        <h4 style="color:var(--primary);margin-bottom:12px;font-size:1rem;"><?php echo ($t['programa'] ?? ''); ?></h4>
                        <div class="event-description" style="font-size:0.9rem;"><?php echo $programa; ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($audiencia): ?>
                    <div style="background:var(--bg);padding:20px;border-radius:8px;border-left:3px solid var(--accent);">
                        <h4 style="color:var(--primary);margin-bottom:12px;font-size:1rem;"><?php echo ($t['publico'] ?? ''); ?></h4>
                        <div class="event-description" style="font-size:0.9rem;"><?php echo $audiencia; ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($accesibilidad): ?>
                    <div style="background:var(--bg);padding:20px;border-radius:8px;border-left:3px solid var(--accent);">
                        <h4 style="color:var(--primary);margin-bottom:12px;font-size:1rem;"><?php echo ($t['accesibilidad'] ?? ''); ?></h4>
                        <div class="event-description" style="font-size:0.9rem;"><?php echo $accesibilidad; ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Meta información -->
        <div class="card" style="margin-bottom:24px;">
            <div class="card-body">
                <h2 class="card-title"><?php echo ($t['info_evento'] ?? ''); ?></h2>
                <div class="meta-grid">
                    <div class="meta-item">
                        <div class="meta-icon">📅</div>
                        <div class="meta-label"><?php echo ($t['fecha_inicio'] ?? ''); ?></div>
                        <div class="meta-value"><?php echo date('d/m/Y', strtotime($evento['start_date'])); ?></div>
                    </div>
                    <?php if (!empty($evento['end_date']) && $evento['end_date'] !== $evento['start_date']): ?>
                    <div class="meta-item">
                        <div class="meta-icon">🏁</div>
                        <div class="meta-label"><?php echo ($t['fecha_fin'] ?? ''); ?></div>
                        <div class="meta-value"><?php echo date('d/m/Y', strtotime($evento['end_date'])); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($ubicacion_display): ?>
                    <div class="meta-item">
                        <div class="meta-icon">📍</div>
                        <div class="meta-label"><?php echo ($t['ubicacion'] ?? ''); ?></div>
                        <div class="meta-value"><?php echo htmlspecialchars($ubicacion_display); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($evento['venue_address'])): ?>
                    <div class="meta-item">
                        <div class="meta-icon">🗺️</div>
                        <div class="meta-label"><?php echo ($t['direccion'] ?? ''); ?></div>
                        <div class="meta-value"><?php echo htmlspecialchars($evento['venue_address']); ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="meta-item">
                        <div class="meta-icon">🏷️</div>
                        <div class="meta-label"><?php echo ($t['categoria'] ?? ''); ?></div>
                        <div class="meta-value"><?php echo htmlspecialchars($categoria_nombre); ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-icon">🎟️</div>
                        <div class="meta-label"><?php echo ($t['precio'] ?? ''); ?></div>
                        <div class="meta-value"><?php echo htmlspecialchars($precio_display); ?></div>
                    </div>
                    <?php if (!empty($evento['organizer'])): ?>
                    <div class="meta-item">
                        <div class="meta-icon">🏛️</div>
                        <div class="meta-label"><?php echo ($t['organiza'] ?? ''); ?></div>
                        <div class="meta-value"><?php echo htmlspecialchars($evento['organizer']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- MAPA (carga diferida con Leaflet) -->
        <?php if (!empty($evento['latitude']) && !empty($evento['longitude'])): ?>
        <div id="event-map-container" class="card" style="margin-bottom:24px;">
            <div id="map-placeholder" class="map-placeholder" onclick="initMap()">
                <div class="map-icon">🗺️</div>
                <strong style="font-size:1rem;"><?php echo ($t['ver_mapa'] ?? ''); ?></strong>
                <p><?php echo ($t['click_mapa'] ?? ''); ?></p>
            </div>
            <div id="event-map" style="display:none;"></div>
            <div class="map-controls" id="map-controls" style="display:none;">
                <button class="map-toggle-btn active" id="btn-evento" onclick="toggleMapLayer('evento')"><?php echo ($t['btn_evento'] ?? ''); ?></button>
                <button class="map-toggle-btn" id="btn-alojamientos" onclick="toggleMapLayer('alojamientos')"><?php echo ($t['btn_alojamientos'] ?? ''); ?></button>
                <button class="map-toggle-btn" id="btn-lugares" onclick="toggleMapLayer('lugares')"><?php echo ($t['btn_lugares'] ?? ''); ?></button>
                <button class="map-toggle-btn" id="btn-actividades" onclick="toggleMapLayer('actividades')"><?php echo ($t['btn_actividades'] ?? ''); ?></button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Contenido cercano (carga diferida) -->
        <div id="nearby-section" class="card" style="margin-bottom:24px;display:none;">
            <div class="card-body">
                <h2 class="card-title"><?php echo ($t['aloj_cercanos'] ?? ''); ?></h2>
                <div id="nearby-alojamientos" class="nearby-grid">
                    <!-- Skeleton loading -->
                    <div class="skeleton skeleton-card"></div>
                    <div class="skeleton skeleton-card"></div>
                    <div class="skeleton skeleton-card"></div>
                </div>
                <div class="nearby-show-more" id="more-alojamientos" style="display:none;">
                    <button onclick="showMoreNearby('alojamientos')"><?php echo ($t['ver_mas_aloj'] ?? ''); ?></button>
                </div>
            </div>
        </div>

        <div id="nearby-lugares-section" class="card" style="margin-bottom:24px;display:none;">
            <div class="card-body">
                <h2 class="card-title"><?php echo ($t['lugares_cercanos'] ?? ''); ?></h2>
                <div id="nearby-lugares" class="nearby-grid">
                    <div class="skeleton skeleton-card"></div>
                    <div class="skeleton skeleton-card"></div>
                    <div class="skeleton skeleton-card"></div>
                </div>
                <div class="nearby-show-more" id="more-lugares" style="display:none;">
                    <button onclick="showMoreNearby('lugares')"><?php echo ($t['ver_mas_lugares'] ?? ''); ?></button>
                </div>
            </div>
        </div>

        <!-- Actividades turísticas cercanas (carga diferida) -->
        <div id="nearby-actividades-section" class="card" style="margin-bottom:24px;display:none;">
            <div class="card-body">
                <h2 class="card-title"><?php echo ($t['activ_cercanas'] ?? ''); ?></h2>
                <div id="nearby-actividades" class="nearby-grid">
                    <div class="skeleton skeleton-card"></div>
                    <div class="skeleton skeleton-card"></div>
                    <div class="skeleton skeleton-card"></div>
                </div>
                <div class="nearby-show-more" id="more-actividades" style="display:none;">
                    <button onclick="showMoreNearby('actividades')"><?php echo ($t['ver_mas_activ'] ?? ''); ?></button>
                </div>
            </div>
        </div>

        <!-- Eventos similares (carga diferida) -->
        <div id="similar-section" class="card" style="margin-bottom:24px;display:none;">
            <div class="card-body">
                <h2 class="card-title"><?php echo ($t['eventos_similares'] ?? ''); ?></h2>
                <div id="similar-events" class="similar-events-grid">
                    <div class="skeleton skeleton-card"></div>
                    <div class="skeleton skeleton-card"></div>
                    <div class="skeleton skeleton-card"></div>
                </div>
            </div>
        </div>

    </main>

    <!-- ── SIDEBAR ── -->
    <aside class="event-sidebar">

        <!-- CTA Principal: Captura de turistas -->
        <?php
        // Textos multiidioma del CTA de conversión
        $cta_i18n = [
            'es' => [
                'titulo'    => '🔔 Avísame cuando sea el evento',
                'subtitulo' => 'Recibe recordatorios y eventos similares en',
                'social'    => 'Prueba social:',
                'placeholder'=> 'tu@email.com',
                'btn_email' => 'Me apunto →',
                'ya_cuenta' => 'Ya tengo cuenta →',
                'divider'   => 'o regístrate completo',
                'btn_reg'   => '✨ Crear cuenta gratis',
                'ok_title'  => '¡Apuntado! 🎉',
                'ok_msg'    => 'Te avisamos antes del evento y cuando haya eventos similares.',
                'visitas_txt'=> 'personas han visto este evento',
            ],
            'en' => [
                'titulo'    => '🔔 Remind me about this event',
                'subtitulo' => 'Get reminders and similar events in',
                'social'    => 'Social proof:',
                'placeholder'=> 'your@email.com',
                'btn_email' => 'Notify me →',
                'ya_cuenta' => 'I already have an account →',
                'divider'   => 'or create full account',
                'btn_reg'   => '✨ Sign up free',
                'ok_title'  => 'You\'re in! 🎉',
                'ok_msg'    => 'We\'ll remind you before the event and notify you of similar ones.',
                'visitas_txt'=> 'people have viewed this event',
            ],
            'fr' => [
                'titulo'    => '🔔 Me rappeler cet événement',
                'subtitulo' => 'Recevez des rappels et événements similaires à',
                'social'    => 'Preuve sociale :',
                'placeholder'=> 'vous@email.com',
                'btn_email' => 'M\'inscrire →',
                'ya_cuenta' => 'J\'ai déjà un compte →',
                'divider'   => 'ou créer un compte complet',
                'btn_reg'   => '✨ Inscription gratuite',
                'ok_title'  => 'C\'est noté ! 🎉',
                'ok_msg'    => 'Nous vous rappellerons cet événement et vous informerons des similaires.',
                'visitas_txt'=> 'personnes ont consulté cet événement',
            ],
            'de' => [
                'titulo'    => '🔔 Erinnerung für dieses Event',
                'subtitulo' => 'Erhalte Erinnerungen und ähnliche Events in',
                'social'    => 'Soziale Bestätigung:',
                'placeholder'=> 'ihre@email.com',
                'btn_email' => 'Benachrichtigen →',
                'ya_cuenta' => 'Ich habe bereits ein Konto →',
                'divider'   => 'oder vollständig registrieren',
                'btn_reg'   => '✨ Kostenlos registrieren',
                'ok_title'  => 'Eingetragen! 🎉',
                'ok_msg'    => 'Wir erinnern Sie vor dem Event und informieren über ähnliche.',
                'visitas_txt'=> 'Personen haben dieses Event angesehen',
            ],
            'zh' => [
                'titulo'    => '🔔 提醒我参加此活动',
                'subtitulo' => '接收提醒和类似活动，地区：',
                'social'    => '社交证明：',
                'placeholder'=> '您的@邮箱.com',
                'btn_email' => '提醒我 →',
                'ya_cuenta' => '已有账户 →',
                'divider'   => '或完整注册',
                'btn_reg'   => '✨ 免费注册',
                'ok_title'  => '已登记！🎉',
                'ok_msg'    => '我们将在活动前提醒您，并通知您类似活动。',
                'visitas_txt'=> '人查看了此活动',
            ],
        ];
        $c = $cta_i18n[$lang] ?? $cta_i18n['es'];
        $lang_prefix_cta = $lang !== 'es' ? '/' . $lang : '';
        ?>
        <div id="cta-register" style="
            background: linear-gradient(145deg, #1e4d22 0%, #2F5233 60%, #3a6b3f 100%);
            border-radius: var(--radius);
            padding: 22px 20px 18px;
            margin-bottom: 16px;
            color: #fff;
            box-shadow: 0 6px 24px rgba(47,82,51,0.35);
        ">
            <!-- Encabezado con urgencia -->
            <div style="font-size:1rem;font-weight:700;margin-bottom:6px;line-height:1.3;">
                <?php echo $c['titulo']; ?>
            </div>
            <div style="font-size:0.8rem;opacity:0.82;margin-bottom:14px;">
                <?php echo $c['subtitulo']; ?> <strong><?php echo htmlspecialchars($evento['province'] ?? ''); ?></strong>
            </div>

            <!-- Prueba social dinámica -->
            <div id="cta-social-proof" style="
                font-size:0.75rem;background:rgba(255,255,255,0.1);
                border-radius:6px;padding:6px 10px;margin-bottom:14px;
                display:flex;align-items:center;gap:6px;
            ">
                <span style="font-size:1rem;">👀</span>
                <span id="cta-view-count" style="font-weight:700;">—</span>
                <span style="opacity:0.8;"><?php echo $c['visitas_txt']; ?></span>
            </div>

            <!-- Formulario inline: solo email -->
            <div id="cta-form-wrap" class="cta-form-container">
                <form id="cta-quick-form" onsubmit="ctaQuickRegister(event)" class="cta-quick-form-inner">
                    <input type="email" id="cta-email" placeholder="<?php echo htmlspecialchars($c['placeholder']); ?>"
                        required class="cta-email-input">
                    <button type="submit" class="cta-submit-btn"><?php echo $c['btn_email']; ?></button>
                </form>

                <!-- Separador -->
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;opacity:0.6;">
                    <div style="flex:1;height:1px;background:rgba(255,255,255,0.3);"></div>
                    <span style="font-size:0.7rem;white-space:nowrap;"><?php echo $c['divider']; ?></span>
                    <div style="flex:1;height:1px;background:rgba(255,255,255,0.3);"></div>
                </div>

                <!-- Botón registro completo -->
                <a href="<?php echo $lang_prefix_cta; ?>/register.html?ref=evento&slug=<?php echo urlencode($slug); ?>"
                   style="display:block;text-align:center;background:rgba(255,255,255,0.12);
                          border:1.5px solid rgba(255,255,255,0.35);color:#fff;border-radius:7px;
                          padding:9px;font-size:0.83rem;font-weight:600;text-decoration:none;
                          transition:background 0.2s;margin-bottom:6px;"
                   onmouseover="this.style.background='rgba(255,255,255,0.2)'"
                   onmouseout="this.style.background='rgba(255,255,255,0.12)'">
                    <?php echo $c['btn_reg']; ?>
                </a>

                <!-- Ya tengo cuenta -->
                <a href="<?php echo $lang_prefix_cta; ?>/login.html?ref=evento&slug=<?php echo urlencode($slug); ?>"
                   style="display:block;text-align:center;color:rgba(255,255,255,0.65);
                          font-size:0.78rem;text-decoration:none;padding:4px;">
                    <?php echo $c['ya_cuenta']; ?>
                </a>
            </div>

            <!-- Estado: confirmación tras envío -->
            <div id="cta-ok" style="display:none;text-align:center;padding:8px 0;">
                <div style="font-size:1.6rem;margin-bottom:6px;">🎉</div>
                <div style="font-weight:700;font-size:0.95rem;margin-bottom:4px;"><?php echo $c['ok_title']; ?></div>
                <div style="font-size:0.8rem;opacity:0.85;"><?php echo $c['ok_msg']; ?></div>
            </div>
        </div>

        <style>
        /* Estilos base del formulario para desacoplar de inline */
        .cta-quick-form-inner { display: flex; gap: 6px; margin-bottom: 10px; }
        .cta-email-input { flex: 1; padding: 10px 12px; border: none; border-radius: 7px; font-size: 0.88rem; outline: none; font-family: inherit; background: #fff; color: #333; min-width: 0; }
        .cta-submit-btn { background: #F9A825; color: #1a2e1a; border: none; border-radius: 7px; padding: 10px 14px; font-weight: 700; font-size: 0.85rem; cursor: pointer; white-space: nowrap; transition: background 0.2s; font-family: inherit; }
        
        /* Estilos de :focus y :hover */
        #cta-email:focus { box-shadow: 0 0 0 3px rgba(249,168,37,0.5); }
        .cta-submit-btn:hover { background: #e69800 !important; }
        </style>
        
        <!-- FIX DEFINITIVO: Ajuste del formulario de email en PC para que no se descuadre -->
        <style>
        @media (min-width: 900px) {
            .cta-quick-form-inner { flex-wrap: wrap; }
            .cta-email-input { flex-basis: 100%; margin-bottom: 8px; }
            .cta-submit-btn { flex-grow: 1; }
        }
        </style>

        <script>
        // ── Función compartida: guarda email en event_newsletter_subscribers ──
        function ctaSaveEmail(email, formEl, wrapId, okId) {
            var btn = formEl.querySelector('button[type="submit"]');
            if (btn) { btn.disabled = true; btn.textContent = '⏳'; }
            fetch('/api/subscribe-events.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    email: email,
                    categoria: <?php echo json_encode($categoria_nombre); ?>,
                    province: <?php echo json_encode($evento['province'] ?? ''); ?>,
                    source_slug: <?php echo json_encode($slug); ?>,
                    source_event_id: <?php echo json_encode((int)($evento['id'] ?? 0)); ?>
                })
            })
            .then(function(r){ return r.json(); })
            .then(function(data){
                // Mostrar confirmación en el mismo sitio
                var wrap = document.getElementById(wrapId);
                var ok   = document.getElementById(okId);
                if (wrap) wrap.style.display = 'none';
                if (ok)   ok.style.display   = 'block';
            })
            .catch(function(){
                // Si falla la red, mostrar igual la confirmación (UX primero)
                var wrap = document.getElementById(wrapId);
                var ok   = document.getElementById(okId);
                if (wrap) wrap.style.display = 'none';
                if (ok)   ok.style.display   = 'block';
            });
        }

        function ctaQuickRegister(e) {
            e.preventDefault();
            var email = document.getElementById('cta-email').value.trim();
            if (!email) return;
            ctaSaveEmail(email, e.target.closest('form') || e.target, 'cta-form-wrap', 'cta-ok');
        }

        // Sincronizar contador de visitas con el del sidebar principal
        document.addEventListener('DOMContentLoaded', function() {
            var vc = document.getElementById('view-count');
            var ctaVc = document.getElementById('cta-view-count');
            if (vc && ctaVc) {
                var obs = new MutationObserver(function() {
                    if (vc.textContent && vc.textContent !== '—') ctaVc.textContent = vc.textContent;
                });
                obs.observe(vc, {childList:true, characterData:true, subtree:true});
                if (vc.textContent && vc.textContent !== '—') ctaVc.textContent = vc.textContent;
            }
        });
        </script>

        <!-- Visitas y Likes -->
        <div class="card" style="margin-bottom:16px;">
            <div class="card-body" style="text-align:center;">
                <div style="display:flex;justify-content:center;gap:24px;margin-bottom:16px;">
                    <div style="text-align:center;">
                        <div style="font-size:1.5rem;font-weight:700;color:var(--primary);" id="view-count">—</div>
                        <div style="font-size:0.75rem;color:var(--text-light);text-transform:uppercase;letter-spacing:0.5px;"><?php echo ($t['visitas'] ?? ''); ?></div>
                    </div>
                    <div style="width:1px;background:#eee;"></div>
                    <div style="text-align:center;">
                        <button id="btn-like" onclick="toggleLike()" style="background:none;border:none;cursor:pointer;font-size:1.8rem;line-height:1;transition:transform 0.2s;">🤍</button>
                        <div style="font-size:0.75rem;color:var(--text-light);text-transform:uppercase;letter-spacing:0.5px;"><span id="like-count">—</span> <?php echo ($t['likes'] ?? ''); ?></div>
                    </div>
                </div>
                <div style="margin-top:12px;display:flex;justify-content:center;gap:12px;">
                    <button onclick="shareEvent('whatsapp')" style="background:none;border:none;cursor:pointer;font-size:1.4rem;" title="Compartir en WhatsApp">💬</button>
                    <button onclick="shareEvent('twitter')" style="background:none;border:none;cursor:pointer;font-size:1.4rem;" title="Compartir en Twitter">🐦</button>
                    <button onclick="shareEvent('facebook')" style="background:none;border:none;cursor:pointer;font-size:1.4rem;" title="Compartir en Facebook">📘</button>
                    <button onclick="shareEvent('copy')" style="background:none;border:none;cursor:pointer;font-size:1.4rem;" title="Copiar enlace">🔗</button>
                </div>
            </div>
        </div>

        <!-- Suscripción a eventos similares -->
        <div class="subscribe-card" id="subscribe-card">
            <div style="font-size:1.8rem;margin-bottom:8px;">🔔</div>
            <h3><?php echo ($t['suscripcion_titulo'] ?? ''); ?></h3>
            <p><?php echo ($t['suscripcion_desc'] ?? ''); ?> <strong><?php echo htmlspecialchars($categoria_nombre); ?></strong> <?php echo ($t['suscripcion_en'] ?? ''); ?> <?php echo htmlspecialchars($evento['province'] ?? ''); ?></p>
            <form class="subscribe-form" onsubmit="subscribeEvents(event)">
                <input type="email" placeholder="tu@email.com" required id="subscribe-email">
                <button type="submit" class="btn btn-accent"><?php echo ($t['suscripcion_btn'] ?? ''); ?></button>
            </form>
        </div>

    </aside>

</div><!-- /.event-layout -->

<?php else: ?>
<!-- Evento no encontrado -->
<div style="max-width:600px;margin:120px auto 60px;text-align:center;padding:40px;">
    <div style="font-size:4rem;margin-bottom:16px;">😕</div>
    <h1 style="color:var(--primary);margin-bottom:12px;"><?php echo ($t['no_encontrado_h1'] ?? ''); ?></h1>
    <p style="color:var(--text-light);margin-bottom:24px;"><?php echo ($t['no_encontrado_p'] ?? ''); ?></p>
    <a href="/eventos-culturales-paginacion.html" class="btn btn-primary" style="display:inline-flex;width:auto;"><?php echo ($t['ver_todos'] ?? ''); ?></a>
</div>
<?php endif; ?>

<!-- ── LIGHTBOX ── -->
<div class="lightbox-overlay" id="lightbox" onclick="closeLightboxOnOverlay(event)">
    <button class="lightbox-close" onclick="closeLightbox()">✕</button>
    <button class="lightbox-nav lightbox-prev" onclick="lightboxNav(-1)">‹</button>
    <img class="lightbox-img" id="lightbox-img" src="" alt="">
    <button class="lightbox-nav lightbox-next" onclick="lightboxNav(1)">›</button>
</div>

<!-- ── TOAST ── -->
<div class="toast" id="toast"></div>

<!-- ── CTA MÓVIL: barra fija inferior + overlay ── -->
<?php if ($evento): ?>

<!-- Barra fija que aparece en la parte inferior en móvil -->
<div id="cta-mobile-bar" role="complementary" aria-label="Registro evento">
    <div class="cmb-text">
        <strong>🔔 <?php echo $c['titulo']; ?></strong>
        <?php echo $c['subtitulo']; ?> <strong><?php echo htmlspecialchars($evento['province'] ?? ''); ?></strong>
    </div>
    <button class="cmb-btn" onclick="document.getElementById('cta-mobile-overlay').classList.add('open');document.body.style.overflow='hidden';">
        <?php echo $c['btn_email']; ?>
    </button>
    <button class="cmb-close" onclick="document.getElementById('cta-mobile-bar').style.display='none';" aria-label="Cerrar">✕</button>
</div>

<!-- Mini-overlay (bottom sheet) con el formulario completo -->
<div id="cta-mobile-overlay" role="dialog" aria-modal="true" aria-label="Formulario de registro"
     onclick="if(event.target===this){this.classList.remove('open');document.body.style.overflow='';}">
    <div class="cmo-box">
        <div class="cmo-header">
            <span class="cmo-title"><?php echo $c['titulo']; ?></span>
            <button class="cmo-close" onclick="document.getElementById('cta-mobile-overlay').classList.remove('open');document.body.style.overflow='';" aria-label="Cerrar">✕</button>
        </div>
        <div style="font-size:0.8rem;opacity:0.8;margin-bottom:14px;">
            <?php echo $c['subtitulo']; ?> <strong><?php echo htmlspecialchars($evento['province'] ?? ''); ?></strong>
        </div>
        <!-- Formulario email -->
        <form onsubmit="ctaQuickRegisterMobile(event)" style="display:flex;gap:6px;margin-bottom:12px;">
            <input type="email" id="cta-email-mobile"
                placeholder="<?php echo htmlspecialchars($c['placeholder']); ?>"
                required autocomplete="email"
                style="flex:1;padding:12px 14px;border:none;border-radius:8px;font-size:1rem;
                       outline:none;font-family:inherit;background:#fff;color:#333;min-width:0;">
            <button type="submit"
                style="background:#F9A825;color:#1a2e1a;border:none;border-radius:8px;
                       padding:12px 16px;font-weight:700;font-size:0.88rem;cursor:pointer;
                       white-space:nowrap;font-family:inherit;">
                <?php echo $c['btn_email']; ?>
            </button>
        </form>
        <!-- Separador -->
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;opacity:0.55;">
            <div style="flex:1;height:1px;background:rgba(255,255,255,0.3);"></div>
            <span style="font-size:0.7rem;white-space:nowrap;"><?php echo $c['divider']; ?></span>
            <div style="flex:1;height:1px;background:rgba(255,255,255,0.3);"></div>
        </div>
        <!-- Registro completo -->
        <a href="<?php echo $lang_prefix_cta; ?>/register.html?ref=evento&slug=<?php echo urlencode($slug); ?>"
           style="display:block;text-align:center;background:rgba(255,255,255,0.12);
                  border:1.5px solid rgba(255,255,255,0.35);color:#fff;border-radius:8px;
                  padding:12px;font-size:0.88rem;font-weight:600;text-decoration:none;margin-bottom:8px;">
            <?php echo $c['btn_reg']; ?>
        </a>
        <!-- Ya tengo cuenta -->
        <a href="<?php echo $lang_prefix_cta; ?>/login.html?ref=evento&slug=<?php echo urlencode($slug); ?>"
           style="display:block;text-align:center;color:rgba(255,255,255,0.6);
                  font-size:0.8rem;text-decoration:none;padding:6px;">
            <?php echo $c['ya_cuenta']; ?>
        </a>
    </div>
</div>

<script>
function ctaQuickRegisterMobile(e) {
    e.preventDefault();
    var email = document.getElementById('cta-email-mobile').value.trim();
    if (!email) return;
    var form = e.target;
    var btn = form.querySelector('button[type="submit"]');
    if (btn) { btn.disabled = true; btn.textContent = '⏳'; }
    fetch('/api/subscribe-events.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            email: email,
            categoria: <?php echo json_encode($categoria_nombre); ?>,
            province: <?php echo json_encode($evento['province'] ?? ''); ?>,
            source_slug: <?php echo json_encode($slug); ?>,
            source_event_id: <?php echo json_encode((int)($evento['id'] ?? 0)); ?>
        })
    })
    .then(function(r){ return r.json(); })
    .catch(function(){return {};})
    .then(function(){
        // Reemplazar el overlay por pantalla de gracias
        var box = document.querySelector('#cta-mobile-overlay .cmo-box');
        if (box) {
            box.innerHTML = '<div style="text-align:center;padding:20px 0;">'
                + '<div style="font-size:2.5rem;margin-bottom:10px;">🎉</div>'
                + '<div style="font-weight:700;font-size:1.1rem;margin-bottom:8px;"><?php echo addslashes($c['ok_title']); ?></div>'
                + '<div style="font-size:0.85rem;opacity:0.85;"><?php echo addslashes($c['ok_msg']); ?></div>'
                + '</div>';
        }
        // Ocultar la barra inferior también
        var bar = document.getElementById('cta-mobile-bar');
        if (bar) bar.style.display = 'none';
        // Cerrar overlay tras 2.5s
        setTimeout(function(){
            var ov = document.getElementById('cta-mobile-overlay');
            if (ov) { ov.classList.remove('open'); document.body.style.overflow = ''; }
        }, 2500);
    });
}
// También necesita el div en móvil para añadir la clase meta-item-text al DOM existente
// (los meta-items fueron generados como SSR sin ese wrapper — los envolvemos via JS en móvil)
if (window.innerWidth <= 600) {
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.meta-item').forEach(function(item) {
            var icon = item.querySelector('.meta-icon');
            var label = item.querySelector('.meta-label');
            var value = item.querySelector('.meta-value');
            if (icon && label && value && !item.querySelector('.meta-item-text')) {
                var wrap = document.createElement('div');
                wrap.className = 'meta-item-text';
                item.appendChild(wrap);
                wrap.appendChild(label);
                wrap.appendChild(value);
            }
        });
    });
}
</script>

<?php endif; ?>

<!-- ── FOOTER ── -->
<footer class="site-footer">
    <div class="footer-social">
        <a href="https://www.instagram.com/rutas_rurales/" target="_blank" rel="noopener" aria-label="Instagram">📸</a>
        <a href="#" aria-label="Facebook">📘</a>
        <a href="#" aria-label="Twitter">🐦</a>
    </div>
    <div class="footer-links">
        <a href="/aviso-legal.html"><?php echo ($t['aviso_legal'] ?? ''); ?></a>
        <a href="/politica-cookies.html"><?php echo ($t['cookies'] ?? ''); ?></a>
        <a href="/agradecimientos.html"><?php echo ($t['agradecimientos'] ?? ''); ?></a>
    </div>
    <p style="color:rgba(255,255,255,0.5);font-size:0.8rem;">© 2026 rutasrurales.io · Todos los derechos reservados</p>
</footer>

<!-- ── SCRIPTS DIFERIDOS ── -->
<!-- Font Awesome (diferido) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>

<!-- Leaflet CSS (diferido, solo si hay mapa) -->
<?php if (!empty($evento['latitude']) && !empty($evento['longitude'])): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" media="print" onload="this.media='all'">
<?php endif; ?>

<!-- Script principal (diferido) -->
<script defer src="/js/evento-modular.js?v=3.0"></script>

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

</body>
</html>
