-- ============================================
-- COMPLETAR TABLAS DEL SISTEMA DE MEMBRESÍAS
-- Rutas Rurales - Sistema de Facturación
-- ============================================

-- 1. CREAR TABLA DE INTENCIONES DE PAGO
CREATE TABLE IF NOT EXISTS payment_intents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'ID del usuario',
    plan_id INT NOT NULL COMMENT 'ID del plan de membresía',
    
    -- Información de Stripe
    stripe_session_id VARCHAR(255) NOT NULL UNIQUE COMMENT 'ID de la sesión de Stripe',
    stripe_price_id VARCHAR(255) NOT NULL COMMENT 'ID del precio en Stripe',
    
    -- Montos
    amount DECIMAL(10,2) NOT NULL COMMENT 'Monto sin IVA',
    vat_amount DECIMAL(10,2) NOT NULL COMMENT 'Monto del IVA',
    total_amount DECIMAL(10,2) NOT NULL COMMENT 'Total con IVA',
    
    -- Ciclo de facturación
    billing_cycle ENUM('monthly', 'yearly') NOT NULL COMMENT 'Ciclo de facturación',
    
    -- Estado
    status ENUM('pending', 'completed', 'failed', 'canceled') DEFAULT 'pending',
    
    -- Fechas
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL COMMENT 'Fecha de completado',
    
    -- Metadatos
    metadata JSON COMMENT 'Metadatos adicionales (plan_name, user_email, etc.)',
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES membership_plans(id) ON DELETE SET NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_stripe_session (stripe_session_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. CREAR TABLA DE FALLOS DE PAGO
CREATE TABLE IF NOT EXISTS payment_failures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'ID del usuario',
    subscription_id INT NULL COMMENT 'ID de la suscripción (si aplica)',
    
    -- Información de Stripe
    stripe_invoice_id VARCHAR(255) NULL COMMENT 'ID de la factura en Stripe',
    stripe_payment_intent_id VARCHAR(255) NULL COMMENT 'ID del intento de pago',
    
    -- Monto
    amount DECIMAL(10,2) NOT NULL COMMENT 'Monto del pago fallido',
    
    -- Razón del fallo
    failure_reason VARCHAR(500) NOT NULL COMMENT 'Razón del fallo de pago',
    failure_code VARCHAR(100) NULL COMMENT 'Código de error de Stripe',
    
    -- Intentos
    attempt_count INT DEFAULT 1 COMMENT 'Número de intentos',
    
    -- Estado
    resolved BOOLEAN DEFAULT FALSE COMMENT 'Indica si el fallo fue resuelto',
    resolved_at TIMESTAMP NULL COMMENT 'Fecha de resolución',
    resolution_notes TEXT NULL COMMENT 'Notas de resolución',
    
    -- Metadatos
    metadata JSON COMMENT 'Metadatos adicionales',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES user_subscriptions(id) ON DELETE SET NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_resolved (resolved),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. ACTUALIZAR TABLA DE FACTURAS CON CAMPOS FALTANTES
ALTER TABLE invoices 
ADD COLUMN IF NOT EXISTS subscription_id INT NULL COMMENT 'ID de la suscripción',
ADD FOREIGN KEY IF NOT EXISTS fk_invoices_subscription_id 
    FOREIGN KEY (subscription_id) REFERENCES user_subscriptions(id) ON DELETE SET NULL;

-- 4. CREAR VISTA PARA RESUMEN DE MEMBRESÍAS
CREATE OR REPLACE VIEW membership_summary AS
SELECT 
    u.id as user_id,
    u.email,
    CONCAT(u.first_name, ' ', u.last_name) as user_name,
    u.membership_type,
    u.membership_status,
    u.membership_start_date,
    u.membership_end_date,
    
    -- Información de suscripción activa
    s.plan_name as current_plan,
    s.billing_cycle,
    s.price as plan_price,
    s.total_amount as plan_total,
    s.status as subscription_status,
    s.end_date as subscription_end_date,
    
    -- Estadísticas
    (SELECT COUNT(*) FROM user_subscriptions us WHERE us.user_id = u.id) as total_subscriptions,
    (SELECT COUNT(*) FROM invoices i WHERE i.user_id = u.id AND i.payment_status = 'paid') as paid_invoices_count,
    (SELECT SUM(i.total_amount) FROM invoices i WHERE i.user_id = u.id AND i.payment_status = 'paid') as total_spent,
    
    -- Límites actuales
    CASE 
        WHEN u.membership_type LIKE '%premium%' THEN 10
        WHEN u.membership_type LIKE '%basic%' THEN 2
        ELSE 1 
    END as max_accommodations,
    
    CASE 
        WHEN u.membership_type LIKE '%premium%' THEN 100
        WHEN u.membership_type LIKE '%basic%' THEN 15
        ELSE 8 
    END as max_places
    
FROM users u
LEFT JOIN user_subscriptions s ON u.id = s.user_id AND s.status = 'active'
WHERE u.membership_type IS NOT NULL AND u.membership_type != 'free';

-- 5. CREAR VISTA PARA REPORTES DE FACTURACIÓN
CREATE OR REPLACE VIEW billing_reports AS
SELECT 
    DATE_FORMAT(i.invoice_date, '%Y-%m') as month,
    COUNT(*) as total_invoices,
    SUM(i.subtotal) as total_subtotal,
    SUM(i.vat_amount) as total_vat,
    SUM(i.total_amount) as total_revenue,
    
    -- Por tipo de plan
    SUM(CASE WHEN i.description LIKE '%Básico Alojamiento%' THEN i.total_amount ELSE 0 END) as basic_accommodation_revenue,
    SUM(CASE WHEN i.description LIKE '%Premium Alojamiento%' THEN i.total_amount ELSE 0 END) as premium_accommodation_revenue,
    SUM(CASE WHEN i.description LIKE '%Básico Restaurante%' THEN i.total_amount ELSE 0 END) as basic_restaurant_revenue,
    SUM(CASE WHEN i.description LIKE '%Premium Restaurante%' THEN i.total_amount ELSE 0 END) as premium_restaurant_revenue,
    SUM(CASE WHEN i.description LIKE '%Apoyo%' THEN i.total_amount ELSE 0 END) as support_revenue,
    
    -- Métricas de pago
    SUM(CASE WHEN i.payment_status = 'paid' THEN 1 ELSE 0 END) as paid_invoices,
    SUM(CASE WHEN i.payment_status = 'pending' THEN 1 ELSE 0 END) as pending_invoices,
    SUM(CASE WHEN i.payment_status = 'failed' THEN 1 ELSE 0 END) as failed_invoices
    
FROM invoices i
WHERE i.invoice_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
GROUP BY DATE_FORMAT(i.invoice_date, '%Y-%m')
ORDER BY month DESC;

-- 6. CREAR PROCEDIMIENTO PARA ACTUALIZAR ESTADOS DE SUSCRIPCIÓN
DELIMITER //
CREATE PROCEDURE update_subscription_statuses()
BEGIN
    -- Marcar suscripciones vencidas como expired
    UPDATE user_subscriptions 
    SET status = 'expired'
    WHERE status = 'active' 
      AND end_date < CURDATE();
    
    -- Marcar usuarios con suscripciones vencidas
    UPDATE users u
    JOIN user_subscriptions s ON u.id = s.user_id AND s.status = 'expired'
    SET u.membership_status = 'expired',
        u.membership_end_date = CURDATE()
    WHERE u.membership_status = 'active';
    
    -- Marcar pagos pendientes muy antiguos como cancelados
    UPDATE payment_intents 
    SET status = 'canceled'
    WHERE status = 'pending' 
      AND created_at < DATE_SUB(CURDATE(), INTERVAL 7 DAY);
END//
DELIMITER ;

-- 7. CREAR EVENTO PARA EJECUTAR PROCEDIMIENTO DIARIAMENTE
CREATE EVENT IF NOT EXISTS daily_subscription_maintenance
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
    CALL update_subscription_statuses();

-- 8. INSERTAR DATOS DE CONFIGURACIÓN INICIAL

-- Configuración de la empresa para facturación
INSERT INTO system_settings (setting_key, setting_value, description)
VALUES 
('company_name', 'Rutas Rurales S.L.', 'Nombre de la empresa para facturación'),
('company_nif', 'B12345678', 'NIF/CIF de la empresa'),
('company_address', 'Calle Ejemplo, 123', 'Dirección de la empresa'),
('company_city', 'Soria', 'Ciudad de la empresa'),
('company_postal_code', '42001', 'Código postal'),
('company_country', 'España', 'País'),
('company_email', 'facturacion@rutasrurales.io', 'Email de facturación'),
('company_phone', '+34 605 249 696', 'Teléfono de contacto'),
('invoice_prefix', 'RUT', 'Prefijo para números de factura'),
('default_vat_rate', '21.00', 'Tasa de IVA por defecto (%)'),
('payment_terms_days', '30', 'Días para vencimiento de facturas'),
('stripe_mode', 'test', 'Modo de Stripe (test/live)'),
('stripe_public_key_test', 'pk_test_...', 'Clave pública de Stripe (test)'),
('stripe_secret_key_test', 'sk_test_...', 'Clave secreta de Stripe (test)'),
('stripe_webhook_secret_test', 'whsec_...', 'Secreto de webhook de Stripe (test)')
ON DUPLICATE KEY UPDATE 
    setting_value = VALUES(setting_value),
    updated_at = CURRENT_TIMESTAMP;

-- 9. CREAR TABLA DE CONFIGURACIÓN DEL SISTEMA (si no existe)
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. FUNCIÓN PARA OBTENER CONFIGURACIÓN
DELIMITER //
CREATE FUNCTION get_setting(setting_key_param VARCHAR(100)) RETURNS TEXT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE setting_value TEXT;
    
    SELECT setting_value INTO setting_value
    FROM system_settings
    WHERE setting_key = setting_key_param;
    
    RETURN setting_value;
END//
DELIMITER ;

-- ============================================
-- MENSAJE DE ÉXITO
-- ============================================
SELECT '✅ Sistema de membresías configurado correctamente' as message;

-- Para verificar la instalación:
-- SELECT * FROM membership_summary LIMIT 5;
-- SELECT * FROM billing_reports LIMIT 12;