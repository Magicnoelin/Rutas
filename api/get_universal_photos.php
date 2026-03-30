<?php
/**
 * Universal Photo Retrieval API
 * Retrieves photos for any content type: accommodations, activities, places_of_interest, cultural_events
 * 
 * GET Parameters:
 * - slug: string (required) - Slug identifier for the item
 * - type: string (required) - Type of content: 'accommodations', 'activities', 'places_of_interest', 'cultural_events'
 * 
 * Returns:
 * JSON with success status and photos organized by category
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
$slug = $_GET['slug'] ?? '';
$type = $_GET['type'] ?? '';

if (empty($slug) || empty($type)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

// Validate item type
$validTypes = ['accommodations', 'activities', 'places_of_interest', 'cultural_events'];
if (!in_array($type, $validTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid item type']);
    exit;
}

// Define base directory
$baseDir = "tourist_activities_images/{$type}/{$slug}";

// Check if directory exists
if (!is_dir($baseDir)) {
    // Return empty result if no photos exist
    echo json_encode([
        'success' => true,
        'data' => [
            'photos_by_category' => []
        ]
    ]);
    exit;
}

// Get all categories for this type
$categoriesByType = [
    'accommodations' => ['salon', 'cocina', 'jardin', 'habitacion', 'bano', 'exterior', 'piscina', 'comedor', 'terraza', 'otro'],
    'activities' => ['ruta', 'paisaje', 'fauna', 'flora', 'panoramica', 'equipo', 'otro'],
    'places_of_interest' => ['exterior', 'interior', 'detalle', 'panoramica', 'otro'],
    'cultural_events' => ['exterior', 'interior', 'detalle', 'panoramica', 'actuacion', 'otro']
];

$categories = $categoriesByType[$type];

$photosByCategory = [];

// Loop through each category and get photos
foreach ($categories as $category) {
    $categoryDir = "{$baseDir}/{$category}";
    
    if (is_dir($categoryDir)) {
        $photos = [];
        $files = scandir($categoryDir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $filePath = "{$categoryDir}/{$file}";
            if (is_file($filePath)) {
                $photos[] = [
                    'url' => str_replace('\\', '/', $filePath),
                    'filename' => $file,
                    'created_at' => filemtime($filePath)
                ];
            }
        }
        
        // Sort photos by creation date (newest first)
        usort($photos, function($a, $b) {
            return $b['created_at'] - $a['created_at'];
        });
        
        if (!empty($photos)) {
            $photosByCategory[$category] = $photos;
        }
    }
}

// Return success response
echo json_encode([
    'success' => true,
    'data' => [
        'photos_by_category' => $photosByCategory
    ]
]);

?>