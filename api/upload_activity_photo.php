<?php
/**
 * API: Subir Foto de Actividad
 * Endpoint: POST /api/upload_activity_photo.php
 * Parameters: activity_identifier (slug or id), photo_category, photo (file)
 */

header('Content-Type: application/json');
require_once 'config.php';

// Configuración de carpetas
$uploadDir = __DIR__ . '/../tourist_activities_images/';
$baseUrl = '/tourist_activities_images/';

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido. Use POST.'
    ]);
    exit;
}

// Obtener parámetros
$activityIdentifier = isset($_POST['activity_identifier']) ? trim($_POST['activity_identifier']) : '';
$photoCategory = isset($_POST['photo_category']) ? trim($_POST['photo_category']) : '';

// Validar parámetros requeridos
if (empty($activityIdentifier)) {
    echo json_encode([
        'success' => false,
        'error' => 'Parámetro activity_identifier requerido'
    ]);
    exit;
}

if (empty($photoCategory)) {
    echo json_encode([
        'success' => false,
        'error' => 'Parámetro photo_category requerido'
    ]);
    exit;
}

if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'success' => false,
        'error' => 'Archivo de foto requerido o error al subir'
    ]);
    exit;
}

$file = $_FILES['photo'];

// Validar tipo de archivo
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode([
        'success' => false,
        'error' => 'Tipo de archivo no permitido. Use JPG, PNG, WebP o GIF.'
    ]);
    exit;
}

// Validar tamaño (máx 5MB)
$maxSize = 5 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    echo json_encode([
        'success' => false,
        'error' => 'El archivo no debe superar 5MB'
    ]);
    exit;
}

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception('Error de conexión: ' . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    // Buscar la actividad por slug o ID
    $isNumeric = is_numeric($activityIdentifier);
    
    if ($isNumeric) {
        $sql = "SELECT id, slug, name FROM activities WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $activityIdentifier);
    } else {
        $sql = "SELECT id, slug, name FROM activities WHERE slug = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $activityIdentifier);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$row = $result->fetch_assoc()) {
        $stmt->close();
        $conn->close();
        echo json_encode([
            'success' => false,
            'error' => 'Actividad no encontrada'
        ]);
        exit;
    }
    
    $activityId = $row['id'];
    $activitySlug = $row['slug'];
    $activityName = $row['name'];
    $stmt->close();
    
    // Crear carpeta si no existe
    $activityFolder = $uploadDir . $activitySlug . '/';
    if (!is_dir($activityFolder)) {
        mkdir($activityFolder, 0755, true);
    }
    
    // Generar nombre de archivo único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFilename = $photoCategory . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $targetPath = $activityFolder . $newFilename;
    
    // Mover archivo
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Error al mover el archivo');
    }
    
    // Convertir a WebP si es necesario
    $webpPath = $activityFolder . str_replace('.' . $extension, '.webp', $newFilename);
    $image = null;
    
    switch ($file['type']) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($targetPath);
            break;
        case 'image/png':
            $image = imagecreatefrompng($targetPath);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($targetPath);
            break;
    }
    
    if ($image) {
        // Guardar como WebP con calidad 85
        imagewebp($image, $webpPath, 85);
        imagedestroy($image);
        
        // Eliminar original si es diferente
        if ($targetPath !== $webpPath) {
            unlink($targetPath);
        }
        
        $finalFilename = str_replace('.' . $extension, '.webp', $newFilename);
        $finalUrl = $baseUrl . $activitySlug . '/' . $finalFilename;
    } else {
        // No se pudo convertir, usar archivo original
        $finalFilename = $newFilename;
        $finalUrl = $baseUrl . $activitySlug . '/' . $newFilename;
    }
    
    // Actualizar la base de datos - determinar qué campo de foto actualizar
    $photoFields = ['photo1', 'photo2', 'photo3', 'photo4'];
    
    // Buscar el primer campo de foto vacío o usar photo1
    $updateField = 'photo1';
    foreach ($photoFields as $field) {
        $checkSql = "SELECT $field FROM activities WHERE id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("i", $activityId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $checkRow = $checkResult->fetch_assoc();
        $checkStmt->close();
        
        if (empty($checkRow[$field])) {
            $updateField = $field;
            break;
        }
    }
    
    $updateSql = "UPDATE activities SET $updateField = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("si", $finalUrl, $activityId);
    
    if (!$updateStmt->execute()) {
        throw new Exception('Error al actualizar la base de datos');
    }
    $updateStmt->close();
    
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $activityId,
            'slug' => $activitySlug,
            'name' => $activityName,
            'url' => $finalUrl,
            'filename' => $finalFilename,
            'category' => $photoCategory,
            'field' => $updateField
        ],
        'message' => 'Foto subida exitosamente'
    ]);
    
} catch (Exception $e) {
    // Limpiar archivo si hay error
    if (isset($targetPath) && file_exists($targetPath)) {
        unlink($targetPath);
    }
    if (isset($webpPath) && file_exists($webpPath)) {
        unlink($webpPath);
    }
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
