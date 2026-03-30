<?php
/**
 * API Endpoint: Obtener Alojamientos por Provincia desde la Base de Datos
 */

// Evitar que config.php envíe headers que causen problemas
define('API_NO_HEADERS', true);

// Cargar configuración de base de datos
require_once 'config.php';

// Establecer headers correctos
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Solo permitir método GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido'
    ]);
    exit;
}

// Obtener parámetros
$provincia = isset($_GET['provincia']) ? trim($_GET['provincia']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

// Validar provincia
if (empty($provincia)) {
    echo json_encode([
        'success' => false,
        'error' => 'Provincia no proporcionada',
        'data' => []
    ]);
    exit;
}

try {
    // Usar la función de conexión de config.php
    $pdo = getDBConnection();
    
    // Sanitizar provincia para comparación
    $provinciaNormalizada = strtolower(trim($provincia));
    $provinciaNormalizada = str_replace(['á','é','í','ó','ú','ñ','ü'], ['a','e','i','o','u','n','u'], $provinciaNormalizada);
    
    // Consulta SQL con JOIN para obtener la categoría correcta
    $sql = "SELECT 
                a.id,
                a.name,
                a.slug,
                a.address,
                a.municipality,
                a.province,
                a.latitude,
                a.longitude,
                a.phone,
                a.email,
                a.website,
                a.description,
                a.public_notes,
                a.photo1,
                a.photo2,
                a.photo3,
                a.photo4,
                a.price_per_night,
                a.category_id,
                a.accommodation_type,
                a.is_active,
                c.name as category_name
            FROM accommodations a
            LEFT JOIN categories_accommodations c ON a.category_id = c.id
            WHERE LOWER(a.province) LIKE :provincia
            AND a.is_active = 1
            ORDER BY a.name
            LIMIT :limit";
    
    $stmt = $pdo->prepare($sql);
    
    $provinciaBusqueda = '%' . $provinciaNormalizada . '%';
    $stmt->bindParam(':provincia', $provinciaBusqueda, PDO::PARAM_STR);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $accommodations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!$accommodations || count($accommodations) === 0) {
        echo json_encode([
            'success' => true,
            'message' => 'No hay alojamientos en esta provincia',
            'data' => []
        ]);
        exit;
    }
    
    // Formatear datos para el carrusel
    $formattedData = array_map(function($item) {
        // Procesar fotos
        $fotos = [];
        if (!empty($item['photo1'])) $fotos[] = $item['photo1'];
        if (!empty($item['photo2'])) $fotos[] = $item['photo2'];
        if (!empty($item['photo3'])) $fotos[] = $item['photo3'];
        if (!empty($item['photo4'])) $fotos[] = $item['photo4'];
        
        $fotoPrincipal = !empty($fotos) ? $fotos[0] : 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400&h=300&fit=crop';
        
        // Usar category_name del JOIN con categories_accommodations
        $tipo = 'Casa Rural';
        if (!empty($item['category_name'])) {
            $tipo = $item['category_name'];
        } elseif (!empty($item['accommodation_type'])) {
            // Fallback a accommodation_type
            $tipoRaw = $item['accommodation_type'];
            $tipoLower = strtolower($tipoRaw);
            if (strpos($tipoLower, 'piso') !== false || strpos($tipoLower, 'apartamento') !== false) {
                $tipo = 'Apartamento';
            } elseif (strpos($tipoLower, 'hotel') !== false) {
                $tipo = 'Hotel';
            } elseif (strpos($tipoLower, 'hostal') !== false) {
                $tipo = 'Hostal';
            } elseif (strpos($tipoLower, 'chalé') !== false || strpos($tipoLower, 'chale') !== false) {
                $tipo = 'Chalé';
            }
        }
        
        return [
            'id' => $item['id'],
            'nombre' => $item['name'] ?? 'Alojamiento',
            'slug' => $item['slug'] ?? '',
            'tipo' => $tipo,
            'direccion' => $item['address'] ?? '',
            'localidad' => $item['municipality'] ?? '',
            'provincia' => $item['province'] ?? '',
            'plazas' => 0,
            'precio' => $item['price_per_night'] ?? 0,
            'telefono' => $item['phone'] ?? '',
            'email' => $item['email'] ?? '',
            'web' => $item['website'] ?? '',
            'descripcion' => $item['description'] ?? $item['public_notes'] ?? '',
            'caracteristicas' => [],
            'foto' => $fotoPrincipal,
            'fotos' => $fotos,
            'Foto1' => $item['photo1'] ?? '',
            'foto1' => $item['photo1'] ?? ''
        ];
    }, $accommodations);
    
    echo json_encode([
        'success' => true,
        'message' => 'Alojamientos obtenidos correctamente',
        'data' => $formattedData,
        'pagination' => [
            'total' => count($formattedData),
            'limit' => $limit,
            'provincia' => $provincia
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Error en get_accommodations_by_province.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error al conectar con la base de datos: ' . $e->getMessage(),
        'data' => []
    ]);
}
