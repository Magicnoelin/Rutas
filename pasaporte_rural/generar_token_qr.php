<?php
/**
 * =============================================================================
 * PASAPORTE RURAL — Endpoint: Generador de Token QR Temporal
 * =============================================================================
 * Archivo  : pasaporte_rural/generar_token_qr.php
 * Método   : GET (llamada AJAX desde mi-carnet.php)
 * Auth     : Sesión PHP activa con user_id (turista logueado)
 * Respuesta: JSON
 *
 * Flujo de seguridad:
 *   1. Verifica sesión activa del turista.
 *   2. Carga/crea su pasaporte en pasaporte_turistas.
 *   3. Invalida (marca 'expirado') todos sus tokens OTP anteriores > 2 minutos
 *      (limpieza lazy para mantener la BD limpia sin cron job).
 *   4. Genera un nuevo hash OTP con bin2hex(random_bytes(48)) — 96 chars hex.
 *   5. Inserta en qr_temporales con estado='pendiente'.
 *   6. Devuelve JSON con la URL firmada y datos del turista.
 *
 * Seguridad:
 *   - Token OTP: 48 bytes de entropía criptográfica real (PHP CSPRNG).
 *   - TTL: 60 segundos (QR_TTL_SEGUNDOS en config.php).
 *   - Un solo uso: se destruye al ser sellado por el propietario.
 *   - Rate limit suave: no se generan tokens si hay uno pendiente de < 30 s.
 *   - CORS: responde solo a solicitudes con sesión válida.
 * =============================================================================
 */

declare(strict_types=1);

// ── Capturar cualquier output inesperado (warnings, notices de PHP) ───────────
// Así si hay un aviso de PHP no rompe el JSON
ob_start();

// ── Dependencias ──────────────────────────────────────────────────────────────
// API_NO_HEADERS evita que api/config.php emita cabeceras CORS y Content-Type
define('API_NO_HEADERS', true);
require_once __DIR__ . '/config.php';

// ── 1. SESIÓN ─────────────────────────────────────────────────────────────────
// Usamos session_start() directamente: no reconfiguramos cookies para evitar
// warnings de PHP 8 ("Cannot change session name when session is active").
// El proyecto principal ya inició sesión con los parámetros correctos.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Descartar cualquier output generado hasta aquí (warnings de PHP, etc.)
$output_previo = ob_get_clean();
if ($output_previo !== '' && $output_previo !== false) {
    // Loguear en error_log para diagnóstico pero no enviarlo al cliente
    error_log('[PasaporteQR] Output no esperado antes del JSON: ' . substr($output_previo, 0, 500));
}

// ── Cabeceras de respuesta ────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error'   => 'Debes iniciar sesión para usar el Pasaporte Rural.',
        'redirect' => '/login.html',
    ]);
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// ── 2. CONEXIÓN A BD ──────────────────────────────────────────────────────────
try {
    $pdo = getDBConnection();
} catch (Exception $e) {
    error_log('[PasaporteQR] Error BD: ' . $e->getMessage());
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'Servicio temporalmente no disponible.']);
    exit;
}

// ── 3. CARGAR O CREAR EL PASAPORTE DEL TURISTA ───────────────────────────────
$stmt = $pdo->prepare(
    'SELECT pt.*, CONCAT(u.first_name, " ", u.last_name) AS nombre_turista,
            u.email, u.avatar_url
       FROM pasaporte_turistas pt
       JOIN users u ON u.id = pt.user_id
      WHERE pt.user_id = ?
      LIMIT 1'
);
$stmt->execute([$user_id]);
$pasaporte = $stmt->fetch();

if (!$pasaporte) {
    // Primera vez: crear el pasaporte automáticamente
    $token_fijo = bin2hex(random_bytes(32)); // 64 chars hex

    try {
        $pdo->prepare(
            'INSERT INTO pasaporte_turistas
                (user_id, token_fijo, descuento_actual, puntos_totales, puntos_periodo, nivel, estado)
             VALUES (?, ?, ?, 0, 0, "Viajero", "activo")'
        )->execute([$user_id, $token_fijo, DESCUENTO_BASE]);

        // Volver a cargar con los datos del usuario
        $stmt->execute([$user_id]);
        $pasaporte = $stmt->fetch();

    } catch (PDOException $e) {
        error_log('[PasaporteQR] Error creando pasaporte: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'No se pudo crear el Pasaporte Rural.']);
        exit;
    }
}

// ── 4. VERIFICAR ESTADO DEL PASAPORTE ────────────────────────────────────────
if ($pasaporte['estado'] !== 'activo') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error'   => 'Tu Pasaporte Rural está ' . $pasaporte['estado'] . '. Contacta con soporte.',
    ]);
    exit;
}

$pasaporte_id = (int) $pasaporte['id'];

