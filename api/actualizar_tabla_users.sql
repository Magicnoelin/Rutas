-- Script para actualizar la tabla users existente con las nuevas columnas

-- Agregar columna user_type si no existe
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS user_type ENUM('turista', 'alojamiento', 'promotor_eventos', 'actividad_cultural') NOT NULL DEFAULT 'turista' AFTER id;

-- Agregar columna business_name si no existe
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS business_name VARCHAR(255) NULL AFTER user_type;

-- Agregar columna business_description si no existe
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS business_description TEXT NULL AFTER business_name;

-- Agregar columna verification_status si no existe
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending' AFTER business_description;

-- Agregar columna subscription_level si no existe
ALTER TABLE users
ADD COLUMN IF NOT EXISTS subscription_level ENUM('basic', 'premium') DEFAULT 'basic' AFTER verification_status;

-- Agregar columna terms_accepted si no existe (esta es la que falta)
ALTER TABLE users
ADD COLUMN IF NOT EXISTS terms_accepted TINYINT(1) DEFAULT 1 AFTER verification_token;

-- Agregar índices si no existen
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_user_type (user_type);
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_verification_status (verification_status);

-- Actualizar usuarios existentes para que sean turistas verificados
UPDATE users SET user_type = 'turista', verification_status = 'verified' WHERE user_type IS NULL OR user_type = '';
