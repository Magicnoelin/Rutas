<?php
/**
 * API: Password Recovery System
 * POST /api/forgot_password.php
 * Body: { email: string }
 * Sends password reset email with secure token
 */

require_once 'config.php';

// Cargar PHPMailer si existe (composer)
$phpmailerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($phpmailerAutoload)) {
    require_once $phpmailerAutoload;
}

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

    // Invalidate previous tokens for this user
    $pdo->prepare("UPDATE password_reset_tokens SET is_used = 1 WHERE user_id = ? AND is_used = 0")
        ->execute([$user['id']]);

    // Store new token in database
    $stmt = $pdo->prepare("
        INSERT INTO password_reset_tokens
        (user_id, token_hash, expires_at, is_used)
        VALUES (?, ?, ?, FALSE)
    ");
    $stmt->execute([$user['id'], $tokenHash, $expiresAt]);

    // Create reset link
    $resetLink = "https://rutasrurales.io/reset_password.html?token=$token&email=" . urlencode($email);

    // Email content
    $subject = "Recuperación de Contraseña - Rutas Rurales";
    $firstName = htmlspecialchars($user['first_name']);
    $year = date('Y');

    $htmlMessage = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #2F5233; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .button { display: inline-block; padding: 12px 24px; background-color: #2F5233; color: white !important; text-decoration: none; border-radius: 5px; font-weight: bold; }
                .footer { margin-top: 20px; font-size: 12px; color: #666; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>🔑 Recuperación de Contraseña</h2>
                </div>
                <div class='content'>
                    <p>Hola <strong>$firstName</strong>,</p>
                    <p>Hemos recibido una solicitud para restablecer tu contraseña en <strong>Rutas Rurales</strong>.</p>
                    <p>Si no has solicitado este cambio, puedes ignorar este correo electrónico con total seguridad.</p>
                    <p>Para restablecer tu contraseña, haz clic en el siguiente botón:</p>
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='$resetLink' class='button'>🔐 Restablecer Contraseña</a>
                    </p>
                    <p>O copia y pega este enlace en tu navegador:</p>
                    <p style='word-break: break-all;'><a href='$resetLink'>$resetLink</a></p>
                    <p><em>⏱️ Este enlace expirará en <strong>24 horas</strong> por motivos de seguridad.</em></p>
                </div>
                <div class='footer'>
                    <p>© $year Rutas Rurales. Todos los derechos reservados.</p>
                    <p>Si tienes problemas, contacta con soporte: <a href='mailto:hola@rutasrurales.io'>hola@rutasrurales.io</a></p>
                </div>
            </div>
        </body>
        </html>
    ";

    $textMessage = "Hola $firstName,\n\n"
        . "Hemos recibido una solicitud para restablecer tu contraseña en Rutas Rurales.\n\n"
        . "Para restablecer tu contraseña, visita el siguiente enlace:\n"
        . "$resetLink\n\n"
        . "Este enlace expirará en 24 horas.\n\n"
        . "Si no solicitaste este cambio, ignora este correo.\n\n"
        . "© $year Rutas Rurales";

    // Log the reset link for debugging (server logs)
    error_log("forgot_password.php: Reset link for $email: $resetLink");

    // ── Enviar email con PHPMailer + SMTP ──────────────────────
    $mailSent = false;
    $mailError = '';

    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        try {
            // Cargar configuración SMTP desde BD (tabla config_smtp)
            $smtpConfig = null;
            try {
                $stmtSmtp = $pdo->query("SELECT * FROM config_smtp WHERE activo = 1 LIMIT 1");
                $smtpConfig = $stmtSmtp->fetch();
            } catch (Exception $e) {
                // Tabla no existe aún, usar valores por defecto de Hostinger
                error_log("forgot_password.php: config_smtp no disponible, usando defaults: " . $e->getMessage());
            }

            // Credenciales SMTP: primero BD, luego constantes de config.php
            $smtpHost     = $smtpConfig['host']       ?? SMTP_HOST;
            $smtpUser     = $smtpConfig['usuario']    ?? SMTP_USER;
            $smtpPass     = $smtpConfig['password']   ?? SMTP_PASS;
            $smtpSec      = $smtpConfig['seguridad']  ?? SMTP_SECURE;
            $smtpPort     = intval($smtpConfig['puerto'] ?? SMTP_PORT);
            $fromEmail    = $smtpConfig['email_from']  ?? SMTP_FROM_EMAIL;
            $fromName     = $smtpConfig['nombre_from'] ?? SMTP_FROM_NAME;

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->CharSet   = 'UTF-8';
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUser;
            $mail->Password   = $smtpPass;
            $mail->SMTPSecure = $smtpSec;
            $mail->Port       = $smtpPort;
            $mail->SMTPDebug  = 0;

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($email, $firstName);
            $mail->addReplyTo('hola@rutasrurales.io', 'Rutas Rurales');

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlMessage;
            $mail->AltBody = $textMessage;

            $mail->send();
            $mailSent = true;
            error_log("forgot_password.php: Email enviado vía PHPMailer/SMTP a $email");

        } catch (Exception $e) {
            $mailError = $e->getMessage();
            error_log("forgot_password.php: PHPMailer error: $mailError — intentando mail() nativo");

            // Fallback a mail() nativo si SMTP falla
            $headers  = "From: Rutas Rurales <noreply@rutasrurales.io>\r\n";
            $headers .= "Reply-To: hola@rutasrurales.io\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();
            $mailSent = mail($email, $subject, $htmlMessage, $headers);

            if ($mailSent) {
                error_log("forgot_password.php: Email enviado vía mail() nativo (fallback) a $email");
            } else {
                error_log("forgot_password.php: mail() nativo también falló para $email");
            }
        }
    } else {
        // PHPMailer no disponible: usar mail() nativo
        error_log("forgot_password.php: PHPMailer no disponible, usando mail() nativo");
        $headers  = "From: Rutas Rurales <noreply@rutasrurales.io>\r\n";
        $headers .= "Reply-To: hola@rutasrurales.io\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        $mailSent = mail($email, $subject, $htmlMessage, $headers);
    }

    // ── Respuesta al cliente ───────────────────────────────────
    if ($mailSent) {
        jsonSuccess([
            'message' => 'Se ha enviado un correo de recuperación a tu dirección de email',
            'next_steps' => [
                '1. Revisa tu bandeja de entrada (y carpeta de spam)',
                '2. Haz clic en el enlace de recuperación',
                '3. Establece una nueva contraseña segura'
            ]
        ], 'Correo de recuperación enviado');
    } else {
        // El token fue creado pero el email falló
        // Devolver el enlace en la respuesta para que el frontend lo muestre (como ya hacía antes)
        error_log("forgot_password.php: No se pudo enviar el email a $email. Error: $mailError");
        jsonSuccess([
            'message' => 'Si el email está registrado, se ha enviado un correo de recuperación',
            'reset_link' => $resetLink  // Mostrado en el frontend como fallback
        ], 'Solicitud procesada');
    }

} catch (PDOException $e) {
    error_log('forgot_password.php PDO Error: ' . $e->getMessage());
    jsonError('Error al procesar la solicitud', 500);
} catch (Exception $e) {
    error_log('forgot_password.php Error: ' . $e->getMessage());
    jsonError('Error al procesar la solicitud', 500);
}
