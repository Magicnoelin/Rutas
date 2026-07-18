<?php
/**
 * DEBUG: Diagnóstico de search_accommodation_contact.php
 * ELIMINAR DESPUÉS DE USAR
 */

// Mostrar todos los errores para diagnóstico
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$debug = [];
$debug['session_user_id'] = $_SESSION['user_id'] ?? 'NO SESSION';
$debug['php_version'] = PHP_VERSION;

try {
    $pdo = getDBConnection();
    $debug['db_connection'] = 'OK';
    
    // 1. Verificar tabla accommodations
    $tables = $pdo->query("SHOW TABLES LIKE 'accommodations'")->rowCount();
    $debug['table_accommodations_exists'] = $tables > 0;
    
    // 2. Verificar columnas
    $cols = $pdo->query("SHOW COLUMNS FROM accommodations")->fetchAll(PDO::FETCH_ASSOC);
    $colNames = array_column($cols, 'Field');
    $debug['columns'] = $colNames;
    $debug['has_status'] = in_array('status', $colNames);
    $debug['has_owner_user_id'] = in_array('owner_user_id', $colNames);
    
    // 3. Verificar tabla user_resources
    $ur = $pdo->query("SHOW TABLES LIKE 'user_resources'")->rowCount();
    $debug['table_user_resources_exists'] = $ur > 0;
    
    if ($ur > 0) {
        $urCols = $pdo->query("SHOW COLUMNS FROM user_resources")->fetchAll(PDO::FETCH_ASSOC);
        $debug['user_resources_columns'] = array_column($urCols, 'Field');
    }
    
    // 4. Contar alojamientos
    $count = $pdo->query("SELECT COUNT(*) FROM accommodations")->fetchColumn();
    $debug['total_accommodations'] = $count;
    
    // 5. Probar query simple
    try {
        $sample = $pdo->query("SELECT id, name, municipality, province FROM accommodations LIMIT 3")->fetchAll();
        $debug['sample_accommodations'] = $sample;
    } catch (Exception $e) {
        $debug['sample_error'] = $e->getMessage();
    }
    
    // 6. Probar el FETCH_COLUMN que podría fallar
    try {
        $checkCols = $pdo->query("SHOW COLUMNS FROM accommodations")->fetchAll(PDO::FETCH_COLUMN);
        $debug['fetch_column_test'] = 'OK - count: ' . count($checkCols);
        $debug['fetch_column_values'] = $checkCols;
    } catch (Exception $e) {
        $debug['fetch_column_error'] = $e->getMessage();
    }
    
} catch (Exception $e) {
    $debug['db_error'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode(['debug' => $debug], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
