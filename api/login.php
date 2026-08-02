<?php
/**
 * API Endpoint: Login de Usuario
 * POST /api/login.php
 * Body: JSON { email, password }
 *
 * Cambios v2 (2026-01-08):
 *   - Email normalizado a minúsculas antes de la consulta.
 *     Esto garantiza que "Usuario@Gmail.com" y "usuario@gmail.com"
 *     encuentren la misma cuenta (evita "usuario no encontrado" por case).
 *   - Mensaje de error interno de BD nunca expuesto al frontend.
 */

require_once 'config.php';
require_once 'user_normalizer.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Registra un intento de login para auditoría y depuración.
 */
function log_login_attempt(PDO $pdo, string $email, bool $success, string $reason = ''): void
{
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            success BOOLEAN NOT NULL,
            reason VARCHAR(255) NULL,
            attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (email),
            INDEX (attempted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $stmt = $pdo->prepare(
            "INSERT INTO login_attempts (email, ip_address, success, reason)
             VALUES (:email, :ip, :success, :reason)"
        );
        $stmt->execute([
            ':email'   => $email,
            ':ip'      => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
            ':success' => (int) $success,
            ':reason'  => $reason,
        ]);
    } catch (PDOException $e) {
        // No detener el login si falla el log
        error_log('[login.php] Error al registrar intento: ' . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

$data = json_decode(file_get_contents('php://input'), true);

// ── Normalizar email a minúsculas antes de cualquier validación ──
// Así "Usuario@Gmail.COM" y "usuario@gmail.com" son equivalentes.
$emailRaw = $data['email'] ?? '';
$email    = validateAndNormalizeEmail($emailRaw);
$password = $data['password'] ?? '';

if ($email === false || empty($password)) {
    jsonError('Email y contraseña son requeridos', 400);
}

$pdo = getDBConnection();

// ── Buscar usuario por email normalizado ─────────────────────
// La BD almacena los emails en minúsculas desde register.php v2,
// por lo que la coincidencia exacta es suficiente y eficiente.
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    log_login_attempt($pdo, $email, false, 'Usuario no encontrado');
    // Mensaje genérico: no revelar si el email existe o no (evita enumeración)
    jsonError('Credenciales incorrectas', 401);
}

// ── Verificar que la cuenta no está suspendida ───────────────
if (isset($user['status']) && $user['status'] === 'suspended') {
    log_login_attempt($pdo, $email, false, 'Cuenta suspendida');
    jsonError('Tu cuenta ha sido suspendida. Contacta con soporte para más información.', 403);
}

// ── Verificar contraseña ──────────────────────────────────────
// La columna puede llamarse 'password' o 'password_hash'
$passwordColumn = isset($user['password_hash']) ? 'password_hash' : 'password';

if (empty($user[$passwordColumn]) || !password_verify($password, $user[$passwordColumn])) {
    log_login_attempt($pdo, $email, false, 'Contraseña incorrecta');
    jsonError('Credenciales incorrectas', 401);
}

// ── Login exitoso ─────────────────────────────────────────────
log_login_attempt($pdo, $email, true, 'Login exitoso');

// Regenerar ID de sesión para prevenir session fixation
session_regenerate_id(true);

$_SESSION['user_id']    = $user['id'];
$_SESSION['user_email'] = $user['email']; // Email ya normalizado en BD
$_SESSION['user_name']  = $user['first_name'] ?? explode('@', $user['email'])[0];
$_SESSION['user_type']  = $user['user_type'] ?? 'turista';

// ── Actualizar last_login ─────────────────────────────────────
try {
    $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id")
        ->execute([':id' => $user['id']]);
} catch (PDOException $e) {
    // No bloquear el login si falla el update de last_login
    error_log('[login.php] No se pudo actualizar last_login: ' . $e->getMessage());
}

// ── Determinar redirección según tipo de usuario ──────────────
$redirectUrl = 'user-dashboard.html';
if (isset($user['user_type'])) {
    $redirectUrl = match($user['user_type']) {
        'gestor' => 'gestor-dashboard.html',
        'admin'  => 'admin/index.php',
        default  => 'user-dashboard.html',
    };
}

jsonSuccess([
    'message'  => 'Inicio de sesión exitoso',
    'redirect' => $redirectUrl,
]);
