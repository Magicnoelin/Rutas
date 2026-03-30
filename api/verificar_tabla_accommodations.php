<?php
/**
 * Script para verificar y crear la tabla accommodations si no existe
 */

require_once 'config.php';

try {
    $pdo = getDBConnection();
    
    // Verificar si la tabla accommodations existe
    $sqlCheck = "SHOW TABLES LIKE 'accommodations'";
    $stmt = $pdo->query($sqlCheck);
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo json_encode([
            'success' => true,
            'message' => 'La tabla accommodations ya existe',
            'table' => 'accommodations'
        ]);
        
        // Verificar estructura de columnas
        $sqlDescribe = "DESCRIBE accommodations";
        $columns = $pdo->query($sqlDescribe)->fetchAll(PDO::FETCH_ASSOC);
        echo "\nColumnas existentes:\n";
        foreach ($columns as $col) {
            echo "- {$col['Field']} ({$col['Type']})\n";
        }
        
    } else {
        echo "La tabla accommodations no existe. Creando tabla...\n";
        
        // Crear tabla accommodations
        $sqlCreate = "
            CREATE TABLE accommodations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(255) UNIQUE,
                category_id INT DEFAULT 1,
                accommodation_type ENUM('Casa', 'Piso', 'Chalé', 'Apartamento', 'Hotel Rural') DEFAULT 'Casa',
                address TEXT,
                municipality VARCHAR(255),
                province VARCHAR(255),
                postal_code VARCHAR(20),
                registration_number VARCHAR(255),
                capacity INT DEFAULT 0,
                price_per_night DECIMAL(10,2),
                description TEXT,
                phone VARCHAR(50),
                email VARCHAR(255),
                website VARCHAR(255),
                instagram VARCHAR(255),
                booking VARCHAR(500),
                photo1 VARCHAR(500),
                photo2 VARCHAR(500),
                photo3 VARCHAR(500),
                photo4 VARCHAR(500),
                is_active TINYINT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $pdo->exec($sqlCreate);
        
        // Crear índices
        $indexes = [
            "CREATE INDEX idx_name ON accommodations(name)",
            "CREATE INDEX idx_slug ON accommodations(slug)",
            "CREATE INDEX idx_type ON accommodations(accommodation_type)",
            "CREATE INDEX idx_municipality ON accommodations(municipality)",
            "CREATE INDEX idx_active ON accommodations(is_active)"
        ];
        
        foreach ($indexes as $index) {
            $pdo->exec($index);
        }
        
        echo "✅ Tabla accommodations creada exitosamente!\n";
        
        // Insertar un alojamiento de prueba
        $sqlInsert = "
            INSERT INTO accommodations (name, slug, municipality, province, accommodation_type, capacity, address, description, phone, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = $pdo->prepare($sqlInsert);
        $stmt->execute([
            'Alojamiento de Prueba',
            'alojamiento-prueba',
            'Vinuesa',
            'Soria',
            'Casa',
            4,
            'Calle Principal 123',
            'Alojamiento de prueba para el sistema de fotos',
            '605249696',
            1
        ]);
        
        echo "✅ Alojamiento de prueba creado!\n";
        echo "Slug: alojamiento-prueba\n";
        
        echo json_encode([
            'success' => true,
            'message' => 'Tabla creada y alojamiento de prueba insertado',
            'table' => 'accommodations',
            'test_accommodation' => 'alojamiento-prueba'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al verificar/crear tabla',
        'message' => $e->getMessage()
    ]);
}
?>