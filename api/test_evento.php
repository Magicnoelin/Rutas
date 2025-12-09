<?php
/**
 * Test script for cultural_events API
 */

require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

echo json_encode([
    'status' => 'API working',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
    'database_config' => [
        'host' => DB_HOST,
        'database' => DB_NAME,
        'user' => DB_USER
    ]
]);
?>
