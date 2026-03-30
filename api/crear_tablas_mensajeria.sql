-- ============================================
-- SISTEMA DE MENSAJERÍA Y USUARIOS - RUTAS
-- ============================================

-- Tabla de usuarios (turistas y proveedores)
-- NOTA: Si la tabla users ya existe, este script la respetará
-- Solo añadirá las columnas que falten si es necesario
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    user_type ENUM('tourist', 'provider') NOT NULL DEFAULT 'tourist',
    nickname VARCHAR(100),
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(20),
    avatar_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_email (email),
    INDEX idx_user_type (user_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Añadir columna preferences_json si no existe (para compatibilidad)
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS preferences_json TEXT AFTER avatar_url;

-- Tabla de preferencias de usuario (desglosadas)
CREATE TABLE IF NOT EXISTS user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    interest_type VARCHAR(50),
    accommodation_type VARCHAR(50),
    budget_range VARCHAR(20),
    group_size VARCHAR(20),
    trip_duration VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de entidades (alojamientos, lugares, actividades, eventos)
CREATE TABLE IF NOT EXISTS entities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type ENUM('accommodation', 'place', 'activity', 'event') NOT NULL,
    entity_id INT NOT NULL,
    owner_user_id INT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    contact_email VARCHAR(255),
    contact_phone VARCHAR(20),
    address TEXT,
    website VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_entity_type (entity_type),
    INDEX idx_entity_id (entity_id),
    INDEX idx_owner (owner_user_id),
    UNIQUE KEY unique_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de conversaciones
CREATE TABLE IF NOT EXISTS conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tourist_id INT NOT NULL,
    entity_type ENUM('accommodation', 'place', 'activity', 'event') NOT NULL,
    entity_id INT NOT NULL,
    provider_id INT,
    status ENUM('active', 'archived', 'closed') DEFAULT 'active',
    last_message_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tourist_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_tourist (tourist_id),
    INDEX idx_provider (provider_id),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_status (status),
    INDEX idx_last_message (last_message_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de mensajes
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    sender_type ENUM('tourist', 'provider') NOT NULL,
    message_text TEXT NOT NULL,
    message_type ENUM('text', 'offer', 'system') DEFAULT 'text',
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_conversation (conversation_id),
    INDEX idx_sender (sender_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de ofertas
CREATE TABLE IF NOT EXISTS offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    offer_title VARCHAR(255) NOT NULL,
    offer_description TEXT,
    offer_price DECIMAL(10, 2),
    original_price DECIMAL(10, 2),
    discount_percentage INT,
    valid_from DATE,
    valid_until DATE,
    terms_conditions TEXT,
    status ENUM('pending', 'accepted', 'rejected', 'expired', 'cancelled') DEFAULT 'pending',
    accepted_at TIMESTAMP NULL,
    rejected_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_message (message_id),
    INDEX idx_conversation (conversation_id),
    INDEX idx_status (status),
    INDEX idx_valid_until (valid_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de contactos compartidos (cuando se acepta una oferta)
CREATE TABLE IF NOT EXISTS shared_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    offer_id INT NOT NULL,
    conversation_id INT NOT NULL,
    tourist_id INT NOT NULL,
    provider_id INT NOT NULL,
    tourist_email VARCHAR(255),
    tourist_phone VARCHAR(20),
    tourist_name VARCHAR(255),
    provider_email VARCHAR(255),
    provider_phone VARCHAR(20),
    provider_name VARCHAR(255),
    provider_address TEXT,
    provider_website VARCHAR(500),
    shared_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE CASCADE,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (tourist_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_offer (offer_id),
    INDEX idx_conversation (conversation_id),
    INDEX idx_tourist (tourist_id),
    INDEX idx_provider (provider_id),
    UNIQUE KEY unique_share (offer_id, conversation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de favoritos
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de notificaciones
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notification_type ENUM('new_message', 'new_offer', 'offer_accepted', 'offer_rejected', 'system') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    related_conversation_id INT,
    related_message_id INT,
    related_offer_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (related_conversation_id) REFERENCES conversations(id) ON DELETE SET NULL,
    FOREIGN KEY (related_message_id) REFERENCES messages(id) ON DELETE SET NULL,
    FOREIGN KEY (related_offer_id) REFERENCES offers(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_type (notification_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de notificaciones por email (log)
CREATE TABLE IF NOT EXISTS email_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notification_id INT,
    email_to VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de sesiones (para WebSockets en tiempo real)
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    socket_id VARCHAR(255),
    is_online BOOLEAN DEFAULT TRUE,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_token (session_token),
    INDEX idx_online (is_online),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DATOS DE EJEMPLO PARA TESTING
-- ============================================

-- Insertar usuarios de ejemplo (solo si no existen)
INSERT IGNORE INTO users (email, password_hash, user_type, nickname, first_name, last_name, phone) VALUES
('maria@email.com', '$2y$10$example_hash_tourist', 'tourist', 'MariaViajera', 'María', 'García', '+34 600 111 222'),
('juan@email.com', '$2y$10$example_hash_tourist', 'tourist', 'JuanAventura', 'Juan', 'Pérez', '+34 600 333 444'),
('casarural@email.com', '$2y$10$example_hash_provider', 'provider', 'CasaRuralElPinar', 'Casa Rural', 'El Pinar', '+34 975 123 456'),
('rutaslobos@email.com', '$2y$10$example_hash_provider', 'provider', 'RutasRioLobos', 'Rutas del Cañón', 'del Río Lobos', '+34 975 789 012');

-- Actualizar preferences_json para usuarios de ejemplo (si la columna existe)
UPDATE users SET preferences_json = '{"interests":["naturaleza","cultura"],"budget":"100-150"}' WHERE email = 'maria@email.com';
UPDATE users SET preferences_json = '{"interests":["aventura","fotografia"],"budget":"50-100"}' WHERE email = 'juan@email.com';

-- Insertar entidades de ejemplo
INSERT INTO entities (entity_type, entity_id, owner_user_id, name, description, contact_email, contact_phone) VALUES
('accommodation', 1, 3, 'Casa Rural El Pinar', 'Acogedora casa rural en plena naturaleza', 'casarural@email.com', '+34 975 123 456'),
('activity', 1, 4, 'Ruta del Cañón del Río Lobos', 'Senderismo guiado por el cañón', 'rutaslobos@email.com', '+34 975 789 012');

-- Insertar conversaciones de ejemplo
INSERT INTO conversations (tourist_id, entity_type, entity_id, provider_id, status) VALUES
(1, 'accommodation', 1, 3, 'active'),
(1, 'activity', 1, 4, 'active');

-- Insertar mensajes de ejemplo
INSERT INTO messages (conversation_id, sender_id, sender_type, message_text, is_read) VALUES
(1, 3, 'provider', '¡Hola! Gracias por tu interés en Casa Rural El Pinar', TRUE),
(1, 3, 'provider', 'Tenemos una oferta especial para ti este fin de semana', TRUE),
(1, 1, 'tourist', '¡Me interesa! ¿Incluye todas las comidas?', FALSE),
(2, 4, 'provider', 'Nueva actividad de senderismo disponible para el próximo sábado', FALSE);

-- Insertar oferta de ejemplo
INSERT INTO offers (message_id, conversation_id, sender_id, offer_title, offer_description, offer_price, original_price, discount_percentage, valid_until, status) VALUES
(2, 1, 3, 'Oferta Fin de Semana', '2 noches + desayuno por solo 150€ (ahorro de 50€)', 150.00, 200.00, 25, '2026-01-25', 'pending');

-- Insertar notificaciones de ejemplo
INSERT INTO notifications (user_id, notification_type, title, message, related_conversation_id, related_message_id) VALUES
(1, 'new_message', 'Nuevo mensaje de Casa Rural El Pinar', 'Tenemos una oferta especial para ti', 1, 2),
(1, 'new_offer', 'Nueva oferta disponible', 'Casa Rural El Pinar te ha enviado una oferta especial', 1, 2);

-- ============================================
-- VISTAS ÚTILES
-- ============================================

-- Vista de conversaciones con información completa
CREATE OR REPLACE VIEW v_conversations_full AS
SELECT 
    c.id AS conversation_id,
    c.tourist_id,
    u_tourist.nickname AS tourist_nickname,
    u_tourist.email AS tourist_email,
    c.entity_type,
    c.entity_id,
    e.name AS entity_name,
    c.provider_id,
    u_provider.nickname AS provider_nickname,
    u_provider.email AS provider_email,
    c.status,
    c.last_message_at,
    (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.is_read = FALSE AND m.sender_type = 'provider') AS unread_count_tourist,
    (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.is_read = FALSE AND m.sender_type = 'tourist') AS unread_count_provider,
    c.created_at
FROM conversations c
LEFT JOIN users u_tourist ON c.tourist_id = u_tourist.id
LEFT JOIN users u_provider ON c.provider_id = u_provider.id
LEFT JOIN entities e ON c.entity_type = e.entity_type AND c.entity_id = e.entity_id;

-- Vista de mensajes con información del remitente
CREATE OR REPLACE VIEW v_messages_full AS
SELECT 
    m.id AS message_id,
    m.conversation_id,
    m.sender_id,
    u.nickname AS sender_nickname,
    u.email AS sender_email,
    m.sender_type,
    m.message_text,
    m.message_type,
    m.is_read,
    m.read_at,
    m.created_at,
    o.id AS offer_id,
    o.offer_title,
    o.offer_description,
    o.offer_price,
    o.status AS offer_status
FROM messages m
LEFT JOIN users u ON m.sender_id = u.id
LEFT JOIN offers o ON m.id = o.message_id;

-- ============================================
-- PROCEDIMIENTOS ALMACENADOS
-- ============================================

DELIMITER //

-- Procedimiento para crear una nueva conversación
CREATE PROCEDURE sp_create_conversation(
    IN p_tourist_id INT,
    IN p_entity_type VARCHAR(50),
    IN p_entity_id INT,
    IN p_provider_id INT
)
BEGIN
    DECLARE v_conversation_id INT;
    
    -- Verificar si ya existe una conversación
    SELECT id INTO v_conversation_id
    FROM conversations
    WHERE tourist_id = p_tourist_id
    AND entity_type = p_entity_type
    AND entity_id = p_entity_id
    LIMIT 1;
    
    IF v_conversation_id IS NULL THEN
        -- Crear nueva conversación
        INSERT INTO conversations (tourist_id, entity_type, entity_id, provider_id)
        VALUES (p_tourist_id, p_entity_type, p_entity_id, p_provider_id);
        
        SET v_conversation_id = LAST_INSERT_ID();
    END IF;
    
    SELECT v_conversation_id AS conversation_id;
END //

-- Procedimiento para enviar un mensaje
CREATE PROCEDURE sp_send_message(
    IN p_conversation_id INT,
    IN p_sender_id INT,
    IN p_sender_type VARCHAR(20),
    IN p_message_text TEXT,
    IN p_message_type VARCHAR(20)
)
BEGIN
    DECLARE v_message_id INT;
    DECLARE v_recipient_id INT;
    
    -- Insertar mensaje
    INSERT INTO messages (conversation_id, sender_id, sender_type, message_text, message_type)
    VALUES (p_conversation_id, p_sender_id, p_sender_type, p_message_text, p_message_type);
    
    SET v_message_id = LAST_INSERT_ID();
    
    -- Actualizar última actividad de la conversación
    UPDATE conversations
    SET last_message_at = CURRENT_TIMESTAMP
    WHERE id = p_conversation_id;
    
    -- Determinar destinatario y crear notificación
    IF p_sender_type = 'tourist' THEN
        SELECT provider_id INTO v_recipient_id
        FROM conversations
        WHERE id = p_conversation_id;
    ELSE
        SELECT tourist_id INTO v_recipient_id
        FROM conversations
        WHERE id = p_conversation_id;
    END IF;
    
    IF v_recipient_id IS NOT NULL THEN
        INSERT INTO notifications (user_id, notification_type, title, message, related_conversation_id, related_message_id)
        VALUES (v_recipient_id, 'new_message', 'Nuevo mensaje', LEFT(p_message_text, 100), p_conversation_id, v_message_id);
    END IF;
    
    SELECT v_message_id AS message_id;
END //

-- Procedimiento para aceptar una oferta
CREATE PROCEDURE sp_accept_offer(
    IN p_offer_id INT,
    IN p_user_id INT
)
BEGIN
    DECLARE v_conversation_id INT;
    DECLARE v_tourist_id INT;
    DECLARE v_provider_id INT;
    
    -- Actualizar estado de la oferta
    UPDATE offers
    SET status = 'accepted', accepted_at = CURRENT_TIMESTAMP
    WHERE id = p_offer_id;
    
    -- Obtener información de la conversación
    SELECT o.conversation_id, c.tourist_id, c.provider_id
    INTO v_conversation_id, v_tourist_id, v_provider_id
    FROM offers o
    JOIN conversations c ON o.conversation_id = c.id
    WHERE o.id = p_offer_id;
    
    -- Compartir datos de contacto
    INSERT INTO shared_contacts (
        offer_id, conversation_id, tourist_id, provider_id,
        tourist_email, tourist_phone, tourist_name,
        provider_email, provider_phone, provider_name
    )
    SELECT 
        p_offer_id, v_conversation_id, v_tourist_id, v_provider_id,
        ut.email, ut.phone, CONCAT(ut.first_name, ' ', ut.last_name),
        up.email, up.phone, CONCAT(up.first_name, ' ', up.last_name)
    FROM users ut, users up
    WHERE ut.id = v_tourist_id AND up.id = v_provider_id;
    
    -- Crear notificaciones
    INSERT INTO notifications (user_id, notification_type, title, message, related_offer_id)
    VALUES 
    (v_provider_id, 'offer_accepted', 'Oferta aceptada', 'Tu oferta ha sido aceptada', p_offer_id),
    (v_tourist_id, 'offer_accepted', 'Oferta aceptada', 'Has aceptado la oferta. Datos de contacto compartidos.', p_offer_id);
    
    SELECT 'Oferta aceptada y datos compartidos' AS result;
END //

DELIMITER ;

-- ============================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- ============================================

-- Índice compuesto para búsquedas frecuentes
CREATE INDEX idx_conv_tourist_status ON conversations(tourist_id, status, last_message_at);
CREATE INDEX idx_conv_provider_status ON conversations(provider_id, status, last_message_at);
CREATE INDEX idx_msg_conv_created ON messages(conversation_id, created_at);
CREATE INDEX idx_notif_user_unread ON notifications(user_id, is_read, created_at);

-- ============================================
-- TRIGGERS
-- ============================================

DELIMITER //

-- Trigger para limpiar sesiones expiradas
CREATE TRIGGER tr_clean_expired_sessions
BEFORE INSERT ON user_sessions
FOR EACH ROW
BEGIN
    DELETE FROM user_sessions
    WHERE expires_at < CURRENT_TIMESTAMP;
END //

-- Trigger para actualizar contador de mensajes no leídos
CREATE TRIGGER tr_update_unread_count
AFTER INSERT ON messages
FOR EACH ROW
BEGIN
    IF NEW.sender_type = 'provider' THEN
        UPDATE conversations
        SET updated_at = CURRENT_TIMESTAMP
        WHERE id = NEW.conversation_id;
    END IF;
END //

DELIMITER ;

-- ============================================
-- FIN DEL SCRIPT
-- ============================================
