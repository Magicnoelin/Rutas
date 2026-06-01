-- ============================================================
-- Tabla de historial de actividad de fotos de alojamientos
-- Registra subidas y eliminaciones de fotos hechas por usuarios
-- ============================================================

CREATE TABLE IF NOT EXISTS accommodation_photo_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    accommodation_id INT NOT NULL,
    accommodation_name VARCHAR(255) DEFAULT NULL,
    accommodation_slug VARCHAR(255) DEFAULT NULL,
    user_id INT DEFAULT NULL,
    user_name VARCHAR(255) DEFAULT NULL,
    action_type VARCHAR(50) NOT NULL COMMENT 'upload / delete',
    category VARCHAR(100) DEFAULT NULL,
    filename VARCHAR(255) DEFAULT NULL,
    file_url VARCHAR(500) DEFAULT NULL,
    details TEXT DEFAULT NULL COMMENT 'Información adicional (ej: motivo, tamaño, etc.)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_accommodation (accommodation_id),
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
