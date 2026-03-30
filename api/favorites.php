<?php
/**
 * API de Favoritos
 * GET  /api/favorites.php?entity_type=accommodation&entity_id=123  → comprueba si es favorito
 * POST /api/favorites.php  { entity_type, entity_id, action: 'add'|'remove' }  → añade/quita favorito
 */
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verificar autenticación
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'No autenticado', 'code' => 401]);
    exit;
}

try {
    $pdo = getDBConnection();

    // ── GET: comprobar si ya es favorito ──────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $entityType = $_GET['entity_type'] ?? '';
        $entityId   = (int)($_GET['entity_id'] ?? 0);

        if (!$entityType || !$entityId) {
            echo json_encode(['success' => false, 'error' => 'Parámetros requeridos']);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT id FROM favorites
            WHERE user_id = :user_id AND entity_type = :entity_type AND entity_id = :entity_id
            LIMIT 1
        ");
        $stmt->execute([
            ':user_id'     => $userId,
            ':entity_type' => $entityType,
            ':entity_id'   => $entityId,
        ]);
        $fav = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'is_favorite' => (bool)$fav]);
        exit;
    }

    // ── POST: añadir o quitar favorito ────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input      = json_decode(file_get_contents('php://input'), true) ?? [];
        $entityType = $input['entity_type'] ?? '';
        $entityId   = (int)($input['entity_id'] ?? 0);
        $action     = $input['action'] ?? 'add'; // 'add' | 'remove'

        $validTypes = ['accommodation', 'place', 'activity', 'event'];
        if (!in_array($entityType, $validTypes) || !$entityId) {
            echo json_encode(['success' => false, 'error' => 'Parámetros inválidos']);
            exit;
        }

        // Mapear entity_type → resource_type para resource_stats
        $resourceType = $entityType; // son iguales en este sistema

        if ($action === 'add') {
            // Insertar en favorites (ignorar si ya existe)
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO favorites (user_id, entity_type, entity_id)
                VALUES (:user_id, :entity_type, :entity_id)
            ");
            $stmt->execute([
                ':user_id'     => $userId,
                ':entity_type' => $entityType,
                ':entity_id'   => $entityId,
            ]);
            $inserted = $stmt->rowCount();

            // Solo incrementar si realmente se insertó (no era duplicado)
            if ($inserted > 0) {
                // Asegurar que existe la fila en resource_stats
                $pdo->prepare("
                    INSERT IGNORE INTO resource_stats (resource_type, resource_id)
                    VALUES (:rt, :rid)
                ")->execute([':rt' => $resourceType, ':rid' => $entityId]);

                // Incrementar favorites_count
                $pdo->prepare("
                    UPDATE resource_stats
                    SET favorites_count = favorites_count + 1
                    WHERE resource_type = :rt AND resource_id = :rid
                ")->execute([':rt' => $resourceType, ':rid' => $entityId]);
            }

            echo json_encode(['success' => true, 'action' => 'added', 'is_favorite' => true]);

        } elseif ($action === 'remove') {
            // Eliminar de favorites
            $stmt = $pdo->prepare("
                DELETE FROM favorites
                WHERE user_id = :user_id AND entity_type = :entity_type AND entity_id = :entity_id
            ");
            $stmt->execute([
                ':user_id'     => $userId,
                ':entity_type' => $entityType,
                ':entity_id'   => $entityId,
            ]);
            $deleted = $stmt->rowCount();

            // Solo decrementar si realmente se borró
            if ($deleted > 0) {
                $pdo->prepare("
                    UPDATE resource_stats
                    SET favorites_count = GREATEST(favorites_count - 1, 0)
                    WHERE resource_type = :rt AND resource_id = :rid
                ")->execute([':rt' => $resourceType, ':rid' => $entityId]);
            }

            echo json_encode(['success' => true, 'action' => 'removed', 'is_favorite' => false]);

        } else {
            echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Método no permitido']);

} catch (Exception $e) {
    error_log('favorites.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error del servidor']);
}
