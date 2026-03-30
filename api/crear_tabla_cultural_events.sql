-- Script SQL para crear la tabla cultural_events
-- Ejecutar este script en phpMyAdmin o línea de comandos

USE u412199647_Rutas;

-- Crear tabla cultural_events si no existe
CREATE TABLE IF NOT EXISTS cultural_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    event_time TIME,
    location VARCHAR(255),
    category VARCHAR(100),
    image VARCHAR(500),
    organizer VARCHAR(255),
    contact_email VARCHAR(255),
    contact_phone VARCHAR(50),
    website VARCHAR(255),
    price DECIMAL(10,2),
    capacity INT,
    status ENUM('active', 'inactive', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear índices para mejorar rendimiento
CREATE INDEX idx_title ON cultural_events(title);
CREATE INDEX idx_event_date ON cultural_events(event_date);
CREATE INDEX idx_category ON cultural_events(category);
CREATE INDEX idx_status ON cultural_events(status);

-- Insertar algunos datos de ejemplo (opcional)
INSERT INTO cultural_events (title, description, event_date, event_time, location, category, image, organizer, contact_email, status) VALUES
('Concierto de Música Clásica', 'Disfruta de una velada única con la Orquesta Sinfónica interpretando obras maestras de compositores clásicos.', '2025-01-15', '20:00:00', 'Auditorio de Soria', 'Música', 'https://images.unsplash.com/photo-1514320291840-2e0a9bf2a9ae?w=400&h=250&fit=crop', 'Ayuntamiento de Soria', 'cultura@soria.es', 'active'),
('Obra de Teatro: La Celestina', 'Representación de la obra clásica española con un elenco de actores profesionales.', '2025-01-18', '19:30:00', 'Teatro Principal, Soria', 'Teatro', 'https://images.unsplash.com/photo-1518998053901-5348d3961a04?w=400&h=250&fit=crop', 'Compañía Nacional de Teatro', 'info@teatrosoria.com', 'active'),
('Exposición de Arte Contemporáneo', 'Muestra de artistas locales con obras que reflejan la cultura y paisajes sorianos.', '2025-01-20', '10:00:00', 'Museo Numantino', 'Exposición', 'https://images.unsplash.com/photo-1460661419201-fd4cecdf8a8b?w=400&h=250&fit=crop', 'Museo Numantino', 'museo@numantino.es', 'active');

-- Verificar que la tabla se creó correctamente
SELECT 'Tabla cultural_events creada exitosamente' as mensaje;
SELECT COUNT(*) as total_cultural_events FROM cultural_events;
