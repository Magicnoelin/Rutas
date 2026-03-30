-- Script para vincular alojamientos del usuario 94 (casaenrique@gmail.com)
-- Ejecutar este script en la base de datos

-- 1. Buscar alojamientos por email
SELECT id, name, email, manager_nickname 
FROM accommodations 
WHERE email = 'casaenrique@gmail.com';

-- 2. Actualizar created_by para esos alojamientos
UPDATE accommodations 
SET created_by = 94 
WHERE email = 'casaenrique@gmail.com';

-- 3. Vincular en user_resources
INSERT INTO user_resources (user_id, resource_type, resource_id, role, status)
SELECT 94, 'accommodation', id, 'owner', 'active'
FROM accommodations
WHERE email = 'casaenrique@gmail.com'
AND id NOT IN (SELECT resource_id FROM user_resources WHERE user_id = 94 AND resource_type = 'accommodation');

-- 4. Crear estadísticas
INSERT IGNORE INTO resource_stats (resource_type, resource_id, views_count, interests_count, messages_count, favorites_count)
SELECT 'accommodation', id, 0, 0, 0, 0
FROM accommodations
WHERE email = 'casaenrique@gmail.com';

-- 5. Verificar resultado
SELECT 
    a.id,
    a.name,
    a.created_by,
    ur.id as vinculacion_id,
    ur.status
FROM accommodations a
LEFT JOIN user_resources ur ON ur.resource_id = a.id AND ur.resource_type = 'accommodation'
WHERE a.email = 'casaenrique@gmail.com';
