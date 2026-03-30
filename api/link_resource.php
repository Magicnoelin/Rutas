<?php
/**
 * API: Vincular recurso con usuario
 * POST /api/link_resource.php
 * Body: { resource_type, resource_id, role }
 */

session_start();
require_once 'config.php';

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    jsonError('No autenticado', 401);
}

$userId = $_SESSION['user_id'];

try {
    // Obtener datos del body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        jsonError('Datos JSON inválidos', 400);
    }
    
    // Validar campos requeridos
    if (!isset($data['resource_type']) || !isset($data['resource_id'])) {
        jsonError('Faltan campos requeridos: resource_type, resource_id', 400);
    }
    
    $resourceType = sanitizeInput($data['resource_type']);
    $resourceId = (int)$data['resource_id'];
    $role = isset($data['role']) ? sanitizeInput($data['role']) : 'owner';
    
    // Validar resource_type
    $validTypes = ['accommodation', 'place', 'activity', 'event'];
    if (!in_array($resourceType, $validTypes)) {
        jsonError('Tipo de recurso inválido', 400);
    }
    
    // Validar role
    $validRoles = ['owner', 'manager', 'collaborator'];
    if (!in_array($role, $validRoles)) {
        jsonError('Rol inválido', 400);
    }
    
    $pdo = getDBConnection();
    
    // Verificar que el recurso existe
    $tableName = '';
    $nameField = 'name';
    
    switch ($resourceType) {
        case 'accommodation':
            $tableName = 'accommodations';
            break;
        case 'place':
            $tableName = 'places_of_interest';
            break;
        case 'activity':
            $tableName = 'tourist_activities';
            break;
        case 'event':
            $tableName = 'cultural_events';
            $nameField = 'name';
            break;
    }
    
    $stmtCheck = $pdo->prepare("SELECT id, $nameField as name FROM $tableName WHERE id = :id");
    $stmtCheck->execute(['id' => $resourceId]);
    $resource = $stmtCheck->fetch();
    
    if (!$resource) {
        jsonError('El recurso no existe', 404);
    }
    
    // Verificar si ya existe la vinculación
    $stmtExists = $pdo->prepare("
        SELECT id, status 
        FROM user_resources 
        WHERE user_id = :user_id 
        AND resource_type = :resource_type 
        AND resource_id = :resource_id
    ");
    $stmtExists->execute([
        'user_id' => $userId,
        'resource_type' => $resourceType,
        'resource_id' => $resourceId
    ]);
    $existing = $stmtExists->fetch();
    
    if ($existing) {
        jsonError('Ya tienes este recurso vinculado (Estado: ' . $existing['status'] . ')', 409);
    }
    
    // Crear vinculación
    $stmtInsert = $pdo->prepare("
        INSERT INTO user_resources (user_id, resource_type, resource_id, role, status)
        VALUES (:user_id, :resource_type, :resource_id, :role, :status)
    ");
    
    // Por defecto, las vinculaciones están pendientes de validación
    $status = 'pending';
    
    // Si el usuario es el creador original del recurso, activar automáticamente
    // (esto se puede ajustar según tu lógica de negocio)
    
    $stmtInsert->execute([
        'user_id' => $userId,
        'resource_type' => $resourceType,
        'resource_id' => $resourceId,
        'role' => $role,
        'status' => $status
    ]);
    
    $linkId = $pdo->lastInsertId();
    
    // Obtener la vinculación creada
    $stmtGet = $pdo->prepare("
        SELECT id, user_id, resource_type, resource_id, role, status, created_at
        FROM user_resources
        WHERE id = :id
    ");
    $stmtGet->execute(['id' => $linkId]);
    $link = $stmtGet->fetch();
    
    $response = [
        'link' => $link,
        'resource' => [
            'id' => $resource['id'],
            'name' => $resource['name'],
            'type' => $resourceType
        ],
        'message' => $status === 'pending' 
            ? 'Recurso vinculado. Pendiente de validación por el administrador.'
            : 'Recurso vinculado exitosamente.'
    ];
    
    jsonSuccess($response, 'Recurso vinculado correctamente');
    
} catch (PDOException $e) {
    error_log('link_resource.php Error: ' . $e->getMessage());
    jsonError('Error al vincular recurso: ' . $e->getMessage(), 500);
}
