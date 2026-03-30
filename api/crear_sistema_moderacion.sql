-- =====================================================
-- SISTEMA DE MODERACIÓN AVANZADO PARA ACCOMMODATIONS
-- =====================================================
-- Este script crea el sistema completo de moderación con versionado
-- Ejecutar en phpMyAdmin o línea de comandos MySQL

USE u412199647_Rutas;

-- =====================================================
-- 1. MODIFICAR TABLA ACCOMMODATIONS (Agregar campos de moderación)
-- =====================================================

-- Agregar columnas de moderación a la tabla principal
ALTER TABLE accommodations 
ADD COLUMN IF NOT EXISTS moderation_status ENUM('draft', 'pending', 'approved', 'rejected') DEFAULT 'draft' COMMENT 'Estado de moderación del alojamiento',
ADD COLUMN IF NOT EXISTS has_pending_changes BOOLEAN DEFAULT FALSE COMMENT 'Indica si hay cambios pendientes de revisión',
ADD COLUMN IF NOT EXISTS rejection_reason TEXT NULL COMMENT 'Motivo del rechazo si aplica',
ADD COLUMN IF NOT EXISTS reviewed_by INT NULL COMMENT 'ID del admin que revisó',
ADD COLUMN IF NOT EXISTS reviewed_at DATETIME NULL COMMENT 'Fecha de revisión',
ADD COLUMN IF NOT EXISTS published_at DATETIME NULL COMMENT 'Fecha de primera publicación',
ADD COLUMN IF NOT EXISTS last_submitted_at DATETIME NULL COMMENT 'Última vez que se envió para revisión',
ADD INDEX idx_moderation_status (moderation_status),
ADD INDEX idx_has_pending_changes (has_pending_changes),
ADD FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL;

-- =====================================================
-- 2. CREAR TABLA DE CAMBIOS PENDIENTES
-- =====================================================

