-- ============================================================
-- SCRIPT: Agregar campo is_premium a accommodations
-- ============================================================
-- EJECUTAR UNA SOLA VEZ en phpMyAdmin o línea de comandos
-- ============================================================

ALTER TABLE accommodations 
ADD COLUMN is_premium TINYINT(1) NOT NULL DEFAULT 0 
COMMENT '1 = Premium (con derecho a traducciones multiidioma), 0 = Gratuito (solo español)';

-- Actualizar alojamientos cuyos propietarios tienen membresía premium/enterprise
UPDATE accommodations a
JOIN user_resources ur ON ur.resource_id = a.id AND ur.resource_type = 'accommodation' AND ur.role = 'owner'
JOIN users u ON u.id = ur.user_id
SET a.is_premium = 1
WHERE u.membership_type IN ('premium', 'enterprise');

-- Verificar resultado
SELECT 
    COUNT(*) as total_alojamientos,
    SUM(CASE WHEN is_premium = 1 THEN 1 ELSE 0 END) as premium,
    SUM(CASE WHEN is_premium = 0 THEN 1 ELSE 0 END) as gratuitos
FROM accommodations;
