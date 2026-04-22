-- ============================================
-- COMPLETAR TABLAS DEL SISTEMA DE MEMBRESÍAS
-- Rutas Rurales - Sistema de Facturación
-- ============================================

-- 0. CREAR TABLAS DE REFERENCIA SI NO EXISTEN (para evitar errores de FK)
-- Tabla users (si no existe)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('turista', 'alojamiento', 'promotor_eventos', 'actividad_cultural') NOT NULL DEFAULT 'turista',
    business_name VARCHAR(255) NULL,
    business_description TEXT NULL,
    verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    subscription_level ENUM('basic', 'premium') DEFAULT 'basic',
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(50),
    password_hash VARCHAR(255) NOT NULL,
    email_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(255),
    terms_accepted TINYINT(1) DEFAULT 1,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    -- Campos de membresía (necesarios para la vista membership_summary)
    membership_type VARCHAR(50) NULL COMMENT 'Tipo de membresía (free, basic, premium, etc.)',
    membership_status ENUM('active', 'expired', 'canceled', 'pending') DEFAULT 'pending' COMMENT 'Estado de la membresía',
    membership_start_date DATE NULL COMMENT 'Fecha de inicio de la membresía',
    membership_end_date DATE NULL COMMENT 'Fecha de fin de la membresía',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_user_type (user_type),
    INDEX idx_verification_status (verification_status),
    INDEX idx_membership_type (membership_type),
    INDEX idx_membership_status (membership_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla membership_plans (si no existe)
CREATE TABLE IF NOT EXISTS membership_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_type ENUM('alojamiento', 'restaurante', 'apoyo_plataforma') NOT NULL COMMENT 'Tipo de membresía',
    name VARCHAR(100) NOT NULL COMMENT 'Nombre del plan',
    slug VARCHAR(100) NOT NULL UNIQUE COMMENT 'Slug único del plan',
    description TEXT COMMENT 'Descripción del plan',
    
    -- Precios mensuales (sin IVA)
    price_monthly DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Precio mensual sin IVA',
    price_yearly DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Precio anual sin IVA',
    
    -- Precios con IVA (21%)
    price_monthly_with_vat DECIMAL(10,2) GENERATED ALWAYS AS (price_monthly * 1.21) STORED,
    price_yearly_with_vat DECIMAL(10,2) GENERATED ALWAYS AS (price_yearly * 1.21) STORED,
    
    -- IDs de Stripe
    stripe_product_id VARCHAR(255) NULL COMMENT 'ID del producto en Stripe',
    stripe_monthly_price_id VARCHAR(255) NULL COMMENT 'ID del precio mensual en Stripe',
    stripe_yearly_price_id VARCHAR(255) NULL COMMENT 'ID del precio anual en Stripe',
    
    -- Límites del plan
    max_accommodations INT DEFAULT 0 COMMENT 'Máximo de alojamientos (para tipo alojamiento)',
    max_places INT DEFAULT 0 COMMENT 'Máximo de plazas totales',
    max_restaurants INT DEFAULT 0 COMMENT 'Máximo de restaurantes (para tipo restaurante)',
    
    -- Características
    features JSON COMMENT 'Características del plan en formato JSON',
    is_popular BOOLEAN DEFAULT FALSE COMMENT 'Indica si es el plan más popular',
    display_order INT DEFAULT 0 COMMENT 'Orden de visualización',
    
    -- Estado
    status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_plan_type (plan_type),
    INDEX idx_status (status),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla user_subscriptions (si no existe)
CREATE TABLE IF NOT EXISTS user_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'ID del usuario',
    plan_id INT NULL COMMENT 'ID del plan de membresía (puede ser NULL si el plan se elimina)',
    plan_name VARCHAR(100) NOT NULL COMMENT 'Nombre del plan al momento de la suscripción',
    
    -- Información de facturación
    billing_cycle ENUM('monthly', 'yearly') NOT NULL COMMENT 'Ciclo de facturación',
    price DECIMAL(10,2) NOT NULL COMMENT 'Precio sin IVA',
    vat_amount DECIMAL(10,2) NOT NULL COMMENT 'Monto del IVA (21%)',
    total_amount DECIMAL(10,2) NOT NULL COMMENT 'Precio total con IVA',
    currency VARCHAR(3) DEFAULT 'EUR' COMMENT 'Moneda (EUR)',
    
    -- Información de Stripe
    stripe_subscription_id VARCHAR(255) NULL COMMENT 'ID de suscripción en Stripe',
    stripe_customer_id VARCHAR(255) NULL COMMENT 'ID del cliente en Stripe',
    stripe_invoice_id VARCHAR(255) NULL COMMENT 'ID de la factura en Stripe',
    
    -- Fechas
    start_date DATE NOT NULL COMMENT 'Fecha de inicio',
    end_date DATE NULL COMMENT 'Fecha de finalización',
    next_billing_date DATE NULL COMMENT 'Próxima fecha de facturación',
    canceled_at DATE NULL COMMENT 'Fecha de cancelación',
    
    -- Estado
    status ENUM('active', 'pending', 'canceled', 'expired', 'past_due') DEFAULT 'pending',
    
    -- Datos de facturación
    billing_name VARCHAR(255) NULL COMMENT 'Nombre para facturación',
    billing_nif VARCHAR(50) NULL COMMENT 'NIF/CIF para facturación',
    billing_address TEXT NULL COMMENT 'Dirección para facturación',
    billing_email VARCHAR(255) NULL COMMENT 'Email para facturación',
    
    -- Metadatos
    metadata JSON COMMENT 'Metadatos adicionales',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES membership_plans(id) ON DELETE SET NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_end_date (end_date),
    INDEX idx_stripe_subscription (stripe_subscription_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1. CREAR TABLA DE INTENCIONES DE PAGO
-- Nota: plan_id debe permitir NULL si usamos ON DELETE SET NULL
CREATE TABLE IF NOT EXISTS payment_intents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'ID del usuario',
    plan_id INT NULL COMMENT 'ID del plan de membresía (puede ser NULL si el plan se elimina)',
    
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

-- 3. AGREGAR CAMPOS FALTANTES A TABLA USERS (para la vista membership_summary)
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS membership_type VARCHAR(50) NULL COMMENT 'Tipo de membresía (free, basic, premium, etc.)',
ADD COLUMN IF NOT EXISTS membership_status ENUM('active', 'expired', 'canceled', 'pending') DEFAULT 'pending' COMMENT 'Estado de la membresía',
ADD COLUMN IF NOT EXISTS membership_start_date DATE NULL COMMENT 'Fecha de inicio de la membresía',
ADD COLUMN IF NOT EXISTS membership_end_date DATE NULL COMMENT 'Fecha de fin de la membresía';

-- 4. AGREGAR CAMPOS FALTANTES A TABLA USER_SUBSCRIPTIONS (si es necesario)
-- Agregar todas las columnas que la vista membership_summary necesita
ALTER TABLE user_subscriptions 
ADD COLUMN IF NOT EXISTS plan_name VARCHAR(100) NULL COMMENT 'Nombre del plan al momento de la suscripción',
ADD COLUMN IF NOT EXISTS billing_cycle ENUM('monthly', 'yearly') NULL COMMENT 'Ciclo de facturación',
ADD COLUMN IF NOT EXISTS price DECIMAL(10,2) NULL COMMENT 'Precio sin IVA',
ADD COLUMN IF NOT EXISTS total_amount DECIMAL(10,2) NULL COMMENT 'Precio total con IVA',
ADD COLUMN IF NOT EXISTS status ENUM('active', 'pending', 'canceled', 'expired', 'past_due') DEFAULT 'pending' COMMENT 'Estado de la suscripción',
ADD COLUMN IF NOT EXISTS end_date DATE NULL COMMENT 'Fecha de finalización';

-- Si las columnas se agregaron como NULL, actualizar los valores existentes si es necesario
-- (esto es solo para compatibilidad, en una instalación nueva serán NOT NULL desde el principio)

-- 5. CREAR TABLA DE FACTURAS SI NO EXISTE (para compatibilidad)
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subscription_id INT NOT NULL COMMENT 'ID de la suscripción',
    user_id INT NOT NULL COMMENT 'ID del usuario',
    
    -- Información de la factura
    invoice_number VARCHAR(50) NOT NULL UNIQUE COMMENT 'Número de factura único',
    invoice_date DATE NOT NULL COMMENT 'Fecha de la factura',
    due_date DATE NOT NULL COMMENT 'Fecha de vencimiento',
    
    -- Montos
    subtotal DECIMAL(10,2) NOT NULL COMMENT 'Subtotal sin IVA',
    vat_rate DECIMAL(5,2) DEFAULT 21.00 COMMENT 'Tasa de IVA (%)',
    vat_amount DECIMAL(10,2) NOT NULL COMMENT 'Monto del IVA',
    total_amount DECIMAL(10,2) NOT NULL COMMENT 'Total con IVA',
    
    -- Información de Stripe
    stripe_invoice_id VARCHAR(255) NULL COMMENT 'ID de factura en Stripe',
    stripe_payment_intent_id VARCHAR(255) NULL COMMENT 'ID del intento de pago',
    stripe_receipt_url VARCHAR(500) NULL COMMENT 'URL del recibo de Stripe',
    
    -- Estado de pago
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    paid_at TIMESTAMP NULL COMMENT 'Fecha de pago',
    
    -- Datos de facturación (snapshot al momento de la factura)
    billing_name VARCHAR(255) NOT NULL,
    billing_nif VARCHAR(50) NULL,
    billing_address TEXT NOT NULL,
    billing_email VARCHAR(255) NOT NULL,
    
    -- Detalles del producto
    description TEXT NOT NULL COMMENT 'Descripción del producto/servicio',
    
    -- Metadatos
    metadata JSON COMMENT 'Metadatos adicionales',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (subscription_id) REFERENCES user_subscriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_user_id (user_id),
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_invoice_date (invoice_date),
    INDEX idx_payment_status (payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar columnas faltantes si la tabla ya existe pero no las tiene
ALTER TABLE invoices 
ADD COLUMN IF NOT EXISTS user_id INT NULL COMMENT 'ID del usuario',
ADD COLUMN IF NOT EXISTS payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending' COMMENT 'Estado de pago',
ADD COLUMN IF NOT EXISTS total_amount DECIMAL(10,2) NULL COMMENT 'Total con IVA';

-- Si user_id se agregó como NULL, agregar foreign key después
-- (esto se manejará manualmente si es necesario)

-- 6. CREAR VISTA PARA RESUMEN DE MEMBRESÍAS
-- Nota: La tabla invoices tiene estructura diferente a la esperada:
-- - tax_amount en lugar de vat_amount
-- - total en lugar de total_amount (pero también hay total_amount agregada después)
-- - status en lugar de payment_status (pero también hay payment_status agregada después)
-- - notes en lugar de description
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
    (SELECT COUNT(*) FROM invoices i WHERE i.user_id = u.id AND (i.status = 'paid' OR i.payment_status = 'paid')) as paid_invoices_count,
    (SELECT SUM(COALESCE(i.total_amount, i.total)) FROM invoices i WHERE i.user_id = u.id AND (i.status = 'paid' OR i.payment_status = 'paid')) as total_spent,
    
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

-- 7. CREAR VISTA PARA REPORTES DE FACTURACIÓN
-- Nota: Adaptada a la estructura real de la tabla invoices
CREATE OR REPLACE VIEW billing_reports AS
SELECT 
    DATE_FORMAT(i.invoice_date, '%Y-%m') as month,
    COUNT(*) as total_invoices,
    SUM(i.subtotal) as total_subtotal,
    SUM(i.tax_amount) as total_vat,
    SUM(COALESCE(i.total_amount, i.total)) as total_revenue,
    
    -- Por tipo de plan (usando notes ya que no hay description)
    -- Nota: Esto puede no funcionar bien si notes no contiene los nombres de plan
    SUM(CASE WHEN i.notes LIKE '%Básico Alojamiento%' OR i.notes LIKE '%basic%' OR i.notes LIKE '%alojamiento%' THEN COALESCE(i.total_amount, i.total) ELSE 0 END) as basic_accommodation_revenue,
    SUM(CASE WHEN i.notes LIKE '%Premium Alojamiento%' OR i.notes LIKE '%premium%' THEN COALESCE(i.total_amount, i.total) ELSE 0 END) as premium_accommodation_revenue,
    SUM(CASE WHEN i.notes LIKE '%Básico Restaurante%' OR i.notes LIKE '%restaurante%' THEN COALESCE(i.total_amount, i.total) ELSE 0 END) as basic_restaurant_revenue,
    SUM(CASE WHEN i.notes LIKE '%Premium Restaurante%' THEN COALESCE(i.total_amount, i.total) ELSE 0 END) as premium_restaurant_revenue,
    SUM(CASE WHEN i.notes LIKE '%Apoyo%' OR i.notes LIKE '%support%' OR i.notes LIKE '%ayuda%' THEN COALESCE(i.total_amount, i.total) ELSE 0 END) as support_revenue,
    
    -- Métricas de pago
    -- Mapeo: status='paid' o payment_status='paid' -> paid
    -- status='issued' o payment_status='pending' -> pending
    -- status='cancelled' o payment_status='failed' -> failed
    SUM(CASE WHEN i.status = 'paid' OR i.payment_status = 'paid' THEN 1 ELSE 0 END) as paid_invoices,
    SUM(CASE WHEN i.status = 'issued' OR i.payment_status = 'pending' THEN 1 ELSE 0 END) as pending_invoices,
    SUM(CASE WHEN i.status = 'cancelled' OR i.payment_status = 'failed' OR i.payment_status = 'refunded' THEN 1 ELSE 0 END) as failed_invoices
    
FROM invoices i
WHERE i.invoice_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
GROUP BY DATE_FORMAT(i.invoice_date, '%Y-%m')
ORDER BY month DESC;

-- 8. CREAR PROCEDIMIENTO PARA ACTUALIZAR ESTADOS DE SUSCRIPCIÓN
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

-- 9. CREAR EVENTO PARA EJECUTAR PROCEDIMIENTO DIARIAMENTE
CREATE EVENT IF NOT EXISTS daily_subscription_maintenance
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
    CALL update_subscription_statuses();

-- 10. INSERTAR DATOS DE CONFIGURACIÓN INICIAL

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

-- 11. CREAR TABLA DE CONFIGURACIÓN DEL SISTEMA (si no existe)
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. FUNCIÓN PARA OBTENER CONFIGURACIÓN
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