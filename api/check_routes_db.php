<?php
require_once 'config.php';
header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $results = [];
    $tables = ['routes', 'route_items'];

    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            $results[$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $results[$table] = "Error or table not found: " . $e->getMessage();
        }
    }

    echo json_encode(['success' => true, 'data' => $results]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>