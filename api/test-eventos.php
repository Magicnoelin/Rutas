<?php
/**
 * Test simple para verificar la API de eventos
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

$response = ['success' => false, 'data' => null, 'message' => '', 'debug' => []];

try {
    // Conectar a la base de datos
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8mb4");
    
    $response['debug'][] = 'Conexión exitosa';

    // Verificar si la tabla existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'cultural_events'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        $response['message'] = 'La tabla cultural_events no existe';
        echo json_encode($response);
        exit;
    }
    
    $response['debug'][] = 'Tabla existe';

    // Ver estructura de la tabla
    $stmt = $pdo->query("DESCRIBE cultural_events");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $response['debug']['columns'] = $columns;

    // Intentar obtener eventos
    $sql = "SELECT * FROM cultural_events LIMIT 5";
    $stmt = $pdo->query($sql);
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['success'] = true;
    $response['data'] = $eventos;
    $response['total'] = count($eventos);
    $response['message'] = 'Test exitoso';

} catch (PDOException $e) {
    $response['message'] = 'Error de base de datos: ' . $e->getMessage();
    $response['debug'][] = 'Error: ' . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    $response['debug'][] = 'Error: ' . $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
