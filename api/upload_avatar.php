<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

if (!isset($_SESSION['user_id'])) {
    jsonError('No autenticado', 401);
}

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    jsonError('Error al subir el archivo', 400);
}

$file = $_FILES['avatar'];
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
$maxSize = 2 * 1024 * 1024; // 2MB

// Validaciones
if (!in_array($file['type'], $allowedTypes)) {
    jsonError('Solo se permiten imágenes JPG, PNG o WEBP', 400);
}

if ($file['size'] > $maxSize) {
    jsonError('La imagen no debe superar los 2MB', 400);
}

// Crear directorio si no existe
$uploadDir = '../uploads/avatars/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generar nombre único
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'user_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
$targetPath = $uploadDir . $filename;
$publicUrl = '/uploads/avatars/' . $filename;

try {
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $pdo = getDBConnection();
        
        // Verificar si existe la columna avatar_url, si no, crearla (Autocorrección de BD)
        try {
            $pdo->query("SELECT avatar_url FROM users LIMIT 1");
        } catch (Exception $e) {
            $pdo->exec("ALTER TABLE users ADD COLUMN avatar_url VARCHAR(255) DEFAULT NULL");
        }

        // Obtener avatar anterior para borrarlo
        $stmt = $pdo->prepare("SELECT avatar_url FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $oldAvatar = $stmt->fetchColumn();

        // Actualizar BD
        $stmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
        $stmt->execute([$publicUrl, $_SESSION['user_id']]);

        // Borrar archivo anterior si existe y es local
        if ($oldAvatar && file_exists('..' . $oldAvatar) && strpos($oldAvatar, '/uploads/avatars/') === 0) {
            unlink('..' . $oldAvatar);
        }

        jsonSuccess(['url' => $publicUrl], 'Avatar actualizado');
    } else {
        jsonError('Error al guardar el archivo en el servidor', 500);
    }
} catch (Exception $e) {
    jsonError('Error de base de datos: ' . $e->getMessage(), 500);
}