<?php
/**
 * =============================================================================
 * PASAPORTE RURAL — API: Alojamientos Premium con precio descontado
 * =============================================================================
 * Archivo  : pasaporte_rural/api/alojamientos-premium.php
 * Acceso   : Turistas con sesión activa y pasaporte
 * Función  : Devuelve JSON con todos los alojamientos is_premium = 1,
 *            incluyendo el precio con el descuento del huésped ya calculado.
 *
 * Respuesta JSON:
 *   {
 *     "success": true,
 *     "descuento": 5,
 *     "total": 12,
 *     "alojamientos": [
 *       {
 *         "id": 42,
 *         "name": "Casa El Roble",
 *         "slug": "casa-el-roble",
 *         "municipality": "Vinuesa",
 *         "province": "Soria",
 *         "accommodation_type": "Casa Rural",
 *         "price_per_night": 80,
 *         "price_con_descuento": 76,
 *         "photo": "https://...",
 *         "lat": 41.9,
 *         "lng": -2.7
 *       }, ...
 *     ]
 *   }
 * =============================================================================
 */

declare(strict_types=1);

// ── Cabeceras ─────────────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, max-age=300');   // 5 min caché privada
header('X-Content-Type-Options: nosniff');

// ── Config y utilidades del módulo ────────────────────────────────────────────
define('API_NO_HEADERS', true);
require_once __DIR__ . '/../config.php';

// ── Función de error JSON ─────────────────────────────────────────────────────
function jsonError(int $code, string $msg): never
{
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── 1. Verificar sesión ───────────────────────────────────────────────────────
pasaporte_iniciar_sesion();

if (empty($_SESSION['user_id'])) {
    jsonError(401, 'Sesión no iniciada. Por favor, inicia sesión.');
}

$user_id = (int) $_SESSION['user_id'];

// ── 2. Conexión BD ────────────────────────────────────────────────────────────
try {
    $pdo = getDBConnection();
} catch (\Exception $e) {
    jsonError(500, 'Error de conexión a la base de datos.');
}

// ── 3. Obtener pasaporte del turista (para el descuento) ──────────────────────
$stmtPasaporte = $pdo->prepare(
    'SELECT descuento_actual FROM pasaporte_turistas WHERE user_id = ? AND estado = "activo" LIMIT 1'
);
$stmtPasaporte->execute([$user_id]);
$pasaporte = $stmtPasaporte->fetch();

if (!$pasaporte) {
    // Si no tiene pasaporte activo, devolver descuento base
    $descuento = DESCUENTO_BASE;
} else {
    $descuento = (int) $pasaporte['descuento_actual'];
}

// ── 4. Obtener alojamientos Premium ───────────────────────────────────────────
$stmtAlos = $pdo->prepare(
    'SELECT
        a.id,
        a.name,
        a.slug,
        a.municipality,
        a.province,
        a.accommodation_type,
        a.price_per_night,
        a.photo1,
        a.latitude,
        a.longitude
     FROM accommodations a
     WHERE a.is_premium = 1
       AND a.is_active  = 1
     ORDER BY
       a.province ASC,
       a.name ASC'
);
$stmtAlos->execute();
$rows = $stmtAlos->fetchAll();

// ── 5. Procesar y calcular precios ────────────────────────────────────────────
$alojamientos = [];
$base_url     = 'https://rutasrurales.io';

foreach ($rows as $row) {
    $precio_original = $row['price_per_night'] !== null ? (float) $row['price_per_night'] : null;

    // Precio con descuento: redondeado al entero más cercano
    $precio_dto = null;
    if ($precio_original !== null && $precio_original > 0) {
        $precio_dto = (int) round($precio_original * (1 - $descuento / 100));
    }

    // URL de la foto
    $foto = null;
    if (!empty($row['photo1'])) {
        $foto = (str_starts_with($row['photo1'], 'http'))
            ? $row['photo1']
            : $base_url . '/' . ltrim($row['photo1'], '/');
    }

    // URL del alojamiento
    $url_alo = !empty($row['slug'])
        ? $base_url . '/alojamiento/' . $row['slug']
        : $base_url . '/alojamiento-detalle.php?id=' . $row['id'];

    $alojamientos[] = [
        'id'                 => (int)   $row['id'],
        'name'               => $row['name'],
        'slug'               => $row['slug'] ?? '',
        'municipality'       => $row['municipality'] ?? '',
        'province'           => $row['province'] ?? '',
        'accommodation_type' => $row['accommodation_type'] ?? 'Alojamiento Rural',
        'price_per_night'    => $precio_original,
        'price_con_descuento'=> $precio_dto,
        'photo'              => $foto,
        'lat'                => $row['latitude']  !== null ? (float) $row['latitude']  : null,
        'lng'                => $row['longitude'] !== null ? (float) $row['longitude'] : null,
        'url'                => $url_alo,
    ];
}

// ── 6. Respuesta ──────────────────────────────────────────────────────────────
echo json_encode([
    'success'      => true,
    'descuento'    => $descuento,
    'total'        => count($alojamientos),
    'alojamientos' => $alojamientos,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
