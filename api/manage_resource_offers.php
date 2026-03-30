<?php
/**
 * API: Gestionar ofertas de recursos
 * GET /api/manage_resource_offers.php - Listar ofertas del usuario
 * POST /api/manage_resource_offers.php - Crear oferta
 * PUT /api/manage_resource_offers.php - Actualizar oferta
 * DELETE /api/manage_resource_offers.php - Eliminar oferta
 */

session_start();
require_once 'config.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    jsonError('No autenticado', 401);
}

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDBConnection();
    
    // ============================================
    // GET: Listar ofertas del usuario
    // ============================================
    if ($method === 'GET') {
        $resourceType = isset($_GET['resource_type']) ? sanitizeInput($_GET['resource_type']) : null;
        $resourceId = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : null;
        $status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : null;
        
        $sql = "
            SELECT 
                ro.id,
                ro.resource_type,
                ro.resource_id,
                ro.title,
                ro.description,
                ro.offer_type,
                ro.original_price,
                ro.offer_price,
                ro.discount_percentage,
                ro.valid_from,
                ro.valid_until,
                ro.max_uses,
                ro.current_uses,
                ro.terms_conditions,
                ro.min_people,
                ro.max_people,
                ro.status,
                ro.is_featured,
                ro.created_at,
                ro.published_at,
                
                -- Nombre del recurso
                CASE ro.resource_type
                    WHEN 'accommodation' THEN (SELECT name FROM accommodations WHERE id = ro.resource_id)
                    WHEN 'place' THEN (SELECT name FROM places_of_interest WHERE id = ro.resource_id)
                    WHEN 'activity' THEN (SELECT name FROM tourist_activities WHERE id = ro.resource_id)
                    WHEN 'event' THEN (SELECT title FROM cultural_events WHERE id = ro.resource_id)
                END AS resource_name,
                
                -- Días restantes
                DATEDIFF(ro.valid_until, CURDATE()) AS days_remaining
                
            FROM resource_offers ro
            WHERE ro.user_id = :user_id
        ";
        
        $params = ['user_id' => $userId];
        
        if ($resourceType) {
            $sql .= " AND ro.resource_type = :resource_type";
            $params['resource_type'] = $resourceType;
        }
        
        if ($resourceId) {
            $sql .= " AND ro.resource_id = :resource_id";
            $params['resource_id'] = $resourceId;
        }
        
        if ($status) {
            $sql .= " AND ro.status = :status";
            $params['status'] = $status;
        }
        
        $sql .= " ORDER BY ro.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $offers = $stmt->fetchAll();
        
        jsonSuccess(['offers' => $offers, 'total' => count($offers)]);
    }
    
    // ============================================
    // POST: Crear nueva oferta
    // ============================================
    elseif ($method === 'POST') {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (!$data) {
            jsonError('Datos JSON inválidos', 400);
        }
        
        // Validar campos requeridos
        $required = ['resource_type', 'resource_id', 'title', 'offer_price', 'valid_from', 'valid_until'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                jsonError("Campo requerido: $field", 400);
            }
        }
        
        $resourceType = sanitizeInput($data['resource_type']);
        $resourceId = (int)$data['resource_id'];
        
        // Verificar que el usuario tiene permisos sobre el recurso
        $stmtCheck = $pdo->prepare("
            SELECT id, role, status 
            FROM user_resources 
            WHERE user_id = :user_id 
            AND resource_type = :resource_type 
            AND resource_id = :resource_id
            AND status = 'active'
        ");
        $stmtCheck->execute([
            'user_id' => $userId,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId
        ]);
        $permission = $stmtCheck->fetch();
        
        if (!$permission) {
            jsonError('No tienes permisos para crear ofertas en este recurso', 403);
        }
        
        // Insertar oferta
        $stmtInsert = $pdo->prepare("
            INSERT INTO resource_offers (
                user_id, resource_type, resource_id,
                title, description, offer_type,
                original_price, offer_price,
                valid_from, valid_until,
                max_uses, terms_conditions,
                min_people, max_people,
                status, is_featured
            ) VALUES (
                :user_id, :resource_type, :resource_id,
                :title, :description, :offer_type,
                :original_price, :offer_price,
                :valid_from, :valid_until,
                :max_uses, :terms_conditions,
                :min_people, :max_people,
                :status, :is_featured
            )
        ");
        
        $stmtInsert->execute([
            'user_id' => $userId,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'title' => sanitizeInput($data['title']),
            'description' => isset($data['description']) ? sanitizeInput($data['description']) : null,
            'offer_type' => isset($data['offer_type']) ? sanitizeInput($data['offer_type']) : 'discount',
            'original_price' => isset($data['original_price']) ? (float)$data['original_price'] : null,
            'offer_price' => (float)$data['offer_price'],
            'valid_from' => $data['valid_from'],
            'valid_until' => $data['valid_until'],
            'max_uses' => isset($data['max_uses']) ? (int)$data['max_uses'] : null,
            'terms_conditions' => isset($data['terms_conditions']) ? sanitizeInput($data['terms_conditions']) : null,
            'min_people' => isset($data['min_people']) ? (int)$data['min_people'] : 1,
            'max_people' => isset($data['max_people']) ? (int)$data['max_people'] : null,
            'status' => isset($data['status']) ? sanitizeInput($data['status']) : 'draft',
            'is_featured' => isset($data['is_featured']) ? (bool)$data['is_featured'] : false
        ]);
        
        $offerId = $pdo->lastInsertId();
        
        // Obtener la oferta creada
        $stmtGet = $pdo->prepare("SELECT * FROM resource_offers WHERE id = :id");
        $stmtGet->execute(['id' => $offerId]);
        $offer = $stmtGet->fetch();
        
        jsonSuccess(['offer' => $offer], 'Oferta creada exitosamente');
    }
    
    // ============================================
    // PUT: Actualizar oferta existente
    // ============================================
    elseif ($method === 'PUT') {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (!$data || !isset($data['id'])) {
            jsonError('ID de oferta requerido', 400);
        }
        
        $offerId = (int)$data['id'];
        
        // Verificar que la oferta pertenece al usuario
        $stmtCheck = $pdo->prepare("SELECT * FROM resource_offers WHERE id = :id AND user_id = :user_id");
        $stmtCheck->execute(['id' => $offerId, 'user_id' => $userId]);
        $offer = $stmtCheck->fetch();
        
        if (!$offer) {
            jsonError('Oferta no encontrada o no tienes permisos', 404);
        }
        
        // Construir UPDATE dinámico
        $updates = [];
        $params = ['id' => $offerId];
        
        $allowedFields = [
            'title', 'description', 'offer_type',
            'original_price', 'offer_price',
            'valid_from', 'valid_until',
            'max_uses', 'terms_conditions',
            'min_people', 'max_people',
            'status', 'is_featured'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            jsonError('No hay campos para actualizar', 400);
        }
        
        $sql = "UPDATE resource_offers SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmtUpdate = $pdo->prepare($sql);
        $stmtUpdate->execute($params);
        
        // Obtener oferta actualizada
        $stmtGet = $pdo->prepare("SELECT * FROM resource_offers WHERE id = :id");
        $stmtGet->execute(['id' => $offerId]);
        $updatedOffer = $stmtGet->fetch();
        
        jsonSuccess(['offer' => $updatedOffer], 'Oferta actualizada exitosamente');
    }
    
    // ============================================
    // DELETE: Eliminar oferta
    // ============================================
    elseif ($method === 'DELETE') {
        $offerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if (!$offerId) {
            jsonError('ID de oferta requerido', 400);
        }
        
        // Verificar que la oferta pertenece al usuario
        $stmtCheck = $pdo->prepare("SELECT id FROM resource_offers WHERE id = :id AND user_id = :user_id");
        $stmtCheck->execute(['id' => $offerId, 'user_id' => $userId]);
        
        if (!$stmtCheck->fetch()) {
            jsonError('Oferta no encontrada o no tienes permisos', 404);
        }
        
        // Eliminar oferta
        $stmtDelete = $pdo->prepare("DELETE FROM resource_offers WHERE id = :id");
        $stmtDelete->execute(['id' => $offerId]);
        
        jsonSuccess(['deleted' => true], 'Oferta eliminada exitosamente');
    }
    
    else {
        jsonError('Método no permitido', 405);
    }
    
} catch (PDOException $e) {
    error_log('manage_resource_offers.php Error: ' . $e->getMessage());
    jsonError('Error al gestionar ofertas: ' . $e->getMessage(), 500);
}
