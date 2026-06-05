<?php
/**
 * API: Guardar datos formulario hostelería (restaurantes)
 * Recibe los datos del formulario agradecimientos-hosteleria.php,
 * guarda las fotos y envía un email de notificación al equipo.
 */

// Capturar cualquier output previo para que no rompa el JSON
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// ===== CONFIGURACIÓN =====
$NOTIFY_EMAIL   = 'olgamarin@rutasrurales.io';
$UPLOAD_DIR     = __DIR__ . '/../interest_places_images/hosteleria_onboarding/';
$MAX_FILE_SIZE  = 5 * 1024 * 1024; // 5 MB
$ALLOWED_TYPES  = ['image/jpeg', 'image/png', 'image/webp'];
$ALLOWED_EXT    = ['jpg', 'jpeg', 'png', 'webp'];

// ===== RECOGER DATOS =====
function clean($val) {
    return htmlspecialchars(strip_tags(trim($val ?? '')), ENT_QUOTES, 'UTF-8');
}

$email       = clean($_POST['email'] ?? '');
$telefono    = clean($_POST['telefono'] ?? '');
$nombre      = clean($_POST['nombre_contacto'] ?? '');
$apellidos   = clean($_POST['apellidos_contacto'] ?? '');
$restaurante = clean($_POST['nombre_restaurante'] ?? '');
$tipo_cocina = clean($_POST['tipo_cocina'] ?? '');
$precio      = clean($_POST['precio_medio'] ?? '');
$descripcion = clean($_POST['descripcion'] ?? '');
$direccion   = clean($_POST['direccion'] ?? '');
$localidad   = clean($_POST['localidad'] ?? '');
$provincia   = clean($_POST['provincia'] ?? '');
$cp          = clean($_POST['codigo_postal'] ?? '');
$web         = clean($_POST['web'] ?? '');
$otras_car   = clean($_POST['otras_caracteristicas'] ?? '');
$comentarios = clean($_POST['comentarios'] ?? '');

// Validación mínima
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email inválido o vacío']);
    exit;
}

if (empty($telefono)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Teléfono requerido']);
    exit;
}

// Características marcadas
$caracteristicas = [];
if (!empty($_POST['caracteristicas']) && is_array($_POST['caracteristicas'])) {
    $allowed_cars = ['reservas','terraza','privados','bodas','menu_grupos','takeaway',
                     'delivery','acceso_silla','parking','wifi','mascotas','niños',
                     'vegetariano','celiaco','vinos_locales','productos_locales'];
    foreach ($_POST['caracteristicas'] as $c) {
        if (in_array($c, $allowed_cars)) {
            $caracteristicas[] = $c;
        }
    }
}

// Horarios
$horarios = [];
$dias_key = ['lunes','martes','miercoles','jueves','viernes','sabado','domingo'];
foreach ($dias_key as $dia) {
    $cerrado  = !empty($_POST["horario_{$dia}_cerrado"]);
    $apertura = clean($_POST["horario_{$dia}_apertura"] ?? '');
    $cierre   = clean($_POST["horario_{$dia}_cierre"] ?? '');
    if ($cerrado || !empty($apertura) || !empty($cierre)) {
        $horarios[$dia] = [
            'cerrado'  => $cerrado,
            'apertura' => $apertura,
            'cierre'   => $cierre,
        ];
    }
}

// ===== GUARDAR FOTOS =====
if (!is_dir($UPLOAD_DIR)) {
    mkdir($UPLOAD_DIR, 0755, true);
}

$fotos_guardadas = [];
$timestamp = date('Ymd_His');
$email_slug = preg_replace('/[^a-z0-9]/', '_', strtolower($email));

for ($i = 1; $i <= 4; $i++) {
    if (empty($_FILES["foto{$i}"]["tmp_name"])) continue;
    $file = $_FILES["foto{$i}"];

    if ($file['error'] !== UPLOAD_ERR_OK) continue;
    if ($file['size'] > $MAX_FILE_SIZE) continue;

    // Validar MIME
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $ALLOWED_TYPES)) continue;

    // Extensión
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $ALLOWED_EXT)) continue;

    $filename = "rest_{$email_slug}_{$timestamp}_foto{$i}.{$ext}";
    $dest     = $UPLOAD_DIR . $filename;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        $fotos_guardadas[] = $filename;
    }
}

