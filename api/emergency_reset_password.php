<?php
/**
 * EMERGENCY PASSWORD RESET - Sin tokens
 * POST /api/emergency_reset_password.php
 * Body: { email: string, new_password: string }
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data || empty($data['email']) || empty($data['new_password'])) {
        jsonError('Email y nueva contraseña requeridos', 400);
    }

    $email = sanitizeInput($data['email']);
    $newPassword = $data['new_password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonError('Formato de email inválido', 400);
    }

    if (strlen($newPassword) < 8) {
        jsonError('La contraseña debe tener al menos 8 caracteres', 400);
    }

    $pdo = getDBConnection();

    // Get user by email
    $stmt = $pdo->prepare("SELECT id, email FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonError('Email no encontrado', 400);
    }

    // Hash the new password
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update user password
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$passwordHash, $user['id']]);

    jsonSuccess([
        'message' => 'Contraseña restablecida correctamente',
        'email' => $email,
        'next_step' => 'Puedes iniciar sesión con tu nueva contraseña'
    ], 'Contraseña actualizada con éxito');

} catch (PDOException $e) {
    error_log('emergency_reset_password.php Error: ' . $e->getMessage());
    jsonError('Error al restablecer la contraseña: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    error_log('emergency_reset_password.php Error: ' . $e->getMessage());
    jsonError('Error: ' . $e->getMessage(), 500);
}
