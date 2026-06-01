-- ============================================================
-- ACTUALIZACIÓN DE BILLING CONCEPTS Y MEMBERSHIP PLANS
-- Estrategia de Pricing: Oferta Lanzamiento 50% en Premium
-- ============================================================
-- Ejecutar en phpMyAdmin del servidor de producción
-- ============================================================

-- ============================================================
-- 1. AÑADIR CAMPO official_price_yearly a membership_plans
-- ============================================================
ALTER TABLE membership_plans 
ADD COLUMN IF NOT EXISTS official_price_yearly DECIMAL(10,2) NULL COMMENT 'Precio oficial anual (antes de descuento)',
ADD COLUMN IF NOT EXISTS max_photos INT DEFAULT NULL COMMENT 'Máximo de fotos (NULL = ilimitado)',
ADD COLUMN IF NOT EXISTS has_direct_link BOOLEAN DEFAULT FALSE COMMENT 'Enlace directo a web/motor de reservas',
ADD COLUMN IF NOT EXISTS has_api BOOLEAN DEFAULT FALSE COMMENT 'Acceso a API',
ADD COLUMN IF NOT EXISTS has_personalized_consulting BOOLEAN DEFAULT FALSE COMMENT 'Asesoramiento personalizado',
ADD COLUMN IF NOT EXISTS has_reports BOOLEAN DEFAULT FALSE COMMENT 'Informes personalizados',
ADD COLUMN IF NOT EXISTS has_basic_stats BOOLEAN DEFAULT FALSE COMMENT 'Estadísticas básicas',
ADD COLUMN IF NOT EXISTS has_advanced_stats BOOLEAN DEFAULT FALSE COMMENT 'Estadísticas avanzadas',
ADD COLUMN IF NOT EXISTS can_receive_messages BOOLEAN DEFAULT TRUE COMMENT 'Recibir mensajes de turistas',
ADD COLUMN IF NOT EXISTS can_send_messages BOOLEAN DEFAULT FALSE COMMENT 'Enviar mensajes ilimitados',
ADD COLUMN IF NOT EXISTS has_priority_position BOOLEAN DEFAULT FALSE COMMENT 'Posicionamiento destacado',
ADD COLUMN IF NOT EXISTS is_launch_offer BOOLEAN DEFAULT FALSE COMMENT 'Es oferta de lanzamiento',
ADD COLUMN IF NOT EXISTS launch_discount_percent INT DEFAULT NULL COMMENT 'Porcentaje de descuento de lanzamiento',
ADD COLUMN IF NOT EXISTS multipropiedad_note TEXT DEFAULT NULL COMMENT 'Nota sobre Pack Multipropiedad';

-- ============================================================
-- 2. ACTUALIZAR BILLING CONCEPTS (Catálogo de productos)
-- ============================================================

-- Limpiar conceptos antiguos de Premium (usando concept_name como identificador)
DELETE FROM billing_concepts WHERE concept_name IN ('Premium Mensual (Oferta)', 'Premium Anual (Oferta 50%)', 'Premium Mensual', 'Premium Anual');

-- Insertar nuevos conceptos de facturación
INSERT INTO billing_concepts (concept_name, description, amount, billing_type, active) VALUES

-- PLAN PREMIUM (Oferta Lanzamiento 50%)
('Premium Mensual (Oferta)', 'Plan Premium - Pago mensual. Incluye 1 alojamiento completo con fotos ilimitadas, enlace directo a tu web y posicionamiento destacado.', 19.99, 'monthly', TRUE),

('Premium Anual (Oferta 50%)', 'Plan Premium - Pago anual. Precio oficial 240,00€. Oferta de lanzamiento al 50%: solo 120,00€/año. Incluye 1 alojamiento completo.', 120.00, 'yearly', TRUE),

-- PLAN BUSINESS
('Business Mensual', 'Plan Business - Pago mensual. Hasta 10 alojamientos, todas las funciones Premium, API, informes y asesoramiento.', 49.99, 'monthly', TRUE),

('Business Anual', 'Plan Business - Pago anual. Hasta 10 alojamientos, todas las funciones Premium, API, informes y asesoramiento.', 499.99, 'yearly', TRUE),

-- PLAN CAFÉ (Apoyo a la comunidad)
('Café Mensual', 'Invítame a un café cada mes. Apoyo al mantenimiento técnico de la plataforma.', 1.50, 'monthly', TRUE),

('Café Anual', 'Invítame a un café cada año. Apoyo al mantenimiento técnico de la plataforma.', 10.00, 'yearly', TRUE)

ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    amount = VALUES(amount),
    billing_type = VALUES(billing_type),
    active = VALUES(active);

