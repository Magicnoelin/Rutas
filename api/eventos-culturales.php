<?php
/**
 * API Endpoint: Obtener Eventos Culturales
 * GET /api/eventos-culturales.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once 'config.php';

$response = ['success' => false, 'data' => null, 'message' => ''];

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8mb4");

    // Query con los nombres correctos de columnas
    $sql = "SELECT 
        id,
        name as titulo,
        slug,
        description as descripcion,
        short_description as descripcion_corta,
        start_date as fecha_evento,
        start_time as hora_evento,
        end_date as fecha_fin,
        venue_name as ubicacion,
        venue_address as direccion,
        municipality as localidad,
        province as provincia,
        category_id as categoria,
        photo1,
        poster_image,
        organizer as organizador,
        email,
        phone as telefono,
        website as web,
        ticket_price as precio,
        ticket_url as url_entradas,
        capacity as capacidad,
        status,
        latitude,
        longitude
    FROM cultural_events 
    WHERE status = 'scheduled' AND is_active = 1 AND start_date >= CURDATE()";

    $conditions = [];
    $params = [];

    // Filtro por categoría
    if (isset($_GET['category']) && !empty($_GET['category'])) {
        $conditions[] = "category_id = :category";
        $params[':category'] = $_GET['category'];
    }

    // Filtro por provincia
    if (isset($_GET['province']) && !empty($_GET['province'])) {
        $conditions[] = "province = :province";
        $params[':province'] = $_GET['province'];
    }

    // Filtro por búsqueda
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $conditions[] = "(name LIKE :search OR description LIKE :search OR venue_name LIKE :search)";
        $params[':search'] = '%' . $_GET['search'] . '%';
    }

    if (count($conditions) > 0) {
        $sql .= " AND " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY start_date ASC LIMIT 100";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatear datos
    $eventosFormateados = [];
    foreach ($eventos as $evento) {
        $fechaFormateada = '';
        if (!empty($evento['fecha_evento'])) {
            $fecha = new DateTime($evento['fecha_evento']);
            $fechaFormateada = $fecha->format('d/m/Y');
        }

        $precioFormateado = 'Entrada gratuita';
        if (!empty($evento['precio']) && $evento['precio'] > 0) {
            $precioFormateado = number_format($evento['precio'], 2) . '€';
        }

        // Usar poster_image o photo1 como imagen principal, con fallback al slug
        $imagen = $evento['poster_image'] ?: $evento['photo1'];
        if (empty($imagen)) {
            $imagen = "/cultural_events_images/" . $evento['slug'] . ".webp";
        }

        $eventosFormateados[] = [
            'id' => $evento['id'],
            'titulo' => $evento['titulo'],
            'descripcion' => strip_tags($evento['descripcion']),
            'descripcion_corta' => $evento['descripcion_corta'],
            'fecha_evento' => $evento['fecha_evento'],
            'fecha_formateada' => $fechaFormateada,
            'hora_evento' => $evento['hora_evento'],
            'fecha_fin' => $evento['fecha_fin'],
            'ubicacion' => $evento['ubicacion'],
            'direccion' => $evento['direccion'],
            'localidad' => $evento['localidad'],
            'provincia' => $evento['provincia'],
            'categoria' => $evento['categoria'],
            'imagen' => $imagen,
            'organizador' => $evento['organizador'],
            'email' => $evento['email'],
            'telefono' => $evento['telefono'],
            'web' => $evento['web'],
            'precio' => $precioFormateado,
            'precio_numerico' => $evento['precio'],
            'url_entradas' => $evento['url_entradas'],
            'capacidad' => $evento['capacidad'],
            'status' => $evento['status'],
            'slug' => $evento['slug'],
            'latitud' => $evento['latitude'],
            'longitud' => $evento['longitude']
        ];
    }

    $response['success'] = true;
    $response['data'] = $eventosFormateados;
    $response['total'] = count($eventosFormateados);
    $response['message'] = 'Eventos obtenidos exitosamente';

} catch (PDOException $e) {
    $response['message'] = 'Error de base de datos: ' . $e->getMessage();
    error_log("Error en eventos-culturales.php: " . $e->getMessage());
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log("Error en eventos-culturales.php: " . $e->getMessage());
}

echo json_encode($response);
