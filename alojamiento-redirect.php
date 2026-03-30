<?php
/**
 * Redirección de alojamiento.html?id=XXX a detalle.html?slug=XXX
 */

$id = $_GET[0] ?? '';

if (!$id) {
    header('Location: /alojamientos-turisticos.html', true, 301);
    exit;
}

// Buscar el slug en accommodations.json
$jsonFile = __DIR__ . '/accommodations.json';
if (file_exists($jsonFile)) {
    $data = json_decode(file_get_contents($jsonFile), true);
    
    foreach ($data as $alojamiento) {
        if (isset($alojamiento['id']) && $alojamiento['id'] == $id) {
            $nombre = $alojamiento['name'] ?? $alojamiento['Nombre'] ?? '';
            
            // Generar slug
            $slug = strtolower($nombre);
            $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
            $slug = preg_replace('/\s+/', '-', $slug);
            $slug = trim($slug, '-');
            
            if ($slug) {
                header('Location: /detalle.html?slug=' . $slug, true, 301);
                exit;
            }
        }
    }
}

// Si no encuentra el alojamiento, redirigir al listado
header('Location: /alojamientos-turisticos.html', true, 301);
exit;
