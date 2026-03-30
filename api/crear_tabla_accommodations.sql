-- Script SQL para crear la tabla accommodations en la base de datos Rutas
-- Ejecutar este script en phpMyAdmin o línea de comandos

USE u412199647_Rutas;

-- Crear tabla accommodations si no existe
CREATE TABLE IF NOT EXISTS accommodations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(100),
    address TEXT,
    capacity INT DEFAULT 0,
    price DECIMAL(10,2),
    description TEXT,
    phone VARCHAR(50),
    email VARCHAR(255),
    website VARCHAR(255),
    image1 VARCHAR(500),
    image2 VARCHAR(500),
    image3 VARCHAR(500),
    image4 VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear índices para mejorar rendimiento
CREATE INDEX idx_name ON accommodations(name);
CREATE INDEX idx_type ON accommodations(type);
CREATE INDEX idx_status ON accommodations(status);

-- Insertar algunos datos de ejemplo (opcional)
INSERT INTO accommodations (name, type, address, capacity, price, description, phone, email, website, image1, status) VALUES
('Hotel Rural El Mirador', 'Hotel', 'Calle Mayor 15, Vinuesa, Soria', 20, 85.00, 'Hotel rural con vistas espectaculares a la Laguna Negra. Habitaciones confortables con baño privado.', '+34 975 123 456', 'info@hotelruralmirador.com', 'https://hotelruralmirador.com', 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400&h=300&fit=crop', 'active'),
('Apartamentos Turísticos Centro', 'Apartamento', 'Plaza Mayor 8, Soria Capital', 6, 65.00, 'Apartamentos modernos en el centro histórico de Soria. Perfectos para familias o grupos.', '+34 975 654 321', 'reservas@apartamentossoria.com', 'https://apartamentossoria.com', 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=400&h=300&fit=crop', 'active'),
('Casa Rural La Encina', 'Casa', 'Camino Viejo 25, Santervás de la Sierra, Soria', 8, 120.00, 'Casa rural tradicional con encanto. Chimenea, jardín privado y vistas a la montaña.', '+34 975 987 654', 'contacto@casaruralaencina.com', 'https://casaruralaencina.com', 'https://images.unsplash.com/photo-1449844908441-8829872d2607?w=400&h=300&fit=crop', 'active'),
('Chalé del Bosque', 'Chalé', 'Sendero del Roble s/n, Deza, Soria', 4, 95.00, 'Chalé acogedor rodeado de naturaleza. Ideal para parejas que buscan tranquilidad.', '+34 975 321 987', 'info@chaledelbosque.com', 'https://chaledelbosque.com', 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=400&h=300&fit=crop', 'active');

-- Verificar que la tabla se creó correctamente
SELECT 'Tabla accommodations creada exitosamente' as mensaje;
SELECT COUNT(*) as total_accommodations FROM accommodations;
