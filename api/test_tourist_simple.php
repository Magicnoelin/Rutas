<?php
/**
 * API de prueba simple para diagnosticar problemas
 * NO requiere autenticación - SOLO PARA PRUEBAS
 */

// Permitir acceso temporal sin autenticación
define('API_NO_HEADERS', true);
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDBConnection();
    
    // Test 1: Verificar conexión
    echo json_encode([
        'success' => true,
        'message' => 'Conexión a BD exitosa',
        'db_name' => DB_NAME
    ]);
    exit;
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error de conexión',
        'message' => $e->getMessage()
    ]);
    exit;
}
?>