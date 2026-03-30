-- =====================================================
-- Tabla de comentarios para eventos culturales
-- Ejecutar en la base de datos u412199647_Rutas
-- =====================================================

CREATE TABLE IF NOT EXISTS event_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    parent_id INT DEFAULT NULL COMMENT 'Para respuestas a comentarios',
    author_name VARCHAR(100) NOT NULL,
    author_email VARCHAR(255) DEFAULT NULL,
    author_avatar VARCHAR(500) DEFAULT NULL,
    comment_text TEXT NOT NULL,
    rating TINYINT DEFAULT NULL COMMENT 'Valoración 1-5 estrellas (opcional)',
    is_approved TINYINT(1) DEFAULT 1 COMMENT '1=aprobado, 0=pendiente moderación',
    is_deleted TINYINT(1) DEFAULT 0,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_event_id (event_id),
    INDEX idx_parent_id (parent_id),
    INDEX idx_approved (is_approved),
    INDEX idx_created (created_at),
    
    FOREIGN KEY (parent_id) REFERENCES event_comments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
