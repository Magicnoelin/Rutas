<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '/home/u412199647/domains/rutasrurales.io/public_html/admin_tablas/vendor/autoload.php';// Asegúrate de que la ruta a PHPMailer sea correcta

// 1. Conexión a la base de datos
$host = "localhost";
$db   = "u412199647_Rutas";
$user = "u412199647_olgamarin";
$pass = "Rutas5Rurales7$";

// 2. Consultar clientes que aún no han autorizado
$resultado = $conexion->query("SELECT id, nombre, email, meeting_point FROM clientes WHERE estado_autorizacion = 0");

while ($cliente = $resultado->fetch_assoc()) {
    
    // Generar un token único para este cliente
    $token = bin2hex(random_bytes(16));
    $conexion->query("UPDATE clientes SET token_confirmacion = '$token' WHERE id = " . $cliente['id']);

    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor (Usa los datos de tu correo de Hostinger)
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'tu-correo@tuweb.com'; 
        $mail->Password   = 'tu-password-seguro';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Destinatario
        $mail->setFrom('tu-correo@tuweb.com', 'Tu Nombre de Empresa');
        $mail->addAddress($cliente['email'], $cliente['nombre']);

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = 'Accion requerida: Autoriza la publicacion de tu actividad';
        
        // El enlace mágico al Paso 2
        $enlace = "https://tuweb.com/confirmar.php?id=" . $cliente['id'] . "&token=" . $token;

        $mail->Body = "
            <h2>Hola {$cliente['nombre']},</h2>
            <p>Por favor, revisa que los datos de tu actividad sean correctos:</p>
            <ul>
                <li><strong>Punto de encuentro:</strong> {$cliente['meeting_point']}</li>
            </ul>
            <p>Si todo está bien, haz clic en el siguiente botón para autorizar la publicación en nuestra web:</p>
            <a href='{$enlace}' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>CONFIRMAR Y AUTORIZAR</a>
            <p>Si hay algún error, responde a este correo.</p>
        ";

        $mail->send();
        echo "Correo enviado a: " . $cliente['email'] . "<br>";

    } catch (Exception $e) {
        echo "Error al enviar a {$cliente['email']}: {$mail->ErrorInfo}<br>";
    }
}
?>