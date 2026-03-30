<?php
/**
 * API: Obtener recursos del usuario
 * GET /api/get_user_resources.php
 * Retorna todos los recursos vinculados al usuario según sus roles
 */

session_start();
require_once 'config.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    jsonError('No autenticado', 401);
}

$userId = $_SESSION['user_id'];

try {
    $pdo = getDBConnection();
    
    // Obtener información del usuario
    $stmtUser = $pdo->prepare("
        SELECT id, email, first_name, last_name, user_type, 
               membership_status, membership_type, avatar_url
        FROM users 
        WHERE id = :user_id
    ");
    $stmtUser->execute(['user_id' => $userId]);
    $user = $stmtUser->fetch();
    
    if (!$user) {
        jsonError('Usuario no encontrado', 404);
    }
    
    // Obtener recursos vinculados
    $stmtResources = $pdo->prepare("
        SELECT 
            ur.id AS link_id,
            ur.resource_type,
            ur.resource_id,
            ur.role,
            ur.status,
            ur.created_at,
            ur.validated_at,
            
            -- Estadísticas
            COALESCE(rs.views_count, 0) AS views_count,
            COALESCE(rs.interests_count, 0) AS interests_count,
            COALESCE(rs.messages_count, 0) AS messages_count,
            COALESCE(rs.favorites_count, 0) AS favorites_count,
            
            -- Información del recurso según tipo
            CASE ur.resource_type
                WHEN 'accommodation' THEN (
                    SELECT JSON_OBJECT(
                        'id', a.id,
                        'name', a.name,
                        'slug', a.slug,
                        'municipality', a.municipality,
                        'province', a.province,
                        'photo', a.photo1,
                        'is_active', a.is_active,
                        'images_folder', CONCAT('/accommodations_images/', a.slug, '/'),
                        'manage_photos_url', CONCAT('/gestion-fotos-v2.html?slug=', a.slug)
                    )
                    FROM accommodations a WHERE a.id = ur.resource_id
                )
                WHEN 'place' THEN (
                    SELECT JSON_OBJECT(
                        'id', p.id,
                        'name', p.name,
                        'slug', p.slug,
                        'municipality', p.municipality,
                        'province', p.province,
                        'photo', p.photo1,
                        'is_active', p.is_active
                    )
                    FROM places_of_interest p WHERE p.id = ur.resource_id
                )
                WHEN 'activity' THEN (
                    SELECT JSON_OBJECT(
                        'id', t.id,
                        'name', t.name,
                        'slug', t.slug,
                        'municipality', t.municipality,
                        'province', t.province,
                        'photo', t.photo1,
                        'is_active', t.is_active
                    )
                    FROM tourist_activities t WHERE t.id = ur.resource_id
                )
                WHEN 'event' THEN (
                    SELECT JSON_OBJECT(
                        'id', e.id,
                        'name', e.name,
                        'slug', e.slug,
                        'municipality', e.municipality,
                        'province', e.province,
                        'photo', e.photo1,
                        'is_active', e.is_active
                    )
                    FROM cultural_events e WHERE e.id = ur.resource_id
                )
            END AS resource_data
            
        FROM user_resources ur
        LEFT JOIN resource_stats rs ON ur.resource_type = rs.resource_type 
            AND ur.resource_id = rs.resource_id
        WHERE ur.user_id = :user_id
        ORDER BY ur.resource_type, ur.created_at DESC
    ");
    
    $stmtResources->execute(['user_id' => $userId]);
    $resources = $stmtResources->fetchAll();
    
    // Organizar recursos por tipo
    $resourcesByType = [
        'accommodation' => [],
        'place' => [],
        'activity' => [],
        'event' => []
    ];
    
    foreach ($resources as $resource) {
        $resourceData = json_decode($resource['resource_data'], true);
        
        if ($resourceData) {
            // Procesar imagen para alojamientos (ruta completa)
            if ($resource['resource_type'] === 'accommodation' && !empty($resourceData['photo'])) {
                // Si hay múltiples imágenes separadas por coma, tomar la primera
                if (strpos($resourceData['photo'], ',') !== false) {
                    $parts = explode(',', $resourceData['photo']);
                    $resourceData['photo'] = trim($parts[0]);
                }
                // Si no es URL absoluta, agregar prefijo
                if (!preg_match('/^https?:\/\//', $resourceData['photo'])) {
                    $resourceData['photo'] = '/accommodations_images/' . $resourceData['photo'];
                }
            }

            // Procesar imagen para eventos (fallback al slug)
            if ($resource['resource_type'] === 'event') {
                if (empty($resourceData['photo'])) {
                    $resourceData['photo'] = "/cultural_events_images/" . ($resourceData['slug'] ?? '') . ".webp";
                }
            }

            $resourcesByType[$resource['resource_type']][] = [
                'link_id' => $resource['link_id'],
                'resource_id' => $resource['resource_id'],
                'role' => $resource['role'],
                'status' => $resource['status'],
                'created_at' => $resource['created_at'],
                'validated_at' => $resource['validated_at'],
                'stats' => [
                    'views' => (int)$resource['views_count'],
                    'interests' => (int)$resource['interests_count'],
                    'messages' => (int)$resource['messages_count'],
                    'favorites' => (int)$resource['favorites_count']
                ],
                'data' => $resourceData
            ];
        }
    }
    
    // Contar ofertas activas del usuario
    $stmtOffers = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM resource_offers
        WHERE user_id = :user_id
        AND status = 'active'
        AND valid_until >= CURDATE()
    ");
    $stmtOffers->execute(['user_id' => $userId]);
    $activeOffers = $stmtOffers->fetch()['total'];
    
    // Contar mensajes no leídos
    $stmtMessages = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM messages m
        JOIN conversations c ON m.conversation_id = c.id
        WHERE c.provider_id = :user_id
        AND m.sender_type = 'tourist'
        AND m.is_read = FALSE
    ");
    $stmtMessages->execute(['user_id' => $userId]);
    $unreadMessages = $stmtMessages->fetch()['total'];
    
    // Determinar roles del usuario
    $roles = ['tourist' => true]; // Todos son turistas por defecto
    
    if (count($resourcesByType['accommodation']) > 0) {
        $roles['accommodation_manager'] = true;
    }
    if (count($resourcesByType['place']) > 0) {
        $roles['place_manager'] = true;
    }
    if (count($resourcesByType['activity']) > 0) {
        $roles['activity_manager'] = true;
    }
    if (count($resourcesByType['event']) > 0) {
        $roles['event_manager'] = true;
    }
    
    // Respuesta
    $response = [
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'full_name' => trim($user['first_name'] . ' ' . $user['last_name']),
            'user_type' => $user['user_type'],
            'membership_status' => $user['membership_status'],
            'membership_type' => $user['membership_type'],
            'avatar_url' => $user['avatar_url']
        ],
        'roles' => $roles,
        'resources' => $resourcesByType,
        'summary' => [
            'total_resources' => count($resources),
            'accommodations' => count($resourcesByType['accommodation']),
            'places' => count($resourcesByType['place']),
            'activities' => count($resourcesByType['activity']),
            'events' => count($resourcesByType['event']),
            'active_offers' => (int)$activeOffers,
            'unread_messages' => (int)$unreadMessages
        ]
    ];
    
    jsonSuccess($response);
    
} catch (PDOException $e) {
    error_log('get_user_resources.php Error: ' . $e->getMessage());
    jsonError('Error al obtener recursos del usuario', 500);
}
