-- ============================================================
-- MEJORA: Añadir columna log_envio a historial_tareas
-- para guardar el resultado real del envío SMTP
-- ============================================================

ALTER TABLE historial_tareas
ADD COLUMN log_envio TEXT NULL COMMENT 'Log detallado del envío SMTP (código, mensaje, destinatario real)'
AFTER error_msg;

-- ============================================================
-- Crear tabla para configuración SMTP (editable desde admin)
-- ============================================================
CREATE TABLE IF NOT EXISTS config_smtp (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    host        VARCHAR(255) NOT NULL DEFAULT 'smtp.hostinger.com',
    puerto      INT NOT NULL DEFAULT 587,
    usuario     VARCHAR(255) NOT NULL DEFAULT '',
    password    VARCHAR(255) NOT NULL DEFAULT '',
    email_from  VARCHAR(255) NOT NULL DEFAULT 'noreply@rutasrurales.io',
    nombre_from VARCHAR(100) NOT NULL DEFAULT 'Rutas Rurales',
    seguridad   ENUM('tls','ssl','none') NOT NULL DEFAULT 'tls',
    activo      TINYINT(1) NOT NULL DEFAULT 1,
    creada_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar configuración por defecto
INSERT INTO config_smtp (host, puerto, usuario, password, email_from, nombre_from, seguridad)
VALUES ('smtp.hostinger.com', 587, '', '', 'noreply@rutasrurales.io', 'Rutas Rurales', 'tls');
