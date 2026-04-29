<?php
/**
 * API: Solicitud de inscripción gestionada por el equipo
 * POST /rutas-del-vino/api/contacto-bodega.php
 *
 * Cuando el bodeguero quiere que lo hagamos nosotros:
 * - Guarda los datos en el log
 * - Envía email de notificación a olgamarin@rutasrurales.io
 * - Envía email de confirmación al bodeguero
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://rutasrurales.io');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }

    // Sanitizar campos
    $nombre     = isset($data['nombre'])      ? htmlspecialchars(trim($data['nombre']), ENT_QUOTES)      : '';
    $contacto   = isset($data['contacto'])    ? htmlspecialchars(trim($data['contacto']), ENT_QUOTES)    : '';
    $email      = isset($data['email'])       ? filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL)  : '';
    $telefono   = isset($data['telefono'])    ? htmlspecialchars(trim($data['telefono']), ENT_QUOTES)    : '';
    $info       = isset($data['info'])        ? htmlspecialchars(trim($data['info']), ENT_QUOTES)        : '';
    $descripcion = isset($data['descripcion']) ? htmlspecialchars(trim($data['descripcion']), ENT_QUOTES) : '';

    if (!$nombre || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nombre y email son obligatorios']);
        exit;
    }

    $fecha = date('Y-m-d H:i:s');

    // ─── LOG INTERNO ──────────────────────────────────────────
    $logEntry = str_repeat('-', 60) . PHP_EOL
        . "FECHA:        {$fecha}" . PHP_EOL
        . "TIPO:         BODEGA_SOLICITUD_EQUIPO" . PHP_EOL
        . "BODEGA:       {$nombre}" . PHP_EOL
        . "CONTACTO:     {$contacto}" . PHP_EOL
        . "EMAIL:        {$email}" . PHP_EOL
        . "TELÉFONO:     {$telefono}" . PHP_EOL
        . "INFO/WEB:     {$info}" . PHP_EOL
        . "DESCRIPCIÓN:  {$descripcion}" . PHP_EOL
        . str_repeat('-', 60) . PHP_EOL . PHP_EOL;

    $logDir  = dirname(__DIR__) . '/logs';
    $logFile = $logDir . '/solicitudes-equipo.log';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

    // ─── EMAIL AL EQUIPO ──────────────────────────────────────
    $adminEmail = 'olgamarin@rutasrurales.io';
    $adminSubject = "🍷 Nueva solicitud de bodega: {$nombre}";
    $adminBody = "
<html><body style='font-family: Arial, sans-serif; color: #333;'>
<div style='max-width:600px; margin:0 auto; background:#fdfaf5; border-radius:12px; overflow:hidden;'>
    <div style='background: linear-gradient(135deg,#4A1820,#722F37); padding:2rem; text-align:center;'>
        <h1 style='color:#E8C97A; margin:0; font-size:1.5rem;'>🍷 Nueva Bodega — Solicitud</h1>
        <p style='color:rgba(255,255,255,0.8); margin:0.5rem 0 0;'>Las Rutas del Vino · rutasrurales.io</p>
    </div>
    <div style='padding:2rem;'>
        <table style='width:100%; border-collapse:collapse;'>
            <tr><td style='padding:0.6rem 0; border-bottom:1px solid #ede5d4; font-weight:700; color:#722F37; width:35%;'>Bodega</td>
                <td style='padding:0.6rem 0; border-bottom:1px solid #ede5d4;'>{$nombre}</td></tr>
            <tr><td style='padding:0.6rem 0; border-bottom:1px solid #ede5d4; font-weight:700; color:#722F37;'>Contacto</td>
                <td style='padding:0.6rem 0; border-bottom:1px solid #ede5d4;'>{$contacto}</td></tr>
            <tr><td style='padding:0.6rem 0; border-bottom:1px solid #ede5d4; font-weight:700; color:#722F37;'>Email</td>
                <td style='padding:0.6rem 0; border-bottom:1px solid #ede5d4;'><a href='mailto:{$email}'>{$email}</a></td></tr>
            <tr><td style='padding:0.6rem 0; border-bottom:1px solid #ede5d4; font-weight:700; color:#722F37;'>Teléfono</td>
                <td style='padding:0.6rem 0; border-bottom:1px solid #ede5d4;'><a href='tel:{$telefono}'>{$telefono}</a></td></tr>
            <tr><td style='padding:0.6rem 0; border-bottom:1px solid #ede5d4; font-weight:700; color:#722F37;'>Web/Info</td>
                <td style='padding:0.6rem 0; border-bottom:1px solid #ede5d4;'>{$info}</td></tr>
            <tr><td style='padding:0.6rem 0; font-weight:700; color:#722F37; vertical-align:top;'>Descripción</td>
                <td style='padding:0.6rem 0;'>{$descripcion}</td></tr>
        </table>
        <div style='background:#f5f0e8; border-radius:10px; padding:1rem; margin-top:1.5rem;'>
            <p style='margin:0; font-size:0.85rem; color:#4a4a4a;'>
                <strong>⚡ Acción requerida:</strong> Contactar a la bodega para confirmar datos y enviar link de pago.<br>
                Precio: <strong>10€ IVA incluido</strong>
            </p>
        </div>
        <p style='font-size:0.8rem; color:#999; margin-top:1.5rem;'>Enviado el {$fecha}</p>
    </div>
</div>
</body></html>
";

    $adminHeaders  = "MIME-Version: 1.0\r\n";
    $adminHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";
    $adminHeaders .= "From: Las Rutas del Vino <noreply@rutasrurales.io>\r\n";
    $adminHeaders .= "Reply-To: {$email}\r\n";
    $adminHeaders .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    mail($adminEmail, $adminSubject, $adminBody, $adminHeaders);

    // ─── EMAIL DE CONFIRMACIÓN AL BODEGUERO ──────────────────
    $confirmSubject = "¡Recibida! Tu solicitud para Las Rutas del Vino 🍷";
    $confirmBody = "
<html><body style='font-family: Arial, sans-serif; color: #333;'>
<div style='max-width:600px; margin:0 auto; background:#fdfaf5; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.1);'>
    <div style='background: linear-gradient(135deg,#4A1820,#722F37); padding:2.5rem 2rem; text-align:center;'>
        <div style='font-size:3rem; margin-bottom:0.5rem;'>🍷</div>
        <h1 style='color:#E8C97A; margin:0; font-family:Georgia,serif; font-size:1.8rem;'>¡Todo listo!</h1>
        <p style='color:rgba(255,255,255,0.8); margin:0.5rem 0 0;'>Hemos recibido tu solicitud</p>
    </div>
    <div style='padding:2rem;'>
        <p style='font-size:1rem; color:#4a4a4a; line-height:1.6;'>
            Hola <strong>{$contacto}</strong>,
        </p>
        <p style='font-size:1rem; color:#4a4a4a; line-height:1.6;'>
            Hemos recibido la solicitud para inscribir <strong style='color:#722F37;'>{$nombre}</strong>
            en el mapa de <strong>Las Rutas del Vino</strong>.
        </p>
        <div style='background:#f5f0e8; border-left:4px solid #C9A84C; padding:1rem 1.5rem; border-radius:0 10px 10px 0; margin:1.5rem 0;'>
            <p style='margin:0; font-size:0.95rem; color:#4a4a4a;'>
                <strong>¿Qué pasa ahora?</strong><br><br>
                1. Revisamos los datos que nos has enviado<br>
                2. Te contactamos por email o teléfono en <strong>menos de 24h</strong><br>
                3. Te enviamos el enlace de pago seguro (10€ IVA incl.)<br>
                4. Confirmado el pago, tu bodega aparece en el mapa en <strong>24-48h</strong>
            </p>
        </div>
        <p style='font-size:0.9rem; color:#4a4a4a;'>
            Si tienes cualquier duda, escríbenos directamente a 
            <a href='mailto:olgamarin@rutasrurales.io' style='color:#722F37;'>olgamarin@rutasrurales.io</a>
            o llámanos al <a href='tel:+34605249696' style='color:#722F37;'>+34 605 249 696</a>.
        </p>
        <div style='text-align:center; margin-top:2rem;'>
            <a href='https://rutasrurales.io/rutas-del-vino/' 
               style='display:inline-block; background:#C9A84C; color:#4A1820; padding:0.8rem 2rem; border-radius:25px; font-weight:700; text-decoration:none;'>
                Ver el mapa de Las Rutas del Vino
            </a>
        </div>
        <p style='font-size:0.75rem; color:#aaa; text-align:center; margin-top:2rem;'>
            rutasrurales.io · Las Rutas del Vino
        </p>
    </div>
</div>
</body></html>
";

    $confirmHeaders  = "MIME-Version: 1.0\r\n";
    $confirmHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";
    $confirmHeaders .= "From: Las Rutas del Vino <noreply@rutasrurales.io>\r\n";
    $confirmHeaders .= "Reply-To: olgamarin@rutasrurales.io\r\n";
    $confirmHeaders .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    mail($email, $confirmSubject, $confirmBody, $confirmHeaders);

    echo json_encode([
        'success' => true,
        'message' => 'Solicitud recibida correctamente. Te contactaremos en menos de 24h.'
    ]);

} catch (Exception $e) {
    error_log('contacto-bodega.php exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>
