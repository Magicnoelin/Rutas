<?php
/**
 * API: Obtener opciones de membresía disponibles
 * GET /api/get_membership_options.php
 * Retorna los planes de membresía disponibles para upgrade
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDBConnection();

    // Intentar obtener opciones de membresía desde la base de datos
    try {
        $stmt = $pdo->query("
            SELECT
                id,
                name,
                description,
                price_monthly,
                price_yearly,
                features,
                is_popular,
                stripe_product_id,
                stripe_monthly_price_id,
                stripe_yearly_price_id
            FROM membership_plans
            WHERE is_active = TRUE
            ORDER BY price_monthly ASC
        ");

        $plans = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Si la tabla no existe, usar planes por defecto
        error_log('get_membership_options.php: La tabla membership_plans no existe, usando planes por defecto');
        $plans = [];
    }

    if (empty($plans)) {
        // Si no hay planes en la base de datos, devolver planes por defecto
        $defaultPlans = [
            [
                'id' => 1,
                'name' => 'Free',
                'description' => 'Plan básico gratuito para empezar',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'features' => [
                    'Acceso básico a la plataforma',
                    'Publicar hasta 1 alojamiento',
                    'Responder a mensajes de turistas',
                    'Acceso a estadísticas básicas'
                ],
                'is_popular' => false,
                'stripe_product_id' => null,
                'stripe_monthly_price_id' => null,
                'stripe_yearly_price_id' => null
            ],
            [
                'id' => 2,
                'name' => 'Premium',
                'description' => 'Plan profesional para maximizar tu visibilidad',
                'price_monthly' => 9.99,
                'price_yearly' => 99.99,
                'features' => [
                    'Publicar hasta 2alojamientos',
                    'Enviar ofertas a turistas',
                    'Mensajes ilimitados',
                    'Estadísticas avanzadas',
                    'Posicionamiento destacado',
                    'Soporte prioritario',
                    'Acceso a promociones especiales'
                ],
                'is_popular' => true,
                'stripe_product_id' => 'prod_premium_accommodation',
                'stripe_monthly_price_id' => 'price_123_monthly',
                'stripe_yearly_price_id' => 'price_123_yearly'
            ],
            [
                'id' => 3,
                'name' => 'Business',
                'description' => 'Plan empresarial para gestión avanzada',
                'price_monthly' => 49.99,
                'price_yearly' => 499.99,
                'features' => [
                    'Todas las funciones Premium',
                    'Gestión de múltiples propiedades',
                    'API para integración con tu web',
                    'Informes personalizados',
                    'Asesoramiento personalizado',
                    'Acceso a eventos exclusivos'
                ],
                'is_popular' => false,
                'stripe_product_id' => 'prod_business_accommodation',
                'stripe_monthly_price_id' => 'price_456_monthly',
                'stripe_yearly_price_id' => 'price_456_yearly'
            ]
        ];

        jsonSuccess($defaultPlans);
    } else {
        // Procesar features si están en formato JSON
        foreach ($plans as &$plan) {
            if ($plan['features'] && is_string($plan['features'])) {
                try {
                    $plan['features'] = json_decode($plan['features'], true);
                } catch (Exception $e) {
                    $plan['features'] = explode(',', $plan['features']);
                }
            }
        }

        jsonSuccess($plans);
    }

} catch (Exception $e) {
    error_log('get_membership_options.php Error: ' . $e->getMessage());
    jsonError('Error al obtener opciones de membresía: ' . $e->getMessage(), 500);
}
