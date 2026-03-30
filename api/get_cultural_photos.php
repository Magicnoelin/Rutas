<?php
header('Content-Type: application/json');

$slug = $_GET['slug'] ?? '';

if (!$slug) {
    echo json_encode(['success' => false, 'photos' => []]);
    exit;
}

// La misma ruta que pusiste en el archivo de subida
$directory = $_SERVER['DOCUMENT_ROOT'] . '/img/eventos-culturales/' . $slug . '/';
$web_path = '/img/eventos-culturales/' . $slug . '/';
$photos = [];

if (is_dir($directory)) {
    // Escaneamos la carpeta buscando archivos .webp
    $files = glob($directory . "*.webp");
    foreach ($files as $file) {
        $photos[] = $web_path . basename($file);
    }
}

echo json_encode(['success' => true, 'photos' => $photos]);