<?php
declare(strict_types=1);

/**
 * =============================================================================
 * PASAPORTE RURAL — Endpoint: Solicitar reserva con descuento
 * =============================================================================
 * Archivo  : pasaporte_rural/solicitar-reserva.php
 * Método   : POST
 * Auth     : Sesión PHP activa (turista logueado)
 *
 * Envía un email al anfitrión del alojamiento Premium informando de la
 * solicitud de reserva con el descuento del Pasaporte Rural ya indicado.
 * También envía una copia de confirmación al turista.
 * =============================================================================
 */

ob_start();

define('API_NO_HEADERS', true);
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$output_previo = ob_get_clean();
if ($output_previo !== '' && $output_previo !== false) {
    error_log('[SolicitarReserva] Output inesperado: ' . substr($output_previo, 0, 500));
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// ── Solo POST ────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Verificar sesión ─────────────────────────────────────────────────────────
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Sesión no iniciada.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// ── Leer datos del POST ───────────────────────────────────────────────────────
$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    // Fallback a form data
    $body = $_POST;
}

$alojamiento_id = isset($body['alojamiento_id']) ? (int) $body['alojamiento_id'] : 0;
$mensaje_extra  = isset($body['mensaje']) ? trim(strip_tags((string) $body['mensaje'])) : '';

