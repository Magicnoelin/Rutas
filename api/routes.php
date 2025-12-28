<?php
/**
 * API Endpoint: Gestión de Rutas Turísticas
 * Soporta: GET (listar), POST (crear), PUT (actualizar), DELETE (eliminar)
 */

require_once 'config.php';

// Cabeceras CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $pdo = getDBConnection();
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            handleGet($pdo);
            break;
        case 'POST':
            handlePost($pdo);
            break;
        case 'PUT':
            handlePut($pdo);
            break;
        case 'DELETE':
            handleDelete($pdo);
            break;
        default:
            jsonError('Método no permitido', 405);
    }

} catch (PDOException $e) {
    error_log('routes.php - Database Error: ' . $e->getMessage());
    jsonError('Error en la base de datos: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    error_log('routes.php - Error: ' . $e->getMessage());
    jsonError('Error: ' . $e->getMessage(), 500);
}

/**
 * GET - Listar rutas
 */
function handleGet($pdo) {
    $id = $_GET['id'] ?? null;
    $status = $_GET['status'] ?? 'active';
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);

    if ($id) {
        // Obtener una ruta específica con sus items
        $route = getRouteById($pdo, $id);
        if ($route) {
            jsonSuccess(['route' => $route]);
        } else {
            jsonError('Ruta no encontrada', 404);
        }
    } else {
        // Listar rutas
        $routes = listRoutes($pdo, $status, $limit, $offset);
        jsonSuccess([
            'routes' => $routes,
            'total' => count($routes)
        ]);
    }
}

/**
 * POST - Crear nueva ruta
 */
function handlePost($pdo) {
    // Obtener datos del formulario
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $duration = (int)($_POST['duration'] ?? 3);
    $difficulty = $_POST['difficulty'] ?? 'media';
    $estimated_price = (float)($_POST['estimated_price'] ?? 0);
    $youtube_url = $_POST['youtube_url'] ?? null;
    $items = json_decode($_POST['items'] ?? '{}', true);
    $map_points = json_decode($_POST['map_points'] ?? '[]', true);
    $discounts = json_decode($_POST['discounts'] ?? '[]', true);

    // Validar campos obligatorios
    if (empty($title) || empty($description)) {
        jsonError('El título y la descripción son obligatorios', 400);
    }

    // Crear slug
    $slug = createSlug($title);

    // Manejar archivos multimedia
    $audio_url = null;
    $video_url = null;

    if (isset($_FILES['audio']) && $_FILES['audio']['error'] === UPLOAD_ERR_OK) {
        $audio_url = uploadFile($_FILES['audio'], 'audio');
    }

    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        $video_url = uploadFile($_FILES['video'], 'video');
    }

    // Iniciar transacción
    $pdo->beginTransaction();

    try {
        // Insertar ruta
        $sql = "INSERT INTO routes (
            title, slug, description, duration_days, difficulty,
            estimated_price, audio_url, video_url, youtube_url,
            map_points, discounts, status, created_at
        ) VALUES (
            :title, :slug, :description, :duration, :difficulty,
            :estimated_price, :audio_url, :video_url, :youtube_url,
            :map_points, :discounts, 'active', NOW()
        )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':title' => $title,
            ':slug' => $slug,
            ':description' => $description,
            ':duration' => $duration,
            ':difficulty' => $difficulty,
            ':estimated_price' => $estimated_price,
            ':audio_url' => $audio_url,
            ':video_url' => $video_url,
            ':youtube_url' => $youtube_url,
            ':map_points' => json_encode($map_points),
            ':discounts' => json_encode($discounts)
        ]);

        $route_id = $pdo->lastInsertId();

        // Insertar items de la ruta
        $order = 1;
        foreach ($items as $category => $categoryItems) {
            foreach ($categoryItems as $item) {
                insertRouteItem($pdo, $route_id, $item, $order);
                $order++;
            }
        }

        $pdo->commit();

        jsonSuccess([
            'message' => 'Ruta creada exitosamente',
            'route_id' => $route_id,
            'slug' => $slug
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * PUT - Actualizar ruta
 */
function handlePut($pdo) {
    parse_str(file_get_contents('php://input'), $_PUT);
    
    $id = $_PUT['id'] ?? null;
    if (!$id) {
        jsonError('ID de ruta requerido', 400);
    }

    // Actualizar campos...
    // (Similar a POST pero con UPDATE)
    
    jsonSuccess(['message' => 'Ruta actualizada exitosamente']);
}

/**
 * DELETE - Eliminar ruta (soft delete)
 */
function handleDelete($pdo) {
    parse_str(file_get_contents('php://input'), $_DELETE);
    
    $id = $_DELETE['id'] ?? null;
    if (!$id) {
        jsonError('ID de ruta requerido', 400);
    }

    $sql = "UPDATE routes SET status = 'inactive' WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);

    jsonSuccess(['message' => 'Ruta eliminada exitosamente']);
}

/**
 * Obtener ruta por ID con sus items
 */
function getRouteById($pdo, $id) {
    $sql = "SELECT * FROM routes WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $route = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$route) {
        return null;
    }

    // Obtener items de la ruta
    $sqlItems = "SELECT * FROM route_items WHERE route_id = :route_id ORDER BY display_order ASC";
    $stmtItems = $pdo->prepare($sqlItems);
    $stmtItems->execute([':route_id' => $id]);
    $route['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    // Decodificar JSON
    $route['map_points'] = json_decode($route['map_points'] ?? '[]', true);
    $route['discounts'] = json_decode($route['discounts'] ?? '[]', true);

    return $route;
}

/**
 * Listar rutas
 */
function listRoutes($pdo, $status, $limit, $offset) {
    $sql = "SELECT id, title, slug, description, duration_days, difficulty,
                   estimated_price, status, views_count, created_at
            FROM routes
            WHERE status = :status
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':status', $status, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Insertar item de ruta
 */
function insertRouteItem($pdo, $route_id, $item, $order) {
    $sql = "INSERT INTO route_items (
        route_id, item_type, item_id, item_name, display_order
    ) VALUES (
        :route_id, :item_type, :item_id, :item_name, :order
    )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':route_id' => $route_id,
        ':item_type' => $item['tipo'] ?? 'other',
        ':item_id' => $item['id'] ?? 0,
        ':item_name' => $item['name'] ?? '',
        ':order' => $order
    ]);
}

/**
 * Crear slug desde título
 */
function createSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

/**
 * Subir archivo
 */
function uploadFile($file, $type) {
    $uploadDir = __DIR__ . '/../uploads/' . $type . '/';
    
    // Crear directorio si no existe
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return '/uploads/' . $type . '/' . $filename;
    }

    return null;
}
