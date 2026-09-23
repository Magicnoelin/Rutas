<?php
/**
 * API Endpoint: GeoJSON para el selector de vista Mapa en /lugares/{slug}
 * GET /api/get_lugares_geojson.php?slug=bodegas&mode=categoria
 * GET /api/get_lugares_geojson.php?slug=soria&mode=provincia
 *
 * Solo devuelve lugares con coordenadas (latitude/longitude NOT NULL).
 * Respuesta: GeoJSON FeatureCollection estándar.
 */
ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://rutasrurales.io');
header('Cache-Control: public, max-age=300'); // 5 min caché en navegador

// ── Parámetros ────────────────────────────────────────────────────────────────
$slug_raw = isset($_GET['slug']) ? $_GET['slug'] : '';
$slug     = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($slug_raw)));
$mode_raw = isset($_GET['mode']) ? $_GET['mode'] : '';
$mode     = in_array($mode_raw, ['categoria', 'provincia'], true) ? $mode_raw : null;

if (empty($slug) || !$mode) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetros requeridos: slug y mode (categoria|provincia)']);
    exit;
}

// ── Helper: texto → slug ──────────────────────────────────────────────────────
function gj_to_slug(string $text): string {
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = strtr($text, [
        'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',
        'à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u','ç'=>'c',
    ]);
    $text = preg_replace('/[^a-z0-9\s\-]/', '', $text);
    $text = preg_replace('/[\s\-]+/', '-', $text);
    return trim($text, '-');
}

// ── Conexión BD ───────────────────────────────────────────────────────────────
if (!defined('API_NO_HEADERS')) define('API_NO_HEADERS', true);
require_once dirname(__DIR__) . '/api/config.php';

