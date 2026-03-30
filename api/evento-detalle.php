<?php
/**
 * API Endpoint: Obtener Detalle de Evento Cultural Multilingüe
 * GET /api/evento-detalle.php?slug=nombre-evento&lang=de
 * GET /api/evento-detalle.php?id=123&lang=en
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once 'config.php';

$response = ['success' => false, 'data' => null, 'message' => ''];

try {
    $slug = isset($_GET['slug']) ? $_GET['slug'] : '';
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    // Detectar idioma (por defecto español 'es')
    $lang = isset($_GET['lang']) ? $_GET['lang'] : 'es';
    
    if (empty($slug) && $id === 0) {
        $response['message'] = 'Slug o ID no proporcionado';
        echo json_encode($response);
        exit;
    }

    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8mb4");

    // ── Verificar si la tabla de traducciones existe ──
    $hasTradsTable = false;
    try {
        $checkTable = $pdo->query("SHOW TABLES LIKE 'cultural_events_trads'");
        $hasTradsTable = ($checkTable->rowCount() > 0);
    } catch (Exception $e) {
        $hasTradsTable = false;
    }

    $evento = null;

    if ($hasTradsTable && $lang !== 'es') {
        /**
         * CONSULTA CON TRADUCCIONES (solo para idiomas distintos al español):
         * Usamos COALESCE para intentar traer el campo de la tabla de traducciones (t).
         * Si no existe o es NULL, usamos el campo original de la tabla principal (e).
         * NOTA: Solo usamos COALESCE en columnas que EXISTEN en cultural_events_trads:
         *   id, event_id, language_code, name, slug, description, short_description,
         *   program, target_audience, accessibility, meta_title, meta_description
         *   (venue_name NO existe en la tabla de traducciones)
         * 
         * IMPORTANTE: Para español (es) NO hacemos JOIN con trads porque el español
         * es el idioma base que vive directamente en cultural_events.
         */
        $sqlBase = "
            SELECT e.*, 
                COALESCE(t.name, e.name) as display_name,
                COALESCE(t.slug, e.slug) as display_slug,
                COALESCE(t.description, e.description) as display_description,
                COALESCE(t.short_description, e.short_description) as display_short_description,
                e.venue_name as display_venue_name,
                COALESCE(t.meta_title, e.meta_title) as display_meta_title,
                COALESCE(t.meta_description, e.meta_description) as display_meta_description,
                COALESCE(t.program, e.program) as display_program,
                COALESCE(t.target_audience, e.target_audience) as display_target_audience,
                COALESCE(t.accessibility, e.accessibility) as display_accessibility
            FROM cultural_events e
            LEFT JOIN cultural_events_trads t ON e.id = t.event_id AND t.language_code = :lang
            WHERE ";

        if (!empty($slug)) {
            $stmt = $pdo->prepare($sqlBase . " (e.slug = :slug1 OR t.slug = :slug2) AND e.is_active = 1 LIMIT 1");
            $stmt->execute(['slug1' => $slug, 'slug2' => $slug, 'lang' => $lang]);
        } else {
            $stmt = $pdo->prepare($sqlBase . " e.id = :id AND e.is_active = 1 LIMIT 1");
            $stmt->execute(['id' => $id, 'lang' => $lang]);
        }
        
        $evento = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Si no hay tabla de traducciones o no se encontró con traducciones, buscar solo en tabla principal
    if (!$evento) {
        $sqlSimple = "
            SELECT e.*,
                e.name as display_name,
                e.slug as display_slug,
                e.description as display_description,
                e.short_description as display_short_description,
                e.venue_name as display_venue_name,
                e.meta_title as display_meta_title,
                e.meta_description as display_meta_description,
                e.program as display_program,
                e.target_audience as display_target_audience,
                e.accessibility as display_accessibility
            FROM cultural_events e
            WHERE ";

        if (!empty($slug)) {
            $stmt = $pdo->prepare($sqlSimple . " e.slug = :slug AND e.is_active = 1 LIMIT 1");
            $stmt->execute(['slug' => $slug]);
        } else {
            $stmt = $pdo->prepare($sqlSimple . " e.id = :id AND e.is_active = 1 LIMIT 1");
            $stmt->execute(['id' => $id]);
        }
        
        $evento = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$evento) {
        $response['message'] = 'Evento no encontrado';
        echo json_encode($response);
        exit;
    }

    // ── Procesar galería de imágenes ──
    $webRoot = dirname(__DIR__);
    $isPhotoValid = function(string $url) use ($webRoot): bool {
        if (empty(trim($url))) return false;
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            $path = parse_url($url, PHP_URL_PATH);
            return $path && file_exists($webRoot . $path);
        }
        if (str_starts_with($url, '/')) {
            return file_exists($webRoot . $url);
        }
        return file_exists($webRoot . '/' . ltrim($url, '/'));
    };

    $fotos = [];
    $candidates = [
        $evento['photo1'] ?? '', $evento['photo2'] ?? '', $evento['photo3'] ?? '', $evento['photo4'] ?? '',
        $evento['poster_image'] ?? '',
        '/cultural_events_images/' . $evento['slug'] . '.webp',
    ];

    foreach ($candidates as $candidate) {
        if (!empty(trim($candidate)) && $isPhotoValid($candidate)) {
            if (!str_starts_with($candidate, '/') && !str_starts_with($candidate, 'http')) {
                $candidate = '/' . $candidate;
            }
            if (!in_array($candidate, $fotos)) { $fotos[] = $candidate; }
        }
    }

    if (!empty($evento['gallery'])) {
        $gallery = json_decode($evento['gallery'], true);
        if (is_array($gallery)) {
            foreach ($gallery as $g) {
                if (!empty($g) && $isPhotoValid($g) && !in_array($g, $fotos)) { $fotos[] = $g; }
            }
        }
    }

    if (empty($fotos)) {
        $fotos[] = 'https://images.unsplash.com/photo-1514320291840-2e0a9bf2a9ae?w=800&h=600&fit=crop';
    }

    // Formatear precio
    $precioFormateado = ($lang === 'de') ? 'Kostenloser Eintritt' : 'Entrada gratuita';
    if (!empty($evento['ticket_price']) && $evento['ticket_price'] > 0) {
        $precioFormateado = number_format($evento['ticket_price'], 2) . '€';
    }

    // Formatear fechas
    $fechaFormateada = '';
    $fechaLarga = '';
    if (!empty($evento['start_date'])) {
        $fecha = new DateTime($evento['start_date']);
        $fechaFormateada = $fecha->format('d/m/Y');
        $fechaLarga = $fecha->format('d/m/Y');
    }

    // Construir respuesta usando los campos "display_" que vienen del COALESCE
    $response['success'] = true;
    $response['data'] = [
        'id' => $evento['id'],
        'titulo' => $evento['display_name'],
        'meta_title' => $evento['display_meta_title'],
        'meta_description' => $evento['display_meta_description'],
        'descripcion' => strip_tags($evento['display_description'], '<strong><b><i><em><a><ul><ol><li><p><br><span><h1><h2><h3><h4><h5><h6><table><tr><td><th><thead><tbody>'),
        'descripcion_corta' => $evento['display_short_description'],
        'fecha_evento' => $evento['start_date'],
        'fecha_fin' => $evento['end_date'] ?? null,
        'hora_evento' => $evento['start_time'] ?? null,
        'hora_fin' => $evento['end_time'] ?? null,
        'hora_formateada' => !empty($evento['start_time']) && $evento['start_time'] !== '00:00:00' ? substr($evento['start_time'], 0, 5) : null,
        'fecha_formateada' => $fechaFormateada,
        'ubicacion' => $evento['display_venue_name'],
        'direccion' => $evento['venue_address'] ?? null,
        'localidad' => $evento['municipality'] ?? null,
        'provincia' => $evento['province'] ?? null,
        'categoria' => $evento['category_id'] ?? null,
        'categoria_nombre' => null,
        'fotos' => $fotos,
        'precio' => $precioFormateado,
        'precio_numerico' => $evento['ticket_price'] ?? 0,
        'slug' => $evento['display_slug'],
        'latitud' => $evento['latitude'] ?? null,
        'longitud' => $evento['longitude'] ?? null,
        'organizador' => $evento['organizer'] ?? null,
        'telefono' => $evento['phone'] ?? null,
        'email' => $evento['email'] ?? null,
        'url_entradas' => $evento['ticket_url'] ?? null,
        // Campos traducibles adicionales
        'program' => $evento['display_program'],
        'target_audience' => $evento['display_target_audience'],
        'accessibility' => $evento['display_accessibility'],
    ];

} catch (PDOException $e) {
    $response['message'] = 'Error de base de datos';
    error_log("Error PDO en evento-detalle: " . $e->getMessage() . " | SQL State: " . $e->getCode());
} catch (Exception $e) {
    $response['message'] = 'Error general';
    error_log("Error general en evento-detalle: " . $e->getMessage());
}

echo json_encode($response);
