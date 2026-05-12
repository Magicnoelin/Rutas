<?php
/**
 * API Endpoint: Upload Accommodation Photo with Category
 * POST /api/upload_accommodation_photo.php
 *
 * Handles photo uploads for accommodations with:
 * - Automatic WebP conversion
 * - Category-based organization
 * - Slug-based folder structure
 */

require_once 'config.php';

// Allowed photo categories
$photoCategories = [
    'fachada' => 'Fachada',
    'salon' => 'Salón',
    'cocina' => 'Cocina',
    'jardin' => 'Jardín',
    'habitacion' => 'Habitación',
    'bano' => 'Cuarto de baño',
    'exterior' => 'Exterior',
    'piscina' => 'Piscina',
    'comedor' => 'Comedor',
    'terraza' => 'Terraza',
    'otro' => 'Otro'
];

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

// Check authentication (optional - can be removed if not needed)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validate required parameters
$requiredParams = ['accommodation_identifier', 'photo_category'];
foreach ($requiredParams as $param) {
    if (!isset($_POST[$param]) || empty($_POST[$param])) {
        jsonError("Parámetro requerido faltante: $param", 400);
    }
}

$accommodationIdentifier = sanitizeInput($_POST['accommodation_identifier']);
$photoCategory = sanitizeInput($_POST['photo_category']);

// Validate category
if (!array_key_exists($photoCategory, $photoCategories)) {
    jsonError('Categoría de foto inválida', 400);
}

// Check if file was uploaded
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    jsonError('Error al subir el archivo', 400);
}

$file = $_FILES['photo'];

// Validate file type and size
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
$maxSize = 5 * 1024 * 1024; // 5MB

if (!in_array($file['type'], $allowedTypes)) {
    jsonError('Solo se permiten imágenes JPG, PNG o WEBP', 400);
}

if ($file['size'] > $maxSize) {
    jsonError('La imagen no debe superar los 5MB', 400);
}

try {
    $pdo = getDBConnection();

    // Buscar alojamiento SOLO por slug exacto
    $stmtSearch = $pdo->prepare("
        SELECT id, name, slug 
        FROM accommodations 
        WHERE slug = ? 
        LIMIT 1
    ");
    $stmtSearch->execute([$accommodationIdentifier]);
    $accommodation = $stmtSearch->fetch();

    if (!$accommodation) {
        jsonError('Alojamiento no encontrado. Verifica que el slug sea correcto.', 404);
    }

    $accommodationId = $accommodation['id'];
    $accommodationSlug = $accommodation['slug'];

    // Create img/alojamientos directory if it doesn't exist
    $baseUploadDir = '../img/alojamientos/';
    if (!file_exists($baseUploadDir)) {
        mkdir($baseUploadDir, 0755, true);
    }

    // Create accommodation-specific directory (img/alojamientos/{slug}/)
    $accommodationDir = $baseUploadDir . $accommodationSlug . '/';
    if (!file_exists($accommodationDir)) {
        mkdir($accommodationDir, 0755, true);
    }

    // Generate unique filename with simple counter
    $counter = 1;
    $filename = $photoCategory . '-' . $counter . '.webp';
    $targetPath = $accommodationDir . $filename;
    
    // Find next available counter
    while (file_exists($targetPath)) {
        $counter++;
        $filename = $photoCategory . '-' . $counter . '.webp';
        $targetPath = $accommodationDir . $filename;
    }
    
    $publicUrl = '/img/alojamientos/' . $accommodationSlug . '/' . $filename;

    // Convert image to WebP format
    $imageData = convertToWebP($file['tmp_name']);

    if ($imageData === false) {
        jsonError('Error al convertir la imagen a formato WebP', 500);
    }

    // Save WebP file
    if (file_put_contents($targetPath, $imageData) === false) {
        jsonError('Error al guardar el archivo en el servidor', 500);
    }

    // Check if photo_categories table exists, if not create it
    try {
        $pdo->query("SELECT 1 FROM photo_categories LIMIT 1");
    } catch (Exception $e) {
        $pdo->exec("
            CREATE TABLE photo_categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                accommodation_id INT NOT NULL,
                category VARCHAR(50) NOT NULL,
                photo_url VARCHAR(500) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (accommodation_id) REFERENCES accommodations(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    // Insert photo record
    $stmtInsert = $pdo->prepare("
        INSERT INTO photo_categories (accommodation_id, category, photo_url)
        VALUES (?, ?, ?)
    ");
    $stmtInsert->execute([$accommodationId, $photoCategories[$photoCategory], $publicUrl]);

    // Return success response
    jsonSuccess([
        'url' => $publicUrl,
        'category' => $photoCategories[$photoCategory],
        'accommodation_slug' => $accommodationSlug,
        'filename' => $filename
    ], 'Foto subida y convertida a WebP exitosamente');

} catch (Exception $e) {
    error_log('Error uploading accommodation photo: ' . $e->getMessage());
    jsonError('Error al procesar la foto: ' . $e->getMessage(), 500);
}

/**
 * Convert image to WebP format
 */
function convertToWebP($sourcePath) {
    // Check if GD library is available
    if (!function_exists('imagecreatefromstring') || !function_exists('imagewebp')) {
        error_log('GD library not available for WebP conversion');
        return false;
    }

    // Get image data
    $imageData = file_get_contents($sourcePath);
    if ($imageData === false) {
        return false;
    }

    // Create image resource based on type
    $imageInfo = getimagesize($sourcePath);
    $mimeType = $imageInfo['mime'];

    switch ($mimeType) {
        case 'image/jpeg':
        case 'image/jpg':
            $image = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $image = imagecreatefrompng($sourcePath);
            // Preserve transparency for PNG
            imagealphablending($image, false);
            imagesavealpha($image, true);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }

    if ($image === false) {
        return false;
    }

    // Convert to WebP with 80% quality
    ob_start();
    imagewebp($image, null, 80);
    $webpData = ob_get_contents();
    ob_end_clean();

    // Free memory
    imagedestroy($image);

    return $webpData;
}
?>