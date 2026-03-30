-- Agregar campos de membresía a la tabla users
-- Ejecutar este SQL en phpMyAdmin

ALTER TABLE users 
ADD COLUMN IF NOT EXISTS membership_start_date DATETIME NULL COMMENT 'Fecha de inicio de la membresía',
ADD COLUMN IF NOT EXISTS membership_end_date DATETIME NULL COMMENT 'Fecha de fin de la membresía',
ADD COLUMN IF NOT EXISTS stripe_customer_id VARCHAR(255) NULL COMMENT 'ID del cliente en Stripe',
ADD COLUMN IF NOT EXISTS stripe_subscription_id VARCHAR(255) NULL COMMENT 'ID de la suscripción en Stripe';

-- Crear índices para mejorar el rendimiento
CREATE INDEX IF NOT EXISTS idx_membership_dates ON users(membership_end_date);
CREATE INDEX IF NOT EXISTS idx_stripe_customer ON users(stripe_customer_id);
CREATE INDEX IF NOT EXISTS idx_stripe_subscription ON users(stripe_subscription_id);

-- Actualizar usuarios existentes con membresía Free (sin fecha de expiración)
UPDATE users 
SET membership_type = 'free',
    membership_start_date = NOW(),
    membership_end_date = NULL
WHERE membership_type IS NULL OR membership_type = '';

SELECT 'Campos de membresía agregados correctamente' AS resultado;