// ===== GUARDAR EN LOG JSON =====
$log_file = __DIR__ . '/../interest_places_images/hosteleria_onboarding/submissions_log.json';
$entry = [
    'fecha'          => date('Y-m-d H:i:s'),
    'email'          => $email,
    'telefono'       => $telefono,
    'nombre'         => $nombre,
    'apellidos'      => $apellidos,
    'restaurante'    => $restaurante,
    'tipo_cocina'    => $tipo_cocina,
    'precio_medio'   => $precio,
    'descripcion'    => $descripcion,
    'direccion'      => $direccion,
    'localidad'      => $localidad,
    'provincia'      => $provincia,
    'codigo_postal'  => $cp,
    'web'            => $web,
    'caracteristicas'=> $caracteristicas,
    'otras_car'      => $otras_car,
    'horarios'       => $horarios,
    'comentarios'    => $comentarios,
    'fotos'          => $fotos_guardadas,
    'ip'             => $_SERVER['REMOTE_ADDR'] ?? '',
];

$log_data = [];
if (file_exists($log_file)) {
    $existing = file_get_contents($log_file);
    $log_data = json_decode($existing, true) ?: [];
}
$log_data[] = $entry;
file_put_contents($log_file, json_encode($log_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// ===== ENVIAR EMAIL DE NOTIFICACIÓN =====
$nombre_completo = trim("$nombre $apellidos") ?: 'Sin especificar';
$cars_str = !empty($caracteristicas) ? implode(', ', $caracteristicas) : 'Ninguna seleccionada';
$fotos_str = !empty($fotos_guardadas)
    ? count($fotos_guardadas) . ' foto(s) subida(s): ' . implode(', ', $fotos_guardadas)
    : 'Sin fotos';

// Horarios formateados
$horarios_str = '';
if (!empty($horarios)) {
    $dias_nombres = [
        'lunes' => 'Lunes', 'martes' => 'Martes', 'miercoles' => 'Miércoles',
        'jueves' => 'Jueves', 'viernes' => 'Viernes', 'sabado' => 'Sábado', 'domingo' => 'Domingo'
    ];
    foreach ($horarios as $d => $h) {
        $nom = $dias_nombres[$d] ?? $d;
        if ($h['cerrado']) {
            $horarios_str .= "  {$nom}: CERRADO\n";
        } else {
            $horarios_str .= "  {$nom}: {$h['apertura']} – {$h['cierre']}\n";
        }
    }
} else {
    $horarios_str = '  No especificados';
}

$subject = "🍽️ Nuevo restaurante registrado: " . ($restaurante ?: $email);

$body = <<<EOT
¡Nuevo restaurante registrado en Rutas Rurales!
================================================

DATOS DE CONTACTO
-----------------
Nombre:       {$nombre_completo}
Email:        {$email}
Teléfono:     {$telefono}

DATOS DEL RESTAURANTE
---------------------
Nombre:       {$restaurante}
Tipo cocina:  {$tipo_cocina}
Precio medio: {$precio}
Web:          {$web}

UBICACIÓN
---------
Dirección:    {$direccion}
Localidad:    {$localidad}
Provincia:    {$provincia}
Cod. Postal:  {$cp}

DESCRIPCIÓN
-----------
{$descripcion}

CARACTERÍSTICAS
---------------
{$cars_str}
Otras: {$otras_car}

HORARIOS
--------
{$horarios_str}

FOTOS
-----
{$fotos_str}

COMENTARIOS ADICIONALES
-----------------------
{$comentarios}

--
Enviado desde: agradecimientos-hosteleria.php
Fecha: {$entry['fecha']}
IP: {$entry['ip']}
EOT;

$headers  = "From: noreply@rutasrurales.io\r\n";
$headers .= "Reply-To: {$email}\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

$mail_sent = mail($NOTIFY_EMAIL, $subject, $body, $headers);

// ===== RESPUESTA =====
// Limpiar cualquier output previo (warnings, notices, etc.) que rompería el JSON
ob_end_clean();
header('Content-Type: application/json; charset=UTF-8');
echo json_encode([
    'success'    => true,
    'message'    => '¡Gracias! Hemos recibido los datos de tu restaurante.',
    'fotos'      => count($fotos_guardadas),
    'mail_sent'  => $mail_sent,
]);
exit;
