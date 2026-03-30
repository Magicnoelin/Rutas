<?php
// api/upload_photo.php

// 1. Configuración
$targetDir = "../img/users_uploads/";
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
$maxSize = 5 * 1024 * 1024; // 5MB

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto'])) {
    $file = $_FILES['foto'];
    $entityId = $_POST['entity_id'] ?? 0; // ID del monumento/evento
    $userId = $_SESSION['user_id'] ?? 0; // Quién la sube

    // 2. Validaciones básicas
    if (!in_array($file['type'], $allowedTypes)) {
        die(json_encode(['success' => false, 'msg' => 'Formato no permitido']));
    }

    // 3. Crear nombre único y ruta
    $fileName = "user_" . $userId . "_" . time() . "_" . bin2hex(random_bytes(4)) . ".webp";
    $targetPath = $targetDir . $fileName;

    // 4. CONVERSIÓN A WEBP (La parte clave)
    // Creamos una imagen desde el archivo temporal
    if ($file['type'] === 'image/jpeg') {
        $img = imagecreatefromjpeg($file['tmp_name']);
    } elseif ($file['type'] === 'image/png') {
        $img = imagecreatefrompng($file['tmp_name']);
        // Mantener transparencia si es necesario
        imagepalettetotruecolor($img);
        imagealphablending($img, true);
        imagesavealpha($img, true);
    } elseif ($file['type'] === 'image/webp') {
        $img = imagecreatefromwebp($file['tmp_name']);
    }

    // Guardar como WebP con calidad 80 (equilibrio perfecto peso/calidad)
    if (imagewebp($img, $targetPath, 80)) {
        imagedestroy($img); // Liberar memoria

        // 5. INSERTAR EN BASE DE DATOS
        // Aquí harías tu SQL: 
        // INSERT INTO entity_photos (entity_id, file_path, author_id, status) 
        // VALUES ($entityId, '$targetPath', $userId, 'pending')

        echo json_encode(['success' => true, 'path' => $targetPath]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Error al procesar WebP']);
    }
}