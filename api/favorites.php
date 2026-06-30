<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];
$pdo = getDBConnection();

// Auto-crear tabla si no existe
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        entity_type VARCHAR(50) NOT NULL,
        entity_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_entity (user_id, entity_type, entity_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
} catch (PDOException $e) {
    // No hacer nada si falla, la API devolverá error después
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    f.id, f.entity_type, f.entity_id, f.created_at,
                    CASE f.entity_type
                        WHEN 'activity' THEN a.name
                        WHEN 'accommodation' THEN ac.name
                        WHEN 'place' THEN p.name
                        WHEN 'event' THEN e.name
                        ELSE NULL
                    END as entity_name,
                    CASE f.entity_type
                        WHEN 'activity' THEN a.slug
                        WHEN 'accommodation' THEN ac.slug
                        WHEN 'place' THEN p.slug
                        WHEN 'event' THEN e.slug
                        ELSE NULL
                    END as entity_slug
                FROM favorites f
                LEFT JOIN tourist_activities a ON f.entity_type = 'activity' AND f.entity_id = a.id
                LEFT JOIN accommodations ac ON f.entity_type = 'accommodation' AND f.entity_id = ac.id
                LEFT JOIN places_of_interest p ON f.entity_type = 'place' AND f.entity_id = p.id
                LEFT JOIN cultural_events e ON f.entity_type = 'event' AND f.entity_id = e.id
                WHERE f.user_id = ?
                ORDER BY f.created_at DESC
            ");
            $stmt->execute([$userId]);
            $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $favorites]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'toggle':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'POST method required']);
            exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $entityType = $data['entity_type'] ?? null;
        $entityId = $data['entity_id'] ?? null;

        if (!$entityType || !$entityId) {
            echo json_encode(['success' => false, 'error' => 'entity_type and entity_id are required']);
            exit;
        }

        try {
            // Verificar si ya es favorito
            $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND entity_type = ? AND entity_id = ?");
            $stmt->execute([$userId, $entityType, $entityId]);
            $isFavorite = $stmt->fetch();

            if ($isFavorite) {
                // Si ya es favorito, eliminarlo
                $deleteStmt = $pdo->prepare("DELETE FROM favorites WHERE id = ?");
                $deleteStmt->execute([$isFavorite['id']]);
                echo json_encode(['success' => true, 'status' => 'removed']);
            } else {
                // Si no es favorito, añadirlo
                $insertStmt = $pdo->prepare("INSERT INTO favorites (user_id, entity_type, entity_id) VALUES (?, ?, ?)");
                $insertStmt->execute([$userId, $entityType, $entityId]);
                echo json_encode(['success' => true, 'status' => 'added', 'id' => $pdo->lastInsertId()]);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'check':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['success' => false, 'error' => 'GET method required']);
            exit;
        }
        $entityType = $_GET['entity_type'] ?? null;
        $entityId = $_GET['entity_id'] ?? null;

        if (!$entityType || !$entityId) {
            echo json_encode(['success' => false, 'error' => 'entity_type and entity_id are required']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND entity_type = ? AND entity_id = ?");
        $stmt->execute([$userId, $entityType, $entityId]);
        echo json_encode(['success' => true, 'is_favorite' => $stmt->fetch() !== false]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}
?>