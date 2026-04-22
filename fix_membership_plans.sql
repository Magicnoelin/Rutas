-- ============================================
-- FIX MEMBERSHIP PLANS DATA
-- Rutas Rurales - Corrección de datos incorrectos
-- ============================================

-- 1. First, check current data
SELECT 'Current membership plans:' as message;
SELECT id, name, price_monthly, price_yearly FROM membership_plans ORDER BY id;

-- 2. Update plans to correct values based on diagnostic report
UPDATE membership_plans SET 
    name = CASE id
        WHEN 1 THEN 'Gratuito Alojamiento'
        WHEN 2 THEN 'Básico Alojamiento'
        WHEN 3 THEN 'Premium Alojamiento'
    END,
    price_monthly = CASE id
        WHEN 1 THEN 0.00
        WHEN 2 THEN 10.00
        WHEN 3 THEN 10.00
    END,
    price_yearly = CASE id
        WHEN 1 THEN 0.00
        WHEN 2 THEN 50.00
        WHEN 3 THEN 100.00
    END,
    description = CASE id
        WHEN 1 THEN 'Plan gratuito para empezar. Publica hasta 2 alojamientos con máximo 15 plazas totales.'
        WHEN 2 THEN 'Plan básico para alojamientos rurales. Publica hasta 4 alojamientos con máximo 30 plazas totales.'
        WHEN 3 THEN 'Plan premium flexible. 10€/mes por cada alojamiento o cada 15 plazas. Sin límites fijos.'
    END,
    features = CASE id
        WHEN 1 THEN JSON_ARRAY(
            'Publicar hasta 2 alojamientos',
            'Máximo 15 plazas totales',
            'Gestión básica de reservas',
            'Soporte por email',
            'Panel de control básico',
            'Sin coste inicial'
        )
        WHEN 2 THEN JSON_ARRAY(
            'Publicar hasta 4 alojamientos',
            'Máximo 30 plazas totales',
            'Gestión básica de reservas',
            'Soporte por email',
            'Panel de control básico',
            'Ahorra 20€ con pago anual'
        )
        WHEN 3 THEN JSON_ARRAY(
            'Pago por uso: 10€/mes por alojamiento',
            'O 10€/mes por cada 15 plazas',
            'Sin límites fijos de alojamientos',
            'Cálculo dinámico mensual',
            'Soporte prioritario',
            'Panel de control avanzado',
            'Estadísticas detalladas',
            'Posicionamiento destacado'
        )
    END,
    max_accommodations = CASE id
        WHEN 1 THEN 2
        WHEN 2 THEN 4
        WHEN 3 THEN 999
    END,
    max_places = CASE id
        WHEN 1 THEN 15
        WHEN 2 THEN 30
        WHEN 3 THEN 999
    END,
    is_popular = CASE id
        WHEN 1 THEN FALSE
        WHEN 2 THEN TRUE
        WHEN 3 THEN FALSE
    END,
    display_order = CASE id
        WHEN 1 THEN 1
        WHEN 2 THEN 2
        WHEN 3 THEN 3
    END
WHERE id IN (1, 2, 3);

-- 3. If table doesn't exist or is empty, insert the correct plans
INSERT INTO membership_plans (
    id, plan_type, name, slug, description,
    price_monthly, price_yearly,
    max_accommodations, max_places,
    features, is_popular, display_order, status
) VALUES 
(
    1, 'alojamiento', 'Gratuito Alojamiento', 'gratuito-alojamiento',
    'Plan gratuito para empezar. Publica hasta 2 alojamientos con máximo 15 plazas totales.',
    0.00, 0.00,
    2, 15,
    JSON_ARRAY(
        'Publicar hasta 2 alojamientos',
        'Máximo 15 plazas totales',
        'Gestión básica de reservas',
        'Soporte por email',
        'Panel de control básico',
        'Sin coste inicial'
    ),
    FALSE, 1, 'active'
),
(
    2, 'alojamiento', 'Básico Alojamiento', 'basico-alojamiento',
    'Plan básico para alojamientos rurales. Publica hasta 4 alojamientos con máximo 30 plazas totales.',
    10.00, 50.00,
    4, 30,
    JSON_ARRAY(
        'Publicar hasta 4 alojamientos',
        'Máximo 30 plazas totales',
        'Gestión básica de reservas',
        'Soporte por email',
        'Panel de control básico',
        'Ahorra 20€ con pago anual'
    ),
    TRUE, 2, 'active'
),
(
    3, 'alojamiento', 'Premium Alojamiento', 'premium-alojamiento',
    'Plan premium flexible. 10€/mes por cada alojamiento o cada 15 plazas. Sin límites fijos.',
    10.00, 100.00,
    999, 999,
    JSON_ARRAY(
        'Pago por uso: 10€/mes por alojamiento',
        'O 10€/mes por cada 15 plazas',
        'Sin límites fijos de alojamientos',
        'Cálculo dinámico mensual',
        'Soporte prioritario',
        'Panel de control avanzado',
        'Estadísticas detalladas',
        'Posicionamiento destacado'
    ),
    FALSE, 3, 'active'
)
ON DUPLICATE KEY UPDATE 
    plan_type = VALUES(plan_type),
    name = VALUES(name),
    slug = VALUES(slug),
    description = VALUES(description),
    price_monthly = VALUES(price_monthly),
    price_yearly = VALUES(price_yearly),
    max_accommodations = VALUES(max_accommodations),
    max_places = VALUES(max_places),
    features = VALUES(features),
    is_popular = VALUES(is_popular),
    display_order = VALUES(display_order),
    status = VALUES(status);

-- 4. Verify the changes
SELECT 'Updated membership plans:' as message;
SELECT 
    id,
    name,
    price_monthly,
    price_yearly,
    max_accommodations,
    max_places,
    is_popular
FROM membership_plans 
ORDER BY id;

-- 5. Create user_memberships table if it doesn't exist (mentioned in diagnostic)
CREATE TABLE IF NOT EXISTS user_memberships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    plan_name VARCHAR(100) NOT NULL,
    status ENUM('active', 'expired', 'canceled', 'pending') DEFAULT 'pending',
    start_date DATE,
    end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES membership_plans(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT '✅ user_memberships table created/verified' as message;