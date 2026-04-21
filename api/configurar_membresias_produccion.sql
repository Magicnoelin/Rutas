-- ============================================
-- CONFIGURACIÓN COMPLETA DE MEMBRESÍAS PARA PRODUCCIÓN
-- Rutas Rurales - Sistema de Facturación
-- ============================================

-- 1. CREAR TABLA DE PLANES DE MEMBRESÍA (si no existe)
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

-- 2. CREAR TABLA DE SUSCRIPCIONES DE USUARIOS
CREATE TABLE IF NOT EXISTS user_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'ID del usuario',
    plan_id INT NOT NULL COMMENT 'ID del plan de membresía',
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

-- 3. CREAR TABLA DE FACTURAS
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

-- 4. INSERTAR PLANES DE MEMBRESÍA SEGÚN ESPECIFICACIONES

-- ============================================
-- PLANES DE ALOJAMIENTO
-- ============================================

-- Plan Gratuito Alojamiento (0€ - hasta 2 alojamientos, 15 plazas)
INSERT INTO membership_plans (
    plan_type, name, slug, description,
    price_monthly, price_yearly,
    max_accommodations, max_places,
    features, is_popular, display_order, status
) VALUES (
    'alojamiento',
    'Gratuito Alojamiento',
    'gratuito-alojamiento',
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
) ON DUPLICATE KEY UPDATE 
    price_monthly = 0.00,
    price_yearly = 0.00,
    max_accommodations = 2,
    max_places = 15;

-- Plan Básico Alojamiento (10€/mes + IVA - hasta 4 alojamientos, 30 plazas)
INSERT INTO membership_plans (
    plan_type, name, slug, description,
    price_monthly, price_yearly,
    max_accommodations, max_places,
    features, is_popular, display_order, status
) VALUES (
    'alojamiento',
    'Básico Alojamiento',
    'basico-alojamiento',
    'Plan básico para alojamientos rurales. Publica hasta 4 alojamientos con máximo 30 plazas totales.',
    10.00, 50.00,  -- 10€/mes o 50€/año
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
) ON DUPLICATE KEY UPDATE 
    price_monthly = 10.00,
    price_yearly = 50.00,
    max_accommodations = 4,
    max_places = 30;

-- Plan Premium Alojamiento (10€/mes por alojamiento o cada 15 plazas)
INSERT INTO membership_plans (
    plan_type, name, slug, description,
    price_monthly, price_yearly,
    max_accommodations, max_places,
    features, is_popular, display_order, status
) VALUES (
    'alojamiento',
    'Premium Alojamiento',
    'premium-alojamiento',
    'Plan premium flexible. 10€/mes por cada alojamiento o cada 15 plazas. Sin límites fijos.',
    10.00, 100.00,  -- Precio base, cálculo dinámico
    999, 999,  -- Límites altos, cálculo dinámico por alojamiento/plazas
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
) ON DUPLICATE KEY UPDATE 
    price_monthly = 10.00,
    price_yearly = 100.00,
    max_accommodations = 999,
    max_places = 999;

-- ============================================
-- PLANES DE RESTAURANTE
-- ============================================

-- Plan Básico Restaurante (5€/mes + IVA)
INSERT INTO membership_plans (
    plan_type, name, slug, description,
    price_monthly, price_yearly,
    max_restaurants,
    features, display_order, status
) VALUES (
    'restaurante',
    'Básico Restaurante',
    'basico-restaurante',
    'Plan básico para restaurantes. Publica tu restaurante en la plataforma.',
    5.00, 0.00,  -- Solo mensual
    1,
    JSON_ARRAY(
        'Publicar 1 restaurante',
        'Ficha básica del restaurante',
        'Menú digital',
        'Horarios y contacto',
        'Soporte básico'
    ),
    3, 'active'
) ON DUPLICATE KEY UPDATE 
    price_monthly = 5.00,
    price_yearly = 0.00,
    max_restaurants = 1;

-- Plan Premium Restaurante (50€/año + IVA)
INSERT INTO membership_plans (
    plan_type, name, slug, description,
    price_monthly, price_yearly,
    max_restaurants,
    features, display_order, status
) VALUES (
    'restaurante',
    'Premium Restaurante',
    'premium-restaurante',
    'Plan completo para restaurantes. Destaca tu negocio y llega a más clientes.',
    0.00, 50.00,  -- Solo anual
    3,
    JSON_ARRAY(
        'Publicar hasta 3 restaurantes',
        'Ficha completa destacada',
        'Menú digital con fotos',
        'Reservas online',
        'Ofertas promocionales',
        'Posicionamiento destacado',
        'Estadísticas de visitas',
        'Soporte prioritario'
    ),
    4, 'active'
) ON DUPLICATE KEY UPDATE 
    price_monthly = 0.00,
    price_yearly = 50.00,
    max_restaurants = 3;

-- ============================================
-- PLANES DE APOYO A LA PLATAFORMA
-- ============================================

