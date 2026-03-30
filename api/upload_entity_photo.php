<?php
/**
 * API: Subida de fotos de usuarios para cualquier entidad
 * POST /api/upload_entity_photo.php
 *
 * Parámetros POST:
 *   - entity_type: 'accommodations' | 'places_of_interest' | 'cultural_events' | 'activities'
 *   - entity_id: int (ID de la entidad)
 *   - category: string (categoría de la foto)
 *   - caption: string (opcional, descripción)
 *   - author_name: string (opcional, nombre del autor si no está registrado)
 *   - author_instagram: string (opcional)
 *   - photo: file
 *
 * Flujo:
 *   1. Valida sesión y parámetros
 *   2. Convierte a WebP (max 1200px ancho, calidad 82)
 *   3. Guarda en img/entity_photos/{entity_type}/{entity_id}/{user_id}_{hash}.webp
 *   4. INSERT en entity_photos con permission_status='pending'
 */

require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');

// ── Autenticación ────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Debes iniciar sesión para subir fotos']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

// ── Validar método ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// ── Parámetros ───────────────────────────────────────────────────────────────
$entityType  = trim($_POST['entity_type'] ?? '');
$entityId    = (int)($_POST['entity_id'] ?? 0);
$category    = trim($_POST['category'] ?? 'otro');
$caption     = trim($_POST['caption'] ?? '');
$authorName  = trim($_POST['author_name'] ?? '');
$authorInsta = trim($_POST['author_instagram'] ?? '');

// Caption ahora es opcional (coincide con la interfaz de usuario)

$validTypes = ['accommodations', 'places_of_interest', 'cultural_events', 'activities'];
if (!in_array($entityType, $validTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Tipo de entidad no válido']);
    exit;
}

if ($entityId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de entidad no válido']);
    exit;
}

// ── Validar archivo ──────────────────────────────────────────────────────────
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE   => 'El archivo supera el límite del servidor',
        UPLOAD_ERR_FORM_SIZE  => 'El archivo supera el límite del formulario',
        UPLOAD_ERR_PARTIAL    => 'El archivo se subió parcialmente',
        UPLOAD_ERR_NO_FILE    => 'No se seleccionó ningún archivo',
        UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal del servidor',
        UPLOAD_ERR_CANT_WRITE => 'Error al escribir en disco',
    ];
    $errCode = $_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE;
    $errMsg  = $uploadErrors[$errCode] ?? 'Error desconocido al subir';
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $errMsg]);
    exit;
}

$file = $_FILES['photo'];
$allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
$maxSize = 8 * 1024 * 1024; // 8MB

// Verificar MIME real con finfo
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$realMime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($realMime, $allowedMimes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Solo se permiten imágenes JPG, PNG o WEBP']);
    exit;
}

if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'La imagen no debe superar los 8MB']);
    exit;
}

// ── Verificar que la entidad existe en BD ────────────────────────────────────
try {
    $pdo = getDBConnection();

    $tableMap = [
        'accommodations'      => 'accommodations',
        'places_of_interest' => 'places_of_interest',
        'cultural_events'    => 'cultural_events',
        'activities'         => 'tourist_activities',
    ];
    $table = $tableMap[$entityType];

    $stmt = $pdo->prepare("SELECT id, name FROM `{$table}` WHERE id = ? LIMIT 1");
    $stmt->execute([$entityId]);
    $entity = $stmt->fetch();

    if (!$entity) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'La entidad no existe en la base de datos']);
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de base de datos: ' . $e->getMessage()]);
    exit;
}

// ── Crear directorio de destino ──────────────────────────────────────────────
// Ruta: img/entity_photos/{entity_type}/{entity_id}/
$baseDir  = dirname(__DIR__) . '/img/entity_photos/' . $entityType . '/' . $entityId . '/';
if (!is_dir($baseDir)) {
    if (!mkdir($baseDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'No se pudo crear el directorio de destino']);
        exit;
    }
}

// ── Nombre único del archivo ─────────────────────────────────────────────────
$hash     = bin2hex(random_bytes(5));
$filename = 'u' . $userId . '_' . time() . '_' . $hash . '.webp';
$destPath = $baseDir . $filename;
$publicUrl = '/img/entity_photos/' . $entityType . '/' . $entityId . '/' . $filename;

// ── Conversión a WebP ────────────────────────────────────────────────────────
$webpData = convertAndResizeToWebP($file['tmp_name'], $realMime, 1200, 82);
if ($webpData === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al convertir la imagen a WebP. Asegúrate de que la extensión GD está activa en el servidor.']);
    exit;
}

if (file_put_contents($destPath, $webpData) === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al guardar el archivo en el servidor']);
    exit;
}

