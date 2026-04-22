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
            ORDER BY id ASC
        ");

        $plans = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Si la tabla no existe, usar planes por defecto
        error_log('get_membership_options.php: La tabla membership_plans no existe, usando planes por defecto');
        $plans = [];
    }

    if (empty($plans)) {
        // Si no hay planes en la base de datos, devolver planes por defecto
        // basados en los que deberían estar insertados por configurar_membresias_produccion.sql
        $defaultPlans = [
            [
                'id' => 1,
                'name' => 'Gratuito Alojamiento',
                'description' => 'Plan gratuito para empezar. Publica hasta 2 alojamientos con máximo 15 plazas totales.',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'features' => [
                    'Publicar hasta 2 alojamientos',
                    'Máximo 15 plazas totales',
                    'Gestión básica de reservas',
                    'Soporte por email',
                    'Panel de control básico',
                    'Sin coste inicial'
                ],
                'is_popular' => false,
                'stripe_product_id' => null,
                'stripe_monthly_price_id' => null,
                'stripe_yearly_price_id' => null
            ],
            [
                'id' => 2,
                'name' => 'Básico Alojamiento',
                'description' => 'Plan básico para alojamientos rurales. Publica hasta 4 alojamientos con máximo 30 plazas totales.',
                'price_monthly' => 10.00,
                'price_yearly' => 50.00,
                'features' => [
                    'Publicar hasta 4 alojamientos',
                    'Máximo 30 plazas totales',
                    'Gestión básica de reservas',
                    'Soporte por email',
                    'Panel de control básico',
                    'Ahorra 20€ con pago anual'
                ],
                'is_popular' => true,
                'stripe_product_id' => null,
                'stripe_monthly_price_id' => null,
                'stripe_yearly_price_id' => null
            ],
            [
                'id' => 3,
                'name' => 'Premium Alojamiento',
                'description' => 'Plan premium para alojamientos con crecimiento. Precio dinámico según número de alojamientos y plazas.',
                'price_monthly' => 10.00,
                'price_yearly' => 100.00,
                'features' => [
                    'Alojamientos ilimitados',
                    'Plazas ilimitadas',
                    'Gestión avanzada de reservas',
                    'Soporte prioritario 24/7',
                    'Panel de control avanzado',
                    'Estadísticas detalladas',
                    'Posicionamiento destacado',
                    'API de integración'
                ],
                'is_popular' => false,
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
        }

        jsonSuccess($plans);
    }

} catch (Exception $e) {
    error_log('get_membership_options.php Error: ' . $e->getMessage());
    jsonError('Error al obtener opciones de membresía: ' . $e->getMessage(), 500);
}
