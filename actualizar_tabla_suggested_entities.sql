-- Script para actualizar la tabla suggested_entities y agregar el campo linked_entity_id
-- También agrega un índice para búsquedas más rápidas

-- 1. Verificar si existe el campo linked_entity_id
SELECT COUNT(*) as column_exists 
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
  AND table_name = 'suggested_entities' 
  AND column_name = 'linked_entity_id';

-- 2. Si no existe, agregarlo
ALTER TABLE suggested_entities 
ADD COLUMN linked_entity_id INT DEFAULT NULL 
COMMENT 'ID de la entidad creada a partir de esta sugerencia (places_of_interest.id, accommodations.id, etc.)';

-- 3. Agregar índice para búsquedas más rápidas
ALTER TABLE suggested_entities 
ADD INDEX idx_linked_entity_id (linked_entity_id);

-- 4. Verificar la estructura actualizada
DESCRIBE suggested_entities;

-- 5. Mostrar sugerencias aprobadas sin linked_entity_id (para actualizar manualmente si es necesario)
SELECT 
    id,
    name,
    entity_type,
    municipality,
    province,
    status,
    reviewed_at,
    admin_notes
FROM suggested_entities 
WHERE status = 'approved' 
  AND linked_entity_id IS NULL
ORDER BY reviewed_at DESC;

-- 6. Para actualizar sugerencias existentes que ya tengan entidades creadas:
--    Necesitarías ejecutar manualmente algo como:
--    UPDATE suggested_entities se
--    JOIN places_of_interest p ON p.name LIKE CONCAT('%', se.name, '%') 
--    SET se.linked_entity_id = p.id
--    WHERE se.entity_type = 'places_of_interest' 
--      AND se.status = 'approved' 
--      AND se.linked_entity_id IS NULL;