// ── Obtener nombre del autor desde users si no se proporcionó ────────────────
if (empty($authorName)) {
    try {
        $stmtUser = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) as full_name FROM users WHERE id = ? LIMIT 1");
        $stmtUser->execute([$userId]);
        $userData = $stmtUser->fetch();
        $authorName = $userData['full_name'] ?? 'Usuario';
    } catch (Exception $e) {
        $authorName = 'Usuario';
    }
}

// ── INSERT en entity_photos ──────────────────────────────────────────────────
try {
    $stmt = $pdo->prepare("
        INSERT INTO entity_photos 
            (entity_type, entity_id, category, author_id, file_path, file_url, 
             author_name, author_instagram, source, permission_status, caption, uploaded_at)
        VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?, 'traveler', 'pending', ?, NOW())
    ");
    $stmt->execute([
        $entityType,
        $entityId,
        $category,
        $userId,
        $destPath,
        $publicUrl,
        $authorName,
        $authorInsta ?: null,
        $caption ?: null,
    ]);

    $photoId = $pdo->lastInsertId();

} catch (Exception $e) {
    // Si falla el INSERT, borrar el archivo subido
    @unlink($destPath);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al registrar la foto en la base de datos: ' . $e->getMessage()]);
    exit;
}

// ── Respuesta exitosa ────────────────────────────────────────────────────────
echo json_encode([
    'success' => true,
    'message' => '¡Foto subida correctamente! Estará visible una vez que sea revisada.',
    'data' => [
        'photo_id'   => $photoId,
        'url'        => $publicUrl,
        'category'   => $category,
        'status'     => 'pending',
        'entity_name'=> $entity['name'],
    ]
]);

// ── Función de conversión ────────────────────────────────────────────────────
function convertAndResizeToWebP(string $sourcePath, string $mime, int $maxWidth, int $quality): string|false
{
    if (!function_exists('imagecreatefromjpeg') || !function_exists('imagewebp')) {
        return false;
    }

    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            $img = @imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $img = @imagecreatefrompng($sourcePath);
            if ($img) {
                imagepalettetotruecolor($img);
                imagealphablending($img, true);
                imagesavealpha($img, true);
            }
            break;
        case 'image/webp':
            $img = @imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }

    if (!$img) return false;

    // ── Corregir orientación EXIF (fotos de móvil ladeadas) ──────────────────
    $img = fixExifOrientation($img, $sourcePath, $mime);

    // Redimensionar si supera el ancho máximo
    $origW = imagesx($img);
    $origH = imagesy($img);

    if ($origW > $maxWidth) {
        $newH  = (int)round($origH * $maxWidth / $origW);
        $resized = imagecreatetruecolor($maxWidth, $newH);

        // Preservar transparencia
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefilledrectangle($resized, 0, 0, $maxWidth, $newH, $transparent);

        imagecopyresampled($resized, $img, 0, 0, 0, 0, $maxWidth, $newH, $origW, $origH);
        imagedestroy($img);
        $img = $resized;
    }

    ob_start();
    imagewebp($img, null, $quality);
    $data = ob_get_clean();
    imagedestroy($img);

    return $data ?: false;
}

/**
 * Corrige la orientación de la imagen según los datos EXIF.
 * Los móviles guardan fotos en horizontal con un metadato EXIF de rotación.
 * GD pierde ese metadato al procesar, así que hay que aplicar la rotación manualmente.
 */
function fixExifOrientation($img, string $sourcePath, string $mime)
{
    // Solo JPEG tiene EXIF
    if (!in_array($mime, ['image/jpeg', 'image/jpg'])) return $img;
    if (!function_exists('exif_read_data')) return $img;

    $exif = @exif_read_data($sourcePath);
    if (!$exif || !isset($exif['Orientation'])) return $img;

    $orientation = (int)$exif['Orientation'];

    switch ($orientation) {
        case 3: // 180°
            $img = imagerotate($img, 180, 0);
            break;
        case 6: // 90° CW (el más común en móviles)
            $img = imagerotate($img, -90, 0);
            break;
        case 8: // 90° CCW
            $img = imagerotate($img, 90, 0);
            break;
        case 2: // Flip horizontal
            imageflip($img, IMG_FLIP_HORIZONTAL);
            break;
        case 4: // Flip vertical
            imageflip($img, IMG_FLIP_VERTICAL);
            break;
        case 5: // Flip horizontal + 90° CCW
            imageflip($img, IMG_FLIP_HORIZONTAL);
            $img = imagerotate($img, 90, 0);
            break;
        case 7: // Flip horizontal + 90° CW
            imageflip($img, IMG_FLIP_HORIZONTAL);
            $img = imagerotate($img, -90, 0);
            break;
        // case 1: orientación normal, no hacer nada
    }

    return $img;
}
