<?php
/**
 * Universal Photo Upload API
 * Handles photo uploads for any content type: accommodations, activities, places_of_interest, cultural_events
 * 
 * POST Parameters:
 * - item_type: string (required) - Type of content: 'accommodations', 'activities', 'places_of_interest', 'cultural_events'
 * - item_slug: string (required) - Slug identifier for the item
 * - photo_category: string (required) - Category for the photo
 * - photo: file (required) - The photo file to upload
 * 
 * Returns:
 * JSON with success status, file path, URL, category, and filename
 */

require_once 'config.php';

header('Content-Type: application/json');

// Verify authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// Validate required parameters
$itemType = $_POST['item_type'] ?? '';
$itemSlug = $_POST['item_slug'] ?? '';
$photoCategory = $_POST['photo_category'] ?? '';
$photoFile = $_FILES['photo'] ?? null;

if (empty($itemType) || empty($itemSlug) || empty($photoCategory) || !$photoFile) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

// Validate item type
$validTypes = ['accommodations', 'activities', 'places_of_interest', 'cultural_events'];
if (!in_array($itemType, $validTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid item type']);
    exit;
}

// Validate category based on item type
$categoriesByType = [
    'accommodations' => ['salon', 'cocina', 'jardin', 'habitacion', 'bano', 'exterior', 'piscina', 'comedor', 'terraza', 'otro'],
    'activities' => ['ruta', 'paisaje', 'fauna', 'flora', 'panoramica', 'equipo', 'otro'],
    'places_of_interest' => ['exterior', 'interior', 'detalle', 'panoramica', 'otro'],
    'cultural_events' => ['exterior', 'interior', 'detalle', 'panoramica', 'actuacion', 'otro']
];

if (!in_array($photoCategory, $categoriesByType[$itemType])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid photo category for this item type']);
    exit;
}

// Validate file upload
if ($photoFile['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'File upload error: ' . $photoFile['error']]);
    exit;
}

// Validate file type and size
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
$maxSize = 5 * 1024 * 1024; // 5MB

if (!in_array($photoFile['type'], $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, and WEBP are allowed.']);
    exit;
}

if ($photoFile['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'File size too large. Maximum 5MB.']);
    exit;
}

// Create directory structure if it doesn't exist
$baseDir = "tourist_activities_images/{$itemType}/{$itemSlug}/{$photoCategory}";
if (!is_dir($baseDir)) {
    mkdir($baseDir, 0755, true);
}

// Generate unique filename
$originalName = pathinfo($photoFile['name'], PATHINFO_FILENAME);
$extension = pathinfo($photoFile['name'], PATHINFO_EXTENSION);
$timestamp = time();
$filename = "{$originalName}_{$timestamp}.{$extension}";
$filePath = "{$baseDir}/{$filename}";

// Move uploaded file
if (!move_uploaded_file($photoFile['heat'], $filePath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file']);
    exit;
}

// Convert to WebP if not already WebP
$webpPath = null;
if (strtolower($extension) !== 'webp') {
    $webpPath = "{$baseDir}/{$originalName}_{$timestamp}.webp";
    $image = null;

    // Create image from file based on type
    if ($photoFile['type'] === 'image/jpeg' || $photoFile['type'] === 'image/jpg') {
        $image = imagecreatefromjpeg($filePath);
    } elseif ($photoFile['type'] === 'image/png') {
        $image = imagecreatefrompng($filePath);
    } elseif ($photoFile['type'] === 'image/webp') {
        $image = imagecreatefromwebp($filePath);
    }

    if ($image) {
        // Save as WebP
        imagewebp($image, $webpPath, 90);
        imagedestroy($image);

        // Delete original file
        unlink($filePath);
        $filePath = $webpPath;
        $extension = 'webp';
    }
}

// Get file info
$fileSize = filesize($filePath);
$fileUrl = str_replace('\\', '/', $filePath); // Convert to web-friendly path

// Log the upload
$logEntry = [
    'user_id' => $_SESSION['user_id'],
    'item_type' => $itemType,
    'item_slug' => $itemSlug,
    'category' => $photoCategory,
    'filename' => $filename,
    'original_name' => $photoFile['name'],
    'size' => $fileSize,
    'created_at' => date('Y-m-d H:i:s')
];

// For now, just log to a file. In production, you'd use a database.
$logFile = 'logs/photo_uploads.log';
file_put_contents($logFile, json_encode($logEntry) . PHP_EOL, FILE_APPEND);

// Return success response
echo json_encode([
    'success' => true,
    'data' => [
        'url' => $fileUrl,
        'category' => $photoCategory,
        'filename' => $filename,
        'original_name' => $photoFile['name'],
        'size' => $fileSize,
        'created_at' => $logEntry['created_at']
    ]
]);

?>