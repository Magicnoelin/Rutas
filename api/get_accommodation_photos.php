<?php
/**
 * API Endpoint: Obtener fotos de un alojamiento desde su carpeta
 * GET /api/get_accommodation_photos.php?slug=nombre-alojamiento
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Método no permitido', 405);
}

$slug = isset($_GET['slug']) ? sanitizeInput($_GET['slug']) : '';

if (empty($slug)) {
    jsonError('Slug requerido', 400);
}

try {
    // Validar que el alojamiento existe (seguridad)
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, name FROM accommodations WHERE slug = ? LIMIT 1");
    $stmt->execute([$slug]);
    $accommodation = $stmt->fetch();

    if (!$accommodation) {
        jsonError('Alojamiento no encontrado', 404);
    }

    // Ruta de la carpeta de imágenes
    $baseDir = '/img/alojamientos/' . $slug . '/';
    $serverDir = __DIR__ . '/..' . $baseDir;
    
    $photosByCategory = [];
    
    // Mapeo de prefijos a nombres legibles (debe coincidir con upload_accommodation_photo.php)
    $categoryMap = [
        'salon' => 'Salón',
        'cocina' => 'Cocina',
        'jardin' => 'Jardín',
        'habitacion' => 'Habitación',
        'bano' => 'Baño',
        'exterior' => 'Exterior',
        'piscina' => 'Piscina',
        'comedor' => 'Comedor',
        'terraza' => 'Terraza',
        'otro' => 'Otro'
    ];
    
    if (is_dir($serverDir)) {
        $scanned_files = scandir($serverDir);
        foreach ($scanned_files as $file) {
            if ($file !== '.' && $file !== '..') {
                // Filtrar solo imágenes válidas
                if (preg_match('/\.(jpg|jpeg|png|webp)$/i', $file)) {
                    // Intentar extraer categoría del nombre (ej: salon-1.webp)
                    $parts = explode('-', $file);
                    $catKey = strtolower($parts[0]);
                    $categoryName = isset($categoryMap[$catKey]) ? $categoryMap[$catKey] : 'Otro';

                    if (!isset($photosByCategory[$categoryName])) {
                        $photosByCategory[$categoryName] = [];
                    }

                    $photosByCategory[$categoryName][] = [
                        'name' => $file,
                        'url' => 'https://rutasrurales.io' . $baseDir . $file,
                        'size' => filesize($serverDir . $file),
                        'created_at' => date('Y-m-d H:i:s', filemtime($serverDir . $file))
                    ];
                }
            }
        }
    }

    jsonSuccess(['photos_by_category' => $photosByCategory, 'folder' => $baseDir]);

} catch (Exception $e) {
    jsonError('Error al obtener fotos: ' . $e->getMessage(), 500);
}
?>