<?php
/**
 * API PUT Handler - Complete Implementation
 * Handles PUT requests for updating resources with proper security
 */

require_once 'config_updated.php';

// Initialize security measures
initSecurity();

// Only allow PUT method
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    jsonError('Método no permitido', 405);
}

// Require CSRF protection
requireCSRF();

// Start secure session
initSecureSession();

try {
    // Get JSON data from request body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        jsonError('Datos JSON inválidos', 400);
    }

    // Validate required fields
    if (!isset($data['id']) || empty($data['id'])) {
        jsonError('ID de recurso es requerido', 400);
    }

    // Validate resource type
    $resourceType = $data['resource_type'] ?? 'accommodations';
    $allowedResources = ['accommodations', 'events', 'places', 'activities'];
    
    if (!in_array($resourceType, $allowedResources)) {
        jsonError('Tipo de recurso no válido', 400);
    }

    $pdo = getDBConnection();

    // Verify resource exists
    $resourceExists = false;
    $resourceId = intval($data['id']);
    
    switch ($resourceType) {
        case 'accommodations':
            $stmt = $pdo->prepare("SELECT id FROM accommodations WHERE id = ?");
            break;
        case 'events':
            $stmt = $pdo->prepare("SELECT id FROM cultural_events WHERE id = ?");
            break;
        case 'places':
            $stmt = $pdo->prepare("SELECT id FROM places_of_interest WHERE id = ?");
            break;
        case 'activities':
            $stmt = $pdo->prepare("SELECT id FROM tourist_activities WHERE id = ?");
            break;
    }
    
    $stmt->execute([$resourceId]);
    $resourceExists = $stmt->fetch() !== false;

    if (!$resourceExists) {
        jsonError('Recurso no encontrado', 404);
    }

    // Validate and prepare update data based on resource type
    $updateData = [];
    $params = [];
    
    switch ($resourceType) {
        case 'accommodations':
            $allowedFields = [
                'name', 'accommodation_type', 'capacity', 'province', 'municipality',
                'address', 'price_per_night', 'phone', 'email', 'description',
                'website', 'photo1', 'photo2', 'photo3', 'photo4', 'slug'
            ];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[] = "$field = ?";
                    
                    // Special validation for specific fields
                    switch ($field) {
                        case 'email':
                            $value = validateAndSanitizeEmail($data[$field]);
                            if ($value === false) {
                                jsonError('Email inválido', 400);
                            }
                            break;
                        case 'capacity':
                        case 'price_per_night':
                            $value = is_numeric($data[$field]) ? floatval($data[$field]) : 0;
                            break;
                        default:
                            $value = sanitizeInput($data[$field], ['strip_tags' => true, 'trim' => true]);
                    }
                    
                    $params[] = $value;
                }
            }
            break;
            
        case 'events':
            $allowedFields = [
                'title', 'description', 'event_date', 'event_time', 'location',
                'category', 'price', 'contact_info', 'website', 'image_url'
            ];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[] = "$field = ?";
                    $params[] = sanitizeInput($data[$field], ['strip_tags' => true, 'trim' => true]);
                }
            }
            break;
            
        case 'places':
            $allowedFields = [
                'name', 'description', 'location', 'category', 'opening_hours',
                'contact_info', 'website', 'image_url', 'latitude', 'longitude'
            ];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[] = "$field = ?";
                    if (in_array($field, ['latitude', 'longitude'])) {
                        $params[] = is_numeric($data[$field]) ? floatval($data[$field]) : null;
                    } else {
                        $params[] = sanitizeInput($data[$field], ['strip_tags' => true, 'trim' => true]);
                    }
                }
            }
            break;
            
        case 'activities':
            $allowedFields = [
                'name', 'description', 'location', 'category', 'duration',
                'price', 'difficulty_level', 'contact_info', 'website', 'image_url'
            ];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[] = "$field = ?";
                    $params[] = sanitizeInput($data[$field], ['strip_tags' => true, 'trim' => true]);
                }
            }
            break;
    }

    // Add updated_at timestamp
    $updateData[] = "updated_at = CURRENT_TIMESTAMP";
    $params[] = $resourceId; // Add ID for WHERE clause

    if (empty($updateData)) {
        jsonError('No hay campos para actualizar', 400);
    }

    // Build and execute update query
    $tableName = $resourceType;
    if ($resourceType === 'places') {
        $tableName = 'places_of_interest';
    }
    if ($resourceType === 'activities') {
        $tableName = 'tourist_activities';
    }
    
    $sql = "UPDATE $tableName SET " . implode(', ', $updateData) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);

    if ($stmt->execute($params)) {
        // Log successful update
        logSecurityEvent('resource_updated', [
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'user_id' => $_SESSION['user_id'] ?? 'anonymous'
        ]);
        
        jsonSuccess([
            'id' => $resourceId,
            'resource_type' => $resourceType,
            'updated_fields' => array_keys(array_intersect_key($data, array_flip($allowedFields ?? [])))
        ], 'Recurso actualizado correctamente');
    } else {
        jsonError('Error al actualizar recurso', 500);
    }

} catch (PDOException $e) {
    // Log database error securely
    error_log('PUT Handler - Database Error: ' . $e->getMessage());
    logSecurityEvent('database_error', [
        'operation' => 'PUT',
        'resource_type' => $data['resource_type'] ?? 'unknown',
        'error_code' => $e->getCode()
    ]);
    
    jsonError('Error interno del servidor al actualizar el recurso', 500);
} catch (Exception $e) {
    // Log general error
    error_log('PUT Handler - General Error: ' . $e->getMessage());
    logSecurityEvent('general_error', [
        'operation' => 'PUT',
        'error' => $e->getMessage()
    ]);
    
    jsonError('Error interno del servidor', 500);
}
?>
