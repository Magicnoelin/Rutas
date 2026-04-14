<?php
/**
 * API: Buscar Actividades
 * Endpoint: /api/search_activities.php?query=texto
 */

header('Content-Type: application/json');
require_once 'config.php';

$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if (empty($query)) {
    echo json_encode([
        'success' => false,
        'error' => 'Parámetro query requerido'
    ]);
    exit;
}

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception('Error de conexión: ' . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    // Buscar actividades por nombre o municipio
    $sql = "SELECT id, name, slug, category_id, municipality, province 
            FROM tourist_activities 
            WHERE (name LIKE ? OR municipality LIKE ?) 
            AND moderation_status = 'approved'
            ORDER BY name ASC 
            LIMIT 20";
    
    $searchTerm = "%{$query}%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $activities = [];
    while ($row = $result->fetch_assoc()) {
        $activities[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'slug' => $row['slug'],
            'category_id' => $row['category_id'],
            'municipality' => $row['municipality'],
            'province' => $row['province']
        ];
    }
    
    $stmt->close();
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'results' => $activities,
            'total' => count($activities)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
