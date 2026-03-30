<?php
/**
 * API: Sugerir un lugar/entidad nueva que no existe en la web
 * POST /api/suggest_entity.php
 *
 * Parámetros POST:
 *   - name: string (requerido)
 *   - entity_type: string (requerido)
 *   - municipality: string (opcional)
 *   - province: string (opcional)
 *   - description: string (opcional)
 *   - photo: file (opcional, se guarda en img/entity_photos/suggested/{id}/)
 *
 * Requiere sesión iniciada
 */

require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');

// ── Autenticación ────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Debes iniciar sesión para sugerir un lugar']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// ── Parámetros ───────────────────────────────────────────────────────────────
$name        = trim($_POST['name'] ?? '');
$entityType  = trim($_POST['entity_type'] ?? 'places_of_interest');
$municipality= trim($_POST['municipality'] ?? '');
$province    = trim($_POST['province'] ?? '');
$description = trim($_POST['description'] ?? '');

$validTypes = ['places_of_interest', 'cultural_events', 'activities', 'accommodations'];
if (empty($name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El nombre del lugar es obligatorio']);
    exit;
}
if (!in_array($entityType, $validTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Tipo de entidad no válido']);
    exit;
}

try {
    $pdo = getDBConnection();

    // ── Verificar que la tabla existe ────────────────────────────────────────
    try {
        $pdo->query("SELECT 1 FROM suggested_entities LIMIT 1");
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error'   => 'La tabla suggested_entities no existe. Ejecuta api/crear_suggested_entities.sql en tu base de datos.'
        ]);
        exit;
    }

    // ── Insertar sugerencia ──────────────────────────────────────────────────
    $stmt = $pdo->prepare("
        INSERT INTO suggested_entities 
            (suggested_by, name, entity_type, municipality, province, description, status, created_at)
        VALUES 
            (?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([$userId, $name, $entityType, $municipality, $province, $description]);
    $suggestedId = $pdo->lastInsertId();

    // ── Procesar foto si se adjuntó ──────────────────────────────────────────
    $photoUrl = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['photo'];
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $maxSize = 8 * 1024 * 1024;

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (in_array($realMime, $allowedMimes) && $file['size'] <= $maxSize) {
            $baseDir = dirname(__DIR__) . '/img/entity_photos/suggested/' . $suggestedId . '/';
            if (!is_dir($baseDir)) mkdir($baseDir, 0755, true);

            $hash     = bin2hex(random_bytes(5));
            $filename = 'u' . $userId . '_' . time() . '_' . $hash . '.webp';
            $destPath = $baseDir . $filename;
            $photoUrl = '/img/entity_photos/suggested/' . $suggestedId . '/' . $filename;

            // Convertir a WebP
            $webpData = convertSuggestedToWebP($file['tmp_name'], $realMime);
            if ($webpData !== false) {
                file_put_contents($destPath, $webpData);

                // Registrar en entity_photos con entity_id=0 y suggested_entity_id
                try {
                    $stmtUser = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) as full_name FROM users WHERE id = ? LIMIT 1");
                    $stmtUser->execute([$userId]);
                    $userData = $stmtUser->fetch();
                    $authorName = $userData['full_name'] ?? 'Usuario';

                    $stmtPhoto = $pdo->prepare("
                        INSERT INTO entity_photos 
                            (entity_type, entity_id, suggested_entity_id, category, author_id, 
                             file_path, file_url, author_name, source, permission_status, uploaded_at)
                        VALUES 
                            (?, 0, ?, 'otro', ?, ?, ?, ?, 'traveler', 'pending', NOW())
                    ");
                    $stmtPhoto->execute([
                        $entityType,   // entity_type
                        $suggestedId,  // suggested_entity_id
                        $userId,       // author_id  ← CORREGIDO
                        $destPath,     // file_path  ← CORREGIDO
                        $photoUrl,     // file_url   ← CORREGIDO
                        $authorName,   // author_name← CORREGIDO
                    ]);
                } catch (Exception $e) {
                    // No es crítico si falla el registro de la foto
                    error_log('Error registrando foto de sugerencia: ' . $e->getMessage());
                }
            }
        }
    }

    echo json_encode([
        'success'      => true,
        'message'      => '¡Gracias por tu sugerencia! La revisaremos pronto y si la aprobamos aparecerá en la web.',
        'data'         => [
            'suggested_id' => $suggestedId,
            'name'         => $name,
            'entity_type'  => $entityType,
            'photo_url'    => $photoUrl,
            'status'       => 'pending',
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al guardar la sugerencia: ' . $e->getMessage()]);
}

function convertSuggestedToWebP(string $sourcePath, string $mime): string|false
{
    if (!function_exists('imagecreatefromjpeg') || !function_exists('imagewebp')) return false;

    switch ($mime) {
        case 'image/jpeg': case 'image/jpg':
            $img = @imagecreatefromjpeg($sourcePath); break;
        case 'image/png':
            $img = @imagecreatefrompng($sourcePath);
            if ($img) { imagepalettetotruecolor($img); imagealphablending($img, true); imagesavealpha($img, true); }
            break;
        case 'image/webp':
            $img = @imagecreatefromwebp($sourcePath); break;
        default: return false;
    }
    if (!$img) return false;

    // ── Corregir orientación EXIF (fotos de móvil ladeadas) ──────────────────
    $img = fixSuggestedExifOrientation($img, $sourcePath, $mime);

    // Redimensionar a máx 1200px
    $w = imagesx($img); $h = imagesy($img);
    if ($w > 1200) {
        $newH = (int)round($h * 1200 / $w);
        $r = imagecreatetruecolor(1200, $newH);
        imagealphablending($r, false); imagesavealpha($r, true);
        imagecopyresampled($r, $img, 0, 0, 0, 0, 1200, $newH, $w, $h);
        imagedestroy($img); $img = $r;
    }

    ob_start();
    imagewebp($img, null, 82);
    $data = ob_get_clean();
    imagedestroy($img);
    return $data ?: false;
}

function fixSuggestedExifOrientation($img, string $sourcePath, string $mime)
{
    if (!in_array($mime, ['image/jpeg', 'image/jpg'])) return $img;
    if (!function_exists('exif_read_data')) return $img;

    $exif = @exif_read_data($sourcePath);
    if (!$exif || !isset($exif['Orientation'])) return $img;

    switch ((int)$exif['Orientation']) {
        case 3: $img = imagerotate($img, 180, 0); break;
        case 6: $img = imagerotate($img, -90, 0); break;  // más común en móviles
        case 8: $img = imagerotate($img, 90, 0);  break;
        case 2: imageflip($img, IMG_FLIP_HORIZONTAL); break;
        case 4: imageflip($img, IMG_FLIP_VERTICAL);   break;
        case 5: imageflip($img, IMG_FLIP_HORIZONTAL); $img = imagerotate($img, 90, 0);  break;
        case 7: imageflip($img, IMG_FLIP_HORIZONTAL); $img = imagerotate($img, -90, 0); break;
    }
    return $img;
}