CREATE TABLE IF NOT EXISTS accommodation_pending_changes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    accommodation_id INT NOT NULL COMMENT 'ID del alojamiento al que pertenecen los cambios',
    change_type ENUM('new', 'update') NOT NULL COMMENT 'Tipo de cambio: nuevo alojamiento o actualización',
    pending_data JSON NOT NULL COMMENT 'Datos completos del alojamiento pendientes de aprobación',
    submitted_by INT NOT NULL COMMENT 'Usuario que envió los cambios',
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de envío',
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' COMMENT 'Estado de la revisión',
    reviewed_by INT NULL COMMENT 'Admin que revisó',
    reviewed_at DATETIME NULL COMMENT 'Fecha de revisión',
    rejection_reason TEXT NULL COMMENT 'Motivo del rechazo',
    admin_notes TEXT NULL COMMENT 'Notas internas del admin',
    
    FOREIGN KEY (accommodation_id) REFERENCES accommodations(id) ON DELETE CASCADE,
    FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_status (status),
    INDEX idx_accommodation (accommodation_id),
    INDEX idx_submitted_at (submitted_at),
    INDEX idx_change_type (change_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Almacena cambios pendientes de aprobación para alojamientos';

-- =====================================================
-- 3. CREAR TABLA DE HISTORIAL DE MODERACIÓN
-- =====================================================

CREATE TABLE IF NOT EXISTS accommodation_moderation_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    accommodation_id INT NOT NULL COMMENT 'ID del alojamiento',
    action ENUM('created', 'submitted', 'approved', 'rejected', 'updated', 'resubmitted') NOT NULL COMMENT 'Acción realizada',
    performed_by INT NOT NULL COMMENT 'Usuario que realizó la acción',
    previous_status VARCHAR(50) NULL COMMENT 'Estado anterior',
    new_status VARCHAR(50) NULL COMMENT 'Nuevo estado',
    notes TEXT NULL COMMENT 'Notas o comentarios',
    rejection_reason TEXT NULL COMMENT 'Motivo de rechazo si aplica',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de la acción',
    
    FOREIGN KEY (accommodation_id) REFERENCES accommodations(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_accommodation (accommodation_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    INDEX idx_performed_by (performed_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Historial completo de acciones de moderación';

-- =====================================================
-- 4. CREAR TABLA DE NOTIFICACIONES DE MODERACIÓN
-- =====================================================

CREATE TABLE IF NOT EXISTS moderation_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'Usuario destinatario',
    accommodation_id INT NOT NULL COMMENT 'Alojamiento relacionado',
    notification_type ENUM('submitted', 'approved', 'rejected', 'changes_requested') NOT NULL,
    title VARCHAR(255) NOT NULL COMMENT 'Título de la notificación',
    message TEXT NOT NULL COMMENT 'Mensaje de la notificación',
    is_read BOOLEAN DEFAULT FALSE COMMENT 'Si fue leída',
    email_sent BOOLEAN DEFAULT FALSE COMMENT 'Si se envió email',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (accommodation_id) REFERENCES accommodations(id) ON DELETE CASCADE,
    
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Notificaciones del sistema de moderación';

-- =====================================================
-- 5. ACTUALIZAR ALOJAMIENTOS EXISTENTES
-- =====================================================

-- Marcar todos los alojamientos activos existentes como aprobados
UPDATE accommodations 
SET moderation_status = 'approved',
    published_at = created_at,
    reviewed_at = created_at
WHERE is_active = 1 AND moderation_status = 'draft';

-- Marcar los inactivos como borradores
UPDATE accommodations 
SET moderation_status = 'draft'
WHERE is_active = 0 AND moderation_status = 'draft';

-- =====================================================
-- 6. CREAR VISTA PARA PANEL DE MODERACIÓN
-- =====================================================

CREATE OR REPLACE VIEW v_moderation_queue AS
SELECT 
    a.id,
    a.name,
    a.municipality,
    a.province,
    a.moderation_status,
    a.has_pending_changes,
    a.last_submitted_at,
    a.created_at,
    u.first_name,
    u.last_name,
    u.email as user_email,
    u.id as user_id,
    CASE 
        WHEN a.has_pending_changes = 1 THEN 'update'
        ELSE 'new'
    END as change_type,
    DATEDIFF(NOW(), a.last_submitted_at) as days_pending
FROM accommodations a
LEFT JOIN users u ON a.created_by = u.id
WHERE a.moderation_status = 'pending' 
   OR (a.moderation_status = 'approved' AND a.has_pending_changes = 1)
ORDER BY a.last_submitted_at ASC;

-- =====================================================
-- 7. CREAR VISTA PARA ESTADÍSTICAS DE MODERACIÓN
-- =====================================================

CREATE OR REPLACE VIEW v_moderation_stats AS
SELECT 
    COUNT(CASE WHEN moderation_status = 'pending' THEN 1 END) as pending_count,
    COUNT(CASE WHEN moderation_status = 'approved' THEN 1 END) as approved_count,
    COUNT(CASE WHEN moderation_status = 'rejected' THEN 1 END) as rejected_count,
    COUNT(CASE WHEN moderation_status = 'draft' THEN 1 END) as draft_count,
    COUNT(CASE WHEN has_pending_changes = 1 THEN 1 END) as pending_changes_count,
    AVG(CASE 
        WHEN moderation_status = 'approved' AND reviewed_at IS NOT NULL 
        THEN TIMESTAMPDIFF(HOUR, last_submitted_at, reviewed_at) 
    END) as avg_review_time_hours
FROM accommodations;

-- =====================================================
-- 8. PROCEDIMIENTO ALMACENADO: APROBAR ALOJAMIENTO
-- =====================================================

DELIMITER //

CREATE PROCEDURE sp_approve_accommodation(
    IN p_accommodation_id INT,
    IN p_admin_id INT,
    IN p_admin_notes TEXT
)
BEGIN
    DECLARE v_previous_status VARCHAR(50);
    DECLARE v_user_id INT;
    
    -- Obtener estado anterior y usuario
    SELECT moderation_status, created_by INTO v_previous_status, v_user_id
    FROM accommodations 
    WHERE id = p_accommodation_id;
    
    -- Actualizar alojamiento
    UPDATE accommodations 
    SET moderation_status = 'approved',
        is_active = 1,
        has_pending_changes = FALSE,
        reviewed_by = p_admin_id,
        reviewed_at = NOW(),
        published_at = COALESCE(published_at, NOW()),
        rejection_reason = NULL
    WHERE id = p_accommodation_id;
    
    -- Si hay cambios pendientes, aplicarlos
    UPDATE accommodation_pending_changes
    SET status = 'approved',
        reviewed_by = p_admin_id,
        reviewed_at = NOW()
    WHERE accommodation_id = p_accommodation_id 
      AND status = 'pending';
    
    -- Registrar en historial
    INSERT INTO accommodation_moderation_history 
        (accommodation_id, action, performed_by, previous_status, new_status, notes)
    VALUES 
        (p_accommodation_id, 'approved', p_admin_id, v_previous_status, 'approved', p_admin_notes);
    
    -- Crear notificación
    INSERT INTO moderation_notifications 
        (user_id, accommodation_id, notification_type, title, message)
    VALUES 
        (v_user_id, p_accommodation_id, 'approved', 
         '¡Alojamiento Aprobado!', 
         'Tu alojamiento ha sido aprobado y ahora es visible para todos los usuarios.');
END //

DELIMITER ;

-- =====================================================
-- 9. PROCEDIMIENTO ALMACENADO: RECHAZAR ALOJAMIENTO
-- =====================================================

DELIMITER //

CREATE PROCEDURE sp_reject_accommodation(
    IN p_accommodation_id INT,
    IN p_admin_id INT,
    IN p_rejection_reason TEXT,
    IN p_admin_notes TEXT
)
BEGIN
    DECLARE v_previous_status VARCHAR(50);
    DECLARE v_user_id INT;
    
    -- Obtener estado anterior y usuario
    SELECT moderation_status, created_by INTO v_previous_status, v_user_id
    FROM accommodations 
    WHERE id = p_accommodation_id;
    
    -- Actualizar alojamiento
    UPDATE accommodations 
    SET moderation_status = 'rejected',
        is_active = 0,
        has_pending_changes = FALSE,
        reviewed_by = p_admin_id,
        reviewed_at = NOW(),
        rejection_reason = p_rejection_reason
    WHERE id = p_accommodation_id;
    
    -- Rechazar cambios pendientes
    UPDATE accommodation_pending_changes
    SET status = 'rejected',
        reviewed_by = p_admin_id,
        reviewed_at = NOW(),
        rejection_reason = p_rejection_reason
    WHERE accommodation_id = p_accommodation_id 
      AND status = 'pending';
    
    -- Registrar en historial
    INSERT INTO accommodation_moderation_history 
        (accommodation_id, action, performed_by, previous_status, new_status, notes, rejection_reason)
    VALUES 
        (p_accommodation_id, 'rejected', p_admin_id, v_previous_status, 'rejected', 
         p_admin_notes, p_rejection_reason);
    
    -- Crear notificación
    INSERT INTO moderation_notifications 
        (user_id, accommodation_id, notification_type, title, message)
    VALUES 
        (v_user_id, p_accommodation_id, 'rejected', 
         'Alojamiento Requiere Correcciones', 
         CONCAT('Tu alojamiento necesita correcciones. Motivo: ', p_rejection_reason));
END //

DELIMITER ;

-- =====================================================
-- 10. VERIFICACIÓN FINAL
-- =====================================================

SELECT 'Sistema de moderación instalado correctamente' as status;

-- Mostrar estadísticas
SELECT * FROM v_moderation_stats;

-- Mostrar cola de moderación
SELECT COUNT(*) as items_en_cola FROM v_moderation_queue;
