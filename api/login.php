<?php
require_once 'config.php';

// Iniciar sesión PHP
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

// Obtener datos
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    jsonError('Datos inválidos', 400);
}

$email = sanitizeInput($input['email'] ?? '');
$password = $input['password'] ?? '';

// Asegurar que la contraseña se procesa igual que en el registro (sin espacios extra)
$password = trim($password);

if (empty($email) || empty($password)) {
    jsonError('Email y contraseña son requeridos', 400);
}

try {
    $pdo = getDBConnection();
    // Buscar usuario por email
    $stmt = $pdo->prepare("SELECT id, password_hash, first_name, last_name, user_type FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Log para el administrador (no mostrar al usuario por seguridad)
        error_log("Login fallido: Usuario no encontrado para email: $email");
        jsonError('Email o contraseña incorrectos', 401);
    }

    // Verificar contraseña
    if (password_verify($password, $user['password_hash'])) {
        // Guardar datos en sesión
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_type'] = $user['user_type'];
        
        jsonSuccess([
            'redirect' => 'user-dashboard.html', // Redirección correcta
            'user_name' => $user['first_name']
        ], 'Inicio de sesión exitoso');
    } else {
        error_log("Login fallido: Contraseña incorrecta para email: $email");
        // Verificar si el hash está truncado (problema común de base de datos)
        if (strlen($user['password_hash']) < 60) {
            error_log("ADVERTENCIA CRÍTICA: El hash en la BD está truncado (" . strlen($user['password_hash']) . " chars). Aumenta la columna password_hash a VARCHAR(255).");
        }
        jsonError('Email o contraseña incorrectos', 401);
    }
} catch (Exception $e) {
    error_log("Login error de servidor: " . $e->getMessage());
    jsonError('Error en el servidor', 500);
}