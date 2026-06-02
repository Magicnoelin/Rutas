<?php
/**
 * API: Obtener opciones de membresía disponibles
 * GET /api/get_membership_options.php
 * Retorna los planes de membresía disponibles para upgrade
 * 
 * Ahora incluye: official_price_yearly, max_photos, has_direct_link,
 * is_launch_offer, launch_discount_percent, multipropiedad_note, etc.
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
                official_price_yearly,
                features,
                max_accommodations,
                max_photos,
                can_send_offers,
                has_advanced_stats,
                has_basic_stats,
                has_priority_support,
                has_direct_link,
                has_api,
                has_personalized_consulting,
                has_reports,
                can_receive_messages,
                can_send_messages,
                has_priority_position,
                is_popular,
                is_launch_offer,
                launch_discount_percent,
                multipropiedad_note,
                stripe_product_id,
                stripe_monthly_price_id,
                stripe_yearly_price_id,
                plan_type
            FROM membership_plans
            WHERE is_active = TRUE
              AND (plan_type IS NULL OR plan_type NOT IN ('apoyo_plataforma','cafe','cafeteria'))
            ORDER BY id ASC
        ");

        $plans = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Si la tabla no existe, usar planes por defecto
        error_log('get_membership_options.php: La tabla membership_plans no existe, usando planes por defecto');
        $plans = [];
    }

    if (empty($plans)) {
        // Planes por defecto con la NUEVA estructura de precios
        $defaultPlans = [
            [
                'id' => 1,
                'name' => 'Free',
                'description' => 'Plan básico para probar la plataforma. Publica 1 alojamiento con máximo 4 fotos y descripción básica.',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'official_price_yearly' => null,
                'features' => [
                    'Publicar 1 alojamiento',
                    'Máximo 4 fotos por alojamiento',
                    'Descripción básica',
                    'Ver mensajes de turistas',
                    'Estadísticas básicas',
                    'Sin coste'
                ],
                'max_accommodations' => 1,
                'max_photos' => 4,
                'can_send_offers' => false,
                'has_advanced_stats' => false,
                'has_basic_stats' => true,
                'has_priority_support' => false,
                'has_direct_link' => false,
                'has_api' => false,
                'has_personalized_consulting' => false,
                'has_reports' => false,
                'can_receive_messages' => true,
                'can_send_messages' => false,
                'has_priority_position' => false,
                'is_popular' => false,
                'is_launch_offer' => false,
                'launch_discount_percent' => null,
                'multipropiedad_note' => null,
                'stripe_product_id' => null,
                'stripe_monthly_price_id' => null,
                'stripe_yearly_price_id' => null
            ],
            [
                'id' => 2,
                'name' => 'Premium',
                'description' => 'Plan profesional para alojamientos que quieren destacar. Oferta de lanzamiento con 50% de descuento. Precio con IVA incluido.',
                'price_monthly' => 19.99,
                'price_yearly' => 120.00,
                'official_price_yearly' => 240.00,
                'features' => [
                    'Hasta 4 alojamientos',
                    'Hasta 20 fotos por alojamiento',
                    'Descripción completa',
                    'ENLACE DIRECTO a tu web o motor de reservas (0% comisiones)',
                    'Posicionamiento destacado en búsquedas',
                    'Soporte prioritario',
                    'Mensajes ilimitados con turistas',
                    'Estadísticas avanzadas'
                ],
                'max_accommodations' => 4,
                'max_photos' => 20,
                'can_send_offers' => true,
                'has_advanced_stats' => true,
                'has_basic_stats' => true,
                'has_priority_support' => true,
                'has_direct_link' => true,
                'has_api' => false,
                'has_personalized_consulting' => false,
                'has_reports' => false,
                'can_receive_messages' => true,
                'can_send_messages' => true,
                'has_priority_position' => true,
                'is_popular' => true,
                'is_launch_offer' => true,
                'launch_discount_percent' => 50,
                'multipropiedad_note' => '¿Tienes más de 4 alojamientos? Consúltanos por nuestro Pack Multipropiedad',
                'stripe_product_id' => null,
                'stripe_monthly_price_id' => null,
                'stripe_yearly_price_id' => null,
                'plan_type' => 'alojamiento'
            ],
            [
                'id' => 3,
                'name' => 'Business',
                'description' => 'Plan empresarial para agencias, complejos grandes o gestión avanzada.',
                'price_monthly' => 49.99,
                'price_yearly' => 499.99,
                'official_price_yearly' => null,
                'features' => [
                    'Hasta 10 alojamientos',
                    'Todas las funciones Premium',
                    'API para integración con tu web',
                    'Informes personalizados',
                    'Asesoramiento personalizado',
                    'Fotos ilimitadas',
                    'Enlace directo a tu web (0% comisiones)',
                    'Posicionamiento destacado',
                    'Soporte prioritario 24/7'
                ],
                'max_accommodations' => 10,
                'max_photos' => null,
                'can_send_offers' => true,
                'has_advanced_stats' => true,
                'has_basic_stats' => true,
                'has_priority_support' => true,
                'has_direct_link' => true,
                'has_api' => true,
                'has_personalized_consulting' => true,
                'has_reports' => true,
                'can_receive_messages' => true,
                'can_send_messages' => true,
                'has_priority_position' => true,
                'is_popular' => false,
                'is_launch_offer' => false,
                'launch_discount_percent' => null,
                'multipropiedad_note' => null,
                'stripe_product_id' => null,
                'stripe_monthly_price_id' => null,
                'stripe_yearly_price_id' => null
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
            // Asegurar tipos correctos
            $plan['price_monthly'] = (float)$plan['price_monthly'];
            $plan['price_yearly'] = (float)$plan['price_yearly'];
            $plan['official_price_yearly'] = $plan['official_price_yearly'] ? (float)$plan['official_price_yearly'] : null;
            $plan['max_accommodations'] = (int)$plan['max_accommodations'];
            $plan['max_photos'] = $plan['max_photos'] ? (int)$plan['max_photos'] : null;
            $plan['launch_discount_percent'] = $plan['launch_discount_percent'] ? (int)$plan['launch_discount_percent'] : null;
            $plan['is_popular'] = (bool)$plan['is_popular'];
            $plan['is_launch_offer'] = (bool)$plan['is_launch_offer'];
            $plan['has_direct_link'] = (bool)$plan['has_direct_link'];
            $plan['has_api'] = (bool)$plan['has_api'];
            $plan['has_priority_position'] = (bool)$plan['has_priority_position'];
            $plan['can_send_messages'] = (bool)$plan['can_send_messages'];
        }

        jsonSuccess($plans);
    }

} catch (Exception $e) {
    error_log('get_membership_options.php Error: ' . $e->getMessage());
    jsonError('Error al obtener opciones de membresía: ' . $e->getMessage(), 500);
}
