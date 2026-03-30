-- ============================================================
-- Tabla: suggested_entities
-- Para que usuarios sugieran lugares que aún no están en la web
-- ============================================================

CREATE TABLE IF NOT EXISTS suggested_entities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    suggested_by INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    entity_type ENUM('places_of_interest','cultural_events','activities','accommodations') NOT NULL DEFAULT 'places_of_interest',
    description TEXT NULL,
    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL,
    municipality VARCHAR(100) NULL,
    province VARCHAR(100) NULL,
    status ENUM('pending','approved','rejected','merged') NOT NULL DEFAULT 'pending',
    merged_into_id INT NULL COMMENT 'ID de la entidad real si se aprueba y crea',
    admin_notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME NULL,
    INDEX idx_status (status),
    INDEX idx_suggested_by (suggested_by),
    INDEX idx_entity_type (entity_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Añadir columna file_url a entity_photos si no existe
-- (La tabla ya existe, solo añadimos lo que falta)
ALTER TABLE entity_photos 
    ADD COLUMN IF NOT EXISTS file_url VARCHAR(500) NULL AFTER file_path,
    ADD COLUMN IF NOT EXISTS suggested_entity_id INT NULL COMMENT 'Si la foto es de una entidad sugerida' AFTER entity_id;

-- Índice para suggested_entity_id
ALTER TABLE entity_photos 
    ADD INDEX IF NOT EXISTS idx_suggested (suggested_entity_id);
