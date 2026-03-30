<?php
/**
 * Media Manager Search API - Rutas Rurales
 * Este archivo busca recursos en las 4 tablas principales y devuelve su ID de categoría
 * para que el panel de fotos adapte sus iconos automáticamente.
 */

header('Content-Type: application/json');
include 'db_connect.php'; 

// 1. Obtener parámetros de búsqueda
$query = $_GET['query'] ?? '';
$type  = $_GET['type'] ?? 'accommodations'; 

// 2. Seguridad: Validar tablas permitidas (nombres en plural exactos de tu DB)
$allowed_tables = [
    'accommodations', 
    'cultural_events', 
    'places_of_interest', 
    'activities'
];

if (!in_array($type, $allowed_tables)) {
    echo json_encode(['success' => false, 'error' => 'Tabla no permitida']);
    exit;
}

// 3. Validar longitud mínima de búsqueda
if (strlen($query) < 2) {
    echo json_encode(['success' => true, 'results' => []]);
    exit;
}

/**
 * 4. DETECTAR COLUMNA DE CATEGORÍA
 * Según tus capturas de phpMyAdmin:
 * - places_of_interest: usa 'category_id'
 * - activities: usa 'category_id'
 * - cultural_events: usa 'category_id'
 * - accommodations: no suele usar ID de categoría (ponemos NULL)
 */
$category_column = "NULL as category_id"; 

if ($type === 'places_of_interest' || $type === 'activities' || $type === 'cultural_events') {
    $category_column = "category_id";
}

// 5. PREPARAR Y EJECUTAR CONSULTA
// Buscamos por nombre o por slug
$sql = "SELECT id, name, slug, $category_column 
        FROM $type 
        WHERE (name LIKE ? OR slug LIKE ?) 
        LIMIT 10";

try {
    $stmt = $conn->prepare($sql);
    $searchTerm = "%" . $query . "%";
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();

    $results = [];
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }

    // 6. RESPUESTA EXITOSA
    echo json_encode([
        'success' => true,
        'type_searched' => $type,
        'results' => $results
    ]);

} catch (Exception $e) {
    // 7. GESTIÓN DE ERRORES
    echo json_encode([
        'success' => false,
        'error' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
}

$stmt->close();
$conn->close();