if ($alojamiento_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos incompletos.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Límite de longitud del mensaje
if (mb_strlen($mensaje_extra) > 600) {
    $mensaje_extra = mb_substr($mensaje_extra, 0, 600);
}

// ── Conexión BD ──────────────────────────────────────────────────────────────
try {
    $pdo = getDBConnection();
} catch (Exception $e) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'Servicio no disponible.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Datos del turista ─────────────────────────────────────────────────────────
$stmtTurista = $pdo->prepare(
    'SELECT u.first_name, u.last_name, u.email AS turista_email,
            pt.descuento_actual, pt.nivel
       FROM users u
       JOIN pasaporte_turistas pt ON pt.user_id = u.id
      WHERE u.id = ? AND pt.estado = "activo"
      LIMIT 1'
);
$stmtTurista->execute([$user_id]);
$turista = $stmtTurista->fetch();

if (!$turista) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No tienes un Pasaporte Rural activo.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$nombre_turista  = trim($turista['first_name'] . ' ' . $turista['last_name']);
$email_turista   = $turista['turista_email'];
$descuento       = (int) $turista['descuento_actual'];
$nivel           = $turista['nivel'];

// ── Datos del alojamiento ─────────────────────────────────────────────────────
$stmtAlo = $pdo->prepare(
    'SELECT name, email, municipality, province, price_per_night
       FROM accommodations
      WHERE id = ? AND is_premium = 1 AND is_active = 1
      LIMIT 1'
);
$stmtAlo->execute([$alojamiento_id]);
$alo = $stmtAlo->fetch();

if (!$alo) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Alojamiento no encontrado.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$nombre_alo   = $alo['name'];
$email_alo    = $alo['email'] ?? '';
$ubicacion    = trim(($alo['municipality'] ?? '') . ', ' . ($alo['province'] ?? ''), ', ');

$precio_orig  = $alo['price_per_night'] ? (float) $alo['price_per_night'] : null;
$precio_dto   = $precio_orig ? (int) round($precio_orig * (1 - $descuento / 100)) : null;

if (empty($email_alo)) {
    // Sin email de anfitrión — guardamos el intento y respondemos ok igualmente
    error_log('[SolicitarReserva] Alojamiento sin email: id=' . $alojamiento_id);
}

// ── Registrar solicitud en la BD (tabla solicitudes_reserva) ──────────────────
// Intentamos insertar; si la tabla no existe, solo logueamos y seguimos.
try {
    $pdo->prepare(
        'CREATE TABLE IF NOT EXISTS solicitudes_reserva (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id         INT UNSIGNED NOT NULL,
            alojamiento_id  INT UNSIGNED NOT NULL,
            descuento       TINYINT UNSIGNED NOT NULL DEFAULT 5,
            mensaje         TEXT,
            estado          ENUM("pendiente","leida","rechazada") NOT NULL DEFAULT "pendiente",
            created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user   (user_id),
            INDEX idx_alo    (alojamiento_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    )->execute();

    $pdo->prepare(
        'INSERT INTO solicitudes_reserva (user_id, alojamiento_id, descuento, mensaje)
         VALUES (?, ?, ?, ?)'
    )->execute([$user_id, $alojamiento_id, $descuento, $mensaje_extra ?: null]);

} catch (PDOException $e) {
    error_log('[SolicitarReserva] No se pudo registrar solicitud: ' . $e->getMessage());
    // No interrumpimos el flujo — igual enviamos el email
}

// ── Enviar email al anfitrión ─────────────────────────────────────────────────
$enviado_ok = false;

if (!empty($email_alo)) {
    $precioLinea = $precio_dto
        ? number_format($precio_orig, 0, ',', '.') . ' €  →  ' . number_format($precio_dto, 0, ',', '.') . ' € con descuento'
        : '(precio a consultar)';

    $asunto_alo = '📨 Solicitud de reserva con Pasaporte Rural — ' . $nombre_alo;

    $cuerpo_alo  = "Hola,\n\n";
    $cuerpo_alo .= "Has recibido una solicitud de reserva a través del Pasaporte Rural de rutasrurales.io.\n\n";
    $cuerpo_alo .= "──────────────────────────────\n";
    $cuerpo_alo .= "TURISTA:     {$nombre_turista}\n";
    $cuerpo_alo .= "EMAIL:       {$email_turista}\n";
    $cuerpo_alo .= "NIVEL:       {$nivel}\n";
    $cuerpo_alo .= "DESCUENTO:   {$descuento}%\n";
    $cuerpo_alo .= "PRECIO/NOCHE: {$precioLinea}\n";
    $cuerpo_alo .= "──────────────────────────────\n";

    if ($mensaje_extra) {
        $cuerpo_alo .= "\nMensaje del turista:\n\"{$mensaje_extra}\"\n";
    }

    $cuerpo_alo .= "\nPor favor, contacta directamente con el turista en: {$email_turista}\n";
    $cuerpo_alo .= "El descuento del {$descuento}% forma parte de su Pasaporte Rural activo y debe respetarse.\n\n";
    $cuerpo_alo .= "rutasrurales.io — Conectando viajeros con la España rural\n";

    $headers_alo  = 'From: Pasaporte Rural <noreply@rutasrurales.io>' . "\r\n";
    $headers_alo .= 'Reply-To: ' . $email_turista . "\r\n";
    $headers_alo .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";

    $enviado_ok = @mail($email_alo, $asunto_alo, $cuerpo_alo, $headers_alo);
}

// ── Enviar confirmación al turista ────────────────────────────────────────────
if (!empty($email_turista)) {
    $asunto_turista = '✅ Solicitud de reserva enviada — ' . $nombre_alo;

    $cuerpo_turista  = "Hola {$nombre_turista},\n\n";
    $cuerpo_turista .= "Tu solicitud de reserva ha sido enviada correctamente al anfitrión.\n\n";
    $cuerpo_turista .= "Alojamiento: {$nombre_alo}\n";
    $cuerpo_turista .= "Ubicación:   {$ubicacion}\n";
    $cuerpo_turista .= "Descuento:   {$descuento}% (Pasaporte Rural activo)\n\n";
    $cuerpo_turista .= "El anfitrión se pondrá en contacto contigo en breve.\n";
    $cuerpo_turista .= "Recuerda mostrar tu QR del Pasaporte Rural al hacer el check-in.\n\n";
    $cuerpo_turista .= "¡Gracias por explorar la España rural con nosotros!\n\n";
    $cuerpo_turista .= "El equipo de rutasrurales.io\n";

    $headers_turista  = 'From: Pasaporte Rural <noreply@rutasrurales.io>' . "\r\n";
    $headers_turista .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";

    @mail($email_turista, $asunto_turista, $cuerpo_turista, $headers_turista);
}

// ── Respuesta ────────────────────────────────────────────────────────────────
echo json_encode([
    'success'        => true,
    'nombre_alo'     => $nombre_alo,
    'descuento'      => $descuento,
    'email_enviado'  => $enviado_ok,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
