-- Script para inicializar estadísticas de eventos en resource_stats
-- Ejecutar una sola vez para crear registros de estadísticas para todos los eventos existentes

-- Primero verificar si la tabla resource_stats existe
-- Si no existe, la API la creará automáticamente cuando se llame

-- Insertar registros de estadísticas para todos los eventos activos que no tengan uno
INSERT IGNORE INTO resource_stats (resource_type, resource_id, views_count, favorites_count, interests_count, messages_count, created_at, updated_at)
SELECT 
    'event' AS resource_type,
    e.id AS resource_id,
    COALESCE(e.views, 0) AS views_count,
    0 AS favorites_count,
    0 AS interests_count,
    0 AS messages_count,
    NOW() AS created_at,
    NOW() AS updated_at
FROM cultural_events e
WHERE e.is_active = 1
AND NOT EXISTS (
    SELECT 1 FROM resource_stats rs 
    WHERE rs.resource_type = 'event' AND rs.resource_id = e.id
);

-- Verificar cuántos eventos tienen estadísticas
SELECT 
    (SELECT COUNT(*) FROM cultural_events WHERE is_active = 1) AS total_eventos,
    (SELECT COUNT(*) FROM resource_stats WHERE resource_type = 'event') AS eventos_con_stats;
