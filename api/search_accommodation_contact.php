<?php
/**
 * API: Buscar Alojamiento para Contacto
 * Devuelve alojamientos con su owner_user_id para iniciar chat
 * GET /api/search_accommodation_contact.php?query=...&province=...
 */

require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Requiere autenticación
if (!isset($_SESSION['user_id'])) {
    jsonError('Debes iniciar sesión', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Método no permitido', 405);
}

$query    = isset($_GET['query'])    ? sanitizeInput($_GET['query'])    : '';
$province = isset($_GET['province']) ? sanitizeInput($_GET['province']) : '';
$limit    = isset($_GET['limit'])    ? max(1, min(20, (int)$_GET['limit'])) : 10;

try {
    $pdo = getDBConnection();

    // Verificar si la tabla user_resources existe para obtener el owner
    $hasUserResources = false;
    try {
        $check = $pdo->query("SHOW TABLES LIKE 'user_resources'");
        $hasUserResources = $check->rowCount() > 0;
    } catch (Exception $e) {
        $hasUserResources = false;
    }

    $conditions = ["a.status = 'active' OR a.status IS NULL"];
    $params = [];

    if (!empty($query)) {
        $conditions[] = "(a.name LIKE :query OR a.municipality LIKE :query OR a.province LIKE :query OR a.slug LIKE :query)";
        $params[':query'] = '%' . $query . '%';
    }

    if (!empty($province)) {
        $conditions[] = "a.province LIKE :province";
        $params[':province'] = '%' . $province . '%';
    }

    $whereClause = implode(' AND ', $conditions);

    if ($hasUserResources) {
        // Con tabla user_resources para obtener el propietario
        $sql = "
            SELECT 
                a.id,
                a.name,
                a.slug,
                a.municipality,
                a.province,
                a.accommodation_type,
                a.price_per_night,
                a.max_guests,
                a.description,
                ur.user_id AS owner_user_id,
                u.first_name AS owner_first_name,
                u.last_name AS owner_last_name
            FROM accommodations a
            LEFT JOIN user_resources ur ON a.id = ur.resource_id 
                AND ur.resource_type = 'accommodation' 
                AND ur.role = 'owner'
            LEFT JOIN users u ON ur.user_id = u.id
            WHERE $whereClause
            ORDER BY a.name ASC
            LIMIT :limit
        ";
    } else {
        // Sin user_resources, intentar con columna owner_user_id directa
        $checkCols = $pdo->query("SHOW COLUMNS FROM accommodations")->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('owner_user_id', $checkCols)) {
            $sql = "
                SELECT 
                    a.id,
                    a.name,
                    a.slug,
                    a.municipality,
                    a.province,
                    a.accommodation_type,
                    a.price_per_night,
                    a.max_guests,
                    a.description,
                    a.owner_user_id,
                    u.first_name AS owner_first_name,
                    u.last_name AS owner_last_name
                FROM accommodations a
                LEFT JOIN users u ON a.owner_user_id = u.id
                WHERE $whereClause
                ORDER BY a.name ASC
                LIMIT :limit
            ";
        } else {
            // Fallback sin owner
            $sql = "
                SELECT 
                    a.id,
                    a.name,
                    a.slug,
                    a.municipality,
                    a.province,
                    a.accommodation_type,
                    a.price_per_night,
                    a.max_guests,
                    a.description,
                    NULL AS owner_user_id,
                    NULL AS owner_first_name,
                    NULL AS owner_last_name
                FROM accommodations a
                WHERE $whereClause
                ORDER BY a.name ASC
                LIMIT :limit
            ";
        }
    }

    $params[':limit'] = $limit;
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $type = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($key, $val, $type);
    }
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatear resultados
    $formatted = array_map(function($row) {
        return [
            'id'                => (int)$row['id'],
            'name'              => $row['name'],
            'slug'              => $row['slug'],
            'municipality'      => $row['municipality'],
            'province'          => $row['province'],
            'accommodation_type'=> $row['accommodation_type'],
            'price_per_night'   => $row['price_per_night'] ? (float)$row['price_per_night'] : null,
            'max_guests'        => $row['max_guests'] ? (int)$row['max_guests'] : null,
            'description'       => $row['description'] ? substr($row['description'], 0, 120) . '...' : '',
            'owner_user_id'     => $row['owner_user_id'] ? (int)$row['owner_user_id'] : null,
            'owner_name'        => trim(($row['owner_first_name'] ?? '') . ' ' . ($row['owner_last_name'] ?? '')) ?: null,
            'has_owner'         => !empty($row['owner_user_id']),
        ];
    }, $results);

    jsonSuccess([
        'results' => $formatted,
        'count'   => count($formatted),
        'query'   => $query
    ], count($formatted) > 0 ? 'Alojamientos encontrados' : 'Sin resultados');

} catch (PDOException $e) {
    error_log('search_accommodation_contact.php Error: ' . $e->getMessage());
    jsonError('Error en la búsqueda: ' . $e->getMessage(), 500);
}
?>
