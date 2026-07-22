<?php
// enviar_mail_ajax.php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $asunto = trim($_POST['asunto'] ?? '');
    $mensaje = trim($_POST['mensaje'] ?? '');

    if (!$email) {
        echo json_encode(['status' => 'error', 'message' => 'El correo electrónico no es válido.']);
        exit;
    }

    if (empty($asunto) || empty($mensaje)) {
        echo json_encode(['status' => 'error', 'message' => 'Por favor, rellena todos los campos.']);
        exit;
    }

    // Cabeceras para enviar correos en formato HTML y con codificación UTF-8
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Rutas Rurales <no-reply@rutasrurales.io>" . "\r\n"; // Ajusta tu remitente real aquí

    // Cuerpo del mensaje formateado en HTML limpio
    $cuerpoHtml = "
    <html>
    <head>
        <title>" . htmlspecialchars($asunto) . "</title>
    </head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px;'>
            <h2 style='color: #0d6efd;'>Rutas Rurales</h2>
            <hr style='border: 0; border-top: 1px solid #eee; margin-bottom: 20px;'>
            <p>" . nl2br(htmlspecialchars($mensaje)) . "</p>
            <hr style='border: 0; border-top: 1px solid #eee; margin-top: 20px;'>
            <small style='color: #777;'>Este es un mensaje enviado automáticamente desde el panel de administración.</small>
        </div>
    </body>
    </html>
    ";

    // Intentar enviar el correo
    if (mail($email, $asunto, $cuerpoHtml, $headers)) {
        echo json_encode(['status' => 'success', 'message' => '¡Correo enviado con éxito a ' . $email . '!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se pudo enviar el correo. Revisa la configuración del servidor de correo.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método de petición no permitido.']);
}