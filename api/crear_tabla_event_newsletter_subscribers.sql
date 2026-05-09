-- Script para crear la tabla event_newsletter_subscribers
-- Almacena suscripciones a newsletters de eventos similares

CREATE TABLE IF NOT EXISTS event_newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL COMMENT 'Email del suscriptor',
    categoria VARCHAR(100) NULL COMMENT 'Categoría del evento que motivó la suscripción',
    province VARCHAR(100) NULL COMMENT 'Provincia del evento',
    source_slug VARCHAR(255) NULL COMMENT 'Slug del evento donde hicieron clic en "Suscribirme"',
    source_event_id INT NULL COMMENT 'ID del evento donde hicieron clic en "Suscribirme"',
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1 COMMENT '1 = activo, 0 = dado de baja',
    unsubscribed_at TIMESTAMP NULL COMMENT 'Fecha de baja si la hubo',

    -- Índices
    INDEX idx_email (email),
    INDEX idx_categoria (categoria),
    INDEX idx_province (province),
    INDEX idx_source_slug (source_slug),
    INDEX idx_is_active (is_active),
    INDEX idx_subscribed_at (subscribed_at),

    -- Unique: un email no puede suscribirse dos veces al mismo evento
    UNIQUE KEY uk_email_event (email, source_slug)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Suscriptores a newsletters de eventos similares';
