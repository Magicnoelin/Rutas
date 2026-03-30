-- ============================================
-- TABLA DE VINCULACIÓN USUARIO-RECURSO
-- Sistema de Roles Múltiples para Rutas
-- ============================================

-- Tabla principal: vincula usuarios con recursos
CREATE TABLE IF NOT EXISTS user_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    resource_type ENUM('accommodation', 'place', 'activity', 'event') NOT NULL,
    resource_id INT NOT NULL,
    role ENUM('owner', 'manager', 'collaborator') DEFAULT 'owner',
    status ENUM('pending', 'active', 'suspended') DEFAULT 'pending',
    permissions JSON COMMENT 'Permisos específicos: {"can_edit": true, "can_delete": false, "can_manage_offers": true}',
    notes TEXT COMMENT 'Notas internas sobre la vinculación',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    validated_at TIMESTAMP NULL COMMENT 'Fecha de validación del recurso',
    validated_by INT NULL COMMENT 'ID del admin que validó',
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_resource (user_id, resource_type, resource_id),
    INDEX idx_user (user_id),
    INDEX idx_resource (resource_type, resource_id),
    INDEX idx_status (status),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Vinculación de usuarios con recursos gestionables';

-- ============================================
-- MODIFICACIONES A LA TABLA USERS
-- ============================================

-- Añadir campos de membresía si no existen
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS membership_status ENUM('pending', 'validated', 'blocked') DEFAULT 'pending' 
    COMMENT 'Estado de validación del usuario',
ADD COLUMN IF NOT EXISTS membership_type ENUM('free', 'premium', 'enterprise') DEFAULT 'free'
    COMMENT 'Tipo de membresía actual',
ADD COLUMN IF NOT EXISTS validated_at TIMESTAMP NULL
    COMMENT 'Fecha de validación de la cuenta',
ADD COLUMN IF NOT EXISTS validated_by INT NULL
    COMMENT 'ID del admin que validó la cuenta';

-- Añadir índices para optimización
ALTER TABLE users
ADD INDEX IF NOT EXISTS idx_membership_status (membership_status),
ADD INDEX IF NOT EXISTS idx_membership_type (membership_type);

-- ============================================
-- TABLA DE OFERTAS POR RECURSO
-- ============================================

