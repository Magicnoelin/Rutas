-- ============================================
-- SISTEMA DE PERMISOS DE MENSAJERÍA
-- Rutas Rurales - Control de Conversaciones por Membresía
-- ============================================

-- Tabla de permisos de chat configurables
CREATE TABLE IF NOT EXISTS chat_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Quién inicia la acción
    initiator_type ENUM('turista', 'gestor') NOT NULL COMMENT 'Tipo de usuario que inicia',
    initiator_membership ENUM('free', 'premium', 'enterprise') NOT NULL COMMENT 'Membresía del iniciador',
    
    -- Con quién interactúa
    recipient_type ENUM('turista', 'gestor') NOT NULL COMMENT 'Tipo de usuario destinatario',
    recipient_membership ENUM('free', 'premium', 'enterprise', 'any') NOT NULL COMMENT 'Membresía del destinatario (any = cualquiera)',
    
    -- Permisos específicos
    can_initiate_conversation BOOLEAN DEFAULT FALSE COMMENT 'Puede iniciar una conversación nueva',
    can_send_messages BOOLEAN DEFAULT FALSE COMMENT 'Puede enviar mensajes',
    can_send_offers BOOLEAN DEFAULT FALSE COMMENT 'Puede enviar ofertas comerciales',
    max_messages_per_day INT DEFAULT NULL COMMENT 'Límite de mensajes por día (NULL = ilimitado)',
    
    -- Metadatos
    description TEXT COMMENT 'Descripción de la regla',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'Si la regla está activa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_permission (initiator_type, initiator_membership, recipient_type, recipient_membership),
    INDEX idx_active (is_active),
    INDEX idx_initiator (initiator_type, initiator_membership)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Permisos de mensajería configurables por tipo de usuario y membresía';

-- ============================================
-- REGLAS INICIALES DE PERMISOS
-- ============================================

-- TURISTAS CON MEMBRESÍA GRATUITA
INSERT INTO chat_permissions (
    initiator_type, initiator_membership, 
    recipient_type, recipient_membership,
    can_initiate_conversation, can_send_messages, can_send_offers, max_messages_per_day,
    description
) VALUES
-- Turista free → Gestor (cualquier membresía)
('turista', 'free', 'gestor', 'any', 
 TRUE, TRUE, FALSE, 50,
 'Turistas gratuitos pueden contactar gestores. Límite 50 mensajes/día para evitar spam'),

-- Turista free → Turista (BLOQUEADO)
('turista', 'free', 'turista', 'any', 
 FALSE, FALSE, FALSE, NULL,
 'Turistas no pueden hablar entre ellos para evitar spam y uso indebido'),

-- TURISTAS CON MEMBRESÍA PREMIUM
('turista', 'premium', 'gestor', 'any', 
 TRUE, TRUE, FALSE, NULL,
 'Turistas premium pueden contactar gestores sin límites'),

('turista', 'premium', 'turista', 'any', 
 FALSE, FALSE, FALSE, NULL,
 'Turistas premium tampoco pueden hablar entre ellos'),

-- GESTORES CON MEMBRESÍA GRATUITA
-- Gestor free → Turista (SOLO RESPONDER, no iniciar)
('gestor', 'free', 'turista', 'any', 
 FALSE, TRUE, FALSE, 20,
 'Gestores gratuitos solo pueden responder a turistas que les contacten. Límite 20 mensajes/día'),

-- Gestor free → Gestor (BLOQUEADO)
('gestor', 'free', 'gestor', 'any', 
 FALSE, FALSE, FALSE, NULL,
 'Gestores gratuitos no pueden contactar a otros gestores'),

-- GESTORES CON MEMBRESÍA PREMIUM
-- Gestor premium → Turista (SOLO RESPONDER, no iniciar proactivamente)
('gestor', 'premium', 'turista', 'any', 
 FALSE, TRUE, TRUE, NULL,
 'Gestores premium pueden responder a turistas y enviar ofertas, pero no iniciar conversaciones'),

-- Gestor premium → Gestor premium (PUEDEN HABLAR ENTRE ELLOS)
('gestor', 'premium', 'gestor', 'premium', 
 TRUE, TRUE, TRUE, NULL,
 'Gestores premium pueden contactar entre ellos y colaborar'),

-- Gestor premium → Gestor free (PUEDEN CONTACTAR)
('gestor', 'premium', 'gestor', 'free', 
 TRUE, TRUE, FALSE, NULL,
 'Gestores premium pueden contactar a gestores gratuitos'),

-- GESTORES CON MEMBRESÍA ENTERPRISE (futuro)
('gestor', 'enterprise', 'turista', 'any', 
 FALSE, TRUE, TRUE, NULL,
 'Gestores enterprise pueden responder y enviar ofertas sin límites'),

('gestor', 'enterprise', 'gestor', 'any', 
 TRUE, TRUE, TRUE, NULL,
 'Gestores enterprise pueden contactar a cualquier gestor');

-- ============================================
-- TABLA DE CONTROL DE MENSAJES DIARIOS
-- ============================================

