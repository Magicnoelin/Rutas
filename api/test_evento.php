<?php
/**
 * Test script for cultural_events API
 */

require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $pdo = getDBConnection();

    // Test table creation
    $sqlCheckTable = "SHOW TABLES LIKE 'cultural_events'";
    $result = $pdo->query($sqlCheckTable);
    $tableExists = $result->rowCount() > 0;

    if (!$tableExists) {
        $sqlCreateTable = "
            CREATE TABLE cultural_events (
                id VARCHAR(50) PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                event_date DATE NOT NULL,
                event_time TIME,
                location VARCHAR(255),
                category VARCHAR(100),
                image VARCHAR(500),
                organizer VARCHAR(255),
                contact_email VARCHAR(255),
                contact_phone VARCHAR(50),
                website VARCHAR(255),
                price DECIMAL(10,2),
                capacity INT,
                status ENUM('active', 'inactive', 'cancelled') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $pdo->exec($sqlCreateTable);
        $tableCreated = true;
    } else {
        $tableCreated = false;
    }

    // Test insert
    $testId = 'test_' . time();
    $stmt = $pdo->prepare("INSERT INTO cultural_events (id, title, description, event_date, location, category, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$testId, 'Test Event', 'Test Description', '2025-12-31', 'Test Location', 'Test Category', 'active']);

    // Test select
    $stmt = $pdo->prepare("SELECT * FROM cultural_events WHERE id = ?");
    $stmt->execute([$testId]);
    $testEvent = $stmt->fetch();

    // Clean up test data
    $stmt = $pdo->prepare("DELETE FROM cultural_events WHERE id = ?");
    $stmt->execute([$testId]);

    echo json_encode([
        'status' => 'API working',
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => phpversion(),
        'database_config' => [
            'host' => DB_HOST,
            'database' => DB_NAME,
            'user' => DB_USER
        ],
        'table_exists' => $tableExists,
        'table_created' => $tableCreated,
        'test_insert_select' => $testEvent ? 'SUCCESS' : 'FAILED',
        'pdo_driver' => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'ERROR',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
