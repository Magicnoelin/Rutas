<?php
/**
 * API Endpoint: Obtener Categorías de Lugares de Interés
 * GET /api/categories_places.php
 * Parámetros opcionales:
 * - is_active: mostrar solo categorías activas (0/1, default: 1)
 */

require_once 'config.php';

// Solo permitir método GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Método no permitido', 405);
}

try {
    $pdo = getDBConnection();

    // Obtener parámetros
    $isActive = isset($_GET['is_active']) ? intval($_GET['is_active']) : 1;

    // Construir query
    $where = [];
    $params = [];

    if ($isActive !== null) {
        $where[] = "is_active = :is_active";
        $params[':is_active'] = $isActive;
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Obtener categorías ordenadas por display_order
    $sql = "SELECT id, name, slug, description, icon, color, display_order FROM categories_places " . $whereClause . " ORDER BY display_order ASC, name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $categories = $stmt->fetchAll();

    // Procesar datos para el frontend
    $categoriesProcesadas = array_map(function($category) {
        return [
            'id' => (int)$category['id'],
            'name' => $category['name'],
            'slug' => $category['slug'],
            'description' => $category['description'],
            'icon' => $category['icon'],
            'color' => $category['color'],
            'display_order' => (int)$category['display_order']
        ];
    }, $categories);

    // Respuesta exitosa
    jsonSuccess([
        'categories' => $categoriesProcesadas,
        'total' => count($categoriesProcesadas)
    ], 'Categorías obtenidas correctamente');

} catch (PDOException $e) {
    jsonError('Error al obtener categorías: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    jsonError('Error inesperado: ' . $e->getMessage(), 500);
}
?>
