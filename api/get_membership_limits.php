<?php
/**
 * API: Obtener límites de membresía del usuario actual
 * GET /api/get_membership_limits.php
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
        SELECT id, email, first_name, last_name, membership_type, membership_status 
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

    // Definir límites según tipo de membresía
    $limits = [
        'free' => [
            'maxAccommodations' => 1,
            'maxPlaces' => 8,
            'name' => 'Gratuita'
        ],
        'basic' => [
            'maxAccommodations' => 2,
            'maxPlaces' => 15,
            'name' => 'Básica'
        ],
        'premium' => [
            'maxAccommodations' => 10,
            'maxPlaces' => 100,
            'name' => 'Premium'
        ],
        'enterprise' => [
            'maxAccommodations' => 50,
            'maxPlaces' => 500,
            'name' => 'Empresa'
        ]
    ];

    // Usar límites básicos por defecto si el tipo no está definido
    $membershipLimits = $limits[$membershipType] ?? $limits['basic'];

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
    $stmtPlans = $pdo->prepare("
        SELECT id, name, price_monthly, price_yearly, description, features
        FROM membership_plans
        WHERE status = 'active'
        ORDER BY price_monthly ASC
    ");
    $stmtPlans->execute();
    $plans = $stmtPlans->fetchAll();

    // Preparar respuesta
    $response = [
        'membershipType' => $membershipType,
        'membershipName' => $membershipLimits['name'],
        'membershipStatus' => $membershipStatus,
        'currentAccommodations' => (int)$totalAlojamientos,
        'currentPlaces' => (int)$totalPlazas,
        'maxAccommodations' => $membershipLimits['maxAccommodations'],
        'maxPlaces' => $membershipLimits['maxPlaces'],
        'availablePlans' => $plans,
        'canUpgrade' => $membershipType !== 'enterprise',
        'upgradeMessage' => $membershipType === 'free' ? 'Actualiza a Básica para más límites' : 
                          ($membershipType === 'basic' ? 'Actualiza a Premium para límites ilimitados' : 
                          ($membershipType === 'premium' ? 'Ya tienes el plan más completo' : ''))
    ];

    jsonSuccess($response, 'Límites de membresía obtenidos correctamente');

} catch (PDOException $e) {
    error_log('get_membership_limits.php Error: ' . $e->getMessage());
    jsonError('Error al obtener límites de membresía: ' . $e->getMessage(), 500);
}
?>