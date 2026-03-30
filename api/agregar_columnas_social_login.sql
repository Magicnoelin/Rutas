-- Agregar columnas para Social Login (Google/Facebook)
-- Este script añade las columnas necesarias para almacenar los IDs de proveedores sociales
-- IMPORTANTE: Si alguna columna ya existe, el script mostrará un warning pero将继续 ejecutándose

-- Agregar columnas individuales (IGNORE para evitar errores si ya existen)
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS google_id VARCHAR(255) NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS facebook_id VARCHAR(255) NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS avatar_url VARCHAR(500) NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS auth_provider VARCHAR(20) NULL DEFAULT NULL COMMENT 'google, facebook, o NULL para login tradicional',
ADD COLUMN IF NOT EXISTS created_social DATETIME NULL DEFAULT NULL;

-- Crear índices para búsquedas rápidas (IGNORE para evitar errores si ya existen)
ALTER TABLE users 
ADD INDEX IF NOT EXISTS idx_google_id (google_id),
ADD INDEX IF NOT EXISTS idx_facebook_id (facebook_id),
ADD INDEX IF NOT EXISTS idx_auth_provider (auth_provider);

-- Si ADD COLUMN IF NOT EXISTS no funciona en tu versión de MySQL, usa este enfoque alternativo:

-- ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL;
-- ALTER TABLE users ADD COLUMN facebook_id VARCHAR(255) NULL;
-- ALTER TABLE users ADD COLUMN avatar_url VARCHAR(500) NULL;
-- ALTER TABLE users ADD COLUMN auth_provider VARCHAR(20) NULL;
-- ALTER TABLE users ADD COLUMN created_social DATETIME NULL;

-- Para verificar las columnas agregadas:
-- SHOW COLUMNS FROM users LIKE 'google_id';
-- SHOW COLUMNS FROM users LIKE 'facebook_id';
-- SHOW COLUMNS FROM users LIKE 'avatar_url';
-- SHOW COLUMNS FROM users LIKE 'auth_provider';
-- SHOW COLUMNS FROM users LIKE 'created_social';
