<?php
/**
 * API: Obtener Evento Cultural por Slug
 * Endpoint: /api/evento-slug.php?slug=nombre-evento
 */

header('Content-Type: application/json');
require_once 'config.php';

// Habilitar logging para depuración
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    echo json_encode([
        'success' => false,
        'error' => 'Parámetro slug requerido'
    ]);
    exit;
}

// Log para depuración
error_log("API evento-slug.php llamada con slug: " . $slug);

try {
    // Conectar a la base de datos
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        $error_msg = 'Error de conexión a la base de datos: ' . $conn->connect_error;
        error_log($error_msg);
        throw new Exception($error_msg);
    }
    
    $conn->set_charset("utf8mb4");
    
    // Consulta para obtener evento cultural - versión más flexible
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
        WHERE e.slug = ?";
    
    error_log("Ejecutando consulta SQL para slug: " . $slug);
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $error_msg = 'Error preparando consulta: ' . $conn->error;
        error_log($error_msg);
        throw new Exception($error_msg);
    }
    
    $stmt->bind_param("s", $slug);
    
    if (!$stmt->execute()) {
        $error_msg = 'Error ejecutando consulta: ' . $stmt->error;
        error_log($error_msg);
        throw new Exception($error_msg);
    }
    
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Verificar si el evento está activo
        if ($row['is_active'] != 1) {
            error_log("Evento encontrado pero no activo. ID: " . $row['id']);
            echo json_encode([
                'success' => false,
                'error' => 'Evento no está activo'
            ]);
        } else {
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
            
            error_log("Evento encontrado exitosamente. ID: " . $row['id'] . ", Título: " . $row['titulo']);
            
            $stmt->close();
            $conn->close();
            
            echo json_encode([
                'success' => true,
                'data' => $evento
            ]);
        }
    } else {
        error_log("Evento no encontrado en la base de datos para slug: " . $slug);
        
        // Intentar buscar sin filtro de is_active
        $sql2 = "SELECT COUNT(*) as total FROM cultural_events WHERE slug = ?";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("s", $slug);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        $count_row = $result2->fetch_assoc();
        
        if ($count_row['total'] > 0) {
            error_log("Evento existe en la base de datos pero no está activo. Total: " . $count_row['total']);
            echo json_encode([
                'success' => false,
                'error' => 'Evento existe pero no está activo',
                'debug' => 'Evento encontrado en DB pero is_active != 1'
            ]);
        } else {
            error_log("Evento no existe en la base de datos");
            echo json_encode([
                'success' => false,
                'error' => 'Evento no encontrado en la base de datos'
            ]);
        }
        
        $stmt2->close();
        $stmt->close();
        $conn->close();
    }
    
} catch (Exception $e) {
    error_log("Excepción en evento-slug.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor',
        'debug' => $e->getMessage()
    ]);
}
?>