<?php
/**
 * API Endpoint: Obtener Lugares de Interés
 * GET /api/lugares_interes.php
 * Parámetros opcionales:
 * - page: número de página (default: 1)
 * - limit: items por página (default: 20)
 * - category_id: filtrar por categoría
 * - province: filtrar por provincia
 * - municipality: filtrar por municipio
 * - is_featured: mostrar solo destacados (0/1)
 * - is_active: mostrar solo activos (0/1, default: 1)
 */

require_once 'config.php';

// Solo permitir método GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Método no permitido', 405);
}

try {
    $pdo = getDBConnection();

    // Obtener parámetros de paginación
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;

    // Construir query base
    $where = [];
    $params = [];

    // Solo mostrar lugares activos por defecto
    $isActive = isset($_GET['is_active']) ? intval($_GET['is_active']) : 1;
    $where[] = "is_active = :is_active";
    $params[':is_active'] = $isActive;

    // Filtro por categoría
    if (isset($_GET['category_id']) && !empty($_GET['category_id'])) {
        $where[] = "category_id = :category_id";
        $params[':category_id'] = intval($_GET['category_id']);
    }

    // Filtro por provincia
    if (isset($_GET['province']) && !empty($_GET['province'])) {
        $where[] = "province = :province";
        $params[':province'] = sanitizeInput($_GET['province']);
    }

    // Filtro por municipio
    if (isset($_GET['municipality']) && !empty($_GET['municipality'])) {
        $where[] = "municipality = :municipality";
        $params[':municipality'] = sanitizeInput($_GET['municipality']);
    }

    // Filtro por destacados
    if (isset($_GET['is_featured']) && $_GET['is_featured'] == '1') {
        $where[] = "is_featured = 1";
    }

    // Construir WHERE clause
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Contar total de registros
    $countSql = "SELECT COUNT(*) as total FROM places_of_interest " . $whereClause;
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch()['total'];

    // Obtener registros paginados
    $orderBy = "is_featured DESC, created_at DESC, name ASC";
    $sql = "SELECT * FROM places_of_interest " . $whereClause . " ORDER BY " . $orderBy . " LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);

    // Bind parámetros
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $lugares = $stmt->fetchAll();

    // Procesar datos para el frontend
    $lugaresProcesados = array_map(function($lugar) {
        // Asegurar que los campos booleanos sean tratados correctamente
        $lugar['pet_friendly'] = (bool)$lugar['pet_friendly'];
        $lugar['suitable_for_children'] = (bool)$lugar['suitable_for_children'];
        $lugar['is_featured'] = (bool)$lugar['is_featured'];
        $lugar['is_active'] = (bool)$lugar['is_active'];
        $lugar['verified'] = (bool)$lugar['verified'];

        // Procesar campos JSON si existen
        if (!empty($lugar['opening_hours'])) {
            $lugar['opening_hours'] = json_decode($lugar['opening_hours'], true);
        }
        if (!empty($lugar['accessibility'])) {
            $lugar['accessibility'] = json_decode($lugar['accessibility'], true);
        }
        if (!empty($lugar['facilities'])) {
            $lugar['facilities'] = json_decode($lugar['facilities'], true);
        }
        if (!empty($lugar['languages_available'])) {
            $lugar['languages_available'] = json_decode($lugar['languages_available'], true);
        }
        if (!empty($lugar['gallery'])) {
            $lugar['gallery'] = json_decode($lugar['gallery'], true);
        }

        return $lugar;
    }, $lugares);

    // Calcular información de paginación
    $totalPages = ceil($totalRecords / $limit);

    // Respuesta exitosa
    jsonSuccess([
        'lugares' => $lugaresProcesados,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $limit,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ]
    ], 'Lugares de interés obtenidos correctamente');

} catch (PDOException $e) {
    jsonError('Error al obtener lugares de interés: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    jsonError('Error inesperado: ' . $e->getMessage(), 500);
}
?>
