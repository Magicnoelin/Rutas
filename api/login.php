<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Registra un intento de login para auditoría y depuración.
 *
 * @param PDO $pdo Conexión a la base de datos.
 * @param string $email El email del usuario que intenta iniciar sesión.
 * @param bool $success Si el login fue exitoso.
 * @param string $reason El motivo del fallo (si lo hubo).
 */
function log_login_attempt($pdo, $email, $success, $reason = '') {
    try {
        // Crear tabla si no existe
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

        $sql = "INSERT INTO login_attempts (email, ip_address, success, reason) VALUES (:email, :ip, :success, :reason)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':email' => $email,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
            ':success' => (int)$success,
            ':reason' => $reason
        ]);
    } catch (PDOException $e) {
        // No detener el login si falla el log, solo registrar el error
        error_log("Error al registrar intento de login: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

$data = json_decode(file_get_contents('php://input'), true);

$email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
$password = $data['password'] ?? '';

if (!$email || empty($password)) {
    jsonError('Email y contraseña son requeridos', 400);
}

$pdo = getDBConnection();

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    log_login_attempt($pdo, $email, false, 'Usuario no encontrado');
    jsonError('Credenciales incorrectas', 401);
}

// Verificar la contraseña
// La columna puede llamarse 'password' o 'password_hash'
$passwordColumn = isset($user['password_hash']) ? 'password_hash' : 'password';

if (!isset($user[$passwordColumn]) || !password_verify($password, $user[$passwordColumn])) {
    log_login_attempt($pdo, $email, false, 'Contraseña incorrecta');
    jsonError('Credenciales incorrectas', 401);
}

// Login exitoso
log_login_attempt($pdo, $email, true, 'Login exitoso');

// Regenerar ID de sesión para prevenir session fixation
session_regenerate_id(true);

$_SESSION['user_id'] = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_name'] = $user['first_name'] ?? explode('@', $user['email'])[0];
$_SESSION['user_type'] = $user['user_type'] ?? 'turista';

// Determinar a dónde redirigir al usuario
$redirectUrl = 'mi-cuenta.html'; // Por defecto
if (isset($user['user_type'])) {
    if ($user['user_type'] === 'gestor') {
        $redirectUrl = 'gestor-dashboard.html';
    } elseif ($user['user_type'] === 'admin') {
        $redirectUrl = 'admin/index.php';
    }
}

jsonSuccess([
    'message' => 'Inicio de sesión exitoso',
    'redirect' => $redirectUrl
]);
?>