<?php
/**
 * API: route-faqs.php
 * CRUD para preguntas frecuentes de rutas (tabla route_faqs)
 * 
 * GET    ?route_id=X          → Listar FAQs de una ruta
 * POST   (JSON body)          → Crear nueva FAQ
 * PUT    ?id=X (JSON body)    → Actualizar FAQ
 * DELETE ?id=X                → Eliminar FAQ
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    $pdo = getDBConnection();
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {

        // ── GET: Listar FAQs de una ruta ─────────────────────────────────
        case 'GET':
            $routeId = isset($_GET['route_id']) ? (int)$_GET['route_id'] : 0;
            if ($routeId <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'route_id es requerido']);
                exit;
            }

            $stmt = $pdo->prepare("
                SELECT id, route_id, question, answer, display_order, is_active, created_at, updated_at
                FROM route_faqs
                WHERE route_id = :route_id AND is_active = 1
                ORDER BY display_order ASC, id ASC
            ");
            $stmt->execute([':route_id' => $routeId]);
            $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $faqs], JSON_UNESCAPED_UNICODE);
            break;

        // ── POST: Crear nueva FAQ ────────────────────────────────────────
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || empty($input['route_id']) || empty($input['question']) || empty($input['answer'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Faltan campos requeridos: route_id, question, answer']);
                exit;
            }

            $stmt = $pdo->prepare("
                INSERT INTO route_faqs (route_id, question, answer, display_order, is_active)
                VALUES (:route_id, :question, :answer, :display_order, :is_active)
            ");
            $stmt->execute([
                ':route_id'      => (int)$input['route_id'],
                ':question'      => trim($input['question']),
                ':answer'        => trim($input['answer']),
                ':display_order' => (int)($input['display_order'] ?? 0),
                ':is_active'     => isset($input['is_active']) ? (int)$input['is_active'] : 1,
            ]);

            $newId = $pdo->lastInsertId();
            echo json_encode(['success' => true, 'id' => $newId, 'message' => 'FAQ creada correctamente'], JSON_UNESCAPED_UNICODE);
            break;

        // ── PUT: Actualizar FAQ existente ────────────────────────────────
        case 'PUT':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'id es requerido']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                http_response_code(400);
                echo json_encode(['error' => 'Body JSON requerido']);
                exit;
            }

            $updates = [];
            $params = [':id' => $id];

            if (isset($input['question'])) {
                $updates[] = 'question = :question';
                $params[':question'] = trim($input['question']);
            }
            if (isset($input['answer'])) {
                $updates[] = 'answer = :answer';
                $params[':answer'] = trim($input['answer']);
            }
            if (isset($input['display_order'])) {
                $updates[] = 'display_order = :display_order';
                $params[':display_order'] = (int)$input['display_order'];
            }
            if (isset($input['is_active'])) {
                $updates[] = 'is_active = :is_active';
                $params[':is_active'] = (int)$input['is_active'];
            }

            if (empty($updates)) {
                http_response_code(400);
                echo json_encode(['error' => 'No hay campos para actualizar']);
                exit;
            }

            $sql = "UPDATE route_faqs SET " . implode(', ', $updates) . " WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            echo json_encode(['success' => true, 'message' => 'FAQ actualizada correctamente'], JSON_UNESCAPED_UNICODE);
            break;

        // ── DELETE: Eliminar FAQ ─────────────────────────────────────────
        case 'DELETE':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'id es requerido']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM route_faqs WHERE id = :id");
            $stmt->execute([':id' => $id]);

            echo json_encode(['success' => true, 'message' => 'FAQ eliminada correctamente'], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno: ' . $e->getMessage()]);
}