// ── 5. LIMPIEZA LAZY: invalidar tokens propios obsoletos ─────────────────────
// Marcamos como 'expirado' todos los tokens pendientes de este turista
// que tengan más de 2 minutos (bien superaron el TTL de 60s con margen)
try {
    $pdo->prepare(
        'UPDATE qr_temporales
            SET estado = "expirado"
          WHERE pasaporte_id = ?
            AND estado = "pendiente"
            AND created_at < DATE_SUB(NOW(), INTERVAL 120 SECOND)'
    )->execute([$pasaporte_id]);
} catch (PDOException $e) {
    // No crítico: loguear y continuar
    error_log('[PasaporteQR] Aviso en limpieza lazy: ' . $e->getMessage());
}

// ── 6. RATE LIMIT SUAVE ───────────────────────────────────────────────────────
// Reutilizar token solo si tiene al menos 15 segundos de vida restante.
// (TTL=60s → solo reutilizar si tiene menos de 45s de antigüedad)
// Esto evita devolver tokens a punto de expirar que generarían QR inútiles.
$margen_minimo  = 15; // segundos mínimos de vida restante para reutilizar
$max_antiguedad = QR_TTL_SEGUNDOS - $margen_minimo; // = 45 segundos

$stmt_recent = $pdo->prepare(
    'SELECT hash_token, created_at
       FROM qr_temporales
      WHERE pasaporte_id = ?
        AND estado = "pendiente"
        AND created_at > DATE_SUB(NOW(), INTERVAL ' . $max_antiguedad . ' SECOND)
      ORDER BY created_at DESC
      LIMIT 1'
);
$stmt_recent->execute([$pasaporte_id]);
$token_reciente = $stmt_recent->fetch();

if ($token_reciente) {
    $creado_ts    = strtotime($token_reciente['created_at']);
    $transcurrido = time() - $creado_ts;
    $expira_en    = max(1, QR_TTL_SEGUNDOS - $transcurrido);

    echo json_encode([
        'success'       => true,
        'token_url'     => PASAPORTE_URL . '/validar_pasaporte.php?token=' . $token_reciente['hash_token'],
        'hash_token'    => $token_reciente['hash_token'],
        'nombre'        => $pasaporte['nombre_turista'],
        'descuento'     => (int) $pasaporte['descuento_actual'],
        'puntos'        => (int) $pasaporte['puntos_totales'],
        'nivel'         => $pasaporte['nivel'],
        'nivel_emoji'   => NIVELES_EMOJI[$pasaporte['nivel']] ?? '🌱',
        'total_sellos'  => (int) $pasaporte['total_sellos'],
        'avatar_url'    => $pasaporte['avatar_url'] ?? '',
        'expira_en'     => $expira_en,
        'rotacion_cada' => QR_ROTACION_SEGUNDOS,
        'reutilizado'   => true,
    ]);
    exit;
}

// ── 7. GENERAR NUEVO TOKEN OTP ────────────────────────────────────────────────
// bin2hex(random_bytes(48)) = 96 caracteres hexadecimales
// Usa el CSPRNG del sistema operativo (seguro para tokens criptográficos)
$hash_token = bin2hex(random_bytes(48));
$ip         = pasaporte_obtener_ip();

try {
    $pdo->prepare(
        'INSERT INTO qr_temporales (pasaporte_id, hash_token, estado, ip_generacion)
         VALUES (?, ?, "pendiente", ?)'
    )->execute([$pasaporte_id, $hash_token, $ip]);

} catch (PDOException $e) {
    error_log('[PasaporteQR] Error insertando token: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error generando el código QR. Inténtalo de nuevo.']);
    exit;
}

// ── 8. RESPUESTA JSON ─────────────────────────────────────────────────────────
/*
 * La URL que codifica el QR lleva el hash como parámetro GET.
 * El propietario la escanea → valida_pasaporte.php la recibe y valida.
 */
echo json_encode([
    'success'       => true,
    'token_url'     => PASAPORTE_URL . '/validar_pasaporte.php?token=' . $hash_token,
    'hash_token'    => $hash_token,       // Por si el cliente necesita el raw hash
    'nombre'        => $pasaporte['nombre_turista'],
    'descuento'     => (int) $pasaporte['descuento_actual'],
    'puntos'        => (int) $pasaporte['puntos_totales'],
    'nivel'         => $pasaporte['nivel'],
    'nivel_emoji'   => NIVELES_EMOJI[$pasaporte['nivel']] ?? '🌱',
    'total_sellos'  => (int) $pasaporte['total_sellos'],
    'avatar_url'    => $pasaporte['avatar_url'] ?? '',
    'expira_en'     => QR_TTL_SEGUNDOS,   // Segundos de validez del token (60)
    'rotacion_cada' => QR_ROTACION_SEGUNDOS, // Para que el JS sepa cuándo rotar (45)
    'reutilizado'   => false,
]);
