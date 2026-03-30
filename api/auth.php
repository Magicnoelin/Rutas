<?php
/**
 * Sistema de Autenticación y Permisos
 * Middleware para verificar permisos de usuario
 */

require_once 'config.php';

/**
 * Verificar si el usuario está autenticado
 */
function isAuthenticated() {
    session_start();
    return isset($_SESSION['user_id']);
}

/**
 * Obtener ID del usuario autenticado
 */
function getCurrentUserId() {
    session_start();
    return $_SESSION['user_id'] ?? null;
}

/**
 * Obtener información completa del usuario autenticado
 */
function getCurrentUser() {
    if (!isAuthenticated()) {
        return null;
    }
    
    try {
        $pdo = getDBConnection();
        $userId = getCurrentUserId();
        
        $sql = "SELECT id, user_type, business_name, business_description, verification_status, 
                       subscription_level, first_name, last_name, email, phone, status, created_at
                FROM users WHERE id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Auth.php - Error getting current user: ' . $e->getMessage());
        return null;
    }
}

/**
 * Verificar si el usuario tiene permiso para una acción específica
 * 
 * @param string $resource Tipo de recurso (accommodations, events, places, activities)
 * @param string $action Acción (create, read, update, delete)
 * @return bool
 */
function hasPermission($resource, $action) {
    if (!isAuthenticated()) {
        return false;
    }
    
    try {
        $pdo = getDBConnection();
        $userId = getCurrentUserId();
        
        $columnMap = [
            'create' => 'can_create',
            'read' => 'can_read',
            'update' => 'can_update',
            'delete' => 'can_delete'
        ];
        
        if (!isset($columnMap[$action])) {
            return false;
        }
        
        $column = $columnMap[$action];
        
        $sql = "SELECT $column FROM user_permissions 
                WHERE user_id = :user_id AND resource_type = :resource_type";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':resource_type' => $resource
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result && $result[$column] == 1;
    } catch (PDOException $e) {
        error_log('Auth.php - Error checking permission: ' . $e->getMessage());
        return false;
    }
}

/**
 * Requerir autenticación - termina la ejecución si no está autenticado
 */
function requireAuth() {
    if (!isAuthenticated()) {
        jsonError('No autenticado. Por favor, inicia sesión.', 401);
        exit;
    }
}

/**
 * Requerir permiso específico - termina la ejecución si no tiene permiso
 * 
 * @param string $resource Tipo de recurso
 * @param string $action Acción requerida
 */
function requirePermission($resource, $action) {
    requireAuth();
    
    if (!hasPermission($resource, $action)) {
        jsonError('No tienes permiso para realizar esta acción.', 403);
        exit;
    }
}

/**
 * Verificar si el usuario es propietario de un recurso
 * 
 * @param string $resourceType Tipo de recurso
 * @param int $resourceId ID del recurso
 * @return bool
 */
function isResourceOwner($resourceType, $resourceId) {
    if (!isAuthenticated()) {
        return false;
    }
    
    try {
        $pdo = getDBConnection();
        $userId = getCurrentUserId();
        
        // Mapeo de tipos de recurso a tablas
        $tableMap = [
            'accommodations' => 'accommodations',
            'events' => 'cultural_events',
            'places' => 'places_of_interest',
            'activities' => 'activities'
        ];
        
        if (!isset($tableMap[$resourceType])) {
            return false;
        }
        
        $table = $tableMap[$resourceType];
        
        // Verificar si el recurso pertenece al usuario
        $sql = "SELECT id FROM $table WHERE id = :resource_id AND user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':resource_id' => $resourceId,
            ':user_id' => $userId
        ]);
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log('Auth.php - Error checking resource ownership: ' . $e->getMessage());
        return false;
    }
}

/**
 * Verificar si el usuario está verificado (para negocios)
 */
function isVerified() {
    $user = getCurrentUser();
    return $user && $user['verification_status'] === 'verified';
}

/**
 * Requerir verificación - termina la ejecución si no está verificado
 */
function requireVerification() {
    requireAuth();
    
    if (!isVerified()) {
        jsonError('Tu cuenta está pendiente de verificación. Por favor, espera a que un administrador apruebe tu cuenta.', 403);
        exit;
    }
}

/**
 * Verificar si el usuario tiene suscripción premium
 */
function isPremium() {
    $user = getCurrentUser();
    return $user && $user['subscription_level'] === 'premium';
}

/**
 * Obtener todos los permisos de un usuario
 */
function getUserPermissions($userId = null) {
    if ($userId === null) {
        $userId = getCurrentUserId();
    }
    
    if (!$userId) {
        return [];
    }
    
    try {
        $pdo = getDBConnection();
        
        $sql = "SELECT resource_type, can_create, can_read, can_update, can_delete 
                FROM user_permissions WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        $permissions = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $permissions[$row['resource_type']] = [
                'create' => (bool)$row['can_create'],
                'read' => (bool)$row['can_read'],
                'update' => (bool)$row['can_update'],
                'delete' => (bool)$row['can_delete']
            ];
        }
        
        return $permissions;
    } catch (PDOException $e) {
        error_log('Auth.php - Error getting user permissions: ' . $e->getMessage());
        return [];
    }
}

/**
 * Cerrar sesión
 */
function logout() {
    session_start();
    session_destroy();
    $_SESSION = [];
}
