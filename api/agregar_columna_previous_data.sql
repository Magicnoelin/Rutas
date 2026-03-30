-- =====================================================
-- AGREGAR COLUMNA previous_data PARA DIFF VISUAL
-- =====================================================
-- Este script agrega solo la columna necesaria para mostrar
-- los cambios en rojo/verde en el panel de moderación

USE u412199647_Rutas;

-- Agregar columna previous_data si no existe
ALTER TABLE accommodation_pending_changes 
ADD COLUMN IF NOT EXISTS previous_data JSON AFTER pending_data;

-- Verificar que se agregó
SELECT 'Columna previous_data agregada correctamente. Ahora verás los cambios en rojo/verde!' as resultado;

-- Ver estructura actualizada
DESCRIBE accommodation_pending_changes;