CREATE TABLE IF NOT EXISTS chat_daily_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    messages_sent INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_date (user_id, date),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Control de límites diarios de mensajes por usuario';

-- ============================================
-- VISTAS ÚTILES
-- ============================================

-- Vista: Permisos activos por tipo de usuario
CREATE OR REPLACE VIEW v_chat_permissions_summary AS
SELECT 
    CONCAT(initiator_type, ' (', initiator_membership, ')') AS from_user,
    CONCAT(recipient_type, ' (', recipient_membership, ')') AS to_user,
    can_initiate_conversation AS can_start,
    can_send_messages AS can_message,
    can_send_offers AS can_offer,
    max_messages_per_day AS daily_limit,
    description
FROM chat_permissions
WHERE is_active = TRUE
ORDER BY initiator_type, initiator_membership, recipient_type;

-- ============================================
-- FUNCIONES AUXILIARES
-- ============================================

DELIMITER //

-- Función: Verificar si un usuario puede iniciar conversación
CREATE FUNCTION fn_can_initiate_chat(
    p_user_id INT,
    p_recipient_id INT
) RETURNS BOOLEAN
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_can_initiate BOOLEAN DEFAULT FALSE;
    DECLARE v_user_type VARCHAR(20);
    DECLARE v_user_membership VARCHAR(20);
    DECLARE v_recipient_type VARCHAR(20);
    DECLARE v_recipient_membership VARCHAR(20);
    
    -- Obtener tipos y membresías
    SELECT 
        CASE WHEN user_type = 'turista' THEN 'turista' ELSE 'gestor' END,
        membership_type
    INTO v_user_type, v_user_membership
    FROM users WHERE id = p_user_id;
    
    SELECT 
        CASE WHEN user_type = 'turista' THEN 'turista' ELSE 'gestor' END,
        membership_type
    INTO v_recipient_type, v_recipient_membership
    FROM users WHERE id = p_recipient_id;
    
    -- Buscar permiso
    SELECT can_initiate_conversation INTO v_can_initiate
    FROM chat_permissions
    WHERE initiator_type = v_user_type
    AND initiator_membership = v_user_membership
    AND recipient_type = v_recipient_type
    AND (recipient_membership = v_recipient_membership OR recipient_membership = 'any')
    AND is_active = TRUE
    LIMIT 1;
    
    RETURN COALESCE(v_can_initiate, FALSE);
END //

-- Procedimiento: Incrementar contador de mensajes diarios
CREATE PROCEDURE sp_increment_daily_messages(
    IN p_user_id INT
)
BEGIN
    INSERT INTO chat_daily_limits (user_id, date, messages_sent)
    VALUES (p_user_id, CURDATE(), 1)
    ON DUPLICATE KEY UPDATE 
        messages_sent = messages_sent + 1,
        updated_at = CURRENT_TIMESTAMP;
END //

-- Función: Verificar límite diario de mensajes
CREATE FUNCTION fn_check_daily_limit(
    p_user_id INT,
    p_recipient_id INT
) RETURNS BOOLEAN
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_messages_sent INT DEFAULT 0;
    DECLARE v_max_limit INT DEFAULT NULL;
    DECLARE v_user_type VARCHAR(20);
    DECLARE v_user_membership VARCHAR(20);
    DECLARE v_recipient_type VARCHAR(20);
    
    -- Obtener mensajes enviados hoy
    SELECT COALESCE(messages_sent, 0) INTO v_messages_sent
    FROM chat_daily_limits
    WHERE user_id = p_user_id AND date = CURDATE();
    
    -- Obtener límite según permisos
    SELECT 
        CASE WHEN u.user_type = 'turista' THEN 'turista' ELSE 'gestor' END,
        u.membership_type
    INTO v_user_type, v_user_membership
    FROM users u WHERE u.id = p_user_id;
    
    SELECT 
        CASE WHEN u.user_type = 'turista' THEN 'turista' ELSE 'gestor' END
    INTO v_recipient_type
    FROM users u WHERE u.id = p_recipient_id;
    
    SELECT max_messages_per_day INTO v_max_limit
    FROM chat_permissions
    WHERE initiator_type = v_user_type
    AND initiator_membership = v_user_membership
    AND recipient_type = v_recipient_type
    AND is_active = TRUE
    LIMIT 1;
    
    -- Si no hay límite (NULL), permitir
    IF v_max_limit IS NULL THEN
        RETURN TRUE;
    END IF;
    
    -- Verificar si está dentro del límite
    RETURN v_messages_sent < v_max_limit;
END //

DELIMITER ;

-- ============================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- ============================================

CREATE INDEX idx_daily_limits_user_date ON chat_daily_limits(user_id, date);

-- ============================================
-- LIMPIEZA AUTOMÁTICA (ejecutar periódicamente)
-- ============================================

-- Eliminar registros de límites diarios antiguos (más de 30 días)
-- DELETE FROM chat_daily_limits WHERE date < DATE_SUB(CURDATE(), INTERVAL 30 DAY);

-- ============================================
-- FIN DEL SCRIPT
-- ============================================
