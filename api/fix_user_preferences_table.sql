-- Script para verificar y corregir la estructura de la tabla user_preferences

-- Primero, verificar la estructura actual
DESCRIBE user_preferences;

-- Si la tabla tiene las columnas viejas (travel_purpose, accommodation_type, etc.)
-- necesitamos agregar las nuevas columnas o reemplazar la tabla

-- Opción 1: Agregar las columnas faltantes si no existen
ALTER TABLE user_preferences
ADD COLUMN IF NOT EXISTS interests JSON NULL AFTER user_id,
ADD COLUMN IF NOT EXISTS accommodation_types JSON NULL AFTER interests,
ADD COLUMN IF NOT EXISTS budget VARCHAR(20) NULL AFTER accommodation_types,
ADD COLUMN IF NOT EXISTS group_size VARCHAR(20) NULL AFTER budget,
ADD COLUMN IF NOT EXISTS trip_duration VARCHAR(20) NULL AFTER group_size;

-- Verificar que las columnas se agregaron
DESCRIBE user_preferences;

-- Si las columnas viejas existen y no las necesitamos, podemos dropearlas
-- ALTER TABLE user_preferences DROP COLUMN IF EXISTS travel_purpose;
-- ALTER TABLE user_preferences DROP COLUMN IF EXISTS accommodation_type;
-- ALTER TABLE user_preferences DROP COLUMN IF EXISTS budget_range;
-- ALTER TABLE user_preferences DROP COLUMN IF EXISTS preferred_activities;
-- ALTER TABLE user_preferences DROP COLUMN IF EXISTS preferred_locations;
-- ALTER TABLE user_preferences DROP COLUMN IF EXISTS special_requirements;
-- ALTER TABLE user_preferences DROP COLUMN IF EXISTS notification_preferences;