-- Apoyo Básico (50€ + IVA - Pago único)
INSERT INTO membership_plans (
    plan_type, name, slug, description,
    price_monthly, price_yearly,
    features, display_order, status
) VALUES (
    'apoyo_plataforma',
    'Apoyo Básico',
    'apoyo-basico',
    'Contribución básica para apoyar el desarrollo de la plataforma.',
    50.00, 50.00,  -- Pago único (igual en mensual y anual)
    JSON_ARRAY(
        'Contribución al desarrollo de la plataforma',
        'Mención en la página de agradecimientos',
        'Certificado de apoyo digital',
        'Newsletter exclusivo'
    ),
    5, 'active'
) ON DUPLICATE KEY UPDATE 
    price_monthly = 50.00,
    price_yearly = 50.00;

-- Apoyo Avanzado (100€ + IVA - Pago único)
INSERT INTO membership_plans (
    plan_type, name, slug, description,
    price_monthly, price_yearly,
    features, display_order, status
) VALUES (
    'apoyo_plataforma',
    'Apoyo Avanzado',
    'apoyo-avanzado',
    'Contribución significativa para el crecimiento de la plataforma.',
    100.00, 100.00,  -- Pago único
    JSON_ARRAY(
        'Contribución significativa al desarrollo',
        'Destacado en la página de agradecimientos',
        'Certificado de apoyo premium',
        'Newsletter exclusivo',
        'Acceso anticipado a nuevas funcionalidades'
    ),
    6, 'active'
) ON DUPLICATE KEY UPDATE 
    price_monthly = 100.00,
    price_yearly = 100.00;

-- Apoyo Premium (1000€ + IVA - Pago único)
INSERT INTO membership_plans (
    plan_type, name, slug, description,
    price_monthly, price_yearly,
    features, display_order, status
) VALUES (
    'apoyo_plataforma',
    'Apoyo Premium',
    'apoyo-premium',
    'Contribución premium para el desarrollo y mantenimiento de la plataforma.',
    1000.00, 1000.00,  -- Pago único
    JSON_ARRAY(
        'Contribución premium al desarrollo',
        'Destacado especial en la página principal',
        'Certificado de apoyo empresarial',
        'Newsletter exclusivo',
        'Acceso anticipado a nuevas funcionalidades',
        'Sesión de consultoría personalizada',
        'Logo en la página de partners'
    ),
    7, 'active'
) ON DUPLICATE KEY UPDATE 
    price_monthly = 1000.00,
    price_yearly = 1000.00;

-- 5. ACTUALIZAR TABLA USERS CON CAMPOS ADICIONALES DE FACTURACIÓN
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS billing_name VARCHAR(255) NULL COMMENT 'Nombre para facturación',
ADD COLUMN IF NOT EXISTS billing_nif VARCHAR(50) NULL COMMENT 'NIF/CIF para facturación',
ADD COLUMN IF NOT EXISTS billing_address TEXT NULL COMMENT 'Dirección para facturación',
ADD COLUMN IF NOT EXISTS billing_city VARCHAR(100) NULL COMMENT 'Ciudad para facturación',
ADD COLUMN IF NOT EXISTS billing_postal_code VARCHAR(20) NULL COMMENT 'Código postal para facturación',
ADD COLUMN IF NOT EXISTS billing_country VARCHAR(100) NULL DEFAULT 'España' COMMENT 'País para facturación',
ADD COLUMN IF NOT EXISTS billing_email VARCHAR(255) NULL COMMENT 'Email para facturación',
ADD COLUMN IF NOT EXISTS billing_phone VARCHAR(50) NULL COMMENT 'Teléfono para facturación',
ADD COLUMN IF NOT EXISTS invoice_prefix VARCHAR(10) DEFAULT 'RUT' COMMENT 'Prefijo para números de factura',
ADD COLUMN IF NOT EXISTS next_invoice_number INT DEFAULT 1 COMMENT 'Próximo número de factura';

-- 6. CREAR FUNCIÓN PARA GENERAR NÚMERO DE FACTURA
DELIMITER //
CREATE FUNCTION IF NOT EXISTS generate_invoice_number(user_id INT) RETURNS VARCHAR(50)
DETERMINISTIC
BEGIN
    DECLARE prefix VARCHAR(10);
    DECLARE next_num INT;
    DECLARE year_str VARCHAR(4);
    DECLARE invoice_num VARCHAR(50);
    
    -- Obtener prefijo y próximo número del usuario
    SELECT invoice_prefix, next_invoice_number 
    INTO prefix, next_num 
    FROM users WHERE id = user_id;
    
    -- Si no existe, usar valores por defecto
    IF prefix IS NULL THEN SET prefix = 'RUT'; END IF;
    IF next_num IS NULL THEN SET next_num = 1; END IF;
    
    -- Obtener año actual
    SET year_str = YEAR(CURDATE());
    
    -- Generar número de factura: PREFIJO-AÑO-NÚMERO (ej: RUT-2025-001)
    SET invoice_num = CONCAT(prefix, '-', year_str, '-', LPAD(next_num, 3, '0'));
    
    -- Incrementar próximo número
    UPDATE users SET next_invoice_number = next_num + 1 WHERE id = user_id;
    
    RETURN invoice_num;
END//
DELIMITER ;

-- 7. CREAR VISTA PARA RESUMEN DE MEMBRESÍ