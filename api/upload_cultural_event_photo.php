<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$event_slug = $_POST['event_slug'] ?? '';
$category_slug = $_POST['category'] ?? 'general';
$file = $_FILES['photo'] ?? null;

if (!$event_slug || !$file) {
    echo json_encode(['success' => false, 'error' => 'Faltan datos']);
    exit;
}

// 1. Configurar rutas
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/img/eventos-culturales/';
$event_path = $base_path . $event_slug . '/';

if (!file_exists($event_path)) {
    mkdir($event_path, 0755, true);
}

// 2. Calcular el número correlativo (1, 2, 3...)
$existing_files = glob($event_path . "*.webp");
$next_number = count($existing_files) + 1;

// Nombre final: 1.webp, 2.webp, etc.
$filename = $next_number . '.webp';
$destination = $event_path . $filename;

// 3. Procesar imagen original
$temp_file = $file['tmp_name'];
$info = getimagesize($temp_file);
$width = $info[0];
$height = $info[1];

if ($info['mime'] == 'image/jpeg') $src = imagecreatefromjpeg($temp_file);
elseif ($info['mime'] == 'image/png') $src = imagecreatefrompng($temp_file);
else {
    echo json_encode(['success' => false, 'error' => 'Formato no soportado']);
    exit;
}

// 4. Ajuste de tamaño automático (Máximo 1200px de ancho/alto)
$max_size = 1200;
$scale = min($max_size / $width, $max_size / $height);

if ($scale < 1) {
    $new_width = floor($width * $scale);
    $new_height = floor($height * $scale);
} else {
    $new_width = $width;
    $new_height = $height;
}

// Crear lienzo nuevo y redimensionar
$tmp_canvas = imagecreatetruecolor($new_width, $new_height);

// Mantener transparencias si las hay
imagealphablending($tmp_canvas, false);
imagesavealpha($tmp_canvas, true);

imagecopyresampled($tmp_canvas, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

// 5. Guardar como WebP
imagewebp($tmp_canvas, $destination, 80); 

// Limpiar memoria
imagedestroy($src);
imagedestroy($tmp_canvas);

echo json_encode([
    'success' => true,
    'message' => "Foto $next_number guardada correctamente",
    'url' => '/img/eventos-culturales/' . $event_slug . '/' . $filename
]);