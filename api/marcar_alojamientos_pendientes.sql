-- Script para marcar alojamientos existentes como pendientes de moderación
-- Esto permitirá que aparezcan en el panel de moderación

-- 1. Verificar el estado actual de los alojamientos
SELECT 
    id, 
    name, 
    moderation_status, 
    has_pending_changes, 
    is_active,
    created_at,
    last_submitted_at
FROM accommodations
WHERE moderation_status IS NULL OR moderation_status = 'draft'
ORDER BY created_at DESC;

-- 2. Marcar todos los alojamientos que están en 'draft' o NULL como 'pending'
UPDATE accommodations 
SET 
    moderation_status = 'pending',
    last_submitted_at = COALESCE(last_submitted_at, NOW()),
    has_pending_changes = 1
WHERE moderation_status IS NULL OR moderation_status = 'draft';

-- 3. Verificar que se actualizaron correctamente
SELECT 
    COUNT(*) as total_alojamientos,
    SUM(CASE WHEN moderation_status = 'pending' THEN 1 ELSE 0 END) as pendientes,
    SUM(CASE WHEN moderation_status = 'approved' THEN 1 ELSE 0 END) as aprobados,
    SUM(CASE WHEN moderation_status = 'rejected' THEN 1 ELSE 0 END) as rechazados,
    SUM(CASE WHEN moderation_status = 'draft' THEN 1 ELSE 0 END) as borradores
FROM accommodations;

-- 4. Mostrar alojamientos que deberían aparecer en moderación
SELECT 
    a.id,
    a.name,
    a.municipality,
    a.province,
    a.moderation_status,
    a.has_pending_changes,
    u.email as user_email,
    DATEDIFF(NOW(), COALESCE(a.last_submitted_at, a.created_at)) as dias_pendientes
FROM accommodations a
LEFT JOIN users u ON a.created_by = u.id
WHERE a.moderation_status = 'pending' OR a.has_pending_changes = 1
ORDER BY a.last_submitted_at ASC;
