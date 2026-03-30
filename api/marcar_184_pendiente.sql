-- =====================================================
-- MARCAR ALOJAMIENTO 184 COMO PENDIENTE DE MODERACIÓN
-- =====================================================
-- Este script marca el alojamiento de prueba como pendiente
-- para que aparezca en el panel de moderación

USE u412199647_Rutas;

-- Marcar como pendiente
UPDATE accommodations 
SET moderation_status = 'pending', 
    last_submitted_at = NOW() 
WHERE id = 184;

-- Verificar el cambio
SELECT id, name, moderation_status, last_submitted_at 
FROM accommodations 
WHERE id = 184;

-- Mensaje de confirmación
SELECT 'Alojamiento 184 marcado como PENDIENTE correctamente' as resultado;
