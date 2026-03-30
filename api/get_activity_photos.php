<?php
/**
 * API: Obtener Fotos de Actividad
 * Endpoint: /api/get_activity_photos.php?slug=nombre-actividad
 */

header('Content-Type: application/json');
require_once 'config.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    echo json_encode([
        'success' => false,
        'error' => 'Parámetro slug requerido'
    ]);
    exit;
}

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception('Error de conexión: ' . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    // Obtener la actividad
    $sql = "SELECT id, name, slug, photo1, photo2, photo3, photo4 
            FROM activities 
            WHERE slug = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $activityId = $row['id'];
        $activityName = $row['name'];
        $activitySlug = $row['slug'];
        
        // Recopilar fotos
        $photos = [];
        $photoFields = ['photo1', 'photo2', 'photo3', 'photo4'];
        
        foreach ($photoFields as $field) {
            if (!empty($row[$field])) {
                $photos[] = [
                    'field' => $field,
                    'url' => $row[$field],
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }
        }
        
        // Agrupar por categoría (simplificado - todas como "general")
        $photosByCategory = [];
        foreach ($photos as $photo) {
            $category = 'general';
            if (!isset($photosByCategory[$category])) {
                $photosByCategory[$category] = [];
            }
            $photosByCategory[$category][] = $photo;
        }
        
        $stmt->close();
        $conn->close();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'activity' => [
                    'id' => $activityId,
                    'name' => $activityName,
                    'slug' => $activitySlug
                ],
                'photos' => $photos,
                'photos_by_category' => $photosByCategory,
                'total' => count($photos)
            ]
        ]);
    } else {
        $stmt->close();
        $conn->close();
        
        echo json_encode([
            'success' => false,
            'error' => 'Actividad no encontrada'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
