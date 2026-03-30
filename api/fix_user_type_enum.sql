-- Corregir el enum de user_type para que coincida con los valores usados en la aplicación

ALTER TABLE users
MODIFY COLUMN user_type ENUM('turista', 'alojamiento', 'promotor_eventos', 'actividad_cultural') NULL DEFAULT 'turista';

-- Verificar que se cambió correctamente
DESCRIBE users;
