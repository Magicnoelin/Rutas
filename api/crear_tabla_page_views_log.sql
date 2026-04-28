-- Tabla para registrar cada vista de página con fecha/hora
CREATE TABLE IF NOT EXISTS page_views_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_type VARCHAR(50) NOT NULL COMMENT 'event, accommodation, route, etc.',
    resource_id INT NOT NULL COMMENT 'ID del recurso',
    viewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_viewed_at (viewed_at),
    INDEX idx_resource (resource_type, resource_id),
    INDEX idx_resource_date (resource_type, resource_id, viewed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
