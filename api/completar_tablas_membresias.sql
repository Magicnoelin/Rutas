-- ============================================
-- COMPLETAR SISTEMA DE MEMBRESÍAS
-- Rutas Rurales - Sistema de Facturación
-- ============================================
-- NOTA: Este script SOLO agrega lo que falta.
-- Las tablas ya existen en la BD.
-- ============================================

-- 1. AGREGAR CAMPOS DE MEMBRESÍA A TABLA users (si no existen)
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS membership_type VARCHAR(50) NULL COMMENT 'Tipo de membresía (free, basic, premium, etc.)',
ADD COLUMN IF NOT EXISTS membership_status ENUM('active', 'expired', 'canceled', 'pending') DEFAULT 'pending' COMMENT 'Estado de la membresía',
ADD COLUMN IF NOT EXISTS membership_start_date DATE NULL COMMENT 'Fecha de inicio de la membresía',
ADD COLUMN IF NOT EXISTS membership_end_date DATE NULL COMMENT 'Fecha de fin de la membresía';

-- 2. AGREGAR CAMPOS FALTANTES A TABLA user_subscriptions (si no existen)
ALTER TABLE user_subscriptions 
ADD COLUMN IF NOT EXISTS plan_name VARCHAR(100) NULL COMMENT 'Nombre del plan al momento de la suscripción',
ADD COLUMN IF NOT EXISTS billing_cycle ENUM('monthly', 'yearly') NULL COMMENT 'Ciclo de facturación',
ADD COLUMN IF NOT EXISTS price DECIMAL(10,2) NULL COMMENT 'Precio sin IVA',
ADD COLUMN IF NOT EXISTS total_amount DECIMAL(10,2) NULL COMMENT 'Precio total con IVA',
ADD COLUMN IF NOT EXISTS status ENUM('active', 'pending', 'canceled', 'expired', 'past_due') DEFAULT 'pending' COMMENT 'Estado de la suscripción',
ADD COLUMN IF NOT EXISTS end_date DATE NULL COMMENT 'Fecha de finalización';

-- 3. AGREGAR CAMPOS FALTANTES A TABLA invoices (si no existen)
ALTER TABLE invoices 
ADD COLUMN IF NOT EXISTS user_id INT NULL COMMENT 'ID del usuario',
ADD COLUMN IF NOT EXISTS payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending' COMMENT 'Estado de pago',
ADD COLUMN IF NOT EXISTS total_amount DECIMAL(10,2) NULL COMMENT 'Total con IVA';

-- 4. RECREAR VISTA membership_summary
-- Adaptada a la estructura real de invoices:
--   - tax_amount en lugar de vat_amount
--   - total como columna principal (total_amount agregada después)
--   - status enum('draft','issued','paid','cancelled') como columna principal
--   - payment_status agregada después
--   - notes en lugar de description
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

-- 5. RECREAR VISTA billing_reports
-- Adaptada a la estructura real de invoices
CREATE OR REPLACE VIEW billing_reports AS
SELECT 
    DATE_FORMAT(i.invoice_date, '%Y-%m') as month,
    COUNT(*) as total_invoices,
    SUM(i.subtotal) as total_subtotal,
    SUM(i.tax_amount) as total_vat,
    SUM(COALESCE(i.total_amount, i.total)) as total_revenue,
    
    -- Métricas de pago
    SUM(CASE WHEN i.status = 'paid' OR i.payment_status = 'paid' THEN 1 ELSE 0 END) as paid_invoices,
    SUM(CASE WHEN i.status = 'issued' OR i.payment_status = 'pending' THEN 1 ELSE 0 END) as pending_invoices,
    SUM(CASE WHEN i.status = 'cancelled' OR i.payment_status = 'failed' OR i.payment_status = 'refunded' THEN 1 ELSE 0 END) as failed_invoices
    
FROM invoices i
WHERE i.invoice_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
GROUP BY DATE_FORMAT(i.invoice_date, '%Y-%m')
ORDER BY month DESC;

-- ============================================
-- MENSAJE DE ÉXITO
-- ============================================
SELECT '✅ Sistema de membresías completado correctamente' as message;