-- ============================================================
-- 3. ACTUALIZAR MEMBERSHIP PLANS
-- ============================================================

-- PLAN FREE (ID 1)
UPDATE membership_plans SET
    name = 'Free',
    description = 'Plan básico para probar la plataforma. Publica 1 alojamiento con máximo 4 fotos y descripción básica.',
    price_monthly = 0.00,
    price_yearly = 0.00,
    official_price_yearly = NULL,
    features = '["Publicar 1 alojamiento", "Máximo 4 fotos por alojamiento", "Descripción básica", "Ver mensajes de turistas", "Estadísticas básicas", "Sin coste"]',
    max_accommodations = 1,
    max_photos = 4,
    can_send_offers = FALSE,
    has_advanced_stats = FALSE,
    has_basic_stats = TRUE,
    has_priority_support = FALSE,
    has_direct_link = FALSE,
    has_api = FALSE,
    has_personalized_consulting = FALSE,
    has_reports = FALSE,
    can_receive_messages = TRUE,
    can_send_messages = FALSE,
    has_priority_position = FALSE,
    is_popular = FALSE,
    is_launch_offer = FALSE,
    launch_discount_percent = NULL,
    multipropiedad_note = NULL,
    is_active = TRUE
WHERE id = 1;

-- PLAN PREMIUM (ID 2) - OFERTA LANZAMIENTO 50%
UPDATE membership_plans SET
    name = 'Premium',
    description = 'Plan profesional para alojamientos que quieren destacar. Oferta de lanzamiento con 50% de descuento.',
    price_monthly = 19.99,
    price_yearly = 120.00,
    official_price_yearly = 240.00,
    features = '["Fotos ilimitadas", "Descripción completa", "ENLACE DIRECTO a tu web o motor de reservas (0% comisiones)", "Posicionamiento destacado en búsquedas", "Soporte prioritario", "Mensajes ilimitados con turistas", "Estadísticas avanzadas"]',
    max_accommodations = 1,
    max_photos = NULL,
    can_send_offers = TRUE,
    has_advanced_stats = TRUE,
    has_basic_stats = TRUE,
    has_priority_support = TRUE,
    has_direct_link = TRUE,
    has_api = FALSE,
    has_personalized_consulting = FALSE,
    has_reports = FALSE,
    can_receive_messages = TRUE,
    can_send_messages = TRUE,
    has_priority_position = TRUE,
    is_popular = TRUE,
    is_launch_offer = TRUE,
    launch_discount_percent = 50,
    multipropiedad_note = '¿Tienes más de un alojamiento? Consúltanos por nuestro Pack Multipropiedad',
    is_active = TRUE
WHERE id = 2;

-- PLAN BUSINESS (ID 3)
UPDATE membership_plans SET
    name = 'Business',
    description = 'Plan empresarial para agencias, complejos grandes o gestión avanzada.',
    price_monthly = 49.99,
    price_yearly = 499.99,
    official_price_yearly = NULL,
    features = '["Hasta 10 alojamientos", "Todas las funciones Premium", "API para integración con tu web", "Informes personalizados", "Asesoramiento personalizado", "Fotos ilimitadas", "Enlace directo a tu web (0% comisiones)", "Posicionamiento destacado", "Soporte prioritario 24/7"]',
    max_accommodations = 10,
    max_photos = NULL,
    can_send_offers = TRUE,
    has_advanced_stats = TRUE,
    has_basic_stats = TRUE,
    has_priority_support = TRUE,
    has_direct_link = TRUE,
    has_api = TRUE,
    has_personalized_consulting = TRUE,
    has_reports = TRUE,
    can_receive_messages = TRUE,
    can_send_messages = TRUE,
    has_priority_position = TRUE,
    is_popular = FALSE,
    is_launch_offer = FALSE,
    launch_discount_percent = NULL,
    multipropiedad_note = NULL,
    is_active = TRUE
WHERE id = 3;

-- ============================================================
-- 4. VERIFICAR RESULTADOS
-- ============================================================
SELECT '=== BILLING CONCEPTS ===' AS '';
SELECT id, concept_name, description, amount, billing_type, active FROM billing_concepts ORDER BY billing_type, amount;

SELECT '=== MEMBERSHIP PLANS ===' AS '';
SELECT id, name, price_monthly, price_yearly, official_price_yearly, max_accommodations, max_photos, is_popular, is_launch_offer, launch_discount_percent FROM membership_plans ORDER BY id;

SELECT '✅ Actualización completada correctamente' AS resultado;
