<?php
/**
 * API: Obtener Actividad por Slug
 * Endpoint: /api/actividad-slug.php?slug=nombre-actividad
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
    
    $sql = "SELECT * FROM tourist_activities WHERE slug = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $activity = [
            'id' => $row['id'],
            'name' => $row['name'],
            'slug' => $row['slug'],
            'description' => $row['description'],
            'activity_type' => $row['activity_type'],
            'difficulty' => $row['difficulty'],
            'duration' => $row['duration'],
            'address' => $row['address'],
            'municipality' => $row['municipality'],
            'province' => $row['province'],
            'latitude' => $row['latitude'],
            'longitude' => $row['longitude'],
            'price' => $row['price'],
            'min_participants' => $row['min_participants'],
            'max_participants' => $row['max_participants'],
            'season' => $row['season'],
            'phone' => $row['phone'],
            'email' => $row['email'],
            'website' => $row['website'],
            'booking_url' => $row['booking_url'],
            'photo1' => $row['photo1'],
            'photo2' => $row['photo2'],
            'photo3' => $row['photo3'],
            'photo4' => $row['photo4'],
            'moderation_status' => $row['moderation_status'],
            'is_active' => $row['is_active'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
        
        $stmt->close();
        $conn->close();
        
        echo json_encode([
            'success' => true,
            'data' => $activity
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
