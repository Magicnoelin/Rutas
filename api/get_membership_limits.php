<?php
/**
 * API: Obtener límites de membresía del usuario actual
 * GET /api/get_membership_limits.php
 * 
 * Devuelve los límites según el plan del usuario y los planes disponibles
 * con la nueva estructura de precios (Free, Premium, Business)
 */

session_start();
require_once 'config.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    jsonError('Debes iniciar sesión para ver tus límites de membresía', 401);
}

try {
    $userId = $_SESSION['user_id'];
    $pdo = getDBConnection();

    // Obtener información de membresía del usuario
    $stmtUser = $pdo->prepare("
        SELECT id, email, first_name, last_name, user_type, membership_type, membership_status,
               membership_start_date, membership_end_date
        FROM users 
        WHERE id = ?
    ");
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch();

    if (!$user) {
        jsonError('Usuario no encontrado', 404);
    }

    $membershipType = strtolower($user['membership_type'] ?? 'free');
    $membershipStatus = $user['membership_status'] ?? 'active';
    $membershipStartDate = $user['membership_start_date'] ?? null;
    $membershipEndDate = $user['membership_end_date'] ?? null;
    $userType = strtolower($user['user_type'] ?? '');

    // Definir límites según tipo de membresía (NUEVA ESTRUCTURA)
    $limits = [
        'free' => [
            'maxAccommodations' => 1,
            'maxPhotos' => 4,
            'name' => 'Free',
            'description' => 'Plan básico para probar la plataforma',
            'canSendOffers' => false,
            'hasAdvancedStats' => false,
            'hasDirectLink' => false,
            'hasPriorityPosition' => false,
            'hasPrioritySupport' => false,
            'hasApi' => false,
            'canSendMessages' => false,
            'canReceiveMessages' => true
        ],
        'premium' => [
            'maxAccommodations' => 1,
            'maxPhotos' => null, // ilimitado
            'name' => 'Premium',
            'description' => 'Plan profesional con oferta de lanzamiento 50%',
            'canSendOffers' => true,
            'hasAdvancedStats' => true,
            'hasDirectLink' => true,
            'hasPriorityPosition' => true,
            'hasPrioritySupport' => true,
            'hasApi' => false,
            'canSendMessages' => true,
            'canReceiveMessages' => true
        ],
        'business' => [
            'maxAccommodations' => 10,
            'maxPhotos' => null, // ilimitado
            'name' => 'Business',
            'description' => 'Plan empresarial para gestión avanzada',
            'canSendOffers' => true,
            'hasAdvancedStats' => true,
            'hasDirectLink' => true,
            'hasPriorityPosition' => true,
            'hasPrioritySupport' => true,
            'hasApi' => true,
            'canSendMessages' => true,
            'canReceiveMessages' => true
        ]
    ];

    // Usar límites free por defecto si el tipo no está definido
    $membershipLimits = $limits[$membershipType] ?? $limits['free'];

    // Contar alojamientos existentes del usuario
    $stmtCount = $pdo->prepare("
        SELECT COUNT(*) as total_alojamientos, 
               SUM(capacity) as total_plazas
        FROM accommodations a
        LEFT JOIN user_resources ur ON ur.resource_id = a.id AND ur.resource_type = 'accommodation'
        WHERE ur.user_id = ? AND ur.role = 'owner'
    ");
    $stmtCount->execute([$userId]);
    $counts = $stmtCount->fetch();

    $totalAlojamientos = $counts['total_alojamientos'] ?? 0;
    $totalPlazas = $counts['total_plazas'] ?? 0;

    // Obtener información de planes de membresía disponibles
    $stmtPlans = $pdo->query("
        SELECT id, name, description, price_monthly, price_yearly, official_price_yearly,
               features, max_accommodations, max_photos, is_popular, is_launch_offer,
               launch_discount_percent, multipropiedad_note, has_direct_link, has_api,
               has_priority_position, can_send_messages
        FROM membership_plans
        WHERE is_active = TRUE
        ORDER BY id ASC
    ");
    $plans = $stmtPlans->fetchAll();

    // Procesar features
    foreach ($plans as &$plan) {
        if ($plan['features'] && is_string($plan['features'])) {
            try {
                $plan['features'] = json_decode($plan['features'], true);
            } catch (Exception $e) {
                $plan['features'] = explode(',', $plan['features']);
            }
        }
        $plan['price_monthly'] = (float)$plan['price_monthly'];
        $plan['price_yearly'] = (float)$plan['price_yearly'];
        $plan['official_price_yearly'] = $plan['official_price_yearly'] ? (float)$plan['official_price_yearly'] : null;
    }

    // Determinar mensaje de upgrade
    $upgradeMessage = '';
    $canUpgrade = true;
    if ($membershipType === 'free') {
        $upgradeMessage = 'Actualiza a Premium y destaca tu alojamiento. ¡50% de descuento por lanzamiento!';
    } elseif ($membershipType === 'premium') {
        $upgradeMessage = '¿Necesitas más alojamientos? Consulta nuestro Plan Business o Pack Multipropiedad.';
    } elseif ($membershipType === 'business') {
        $upgradeMessage = 'Ya tienes el plan más completo. ¡Gracias por confiar en nosotros!';
        $canUpgrade = false;
    }

    // Preparar respuesta
    $response = [
        'membershipType' => $membershipType,
        'membershipName' => $membershipLimits['name'],
        'membershipStatus' => $membershipStatus,
        'membershipStartDate' => $membershipStartDate,
        'membershipEndDate' => $membershipEndDate,
        'userType' => $userType,
        'currentAccommodations' => (int)$totalAlojamientos,
        'currentPlaces' => (int)$totalPlazas,
        'maxAccommodations' => $membershipLimits['maxAccommodations'],
        'maxPhotos' => $membershipLimits['maxPhotos'],
        'canSendOffers' => $membershipLimits['canSendOffers'],
        'hasAdvancedStats' => $membershipLimits['hasAdvancedStats'],
        'hasDirectLink' => $membershipLimits['hasDirectLink'],
        'hasPriorityPosition' => $membershipLimits['hasPriorityPosition'],
        'hasPrioritySupport' => $membershipLimits['hasPrioritySupport'],
        'hasApi' => $membershipLimits['hasApi'],
        'canSendMessages' => $membershipLimits['canSendMessages'],
        'availablePlans' => $plans,
        'canUpgrade' => $canUpgrade,
        'upgradeMessage' => $upgradeMessage
    ];

    jsonSuccess($response, 'Límites de membresía obtenidos correctamente');

} catch (PDOException $e) {
    error_log('get_membership_limits.php Error: ' . $e->getMessage());
    jsonError('Error al obtener límites de membresía: ' . $e->getMessage(), 500);
}
