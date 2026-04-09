<?php
/**
 * API: Obtener Evento Cultural por Slug
 * Endpoint: /api/evento-slug.php?slug=nombre-evento
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
    
    // Consulta para obtener evento cultural
    $sql = "SELECT 
            e.id,
            e.name AS titulo,
            e.slug,
            e.description AS descripcion,
            e.short_description AS descripcion_corta,
            e.meta_title,
            e.meta_description,
            e.start_date,
            e.venue_name AS localidad,
            e.municipality,
            e.province AS provincia,
            e.organizer AS organizador,
            e.ticket_price AS precio,
            e.category_id,
            e.photo1,
            e.poster_image,
            e.is_active,
            e.created_at,
            e.updated_at
        FROM cultural_events e
        WHERE e.slug = ? AND e.is_active = 1
        LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $evento = [
            'id' => $row['id'],
            'titulo' => $row['titulo'],
            'slug' => $row['slug'],
            'descripcion' => $row['descripcion'],
            'descripcion_corta' => $row['descripcion_corta'],
            'meta_title' => $row['meta_title'],
            'meta_description' => $row['meta_description'],
            'start_date' => $row['start_date'],
            'localidad' => $row['localidad'],
            'municipality' => $row['municipality'],
            'provincia' => $row['provincia'],
            'organizador' => $row['organizador'],
            'precio' => $row['precio'],
            'category_id' => $row['category_id'],
            'photo1' => $row['photo1'],
            'poster_image' => $row['poster_image'],
            'is_active' => $row['is_active'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
        
        $stmt->close();
        $conn->close();
        
        echo json_encode([
            'success' => true,
            'data' => $evento
        ]);
    } else {
        $stmt->close();
        $conn->close();
        
        echo json_encode([
            'success' => false,
            'error' => 'Evento no encontrado'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>