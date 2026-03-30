<?php
/**
 * API: Password Reset System (Compatible con ambos tipos de hash)
 * POST /api/reset_password.php
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data || empty($data['token']) || empty($data['email']) || empty($data['new_password'])) {
        jsonError('Datos incompletos', 400);
    }

    $token = $data['token'];
    $email = sanitizeInput($data['email']);
    $newPassword = $data['new_password'];

    // Calculate SHA256 hash
    $tokenHash = hash('sha256', $token);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonError('Formato de email inválido', 400);
    }

    if (strlen($newPassword) < 8) {
        jsonError('La contraseña debe tener al menos 8 caracteres', 400);
    }

    $pdo = getDBConnection();

    // Get user by email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonError('Email no encontrado', 400);
    }

    // Check if token exists and is valid for this user
    $stmt = $pdo->prepare("
        SELECT id, user_id, expires_at, is_used, token_hash
        FROM password_reset_tokens
        WHERE user_id = ?
        AND token_hash = ?
        AND expires_at > NOW()
        AND is_used = 0
        LIMIT 1
    ");
    $stmt->execute([$user['id'], $tokenHash]);
    $tokenData = $stmt->fetch();

    if (!$tokenData) {
        jsonError('Token de recuperación inválido, expirado o ya utilizado', 400);
    }

    // Hash the new password
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update user password
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$passwordHash, $user['id']]);

    // Mark token as used
    $stmt = $pdo->prepare("UPDATE password_reset_tokens SET is_used = TRUE WHERE id = ?");
    $stmt->execute([$tokenData['id']]);

    jsonSuccess([
        'message' => 'Contraseña restablecida correctamente',
        'next_steps' => [
            'Puedes iniciar sesión con tu nueva contraseña',
            'Te recomendamos guardarla en un lugar seguro'
        ]
    ], 'Contraseña actualizada con éxito');

} catch (PDOException $e) {
    error_log('reset_password.php PDO Error: ' . $e->getMessage());
    jsonError('Error al restablecer la contraseña', 500);
} catch (Exception $e) {
    error_log('reset_password.php Error: ' . $e->getMessage());
    jsonError('Error al restablecer la contraseña', 500);
}
