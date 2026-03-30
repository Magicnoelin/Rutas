<?php
// Aumentar el tiempo de ejecución y la memoria
set_time_limit(300);
ini_set('memory_limit', '512M');

// 1. Cargas
require_once '../vendor/dompdf/autoload.inc.php';
require_once '../vendor/phpmailer/src/Exception.php';
require_once '../vendor/phpmailer/src/PHPMailer.php';
require_once '../vendor/phpmailer/src/SMTP.php';

// 2. Nombres de espacio
use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- Configuración de base de datos ---
$host = "localhost";
$db   = "u412199647_Rutas";
$user = "u412199647_olgamarin";
$pass = "Rutas5Rurales7$";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// --- ID de suscripción / factura ---
$subscription_id = 1; 
$invoice_id = null;

// --- Obtener datos ---
$sql = "
SELECT s.id as subscription_id, u.email, bp.legal_name, bp.tax_id, 
       bc.id as concept_id, bc.concept_name, bc.description, bc.amount, bc.billing_type
FROM subscriptions s
JOIN billing_profiles bp ON bp.id = s.billing_profile_id
JOIN billing_concepts bc ON bc.id = s.billing_concept_id
JOIN users u ON u.id = bp.user_id
WHERE s.id = $subscription_id
";
$result = $conn->query($sql);
if ($result->num_rows == 0) die("Suscripción no encontrada");

$sub = $result->fetch_assoc();

// --- Lógica de Factura ---
$invoice_number = 'RR-' . date('Y') . '-' . str_pad(rand(1,9999),4,'0',STR_PAD_LEFT);
$invoice_date = date('d/m/Y');
$due_date = date('d/m/Y', strtotime('+15 days'));
$subtotal = $sub['amount'];
$tax_rate = 21;
$tax_amount = round($subtotal*($tax_rate/100),2);
$total = round($subtotal+$tax_amount,2);

// Guardar en DB (usamos formato Y-m-d para la base de datos)
$db_date = date('Y-m-d');
$db_due = date('Y-m-d', strtotime('+15 days'));

$conn->query("
INSERT INTO invoices
(invoice_number, customer_name, customer_tax_id, customer_email, invoice_date, due_date, subtotal, tax_rate, tax_amount, total, status, created_at)
VALUES
('$invoice_number','{$sub['legal_name']}','{$sub['tax_id']}','{$sub['email']}','$db_date','$db_due','$subtotal','$tax_rate','$tax_amount','$total','draft',NOW())
");

$invoice_id = $conn->insert_id;

$conn->query("
INSERT INTO invoice_items
(invoice_id, concept_code, concept_name, description, quantity, unit_price, line_total, billing_type, subscription_id)
VALUES
($invoice_id,'{$sub['concept_id']}','{$sub['concept_name']}','{$sub['description']}',1,'{$sub['amount']}','{$sub['amount']}','{$sub['billing_type']}',$subscription_id)
");

// --- GENERAR HTML CON NUEVO DISEÑO ---
$items_result = $conn->query("SELECT * FROM invoice_items WHERE invoice_id=$invoice_id");

$html = '
<html>
<head>
<style>
    body { font-family: Helvetica, Arial, sans-serif; color: #444; line-height: 1.5; }
    .header-table { width: 100%; border-bottom: 2px solid #246634 ; margin-bottom: 20px; padding-bottom: 10px; }
    .invoice-title { font-size: 28px; color: #246634 ; text-transform: uppercase; }
    .info-table { width: 100%; margin-bottom: 30px; }
    .info-table td { vertical-align: top; width: 50%; }
    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
    .items-table th { background-color:#246634 ; color: white; padding: 10px; text-align: left; }
    .items-table td { padding: 10px; border-bottom: 1px solid #eee; }
    .totals-table { width: 100%; }
    .totals-table td { text-align: right; padding: 5px 0; }
    .total-row { font-size: 18px; font-weight: bold; color: #246634 ; }
    .footer { text-align: center; font-size: 10px; color: #999; margin-top: 50px; border-top: 1px solid #eee; padding-top: 10px; }
</style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td><span class="invoice-title">Factura</span></td>
            <td style="text-align: right;"><strong>'.$invoice_number.'</strong></td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td>
                <strong>Emisor:</strong><br>
                RutasRurales.io<br>
                Email: olgamarin@rutasrurales.io
            </td>
            <td style="text-align: right;">
                <strong>Cliente:</strong><br>
                '.$sub['legal_name'].'<br>
                Tax ID: '.$sub['tax_id'].'<br>
                '.$sub['email'].'
            </td>
        </tr>
    </table>

    <p><strong>Fecha de emisión:</strong> '.$invoice_date.'<br>
    <strong>Fecha de vencimiento:</strong> '.$due_date.'</p>

    <table class="items-table">
        <thead>
            <tr>
                <th>Concepto</th>
                <th style="text-align: center;">Cant.</th>
                <th style="text-align: right;">Precio</th>
                <th style="text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>';

while($item = $items_result->fetch_assoc()) {
    $html .= '
        <tr>
            <td>'.$item['concept_name'].'</td>
            <td style="text-align: center;">'.$item['quantity'].'</td>
            <td style="text-align: right;">'.number_format($item['unit_price'], 2).' €</td>
            <td style="text-align: right;">'.number_format($item['line_total'], 2).' €</td>
        </tr>';
}

$html .= '
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td style="width: 80%;">Subtotal:</td>
            <td style="width: 20%;">'.number_format($subtotal, 2).' €</td>
        </tr>
        <tr>
            <td>IVA (21%):</td>
            <td>'.number_format($tax_amount, 2).' €</td>
        </tr>
        <tr class="total-row">
            <td>TOTAL:</td>
            <td>'.number_format($total, 2).' €</td>
        </tr>
    </table>

    <div class="footer">
        Este documento es una factura oficial de RutasRurales.io.<br>
        Gracias por su confianza.
    </div>

</body>
</html>';

// --- Procesar PDF ---
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4','portrait');
$dompdf->render();

$pdf_path = __DIR__ . '/facturas/Factura_'.$invoice_number.'.pdf';
file_put_contents($pdf_path, $dompdf->output());

// --- Enviar email ---
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.hostinger.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'olgamarin@rutasrurales.io';
    $mail->Password   = 'Rutas5Rurales7$';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    $mail->setFrom('olgamarin@rutasrurales.io', 'RutasRurales.io');
    $mail->addAddress($sub['email'], $sub['legal_name']);

    $mail->isHTML(true);
    $mail->Subject = "Factura $invoice_number - RutasRurales.io";
    $mail->Body    = "Hola <b>{$sub['legal_name']}</b>,<br><br>Adjunto encontrará su factura correspondiente a la suscripción en RutasRurales.io.<br><br>Saludos cordiales.";
    
    $mail->addAttachment($pdf_path);

    $mail->send();
    echo "<h1>¡Éxito!</h1><p>La factura <b>$invoice_number</b> ha sido generada y enviada a <b>{$sub['email']}</b>.</p>";
} catch (Exception $e) {
    echo "Error al enviar el email: " . $mail->ErrorInfo;
}

// --- Actualizar próxima facturación ---
$conn->query("UPDATE subscriptions SET next_billing_date = DATE_ADD(next_billing_date, INTERVAL 1 MONTH) WHERE id=$subscription_id");

$conn->close();