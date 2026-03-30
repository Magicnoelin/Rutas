-- Script para crear la tabla user_preferences con todas las columnas necesarias

DROP TABLE IF EXISTS user_preferences;

CREATE TABLE user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    travel_purpose VARCHAR(50) NULL COMMENT 'Propósito del viaje: relaxation, adventure, culture, family, business',
    accommodation_type VARCHAR(50) NULL COMMENT 'Tipo de alojamiento preferido: rural_house, hotel, apartment, camping',
    budget_range VARCHAR(20) NULL COMMENT 'Rango de presupuesto: 0-100, 100-200, 200-500, 500+',
    group_size VARCHAR(20) NULL COMMENT 'Tamaño del grupo: solo, 2, 2-4, 4-8, 8+',
    preferred_activities JSON NULL COMMENT 'Actividades preferidas como array JSON',
    preferred_locations JSON NULL COMMENT 'Ubicaciones preferidas como array JSON',
    special_requirements TEXT NULL COMMENT 'Requisitos especiales: accesibilidad, mascotas, etc.',
    notification_preferences JSON NULL COMMENT 'Preferencias de notificación: email, sms, push',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Índices y restricciones
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_travel_purpose (travel_purpose),
    INDEX idx_accommodation_type (accommodation_type),
    INDEX idx_budget_range (budget_range),
    INDEX idx_group_size (group_size)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Preferencias de usuario para personalizar recomendaciones de viaje';
