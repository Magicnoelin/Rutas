<?php
/**
 * Prueba de conexión a base de datos
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

try {
    $pdo = getDBConnection();
    
    // Verificar que la tabla accommodations existe
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    // Obtener estructura de la tabla accommodations
    $columns = [];
    if (in_array('accommodations', $tables)) {
        $stmt = $pdo->query("DESCRIBE accommodations");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Conexión a BD exitosa',
        'host' => DB_HOST,
        'database' => DB_NAME,
        'user' => DB_USER,
        'tables' => $tables,
        'accommodations_columns' => array_column($columns, 'Field'),
        'has_updated_at' => in_array('updated_at', array_column($columns, 'Field'))
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de conexión',
        'message' => $e->getMessage(),
        'host' => DB_HOST
    ], JSON_PRETTY_PRINT);
}