CREATE TABLE IF NOT EXISTS resource_offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'Gestor que crea la oferta',
    resource_type ENUM('accommodation', 'place', 'activity', 'event') NOT NULL,
    resource_id INT NOT NULL,
    
    -- Datos de la oferta
    title VARCHAR(255) NOT NULL,
    description TEXT,
    offer_type ENUM('discount', 'package', 'special', 'seasonal') DEFAULT 'discount',
    
    -- Precios
    original_price DECIMAL(10, 2),
    offer_price DECIMAL(10, 2) NOT NULL,
    discount_percentage INT COMMENT 'Calculado automáticamente',
    
    -- Validez
    valid_from DATE NOT NULL,
    valid_until DATE NOT NULL,
    max_uses INT DEFAULT NULL COMMENT 'Número máximo de usos (NULL = ilimitado)',
    current_uses INT DEFAULT 0,
    
    -- Condiciones
    terms_conditions TEXT,
    min_people INT DEFAULT 1,
    max_people INT DEFAULT NULL,
    
    -- Estado
    status ENUM('draft', 'active', 'paused', 'expired', 'cancelled') DEFAULT 'draft',
    is_featured BOOLEAN DEFAULT FALSE COMMENT 'Destacada en la home',
    
    -- Metadatos
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_resource (resource_type, resource_id),
    INDEX idx_status (status),
    INDEX idx_dates (valid_from, valid_until),
    INDEX idx_featured (is_featured, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Ofertas creadas por gestores de recursos';

-- ============================================
-- TABLA DE ESTADÍSTICAS POR RECURSO
-- ============================================

CREATE TABLE IF NOT EXISTS resource_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_type ENUM('accommodation', 'place', 'activity', 'event') NOT NULL,
    resource_id INT NOT NULL,
    
    -- Estadísticas
    views_count INT DEFAULT 0 COMMENT 'Visitas a la ficha',
    interests_count INT DEFAULT 0 COMMENT 'Clics en "Estoy interesado"',
    messages_count INT DEFAULT 0 COMMENT 'Mensajes recibidos',
    favorites_count INT DEFAULT 0 COMMENT 'Veces añadido a favoritos',
    offers_count INT DEFAULT 0 COMMENT 'Ofertas activas',
    
    -- Fechas
    last_view_at TIMESTAMP NULL,
    last_interest_at TIMESTAMP NULL,
    last_message_at TIMESTAMP NULL,
    
    -- Metadatos
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_resource (resource_type, resource_id),
    INDEX idx_resource (resource_type, resource_id),
    INDEX idx_views (views_count),
    INDEX idx_interests (interests_count)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Estadísticas agregadas por recurso';

-- ============================================
-- MODIFICAR TABLA CONVERSATIONS
-- ============================================

-- Asegurar que conversations tenga los campos necesarios para recursos
ALTER TABLE conversations
ADD COLUMN IF NOT EXISTS resource_type ENUM('accommodation', 'place', 'activity', 'event') NULL
    COMMENT 'Tipo de recurso asociado',
ADD COLUMN IF NOT EXISTS resource_id INT NULL
    COMMENT 'ID del recurso asociado';

-- Añadir índice compuesto
ALTER TABLE conversations
ADD INDEX IF NOT EXISTS idx_resource_conversation (resource_type, resource_id);

-- ============================================
-- TABLA FAVORITES (crear si no existe)
-- ============================================

-- Crear tabla favorites si no existe
CREATE TABLE IF NOT EXISTS favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    entity_type ENUM('accommodation', 'place', 'activity', 'event') NOT NULL,
    entity_id INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_entity (entity_type, entity_id),
    UNIQUE KEY unique_favorite (user_id, entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Favoritos de usuarios';

-- ============================================
-- VISTAS ÚTILES
-- ============================================

-- Vista: Recursos por usuario con información completa
CREATE OR REPLACE VIEW v_user_resources_full AS
SELECT 
    ur.id AS link_id,
    ur.user_id,
    u.email AS user_email,
    CONCAT(u.first_name, ' ', u.last_name) AS user_name,
    ur.resource_type,
    ur.resource_id,
    ur.role,
    ur.status,
    ur.created_at AS linked_at,
    ur.validated_at,
    
    -- Información del recurso (se completa con UNION de cada tipo)
    CASE ur.resource_type
        WHEN 'accommodation' THEN (SELECT name FROM accommodations WHERE id = ur.resource_id)
        WHEN 'place' THEN (SELECT name FROM places_of_interest WHERE id = ur.resource_id)
        WHEN 'activity' THEN (SELECT name FROM tourist_activities WHERE id = ur.resource_id)
        WHEN 'event' THEN (SELECT name FROM cultural_events WHERE id = ur.resource_id)
    END AS resource_name,
    
    -- Estadísticas
    COALESCE(rs.views_count, 0) AS views_count,
    COALESCE(rs.interests_count, 0) AS interests_count,
    COALESCE(rs.messages_count, 0) AS messages_count,
    COALESCE(rs.favorites_count, 0) AS favorites_count
    
FROM user_resources ur
LEFT JOIN users u ON ur.user_id = u.id
LEFT JOIN resource_stats rs ON ur.resource_type = rs.resource_type AND ur.resource_id = rs.resource_id;

-- Vista: Ofertas activas por recurso
CREATE OR REPLACE VIEW v_active_offers AS
SELECT 
    ro.id,
    ro.user_id,
    CONCAT(u.first_name, ' ', u.last_name) AS creator_name,
    ro.resource_type,
    ro.resource_id,
    ro.title,
    ro.description,
    ro.offer_price,
    ro.original_price,
    ro.discount_percentage,
    ro.valid_from,
    ro.valid_until,
    ro.status,
    ro.is_featured,
    ro.current_uses,
    ro.max_uses,
    
    -- Nombre del recurso
    CASE ro.resource_type
        WHEN 'accommodation' THEN (SELECT name FROM accommodations WHERE id = ro.resource_id)
        WHEN 'place' THEN (SELECT name FROM places_of_interest WHERE id = ro.resource_id)
        WHEN 'activity' THEN (SELECT name FROM tourist_activities WHERE id = ro.resource_id)
        WHEN 'event' THEN (SELECT name FROM cultural_events WHERE id = ro.resource_id)
    END AS resource_name,
    
    -- Días restantes
    DATEDIFF(ro.valid_until, CURDATE()) AS days_remaining
    
FROM resource_offers ro
LEFT JOIN users u ON ro.user_id = u.id
WHERE ro.status = 'active'
AND ro.valid_until >= CURDATE()
ORDER BY ro.is_featured DESC, ro.created_at DESC;

-- ============================================
-- PROCEDIMIENTOS ALMACENADOS
-- ============================================

DELIMITER //

-- Procedimiento: Vincular usuario con recurso
CREATE PROCEDURE sp_link_user_resource(
    IN p_user_id INT,
    IN p_resource_type VARCHAR(20),
    IN p_resource_id INT,
    IN p_role VARCHAR(20)
)
BEGIN
    DECLARE v_exists INT;
    
    -- Verificar si ya existe la vinculación
    SELECT COUNT(*) INTO v_exists
    FROM user_resources
    WHERE user_id = p_user_id
    AND resource_type = p_resource_type
    AND resource_id = p_resource_id;
    
    IF v_exists = 0 THEN
        -- Crear vinculación
        INSERT INTO user_resources (user_id, resource_type, resource_id, role, status)
        VALUES (p_user_id, p_resource_type, p_resource_id, p_role, 'pending');
        
        SELECT LAST_INSERT_ID() AS link_id, 'created' AS action;
    ELSE
        SELECT 0 AS link_id, 'already_exists' AS action;
    END IF;
END //

-- Procedimiento: Incrementar estadística de recurso
CREATE PROCEDURE sp_increment_resource_stat(
    IN p_resource_type VARCHAR(20),
    IN p_resource_id INT,
    IN p_stat_type VARCHAR(20)
)
BEGIN
    -- Crear registro si no existe
    INSERT INTO resource_stats (resource_type, resource_id)
    VALUES (p_resource_type, p_resource_id)
    ON DUPLICATE KEY UPDATE id = id;
    
    -- Incrementar según tipo
    CASE p_stat_type
        WHEN 'view' THEN
            UPDATE resource_stats
            SET views_count = views_count + 1,
                last_view_at = CURRENT_TIMESTAMP
            WHERE resource_type = p_resource_type AND resource_id = p_resource_id;
        
        WHEN 'interest' THEN
            UPDATE resource_stats
            SET interests_count = interests_count + 1,
                last_interest_at = CURRENT_TIMESTAMP
            WHERE resource_type = p_resource_type AND resource_id = p_resource_id;
        
        WHEN 'message' THEN
            UPDATE resource_stats
            SET messages_count = messages_count + 1,
                last_message_at = CURRENT_TIMESTAMP
            WHERE resource_type = p_resource_type AND resource_id = p_resource_id;
        
        WHEN 'favorite' THEN
            UPDATE resource_stats
            SET favorites_count = favorites_count + 1
            WHERE resource_type = p_resource_type AND resource_id = p_resource_id;
    END CASE;
END //

-- Procedimiento: Obtener recursos de un usuario
CREATE PROCEDURE sp_get_user_resources(
    IN p_user_id INT
)
BEGIN
    SELECT * FROM v_user_resources_full
    WHERE user_id = p_user_id
    ORDER BY resource_type, resource_name;
END //

DELIMITER ;

-- ============================================
-- TRIGGERS
-- ============================================

DELIMITER //

-- Trigger: Calcular descuento automáticamente
CREATE TRIGGER tr_calculate_discount
BEFORE INSERT ON resource_offers
FOR EACH ROW
BEGIN
    IF NEW.original_price IS NOT NULL AND NEW.offer_price IS NOT NULL THEN
        SET NEW.discount_percentage = ROUND(((NEW.original_price - NEW.offer_price) / NEW.original_price) * 100);
    END IF;
END //

CREATE TRIGGER tr_calculate_discount_update
BEFORE UPDATE ON resource_offers
FOR EACH ROW
BEGIN
    IF NEW.original_price IS NOT NULL AND NEW.offer_price IS NOT NULL THEN
        SET NEW.discount_percentage = ROUND(((NEW.original_price - NEW.offer_price) / NEW.original_price) * 100);
    END IF;
END //

-- Trigger: Actualizar estado de oferta si expira
CREATE TRIGGER tr_check_offer_expiry
BEFORE UPDATE ON resource_offers
FOR EACH ROW
BEGIN
    IF NEW.valid_until < CURDATE() AND NEW.status = 'active' THEN
        SET NEW.status = 'expired';
    END IF;
END //

DELIMITER ;

-- ============================================
-- DATOS DE EJEMPLO (OPCIONAL)
-- ============================================

-- Ejemplo: Vincular usuario con alojamiento
-- INSERT INTO user_resources (user_id, resource_type, resource_id, role, status)
-- VALUES (1, 'accommodation', 1, 'owner', 'active');

-- ============================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- ============================================

-- Índices compuestos para consultas frecuentes
CREATE INDEX idx_user_status ON user_resources(user_id, status);
CREATE INDEX idx_resource_status ON user_resources(resource_type, resource_id, status);
CREATE INDEX idx_offers_active ON resource_offers(status, valid_until);

-- ============================================
-- FIN DEL SCRIPT
-- ============================================
