-- ============================================
-- SISTEMA DE FACTURACIÓN Y PAGOS
-- RutasRurales.io
-- ============================================

-- 1. Tabla de Billing Concepts (Catálogo de Productos/Servicios)
CREATE TABLE IF NOT EXISTS billing_concepts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    concept_code VARCHAR(50) UNIQUE NOT NULL,
    concept_name VARCHAR(255) NOT NULL,
    description TEXT,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'EUR',
    billing_type ENUM('monthly', 'yearly', 'one_time') DEFAULT 'monthly',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_concept_code (concept_code),
    INDEX idx_billing_type (billing_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla de Billing Profiles (Datos Fiscales de Clientes)
CREATE TABLE IF NOT EXISTS billing_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    legal_name VARCHAR(255) NOT NULL,
    tax_id VARCHAR(50),
    address TEXT,
    city VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'España',
    phone VARCHAR(50),
    email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_tax_id (tax_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla de Subscriptions (Suscripciones Activas)
CREATE TABLE IF NOT EXISTS subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    billing_profile_id INT NOT NULL,
    billing_concept_id INT NOT NULL,
    status ENUM('active', 'paused', 'cancelled', 'expired') DEFAULT 'active',
    start_date DATE NOT NULL,
    end_date DATE,
    next_billing_date DATE,
    stripe_subscription_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (billing_profile_id) REFERENCES billing_profiles(id),
    FOREIGN KEY (billing_concept_id) REFERENCES billing_concepts(id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_next_billing_date (next_billing_date),
    INDEX idx_stripe_subscription_id (stripe_subscription_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabla de Invoices (Facturas)
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_tax_id VARCHAR(50),
    customer_email VARCHAR(255),
    invoice_date DATE NOT NULL,
    due_date DATE,
    subtotal DECIMAL(10,2) NOT NULL,
    tax_rate DECIMAL(5,2) DEFAULT 21.00,
    tax_amount DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('draft', 'sent', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
    pdf_path VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_customer_email (customer_email),
    INDEX idx_status (status),
    INDEX idx_invoice_date (invoice_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabla de Invoice Items (Líneas de Factura)
CREATE TABLE IF NOT EXISTS invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    concept_code VARCHAR(50),
    concept_name VARCHAR(255) NOT NULL,
    description TEXT,
    quantity INT DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    line_total DECIMAL(10,2) NOT NULL,
    billing_type VARCHAR(50),
    subscription_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL,
    INDEX idx_invoice_id (invoice_id),
    INDEX idx_subscription_id (subscription_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabla de Payments (Pagos Recibidos)
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'EUR',
    payment_method ENUM('stripe', 'paypal', 'bank_transfer', 'other') DEFAULT 'stripe',
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    stripe_payment_intent_id VARCHAR(255),
    stripe_charge_id VARCHAR(255),
    transaction_id VARCHAR(255),
    payment_date TIMESTAMP,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_stripe_payment_intent_id (stripe_payment_intent_id),
    INDEX idx_transaction_id (transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tabla de Membership Plans (Planes de Membresía)
CREATE TABLE IF NOT EXISTS membership_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price_monthly DECIMAL(10,2) NOT NULL DEFAULT 0,
    price_yearly DECIMAL(10,2) NOT NULL DEFAULT 0,
    features JSON,
    max_accommodations INT DEFAULT 1,
    can_send_offers BOOLEAN DEFAULT FALSE,
    has_advanced_stats BOOLEAN DEFAULT FALSE,
    has_priority_support BOOLEAN DEFAULT FALSE,
    is_popular BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    stripe_product_id VARCHAR(255),
    stripe_monthly_price_id VARCHAR(255),
    stripe_yearly_price_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Tabla de User Subscriptions (Suscripciones de Usuario)
CREATE TABLE IF NOT EXISTS user_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    plan_name VARCHAR(100) NOT NULL,
    billing_cycle ENUM('monthly', 'yearly') NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'EUR',
    status ENUM('active', 'cancelled', 'expired', 'pending') DEFAULT 'pending',
    stripe_subscription_id VARCHAR(255),
    stripe_customer_id VARCHAR(255),
    current_period_start TIMESTAMP,
    current_period_end TIMESTAMP,
    cancel_at_period_end BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    valid_until TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES membership_plans(id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_stripe_subscription_id (stripe_subscription_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Tabla de Membership Upgrade Intents (Intenciones de Upgrade)
CREATE TABLE IF NOT EXISTS membership_upgrade_intents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    plan_name VARCHAR(100) NOT NULL,
    billing_cycle ENUM('monthly', 'yearly') NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'EUR',
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    stripe_session_id VARCHAR(255),
    payment_intent_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES membership_plans(id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_stripe_session_id (stripe_session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERTAR DATOS INICIALES
-- ============================================

-- Insertar Billing Concepts para Premium
INSERT INTO billing_concepts (id, concept_code, concept_name, description, amount, billing_type) VALUES
(12, 'PREMIUM_MONTHLY', 'Membresía Premium Mensual', 'Plan profesional para maximizar tu visibilidad - Pago mensual', 9.99, 'monthly'),
(15, 'PREMIUM_YEARLY', 'Membresía Premium Anual', 'Plan profesional para maximizar tu visibilidad - Pago anual (2 meses gratis)', 99.99, 'yearly')
ON DUPLICATE KEY UPDATE
    concept_name = VALUES(concept_name),
    description = VALUES(description),
    amount = VALUES(amount),
    billing_type = VALUES(billing_type);

-- Insertar Planes de Membresía
INSERT INTO membership_plans (id, name, description, price_monthly, price_yearly, features, max_accommodations, can_send_offers, has_advanced_stats, has_priority_support, is_popular, is_active) VALUES
(1, 'Free', 'Plan básico gratuito para empezar', 0.00, 0.00, 
 '["Acceso básico a la plataforma", "Publicar hasta 1 alojamiento", "Responder a mensajes de turistas", "Acceso a estadísticas básicas"]',
 1, FALSE, FALSE, FALSE, FALSE, TRUE),
 
(2, 'Premium', 'Plan profesional para maximizar tu visibilidad', 9.99, 99.99,
 '["Publicar hasta 2 alojamientos", "Enviar ofertas a turistas", "Mensajes ilimitados", "Estadísticas avanzadas", "Posicionamiento destacado", "Soporte prioritario", "Acceso a promociones especiales"]',
 2, TRUE, TRUE, TRUE, TRUE, TRUE),
 
(3, 'Business', 'Plan empresarial para gestión avanzada', 49.99, 499.99,
 '["Todas las funciones Premium", "Gestión de múltiples propiedades", "API para integración con tu web", "Informes personalizados", "Asesoramiento personalizado", "Acceso a eventos exclusivos"]',
 999, TRUE, TRUE, TRUE, FALSE, TRUE)
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    price_monthly = VALUES(price_monthly),
    price_yearly = VALUES(price_yearly),
    features = VALUES(features),
    max_accommodations = VALUES(max_accommodations),
    can_send_offers = VALUES(can_send_offers),
    has_advanced_stats = VALUES(has_advanced_stats),
    has_priority_support = VALUES(has_priority_support),
    is_popular = VALUES(is_popular);

-- Verificar que las columnas necesarias existen en la tabla users
-- (Ejecutar manualmente si es necesario)
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS membership_type VARCHAR(50) DEFAULT 'Free';
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS membership_status VARCHAR(50) DEFAULT 'active';
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS membership_updated_at TIMESTAMP NULL;

SELECT 'Sistema de facturación creado exitosamente' AS resultado;
