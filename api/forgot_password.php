<?php
/**
 * API: Password Recovery System
 * POST /api/forgot_password.php
 * Body: { email: string }
 * Sends password reset email with secure token
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data || empty($data['email'])) {
        jsonError('Email requerido', 400);
    }

    $email = sanitizeInput($data['email']);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonError('Formato de email inválido', 400);
    }

    $pdo = getDBConnection();

    // Create password_reset_tokens table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token_hash VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        is_used TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX (token_hash),
        INDEX (expires_at),
        INDEX (is_used)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, first_name, email FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        // Don't reveal whether email exists for security
        jsonSuccess([], 'Si el email está registrado, se ha enviado un correo de recuperación');
        exit;
    }

    // Create password reset token (valid for 24 hours)
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

    // Store token in database
    $stmt = $pdo->prepare("
        INSERT INTO password_reset_tokens
        (user_id, token_hash, expires_at, is_used)
        VALUES (?, ?, ?, FALSE)
        ON DUPLICATE KEY UPDATE
        token_hash = VALUES(token_hash),
        expires_at = VALUES(expires_at),
        is_used = FALSE
    ");
    $stmt->execute([$user['id'], $tokenHash, $expiresAt]);

    // Create reset link
    $resetLink = "https://www.rutasrurales.io/reset_password.html?token=$token&email=" . urlencode($email);

    // Email content
    $subject = "Recuperación de Contraseña - Rutas Rurales";
    $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #2F5233; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .button { display: inline-block; padding: 12px 24px; background-color: #2F5233; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
                .footer { margin-top: 20px; font-size: 12px; color: #666; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Recuperación de Contraseña</h2>
                </div>
                <div class='content'>
                    <p>Hola " . htmlspecialchars($user['first_name']) . ",</p>
                    <p>Hemos recibido una solicitud para restablecer tu contraseña en Rutas Rurales.</p>
                    <p>Si no has solicitado este cambio, puedes ignorar este correo electrónico.</p>
                    <p>Para restablecer tu contraseña, haz clic en el siguiente botón:</p>
                    <p style='text-align: center; margin: 20px 0;'>
                        <a href='$resetLink' class='button'>Restablecer Contraseña</a>
                    </p>
                    <p>O copia y pega este enlace en tu navegador:</p>
                    <p><a href='$resetLink'>$resetLink</a></p>
                    <p>Este enlace expirará en 1 hora por motivos de seguridad.</p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " Rutas Rurales. Todos los derechos reservados.</p>
                    <p>Si tienes problemas, contacta con nuestro soporte: olgamarin@rutasrurales.io</p>
                </div>
            </div>
        </body>
        </html>
    ";

    // Send email
    $headers = "From: Rutas Rurales <noreply@rutasrurales.io>\r\n";
    $headers .= "Reply-To: soporte@rutasrurales.io\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    // In production, you would use a proper email service like SendGrid, Mailgun, etc.
    // For now, we'll use the basic mail() function
    $mailSent = mail($email, $subject, $message, $headers);

    // For development/testing: log the reset link to error log
    error_log("Password reset link for $email: $resetLink");

    if ($mailSent) {
        jsonSuccess([
            'message' => 'Se ha enviado un correo de recuperación a tu dirección de email',
            'next_steps' => [
                '1. Revisa tu bandeja de entrada (y spam)',
                '2. Haz clic en el enlace de recuperación',
                '3. Establece una nueva contraseña segura'
            ],
            // For development: return the token so it can be used directly
            'debug_token' => $token,
            'debug_reset_link' => $resetLink
        ], 'Correo de recuperación enviado');
    } else {
        // Even if email fails, don't reveal this to prevent email enumeration
        jsonSuccess([
            'message' => 'Si el email está registrado, se ha enviado un correo de recuperación',
            // For development: return the token so it can be used directly
            'debug_token' => $token,
            'debug_reset_link' => $resetLink
        ], 'Solicitud procesada');
    }

} catch (PDOException $e) {
    error_log('forgot_password.php Error: ' . $e->getMessage());
    jsonError('Error al procesar la solicitud', 500);
} catch (Exception $e) {
    error_log('forgot_password.php Error: ' . $e->getMessage());
    jsonError('Error al procesar la solicitud', 500);
}