try {
    $pdo = getDBConnection();
    $places = [];

    if ($mode === 'categoria') {
        // Buscar category_id por slug
        $sc = $pdo->prepare(
            "SELECT id, name, icon FROM categories_places WHERE slug = ? AND is_active = 1 LIMIT 1"
        );
        $sc->execute([$slug]);
        $cat = $sc->fetch(PDO::FETCH_ASSOC);

        if (!$cat) {
            echo json_encode(['type' => 'FeatureCollection', 'features' => []]);
            exit;
        }

        // ── 1. Lugares de places_of_interest ─────────────────────────────────
        $sp = $pdo->prepare(
            "SELECT id, slug, name, municipality, province,
                    short_description, photo1, website, phone,
                    latitude, longitude,
                    'poi' AS source
             FROM places_of_interest
             WHERE category_id = ? AND is_active = 1
               AND latitude IS NOT NULL AND longitude IS NOT NULL
             ORDER BY name ASC
             LIMIT 200"
        );
        $sp->execute([$cat['id']]);
        $places = $sp->fetchAll(PDO::FETCH_ASSOC);

        // ── 2. Lugares de auxiliar_poi filtrados por categoría ────────────────
        // Buscamos en categoria_label usando el nombre de la categoría (ej: "Bodegas")
        $cat_name_like = '%' . $cat['name'] . '%';
        $sa = $pdo->prepare(
            "SELECT osm_id AS id,
                    COALESCE(slug, CONCAT('aux-', osm_id)) AS slug,
                    nombre AS name,
                    '' AS municipality,
                    '' AS province,
                    '' AS short_description,
                    '' AS photo1,
                    '' AS website,
                    '' AS phone,
                    latitud AS latitude,
                    longitud AS longitude,
                    datos_json,
                    'aux' AS source
             FROM auxiliar_poi
             WHERE (categoria_label LIKE ? OR categoria_label LIKE ?)
               AND latitud IS NOT NULL AND longitud IS NOT NULL
             LIMIT 500"
        );
        // También probar con el slug directamente (ej: slug=bodegas → 'bodega')
        $slug_like = '%' . rtrim($slug, 's') . '%';
        $sa->execute([$cat_name_like, $slug_like]);
        $aux_places = $sa->fetchAll(PDO::FETCH_ASSOC);

        // Enriquecer los auxiliar_poi con datos de datos_json
        foreach ($aux_places as &$aux) {
            $extra = json_decode($aux['datos_json'] ?? '{}', true) ?: [];
            $aux['website'] = $extra['web'] ?? '';
            $aux['phone']   = $extra['telefono'] ?? '';
            // Foto: miniatura del sitio web o imagen de Unsplash por defecto
            if (!empty($aux['website'])) {
                $aux['photo1'] = 'https://image.thum.io/get/width/300/crop/600/' . $aux['website'];
            }
            unset($aux['datos_json']);
        }
        unset($aux);

        // Merge: primero los POIs propios, luego los auxiliares (sin duplicar por coords aproximadas)
        $seen_coords = [];
        foreach ($places as $p) {
            $key = round((float)$p['latitude'], 4) . ',' . round((float)$p['longitude'], 4);
            $seen_coords[$key] = true;
        }
        foreach ($aux_places as $a) {
            $key = round((float)$a['latitude'], 4) . ',' . round((float)$a['longitude'], 4);
            if (!isset($seen_coords[$key])) {
                $places[] = $a;
                $seen_coords[$key] = true;
            }
        }

    } else {
        // mode = provincia — normalizar el slug a nombre de provincia real
        $sprovs = $pdo->query(
            "SELECT DISTINCT province FROM places_of_interest
             WHERE is_active = 1 AND province IS NOT NULL AND province != ''"
        );
        $all_provinces = $sprovs->fetchAll(PDO::FETCH_COLUMN);
        $province_label = null;
        foreach ($all_provinces as $prov) {
            if (gj_to_slug($prov) === $slug) {
                $province_label = $prov;
                break;
            }
        }

        if (!$province_label) {
            echo json_encode(['type' => 'FeatureCollection', 'features' => []]);
            exit;
        }

        $sp = $pdo->prepare(
            "SELECT p.id, p.slug, p.name, p.municipality, p.province,
                    p.short_description, p.photo1, p.website, p.phone,
                    p.latitude, p.longitude,
                    c.name AS category_name
             FROM places_of_interest p
             LEFT JOIN categories_places c ON p.category_id = c.id
             WHERE p.province = ? AND p.is_active = 1
               AND p.latitude IS NOT NULL AND p.longitude IS NOT NULL
             ORDER BY p.name ASC
             LIMIT 200"
        );
        $sp->execute([$province_label]);
        $places = $sp->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Construir GeoJSON ─────────────────────────────────────────────────────
    $features = [];
    foreach ($places as $p) {
        $lat = (float)$p['latitude'];
        $lng = (float)$p['longitude'];
        if ($lat === 0.0 && $lng === 0.0) continue; // ignorar coords nulas

        $source = $p['source'] ?? 'poi';

        $features[] = [
            'type' => 'Feature',
            'geometry' => [
                'type'        => 'Point',
                'coordinates' => [$lng, $lat],  // GeoJSON: [lon, lat]
            ],
            'properties' => [
                'id'          => $p['id'] ?? '',
                'slug'        => $p['slug'] ?? '',
                'nombre'      => $p['name'] ?? '',
                'municipio'   => $p['municipality'] ?? '',
                'provincia'   => $p['province'] ?? '',
                'descripcion' => $p['short_description'] ? mb_substr($p['short_description'], 0, 120) : '',
                'foto'        => $p['photo1'] ?? '',
                'web'         => $p['website'] ?? '',
                'telefono'    => $p['phone'] ?? '',
                'categoria'   => $p['category_name'] ?? '',
                'source'      => $source,  // 'poi' | 'aux'
            ],
        ];
    }

    echo json_encode(
        ['type' => 'FeatureCollection', 'features' => $features],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno', 'features' => []]);
}
