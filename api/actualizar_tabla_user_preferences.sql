-- Script para actualizar la tabla user_preferences existente agregando las columnas faltantes

-- Agregar columnas faltantes si no existen
ALTER TABLE user_preferences
ADD COLUMN IF NOT EXISTS travel_purpose VARCHAR(50) NULL COMMENT 'Propósito del viaje: relaxation, adventure, culture, family, business' AFTER user_id;

ALTER TABLE user_preferences
ADD COLUMN IF NOT EXISTS accommodation_type VARCHAR(50) NULL COMMENT 'Tipo de alojamiento preferido: rural_house, hotel, apartment, camping' AFTER travel_purpose;

ALTER TABLE user_preferences
ADD COLUMN IF NOT EXISTS budget_range VARCHAR(20) NULL COMMENT 'Rango de presupuesto: 0-100, 100-200, 200-500, 500+' AFTER accommodation_type;

ALTER TABLE user_preferences
ADD COLUMN IF NOT EXISTS group_size VARCHAR(20) NULL COMMENT 'Tamaño del grupo: solo, 2, 2-4, 4-8, 8+' AFTER budget_range;

ALTER TABLE user_preferences
ADD COLUMN IF NOT EXISTS preferred_activities JSON NULL COMMENT 'Actividades preferidas como array JSON' AFTER group_size;

ALTER TABLE user_preferences
ADD COLUMN IF NOT EXISTS preferred_locations JSON NULL COMMENT 'Ubicaciones preferidas como array JSON' AFTER preferred_activities;

ALTER TABLE user_preferences
ADD COLUMN IF NOT EXISTS special_requirements TEXT NULL COMMENT 'Requisitos especiales: accesibilidad, mascotas, etc.' AFTER preferred_locations;

ALTER TABLE user_preferences
ADD COLUMN IF NOT EXISTS notification_preferences JSON NULL COMMENT 'Preferencias de notificación: email, sms, push' AFTER special_requirements;

-- Agregar columna updated_at si no existe
ALTER TABLE user_preferences
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER notification_preferences;

-- Agregar índices si no existen
ALTER TABLE user_preferences ADD INDEX IF NOT EXISTS idx_user_id (user_id);
ALTER TABLE user_preferences ADD INDEX IF NOT EXISTS idx_travel_purpose (travel_purpose);
ALTER TABLE user_preferences ADD INDEX IF NOT EXISTS idx_accommodation_type (accommodation_type);
ALTER TABLE user_preferences ADD INDEX IF NOT EXISTS idx_budget_range (budget_range);
ALTER TABLE user_preferences ADD INDEX IF NOT EXISTS idx_group_size (group_size);

-- Agregar foreign key si no existe (esto puede fallar si ya existe, pero es OK)
-- ALTER TABLE user_preferences ADD CONSTRAINT fk_user_preferences_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
