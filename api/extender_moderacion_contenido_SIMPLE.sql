-- =====================================================
-- EXTENDER SISTEMA DE MODERACIÓN - VERSIÓN SIMPLE
-- =====================================================
-- Ejecutar en phpMyAdmin
-- Este script crea las tablas SIN las vistas (las vistas se crearán después)

USE u412199647_Rutas;

-- =====================================================
-- 1. EXTENDER TABLA CULTURAL_EVENTS
-- =====================================================

ALTER TABLE cultural_events 
ADD COLUMN IF NOT EXISTS moderation_status ENUM('draft', 'pending', 'approved', 'rejected') DEFAULT 'draft',
ADD COLUMN IF NOT EXISTS created_by INT NULL,
ADD COLUMN IF NOT EXISTS reviewed_by INT NULL,
ADD COLUMN IF NOT EXISTS reviewed_at DATETIME NULL,
ADD COLUMN IF NOT EXISTS rejection_reason TEXT NULL,
ADD COLUMN IF NOT EXISTS published_at DATETIME NULL,
ADD COLUMN IF NOT EXISTS last_submitted_at DATETIME NULL,
ADD COLUMN IF NOT EXISTS slug VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS municipality VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS province VARCHAR(255) NULL;

-- =====================================================
-- 2. CREAR TABLA ACTIVITIES
-- =====================================================

CREATE TABLE IF NOT EXISTS activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    activity_type VARCHAR(100),
    difficulty ENUM('fácil', 'moderada', 'difícil') DEFAULT 'moderada',
    duration VARCHAR(100),
    address VARCHAR(255),
    municipality VARCHAR(255) NOT NULL,
    province VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    price DECIMAL(10,2),
    min_participants INT,
    max_participants INT,
    season VARCHAR(100),
    phone VARCHAR(50),
    email VARCHAR(255),
    website VARCHAR(255),
    booking_url VARCHAR(500),
    photo1 VARCHAR(500),
    photo2 VARCHAR(500),
    photo3 VARCHAR(500),
    photo4 VARCHAR(500),
    moderation_status ENUM('draft', 'pending', 'approved', 'rejected') DEFAULT 'draft',
    created_by INT,
    reviewed_by INT,
    reviewed_at DATETIME,
    rejection_reason TEXT,
    published_at DATETIME,
    last_submitted_at DATETIME,
    is_active BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_municipality (municipality),
    INDEX idx_moderation_status (moderation_status),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. CREAR TABLA PLACES_OF_INTEREST
-- =====================================================

CREATE TABLE IF NOT EXISTS places_of_interest (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    place_type VARCHAR(100),
    address VARCHAR(255),
    municipality VARCHAR(255) NOT NULL,
    province VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    opening_hours TEXT,
    entry_fee DECIMAL(10,2),
    accessibility TEXT,
    phone VARCHAR(50),
    email VARCHAR(255),
    website VARCHAR(255),
    photo1 VARCHAR(500),
    photo2 VARCHAR(500),
    photo3 VARCHAR(500),
    photo4 VARCHAR(500),
    moderation_status ENUM('draft', 'pending', 'approved', 'rejected') DEFAULT 'draft',
    created_by INT,
    reviewed_by INT,
    reviewed_at DATETIME,
    rejection_reason TEXT,
    published_at DATETIME,
    last_submitted_at DATETIME,
    is_active BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_municipality (municipality),
    INDEX idx_moderation_status (moderation_status),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. CREAR TABLA DE HISTORIAL
-- =====================================================

CREATE TABLE IF NOT EXISTS content_moderation_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content_type ENUM('accommodation', 'event', 'activity', 'place') NOT NULL,
    content_id INT NOT NULL,
    accommodation_id INT NULL,
    action ENUM('created', 'submitted', 'approved', 'rejected', 'updated') NOT NULL,
    performed_by INT NOT NULL,
    previous_status VARCHAR(50),
    new_status VARCHAR(50),
    notes TEXT,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_content (content_type, content_id),
    INDEX idx_performed_by (performed_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. EXTENDER NOTIFICACIONES
-- =====================================================

ALTER TABLE moderation_notifications
ADD COLUMN IF NOT EXISTS content_type ENUM('accommodation', 'event', 'activity', 'place') DEFAULT 'accommodation',
ADD COLUMN IF NOT EXISTS content_id INT NULL;

-- =====================================================
-- VERIFICACIÓN
-- =====================================================

SELECT 'Tablas creadas correctamente' as status;

SELECT 
    'activities' as tabla,
    COUNT(*) as registros
FROM activities
UNION ALL
SELECT 
    'places_of_interest' as tabla,
    COUNT(*) as registros
FROM places_of_interest;
