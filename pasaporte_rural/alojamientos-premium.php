<?php
declare(strict_types=1);

/**
 * =============================================================================
 * PASAPORTE RURAL — Endpoint: Alojamientos Premium con precio descontado
 * =============================================================================
 * Archivo  : pasaporte_rural/alojamientos-premium.php
 * Método   : GET (llamada AJAX desde mi-pasaporte.php)
 * Auth     : Sesión PHP activa con user_id (turista logueado)
 * Respuesta: JSON
 *
 * Devuelve todos los alojamientos is_premium=1 con el precio_con_descuento
 * calculado para el descuento específico del huésped (5-10%).
 * =============================================================================
 */

// ob_start captura cualquier output accidental (notices, warnings de PHP)
// que corrompería el JSON — patrón idéntico a generar_token_qr.php
ob_start();

// ── Dependencias ──────────────────────────────────────────────────────────────
define('API_NO_HEADERS', true);
require_once __DIR__ . '/config.php';

// ── Sesión — igual que generar_token_qr.php ───────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Descartar cualquier output generado hasta aquí ───────────────────────────
$output_previo = ob_get_clean();
if ($output_previo !== '' && $output_previo !== false) {
    error_log('[PremiumAlos] Output inesperado antes del JSON: ' . substr($output_previo, 0, 500));
}

// ── Cabeceras JSON ────────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, max-age=300');
header('X-Content-Type-Options: nosniff');

// ── Verificar sesión ──────────────────────────────────────────────────────────
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Sesión no iniciada.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// ── Conexión BD ───────────────────────────────────────────────────────────────
try {
    $pdo = getDBConnection();
} catch (Exception $e) {
    error_log('[PremiumAlos] Error BD: ' . $e->getMessage());
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'Servicio no disponible.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Obtener descuento del turista ─────────────────────────────────────────────
$stmtPasaporte = $pdo->prepare(
    'SELECT descuento_actual FROM pasaporte_turistas WHERE user_id = ? AND estado = "activo" LIMIT 1'
);
$stmtPasaporte->execute([$user_id]);
$pasaporte = $stmtPasaporte->fetch();

$descuento = $pasaporte ? (int) $pasaporte['descuento_actual'] : DESCUENTO_BASE;

// ── Obtener alojamientos Premium activos ──────────────────────────────────────
try {
    $stmtAlos = $pdo->prepare(
        'SELECT a.id, a.name, a.slug, a.municipality, a.province,
                a.accommodation_type, a.price_per_night,
                a.photo1, a.latitude, a.longitude
           FROM accommodations a
          WHERE a.is_premium = 1
            AND a.is_active  = 1
          ORDER BY a.province ASC, a.name ASC'
    );
    $stmtAlos->execute();
    $rows = $stmtAlos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('[PremiumAlos] Error query: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al obtener alojamientos.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Procesar y calcular precios ───────────────────────────────────────────────
$alojamientos = [];
$base_url     = 'https://rutasrurales.io';

foreach ($rows as $row) {
    $precio_original = ($row['price_per_night'] !== null) ? (float) $row['price_per_night'] : null;

    $precio_dto = null;
    if ($precio_original !== null && $precio_original > 0) {
        $precio_dto = (int) round($precio_original * (1 - $descuento / 100));
    }

    // URL de la foto (compatible PHP 7.4+)
    $foto = null;
    if (!empty($row['photo1'])) {
        $foto = (strpos($row['photo1'], 'http') === 0)
            ? $row['photo1']
            : $base_url . '/' . ltrim($row['photo1'], '/');
    }

    // URL del alojamiento
    $url_alo = !empty($row['slug'])
        ? $base_url . '/alojamiento/' . $row['slug']
        : $base_url . '/alojamiento-detalle.php?id=' . (int) $row['id'];

    $alojamientos[] = [
        'id'                  => (int) $row['id'],
        'name'                => (string) $row['name'],
        'slug'                => (string) ($row['slug'] ?? ''),
        'municipality'        => (string) ($row['municipality'] ?? ''),
        'province'            => (string) ($row['province'] ?? ''),
        'accommodation_type'  => (string) ($row['accommodation_type'] ?? 'Alojamiento Rural'),
        'price_per_night'     => $precio_original,
        'price_con_descuento' => $precio_dto,
        'photo'               => $foto,
        'lat'                 => ($row['latitude']  !== null) ? (float) $row['latitude']  : null,
        'lng'                 => ($row['longitude'] !== null) ? (float) $row['longitude'] : null,
        'url'                 => $url_alo,
    ];
}

// ── Respuesta ─────────────────────────────────────────────────────────────────
echo json_encode([
    'success'      => true,
    'descuento'    => $descuento,
    'total'        => count($alojamientos),
    'alojamientos' => $alojamientos,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
