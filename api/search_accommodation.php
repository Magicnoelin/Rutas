<?php
/**
 * API Endpoint: Buscar Alojamiento por Nombre o Slug
 * GET /api/search_accommodation.php?query={nombre_o_slug}
 */

require_once 'config.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Método no permitido', 405);
}

if (!isset($_GET['query']) || empty($_GET['query'])) {
    jsonError('Parámetro query requerido', 400);
}

$query = sanitizeInput($_GET['query']);

try {
    $pdo = getDBConnection();

    // Buscar alojamientos que coincidan con el nombre o slug
    $stmt = $pdo->prepare("
        SELECT id, name, slug, municipality, province, accommodation_type
        FROM accommodations
        WHERE name LIKE ? OR slug LIKE ?
        ORDER BY name
        LIMIT 10
    ");
    
    $searchPattern = '%' . $query . '%';
    $stmt->execute([$searchPattern, $searchPattern]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($results) === 0) {
        jsonSuccess([], 'No se encontraron alojamientos con ese término de búsqueda');
    }

    jsonSuccess([
        'results' => $results,
        'count' => count($results)
    ], 'Alojamientos encontrados');

} catch (PDOException $e) {
    error_log('Error searching accommodations: ' . $e->getMessage());
    jsonError('Error al buscar alojamientos: ' . $e->getMessage(), 500);
}
?>